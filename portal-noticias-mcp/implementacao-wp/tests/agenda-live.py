"""Agenda pública: filtros, cartões, responsividade e fallback sem JavaScript.
Prévia opcional autenticada, salva fora do repositório: --preview /tmp/preview.json.
Nenhuma credencial ou chamada de compra/envio de mensagem é necessária.
"""
from pathlib import Path
from playwright.sync_api import sync_playwright,expect
import urllib.request,urllib.error,json,argparse
parser=argparse.ArgumentParser();parser.add_argument('--preview');args=parser.parse_args()
out=Path('/tmp/g73-agenda-validation');out.mkdir(exist_ok=True)
cache={};errors=[];opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor())
def route(r):
 u=r.request.url
 if not u.startswith('https://giro73.com.br/'):
  r.fulfill(status=200,content_type='text/html',body='<p>Destino externo interceptado: nenhuma compra ou mensagem enviada.</p>');return
 try:
  if u in cache:status,headers,body=cache[u]
  else:
   try:x=opener.open(urllib.request.Request(u,headers={'User-Agent':'Giro73AgendaValidation/1.0'}),timeout=10)
   except urllib.error.HTTPError as e:x=e
   status=x.status;body=x.read();headers={k.lower():v for k,v in x.headers.items() if k.lower() not in ('content-length','content-encoding','transfer-encoding','connection')};cache[u]=(status,headers,body)
  r.fulfill(status=status,headers=headers,body=body)
 except Exception as e:errors.append({'resource':u,'error':str(e)});r.abort()
labels=['Todos','Shows & Festas','Gastronomia & Feiras','Esportes','Teatro & Cultura','Negócios & Cursos','Infantil & Família']
result=[]
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']);ctx=b.new_context();ctx.route('**/*',route);page=ctx.new_page();page.set_default_timeout(20000);page.on('pageerror',lambda e:errors.append({'javascript':str(e)}))
 for width in (1440,390,360):
  page.set_viewport_size({'width':width,'height':1000})
  for name,url in [('home','https://giro73.com.br/'),('directory','https://giro73.com.br/agenda-73/')]:
   r=page.goto(url,wait_until='domcontentloaded',timeout=90000);assert r.status==200
   section=page.locator('[data-g73-agenda]');expect(section).to_have_count(1);section.scroll_into_view_if_needed();filters=section.locator('[data-g73-agenda-filter]');assert filters.all_inner_texts()==labels
   expect(section.locator('.g73-event-poster')).to_have_count(4);expect(section.locator('.g73-event-cta')).to_have_count(4);expect(section.locator('.g73-event-cta:disabled')).to_have_count(4)
   assert section.locator('a.g73-event-cta').count()==0;assert section.locator('.g73-event-contact').count()==0;assert 'Falar com' not in section.inner_text();assert 'Contato da organização' not in section.inner_text()
   for card in section.locator('.g73-event-poster').all():
    assert 'Modelo • Não confirmado' in card.inner_text();assert card.locator('time[datetime]').count()==1;assert card.locator('.g73-event-facts svg').count()==2
    for button in card.locator('.g73-event-cta').all():assert button.bounding_box()['height']>=48
   for link in filters.all():
    slug=link.get_attribute('data-g73-agenda-filter');link.click();expect(link).to_have_attribute('aria-current','true');expected=4 if not slug else section.locator(f'[data-g73-event-category="{slug}"]').count();expect(section.locator('.g73-event-poster:visible')).to_have_count(expected)
    assert section.locator('.g73-agenda-empty').is_visible()==(expected==0)
   filters.first.click();expect(section.locator('.g73-event-poster:visible')).to_have_count(4)
   assert not page.evaluate('document.documentElement.scrollWidth>innerWidth'),(name,width)
   if width<600:assert section.locator('.g73-agenda-filters').evaluate('e=>e.scrollWidth>e.clientWidth')
   if name=='home':expect(page.locator('.g73-feed-query .wp-block-post-template > li')).to_have_count(10)
   page.add_style_tag(content='.g73-portal .g73-header{position:static!important}')
   section.screenshot(path=str(out/f'{name}-{width}.png'));result.append({'page':name,'width':width,'filters':7,'models':4,'cta_min_height':48,'overflow':False});print('OK',name,width,flush=True)
 # Links nativos filtram todo o catálogo, inclusive sem JavaScript e em páginas paginadas.
 nojs=b.new_context(java_script_enabled=False,viewport={'width':390,'height':1000});nojs.route('**/*',route);q=nojs.new_page();q.goto('https://giro73.com.br/agenda-73/?g73_categoria=gastronomia_feiras',wait_until='domcontentloaded',timeout=90000);expect(q.locator('.g73-event-poster')).to_have_count(1);assert q.locator('[aria-current="true"]').inner_text()=='Gastronomia & Feiras';q.locator('[data-g73-agenda-filter=""]').click();expect(q.locator('.g73-event-poster')).to_have_count(4);nojs.close()
 if args.preview:
  d=json.loads(Path(args.preview).read_text());html=d.get('body',d)['html'];page.set_viewport_size({'width':390,'height':900});page.set_content('<main class="g73-portal"><section class="g73-agenda-showcase"><div class="g73-agenda-grid">'+html+'</div></section></main>');css=Path(__file__).resolve().parents[1]/'css-g1-techtudo-ultra-leve.css';page.add_style_tag(content=css.read_text());button=page.locator('a.g73-event-open');expect(button).to_have_count(1);expect(page.locator('.g73-event-contact')).to_have_count(0)
  assert button.inner_text()=='Ver atrações →';assert 'Falar com' not in page.locator('body').inner_text();assert page.locator('.g73-event-cta').count()==1
  for link in page.locator('.g73-event-open,.g73-event-cover-link,.g73-event-title-link').all():
   assert link.get_attribute('href')=='https://events.example/agenda-73?origem=teste';assert link.get_attribute('target')=='_blank';assert {'nofollow','noopener'}<=set(link.get_attribute('rel').split())
   with page.expect_popup() as opened:link.click()
   popup=opened.value;popup.wait_for_load_state('domcontentloaded');assert popup.url.startswith('https://events.example/');popup.close()
  assert button.bounding_box()['height']>=48
  page.mouse.move(0,0);expect(button).to_have_css('background-color','rgb(255, 190, 11)')
  assert button.evaluate('e=>getComputedStyle(e).color')=='rgb(44, 16, 103)'
  button.hover();expect(button).to_have_css('background-color','rgb(255, 234, 2)')
  page.mouse.move(0,0)
  page.screenshot(path=str(out/'private-cta-390.png'));result.append({'private_preview_cta':True,'external_requests_intercepted':True})
 assert not [e for e in errors if 'javascript' in e],errors;b.close()
(out/'result.json').write_text(json.dumps({'checks':result,'errors':errors},ensure_ascii=False,indent=2));print(json.dumps({'checks':result,'errors':errors},ensure_ascii=False))
