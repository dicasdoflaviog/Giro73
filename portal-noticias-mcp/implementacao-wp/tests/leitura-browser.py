"""Exercita filas, pausa/retomada e seleção de texto sem tocar áudio ou serviços externos."""
from pathlib import Path
import json
from playwright.sync_api import sync_playwright,expect
root=Path(__file__).resolve().parents[1];script=(root/'leitura-noticia.js').read_text();results={}
mock="""window.voiceCalls=[];window.voiceEvents=[];window.SpeechSynthesisUtterance=class{constructor(text){this.text=text;}};Object.defineProperty(window,'speechSynthesis',{configurable:true,value:{speaking:false,paused:false,getVoices(){return[{lang:'pt-BR',name:'Teste'}]},speak(u){this.speaking=true;window.currentUtterance=u;voiceCalls.push({text:u.text,lang:u.lang,rate:u.rate,voice:u.voice.lang});u.onstart()},pause(){this.paused=true;voiceEvents.push('pause')},resume(){this.paused=false;voiceEvents.push('resume')},cancel(){this.speaking=false;voiceEvents.push('cancel')}}});"""
html='''<main class="g73-single"><h1>Título regional</h1><div class="g73-summary">Linha fina da matéria.</div><section class="g73-audio-player"><button data-g73-voice-play>🔊 Ouvir notícia</button><button data-g73-voice-stop hidden>Parar</button><span data-g73-voice-status role="status"></span></section><div class="entry-content"><p class="g73-photo-credit">CRÉDITO AVULSO NÃO LER</p><p>Primeiro parágrafo da reportagem.</p><aside class="g73-partner-unit"><p>PUBLICIDADE NÃO LER</p></aside><div class="g73-read-also"><p>LEIA TAMBÉM NÃO LER</p></div><p>Último parágrafo limpo.</p><figure><p>CRÉDITO NÃO LER</p></figure></div></main>'''
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox'])
 for width in [390,1440]:
  page=browser.new_page(viewport={'width':width,'height':900});page.set_content(html+'<script>'+mock+'</script><script>'+script+'</script>');assert page.evaluate('voiceCalls.length')==0
  page.locator('[data-g73-voice-play]').click();expect(page.locator('[data-g73-voice-play]')).to_have_text('⏸ Pausar');assert page.evaluate('voiceCalls[0]')=={'text':'Título regional','lang':'pt-BR','rate':1.05,'voice':'pt-BR'}
  page.locator('[data-g73-voice-play]').click();assert page.evaluate('speechSynthesis.paused');expect(page.locator('[data-g73-voice-status]')).to_have_text('Leitura pausada.');page.locator('[data-g73-voice-play]').click();assert not page.evaluate('speechSynthesis.paused')
  for i in range(4):page.evaluate('currentUtterance.onend()')
  assert page.evaluate('voiceCalls.map(x=>x.text)')==['Título regional','Linha fina da matéria.','Primeiro parágrafo da reportagem.','Último parágrafo limpo.'];expect(page.locator('[data-g73-voice-status]')).to_have_text('Leitura concluída.')
  page.locator('[data-g73-voice-play]').click();page.locator('[data-g73-voice-stop]').click();expect(page.locator('[data-g73-voice-status]')).to_have_text('Leitura encerrada.');assert not page.evaluate('speechSynthesis.speaking');results[str(width)]={'no_autoplay':True,'pt_BR_rate':1.05,'clean_text':True,'pause_resume_stop':True};page.close()
 page=browser.new_page();page.set_content(html+"<script>Object.defineProperty(window,'speechSynthesis',{value:undefined,configurable:true});delete window.speechSynthesis;</script><script>"+script+'</script>');expect(page.locator('[data-g73-voice-play]')).to_be_disabled();results['unsupported']='disabled';browser.close()
print(json.dumps(results));Path('/tmp/g73-final/voice-test.json').write_text(json.dumps(results))
