/* ---------- shared UI helpers ---------- */
let chartReg={};
function drawChart(id,cfg){ const el=document.getElementById(id); if(!el)return;
  if(chartReg[id])chartReg[id].destroy(); chartReg[id]=new Chart(el,cfg); }
function makeQR(id,text){ const el=document.getElementById(id); if(!el)return; el.innerHTML="";
  try{ new QRCode(el,{text,width:148,height:148,correctLevel:QRCode.CorrectLevel.M}); }catch(e){ el.textContent="QR unavailable"; } }
function emptyState(title,sub,btn,act){ return `<div class="card"><div class="empty">
  <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
  <b>${title}</b>${sub}${btn?`<div style="margin-top:14px"><button class="btn btn-primary" onclick="${act}">${btn}</button></div>`:''}</div></div>`; }
function openModal(html){ closeModal(); const bg=document.createElement("div"); bg.className="modal-bg"; bg.id="modalBg";
  bg.innerHTML=`<div class="modal ${html.includes('form-grid')||html.includes('qr-box')?'wide':''}">${html}</div>`;
  bg.addEventListener("mousedown",e=>{if(e.target===bg)closeModal();}); document.body.appendChild(bg); }
function closeModal(){ const m=$("#modalBg"); if(m)m.remove(); }
function closeNotif(){ const p=$("#notifPanel"); if(p)p.remove(); App.notifOpen=false; document.removeEventListener("click",notifOutside); }
function notifOutside(e){ if(!e.target.closest("#notifPanel")&&!e.target.closest("#bell")) closeNotif(); }
function toast(msg,type="info"){
  const t=document.createElement("div"); t.className="toast "+type;
  const ic={ok:'<path d="M20 6 9 17l-5-5"/>',err:'<path d="M18 6 6 18M6 6l12 12"/>',info:'<path d="M12 16v-4M12 8h.01"/><circle cx="12" cy="12" r="9"/>'}[type];
  t.innerHTML=`<div class="ti"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.6">${ic}</svg></div><span>${esc(msg)}</span>`;
  $("#toasts").appendChild(t); setTimeout(()=>{t.style.opacity="0";t.style.transition=".3s";setTimeout(()=>t.remove(),300);},3200);
}
document.addEventListener("keydown",e=>{if(e.key==="Escape"){closeModal();closeNotif();}});
