"""Integração real em rascunhos; limpa apenas os próprios posts, mídias e tags.
Uso: python publisher-live.py --token-file /caminho/privado/token.json
Senha de aplicativo é solicitada sem eco. O arquivo do token não deve ir ao Git.
"""
import argparse,json,urllib.request,urllib.error,urllib.parse,base64,getpass,uuid,re,concurrent.futures
from pathlib import Path
p=argparse.ArgumentParser();p.add_argument('--token-file',required=True);p.add_argument('--site',default='https://giro73.com.br');p.add_argument('--user',default='admin');args=p.parse_args()
secret=json.loads(Path(args.token_file).read_text());token=secret.get('body',secret)['token'];password=getpass.getpass('Senha de aplicativo (sem eco): ');basic='Basic '+base64.b64encode((args.user+':'+password.replace(' ','')).encode()).decode();root=args.site.rstrip('/')
class SameHost(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):
  if urllib.parse.urlparse(newurl).netloc!=urllib.parse.urlparse(root).netloc:raise RuntimeError('Redirecionamento externo bloqueado')
  return super().redirect_request(req,fp,code,msg,headers,newurl)
def request(path,data=None,method=None,admin=False,provided=token,extra=None):
 headers={'User-Agent':'Giro73PublisherValidation/1.0','Content-Type':'application/json'}
 if admin:headers['Authorization']=basic
 if provided is not None:headers['X-G73-Token']=provided
 headers.update(extra or {});op=urllib.request.build_opener(SameHost(),urllib.request.HTTPCookieProcessor());req=urllib.request.Request(root+path,data=json.dumps(data).encode() if data is not None else None,headers=headers,method=method or ('POST' if data is not None else 'GET'))
 for attempt in range(3):
  try:r=op.open(req,timeout=70)
  except urllib.error.HTTPError as e:r=e
  if r.status==307:continue
  raw=r.read();return r.status,json.loads(raw) if raw.startswith((b'{',b'[')) else raw,r.headers
 raise RuntimeError('Redirecionamento repetido')
def post(data,key=None):return request('/wp-json/g73/v1/publish',data,extra={'Idempotency-Key':key} if key else {})
uid=uuid.uuid4().hex;owned_posts=[];owned_media=[];owned_tags=[];checks=[]
def ok(label):checks.append(label);print('OK',label,flush=True)
status,before,_=request('/wp-json/wp/v2/posts?status=publish&per_page=100&_fields=id',admin=True);assert status==200;before={x['id'] for x in before}
_,tags_before,_=request('/wp-json/wp/v2/tags?per_page=100',admin=True);known_tags={x['id'] for x in tags_before};names=[x['name'] for x in tags_before[:2]] or ['g73-teste-'+uid]
_,source,_=request('/wp-json/wp/v2/posts/20?_embed',admin=True);image=source['_embedded']['wp:featuredmedia'][0]['source_url'];source_id=source['featured_media'];source_alt=source['_embedded']['wp:featuredmedia'][0]['alt_text']
payload={'title':'VALIDAÇÃO PRIVADA — Motor Giro73 '+uid[:8],'content':'<p>Teste privado da integração editorial. Este conteúdo não deve ser publicado.</p><h2>Validação técnica</h2><p>Segundo parágrafo preservado.</p><script>alert(1)</script>[giro73_colunistas]','excerpt':'Validação privada do fluxo editorial.','category':'noticias-regiao','tags':names,'image_url':image+'?g73_pipeline_test='+uid,'image_credit':'Arquivo de teste técnico privado; mídia de origem já existente no Giro73.','focus_keyword':'palavra-chave legada','meta':{'rank_math_focus_keyword':'integração editorial','rank_math_title':'Título SEO revisado','rank_math_description':'Descrição SEO enviada pelo bot.'},'status':'draft'}
try:
 for bad_token in (None,'invalido'):
  s,_,_=request('/wp-json/g73/v1/publish',payload,provided=bad_token);assert s==401,s
 ok('token obrigatório; publicação não depende de Basic Auth')
 for diff,expected in [({'category':'nao-existe-g73'},400),({'columnist_slug':'nao-existe'},400),({'meta':'invalido'},400),({'meta':{'rank_math_title':[]}},400),({'status':'invalido'},400)]:
  s,d,_=post(dict(payload,**diff));assert s==expected,(expected,s,d)
 # Status publish é coberto offline; nenhuma fixture deve ser publicada ao vivo.
 for image_url in ['http://127.0.0.1/imagem.jpg','https://example.org/foto.jpg','https://giro73.com.br/wp-content/uploads/arquivo-inexistente.jpg']:
  s,d,_=post(dict(payload,image_url=image_url));assert s==201 and d['status']=='draft' and not d['featured_media'],(s,d)
  owned_posts.append(d['id']);assert d['warnings']
 ok('campos inválidos rejeitados; imagem inválida cria rascunho com aviso, sem buscar hosts inseguros')
 s,one,h=post(payload,'test-'+uid);assert s==201,(s,one);owned_posts.append(one['id']);owned_media.append(one['featured_media']);assert one['status']=='draft' and one['seo']['schema']=='NewsArticle';assert one['seo']['focus_keyword']=='integração editorial' and one['seo']['title']=='Título SEO revisado' and one['seo']['description']=='Descrição SEO enviada pelo bot.';assert one['image']['width']==1200 and one['image']['height']==675;assert 'no-store' in h.get('Cache-Control','');assert one['featured_media']!=source_id
 _,media,_=request('/wp-json/wp/v2/media/'+str(one['featured_media']),admin=True);assert media['mime_type']=='image/webp';assert media['media_details']['width']==1200 and media['media_details']['height']==675
 photo=urllib.request.urlopen(one['image']['url'],timeout=15).read();assert len(photo)<60000
 _,saved,_=request('/wp-json/wp/v2/posts/'+str(one['id'])+'?context=edit',admin=True);assert '<script' not in saved['content']['raw'] and re.search(r'&#0*91;giro73_colunistas&#0*93;',saved['content']['raw']) and 'class="g73-opinions"' not in saved['content']['rendered'];assert saved['categories']==[1]
 owned_tags.extend(x for x in saved['tags'] if x not in known_tags);ok('rascunho completo, HTML seguro, tags, SEO e WebP 1200×675 <60KB')
 s,two,_=post(payload,'test-'+uid);assert s==200 and two['id']==one['id'] and not two['created'];s,_,_=post(dict(payload,title=payload['title']+' alterado'),'test-'+uid);assert s==409;ok('repetição idempotente e conflito de conteúdo detectado')
 column=dict(payload,title=payload['title']+' — coluna',columnist_slug='lucas-vianna',image_url=image)
 s,col,_=post(column);assert s==201,(s,col);owned_posts.append(col['id']);assert col['featured_media'] and col['seo']['schema']=='OpinionNewsArticle'
 if col['featured_media']!=source_id:owned_media.append(col['featured_media'])
 _,original_media,_=request('/wp-json/wp/v2/media/'+str(source_id),admin=True);assert original_media['alt_text']==source_alt
 _,column_media,_=request('/wp-json/wp/v2/media/'+str(col['featured_media']),admin=True);assert column_media['alt_text']==payload['meta']['rank_math_focus_keyword']
 _,saved,_=request('/wp-json/wp/v2/posts/'+str(col['id'])+'?context=edit',admin=True);assert saved['meta']['g73_columnist_slug']=='lucas-vianna' and saved['author']==1 and 'Redação Giro73' in saved['meta']['g73_columnist_bio'];_,preview,_=request('/wp-json/g73/v1/editorial-preview/'+str(col['id']),admin=True);assert 'Redação Giro73' in preview['byline'];s,again,_=post(column);assert s==200 and again['id']==col['id'];ok('coluna editorial, autoria institucional e reaproveitamento seguro da mídia')
 parallel_payload=dict(payload,title=payload['title']+' — concorrência',image_url=image);key='parallel-'+uid
 with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:results=list(pool.map(lambda _:post(parallel_payload,key),range(2)))
 created=[d for s,d,h in results if s==201];owned_posts.extend(d['id'] for d in created);owned_media.extend(d['featured_media'] for d in created if d.get('featured_media') and d['featured_media']!=source_id);assert len(created)==1,results;assert all(s in (200,201,409) for s,_,_ in results);s,replay,_=post(parallel_payload,key);assert s==200 and replay['id']==created[0]['id'];ok('concorrência sem duplicação de matéria')
 s,logs,_=request('/wp-json/g73/v1/publisher/logs',admin=True);assert s==200 and any(x['http']==201 and x['post_id']==one['id'] for x in logs);text=json.dumps(logs);assert token not in text and password not in text and payload['title'] not in text
 for path in ['/wp-json/g73/v1/publisher/logs','/wp-json/g73/v1/publisher/status','/wp-json/g73/v1/publisher/token']:
  s,_,_=request(path,data={} if path.endswith('token') else None,provided=None);assert s==401,(path,s)
 ok('logs sem segredos e administração privada')
finally:
 for id in owned_posts:
  _,saved,_=request('/wp-json/wp/v2/posts/'+str(id)+'?context=edit',admin=True);owned_tags.extend(x for x in saved.get('tags',[]) if x not in known_tags)
  s,d,_=request('/wp-json/wp/v2/posts/'+str(id)+'?force=true',method='DELETE',admin=True);assert s==200 and d['deleted'],(id,s,d)
 for id in set(owned_media):
  if id!=source_id:
   s,d,_=request('/wp-json/wp/v2/media/'+str(id)+'?force=true',method='DELETE',admin=True);assert s==200 and d['deleted'],(id,s,d)
 for id in set(owned_tags):
  s,d,_=request('/wp-json/wp/v2/tags/'+str(id)+'?force=true',method='DELETE',admin=True);assert s==200
 _,after,_=request('/wp-json/wp/v2/posts?status=publish&per_page=100&_fields=id',admin=True);assert before=={x['id'] for x in after};ok('fixtures excluídas; conjunto de notícias publicadas preservado')
Path('/tmp/g73-publisher/result.json').write_text(json.dumps({'checks':checks,'fixture_ids':owned_posts,'fixture_media':owned_media,'cleaned':True},ensure_ascii=False,indent=2));print('PASS',len(checks),'verificações')
