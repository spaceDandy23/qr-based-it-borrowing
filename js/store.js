const DB_KEY = "babadook:db:v3";

/* ---- storage ---- */
const Store = {
  async load(){
    if(window.storage){
      try{ const r=await window.storage.get(DB_KEY); if(r&&r.value) return JSON.parse(r.value);}catch(e){}
    }
    return null;
  },
  async save(db){
    if(window.storage){
      try{ await window.storage.set(DB_KEY, JSON.stringify(db)); }catch(e){}
    }
  }
};
