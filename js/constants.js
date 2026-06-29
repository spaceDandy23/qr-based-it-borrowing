const CATEGORIES = ["Laptop","Desktop","Monitor","Projector","Camera","Networking","Printer","Peripheral","Tablet","Audio"];
const CAT_ICON = {
  Laptop:'<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>',
  Desktop:'<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
  Monitor:'<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
  Projector:'<rect x="2" y="7" width="20" height="10" rx="2"/><circle cx="9" cy="12" r="3"/><path d="M17 10v4"/>',
  Camera:'<path d="M3 7h4l2-2h6l2 2h4v12H3z"/><circle cx="12" cy="13" r="3.5"/>',
  Networking:'<rect x="3" y="9" width="18" height="6" rx="1"/><path d="M7 9V6M12 9V6M17 9V6M7 18v-3M12 18v-3M17 18v-3"/>',
  Printer:'<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/>',
  Peripheral:'<rect x="5" y="2" width="14" height="20" rx="7"/><path d="M12 6v5"/>',
  Tablet:'<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
  Audio:'<path d="M11 5 6 9H2v6h4l5 4zM15 9a4 4 0 0 1 0 6M18 6a8 8 0 0 1 0 12"/>'
};
const catIcon = (cat,size=40,stroke="#8d7e77")=>`<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="${stroke}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">${CAT_ICON[cat]||CAT_ICON.Peripheral}</svg>`;

/* ---- status presentation ---- */
const EQ_STATUS = {
  Available:"p-ok", "Checked Out":"p-info", Reserved:"p-reserved", Maintenance:"p-warn", Damaged:"p-bad"
};
const REQ_STATUS = {
  Pending:"p-warn", Approved:"p-ok", Rejected:"p-bad", "Checked Out":"p-info", Returned:"p-slate"
};
const pill = (s,map)=>`<span class="pill ${map[s]||'p-slate'}">${esc(s)}</span>`;
