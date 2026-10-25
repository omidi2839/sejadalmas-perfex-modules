(function(){
'use strict';
// v0.1.14.1 — canonical group resolver + explicit fixed/SQM UI modes.
var path=String(window.location.pathname||'').toLowerCase();
if(path.indexOf('/admin/warehouse/commodity_list')===-1){return;}
var CFG=window.PSP_CONFIG||{}; if(!CFG.calculateUrl||!CFG.fixedModeUrl){return;}
function qa(s,r){try{return Array.prototype.slice.call((r||document).querySelectorAll(s));}catch(e){return [];}}
function vis(e){return !!(e&&(e.offsetWidth||e.offsetHeight||e.getClientRects().length));}
function pick(s,r){var a=qa(s,r),v=a.find(vis);return v||a[0]||null;}
function val(e){return e?String(e.value==null?'':e.value).trim():'';}
function text(e){if(!e)return '';if(e.tagName==='SELECT'&&e.selectedIndex>=0)return String(e.options[e.selectedIndex].text||'').trim();return val(e);}
function norm(v){return String(v||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/\s+/g,' ').trim().toLowerCase();}
function activeModal(){var a=qa('.modal').filter(vis);return a.length?a[a.length-1]:null;}
function field(sel){var m=activeModal();return m?pick(sel,m):null;}
function group(){return field('select[name="group_id"],#group_id');}
function size(){return field('select[name="size_id"],#size_id');}
function dyn(k){return field('select[name="dyn_attr['+k+']"],input[name="dyn_attr['+k+']"]');} function design(){return dyn('design')||dyn('pattern')||field('select[name="design"],input[name="design"],select[name="pattern"],input[name="pattern"],#design,#pattern');} function code(){return field('input[name="commodity_code"],#commodity_code');}
function saleSelects(){var m=activeModal();if(!m)return [];return qa('select[name^="sale_type"],select[id^="sale_type"]',m).filter(function(e){return vis(e)&&val(e)!=='';});}
function priceForSale(s){
 var m=activeModal(),n=(s.name||s.id||''),suffix=(n.match(/(\d+)$/)||[])[1]||'',c=[];
 if(suffix)c=c.concat(qa('input[name="sale_type_price_'+suffix+'"],input[name="selling_price_'+suffix+'"],input[name="c_selling_price_'+suffix+'"],input[id="sale_type_price_'+suffix+'"],input[id="selling_price_'+suffix+'"],input[id="c_selling_price_'+suffix+'"]',m));
 var p=s.parentElement;for(var d=0;p&&d<5;d++,p=p.parentElement){var nums=qa('input[type="number"],input[name^="sale_type"][type="number"],input[name*="selling_price"],input[name^="c_selling_price"]',p).filter(vis);if(nums.length){c=c.concat(nums);break;}}
 c=c.concat(qa('input[name^="c_selling_price"],input[id^="c_selling_price"],input[name*="selling_price"]',m).filter(vis));
 return c.find(function(x){return x!==s;})||null;
}
function pairs(){return saleSelects().map(function(s){return {sale:s,price:priceForSale(s),name:text(s),key:val(s)};}).filter(function(x){return x.price;});}
function setPrice(p,v){if(!p)return;p.value=v;p.setAttribute('value',v);p.dispatchEvent(new Event('input',{bubbles:true}));p.dispatchEvent(new Event('change',{bubbles:true}));}
function lock(p){if(!p)return;p.readOnly=true;p.setAttribute('readonly','readonly');p.style.backgroundColor='#f3f4f6';}
function request(url,d,cb){var q=new URLSearchParams();Object.keys(d).forEach(function(k){q.append(k,d[k]);});fetch(url+(url.indexOf('?')>=0?'&':'?')+q.toString()+'&_psp_ts='+Date.now(),{method:'GET',credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},cache:'no-store'}).then(function(r){return r.text().then(function(t){var j;try{j=JSON.parse(t);}catch(e){throw new Error('HTTP '+r.status);}if(!r.ok)throw new Error('HTTP '+r.status);return j;});}).then(function(j){cb(null,j);}).catch(function(e){cb(e);});}
function isOnline(n){n=norm(n);return n.indexOf('آنلاین')>=0||n.indexOf('online')>=0;}
function isPresence(n){n=norm(n);return n.indexOf('حضوری')>=0||n.indexOf('نمایشگاه')>=0||n.indexOf('showroom')>=0||n.indexOf('in person')>=0;}
function ruleFor(rules,p){var nk=norm(p.key),nn=norm(p.name);return rules.find(function(r){var rk=norm(r.sale_type_key),rn=norm(r.sale_type_name);return (rk&&rk===nk)||(rn&&rn===nn)||(isOnline(nn)&&isOnline(rn))||(isPresence(nn)&&isPresence(rn));})||null;}
function removeFixedPanel(m){var x=m&&m.querySelector('#psp-fixed-price-panel');if(x)x.remove();qa('[data-psp-hidden-fixed="1"]',m).forEach(function(e){e.style.display=e.dataset.pspOldDisplay||'';delete e.dataset.pspHiddenFixed;delete e.dataset.pspOldDisplay;});}
function hideField(el){if(!el)return;var w=el.closest('.form-group')||el.parentElement;if(w&&!w.dataset.pspHiddenFixed){w.dataset.pspHiddenFixed='1';w.dataset.pspOldDisplay=w.style.display||'';w.style.display='none';}}
function fixedPanel(m,online,presence){
 removeFixedPanel(m);
 var ps=pairs();ps.forEach(function(p){hideField(p.price);hideField(p.sale);});
 var host=document.createElement('div');host.id='psp-fixed-price-panel';host.dir='rtl';host.style.cssText='margin:18px 0;padding:20px;border:1px solid #e5e7eb;border-radius:12px;background:#fafafa';
 host.innerHTML='<div style="font-weight:700;font-size:17px;margin-bottom:16px">قیمت ثابت کالا</div><div style="display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:18px"><div><label style="display:block;font-weight:600;margin-bottom:7px">قیمت ثابت آنلاین</label><input class="form-control" readonly value="'+(online?online.price:'')+'"><small style="display:block;margin-top:5px;color:#64748b">مستقیماً از تعرفه ثابت فعال</small></div><div><label style="display:block;font-weight:600;margin-bottom:7px">قیمت ثابت نمایشگاهی (حضوری)</label><input class="form-control" readonly value="'+(presence?presence.price:'')+'"><small style="display:block;margin-top:5px;color:#64748b">مستقیماً از تعرفه ثابت فعال</small></div></div>';
 var anchor=ps.length?(ps[0].price.closest('.row')||ps[0].price.parentElement):null;if(anchor&&anchor.parentNode)anchor.parentNode.insertBefore(host,anchor);else m.appendChild(host);
 ps.forEach(function(p){var r=ruleFor([].concat(online||[],presence||[]),p);if(r)setPrice(p.price,r.price);});
}
function sqmMode(m){removeFixedPanel(m);runSqm(true);}
function runSqm(force){var m=activeModal();if(!m)return;var g=group(),z=size(),d=design(),ps=pairs(),gid=val(g),sz=text(z),ds=text(d);if(!gid)return;ps.forEach(function(p){lock(p.price);var sig=[gid,sz,ds,p.key,p.name].join('|');if(!force&&p.price.dataset.pspSig===sig)return;p.price.dataset.pspSig=sig;request(CFG.calculateUrl,{group_id:gid,sale_type_key:p.key,sale_type_name:p.name,size:sz,design:ds},function(err,r){if(err||!r||!r.success){setPrice(p.price,'');return;}setPrice(p.price,r.price);});});}
var lastGroup='',timer;
function refresh(force){clearTimeout(timer);timer=setTimeout(function(){var m=activeModal(),g=group();if(!m||!g)return;var gid=val(g);if(!gid){removeFixedPanel(m);return;}if(!force&&gid===lastGroup)return;lastGroup=gid;request(CFG.fixedModeUrl,{group_id:gid,group_name:text(g),size:text(size()),design:text(design()),commodity_code:val(code()),reeds:text(dyn('reeds')),density:text(dyn('density')),weave_type:text(dyn('weave_type'))},function(err,r){if(err||!r||!r.success){sqmMode(m);return;}if(r.mode==='fixed'){
   var online=(r.rules||[]).find(function(x){return isOnline(x.sale_type_name);})||null;
   var presence=(r.rules||[]).find(function(x){return isPresence(x.sale_type_name);})||null;
   fixedPanel(m,online,presence);
 }else sqmMode(m);});},120);}
document.addEventListener('change',function(e){if(e.target&&e.target.matches('select[name="group_id"],#group_id')){lastGroup='';refresh(true);}else if(e.target&&e.target.matches('select[name="size_id"],select[name="design"],select[name="pattern"],#design,#pattern,select[name^="sale_type"]')){lastGroup='';refresh(true);runSqm(true);}},true);
function start(){new MutationObserver(function(){refresh(false);}).observe(document.body,{childList:true,subtree:true});refresh(true);setInterval(function(){refresh(false);},1000);}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
