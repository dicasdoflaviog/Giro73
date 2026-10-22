(()=>{
  const button=document.querySelector('[data-g73-whatsapp]');
  if(!button)return;
  const url=document.querySelector('link[rel="canonical"]')?.href||location.href.split('#')[0];
  const title=document.querySelector('.g73-single h1')?.textContent.trim()||document.title;
  button.href='https://wa.me/?text='+encodeURIComponent(title+'\n'+url);
})();
