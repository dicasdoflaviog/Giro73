from playwright.sync_api import sync_playwright
from pathlib import Path
import json,copy,base64
root=Path(__file__).resolve().parents[1]
php=root.joinpath('publicidade-parceiros.php').read_text();script=php.split("function g73_partner_frontend_js(){return <<<'G73JS'\n")[1].split('\nG73JS;')[0];css=root.joinpath('css-g1-techtudo-ultra-leve.css').read_text().split('/* Parceiros G73:')[1].split('/* Arquivos e busca:')[0];css='/* Parceiros G73:'+css
img='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLbtAAAAABJRU5ErkJggg=='
base={'server_time':1792699200000,'settings':{'contact_email':'comercial@giro73.com.br','modes':{'sidebar_lateral':'rotacao'},'overlay':{'enabled':False,'trigger':'tempo','delay':3,'scroll':30,'frequency':'1x_por_dia','scope':'todo_portal','auto_close':15}},'campaigns':[],'endpoint':'https://giro73.test/wp-json/g73/v1/partners','page':'home'}
def c(i,pos='sidebar_lateral',**kw):
 d={'id':i,'name':'Parceiro '+str(i),'desktop':{'url':img,'width':300,'height':250},'mobile':None,'url':'https://example.com/parceiro'+str(i),'position':pos,'format':'retangulo_medio','width':300,'height':250,'start':0,'end':0};d.update(kw);return d
results={}
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
 def scenario(config,width=1440,bodyheight=1400):
  ctx=b.new_context(viewport={'width':width,'height':850});page=ctx.new_page();page.clock.install(time=base['server_time']);errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
  data=copy.deepcopy(config)
  def route(r):
   if '/wp-json/' in r.request.url:r.fulfill(json=data)
   else:r.fulfill(content_type='text/html',body=f'''<html><head><style>{css}</style></head><body style="margin:16px"><button id="before">Antes</button><aside class="g73-partner-unit" data-g73-position="sidebar_lateral" data-g73-empty="1" style="--g73-partner-width:300px;--g73-partner-ratio:1.2;--g73-partner-mobile-ratio:1.2"><span class="g73-partner-label">PUBLICIDADE</span><div class="g73-vitrine-box"></div></aside><div style="height:{bodyheight}px">Conteúdo</div><dialog class="g73-overlay-card"><div class="g73-overlay-toolbar"><span>PUBLICIDADE</span><button data-g73-close>✕ Fechar</button></div><div class="g73-overlay-content"></div></dialog><script id="g73-partners-boot" type="application/json">{json.dumps(data)}</script><script>{script}</script></body></html>''')
  ctx.route('**/*',route);page.goto('https://giro73.test/',wait_until='networkidle');return ctx,page,errors
 cfg=copy.deepcopy(base);cfg['campaigns']=[c(1),c(2)]
 for width in (390,1440):
  ctx,page,errors=scenario(cfg,width);page.mouse.move(0,0)
  assert page.locator('.g73-media-frame').get_attribute('data-campaign')=='1';assert page.locator('.g73-media-frame a').get_attribute('rel')=='sponsored noopener';h=page.locator('.g73-vitrine-box').bounding_box()['height'];page.clock.fast_forward(8100);assert page.locator('.g73-media-frame').get_attribute('data-campaign')=='2';assert h==page.locator('.g73-vitrine-box').bounding_box()['height'];assert page.evaluate('document.documentElement.scrollWidth===innerWidth');assert not errors,errors;ctx.close();results['rotation_'+str(width)]=True
 cfg['settings']['modes']['sidebar_lateral']='empilhado';ctx,page,errors=scenario(cfg);assert page.locator('.g73-media-frame').count()==2;ctx.close();results['stacked']=True
 cfg=copy.deepcopy(base);cfg['campaigns']=[c(1,end=base['server_time']+4000),c(2,start=base['server_time']+5000)];ctx,page,errors=scenario(cfg);assert page.locator('[data-campaign="1"]').count()==1;page.clock.fast_forward(4100);assert page.locator('[data-campaign]').count()==0;page.clock.fast_forward(1000);assert page.locator('[data-campaign="2"]').count()==1;ctx.close();results['schedule_and_expiry']=True
 cfg=copy.deepcopy(base);cfg['settings']['overlay']['enabled']=True;cfg['campaigns']=[c(7,'popup_global'),c(8,'popup_global')]
 for width in (390,1440):
  ctx,page,errors=scenario(cfg,width);page.locator('#before').focus();page.clock.fast_forward(3100);assert page.locator('dialog').evaluate('(e)=>e.open');assert page.locator('dialog').get_attribute('data-campaign')=='7';assert page.locator('[data-g73-close]').evaluate('(e)=>e===document.activeElement');page.keyboard.press('Escape');assert not page.locator('dialog').evaluate('(e)=>e.open');assert page.locator('#before').evaluate('(e)=>e===document.activeElement');page.reload(wait_until='networkidle');page.clock.fast_forward(3100);assert not page.locator('dialog').evaluate('(e)=>e.open');assert not errors,errors;ctx.close();results['daily_frequency_esc_focus_'+str(width)]=True
 cfg['settings']['overlay']['frequency']='sempre';ctx,page,errors=scenario(cfg);page.clock.fast_forward(3100);assert page.locator('dialog').evaluate('(e)=>e.open');page.locator('[data-g73-close]').click();page.reload(wait_until='networkidle');page.clock.fast_forward(3100);assert page.locator('dialog').get_attribute('data-campaign')=='8';page.clock.fast_forward(15100);assert not page.locator('dialog').evaluate('(e)=>e.open');ctx.close();results['popup_rotation_and_auto_close']=True
 cfg['settings']['overlay']['frequency']='1x_por_sessao';ctx,page,errors=scenario(cfg);page.clock.fast_forward(3100);page.mouse.click(5,5);assert not page.locator('dialog').evaluate('(e)=>e.open');page.reload(wait_until='networkidle');page.clock.fast_forward(3100);assert not page.locator('dialog').evaluate('(e)=>e.open');ctx.close();results['session_and_backdrop']=True
 cfg['settings']['overlay'].update({'frequency':'sempre','trigger':'rolagem'});ctx,page,errors=scenario(cfg);page.clock.fast_forward(4000);assert not page.locator('dialog').evaluate('(e)=>e.open');page.evaluate('scrollTo(0,document.documentElement.scrollHeight*.5)');page.wait_for_timeout(100);assert page.locator('dialog').evaluate('(e)=>e.open');ctx.close();results['scroll_trigger']=True
 cfg['settings']['overlay']['scope']='materias';ctx,page,errors=scenario(cfg);page.clock.fast_forward(5000);page.evaluate('scrollTo(0,document.documentElement.scrollHeight*.5)');page.wait_for_timeout(100);assert not page.locator('dialog').evaluate('(e)=>e.open');ctx.close();results['scope']=True
 b.close()
Path('/tmp/g73-partners-browser-tests.json').write_text(json.dumps(results,indent=2));print(json.dumps(results))
