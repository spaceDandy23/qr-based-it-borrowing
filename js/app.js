/* ===================================================================== */
const App = {
  db:null, user:null, route:"dashboard", notifOpen:false,
  filters:{cat:"All",status:"All",q:""}, reqFilter:"All", invQ:"",

  async init(){
    this.db = await Store.load() || seed();
    // attach default unread admin notifications based on pending
  },
  persist(){ Store.save(this.db); },

  withFocus(render){
    const view=$("#view"), active=document.activeElement;
    let id=null, start=null, end=null;
    if(active && view && view.contains(active) && active.id){
      id=active.id; start=active.selectionStart; end=active.selectionEnd;
    }
    render();
    if(id){
      const el=document.getElementById(id);
      if(el){ el.focus(); if(start!=null && el.setSelectionRange) try{ el.setSelectionRange(start,end); }catch(e){} }
    }
  },

  /* ---------- auth ---------- */
  login(){
    const email=$("#loginEmail").value.trim().toLowerCase(), pass=$("#loginPass").value;
    const u=this.db.users[email];
    if(!u || pass!=="demo"){ toast("Incorrect email or password. Try the demo buttons.","err"); return; }
    this.enter(u);
  },
  demo(role){
    const email = role==="admin"?"admin@babadook.gov":"jordan@babadook.gov";
    this.enter(this.db.users[email]);
  },
  enter(u){
    this.user=u; this.route = u.role==="admin"?"dashboard":"browse";
    $("#auth").style.display="none"; $("#app").style.display="grid";
    $("#sideName").textContent=u.name; $("#sideRole").textContent = u.role==="admin"?"Administrator":"Employee";
    const av=$("#sideAvatar"); av.textContent=initials(u.name); av.style.background=avatarColor(u.name);
    this.renderNav(); this.go(this.route);
    toast(`Signed in as ${u.name}`,"ok");
  },
  logout(){ this.user=null; $("#app").style.display="none"; $("#auth").style.display="grid"; closeModal(); },

  toggleNav(open){ $("#sidebar").classList.toggle("open",open); $(".scrim").classList.toggle("open",open); },

  /* ---------- nav ---------- */
  navItems(){
    if(this.user.role==="admin") return [
      {sec:"Overview"},
      {id:"dashboard",label:"Dashboard",icon:'<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>'},
      {id:"requests",label:"Requests",icon:'<path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>',badge:()=>this.pendingCount()},
      {id:"loans",label:"Active Loans",icon:'<path d="M3 7h18M3 12h18M3 17h18"/>',badge:()=>this.overdueCount(),badgeColor:"bad"},
      {sec:"Manage"},
      {id:"inventory",label:"Inventory",icon:'<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>'},
      {id:"scan",label:"QR Scan",icon:'<path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/>'},
      {sec:"Insight"},
      {id:"reports",label:"Reports",icon:'<path d="M3 3v18h18"/><path d="M7 14l3-3 3 3 5-6"/>'},
      {id:"audit",label:"Activity Log",icon:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'},
    ];
    return [
      {sec:"Browse"},
      {id:"browse",label:"Equipment",icon:'<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/>'},
      {sec:"My activity"},
      {id:"myrequests",label:"My Requests",icon:'<path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>',badge:()=>this.myActiveCount()},
      {id:"history",label:"History",icon:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'},
    ];
  },
  renderNav(){
    $("#nav").innerHTML = this.navItems().map(it=>{
      if(it.sec) return `<div class="nav-label">${it.sec}</div>`;
      const b = it.badge ? it.badge() : 0;
      const badge = b ? `<span class="badge" ${it.badgeColor?'style="background:#c2810b"':''}>${b}</span>` : "";
      return `<a data-route="${it.id}" onclick="App.go('${it.id}')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${it.icon}</svg>
        <span>${it.label}</span>${badge}</a>`;
    }).join("");
  },
  go(route){
    this.route=route; this.toggleNav(false); this.notifOpen=false; closeNotif();
    document.querySelectorAll("#nav a").forEach(a=>a.classList.toggle("active",a.dataset.route===route));
    const meta = {
      dashboard:["Dashboard","Live overview of your asset inventory and loans"],
      requests:["Borrowing Requests","Review, approve, and reject incoming requests"],
      loans:["Active Loans","Checked-out items, returns, and overdue tracking"],
      inventory:["Inventory","Add, edit, and manage every tracked asset"],
      scan:["QR Scan","Quick check-out and check-in by asset tag"],
      reports:["Reports","Statistics and exportable data"],
      audit:["Activity Log","Full audit trail of every action"],
      browse:["Equipment Catalog","Find and request available equipment"],
      myrequests:["My Requests","Track the status of everything you've requested"],
      history:["Borrowing History","Your past and present loans"],
    }[route]||["",""];
    $("#pageTitle").textContent=meta[0]; $("#pageSub").textContent=meta[1];
    this.refreshBell();
    ({dashboard:this.viewDashboard, requests:this.viewRequests, loans:this.viewLoans, inventory:this.viewInventory,
      scan:this.viewScan, reports:this.viewReports, audit:this.viewAudit, browse:this.viewBrowse,
      myrequests:this.viewMyRequests, history:this.viewHistory})[route].call(this);
  },

  /* ---------- helpers / derived ---------- */
  eq(id){ return this.db.equipment.find(e=>e.id===id); },
  isOverdue(r){ return r.status==="Checked Out" && new Date(r.endDate) < new Date(todayISO()); },
  pendingCount(){ return this.db.requests.filter(r=>r.status==="Pending").length; },
  overdueCount(){ return this.db.requests.filter(r=>this.isOverdue(r)).length; },
  myActiveCount(){ return this.db.requests.filter(r=>r.email===this.user.email && ["Pending","Approved","Checked Out"].includes(r.status)).length; },
  log(action,detail){ this.db.logs.unshift({id:uid("log"),ts:new Date().toISOString(),actor:this.user.name,action,detail}); },
  notify(text,role){ this.db.notifications.unshift({id:uid("n"),ts:new Date().toISOString(),text,role,read:false}); },

  refreshNavBadges(){ this.renderNav(); document.querySelectorAll("#nav a").forEach(a=>a.classList.toggle("active",a.dataset.route===this.route)); },
  refreshBell(){
    const mine = this.db.notifications.filter(n=>n.role===this.user.role && !n.read);
    $("#bellDot").style.display = mine.length? "block":"none";
  },

  /* =================== ADMIN: DASHBOARD =================== */
  viewDashboard(){
    const eq=this.db.equipment, R=this.db.requests;
    const total=eq.length, avail=eq.filter(e=>e.status==="Available").length,
      borrowed=eq.filter(e=>e.status==="Checked Out").length,
      overdue=this.overdueCount(), pending=this.pendingCount(),
      maint=eq.filter(e=>e.status==="Maintenance"||e.status==="Damaged").length;
    const stat=(ico,bg,col,num,lbl)=>`<div class="stat"><div class="ico" style="background:${bg};color:${col}">${ico}</div><div class="num">${num}</div><div class="lbl">${lbl}</div></div>`;
    const I=(p)=>`<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${p}</svg>`;

    const recent = this.db.logs.slice(0,7);
    const pendList = R.filter(r=>r.status==="Pending").slice(0,5);

    $("#view").innerHTML = `
      <div class="grid stats" style="margin-bottom:16px">
        ${stat(I('<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>'),"#e8effe","#2563eb",total,"Total assets")}
        ${stat(I('<path d="M20 6 9 17l-5-5"/>'),"#e2f5ed","#0f9d6b",avail,"Available")}
        ${stat(I('<path d="M3 7h18M3 12h18M3 17h18"/>'),"#e8effe","#2563eb",borrowed,"Borrowed")}
        ${stat(I('<path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>'),"#fde6e8","#dc3a47",overdue,"Overdue")}
        ${stat(I('<path d="M9 11l3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>'),"#fcf1d9","#c2810b",pending,"Pending requests")}
      </div>

      <div class="two-col" style="margin-bottom:16px">
        <div class="card">
          <div class="card-head"><h3>Inventory status</h3><span class="sub">Distribution of all ${total} assets</span></div>
          <div class="card-body"><div style="height:240px"><canvas id="chStatus"></canvas></div></div>
        </div>
        <div class="card">
          <div class="card-head"><h3>By category</h3></div>
          <div class="card-body"><div style="height:240px"><canvas id="chCat"></canvas></div></div>
        </div>
      </div>

      <div class="two-col">
        <div class="card">
          <div class="card-head"><h3>Pending approvals</h3><span class="sub">${pending} waiting</span>
            <button class="btn btn-ghost btn-sm" style="margin-left:auto" onclick="App.go('requests')">View all</button></div>
          <div class="card-body" style="padding-top:6px">
            ${pendList.length? pendList.map(r=>{const e=this.eq(r.equipmentId);return `
              <div class="list-row">
                <div class="ico" style="background:var(--surface-2)">${catIcon(e?.category,20,"#5b6b82")}</div>
                <div class="t"><b>${esc(e?.name||"—")}</b><span>${esc(r.employee)} · ${fmtDate(r.startDate)}–${fmtDate(r.endDate)}</span></div>
                <div class="actions">
                  <button class="btn btn-primary btn-sm" onclick="App.approve('${r.id}')">Approve</button>
                  <button class="btn btn-ghost btn-sm" onclick="App.openReject('${r.id}')">Reject</button>
                </div>
              </div>`;}).join("")
              : `<div class="empty" style="padding:28px"><b>All caught up</b>No requests awaiting review.</div>`}
          </div>
        </div>
        <div class="card">
          <div class="card-head"><h3>Recent activity</h3>
            <button class="btn btn-ghost btn-sm" style="margin-left:auto" onclick="App.go('audit')">Full log</button></div>
          <div class="card-body" style="padding-top:6px">
            ${recent.map(l=>`<div class="list-row">
              <div class="ico" style="background:var(--surface-2);color:var(--ink-2)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg></div>
              <div class="t"><b>${esc(l.action)}</b><span>${esc(l.detail)}</span></div>
              <small style="color:var(--ink-3);white-space:nowrap">${fmtDateTime(l.ts)}</small>
            </div>`).join("")}
          </div>
        </div>
      </div>`;

    // charts
    const counts = {};
    Object.keys(EQ_STATUS).forEach(s=>counts[s]=eq.filter(e=>e.status===s).length);
    drawChart("chStatus",{type:"doughnut",
      data:{labels:Object.keys(counts),datasets:[{data:Object.values(counts),
        backgroundColor:["#0f9d6b","#2563eb","#6d4ad6","#c2810b","#dc3a47"],borderWidth:0}]},
      options:{cutout:"62%",plugins:{legend:{position:"right",labels:{usePointStyle:true,boxWidth:8,font:{family:"Inter",size:12}}}},responsive:true,maintainAspectRatio:false}});
    const catLabels=CATEGORIES.filter(c=>eq.some(e=>e.category===c));
    drawChart("chCat",{type:"bar",
      data:{labels:catLabels,datasets:[{data:catLabels.map(c=>eq.filter(e=>e.category===c).length),
        backgroundColor:"#2563eb",borderRadius:6,barThickness:18}]},
      options:{plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{font:{size:11}}},y:{beginAtZero:true,ticks:{precision:0,stepSize:1}}},responsive:true,maintainAspectRatio:false}});
  },

  /* =================== ADMIN: REQUESTS =================== */
  viewRequests(){
    const filt=this.reqFilter;
    const list=this.db.requests.filter(r=>filt==="All"?true:r.status===filt)
      .sort((a,b)=>new Date(b.createdAt)-new Date(a.createdAt));
    const counts=s=>this.db.requests.filter(r=>r.status===s).length;
    const segs=["All","Pending","Approved","Checked Out","Returned","Rejected"];
    $("#view").innerHTML = `
      <div class="toolbar">
        <div class="seg">${segs.map(s=>`<button class="${filt===s?'active':''}" onclick="App.reqFilter='${s}';App.viewRequests()">${s}${s==='Pending'&&counts('Pending')?` (${counts('Pending')})`:''}</button>`).join("")}</div>
      </div>
      ${list.length? `<div class="tbl-wrap"><table>
        <thead><tr><th>Equipment</th><th>Requested by</th><th>Purpose</th><th>Period</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>${list.map(r=>this.requestRow(r)).join("")}</tbody></table></div>`
        : emptyState("No requests","Nothing matches this filter yet.")}`;
  },
  requestRow(r){
    const e=this.eq(r.equipmentId);
    const overdue=this.isOverdue(r);
    let act="";
    if(r.status==="Pending") act=`<button class="btn btn-primary btn-sm" onclick="App.approve('${r.id}')">Approve</button>
      <button class="btn btn-ghost btn-sm" onclick="App.openReject('${r.id}')">Reject</button>`;
    else if(r.status==="Approved") act=`<button class="btn btn-primary btn-sm" onclick="App.checkOut('${r.id}')">Check out</button>`;
    else if(r.status==="Checked Out") act=`<button class="btn btn-primary btn-sm" onclick="App.openCheckIn('${r.id}')">Check in</button>
      ${r.extension?`<button class="btn btn-ghost btn-sm" onclick="App.resolveExtension('${r.id}',true)">Extend ✓</button>`:''}`;
    else act=`<button class="btn btn-ghost btn-sm" onclick="App.openRequestDetail('${r.id}')">View</button>`;
    return `<tr>
      <td><div class="row-main">${esc(e?.name||"—")}</div><div class="row-sub mono">${esc(e?.assetTag||"")}</div></td>
      <td><div class="row-main">${esc(r.employee)}</div><div class="row-sub">${esc(r.email)}</div></td>
      <td style="max-width:240px"><div class="row-sub" style="color:var(--ink-2)">${esc(r.purpose)}</div>
        ${r.extension?`<span class="flag" style="color:var(--warn)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></svg>Extension requested → ${fmtDate(r.extension.newEnd)}</span>`:''}
        ${r.damage?`<span class="flag"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>Damage reported</span>`:''}</td>
      <td><div class="row-sub">${fmtDate(r.startDate)}<br>→ ${fmtDate(r.endDate)}</div>${overdue?'<span class="pill p-bad" style="margin-top:3px">Overdue</span>':''}</td>
      <td>${pill(r.status,REQ_STATUS)}</td>
      <td><div class="actions">${act}</div></td>
    </tr>`;
  },

  approve(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    if(e.status!=="Available" && e.status!=="Reserved"){ toast(`${e.name} is ${e.status} and can't be reserved.`,"err"); return; }
    r.status="Approved"; r.decidedAt=new Date().toISOString(); e.status="Reserved";
    this.log("Request approved",`${e.name} (${e.assetTag}) reserved for ${r.employee}.`);
    this.notify(`Your request for ${e.name} was approved and reserved.`,"employee");
    toast("Request approved — equipment reserved.","ok"); this.persist(); this.afterMutate();
  },
  openReject(id){
    openModal(`<div class="modal-head"><div><h3>Reject request</h3><p>Let the requester know why.</p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body"><div class="field"><label>Reason (optional)</label><textarea id="rejReason" rows="3" placeholder="e.g. Equipment is reserved for another event that week."></textarea></div></div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="App.reject('${id}')">Reject request</button></div>`);
  },
  reject(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    r.status="Rejected"; r.decidedAt=new Date().toISOString(); r.rejectReason=$("#rejReason").value.trim();
    this.log("Request rejected",`${e.name} request from ${r.employee} declined.`);
    this.notify(`Your request for ${e.name} was rejected.`,"employee");
    closeModal(); toast("Request rejected.","info"); this.persist(); this.afterMutate();
  },
  checkOut(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    r.status="Checked Out"; r.checkedOutAt=new Date().toISOString(); e.status="Checked Out";
    this.log("Checked out",`${e.name} (${e.assetTag}) checked out to ${r.employee}.`);
    this.notify(`${e.name} is checked out to you. Due ${fmtDate(r.endDate)}.`,"employee");
    toast(`${e.name} checked out to ${r.employee}.`,"ok"); this.persist(); this.afterMutate();
  },
  openCheckIn(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    openModal(`<div class="modal-head"><div><h3>Check in equipment</h3><p>${esc(e.name)} · <span class="mono">${esc(e.assetTag)}</span></p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body">
        <div class="field"><label>Returned condition</label>
          <select id="ciCond" class="flt" style="width:100%">
            <option>Excellent</option><option selected>Good</option><option>Fair</option><option>Poor</option><option>Damaged</option></select></div>
        <div class="field"><label>Notes (optional)</label><textarea id="ciNotes" rows="2" placeholder="Any observations on return…"></textarea></div>
        <p class="hint">Marking the condition as <b>Damaged</b> flags the item for maintenance instead of returning it to the available pool.</p>
      </div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="App.checkIn('${id}')">Confirm check-in</button></div>`);
  },
  checkIn(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    const cond=$("#ciCond").value;
    r.status="Returned"; r.returnedAt=new Date().toISOString(); r.returnCondition=cond;
    e.condition = cond==="Damaged"?"Poor":cond;
    e.status = cond==="Damaged"?"Maintenance":"Available";
    this.log("Checked in",`${e.name} returned by ${r.employee} in ${cond} condition.`);
    this.notify(`Return confirmed for ${e.name}. Thank you.`,"employee");
    closeModal(); toast(`${e.name} checked in (${cond}).`,"ok"); this.persist(); this.afterMutate();
  },
  resolveExtension(id,approve){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    if(approve){ r.endDate=r.extension.newEnd; this.notify(`Extension approved — ${e.name} now due ${fmtDate(r.endDate)}.`,"employee");
      this.log("Extension approved",`${e.name} due date moved to ${fmtDate(r.endDate)}.`); toast("Extension approved.","ok"); }
    r.extension=null; this.persist(); this.afterMutate();
  },

  /* =================== ADMIN: ACTIVE LOANS =================== */
  viewLoans(){
    const loans=this.db.requests.filter(r=>["Checked Out","Approved"].includes(r.status))
      .sort((a,b)=>new Date(a.endDate)-new Date(b.endDate));
    const overdue=loans.filter(r=>this.isOverdue(r));
    $("#view").innerHTML = `
      ${overdue.length?`<div class="card" style="margin-bottom:16px;border-color:#f4cfd3">
        <div class="card-head" style="background:var(--bad-soft);border-bottom-color:#f4cfd3">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc3a47" stroke-width="2"><path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
          <h3 style="color:#b32a36">${overdue.length} overdue item${overdue.length>1?'s':''}</h3>
          <span class="sub" style="color:#b32a36">Reminders have been sent to borrowers</span></div></div>`:''}
      <div class="tbl-wrap"><table>
        <thead><tr><th>Equipment</th><th>Borrower</th><th>Checked out</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>${loans.length? loans.map(r=>{const e=this.eq(r.equipmentId);const od=this.isOverdue(r);
          const daysLeft = daysBetween(todayISO(), r.endDate);
          return `<tr>
            <td><div class="row-main">${esc(e?.name)}</div><div class="row-sub mono">${esc(e?.assetTag)}</div></td>
            <td><div class="row-main">${esc(r.employee)}</div><div class="row-sub">${esc(r.email)}</div></td>
            <td class="row-sub">${r.checkedOutAt?fmtDate(r.checkedOutAt.slice(0,10)):'Not yet'}</td>
            <td><div class="row-main">${fmtDate(r.endDate)}</div><div class="row-sub" style="color:${od?'var(--bad)':daysLeft<=2?'var(--warn)':'var(--ink-3)'}">${od?`${Math.abs(daysLeft)}d overdue`:`${daysLeft}d left`}</div></td>
            <td>${r.status==="Approved"?pill("Reserved",{Reserved:"p-reserved"}):pill(od?"Overdue":"Checked Out",{Overdue:"p-bad","Checked Out":"p-info"})}</td>
            <td><div class="actions">
              ${r.status==="Approved"?`<button class="btn btn-primary btn-sm" onclick="App.checkOut('${r.id}')">Check out</button>`
                :`<button class="btn btn-primary btn-sm" onclick="App.openCheckIn('${r.id}')">Check in</button>
                  ${od?`<button class="btn btn-ghost btn-sm" onclick="App.remind('${r.id}')">Remind</button>`:''}`}
            </div></td>
          </tr>`;}).join("")
          : `<tr><td colspan="6">${emptyState("No active loans","Nothing is currently checked out or reserved.")}</td></tr>`}
        </tbody></table></div>`;
  },
  remind(id){ const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    this.notify(`Reminder: ${e.name} is overdue. Please return it as soon as possible.`,"employee");
    this.log("Reminder sent",`Overdue reminder sent to ${r.employee} for ${e.name}.`);
    toast(`Reminder sent to ${r.employee}.`,"info"); this.persist(); },

  /* =================== ADMIN: INVENTORY =================== */
  viewInventory(){ this.withFocus(()=>this._viewInventory()); },
  _viewInventory(){
    const q=this.invQ.toLowerCase();
    const list=this.db.equipment.filter(e=>!q || (e.name+e.assetTag+e.serial+e.category).toLowerCase().includes(q));
    $("#view").innerHTML = `
      <div class="toolbar">
        <div class="input-wrap grow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
          <input id="invSearch" class="flt" style="width:100%" placeholder="Search by name, tag, or serial…" value="${esc(this.invQ)}" oninput="App.invQ=this.value;App.viewInventory()"></div>
        <button class="btn btn-ghost" onclick="App.exportCSV('equipment')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Export CSV</button>
        <button class="btn btn-primary" onclick="App.openEquipForm()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Add equipment</button>
      </div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Asset</th><th>Tag</th><th>Category</th><th>Serial</th><th>Condition</th><th>Status</th><th>Location</th><th></th></tr></thead>
        <tbody>${list.map(e=>`<tr>
          <td><div style="display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:34px;border-radius:8px;background:var(--surface-2);display:grid;place-items:center;flex:0 0 auto">${e.image?`<img src="${esc(e.image)}" style="width:100%;height:100%;object-fit:cover;border-radius:8px">`:catIcon(e.category,18,"#5b6b82")}</div>
            <div class="row-main">${esc(e.name)}</div></div></td>
          <td class="mono row-sub" style="color:var(--ink-2)">${esc(e.assetTag)}</td>
          <td><span class="chip">${esc(e.category)}</span></td>
          <td class="mono row-sub">${esc(e.serial)}</td>
          <td class="row-sub">${esc(e.condition)}</td>
          <td>${pill(e.status,EQ_STATUS)}</td>
          <td class="row-sub">${esc(e.location)}</td>
          <td><div class="actions">
            <button class="icon-btn" style="width:32px;height:32px" title="Details / QR" onclick="App.openEquipDetail('${e.id}')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg></button>
            <button class="icon-btn" style="width:32px;height:32px" title="Edit" onclick="App.openEquipForm('${e.id}')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>
            <button class="icon-btn" style="width:32px;height:32px;color:var(--bad)" title="Delete" onclick="App.deleteEquip('${e.id}')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
          </div></td>
        </tr>`).join("")}</tbody></table></div>`;
  },
  openEquipForm(id){
    const e = id? this.eq(id) : {assetTag:"",name:"",category:"Laptop",serial:"",condition:"Good",status:"Available",location:"",purchaseDate:todayISO(),image:""};
    const opt=(arr,sel)=>arr.map(o=>`<option ${o===sel?'selected':''}>${o}</option>`).join("");
    openModal(`<div class="modal-head"><div><h3>${id?'Edit equipment':'Add equipment'}</h3><p>${id?'Update this asset record.':'Register a new asset into inventory.'}</p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body"><div class="form-grid">
        <div class="field"><label>Equipment name</label><input id="fName" value="${esc(e.name)}" placeholder="e.g. Dell Latitude 5440"></div>
        <div class="field"><label>Asset tag</label><input id="fTag" class="mono" value="${esc(e.assetTag)}" placeholder="FDCP-LT-001"></div>
        <div class="field"><label>Category</label><select id="fCat">${opt(CATEGORIES,e.category)}</select></div>
        <div class="field"><label>Serial number</label><input id="fSerial" class="mono" value="${esc(e.serial)}" placeholder="Serial / IMEI"></div>
        <div class="field"><label>Condition</label><select id="fCond">${opt(["Excellent","Good","Fair","Poor"],e.condition)}</select></div>
        <div class="field"><label>Status</label><select id="fStatus">${opt(["Available","Reserved","Checked Out","Maintenance","Damaged"],e.status)}</select></div>
        <div class="field"><label>Purchase date</label><input id="fDate" type="date" value="${e.purchaseDate}"></div>
        <div class="field"><label>Location</label><input id="fLoc" value="${esc(e.location)}" placeholder="Storage Room A"></div>
        <div class="field full"><label>Image URL (optional)</label><input id="fImg" value="${esc(e.image)}" placeholder="https://… (leave blank for a category icon)"></div>
      </div></div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="App.saveEquip('${id||''}')">${id?'Save changes':'Add to inventory'}</button></div>`);
  },
  saveEquip(id){
    const get=s=>$(s).value.trim();
    const name=get("#fName"), tag=get("#fTag");
    if(!name||!tag){ toast("Name and asset tag are required.","err"); return; }
    const data={assetTag:tag,name,category:$("#fCat").value,serial:get("#fSerial"),condition:$("#fCond").value,
      status:$("#fStatus").value,location:get("#fLoc"),purchaseDate:$("#fDate").value,image:get("#fImg")};
    if(id){ Object.assign(this.eq(id),data); this.log("Equipment updated",`${name} (${tag}) details edited.`); toast("Equipment updated.","ok"); }
    else { this.db.equipment.unshift(Object.assign({id:uid("eq")},data)); this.log("Equipment added",`${name} (${tag}) added to inventory.`); toast("Equipment added.","ok"); }
    closeModal(); this.persist(); this.afterMutate();
  },
  deleteEquip(id){
    const e=this.eq(id);
    if(["Checked Out","Reserved"].includes(e.status)){ toast("Can't delete an item that's reserved or on loan.","err"); return; }
    openModal(`<div class="modal-head"><div><h3>Delete equipment</h3><p>This removes the asset record permanently.</p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body"><p>Delete <b>${esc(e.name)}</b> (<span class="mono">${esc(e.assetTag)}</span>)? This can't be undone.</p></div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="App.confirmDelete('${id}')">Delete</button></div>`);
  },
  confirmDelete(id){ const e=this.eq(id); this.db.equipment=this.db.equipment.filter(x=>x.id!==id);
    this.log("Equipment deleted",`${e.name} (${e.assetTag}) removed from inventory.`);
    closeModal(); toast("Equipment deleted.","info"); this.persist(); this.afterMutate(); },

  openEquipDetail(id){
    const e=this.eq(id);
    const hist=this.db.requests.filter(r=>r.equipmentId===id);
    openModal(`<div class="modal-head"><div><h3>${esc(e.name)}</h3><p class="mono">${esc(e.assetTag)}</p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body">
        <div class="detail-img" style="margin-bottom:16px">${e.image?`<img src="${esc(e.image)}">`:catIcon(e.category,72,"#b3c0d6")}</div>
        <dl class="kv" style="margin-bottom:18px">
          <dt>Status</dt><dd>${pill(e.status,EQ_STATUS)}</dd>
          <dt>Category</dt><dd>${esc(e.category)}</dd>
          <dt>Serial</dt><dd class="mono">${esc(e.serial)}</dd>
          <dt>Condition</dt><dd>${esc(e.condition)}</dd>
          <dt>Location</dt><dd>${esc(e.location)}</dd>
          <dt>Purchased</dt><dd>${fmtDate(e.purchaseDate)}</dd>
          <dt>Loan records</dt><dd>${hist.length}</dd>
        </dl>
        <div class="card-head" style="padding:0 0 10px;border:none"><h3>Asset QR code</h3></div>
        <div class="qr-box">
          <div class="qr" id="qrTarget"></div>
          <div class="qr-cap">Scan this from the <b>QR Scan</b> screen to check this item in or out. Encodes the asset tag <span class="mono">${esc(e.assetTag)}</span>.
          <div style="margin-top:10px"><button class="btn btn-ghost btn-sm" onclick="App.printQR('${esc(e.assetTag)}','${esc(e.name)}')">Print label</button></div></div>
        </div>
      </div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Close</button>
        ${e.status!=="Maintenance"?`<button class="btn btn-ghost" onclick="App.setMaintenance('${id}')">Mark maintenance</button>`:`<button class="btn btn-ghost" onclick="App.clearMaintenance('${id}')">Return to service</button>`}
        <button class="btn btn-primary" onclick="closeModal();App.openEquipForm('${id}')">Edit</button></div>`);
    makeQR("qrTarget", e.assetTag);
  },
  setMaintenance(id){ const e=this.eq(id);
    if(["Checked Out","Reserved"].includes(e.status)){ toast("Item is on loan — check it in first.","err"); return; }
    e.status="Maintenance"; this.log("Maintenance",`${e.name} marked under maintenance.`);
    closeModal(); toast(`${e.name} marked for maintenance.`,"info"); this.persist(); this.afterMutate(); },
  clearMaintenance(id){ const e=this.eq(id); e.status="Available"; this.log("Maintenance cleared",`${e.name} returned to available pool.`);
    closeModal(); toast(`${e.name} is available again.`,"ok"); this.persist(); this.afterMutate(); },
  printQR(tag,name){
    const w=window.open("","_blank","width=420,height=520");
    if(!w){ toast("Allow pop-ups to print labels.","err"); return; }
    w.document.write(`<title>${tag}</title><style>body{font-family:Inter,sans-serif;text-align:center;padding:30px}#q{display:inline-block;padding:14px;border:1px solid #ddd;border-radius:12px}h2{font-size:16px;margin:14px 0 2px}p{font-family:monospace;color:#555;margin:0}</style><div id="q"></div><h2>${name}</h2><p>${tag}</p><scr`+`ipt src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></scr`+`ipt><scr`+`ipt>new QRCode(document.getElementById("q"),{text:"${tag}",width:200,height:200});setTimeout(()=>window.print(),400);</scr`+`ipt>`);
    w.document.close();
  },

  /* =================== ADMIN: QR SCAN =================== */
  viewScan(){
    const checkedOut=this.db.requests.filter(r=>r.status==="Checked Out");
    const reserved=this.db.requests.filter(r=>r.status==="Approved");
    $("#view").innerHTML = `
      <div class="two-col">
        <div class="card">
          <div class="card-head"><h3>Scan or enter a tag</h3><span class="sub">Quick check-out / check-in</span></div>
          <div class="card-body">
            <div class="scan-target" style="margin-bottom:14px">
              <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#9aa8c0" stroke-width="1.6"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg>
              <p style="margin:10px 0 0;color:var(--ink-3);font-size:13px">Point a scanner at an asset QR code, or type the tag below.</p>
            </div>
            <div class="field"><label>Asset tag</label>
              <div style="display:flex;gap:8px">
                <input id="scanTag" class="mono" placeholder="FDCP-LT-002" style="flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:10px" onkeydown="if(event.key==='Enter')App.scanLookup()">
                <button class="btn btn-primary" onclick="App.scanLookup()">Look up</button>
              </div>
              <p class="hint">Try a checked-out tag like <b>FDCP-LT-002</b> or a reserved one like <b>FDCP-PJ-001</b>.</p>
            </div>
            <div id="scanResult"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-head"><h3>Ready to process</h3></div>
          <div class="card-body" style="padding-top:6px">
            ${[...reserved,...checkedOut].length? [...reserved,...checkedOut].map(r=>{const e=this.eq(r.equipmentId);return `
              <div class="list-row">
                <div class="ico" style="background:var(--surface-2)">${catIcon(e.category,18,"#5b6b82")}</div>
                <div class="t"><b>${esc(e.name)}</b><span class="mono">${esc(e.assetTag)} · ${esc(r.employee)}</span></div>
                <button class="btn btn-ghost btn-sm" onclick="document.getElementById('scanTag').value='${esc(e.assetTag)}';App.scanLookup()">${r.status==="Approved"?'Check out':'Check in'}</button>
              </div>`;}).join("") : `<div class="empty" style="padding:24px"><b>Nothing pending</b>No items to check out or in.</div>`}
          </div>
        </div>
      </div>`;
  },
  scanLookup(){
    const tag=$("#scanTag").value.trim().toUpperCase();
    const e=this.db.equipment.find(x=>x.assetTag.toUpperCase()===tag);
    const box=$("#scanResult");
    if(!e){ box.innerHTML=`<div class="req-msg" style="border-color:#f4cfd3;background:var(--bad-soft);color:#b32a36">No asset found for tag <b class="mono">${esc(tag)}</b>.</div>`; return; }
    const r=this.db.requests.find(x=>x.equipmentId===e.id && ["Checked Out","Approved"].includes(x.status));
    let action="";
    if(r&&r.status==="Approved") action=`<button class="btn btn-primary btn-block" style="margin-top:12px" onclick="App.checkOut('${r.id}');App.go('scan')">Check out to ${esc(r.employee)}</button>`;
    else if(r&&r.status==="Checked Out") action=`<button class="btn btn-primary btn-block" style="margin-top:12px" onclick="App.openCheckIn('${r.id}')">Check in from ${esc(r.employee)}</button>`;
    else action=`<div class="hint" style="margin-top:10px">This item is <b>${esc(e.status)}</b> — no open loan to process.</div>`;
    box.innerHTML=`<div class="card" style="box-shadow:none;border-color:var(--line)"><div class="card-body" style="display:flex;gap:12px;align-items:center">
      <div style="width:46px;height:46px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;flex:0 0 auto">${catIcon(e.category,24,"#5b6b82")}</div>
      <div style="flex:1"><div class="row-main">${esc(e.name)}</div><div class="row-sub mono">${esc(e.assetTag)}</div></div>
      ${pill(e.status,EQ_STATUS)}</div>${action?`<div style="padding:0 18px 16px">${action}</div>`:''}</div>`;
  },

  /* =================== ADMIN: REPORTS =================== */
  viewReports(){
    const eq=this.db.equipment, R=this.db.requests;
    const util = eq.length? Math.round(eq.filter(e=>["Checked Out","Reserved"].includes(e.status)).length/eq.length*100):0;
    const returned=R.filter(r=>r.status==="Returned");
    const onTime=returned.filter(r=>new Date(r.returnedAt)<=new Date(r.endDate+"T23:59:59")).length;
    const onTimeRate=returned.length?Math.round(onTime/returned.length*100):100;
    // most borrowed
    const counts={}; R.forEach(r=>{const e=this.eq(r.equipmentId); if(e)counts[e.name]=(counts[e.name]||0)+1;});
    const top=Object.entries(counts).sort((a,b)=>b[1]-a[1]).slice(0,6);
    $("#view").innerHTML=`
      <div class="toolbar"><div class="grow"></div>
        <button class="btn btn-ghost" onclick="App.exportCSV('equipment')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Inventory CSV</button>
        <button class="btn btn-primary" onclick="App.exportCSV('history')"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Borrowing history CSV</button></div>
      <div class="grid stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px">
        <div class="stat"><div class="num">${util}%</div><div class="lbl">Utilization rate</div></div>
        <div class="stat"><div class="num">${R.length}</div><div class="lbl">Total requests</div></div>
        <div class="stat"><div class="num">${returned.length}</div><div class="lbl">Completed loans</div></div>
        <div class="stat"><div class="num">${onTimeRate}%</div><div class="lbl">Returned on time</div></div>
      </div>
      <div class="two-col">
        <div class="card"><div class="card-head"><h3>Most borrowed equipment</h3></div>
          <div class="card-body"><div style="height:260px"><canvas id="chTop"></canvas></div></div></div>
        <div class="card"><div class="card-head"><h3>Request outcomes</h3></div>
          <div class="card-body"><div style="height:260px"><canvas id="chOut"></canvas></div></div></div>
      </div>`;
    drawChart("chTop",{type:"bar",
      data:{labels:top.map(t=>t[0]),datasets:[{data:top.map(t=>t[1]),backgroundColor:"#6d4ad6",borderRadius:6,barThickness:16}]},
      options:{indexAxis:"y",plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,ticks:{precision:0,stepSize:1}},y:{grid:{display:false},ticks:{font:{size:11}}}},responsive:true,maintainAspectRatio:false}});
    const outc={}; ["Pending","Approved","Checked Out","Returned","Rejected"].forEach(s=>outc[s]=R.filter(r=>r.status===s).length);
    drawChart("chOut",{type:"doughnut",
      data:{labels:Object.keys(outc),datasets:[{data:Object.values(outc),backgroundColor:["#c2810b","#0f9d6b","#2563eb","#5b6b82","#dc3a47"],borderWidth:0}]},
      options:{cutout:"60%",plugins:{legend:{position:"right",labels:{usePointStyle:true,boxWidth:8,font:{size:12}}}},responsive:true,maintainAspectRatio:false}});
  },
  exportCSV(kind){
    let rows,name;
    if(kind==="equipment"){ name="inventory";
      rows=[["Asset Tag","Name","Category","Serial","Condition","Status","Location","Purchase Date"]];
      this.db.equipment.forEach(e=>rows.push([e.assetTag,e.name,e.category,e.serial,e.condition,e.status,e.location,e.purchaseDate]));
    } else { name="borrowing-history";
      rows=[["Asset Tag","Equipment","Borrower","Email","Purpose","Start","End","Status","Returned Condition"]];
      this.db.requests.forEach(r=>{const e=this.eq(r.equipmentId);rows.push([e?.assetTag||"",e?.name||"",r.employee,r.email,r.purpose,r.startDate,r.endDate,r.status,r.returnCondition||""]);});
    }
    const csv=rows.map(r=>r.map(c=>`"${String(c).replace(/"/g,'""')}"`).join(",")).join("\n");
    const blob=new Blob([csv],{type:"text/csv"}); const url=URL.createObjectURL(blob);
    const a=document.createElement("a"); a.href=url; a.download=`babadook-${name}-${todayISO()}.csv`; a.click(); URL.revokeObjectURL(url);
    this.log("Export",`${name} exported to CSV.`); toast("CSV downloaded.","ok");
  },

  /* =================== ADMIN: AUDIT =================== */
  viewAudit(){
    $("#view").innerHTML=`<div class="tbl-wrap"><table>
      <thead><tr><th style="width:170px">When</th><th style="width:160px">Actor</th><th style="width:170px">Action</th><th>Detail</th></tr></thead>
      <tbody>${this.db.logs.map(l=>`<tr>
        <td class="row-sub">${fmtDateTime(l.ts)}</td>
        <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar" style="width:24px;height:24px;font-size:10px;background:${avatarColor(l.actor)}">${initials(l.actor)}</div>${esc(l.actor)}</div></td>
        <td><span class="chip">${esc(l.action)}</span></td>
        <td class="row-sub" style="color:var(--ink-2)">${esc(l.detail)}</td></tr>`).join("")}</tbody></table></div>`;
  },

  /* =================== EMPLOYEE: BROWSE =================== */
  viewBrowse(){ this.withFocus(()=>this._viewBrowse()); },
  _viewBrowse(){
    const f=this.filters;
    let list=this.db.equipment.slice();
    if(f.cat!=="All") list=list.filter(e=>e.category===f.cat);
    if(f.status==="Available") list=list.filter(e=>e.status==="Available");
    if(f.q) list=list.filter(e=>(e.name+e.assetTag+e.category+e.serial).toLowerCase().includes(f.q.toLowerCase()));
    const cats=["All",...CATEGORIES.filter(c=>this.db.equipment.some(e=>e.category===c))];
    $("#view").innerHTML=`
      <div class="toolbar">
        <div class="input-wrap grow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
          <input id="browseSearch" class="flt" style="width:100%" placeholder="Search equipment…" value="${esc(f.q)}" oninput="App.filters.q=this.value;App.viewBrowse()"></div>
        <select class="flt" onchange="App.filters.cat=this.value;App.viewBrowse()">${cats.map(c=>`<option ${f.cat===c?'selected':''}>${c}</option>`).join("")}</select>
        <div class="seg">
          <button class="${f.status==='All'?'active':''}" onclick="App.filters.status='All';App.viewBrowse()">All</button>
          <button class="${f.status==='Available'?'active':''}" onclick="App.filters.status='Available';App.viewBrowse()">Available only</button>
        </div>
      </div>
      ${list.length? `<div class="eq-grid">${list.map(e=>this.eqCard(e)).join("")}</div>`
        : emptyState("No equipment found","Try a different category or clear your search.")}`;
  },
  eqCard(e){
    const can = e.status==="Available";
    return `<div class="eq-card">
      <div class="eq-thumb">${e.image?`<img src="${esc(e.image)}">`:`<div class="ph">${catIcon(e.category,52)}</div>`}</div>
      <div class="body">
        <div style="display:flex;justify-content:space-between;gap:8px;align-items:start">
          <div class="name">${esc(e.name)}</div>${pill(e.status,EQ_STATUS)}</div>
        <div class="tag"><span class="chip">${esc(e.category)}</span> <span class="mono" style="margin-left:4px">${esc(e.assetTag)}</span></div>
        <div class="tag">${esc(e.location)} · ${esc(e.condition)} condition</div>
        <div class="foot">
          ${can? `<button class="btn btn-primary btn-sm btn-block" onclick="App.openBorrow('${e.id}')">Request to borrow</button>`
            : `<button class="btn btn-ghost btn-sm btn-block" disabled style="opacity:.6;cursor:not-allowed">Unavailable</button>`}
        </div>
      </div></div>`;
  },
  openBorrow(id){
    const e=this.eq(id);
    const start=todayISO(); const d=new Date(); d.setDate(d.getDate()+7); const end=d.toISOString().slice(0,10);
    openModal(`<div class="modal-head"><div><h3>Request to borrow</h3><p>${esc(e.name)} · <span class="mono">${esc(e.assetTag)}</span></p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body">
        <div class="field"><label>Purpose of borrowing</label><textarea id="bPurpose" rows="3" placeholder="Describe how you'll use this equipment…"></textarea></div>
        <div class="form-grid">
          <div class="field"><label>Needed from</label><input id="bStart" type="date" value="${start}" min="${start}"></div>
          <div class="field"><label>Return by</label><input id="bEnd" type="date" value="${end}" min="${start}"></div>
        </div>
        <p class="hint">Your request goes to IT staff for review. You'll be notified once it's approved.</p>
      </div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="App.submitBorrow('${id}')">Submit request</button></div>`);
  },
  submitBorrow(id){
    const e=this.eq(id);
    const purpose=$("#bPurpose").value.trim(), start=$("#bStart").value, end=$("#bEnd").value;
    if(!purpose){ toast("Please describe the purpose.","err"); return; }
    if(!start||!end||new Date(end)<new Date(start)){ toast("Check your borrowing dates.","err"); return; }
    this.db.requests.unshift({id:uid("req"),equipmentId:id,employee:this.user.name,email:this.user.email,purpose,
      startDate:start,endDate:end,status:"Pending",createdAt:new Date().toISOString(),
      decidedAt:null,checkedOutAt:null,returnedAt:null,returnCondition:null,extension:null,damage:null});
    this.log("Request submitted",`${this.user.name} requested ${e.name} (${e.assetTag}).`);
    this.notify(`New borrowing request from ${this.user.name} for ${e.name}.`,"admin");
    closeModal(); toast("Request submitted for review.","ok"); this.persist(); this.afterMutate(); this.go("myrequests");
  },

  /* =================== EMPLOYEE: MY REQUESTS =================== */
  viewMyRequests(){
    const mine=this.db.requests.filter(r=>r.email===this.user.email).sort((a,b)=>new Date(b.createdAt)-new Date(a.createdAt));
    const active=mine.filter(r=>["Pending","Approved","Checked Out"].includes(r.status));
    const past=mine.filter(r=>["Returned","Rejected"].includes(r.status));
    const card=r=>{const e=this.eq(r.equipmentId);const od=this.isOverdue(r);
      return `<div class="card" style="margin-bottom:12px"><div class="card-body" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
        <div style="width:46px;height:46px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;flex:0 0 auto">${catIcon(e?.category,24,"#5b6b82")}</div>
        <div style="flex:1;min-width:160px"><div class="row-main">${esc(e?.name)}</div>
          <div class="row-sub mono">${esc(e?.assetTag)}</div>
          <div class="row-sub" style="margin-top:3px">${fmtDate(r.startDate)} → ${fmtDate(r.endDate)} · ${esc(r.purpose).slice(0,70)}${r.purpose.length>70?'…':''}</div>
          ${r.rejectReason?`<div class="req-msg" style="margin-top:8px">Reason: ${esc(r.rejectReason)}</div>`:''}
          ${r.extension?`<div class="hint" style="color:var(--warn);margin-top:6px">Extension to ${fmtDate(r.extension.newEnd)} pending review.</div>`:''}
        </div>
        <div style="text-align:right;display:flex;flex-direction:column;gap:8px;align-items:flex-end">
          ${od?pill("Overdue",{Overdue:"p-bad"}):pill(r.status,REQ_STATUS)}
          <div class="actions">
            ${r.status==="Checked Out"&&!r.extension?`<button class="btn btn-ghost btn-sm" onclick="App.openExtension('${r.id}')">Request extension</button>`:''}
            ${r.status==="Checked Out"&&!r.damage?`<button class="btn btn-ghost btn-sm" onclick="App.openDamage('${r.id}')">Report damage</button>`:''}
          </div>
        </div></div></div>`;};
    $("#view").innerHTML=`
      ${active.length?`<h3 style="font-size:14px;margin:0 0 12px;color:var(--ink-2)">Active (${active.length})</h3>${active.map(card).join("")}`:''}
      ${past.length?`<h3 style="font-size:14px;margin:22px 0 12px;color:var(--ink-2)">Past requests</h3>${past.map(card).join("")}`:''}
      ${!mine.length?emptyState("No requests yet","Browse the catalog to request your first item.","Browse equipment","App.go('browse')"):''}`;
  },
  openExtension(id){
    const r=this.db.requests.find(x=>x.id===id);
    const d=new Date(r.endDate); d.setDate(d.getDate()+7); const ne=d.toISOString().slice(0,10);
    openModal(`<div class="modal-head"><div><h3>Request an extension</h3><p>Current due date: ${fmtDate(r.endDate)}</p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body"><div class="field"><label>New return date</label><input id="extDate" type="date" value="${ne}" min="${r.endDate}"></div>
        <div class="field"><label>Reason</label><textarea id="extReason" rows="2" placeholder="Why do you need more time?"></textarea></div></div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-primary" onclick="App.submitExtension('${id}')">Send request</button></div>`);
  },
  submitExtension(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    r.extension={newEnd:$("#extDate").value,reason:$("#extReason").value.trim(),requestedAt:new Date().toISOString()};
    this.log("Extension requested",`${this.user.name} requested extension for ${e.name} to ${fmtDate(r.extension.newEnd)}.`);
    this.notify(`${this.user.name} requested an extension for ${e.name}.`,"admin");
    closeModal(); toast("Extension request sent.","ok"); this.persist(); this.afterMutate();
  },
  openDamage(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    openModal(`<div class="modal-head"><div><h3>Report damage</h3><p>${esc(e.name)} · <span class="mono">${esc(e.assetTag)}</span></p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body">
        <div class="field"><label>What happened?</label><textarea id="dmgDesc" rows="3" placeholder="Describe the damage or malfunction…"></textarea></div>
        <div class="field"><label>Severity</label><select id="dmgSev" class="flt" style="width:100%"><option>Minor — still usable</option><option>Moderate — partly working</option><option>Severe — not working</option></select></div>
      </div>
      <div class="modal-foot"><button class="btn btn-ghost" onclick="closeModal()">Cancel</button><button class="btn btn-danger" onclick="App.submitDamage('${id}')">Submit report</button></div>`);
  },
  submitDamage(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    const desc=$("#dmgDesc").value.trim();
    if(!desc){ toast("Please describe the damage.","err"); return; }
    r.damage={desc,severity:$("#dmgSev").value,reportedAt:new Date().toISOString()};
    this.log("Damage reported",`${this.user.name} reported damage on ${e.name}: ${desc.slice(0,60)}`);
    this.notify(`Damage reported on ${e.name} by ${this.user.name}.`,"admin");
    closeModal(); toast("Damage report submitted. IT staff notified.","info"); this.persist(); this.afterMutate();
  },

  /* =================== EMPLOYEE: HISTORY =================== */
  viewHistory(){
    const mine=this.db.requests.filter(r=>r.email===this.user.email).sort((a,b)=>new Date(b.createdAt)-new Date(a.createdAt));
    $("#view").innerHTML = mine.length? `<div class="tbl-wrap"><table>
      <thead><tr><th>Equipment</th><th>Purpose</th><th>Period</th><th>Status</th><th>Returned</th></tr></thead>
      <tbody>${mine.map(r=>{const e=this.eq(r.equipmentId);return `<tr>
        <td><div class="row-main">${esc(e?.name)}</div><div class="row-sub mono">${esc(e?.assetTag)}</div></td>
        <td class="row-sub" style="max-width:260px;color:var(--ink-2)">${esc(r.purpose)}</td>
        <td class="row-sub">${fmtDate(r.startDate)} → ${fmtDate(r.endDate)}</td>
        <td>${this.isOverdue(r)?pill("Overdue",{Overdue:"p-bad"}):pill(r.status,REQ_STATUS)}</td>
        <td class="row-sub">${r.returnedAt?`${fmtDate(r.returnedAt.slice(0,10))}<br><span style="color:var(--ink-3)">${esc(r.returnCondition||'')}</span>`:'—'}</td>
      </tr>`;}).join("")}</tbody></table></div>`
      : emptyState("No history yet","Your completed loans will appear here.");
  },
  openRequestDetail(id){
    const r=this.db.requests.find(x=>x.id===id); const e=this.eq(r.equipmentId);
    openModal(`<div class="modal-head"><div><h3>${esc(e?.name)}</h3><p class="mono">${esc(e?.assetTag)}</p></div><button class="x" onclick="closeModal()">✕</button></div>
      <div class="modal-body"><dl class="kv">
        <dt>Requested by</dt><dd>${esc(r.employee)}</dd>
        <dt>Status</dt><dd>${pill(r.status,REQ_STATUS)}</dd>
        <dt>Purpose</dt><dd style="font-weight:500">${esc(r.purpose)}</dd>
        <dt>Period</dt><dd>${fmtDate(r.startDate)} → ${fmtDate(r.endDate)}</dd>
        <dt>Submitted</dt><dd style="font-weight:500">${fmtDateTime(r.createdAt)}</dd>
        ${r.checkedOutAt?`<dt>Checked out</dt><dd style="font-weight:500">${fmtDateTime(r.checkedOutAt)}</dd>`:''}
        ${r.returnedAt?`<dt>Returned</dt><dd style="font-weight:500">${fmtDateTime(r.returnedAt)} · ${esc(r.returnCondition||'')}</dd>`:''}
        ${r.rejectReason?`<dt>Reason</dt><dd style="font-weight:500">${esc(r.rejectReason)}</dd>`:''}
        ${r.damage?`<dt>Damage</dt><dd style="font-weight:500;color:var(--bad)">${esc(r.damage.severity)} — ${esc(r.damage.desc)}</dd>`:''}
      </dl></div><div class="modal-foot"><button class="btn btn-primary" onclick="closeModal()">Close</button></div>`);
  },

  /* ---------- notifications ---------- */
  toggleNotif(){
    this.notifOpen=!this.notifOpen;
    if(!this.notifOpen){ closeNotif(); return; }
    const mine=this.db.notifications.filter(n=>n.role===this.user.role);
    const panel=document.createElement("div"); panel.className="notif-panel"; panel.id="notifPanel";
    panel.innerHTML=`<div class="h"><b>Notifications</b><button class="btn btn-ghost btn-sm" style="margin-left:auto" onclick="App.markAllRead()">Mark all read</button></div>
      <div class="notif-list">${mine.length? mine.slice(0,20).map(n=>`<div class="notif ${n.read?'':'unread'}">
        <div class="ni" style="background:var(--brand-soft);color:var(--brand)"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/></svg></div>
        <div><p>${esc(n.text)}</p><small>${fmtDateTime(n.ts)}</small></div></div>`).join("")
        : `<div class="empty" style="padding:30px"><b>You're all caught up</b>No notifications right now.</div>`}</div>`;
    document.body.appendChild(panel);
    this.db.notifications.forEach(n=>{if(n.role===this.user.role)n.read=true;});
    this.refreshBell(); this.persist();
    setTimeout(()=>document.addEventListener("click",notifOutside),0);
  },
  markAllRead(){ this.db.notifications.forEach(n=>{if(n.role===this.user.role)n.read=true;}); this.refreshBell(); this.persist();
    const p=$("#notifPanel"); if(p)p.querySelectorAll(".notif").forEach(x=>x.classList.remove("unread")); },

  globalSearch(v){
    this.filters.q=v; this.invQ=v;
    if(this.user.role==="admin"){ if(this.route!=="inventory")this.go("inventory"); else this.viewInventory(); }
    else { if(this.route!=="browse")this.go("browse"); else this.viewBrowse(); }
  },

  afterMutate(){ this.refreshNavBadges(); this.refreshBell();
    ({dashboard:this.viewDashboard,requests:this.viewRequests,loans:this.viewLoans,inventory:this.viewInventory,
      scan:this.viewScan,reports:this.viewReports,audit:this.viewAudit,browse:this.viewBrowse,
      myrequests:this.viewMyRequests,history:this.viewHistory})[this.route]?.call(this);
  },
};

App.init();
