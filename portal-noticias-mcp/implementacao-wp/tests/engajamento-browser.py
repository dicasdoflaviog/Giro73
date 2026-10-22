from pathlib import Path
import json,copy
from playwright.sync_api import sync_playwright,expect
root=Path(__file__).resolve().parents[1];source=root.joinpath('engajamento-regional.php').read_text();script=source.split("function g73_eng_js(){return <<<'G73ENG'\n")[1].split('\nG73ENG;')[0];css=root.joinpath('css-g1-techtudo-ultra-leve.css').read_text();base={'id':20,'reactions':{'util':0,'surpreendente':0,'indignado':0},'poll':{'question':'Qual tema você prefere?','options':['Serviços públicos','Cultura e eventos'],'votes':[0,0],'total':0,'version':'abc123'}};results={}
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/usr/bin/chromium',headless=True,args=['--no-sandbox'])
 for width in (390,1440):
  ctx=b.new_context(viewport={'width':width,'height':900});data=copy.deepcopy(base);calls=[];page=ctx.new_page();errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
  def route(r):
   if '/wp-json/' in r.request.url:
    if r.request.method=='POST':
     body=r.request.post_data_json;calls.append(body)
     if body['kind']=='reaction':data['reactions'][body['choice']]+=1
     else:data['poll']['votes'][body['choice']]+=1;data['poll']['total']+=1
    r.fulfill(json=data);return
   buttons=''.join(f'<button data-g73-choice="{k}" aria-pressed="false">{label}<span>0</span></button>' for k,label in [('util','👍 Útil'),('surpreendente','😮 Surpreendente'),('indignado','😡 Indignado')])
   r.fulfill(content_type='text/html',body=f'''<html><head><style>{css}</style></head><body class="single-post"><main class="g73-portal" style="max-width:740px;margin:auto;padding:16px"><section class="g73-reactions" data-g73-engagement="20" data-g73-kind="reaction"><h2>O que você achou desta notícia?</h2><div class="g73-reaction-buttons">{buttons}</div><p data-g73-status role="status"></p></section><section class="g73-poll" data-g73-engagement="20" data-g73-kind="poll" data-g73-version="abc123"><h2 data-g73-question></h2><div data-g73-options></div><p data-g73-status role="status"></p></section></main><script id="g73-engagement-boot" type="application/json">{{"endpoint":"https://giro73.test/wp-json/g73/v1/engagement/"}}</script><script>{script}</script></body></html>''')
  ctx.route('**/*',route);page.goto('https://giro73.test/',wait_until='networkidle');page.locator('.g73-reactions [data-g73-choice="util"]').click();page.wait_for_load_state('networkidle');expect(page.locator('.g73-reactions [data-g73-choice="util"] span')).to_have_text('1');assert page.locator('.g73-reactions button:disabled').count()==3;assert page.locator('.g73-reactions [data-g73-choice="util"]').get_attribute('aria-pressed')=='true';assert len(calls)==1
  page.locator('.g73-poll [data-g73-choice="1"]').click();page.locator('.g73-poll-result').first.wait_for();page.wait_for_load_state('networkidle');assert page.locator('.g73-poll-result').count()==2;assert page.locator('.g73-poll-result strong').all_inner_texts()==['0%','100%'];assert len(calls)==2
  page.reload(wait_until='networkidle');expect(page.locator('.g73-reactions button:disabled')).to_have_count(3);assert page.locator('.g73-poll button').count()==0;assert len(calls)==2;assert page.evaluate('document.documentElement.scrollWidth===innerWidth');assert not errors,errors;results[str(width)]={'reactions':True,'poll_results':True,'reload_lock':True,'requests':len(calls)};ctx.close()
 b.close()
Path('/tmp/g73-eng-ui-result.json').write_text(json.dumps(results));print(json.dumps(results))
