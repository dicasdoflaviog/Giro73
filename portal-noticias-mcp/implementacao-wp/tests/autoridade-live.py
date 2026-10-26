"""Validação somente leitura da Home e matéria publicadas; requer Playwright e Chromium."""
from pathlib import Path
from playwright.sync_api import sync_playwright,expect
import urllib.request,urllib.error,json
Path('/tmp/g73-autoridade-validation').mkdir(exist_ok=True)
cache={};errors=[]
# Confiança TLS padrão é mantida; os recursos HTTPS são carregados com urllib verificado.
opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor())
def route(r):
 u=r.request.url
 try:
  if u in cache and '/wp-json/' not in u:status,headers,body=cache[u]
  else:
   req=urllib.request.Request(u,headers={'User-Agent':'Giro73Validation/1.0'},method=r.request.method)
   try:x=opener.open(req,timeout=25)
   except urllib.error.HTTPError as e:x=e
   status=x.status;headers={k.lower():v for k,v in x.headers.items() if k.lower() not in ('content-encoding','transfer-encoding','content-length','connection')};body=x.read();cache[u]=(status,headers,body)
  r.fulfill(status=status,headers=headers,body=body)
 except Exception as e:r.abort();errors.append({'url':u,'error':str(e)})
post='https://giro73.com.br/extremo-sul-bahia-economia-infraestrutura-desenvolvimento/'
result={}
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox']);ctx=b.new_context();ctx.route('**/*',route);page=ctx.new_page();page.on('pageerror',lambda e:errors.append({'javascript':str(e)}))
 for width in (390,1440):
  page.set_viewport_size({'width':width,'height':1000})
  for name,url,positions in [('home','https://giro73.com.br/',{'topo_portal','sidebar_lateral','sidebar_base','home_entre_secoes'}),('single',post,{'topo_portal','in_article_1','in_article_2'})]:
   response=page.goto(url,wait_until='networkidle');assert response.status==200
   assert set(page.locator('[data-g73-position]:not([hidden])').evaluate_all('(xs)=>xs.map(x=>x.dataset.g73Position)'))==positions
   for position in positions:
    unit=page.locator(f'[data-g73-position="{position}"]');image=unit.locator('img');image.scroll_into_view_if_needed();expect(image).to_be_visible();page.wait_for_function('(p)=>{const i=document.querySelector(`[data-g73-position="${p}"] img`);return i&&i.complete&&i.naturalWidth>0}',arg=position)
    m=unit.evaluate('''e=>{const f=e.querySelector('.g73-media-frame'),i=e.querySelector('img'),t=e.querySelector('.g73-partner-label'),r=x=>{const a=x.getBoundingClientRect();return {x:a.x,y:a.y,w:a.width,h:a.height}};return {unit:r(e),frame:r(f),image:r(i),label:r(t),background:getComputedStyle(e).backgroundColor,border:getComputedStyle(e).borderWidth,padding:getComputedStyle(e).padding,src:i.currentSrc,format:[i.naturalWidth,i.naturalHeight]}}''')
    assert m['background']=='rgba(0, 0, 0, 0)' and m['border']=='0px' and m['padding']=='0px',(position,m)
    assert abs(m['image']['x']-m['label']['x'])<1 and abs(m['image']['y']-m['label']['y']-m['label']['h'])<1,(position,m)
    assert abs(m['frame']['h']-m['image']['h']-m['label']['h'])<1,(position,m)
    assert abs(m['frame']['x']+m['frame']['w']/2-m['unit']['x']-m['unit']['w']/2)<1,(position,m)
    if position=='topo_portal':assert m['image']['w']==(320 if width<=768 else 970),(position,m)
    if position=='sidebar_lateral':assert [m['image']['w'],m['image']['h']]==[300,250],m
    if position=='sidebar_base':assert [m['image']['w'],m['image']['h']]==[300,600],m
    result[name+'_'+str(width)+'_'+position]=m
   if name=='home':
    cards=page.locator('.g73-opinion-card');expect(cards).to_have_count(6);expect(page.locator('.g73-event-demo')).to_have_count(0)
    for image in page.locator('.g73-opinion-avatar,.g73-event-cover').all():
     image.scroll_into_view_if_needed();expect(image).to_be_visible();page.wait_for_function('(e)=>e.complete&&e.naturalWidth>0',arg=image.element_handle())
    avatar=cards.first.locator('img').bounding_box();assert avatar['width']==64 and avatar['height']==64,avatar
    gap=cards.first.evaluate('e=>e.querySelector(".g73-opinion-copy").getBoundingClientRect().top-e.querySelector(".g73-opinion-portrait").getBoundingClientRect().bottom');assert abs(gap-14)<.1,gap
    assert page.locator('.g73-opinions').bounding_box()['width']>300
    expect(cards.nth(5).locator('h3')).to_have_text('Redação Giro73');expect(cards.nth(5).locator('.g73-opinion-pill')).to_have_text('Raiz & Vitalidade')
    result['new_home_'+str(width)]={'columnists':6,'avatars_px':[64,64],'models':0,'events':page.locator('.g73-event-poster').count(),'body_width':page.evaluate('document.documentElement.scrollWidth')}
    page.add_style_tag(content='.g73-portal .g73-header {position:static!important}');page.locator('.g73-opinions').screenshot(path=f'/tmp/g73-autoridade-validation/colunistas-{width}.png')
    if page.locator('.g73-agenda-showcase').count():page.locator('.g73-agenda-showcase').screenshot(path=f'/tmp/g73-autoridade-validation/agenda-{width}.png')
    assert page.locator('#g73-voice-runtime').count()==0
   else:
    expect(page.locator('.g73-audio-player')).to_have_count(1);assert page.locator('.g73-audio-player').evaluate('e=>e.previousElementSibling.classList.contains("g73-single-signature")');assert page.locator('.g73-sidebar').count()==0
    assert page.locator('.g73-reaction-buttons button').count()==3;assert page.locator('.g73-whatsapp-join[disabled]').count()==0;assert page.locator('.g73-author').count()==1;assert page.locator('.g73-columnist-badge').count()==0
    page.locator('.g73-single-signature').scroll_into_view_if_needed();page.screenshot(path=f'/tmp/g73-autoridade-validation/single-reading-{width}.png')
    if width==1440:
     page.emulate_media(media='print');assert page.locator('.g73-audio-player').is_hidden() and page.locator('.g73-partner-unit').first.is_hidden() and page.locator('.g73-reactions').is_hidden();expect(page.locator('.g73-print-header')).to_be_visible();expect(page.locator('.entry-content')).to_be_visible();page.pdf(path='/tmp/g73-autoridade-validation/artigo.pdf',format='A4',print_background=True,display_header_footer=False);page.emulate_media(media='screen')
   assert page.evaluate('document.documentElement.scrollWidth===innerWidth'),(width,name)
   page.evaluate('scrollTo(0,0)');page.screenshot(path=f'/tmp/g73-autoridade-validation/{name}-{width}.png',full_page=True)
 nojs=b.new_context(java_script_enabled=False,viewport={'width':1440,'height':1000});nojs.route('**/*',route);q=nojs.new_page();q.goto('https://giro73.com.br/',wait_until='networkidle');assert q.locator('.g73-opinion-card').count()==6 and q.locator('.g73-event-demo').count()==0 and q.locator('[data-g73-position] img').count()==4;nojs.close()
 assert not [e for e in errors if 'javascript'in e],errors;b.close()
Path('/tmp/g73-autoridade-validation/live-result.json').write_text(json.dumps({'checks':result,'errors':errors},ensure_ascii=False,indent=2));print(json.dumps({'desktop_mobile':True,'six_columns':True,'no_public_event_models':True,'native_voice_controls':True,'clean_banners':True,'print_pdf':True,'nojs':True,'errors':errors},ensure_ascii=False))
