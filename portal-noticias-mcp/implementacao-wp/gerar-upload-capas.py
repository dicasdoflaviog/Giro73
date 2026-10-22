#!/usr/bin/env python3
"""Capas originais Giro73: Pillow, WebP <50.000 bytes e upload REST idempotente.

Sem --upload apenas gera arquivos. Credenciais: variaveis de ambiente ou prompt;
--password-stdin permite entrada segura por um gerenciador de segredos.
Nunca cria posts/paginas, altera texto ou grava credenciais no manifesto.
"""

import argparse
import base64
import getpass
import hashlib
import io
import json
import os
from pathlib import Path
import sys
import urllib.error
import urllib.parse
import urllib.request

from PIL import Image, ImageDraw, ImageFont

SIZE = (1200, 675)
MAX_BYTES = 50_000
PALETTE = {
    "yellow": "#FFEA02", "amber": "#FFBE0B", "purple": "#2C1067",
    "cyan": "#03F1F4", "blue": "#3A86FF", "pink": "#FF006E",
    "orange": "#FB5607", "black": "#000000", "white": "#FFFFFF",
}
EDITORIAS = {
    "noticias-regiao": ("NOTÍCIAS & REGIÃO", PALETTE["pink"], "cidade"),
    "dinheiro-renda-extra": ("DINHEIRO & RENDA EXTRA", PALETTE["blue"], "financas"),
    "tecnologia-inovacao": ("TECNOLOGIA & INOVAÇÃO", PALETTE["purple"], "tecnologia"),
    "esportes": ("ESPORTES", PALETTE["amber"], "esportes"),
    "famosos-entretenimento": ("FAMOSOS & ENTRETENIMENTO", PALETTE["orange"], "tv"),
}
DEFAULT_POSTS = [
    {"post_id": 20, "categoria": "noticias-regiao",
     "headline": ["EXTREMO SUL", "DA BAHIA"], "apoio": "Economia e infraestrutura",
     "keyword": "economia do Extremo Sul da Bahia",
     "alt_text": "Ilustração sobre a economia do Extremo Sul da Bahia, com cidade e conexões de infraestrutura.",
     "caption": "Economia do Extremo Sul da Bahia: ilustração original Giro73. Elementos abstratos; não é mapa geográfico."},
    {"post_id": 21, "categoria": "dinheiro-renda-extra",
     "headline": ["ORGANIZE", "SUAS FINANÇAS"], "apoio": "Planejamento, renda e crédito",
     "keyword": "planejamento financeiro",
     "alt_text": "Ilustração de planejamento financeiro com calculadora, orçamento e organização de despesas.",
     "caption": "Planejamento financeiro: capa original Giro73 sobre orçamento, renda extra e análise responsável do crédito."},
    {"post_id": 22, "categoria": "tecnologia-inovacao",
     "headline": ["INTELIGÊNCIA", "ARTIFICIAL"], "apoio": "Trabalho e pequenos negócios",
     "keyword": "inteligência artificial",
     "alt_text": "Ilustração de inteligência artificial com computador e nós conectados para representar ferramentas digitais.",
     "caption": "Inteligência artificial no trabalho e nos pequenos negócios: ilustração original Giro73."},
    {"post_id": 23, "categoria": "esportes",
     "headline": ["FUTEBOL", "EM PAUTA"], "apoio": "Brasil, Bahia e o torcedor",
     "keyword": "futebol brasileiro e baiano",
     "alt_text": "Ilustração sobre futebol brasileiro e baiano com campo, bola e elementos de calendário, sem clubes ou datas reais.",
     "caption": "Futebol brasileiro e baiano: ilustração original Giro73 sobre competições e planejamento da torcida."},
    {"post_id": 24, "categoria": "famosos-entretenimento",
     "headline": ["TV E", "STREAMING"], "apoio": "Conteúdo, escolhas e audiência",
     "keyword": "TV e streaming no Brasil",
     "alt_text": "Ilustração de TV e streaming no Brasil com telas e botão de reprodução, sem cenas ou marcas de terceiros.",
     "caption": "TV e streaming no Brasil: ilustração original Giro73 sobre entretenimento e escolhas do público."},
]


def font(size, bold=False):
    candidates = [
        Path("/usr/share/fonts/truetype/dejavu") / ("DejaVuSans-Bold.ttf" if bold else "DejaVuSans.ttf"),
        Path("/usr/share/fonts/truetype/liberation2") / ("LiberationSans-Bold.ttf" if bold else "LiberationSans-Regular.ttf"),
    ]
    for path in candidates:
        if path.is_file():
            return ImageFont.truetype(str(path), size)
    raise RuntimeError("Instale DejaVu Sans ou Liberation Sans; não são baixadas fontes externas.")


def draw_art(draw, kind, color, clean=False):
    """Primitivas próprias para as ilustrações editoriais."""
    ink, paper = PALETTE["purple"], PALETTE["white"]
    if kind == "cidade":
        for x, top, width in [(735, 310, 95), (850, 220, 115), (985, 280, 100)]:
            draw.rounded_rectangle((x, top, x+width, 480), 10, fill=paper, outline=ink, width=4)
            for xx in range(x+20, x+width-10, 29):
                for yy in range(top+25, 455, 37):
                    draw.rectangle((xx, yy, xx+10, yy+13), fill=color)
        draw.line([(720, 525), (825, 505), (945, 535), (1100, 490)], fill=color, width=8)
        for x, y in [(720, 525), (825, 505), (945, 535), (1100, 490)]:
            draw.ellipse((x-9, y-9, x+9, y+9), fill=paper, outline=ink, width=3)
    elif kind == "financas":
        draw.rounded_rectangle((720, 225, 925, 510), 20, fill=paper, outline=ink, width=4)
        draw.rounded_rectangle((742, 249, 902, 312), 8, fill="#F4F5F8" if clean else PALETTE["cyan"])
        draw.line((762, 283, 882, 283), fill=color, width=5)
        for x in (747, 803, 859):
            for y in (339, 391, 443):
                draw.rounded_rectangle((x, y, x+37, y+32), 5, fill=color)
        draw.rounded_rectangle((948, 277, 1100, 469), 12, fill=paper, outline=ink, width=4)
        for y, length in ((315, 96), (356, 72), (397, 88)):
            draw.line((968, y, 968+length, y), fill=color, width=7)
        draw.ellipse((999, 478, 1070, 549), fill=color)
        draw.line((1018, 515, 1033, 529, 1054, 501), fill=paper, width=6)
    elif kind == "tecnologia":
        points = [(760, 230), (1060, 230), (1100, 475), (730, 470)]
        for x, y in points:
            draw.line((910, 350, x, y), fill=color, width=5)
            draw.ellipse((x-22, y-22, x+22, y+22), fill=paper, outline=ink, width=4)
        draw.rounded_rectangle((790, 280, 1030, 443), 15, fill=paper, outline=ink, width=5)
        draw.rounded_rectangle((863, 318, 957, 405), 9, fill=color)
        for x in (881, 908, 935):
            draw.line((x, 305, x, 318), fill=ink, width=4)
            draw.line((x, 405, x, 418), fill=ink, width=4)
        draw.line((910, 443, 910, 488), fill=ink, width=6)
        draw.line((850, 488, 970, 488), fill=ink, width=6)
    elif kind == "esportes":
        field_ink = paper if clean else PALETTE["black"]
        draw.rounded_rectangle((714, 250, 1110, 495), 17, fill=color, outline=ink, width=4)
        draw.rectangle((738, 273, 1086, 472), outline=field_ink, width=4)
        draw.line((912, 273, 912, 472), fill=field_ink, width=4)
        draw.ellipse((867, 328, 957, 418), outline=field_ink, width=4)
        draw.rectangle((738, 321, 791, 424), outline=field_ink, width=4)
        draw.rectangle((1033, 321, 1086, 424), outline=field_ink, width=4)
        draw.ellipse((970, 455, 1050, 535), fill=paper, outline=ink, width=4)
        draw.regular_polygon((1010, 495, 18), 5, rotation=0, fill=ink)
        draw.rounded_rectangle((744, 175, 835, 226), 8, fill=paper, outline=ink, width=3)
        draw.line((744, 194, 835, 194), fill=ink, width=3)
        for x in (765, 786, 807):
            draw.ellipse((x-3, 207, x+3, 213), fill=color)
    else:
        draw.rounded_rectangle((728, 237, 1094, 462), 20, fill=paper, outline=ink, width=5)
        draw.rounded_rectangle((748, 257, 1074, 442), 10, fill=color)
        draw.polygon([(882, 300), (882, 398), (961, 349)], fill=paper)
        draw.line((910, 462, 910, 492), fill=ink, width=5)
        draw.line((844, 492, 976, 492), fill=ink, width=5)
        draw.rounded_rectangle((998, 382, 1117, 551), 16, fill=paper, outline=ink, width=4)
        draw.rounded_rectangle((1011, 404, 1104, 523), 5, fill="#F4F5F8" if clean else PALETTE["yellow"])
        draw.polygon([(1044, 438), (1044, 490), (1080, 464)], fill=color)


class ScaledDraw:
    """Desenho em 2x, com redução final para contornos suaves."""
    def __init__(self, image):
        self.image = image

    def __getattr__(self, name):
        operation = getattr(ImageDraw.Draw(self.image), name)
        def call(coords, *args, **kwargs):
            def scale(value):
                if isinstance(value, (tuple, list)):return tuple(scale(x) for x in value)
                return value*2
            if name=="rounded_rectangle":args=(args[0]*2,)+args[1:]
            if "width" in kwargs:kwargs["width"]*=2
            return operation(scale(coords),*args,**kwargs)
        return call


def render_illustration(kind):
    """Apenas formas gráficas: sem títulos, legendas, URLs, fontes ou marcas."""
    layer=Image.new("RGBA",(2400,1350),(0,0,0,0))
    draw_art(ScaledDraw(layer),kind,PALETTE["purple"],clean=True)
    art=layer.crop(layer.getbbox())
    ratio=min(1700/art.width,950/art.height)
    art=art.resize((round(art.width*ratio),round(art.height*ratio)),Image.Resampling.LANCZOS)
    image=Image.new("RGB",(2400,1350),PALETTE["white"])
    ImageDraw.Draw(image).ellipse((320,120,2080,1230),fill="#F4F5F8")
    image.paste(art,((2400-art.width)//2,(1350-art.height)//2),art)
    return image


def generate(spec, directory, seal=None, clean=True):
    label, color, kind = EDITORIAS[spec["categoria"]]
    if clean:
        return save_cover(render_illustration(kind),spec,directory,False,"illustration")
    image=Image.new("RGB",(2400,1350),PALETTE["white"])
    draw=ScaledDraw(image)
    draw.rectangle((0, 0, 1200, 14), fill=color)
    draw.rounded_rectangle((40, 38, 160, 90), 8, fill=PALETTE["yellow"])
    draw.text((64, 46), "GUIA", font=font(42, True), fill=PALETTE["black"])
    draw.text((190, 49), label, font=font(40, True), fill=PALETTE["purple"])
    draw.ellipse((675, 150, 1160, 615), fill=PALETTE["cyan"] if kind=="tecnologia" else PALETTE["yellow"])
    draw_art(draw, kind, color)
    title_size=64
    for line in spec["headline"]:
        if ImageDraw.Draw(image).textbbox((0,0), line, font=font(title_size*2,True))[2]>1180:
            raise ValueError("Título da capa longo demais; forneça linhas curtas no manifesto.")
    for index, line in enumerate(spec["headline"]):
        draw.text((48, 242+index*78), line, font=font(title_size*2,True), fill=PALETTE["black"])
    draw.rectangle((50, 435, 152, 443), fill=color)
    draw.text((48, 470), spec["apoio"], font=font(46), fill=PALETTE["purple"])
    draw.text((48, 610), "GIRO73.COM.BR  /  ILUSTRAÇÃO EDITORIAL", font=font(28,True), fill=PALETTE["purple"])
    if seal is not None:
        # Marca original fornecida pela redação; sem redesenho ou recoloração.
        mark=seal.copy()
        ratio=min(340/mark.width,160/mark.height)
        mark=mark.resize((max(1,round(mark.width*ratio)),max(1,round(mark.height*ratio))),Image.Resampling.LANCZOS)
        x,y=2320-mark.width,1306-mark.height
        draw.rounded_rectangle(((x-16)/2,(y-12)/2,1172,659),8,fill=PALETTE["white"])
        image.paste(mark,(x,y),mark)
    return save_cover(image,spec,directory,seal is not None,"classic")


def save_cover(image,spec,directory,has_seal,style):
    image=image.resize(SIZE, Image.Resampling.LANCZOS)
    for quality in (84, 80, 76, 72, 68, 64):
        buffer=io.BytesIO();image.save(buffer,"WEBP",quality=quality,method=6)
        content=buffer.getvalue()
        if len(content)<MAX_BYTES:break
    else:raise ValueError("Não foi possível manter a imagem legível abaixo de 50.000 bytes.")
    digest=hashlib.sha256(content).hexdigest()
    filename=f"giro73-pilar-{spec['post_id']}-{digest[:12]}.webp"
    path=directory/filename;path.write_bytes(content)
    with Image.open(path) as check:
        if check.size!=SIZE or check.format!="WEBP":raise ValueError("Formato inválido.")
        check.load()
    return {"post_id":spec["post_id"],"path":str(path),"slug":path.stem,
            "sha256":digest,"bytes":len(content),"width":1200,"height":675,"quality":quality,
            "official_seal":has_seal,"style":style}


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
        if len(remote)>=MAX_BYTES:raise ValueError("Imagem remota excede o limite de 50.000 bytes.")
        with Image.open(io.BytesIO(remote)) as check:
            if check.size!=SIZE or check.format!="WEBP":raise ValueError("Imagem remota fora da especificação.")
            check.load()
        result.update({"media_id":media["id"],"source_url":media["source_url"],"post_url":verified["link"],
                       "reused":reused,"featured_media_before":post["featured_media"],"remote_bytes":len(remote),"metadata":metadata})
        return result


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output-dir",type=Path,default=Path("/tmp/giro73-capas"))
    parser.add_argument("--manifest",type=Path,help="JSON com post_id, categoria, headline, apoio, keyword, alt_text e caption.")
    parser.add_argument("--seal",type=Path,help="Selo original, usado somente no modo --classic.")
    parser.add_argument("--classic",action="store_true",help="Composição antiga com textos; o padrão é apenas ilustração.")
    parser.add_argument("--upload",action="store_true",help="Upload e vínculo; nunca cria posts ou páginas.")
    parser.add_argument("--site-url",default="https://giro73.com.br")
    parser.add_argument("--user",default=os.environ.get("WORDPRESS_USERNAME"))
    parser.add_argument("--password-stdin",action="store_true")
    args=parser.parse_args()
    if args.classic and not args.seal:
        print("Marca pendente: capas sem selo oficial; forneça --seal quando o PNG original estiver disponível.",flush=True)
    seal=None
    if args.seal:
        with Image.open(args.seal) as original:
            if original.format!="PNG":raise ValueError("Forneça o selo oficial em PNG.")
            seal=original.convert("RGBA")
    specs=json.loads(args.manifest.read_text()) if args.manifest else DEFAULT_POSTS
    if not isinstance(specs,list) or not specs:raise ValueError("O manifesto deve conter uma lista não vazia.")
    seen=set()
    for spec in specs:
        if type(spec.get("post_id")) is not int or spec["post_id"]<=0 or spec["post_id"] in seen:
            raise ValueError("IDs de posts devem ser positivos e únicos.")
        seen.add(spec["post_id"])
        if spec["categoria"] not in EDITORIAS:raise ValueError("Editoria fora da Fase 1.")
        if not isinstance(spec["headline"],list) or not 1<=len(spec["headline"])<=2:raise ValueError("Use uma ou duas linhas de título.")
        if not all(isinstance(line,str) and line.strip() for line in spec["headline"]):raise ValueError("Linhas de título inválidas.")
        for field in ("apoio","keyword","alt_text","caption"):
            if not isinstance(spec.get(field),str) or not spec[field].strip():raise ValueError(f"Campo obrigatório: {field}")
            if any(char in spec[field] for char in "<>\r\n"):raise ValueError(f"Use texto simples no campo {field}.")
    args.output_dir.mkdir(parents=True,exist_ok=True)
    results=[]
    for spec in specs:
        result=generate(spec,args.output_dir,seal,clean=not args.classic);results.append(result)
        print(f"Gerada: post {spec['post_id']}, 1200x675, {result['bytes']} bytes, qualidade {result['quality']}",flush=True)
    client=None
    if args.upload:
        if not args.user:raise ValueError("Informe --user ou WORDPRESS_USERNAME.")
        password=os.environ.get("WORDPRESS_APP_PASSWORD")
        if not password:
            password=sys.stdin.readline().strip() if args.password_stdin else getpass.getpass("Senha de aplicativo (não será salva): ")
        if not password:raise ValueError("Senha de aplicativo ausente.")
        client=WordPress(args.site_url,args.user,password)
        terms=client.request("categories?per_page=100&_fields=id,slug")
        categories={term["slug"]:term["id"] for term in terms}
        for spec,result in zip(specs,results):
            if spec["categoria"] not in categories:raise ValueError("Categoria não encontrada no WordPress.")
            client.attach(spec,result,categories)
            (args.output_dir/"manifesto-upload.json").write_text(json.dumps(results,ensure_ascii=False,indent=2)+"\n")
            print(f"Vinculada: post {result['post_id']}, mídia {result['media_id']}, reutilizada={result['reused']}",flush=True)
    else:
        (args.output_dir/"manifesto-geracao.json").write_text(json.dumps(results,ensure_ascii=False,indent=2)+"\n")
    print("Concluído; nenhum post ou página foi criado.",flush=True)


if __name__=="__main__":
    try:main()
    except (ValueError,RuntimeError,urllib.error.URLError) as error:
        print(f"Falha: {error}",file=sys.stderr);sys.exit(1)
