/* ---- seed data ---- */
function seed(){
  const today = new Date();
  const offset = n => { const d=new Date(today); d.setDate(d.getDate()+n); return d.toISOString().slice(0,10); };
  const E=[];
  const add=(tag,name,cat,serial,cond,status,loc,pd)=>E.push({id:uid("eq"),assetTag:tag,name,category:cat,serial,condition:cond,status,location:loc,purchaseDate:pd,image:""});
  add("FDCP-LT-001","Dell Latitude 5440","Laptop","DL5440-9X2K7","Good","Available","Storage Room A","2024-02-12");
  add("FDCP-LT-002","MacBook Pro 14 M3","Laptop","C02XJ3MPLVDQ","Excellent","Checked Out","IT Office","2024-06-01");
  add("FDCP-LT-003","Lenovo ThinkPad T14","Laptop","TP14-44A0091","Good","Available","Storage Room A","2023-11-08");
  add("FDCP-MN-001",'LG 27" UltraFine 4K',"Monitor","LG27UF-7781","Good","Available","Storage Room B","2023-09-20");
  add("FDCP-MN-002",'Dell 24" P2422H',"Monitor","DP2422-3320","Fair","Maintenance","Repair Bench","2022-05-14");
  add("FDCP-PJ-001","Epson EB-2250U Projector","Projector","EP2250-55120","Good","Reserved","AV Closet","2023-01-30");
  add("FDCP-CM-001","Sony A7 IV Mirrorless","Camera","SNYA7IV-0098","Excellent","Available","Media Vault","2024-03-18");
  add("FDCP-CM-002","Canon EOS R6","Camera","CANR6-66231","Good","Checked Out","Media Vault","2023-07-22");
  add("FDCP-NW-001","Ubiquiti UniFi Switch 24","Networking","US24-PoE-1142","Good","Available","Server Room","2023-04-10");
  add("FDCP-PR-001","HP LaserJet M404dn","Printer","HPM404-7781","Good","Available","Floor 2","2022-12-01");
  add("FDCP-TB-001",'iPad Pro 11"',"Tablet","IPADP11-3349","Excellent","Available","IT Office","2024-05-05");
  add("FDCP-PH-001","Logitech MX Master 3S","Peripheral","MXM3S-22019","Good","Damaged","Storage Room A","2023-08-15");
  add("FDCP-AU-001","Rode Wireless GO II","Audio","RODEGO2-1180","Good","Available","Media Vault","2023-10-02");
  add("FDCP-DT-001","HP EliteDesk 800 G9","Desktop","HPED800-4410","Good","Available","Storage Room B","2024-01-19");

  const byTag = t => E.find(e=>e.assetTag===t);
  const R=[];
  const mkReq=(o)=>R.push(Object.assign({id:uid("req"),createdAt:o.createdAt||new Date().toISOString(),decidedAt:null,checkedOutAt:null,returnedAt:null,returnCondition:null,extension:null,damage:null},o));
  // checked out (one overdue)
  mkReq({equipmentId:byTag("FDCP-LT-002").id, employee:"Jordan Cruz", email:"jordan@babadook.gov", purpose:"Field documentation for the regional film workshop.", startDate:offset(-9), endDate:offset(-2), status:"Checked Out", createdAt:new Date(Date.now()-11*864e5).toISOString(), decidedAt:new Date(Date.now()-10*864e5).toISOString(), checkedOutAt:new Date(Date.now()-9*864e5).toISOString()});
  mkReq({equipmentId:byTag("FDCP-CM-002").id, employee:"Maria Santos", email:"maria@babadook.gov", purpose:"Photo coverage of the awards ceremony.", startDate:offset(-3), endDate:offset(4), status:"Checked Out", createdAt:new Date(Date.now()-5*864e5).toISOString(), decidedAt:new Date(Date.now()-4*864e5).toISOString(), checkedOutAt:new Date(Date.now()-3*864e5).toISOString()});
  // reserved (approved, not yet out)
  mkReq({equipmentId:byTag("FDCP-PJ-001").id, employee:"Jordan Cruz", email:"jordan@babadook.gov", purpose:"Screening setup for the cinematheque event next week.", startDate:offset(2), endDate:offset(6), status:"Approved", createdAt:new Date(Date.now()-2*864e5).toISOString(), decidedAt:new Date(Date.now()-1*864e5).toISOString()});
  // pending
  mkReq({equipmentId:byTag("FDCP-LT-001").id, employee:"Maria Santos", email:"maria@babadook.gov", purpose:"Temporary workstation while my unit is being repaired.", startDate:offset(1), endDate:offset(8), status:"Pending", createdAt:new Date(Date.now()-1*864e5).toISOString()});
  mkReq({equipmentId:byTag("FDCP-TB-001").id, employee:"Jordan Cruz", email:"jordan@babadook.gov", purpose:"On-site inventory tagging using the asset app.", startDate:offset(0), endDate:offset(3), status:"Pending", createdAt:new Date(Date.now()-6*36e5).toISOString()});
  // returned (history)
  mkReq({equipmentId:byTag("FDCP-LT-003").id, employee:"Jordan Cruz", email:"jordan@babadook.gov", purpose:"Off-site editing during the festival.", startDate:offset(-30), endDate:offset(-22), status:"Returned", createdAt:new Date(Date.now()-33*864e5).toISOString(), decidedAt:new Date(Date.now()-32*864e5).toISOString(), checkedOutAt:new Date(Date.now()-30*864e5).toISOString(), returnedAt:new Date(Date.now()-22*864e5).toISOString(), returnCondition:"Good"});
  // rejected
  mkReq({equipmentId:byTag("FDCP-CM-001").id, employee:"Maria Santos", email:"maria@babadook.gov", purpose:"Personal weekend trip.", startDate:offset(-12), endDate:offset(-8), status:"Rejected", createdAt:new Date(Date.now()-14*864e5).toISOString(), decidedAt:new Date(Date.now()-13*864e5).toISOString(), rejectReason:"Requests must be for official use only."});

  const logs=[];
  const log=(actor,action,detail,t)=>logs.unshift({id:uid("log"),ts:t||new Date().toISOString(),actor,action,detail});
  log("System","Seed","Initialized demo inventory and request records.", new Date(Date.now()-34*864e5).toISOString());

  return {equipment:E, requests:R, logs, notifications:[], users:{
    "admin@babadook.gov":{name:"Alex Rivera", role:"admin", email:"admin@babadook.gov"},
    "jordan@babadook.gov":{name:"Jordan Cruz", role:"employee", email:"jordan@babadook.gov"}
  }};
}
