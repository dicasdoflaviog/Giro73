#!/usr/bin/env python3
"""Fotografias editoriais: origem/licenca Commons, WebP1200x675 <60KB, upload opcional.

Exige manifesto humano revisado com post_id, categoria, commons_title, keyword,
alt_text e caption. Sem --upload, apenas baixa/converte. Nunca desenha textos,
cria artigos ou inventa licencas. A biblioteca requests e Pillow sao necessarias.
"""
import argparse,base64,getpass,hashlib,html,io,json,os,ssl,sys
import urllib.error,urllib.parse,urllib.request
from html.parser import HTMLParser
from pathlib import Path
from PIL import Image,ImageOps,ImageFilter

SIZE=(1200,675)
MAX_BYTES=60_000
EDITORIAS={x:None for x in ('noticias-regiao','dinheiro-renda-extra','tecnologia-inovacao','esportes','famosos-entretenimento')}
COMMONS='https://commons.wikimedia.org/w/api.php'
USER_AGENT='Giro73Editorial/1.0 (+https://giro73.com.br/contato/)'
class PlainText(HTMLParser):
    def __init__(self):super().__init__();self.parts=[]
    def handle_data(self,data):self.parts.append(data)
def plain(value):
    p=PlainText();p.feed(value);return ' '.join(' '.join(p.parts).split())
def get_url(session,url,params=None,limit=12_000_000):
    parsed=urllib.parse.urlsplit(url)
    if parsed.scheme!='https' or parsed.hostname not in ('commons.wikimedia.org','upload.wikimedia.org'):
        raise ValueError('Somente fontes HTTPS oficiais do Wikimedia Commons sao aceitas.')
    # Redirecionamentos recusados: reavaliar qualquer novo destino explicitamente.
    with session.get(url,params=params,timeout=25,stream=True,allow_redirects=False) as r:
        r.raise_for_status()
        if r.status_code!=200:raise ValueError('Origem respondeu sem arquivo final HTTP200.')
        data=bytearray()
        for chunk in r.iter_content(65536):
            data.extend(chunk)
            if len(data)>limit:raise ValueError('Arquivo excede o limite de download.')
        return bytes(data)
def licensed_source(session,title):
    if not title.startswith('File:'):raise ValueError('commons_title deve ser o titulo File: exato revisado.')
    data=json.loads(get_url(session,COMMONS,{'action':'query','format':'json','titles':title,'prop':'imageinfo','iiprop':'url|size|mime|extmetadata','iiurlwidth':1600}))
    pages=list(data['query']['pages'].values())
    if len(pages)!=1 or not pages[0].get('imageinfo'):raise ValueError('Fotografia nao encontrada no Commons.')
    info=pages[0]['imageinfo'][0];meta=info.get('extmetadata',{})
    value=lambda key:plain(meta.get(key,{}).get('value',''))
    license_url=value('LicenseUrl');parsed=urllib.parse.urlsplit(license_url)
    license_path=parsed.path.rstrip('/')
    if '/deed.' in license_path:license_path=license_path.rsplit('/deed.',1)[0]
    allowed_paths={'/publicdomain/zero/1.0','/publicdomain/mark/1.0'}|{f'/licenses/{kind}/{version}' for kind in ('by','by-sa') for version in ('2.0','2.5','3.0','4.0')}
    if parsed.hostname not in ('creativecommons.org','www.creativecommons.org') or license_path not in allowed_paths:
        raise ValueError('Licenca ausente ou fora das licencas livres aceitas; revisar a origem.')
    author=value('Artist')
    if not author:raise ValueError('Autor ausente; nao publicar sem atribuicao verificada.')
    if info.get('mime') not in ('image/jpeg','image/png') or info['width']<1200 or info['height']<675:
        raise ValueError('Exige fotografia JPEG/PNG com resolucao suficiente para1200x675.')
    source_page=info['descriptionurl']
    if urllib.parse.urlsplit(source_page).hostname!='commons.wikimedia.org':raise ValueError('Pagina fonte inesperada.')
    return info,{'source_page':source_page,'author':author,'license':value('LicenseShortName'),'license_url':license_url,'changes':'Recorte16:9, redimensionamento e compressao WebP.'}
def convert_photo(content,spec,directory):
    with Image.open(io.BytesIO(content)) as original:
        if original.format not in ('JPEG','PNG'):raise ValueError('Arquivo baixado nao e JPEG/PNG.')
        image=ImageOps.exif_transpose(original).convert('RGB')
        if image.width<1200 or image.height<675:raise ValueError('Nao ampliar fotografias pequenas.')
        center=spec.get('crop_center',[.5,.5])
        if len(center)!=2 or any(type(v) not in (int,float) or not 0<=v<=1 for v in center):raise ValueError('crop_center invalido.')
        image=ImageOps.fit(image,SIZE,method=Image.Resampling.LANCZOS,centering=tuple(center))
        radius=spec.get('denoise_radius',0)
        if type(radius) not in (int,float) or not 0<=radius<=.8:raise ValueError('Suavizacao maxima permitida:0.8px.')
        if radius:image=image.filter(ImageFilter.GaussianBlur(radius))
        for quality in (86,82,78,74,70,66,62,58,54,50,46,42,38,34,30):
            output=io.BytesIO();image.save(output,'WEBP',quality=quality,method=6)
            data=output.getvalue()
            if len(data)<MAX_BYTES:break
        else:raise ValueError('Fotografia nao coube em60KB; revisar recorte.')
    digest=hashlib.sha256(data).hexdigest();slug=f"giro73-foto-{spec['post_id']}-{digest[:12]}"
    path=directory/(slug+'.webp');path.write_bytes(data)
    with Image.open(path) as check:
        assert check.format=='WEBP' and check.size==SIZE;check.load()
    return {'post_id':spec['post_id'],'path':str(path),'slug':slug,'sha256':digest,'bytes':len(data),'quality':quality,'width':1200,'height':675,'style':'photograph','denoise_radius':radius,'source_sha256':hashlib.sha256(content).hexdigest()}
def attribution(provenance):
    p={k:html.escape(v,quote=True) for k,v in provenance.items()}
    return f'Foto: {p["author"]} / <a href="{p["source_page"]}">Wikimedia Commons</a> · <a href="{p["license_url"]}">{p["license"]}</a>. {p["changes"]}'


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args):return None


class WordPress:
    def __init__(self, site, user, password):
        parsed=urllib.parse.urlsplit(site)
        if parsed.scheme!="https" or not parsed.hostname or parsed.username or parsed.password or parsed.query or parsed.fragment:
            raise ValueError("Use a URL HTTPS do WordPress, sem credenciais ou query string.")
        self.site=site.rstrip("/")
        self.auth="Basic "+base64.b64encode((user+":"+password.replace(" ","")).encode()).decode()
        self.opener=urllib.request.build_opener(NoRedirect())

    def request(self,path,method="GET",data=None,binary=None,filename=None):
        headers={"Accept":"application/json","Authorization":self.auth}
        body=None
        if data is not None:headers["Content-Type"]="application/json";body=json.dumps(data).encode()
        if binary is not None:
            headers.update({"Content-Type":"image/webp","Content-Disposition":f'attachment; filename="{filename}"'})
            body=binary
        req=urllib.request.Request(self.site+"/wp-json/wp/v2/"+path,body,headers,method=method)
        try:
            with self.opener.open(req,timeout=30) as response:return json.load(response)
        except urllib.error.HTTPError as error:
            raise RuntimeError(f"WordPress HTTP {error.code} em {path.split('?')[0]}; nenhuma credencial será impressa.") from None

    def attach(self,spec,result,categories):
        post=self.request(f"posts/{spec['post_id']}?context=edit")
        if post["status"] not in ("publish","draft","pending","future") or categories[spec["categoria"]] not in post["categories"]:
            raise ValueError(f"Post {spec['post_id']}: status/categoria não correspondem ao manifesto.")
        matches=self.request("media?context=edit&per_page=10&slug="+urllib.parse.quote(result["slug"]))
        metadata={"slug":result["slug"],"post":post["id"],"title":spec["keyword"]+" | Giro73",
                  "alt_text":spec["alt_text"],"caption":spec["caption"]}
        if matches:
            if len(matches)!=1:raise ValueError("Slug de mídia ambíguo.")
            media=matches[0]
            if media["mime_type"]!="image/webp" or (media["media_details"]["width"],media["media_details"]["height"])!=SIZE:
                raise ValueError("Mídia existente tem formato/dimensões inesperados.")
            reused=True
        else:
            media=self.request("media",method="POST",binary=Path(result["path"]).read_bytes(),filename=Path(result["path"]).name)
            reused=False
        changed=(media.get("alt_text")!=metadata["alt_text"] or media.get("post")!=post["id"] or
                 media.get("title",{}).get("raw")!=metadata["title"] or
                 media.get("caption",{}).get("raw")!=metadata["caption"] or media.get("slug")!=metadata["slug"])
        if changed:media=self.request(f"media/{media['id']}","POST",metadata)
        if post["featured_media"]!=media["id"]:
            self.request(f"posts/{post['id']}","POST",{"featured_media":media["id"]})
        verified=self.request(f"posts/{post['id']}?context=edit")
        for key in ("title","slug","content","categories","status","date"):
            # rendered pode mudar por plugins; texto bruto e dados autorais não.
            before,after=post[key],verified[key]
            if isinstance(before,dict):before,after=before.get("raw"),after.get("raw")
            if before!=after:raise ValueError(f"Post {post['id']}: alteração concorrente em {key}; confira antes de prosseguir.")
        if verified["featured_media"]!=media["id"]:raise ValueError("Vínculo de imagem não persistiu.")
        source=urllib.parse.urlsplit(media["source_url"])
        origin=urllib.parse.urlsplit(self.site)
        if source.scheme!="https" or source.netloc!=origin.netloc:
            raise ValueError("Verificação de mídia exige URL HTTPS da mesma origem; confira seu CDN.")
        # Releitura pública sem Authorization: confirma o arquivo efetivamente servido.
        with self.opener.open(media["source_url"],timeout=30) as response:
            remote=response.read(MAX_BYTES+1)
        if len(remote)>=MAX_BYTES:raise ValueError("Imagem remota excede o limite de 60.000 bytes.")
        if hashlib.sha256(remote).hexdigest()!=result["sha256"]:raise ValueError("Arquivo remoto difere da fotografia local.")
        with Image.open(io.BytesIO(remote)) as check:
            if check.size!=SIZE or check.format!="WEBP":raise ValueError("Imagem remota fora da especificação.")
            check.load()
        result.update({"media_id":media["id"],"source_url":media["source_url"],"post_url":verified["link"],
                       "reused":reused,"featured_media_before":post["featured_media"],"remote_bytes":len(remote),"metadata":metadata})
        return result


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--manifest',type=Path,required=True,help='Manifesto revisado das fotografias; nao ha desenhos padrao.')
    parser.add_argument('--output-dir',type=Path,default=Path('/tmp/giro73-fotos'))
    parser.add_argument('--upload',action='store_true')
    parser.add_argument('--site-url',default='https://giro73.com.br')
    parser.add_argument('--user',default=os.environ.get('WORDPRESS_USERNAME'))
    parser.add_argument('--password-stdin',action='store_true')
    args=parser.parse_args()
    import requests
    specs=json.loads(args.manifest.read_text());seen=set()
    if not isinstance(specs,list) or not specs:raise ValueError('Manifesto vazio.')
    for spec in specs:
        pid=spec.get('post_id')
        if type(pid) is not int or pid<=0 or pid in seen:raise ValueError('IDs invalidos ou repetidos.')
        seen.add(pid)
        if spec.get('categoria') not in EDITORIAS:raise ValueError('Editoria fora da Fase1.')
        for field in ('commons_title','keyword','alt_text','caption'):
            if not isinstance(spec.get(field),str) or not spec[field].strip():raise ValueError(f'Campo obrigatorio: {field}')
            if any(c in spec[field] for c in '<>\r\n'):raise ValueError(f'Texto simples exigido em {field}')
    args.output_dir.mkdir(parents=True,exist_ok=True)
    session=requests.Session();session.headers['User-Agent']=USER_AGENT
    session.verify=ssl.get_default_verify_paths().cafile or True
    results=[]
    for spec in specs:
        info,provenance=licensed_source(session,spec['commons_title'])
        content=get_url(session,info['url'])
        result=convert_photo(content,spec,args.output_dir)
        if result['denoise_radius']:provenance['changes']+=' Suavizacao leve de ruido.'
        result['provenance']=provenance
        credit=attribution(provenance)
        spec['caption']=html.escape(spec['caption'])+' '+credit
        result['visible_credit_html']=credit
        results.append(result)
        print(f"Preparada fotografia: post {spec['post_id']}, {result['bytes']} bytes")
    (args.output_dir/'manifesto-geracao.json').write_text(json.dumps(results,ensure_ascii=False,indent=2)+'\n')
    if args.upload:
        if not args.user:raise ValueError('Informe --user ou WORDPRESS_USERNAME.')
        password=os.environ.get('WORDPRESS_APP_PASSWORD')
        if not password:password=sys.stdin.readline().strip() if args.password_stdin else getpass.getpass('Senha de aplicativo: ')
        if not password:raise ValueError('Senha ausente.')
        client=WordPress(args.site_url,args.user,password)
        categories={x['slug']:x['id'] for x in client.request('categories?per_page=100&_fields=id,slug')}
        for spec,result in zip(specs,results):
            if spec['categoria'] not in categories:raise ValueError('Editoria ausente no site.')
            client.attach(spec,result,categories)
            post=client.request(f"posts/{spec['post_id']}?context=edit")
            raw=post['content']['raw'];start='<!-- wp:html -->\n<!-- giro73-photo-credit:start -->'
            end='<!-- giro73-photo-credit:end -->\n<!-- /wp:html -->\n'
            base=raw
            if raw.startswith(start):
                if end not in raw:raise ValueError('Credito anterior incompleto; revisar conteudo.')
                base=raw.split(end,1)[1]
            credit=start+'\n<p class="g73-photo-credit g73-meta">'+result['visible_credit_html']+'</p>\n'+end
            desired=credit+base
            if raw!=desired:client.request(f"posts/{post['id']}",'POST',{'content':desired})
            verified=client.request(f"posts/{post['id']}?context=edit")
            if verified['content']['raw']!=desired:raise ValueError('Credito nao persistiu exatamente.')
            for key in ('title','slug','categories','status','date','featured_media'):
                before,after=post[key],verified[key]
                if isinstance(before,dict):before,after=before.get('raw'),after.get('raw')
                if before!=after:raise ValueError(f'Alteracao concorrente em {key}.')
            (args.output_dir/'manifesto-upload.json').write_text(json.dumps(results,ensure_ascii=False,indent=2)+'\n')
            print(f"Vinculada: post {post['id']}, midia {result['media_id']}; credito visivel incluido.")
    print('Concluido; nenhum artigo ou pagina criado.')


if __name__=='__main__':
    try:main()
    except (ValueError,RuntimeError,urllib.error.URLError) as error:
        print(f'Falha: {error}',file=sys.stderr);sys.exit(1)
