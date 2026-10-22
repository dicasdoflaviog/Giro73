(()=>{
  const clocks=document.querySelectorAll('[data-g73-clock]');
  const date=new Intl.DateTimeFormat('pt-BR',{timeZone:'America/Sao_Paulo',day:'2-digit',month:'short',year:'numeric'});
  const hour=new Intl.DateTimeFormat('pt-BR',{timeZone:'America/Sao_Paulo',hour:'2-digit',minute:'2-digit',hourCycle:'h23'});
  const tick=()=>{const now=new Date();clocks.forEach(el=>{el.dateTime=now.toISOString();el.textContent=date.format(now)+' • '+hour.format(now).replace(':','h');});};
  if(clocks.length){tick();setInterval(tick,60000);}
  const toggle=document.querySelector('[data-g73-menu]'),drawer=document.querySelector('#g73-drawer');
  const searchToggle=document.querySelector('[data-g73-search]'),search=document.querySelector('#g73-search-panel');
  const closeSearch=()=>{if(search){search.hidden=true;searchToggle?.setAttribute('aria-expanded','false');}};
  if(toggle&&drawer){
    toggle.addEventListener('click',()=>{closeSearch();drawer.showModal();toggle.setAttribute('aria-expanded','true');});
    drawer.querySelector('[data-g73-close]')?.addEventListener('click',()=>drawer.close());
    drawer.addEventListener('close',()=>{toggle.setAttribute('aria-expanded','false');toggle.focus({preventScroll:true});});
    drawer.addEventListener('click',e=>{const r=drawer.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)drawer.close();});
  }
  if(searchToggle&&search){
    searchToggle.addEventListener('click',()=>{search.hidden=!search.hidden;searchToggle.setAttribute('aria-expanded',String(!search.hidden));if(!search.hidden)search.querySelector('input[type="search"]').focus();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!search.hidden){closeSearch();searchToggle.focus({preventScroll:true});}});
    document.addEventListener('click',e=>{if(!search.hidden&&!search.contains(e.target)&&!searchToggle.contains(e.target))closeSearch();});
  }
})();
