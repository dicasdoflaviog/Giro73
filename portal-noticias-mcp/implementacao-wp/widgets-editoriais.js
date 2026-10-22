(()=>{
  const zone='America/Sao_Paulo',brl=new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL',minimumFractionDigits:4,maximumFractionDigits:4}),points=new Intl.NumberFormat('pt-BR',{maximumFractionDigits:2}),pct=new Intl.NumberFormat('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2,signDisplay:'always'});
  const cities={teixeira:{latitude:-17.535,longitude:-39.7419},porto:{latitude:-16.4497,longitude:-39.0647},eunapolis:{latitude:-16.377,longitude:-39.5807},itamaraju:{latitude:-17.0392,longitude:-39.5312}};
  const finite=v=>v!==null&&v!==''&&v!==undefined&&Number.isFinite(Number(v));
  const stamp=date=>new Intl.DateTimeFormat('pt-BR',{timeZone:zone,day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}).format(date);
  const today=()=>{const p=Object.fromEntries(new Intl.DateTimeFormat('en-CA',{timeZone:zone,year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date()).map(x=>[x.type,x.value]));return p.year+'-'+p.month+'-'+p.day;};
  const read=(key,age)=>{try{const v=JSON.parse(localStorage.getItem(key));return v&&Date.now()-v.saved>=0&&Date.now()-v.saved<age?v.data:null;}catch{return null;}};
  const write=(key,data)=>{try{localStorage.setItem(key,JSON.stringify({saved:Date.now(),data}));}catch{}};
  async function fetchJSON(url){const c=new AbortController(),timer=setTimeout(()=>c.abort(),6500);try{const r=await fetch(url,{signal:c.signal,credentials:'omit',headers:{Accept:'application/json'}});if(!r.ok)throw new Error('Provider unavailable');return await r.json();}finally{clearTimeout(timer);}}
  const sun='<circle cx="24" cy="20" r="7"/><path d="M24 6v3m0 22v3M10 20h3m22 0h3M14 10l2 2m16 16 2 2m0-20-2 2M16 28l-2 2"/>';
  const moon='<path d="M29 7a14 14 0 1 0 12 22A13 13 0 0 1 29 7Z"/>';
  const cloud='<path d="M13 31h23a8 8 0 0 0 0-16 12 12 0 0 0-23-2 9 9 0 0 0 0 18Z"/>';
  function weatherIcon(code,night){
    let paths,label;
    if(code===0||code===1){paths=night?moon:sun;label=night?'Céu limpo à noite':'Céu limpo ou pouco nublado';}
    else if(code===2||code===3){paths=cloud;label='Nublado ou parcialmente nublado';}
    else if(code===45||code===48){paths=cloud+'<path d="M10 37h28m-25 5h22"/>';label='Nevoeiro';}
    else if(code>=95){paths=cloud+'<path d="m25 29-6 9h7l-4 7"/>';label='Trovoadas';}
    else if([71,73,75,77,85,86].includes(code)){paths=cloud+'<path d="M15 36v7m-3-3h6m11-4v7m-3-3h6"/>';label='Neve';}
    else if(code>=51&&code<=82){paths=cloud+'<path d="m16 35-3 7m13-7-3 7m13-7-3 7"/>';label='Chuva';}
    else {paths='<path d="M15 24h18"/>';label='Condição indisponível';}
    return {html:'<svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">'+paths+'</svg>',label};
  }
  function renderWeather(root,data){
    if(!Array.isArray(data?.hourly?.time)||!Array.isArray(data.hourly.temperature_2m)||!Array.isArray(data.hourly.weather_code))throw new Error('Invalid forecast');
    const day=today();if(data.hourly.time[0]?.slice(0,10)!==day)throw new Error('Forecast for another day');
    const periods=[[6,12],[12,18],[18,24]],models=periods.map(([from,to],n)=>{
      const indexes=data.hourly.time.map((t,i)=>t.slice(0,10)===day&&Number(t.slice(11,13))>=from&&Number(t.slice(11,13))<to?i:-1).filter(i=>i>=0);
      const temps=indexes.map(i=>data.hourly.temperature_2m[i]).filter(finite).map(Number),codes=indexes.map(i=>data.hourly.weather_code[i]).filter(finite).map(Number);
      if(!temps.length||!codes.length)throw new Error('Incomplete forecast');
      const counts=new Map();codes.forEach(c=>counts.set(c,(counts.get(c)||0)+1));const code=[...counts].sort((a,b)=>b[1]-a[1])[0][0];
      return {max:Math.round(Math.max(...temps)),min:Math.round(Math.min(...temps)),icon:weatherIcon(code,n===2)};
    });
    root.querySelectorAll('[data-weather-period]').forEach((el,i)=>{const m=models[i];el.querySelector('[data-weather-icon]').innerHTML=m.icon.html;el.querySelector('[data-weather-icon]').setAttribute('title',m.icon.label);el.querySelector('[data-weather-temps]').textContent=m.max+'° / '+m.min+'°';el.setAttribute('aria-label',el.dataset.weatherPeriod+': '+m.icon.label+', máxima '+m.max+' graus e mínima '+m.min+' graus.');});
    root.dataset.state='ready';return true;
  }
  async function startWeather(root){
    const select=root.querySelector('select'),status=root.querySelector('[data-weather-status]');let request=0;
    async function load(){
      const version=++request,key=select.value,city=cities[key];if(!city)return;
      const storage='g73-weather-v1-'+key+'-'+today(),cached=read(storage,15*60*1000);
      if(cached){try{renderWeather(root,cached);status.textContent='Dados Open-Meteo • previsão para hoje';return;}catch{}}
      root.dataset.state='loading';status.textContent='Consultando a previsão…';root.querySelectorAll('[data-weather-temps]').forEach(el=>el.textContent='— / —');root.querySelectorAll('[data-weather-icon]').forEach(el=>{el.innerHTML=weatherIcon(-1,false).html;el.removeAttribute('title');});root.querySelectorAll('[data-weather-period]').forEach(el=>el.removeAttribute('aria-label'));
      const p=new URLSearchParams({...city,hourly:'temperature_2m,weather_code',timezone:zone,forecast_days:'1',temperature_unit:'celsius'});
      try{const data=await fetchJSON('https://api.open-meteo.com/v1/forecast?'+p);if(version!==request)return;renderWeather(root,data);write(storage,data);status.textContent='Dados Open-Meteo • consulta '+stamp(new Date());}
      catch{if(version!==request)return;root.dataset.state='unavailable';status.textContent='Previsão indisponível no momento.';root.querySelectorAll('[data-weather-icon]').forEach(el=>{el.innerHTML='<svg viewBox="0 0 48 48" width="40" height="40" aria-hidden="true"><path d="M15 24h18" fill="none" stroke="currentColor" stroke-width="2"/></svg>';el.removeAttribute('title');});root.querySelectorAll('[data-weather-period]').forEach(el=>el.removeAttribute('aria-label'));}
    }
    select.addEventListener('change',load);await load();
  }
  function setQuote(root,name,value,variation,unit){
    if(!finite(value)||Number(value)<=0||!finite(variation))throw new Error('Invalid quote');
    const row=root.querySelector('[data-quote="'+name+'"]');row.querySelector('[data-quote-value]').textContent=unit==='points'?points.format(Number(value))+' pts':brl.format(Number(value));
    const v=Number(variation),cell=row.querySelector('[data-quote-change]');cell.textContent=pct.format(v)+'%';cell.className=v>0?'g73-up':v<0?'g73-down':'g73-flat';row.dataset.state='ready';
  }
  async function startEconomy(root){
    const status=root.querySelector('[data-economy-status]');let successful=0,when=[];
    const load=async()=>{
      successful=0;when=[];status.textContent='Consultando cotações…';root.querySelectorAll('[data-quote]').forEach(row=>{row.dataset.state='unavailable';row.querySelector('[data-quote-value]').textContent='—';const c=row.querySelector('[data-quote-change]');c.textContent='—';c.className='g73-flat';});
      await Promise.allSettled([
        (async()=>{const key='g73-fx-v1',cached=read(key,5*60*1000),data=cached||await fetchJSON('https://economia.awesomeapi.com.br/json/last/USD-BRL,EUR-BRL');
          for(const [name,code] of [['usd','USDBRL'],['eur','EURBRL']]){const q=data?.[code];setQuote(root,name,q?.bid,q?.pctChange,'currency');if(finite(q.timestamp)){const date=new Date(Number(q.timestamp)*1000);if(Number.isFinite(date.getTime()))when.push(date);}successful++;}
          if(!cached)write(key,data);
        })(),
        (async()=>{const key='g73-ibov-v1',cached=read(key,5*60*1000),data=cached||await fetchJSON('https://api.hgbrasil.com/finance?format=json-cors');const q=data?.results?.stocks?.IBOVESPA;setQuote(root,'ibov',q?.points,q?.variation,'points');successful++;if(!cached)write(key,data);})()
      ]);
      root.dataset.state=successful===3?'ready':successful?'partial':'unavailable';
      status.textContent=successful?(when.length?'Moedas: '+stamp(new Date(Math.min(...when.map(x=>x.getTime()))))+'. ':'')+(successful===3?'Índice: HG Brasil.':'Dados não recebidos permanecem indisponíveis.'):'Cotações indisponíveis no momento.';
    };
    await load();setInterval(()=>{if(!document.hidden&&root.getBoundingClientRect().bottom>=0&&root.getBoundingClientRect().top<innerHeight)load();},5*60*1000);
  }
  const entries=[...document.querySelectorAll('[data-g73-weather]')].map(root=>[root,startWeather]).concat([...document.querySelectorAll('[data-g73-economy]')].map(root=>[root,startEconomy]));
  const begin=()=>{if('IntersectionObserver'in window){const map=new Map(entries),observer=new IntersectionObserver(list=>{list.forEach(e=>{if(e.isIntersecting){observer.unobserve(e.target);map.get(e.target)?.(e.target);}});},{rootMargin:'240px'});entries.forEach(([root])=>observer.observe(root));}else entries.forEach(([root,start])=>start(root));};
  const defer=()=>('requestIdleCallback'in window?requestIdleCallback(begin,{timeout:1500}):setTimeout(begin,0));
  if(entries.length){if(document.readyState==='complete')defer();else window.addEventListener('load',defer,{once:true});}
})();
