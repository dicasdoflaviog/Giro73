"""Validação pública de Política, dez itens do feed e colunas institucionais."""
from pathlib import Path
from playwright.sync_api import sync_playwright,expect
import urllib.request,urllib.error,json
Path('/tmp/g73-politica-validation').mkdir(exist_ok=True)
cache={};errors=[];opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor())
def route(r):
 u=r.request.url
 try:
  if u in cache and '/wp-json/' not in u:status,headers,body=cache[u]
  else:
   try:x=opener.open(urllib.request.Request(u,headers={'User-Agent':'Giro73Validation/1.0'}),timeout=7)
   except urllib.error.HTTPError as e:x=e
   status=x.status;body=x.read();headers={k.lower():v for k,v in x.headers.items() if k.lower() not in ['content-length','content-encoding','transfer-encoding','connection']};cache[u]=(status,headers,body)
  r.fulfill(status=status,headers=headers,body=body)
 except Exception as e:errors.append({'url':u,'error':str(e)});r.abort()
results={}
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);c=b.new_context();c.route('**/*',route);page=c.new_page();page.set_default_timeout(60000);page.on('pageerror',lambda e:errors.append({'javascript':str(e)}))
 for w in (1440,390):
  page.set_viewport_size({'width':w,'height':1000});response=page.goto('https://giro73.com.br/',wait_until='domcontentloaded',timeout=90000);assert response.status==200
  expect(page.locator('.g73-feed-query .wp-block-post-template > li')).to_have_count(10);expect(page.locator('.g73-opinion-card')).to_have_count(6)
  assert page.locator('.g73-sidebar .g73-agenda').count()==0
  assert page.locator('.g73-toplinks a').all_inner_texts()==['notícias','política','dinheiro','tecnologia','esportes','famosos']
  expect(page.locator('.g73-opinions-note')).to_have_text('Análise, bastidores e opinião especializada sobre o Extremo Sul da Bahia e o Brasil.')
  assert page.locator('.g73-opinion-card h3').all_inner_texts()==['Redação Giro73']*6
  assert 'persona' not in page.locator('body').inner_text().lower();assert page.locator('.g73-opinion-latest[href]').count()==6
  for i in page.locator('.g73-opinion-avatar').all():
   i.scroll_into_view_if_needed();page.wait_for_function('e=>e.complete&&e.naturalWidth>=64&&e.naturalWidth===e.naturalHeight',arg=i.element_handle());assert '/coluna-' in i.get_attribute('src')
  results[str(w)]=page.evaluate('''()=>{let f=document.querySelector('.g73-feed'),s=document.querySelector('.g73-sidebar'),l=document.querySelector('.g73-content-layout'),r=e=>{let a=e.getBoundingClientRect();return {h:a.height,w:a.width,top:a.top,bottom:a.bottom}};return {feed:r(f),sidebar:r(s),grid:r(l),align:getComputedStyle(l).alignItems,overflow:document.documentElement.scrollWidth>innerWidth}}''');print('Layout',w,results[str(w)],flush=True);assert not results[str(w)]['overflow'];
  if w==1440:assert abs(results[str(w)]['feed']['h']-results[str(w)]['sidebar']['h'])<1,results[str(w)]
  page.add_style_tag(content='.g73-portal .g73-header{position:static!important}');page.screenshot(path=f'/tmp/g73-politica-validation/home-{w}.png',full_page=True);page.locator('.g73-opinions').screenshot(path=f'/tmp/g73-politica-validation/colunas-{w}.png')
  if w==1440:
   page.locator('[data-g73-menu]').click();expect(page.locator('#g73-drawer')).to_be_visible();expect(page.locator('#g73-drawer a[href="/category/politica/"]')).to_be_visible();page.locator('#g73-drawer [data-g73-close]').click()
  page.goto('https://giro73.com.br/category/politica/',wait_until='domcontentloaded',timeout=90000);expect(page.locator('h1')).to_have_text('Política & Poder');expect(page.locator('.g73-feed-query .wp-block-post-template > li')).to_have_count(2);assert page.locator('.g73-archive-heading').evaluate('e=>getComputedStyle(e).borderBottomColor')=='rgb(185, 28, 28)'
  page.goto('https://giro73.com.br/orcamento-municipal-documentos-prioridades-prefeitura/',wait_until='domcontentloaded',timeout=90000);assert 'Por Redação Giro73' in page.locator('.g73-byline-text').inner_text();assert 'persona' not in page.locator('body').inner_text().lower();assert page.locator('.g73-single').evaluate('e=>getComputedStyle(e).getPropertyValue("--cor-editoria").trim()')=='#B91C1C';assert page.locator('.g73-columnist-badge').count()==2;assert page.locator('.g73-author').count()==1;page.add_style_tag(content='.g73-portal .g73-header{position:static!important}');page.screenshot(path=f'/tmp/g73-politica-validation/helena-{w}.png',full_page=True)
  graphs=page.locator('script[type="application/ld+json"]').all_text_contents();assert any('OpinionNewsArticle' in x for x in graphs);assert not any('Helena Gusmão' in x for x in graphs)
 assert not [x for x in errors if 'javascript' in x],errors;b.close()
Path('/tmp/g73-politica-validation/layout-result.json').write_text(json.dumps({'layout':results,'errors':errors},ensure_ascii=False,indent=2));print(json.dumps(results))
