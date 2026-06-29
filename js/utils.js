const $ = (s,r=document)=>r.querySelector(s);
const esc = s => String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const todayISO = ()=> new Date().toISOString().slice(0,10);
const fmtDate = d => d? new Date(d+(d.length===10?"T00:00:00":"")).toLocaleDateString(undefined,{month:"short",day:"numeric",year:"numeric"}):"—";
const fmtDateTime = d => d? new Date(d).toLocaleString(undefined,{month:"short",day:"numeric",hour:"numeric",minute:"2-digit"}):"—";
const daysBetween = (a,b)=> Math.round((new Date(b)-new Date(a))/86400000);
const uid = p => p+"-"+Math.random().toString(36).slice(2,8);
const avatarColor = n => ["#7a0d14","#3c7a4a","#8a5a12","#5b4a8a","#a3131c","#4a4540"][[...n].reduce((a,c)=>a+c.charCodeAt(0),0)%6];
const initials = n => n.split(" ").map(w=>w[0]).join("").slice(0,2).toUpperCase();
