(function(factory){function start(){if(window.jQuery&&window.jQuery.fn){factory(window.jQuery);return true}return false}if(!start()){var n=0,t=setInterval(function(){if(start()||++n>300)clearInterval(t)},50)}})(function($){'use strict';
if(!/\/admin\/invoices\/invoice(?:\/|$|\?)/i.test(location.pathname+location.search))return;
var C=window.InvoiceFormCustomizerConfig||{},cache={},currentItem='',pendingLine=null,lineSeq=0;
function norm(s){return String(s||'').replace(/\s+/g,' ').trim().toLowerCase()} function q(s,r){return(r||document).querySelector(s)} function qa(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))} function hide(e){if(e)e.classList.add('ifc-hidden')}
function fieldBox(e){return e&&(e.closest('.form-group,.form-group-select,.tw-mb-4,.mbot15,.mbot20,.input-group,.col-md-12,.col-md-6,.col-md-5,.col-md-4,.col-sm-6')||e)}
var REMOVE=['اطلاعات شرکت','قالب خروجی pdf','برچسب ها','برچسب‌ها','صورت حساب برای','صورت‌حساب برای','در حال حمل به','روش های پرداخت مجاز برای این فاکتور','روش‌های پرداخت مجاز برای این فاکتور','فاکتور بازه تکرار داشته باشد','نوع تخفیف','جلوگیری از ارسال هشدارهای سررسید برای این فاکتور','نمایش تعداد به عنوان','یادداشت مشتری','قوانین و مقررات','company information','pdf output','tags','bill to','ship to','allowed payment modes','recurring invoice','discount type','prevent sending overdue reminders','show quantity as','customer note','terms & conditions','terms and conditions'];
function cleanTop(){var item=q('[name="item_select"]'),cut=item?item.getBoundingClientRect().top:1e9;qa('label,.control-label,h4,h5,p').forEach(function(e){if(e.getBoundingClientRect().top>=cut)return;var t=norm(e.textContent);if(REMOVE.some(x=>t.indexOf(norm(x))!==-1))hide(fieldBox(e))});['[name="company_id"]','[name="pdf_template"]','[name="tags"]','[name="allowed_payment_modes[]"]','[name="recurring"]','[name="discount_type"]','[name="cancel_overdue_reminders"]','[name="show_quantity_as"]','[name="clientnote"]','[name="terms"]'].forEach(s=>qa(s).forEach(e=>hide(fieldBox(e))));qa('.billing-shipping-wrapper,.billing_and_shipping,#billing_and_shipping_details,.invoice-billing-shipping,.show_quantity_as_wrapper').forEach(hide)}
function hideInvoiceTasks(){qa('select').forEach(function(s){var n=norm(s.name),txt=norm(Array.from(s.options||[]).map(o=>o.text).join(' '));if(n.indexOf('task')>=0||txt.indexOf('وظایف صورتحساب')>=0||txt.indexOf('وظایف صورت‌حساب')>=0||txt.indexOf('invoice tasks')>=0){var b=s.closest('.input-group,.form-group,.form-group-select,.col-md-6,.col-md-5,.col-md-4,.col-sm-6')||s.parentElement;hide(b)}});qa('button,.dropdown-toggle,.filter-option').forEach(function(e){var t=norm(e.textContent);if(t.indexOf('وظایف صورتحساب')>=0||t.indexOf('وظایف صورت‌حساب')>=0||t.indexOf('invoice tasks')>=0){var b=e.closest('.input-group,.form-group,.form-group-select,.col-md-6,.col-md-5,.col-md-4,.col-sm-6');hide(b)}})}
function hideGlobalDiscount(){['input[name="discount_percent"]','input[name="discount_total"]','select[name="discount_type"]'].forEach(function(s){qa(s).forEach(function(e){var tr=e.closest('tr');if(tr)hide(tr);else hide(fieldBox(e))})});qa('tr').forEach(function(tr){var t=norm(tr.textContent);if((t==='تخفیف'||t.indexOf('تخفیف')===0||t.indexOf('discount')===0)&&q('input,select',tr)&&!tr.closest('table.items'))hide(tr)})}
function itemLayout(){var s=q('select[name="item_select"]');if(!s)return;var box=s.closest('.form-group,.items-select-wrapper,.invoice-items-select-wrapper')||s.parentElement;var t=table();if(t&&box){var host=q('.ifc-item-selector-bar');if(!host){host=document.createElement('div');host.className='ifc-item-selector-bar';t.parentNode.insertBefore(host,t)}if(box.parentNode!==host)host.appendChild(box)}var row=box.closest('.row')||box.parentElement;if(row){row.classList.add('ifc-item-select-row');row.style.width='100%';row.style.maxWidth='100%'}if(box){box.classList.add('ifc-item-select-wide');box.style.width='100%';box.style.maxWidth='100%';box.style.flex='0 0 100%'}var bs=s.closest('.bootstrap-select')||s.parentElement;if(bs){bs.classList.add('ifc-item-select-host')}}
function moveDelivery(){var lab=qa('label,.control-label').find(e=>/روش\s*تحویل\s*کالا/i.test(norm(e.textContent)));if(!lab)return;var box=lab.closest('.form-group,.form-group-select,.col-md-6,.col-md-4,.col-sm-6');var st=q('select[name="invoice_type"]'),stbox=st&&st.closest('.col-md-6,.col-md-4,.col-sm-6');if(!box||!stbox)return;var row=stbox.parentElement;if(!row||box.parentElement===row)return;box.classList.remove('col-md-12','col-md-4','col-sm-6');box.classList.add('col-md-6','ifc-delivery-top');stbox.classList.remove('col-md-12','col-md-4','col-sm-6');stbox.classList.add('col-md-6');row.appendChild(box)}
function hideCreateItem(){var s=q('select[name="item_select"]'),t=table();if(!s||!t)return;var top=s.getBoundingClientRect().top,tt=t.getBoundingClientRect().top;qa('a,button,.input-group-addon,.input-group-btn').forEach(function(b){var r=b.getBoundingClientRect();if(r.top<top-180||r.top>tt+20)return;var txt=norm(b.textContent),plus=b.querySelector&&b.querySelector('.fa-plus,.fa-plus-circle,[class*="plus"]');if(plus||txt==='+')hide(b)})}
function keepSaleTypeEnabled(){var s=q('select[name="invoice_type"]');if(!s)return;var changed=!!s.disabled||s.hasAttribute('disabled');s.disabled=false;s.removeAttribute('disabled');var w=s.closest('.bootstrap-select')||s.parentElement;if(w){w.classList.remove('disabled');var b=q('button.dropdown-toggle',w);if(b){b.disabled=false;b.removeAttribute('disabled');b.classList.remove('disabled');b.style.pointerEvents='auto';b.style.opacity='1'}}/* Do not refresh bootstrap-select while its menu is open: refresh rebuilds the menu and cancels option clicks. */try{if(changed&&!(w&&(w.classList.contains('open')||w.classList.contains('show')))){$(s).prop('disabled',false).selectpicker('refresh')}}catch(e){}}
function hideEditIcons(){qa('a,button').forEach(function(e){var icon=e.querySelector&&e.querySelector('.fa-pencil,.fa-pencil-square-o,.fa-edit,[class*="pencil"],[class*="edit"]');if(icon&&!norm(e.textContent))hide(e)})}
function table(){return q('table.items')}
function headerIndex(words){var t=table();if(!t)return -1;var hs=qa('thead th',t);for(var i=0;i<hs.length;i++){var x=norm(hs[i].textContent);for(var k=0;k<words.length;k++){if(x.indexOf(norm(words[k]))!==-1)return i}}return -1}
function longDescCell(row){if(!row)return null;var e=q('textarea[name="long_description"],textarea[name*="[long_description]"],textarea[placeholder*="طولانی"],textarea[placeholder*="Long"]',row);return e?e.closest('td'):null}
function longDescHeader(){var t=table();if(!t)return null;var row=q('tbody tr.main',t),td=longDescCell(row);if(td&&row){var idx=Array.prototype.indexOf.call(row.children,td);return qa('thead th',t)[idx]||null}var hs=qa('thead th',t);return hs.find(h=>/توضیحات|description/i.test(norm(h.textContent)))||null}
function itemDescriptionCell(row){
 if(!row)return null;
 var e=q('textarea[name="description"],textarea[name*="[description]"],input[name="description"],input[name*="[description]"]',row);
 if(e)return e.closest('td');
 /* Perfex preview row sometimes uses an unindexed textarea for description. Pick the first visible textarea that is NOT long_description. */
 var es=qa('textarea,input[type="text"]',row).filter(function(x){var n=String(x.name||'');return n.indexOf('long_description')===-1 && !x.classList.contains('ifc-color-display') && !x.classList.contains('ifc-size-display')});
 return es.length?es[0].closest('td'):null;
}
function ensureColorSizeColumns(){var t=table();if(!t)return;
 var hr=q('thead tr',t);if(!hr)return;
 /* Remove any stale injected cells first; we rebuild their exact position from the REAL description cell. */
 var old=longDescHeader();if(old){old.classList.add('ifc-original-longdesc');old.style.display='none'}
 var color=q('th[data-ifc-color="1"]',t),size=q('th[data-ifc-size="1"]',t);
 var itemH=qa('thead th',t).find(function(h){return /کالا یا خدمت|item|service/i.test(norm(h.textContent)) && !/discount/i.test(norm(h.textContent))});
 if(!color){color=document.createElement('th');color.textContent='رنگ';color.dataset.ifcColor='1'}
 if(!size){size=document.createElement('th');size.textContent='اندازه';size.dataset.ifcSize='1'}
 /* In RTL Perfex the DOM order still follows the actual table cells. Insert immediately after the item header. */
 if(itemH){if(itemH.nextSibling!==color)hr.insertBefore(color,itemH.nextSibling);if(color.nextSibling!==size)hr.insertBefore(size,color.nextSibling)}
 else {if(!color.parentNode)hr.appendChild(color);if(!size.parentNode)hr.appendChild(size)}
 qa('tbody tr',t).forEach(function(r){
   var oldtd=longDescCell(r);if(oldtd){oldtd.classList.add('ifc-original-longdesc');oldtd.style.display='none'}
   var itemTd=itemDescriptionCell(r);if(!itemTd)return;
   var c=q('td[data-ifc-color-cell="1"]',r),z=q('td[data-ifc-size-cell="1"]',r);
   if(!c){c=document.createElement('td');c.dataset.ifcColorCell='1'}
   if(!z){z=document.createElement('td');z.dataset.ifcSizeCell='1'}
   if(itemTd.nextSibling!==c)r.insertBefore(c,itemTd.nextSibling);
   if(c.nextSibling!==z)r.insertBefore(z,c.nextSibling);
   /* Always create real, visible, independent readonly controls. */
   readonlyDisplay(c,'ifc-color-display',c.dataset.ifcValue||'-');
   readonlyDisplay(z,'ifc-size-display',z.dataset.ifcValue||'-');
 })
}
function customize(){var t=table();if(!t)return;qa('thead th',t).forEach(function(th,i){var x=norm(th.textContent);if(x.indexOf('item_discount_discount_percent')>=0||x==='درصد تخفیف')th.textContent='تخفیفات';if(x==='مالیات'||x.indexOf('tax')>=0||x.indexOf('رگال')>=0||x.indexOf('rack')>=0||x==='مبلغ'||x==='amount'){th.classList.add('ifc-col-hidden');qa('tbody tr',t).forEach(r=>{if(r.children[i])r.children[i].classList.add('ifc-col-hidden')})}});ensureColorSizeColumns()}
function saleType(){var e=q('select[name="invoice_type"]');return e?e.value:''} function invoiceDate(){var e=q('input[name="date"]');return e?String(e.value||''):''} function selectedItemId(){var e=q('select[name="item_select"]');if(!e)return '';return String(e.value||'')} function policyUrl(id){return C.policyUrl+'?itemid='+encodeURIComponent(id)+'&sale_type_id='+encodeURIComponent(saleType())+'&invoice_date='+encodeURIComponent(invoiceDate())+'&_='+Date.now()} function setv(e,v){if(!e)return;e.value=v==null?'':v;e.setAttribute('value',e.value);try{$(e).trigger('input').trigger('change')}catch(x){}}
function loadPolicy(id,cb){if(!id)return;fetch(policyUrl(id),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(function(j){if(j&&j.success){cache[id]=j;cb&&cb(j)}})}
function readonlyDisplay(td,cls,val){if(!td)return;qa('textarea,input',td).forEach(function(e){e.classList.add('ifc-source-hidden');e.tabIndex=-1});var d=q('.'+cls,td);if(!d){d=document.createElement('input');d.type='text';d.className='form-control ifc-readonly-text '+cls;d.readOnly=true;td.appendChild(d)}d.value=val||'-'}
function previewRow(){return q('table.items tbody tr.main')} function applyDetails(row,j){if(!row||!j)return;ensureColorSizeColumns();var c=q('td[data-ifc-color-cell="1"]',row),z=q('td[data-ifc-size-cell="1"]',row);if(c)c.dataset.ifcValue=j.color||'-';if(z)z.dataset.ifcValue=j.size||'-';readonlyDisplay(c,'ifc-color-display',j.color||'-');readonlyDisplay(z,'ifc-size-display',j.size||'-')}
function discountInput(row){var i=headerIndex(['تخفیف','discount']);return i>=0&&row&&row.children[i]?q('input',row.children[i]):q('input[name*="discount"]',row)}
function applyDiscount(el,j,useBase){if(!el||!j)return;var found=!!(j.discount&&j.discount.found),base=found?Number(j.discount.base_percent||0):0,lim=found?Number(j.discount.allowed_percent||0):0;el.min=0;el.max=lim;el.dataset.ifcMaxDiscount=String(lim);el.dataset.ifcBaseDiscount=String(base);var p=el.parentElement,n=q('.ifc-discount-note',p);if(!n){n=document.createElement('small');n.className='ifc-discount-note';p.appendChild(n)}n.textContent=found?'تخفیف این تاریخ: '+base+'٪ — حداکثر مجاز: '+lim+'٪':'تخفیف برای این گروه کالا مجاز نیست (۰٪) — گروه #'+String((j.discount&&j.discount.group_id)||j.group_id||0)+' — تاریخ '+String((j.discount&&j.discount.resolved_date)||invoiceDate()||'-');if(useBase&&found)setv(el,base);else if(parseFloat(el.value||0)>lim)setv(el,lim)}
function fillPreview(j){var r=previewRow();if(!r)return;var opt=q('select[name="item_select"] option:checked');if(opt){if(!j.color)j.color=opt.getAttribute('data-color-name')||opt.getAttribute('data-color')||opt.dataset.color||'';if(!j.size)j.size=opt.getAttribute('data-size-name')||opt.getAttribute('data-size')||opt.dataset.size||'';}ensureColorSizeColumns();var nameIdx=headerIndex(['کالا یا خدمت','item']),name=nameIdx>=0&&r.children[nameIdx]?q('textarea,input',r.children[nameIdx]):null;if(name){setv(name,j.description);name.readOnly=true;name.classList.add('ifc-readonly-text')}applyDetails(r,j);setv(q('input[name="unit"]',r),j.unit);setv(q('input[name="quantity"]',r),1);var rate=q('input[name="rate"]',r);if(rate){setv(rate,j.price);rate.readOnly=true;rate.classList.add('ifc-locked-rate');rate.title=j.price_source==='sale_type'?'قیمت نوع فروش':'قیمت پایه کالا'}var qty=q('input[name="quantity"]',r);if(qty){qty.max=j.stock;qty.dataset.ifcStock=j.stock}applyDiscount(discountInput(r),j,true);currentItem=String(j.itemid)}
function rowItemId(row){var h=q('input.ifc-itemid,input[name*="[itemid]"],input[name*="[item_id]"],input[name="itemid"]',row);return h&&h.value?String(h.value):''}
function finalRows(){var t=table();if(!t)return[];return qa('tbody tr',t).filter(function(r){if(r.classList.contains('main')||r.classList.contains('ifc-line-detail-row'))return false;if(q('button.remove-invoice-item,.remove-invoice-item,.fa-trash,.fa-trash-o,[data-toggle="tooltip"][title*="حذف"]',r))return true;return !!q('input[name*="[qty]"],input[name*="[rate]"],input[name*="[itemid]"]',r)})}
function snapshotPreview(){var r=previewRow(),id=currentItem||selectedItemId(),j=cache[id];if(!r||!j)return null;return{id:String(id),j:j,qty:rowQty(r),discount:rowDiscount(r),created:Date.now()}}
function bindNativeItemId(row,id){if(!row||!id)return;id=String(id);var touched=false;qa('input[name*="[itemid]"],input[name*="[item_id]"],input[name*="[itemItemId]"]',row).forEach(function(h){setv(h,id);touched=true});if(!touched){var qty=q('input[name*="[qty]"]',row),m=qty&&String(qty.name||'').match(/^(.+)\[qty\]$/);if(m){var h=document.createElement('input');h.type='hidden';h.name=m[1]+'[itemid]';h.value=id;h.className='ifc-itemid';row.appendChild(h)}}row.dataset.ifcItemid=id}
function applyPendingToNewest(){if(!pendingLine)return;var rows=finalRows().filter(function(r){return !r.dataset.ifcBound});if(!rows.length)return;var r=rows[rows.length-1],p=pendingLine;r.dataset.ifcBound='1';bindNativeItemId(r,p.id);cache[p.id]=p.j;var qty=q('input[name*="[qty]"]',r);if(qty&&p.qty)setv(qty,p.qty);var di=discountInput(r);if(di)setv(di,p.discount);guardRow(r,p.id);pendingLine=null}
function guardRow(row,id){var j=cache[id];if(!j||!row)return;ensureColorSizeColumns();var nameIdx=headerIndex(['کالا یا خدمت','item']),name=nameIdx>=0&&row.children[nameIdx]?q('textarea,input',row.children[nameIdx]):null;if(name){name.value=j.description||name.value;name.readOnly=true;name.classList.add('ifc-readonly-text')}applyDetails(row,j);var rate=q('input[name*="[rate]"]',row);if(rate){setv(rate,j.price);rate.readOnly=true;rate.classList.add('ifc-locked-rate');rate.title=j.price_source==='sale_type'?'قیمت نوع فروش':'قیمت پایه کالا'}var qty=q('input[name*="[qty]"]',row);if(qty){qty.max=j.stock;qty.dataset.ifcStock=j.stock;var note=q('.ifc-stock-note',qty.parentElement);if(!note){note=document.createElement('small');note.className='ifc-stock-note';qty.parentElement.appendChild(note)}note.textContent='موجودی قابل فروش: '+j.stock}applyDiscount(discountInput(row),j);renderLineSummary(row,j)}
function invokePerfexItemPreview(id){
 if(!id)return;
 /* Perfex core owns population of the preview row. Earlier IFC versions only
    loaded our policy, so the select value changed while description/rate/unit
    never entered the native preview. Call the core loader explicitly. */
 if(typeof window.add_item_to_preview==='function'){
   try{window.add_item_to_preview(id);return}catch(e){}
 }
 /* Fallback for installations where the global is exposed without window binding. */
 try{if(typeof add_item_to_preview==='function')add_item_to_preview(id)}catch(e){}
}

function num(v){v=String(v==null?'':v).replace(/,/g,'').replace(/[^0-9.\-]/g,'');var n=parseFloat(v);return isFinite(n)?n:0}
function money(v){try{var n=Number(v)||0;var frac=Math.abs(n-Math.round(n))>0.000001;return n.toLocaleString('en-US',{minimumFractionDigits:frac?2:0,maximumFractionDigits:2})}catch(e){return String(Number(v)||0)}}
function rowQty(row){var e=q('input[name="quantity"],input[name*="[qty]"]',row);return e?num(e.value):1}
function rowRate(row,j){var e=q('input[name="rate"],input[name*="[rate]"]',row);return e?num(e.value):(j?num(j.price):0)}
function rowDiscount(row){var e=discountInput(row);return e?Math.max(0,num(e.value)):0}
function compactItemName(row,j){var idx=headerIndex(['کالا یا خدمت','item']);var e=idx>=0&&row.children[idx]?q('textarea,input',row.children[idx]):null;return String((e&&e.value)||(j&&j.description)||'').replace(/\s+/g,' ').trim()}
function renderLineSummary(row,j){
 if(!row||!j)return;
 /* Keep the native product row strictly aligned with the table columns.
    Commercial details live in a dedicated full-width row immediately below it. */
 var oldInside=q('.ifc-line-summary',row);if(oldInside)oldInside.remove();
 var key=row.dataset.ifcLineKey;if(!key){key='ifc-'+(++lineSeq)+'-'+Date.now();row.dataset.ifcLineKey=key}
 var detail=row.nextElementSibling;
 if(!detail||!detail.classList.contains('ifc-line-detail-row')||detail.dataset.ifcFor!==key){
   detail=document.createElement('tr');detail.className='ifc-line-detail-row';detail.dataset.ifcFor=key;
   var cell=document.createElement('td');cell.className='ifc-line-detail-cell';detail.appendChild(cell);
   row.parentNode.insertBefore(detail,row.nextSibling);
 }
 row.classList.add('ifc-native-line-hidden');
 var cell=detail.children[0];
 var visibleHeaders=qa('thead th',table()).filter(function(h){return getComputedStyle(h).display!=='none'}).length;
 cell.colSpan=Math.max(1,visibleHeaders);
 var box=q('.ifc-line-summary',cell);if(!box){box=document.createElement('div');box.className='ifc-line-summary';cell.appendChild(box)}
 var qty=rowQty(row),rate=rowRate(row,j),pct=rowDiscount(row),gross=rate*qty,disc=gross*pct/100,net=gross-disc,unitNet=rate*(1-pct/100);
 var name=compactItemName(row,j), color=(j.color||'-'), size=(j.size||'-');
 box.innerHTML='<div class="ifc-line-identity"><strong class="ifc-line-name" title="'+name.replace(/"/g,'&quot;')+'">'+name+'</strong><span>رنگ: <b>'+color+'</b></span><span>اندازه: <b>'+size+'</b></span><span>تعداد: <b>'+qty+'</b></span></div>'+
 '<div class="ifc-line-metrics">'+
 '<span><b>قیمت اصلی</b><em>'+money(rate)+'</em></span>'+
 '<span><b>تخفیف</b><em>'+pct+'٪</em></span>'+
 '<span><b>مبلغ تخفیف</b><em>'+money(disc)+'</em></span>'+
 '<span><b>قیمت پس از تخفیف</b><em>'+money(unitNet)+'</em></span>'+
 '<span class="ifc-line-total"><b>جمع ردیف</b><em>'+money(net)+'</em></span>'+ 
 '<button type="button" class="ifc-summary-remove" title="حذف کالا" aria-label="حذف کالا">×</button></div>';
}
function removeNativeLine(row){if(!row)return;var btn=q('button.remove-invoice-item,.remove-invoice-item,[onclick*=\"remove_item\"],.fa-trash,.fa-trash-o',row);if(btn){var target=btn.closest('button,a')||btn;target.click();return}try{row.remove();if(typeof calculate_total==='function')calculate_total()}catch(e){}}
function cleanupOrphanSummaries(){var t=table();if(!t)return;qa('tbody tr.ifc-line-detail-row',t).forEach(function(d){var key=d.dataset.ifcFor;if(!key||!q('tbody tr[data-ifc-line-key=\"'+key+'\"]',t))d.remove()})}
function labelAddButton(){var r=previewRow();if(!r)return;qa('button,a',r).forEach(function(b){if(!b.querySelector('.fa-check,.fa-check-circle,[class*="check"]'))return;if(q('.ifc-add-label',b))return;var sp=document.createElement('span');sp.className='ifc-add-label';sp.textContent='افزودن به فاکتور';b.appendChild(sp);b.classList.add('ifc-add-item-button')})}
function refreshSummaries(){applyPendingToNewest();finalRows().forEach(function(r){var id=rowItemId(r)||r.dataset.ifcItemid||'';if(id&&cache[id]){r.dataset.ifcBound='1';guardRow(r,id)}})}

function inlineWarn(e,msg,type){if(!e)return;var p=e.parentElement||e.closest('td');if(!p)return;var n=q('.ifc-inline-warning[data-ifc-type="'+type+'"]',p);if(!n){n=document.createElement('small');n.className='ifc-inline-warning';n.dataset.ifcType=type;p.appendChild(n)}n.textContent=msg;n.classList.add('is-visible');clearTimeout(n.__ifcTimer);n.__ifcTimer=setTimeout(function(){n.classList.remove('is-visible')},4500)}
function guardValue(e){if(!e)return;var mx=parseFloat(e.dataset.ifcMaxDiscount);if(isFinite(mx)&&parseFloat(e.value||0)>mx){e.value=mx;inlineWarn(e,'حداکثر تخفیف مجاز '+mx+'٪ است.','discount');try{calculate_total()}catch(x){}}var st=parseFloat(e.dataset.ifcStock);if(isFinite(st)&&parseFloat(e.value||0)>st){e.value=st;inlineWarn(e,'موجودی قابل فروش فقط '+st+' عدد است.','stock');try{calculate_total()}catch(x){}}}
document.addEventListener('click',function(e){var rm=e.target&&e.target.closest?e.target.closest('.ifc-summary-remove'):null;if(rm){e.preventDefault();e.stopPropagation();var d=rm.closest('tr.ifc-line-detail-row'),key=d&&d.dataset.ifcFor,row=key?q('table.items tbody tr[data-ifc-line-key=\"'+key+'\"]'):null;if(d)d.remove();removeNativeLine(row);setTimeout(function(){cleanupOrphanSummaries();try{if(typeof calculate_total==='function')calculate_total()}catch(x){}},80);return}var b=e.target&&e.target.closest?e.target.closest('.ifc-add-item-button'):null;if(b){pendingLine=snapshotPreview();setTimeout(function(){applyPendingToNewest();refreshSummaries()},120);setTimeout(function(){applyPendingToNewest();refreshSummaries()},500)}},true);
document.addEventListener('change',function(e){if(e.target&&e.target.matches('select[name="item_select"]')){currentItem=selectedItemId();if(currentItem){invokePerfexItemPreview(currentItem);loadPolicy(currentItem,j=>{setTimeout(()=>fillPreview(j),120);setTimeout(()=>fillPreview(j),500)})}}if(e.target&&e.target.matches('select[name="invoice_type"]')){if(currentItem)loadPolicy(currentItem,fillPreview);finalRows().forEach(r=>{var id=rowItemId(r)||r.dataset.ifcItemid;if(id)loadPolicy(id,j=>guardRow(r,id))})}if(e.target&&e.target.matches('input[name="date"]')){if(currentItem)loadPolicy(currentItem,function(j){fillPreview(j)});finalRows().forEach(function(r){var id=rowItemId(r)||r.dataset.ifcItemid;if(id)loadPolicy(id,function(j){guardRow(r,id)})})}guardValue(e.target)},true);document.addEventListener('input',function(e){guardValue(e.target);var r=e.target&&e.target.closest?e.target.closest('table.items tbody tr'):null;if(r&&r.classList.contains('main'))r=null;if(r){var id=rowItemId(r);if(id&&cache[id])setTimeout(function(){renderLineSummary(r,cache[id])},20)}},true);
$(document).on('changed.bs.select','select[name="invoice_type"]',function(e,clickedIndex,isSelected,previousValue){var s=this;if(clickedIndex!=null&&s.options&&s.options[clickedIndex]){s.value=s.options[clickedIndex].value;}try{$(s).selectpicker('render')}catch(x){}if(currentItem)loadPolicy(currentItem,fillPreview);finalRows().forEach(function(r){var id=rowItemId(r)||r.dataset.ifcItemid;if(id)loadPolicy(id,function(j){guardRow(r,id)})});});
$(document).on('changed.bs.select','select[name="item_select"]',function(){currentItem=selectedItemId();if(currentItem){invokePerfexItemPreview(currentItem);loadPolicy(currentItem,function(j){setTimeout(function(){fillPreview(j)},150)})}});
$(document).on('item-added-to-preview',function(){var id=currentItem||selectedItemId();if(id)loadPolicy(id,fillPreview)});$(document).on('item-added-to-table',function(){setTimeout(function(){protect();refreshSummaries()},60)});
function protect(){applyPendingToNewest();finalRows().forEach(function(r){var id=rowItemId(r)||r.dataset.ifcItemid||'';if(!id)return;r.dataset.ifcBound='1';bindNativeItemId(r,id);if(cache[id])guardRow(r,id);else loadPolicy(id,j=>guardRow(r,id))})}
document.addEventListener('submit',function(e){var f=e.target;if(!f||!f.matches('form'))return;applyPendingToNewest();finalRows().forEach(function(r){var id=r.dataset.ifcItemid||rowItemId(r);if(id)bindNativeItemId(r,id)})},true);
function compactTopFields(){
 var item=q('select[name="item_select"]'), anchor=item?(item.closest('.ifc-item-selector-bar')||table()):table();if(!anchor)return;
 var host=q('.ifc-top-grid');if(!host){host=document.createElement('div');host.className='ifc-top-grid';anchor.parentNode.insertBefore(host,anchor)}
 var specs=[
  ['select[name="invoice_type"]','ifc-top-normal'],
  ['select[name*="marketer"],select[name*="referrer"]','ifc-top-normal'],
  ['select[name*="delivery"],select[name*="shipping_method"]','ifc-top-normal'],
  ['select[name="clientid"],select[name="client_id"],select[name="customer"]','ifc-top-wide'],
  ['select[name="currency"]','ifc-top-normal'],
  ['select[name="sale_agent"]','ifc-top-normal'],
  ['textarea[name*="manager"],textarea[name*="adminnote"]','ifc-top-wide'],
  ['input[name="number"]','ifc-top-normal'],
  ['input[name="date"]','ifc-top-normal'],
  ['input[name="duedate"]','ifc-top-normal']
 ];
 var seen=[];specs.forEach(function(sp){var e=q(sp[0]);if(!e)return;var box=e.closest('.form-group,.form-group-select,.col-md-12,.col-md-8,.col-md-6,.col-md-5,.col-md-4,.col-md-3,.col-sm-6')||e.parentElement;if(!box||seen.indexOf(box)>=0||box.closest('.ifc-top-grid'))return;seen.push(box);box.classList.add('ifc-top-card',sp[1]);host.appendChild(box)});
 /* Capture Persian-labelled custom fields that do not have predictable names. */
 ['بازاریابی','روش تحویل کالا','مامور فروش','مأمور فروش','یادداشت مدیر'].forEach(function(txt){var lab=qa('label,.control-label').find(function(x){return norm(x.textContent).indexOf(norm(txt))>=0});if(!lab)return;var box=lab.closest('.form-group,.form-group-select,.col-md-12,.col-md-8,.col-md-6,.col-md-5,.col-md-4,.col-md-3,.col-sm-6')||lab.parentElement;if(box&&seen.indexOf(box)<0&&!box.closest('.ifc-top-grid')){seen.push(box);box.classList.add('ifc-top-card',txt.indexOf('یادداشت')>=0?'ifc-top-wide':'ifc-top-normal');host.appendChild(box)}})
}

function collapseVacatedHeaderRows(){
 var host=q('.ifc-top-grid'); if(!host)return;
 /* Remove the original empty shells left behind after fields are moved to the compact grid.
    Ignore decorative edit/create controls; those must not keep a large Bootstrap row alive. */
 var cut=host.getBoundingClientRect().top;
 qa('.row').forEach(function(r){
  if(r.closest('.ifc-top-grid')||r.closest('.ifc-item-selector-bar')||r.closest('table.items'))return;
  var b=r.getBoundingClientRect(); if(b.top>=cut||b.height<35)return;
  var live=qa('input,select,textarea',r).some(function(e){
   if(e.closest('.ifc-hidden')||e.classList.contains('ifc-hidden')||e.closest('.ifc-top-grid'))return false;
   var cs=getComputedStyle(e),x=e.getBoundingClientRect();
   return cs.display!=='none'&&cs.visibility!=='hidden'&&x.width>2&&x.height>2;
  });
  var txt=Array.from(r.childNodes).filter(function(n){return n.nodeType===3}).map(function(n){return norm(n.textContent)}).join('');
  if(!live&&!txt)r.classList.add('ifc-vacated-header-row');
 });
 /* Some Perfex builds wrap those rows in billing/address panels with fixed min-height.
    Collapse only wrappers that contain no remaining usable form control. */
 qa('.billing-shipping-wrapper,.billing_and_shipping,#billing_and_shipping_details,.invoice-billing-shipping,.panel-body>.row,.panel-body>div').forEach(function(w){
   if(w.closest('.ifc-top-grid')||w.closest('.ifc-item-selector-bar')||w.closest('table.items'))return;
   var wb=w.getBoundingClientRect(); if(wb.bottom>host.getBoundingClientRect().top+5)return;
   var usable=qa('input,select,textarea',w).some(function(e){
     if(e.closest('.ifc-hidden')||e.classList.contains('ifc-hidden'))return false;
     var cs=getComputedStyle(e),x=e.getBoundingClientRect();return cs.display!=='none'&&cs.visibility!=='hidden'&&x.width>2&&x.height>2;
   });
   if(!usable){w.classList.add('ifc-vacated-header-shell');qa('a,button',w).forEach(hide)}
 });
}
function colorNativeTotalsLabels(){
 qa('td,th,label,span,strong,div').forEach(function(e){
  if(e.closest('table.items')||e.closest('.ifc-line-summary'))return;
  var t=norm(e.textContent).replace(/\s+/g,' ').trim();
  if(t==='جمع جزئی :'||t==='جمع جزئی:'||t==='جمع جزئی'||t==='تعدیل'||t==='جمع کل :'||t==='جمع کل:'||t==='جمع کل'||t==='Subtotal :'||t==='Subtotal'||t==='Adjustment'||t==='Total :'||t==='Total') e.classList.add('ifc-native-total-label');
 });
}
function apply(){
 /* 0.1.44: edit pages can contain a different/pre-populated DOM. One failing line-item
    enhancement must never prevent the top-form customizer from running. */
 var stages=[
  ['cleanTop',cleanTop],['hideInvoiceTasks',hideInvoiceTasks],['hideGlobalDiscount',hideGlobalDiscount],
  ['moveDelivery',moveDelivery],['compactTopFields',compactTopFields],['itemLayout',itemLayout],
  ['hideCreateItem',hideCreateItem],['hideEditIcons',hideEditIcons],['collapseVacatedHeaderRows',collapseVacatedHeaderRows],
  ['keepSaleTypeEnabled',keepSaleTypeEnabled],['customize',customize],['protect',protect],
  ['labelAddButton',labelAddButton],['refreshSummaries',refreshSummaries],['colorNativeTotalsLabels',colorNativeTotalsLabels]
 ];
 try{cleanupOrphanSummaries()}catch(e){if(window.console)console.warn('IFC cleanup',e)}
 stages.forEach(function(st){try{st[1]()}catch(e){if(window.console)console.warn('IFC '+st[0],e)}});
 document.body.classList.add('ifc-active');
 document.body.dataset.ifcVersion='0.1.52';
}
/* 0.1.44 - Create and Edit are first-class targets. Perfex edit pages populate controls and
   existing invoice rows asynchronously, so re-apply in bounded passes instead of assuming the
   create-page timing. Each pass is idempotent and guarded stage-by-stage above. */
function ifcCleanModalResidue(){
 try{$('.modal-backdrop').remove();$('body').removeClass('modal-open').css({paddingRight:'',overflow:''});}catch(e){}
}
function ifcIsEdit(){return /\/admin\/invoices\/invoice\/\d+(?:[\/?#]|$)/i.test(location.pathname+location.search)}
$(function(){
 ifcCleanModalResidue();
 var passes=ifcIsEdit()?[80,250,600,1200,2200,3800,6000]:[120,650,1400,2400];
 passes.forEach(function(ms){setTimeout(apply,ms)});
});
/* Observe only during Perfex's asynchronous construction window. */
var mo=new MutationObserver(function(){
 clearTimeout(window.__ifc141);
 window.__ifc141=setTimeout(apply,100);
});
mo.observe(document.documentElement,{childList:true,subtree:true});
setTimeout(function(){try{mo.disconnect()}catch(e){};clearTimeout(window.__ifc141);apply()},8000);
window.InvoiceFormCustomizer={version:'0.1.55',apply:apply,status:function(){return{version:'0.1.45',active:document.body.classList.contains('ifc-active'),currentItem:currentItem,cache:Object.keys(cache)}}};
});

/* 0.1.52 edit-safe-shell hydrator: load persisted invoice values into the known-good create shell. */
(function(){
 function editId(){try{return new URLSearchParams(location.search).get('ifc_edit')||''}catch(e){return''}}
 var id=editId(); if(!id||!/^[0-9]+$/.test(id))return;
 function ready(fn){if(window.jQuery&&jQuery.ajax)return fn();setTimeout(function(){ready(fn)},60)}
 function control(name){return document.querySelector('[name="'+name+'"]')}
 function change(e){if(!e)return;try{jQuery(e).trigger('change')}catch(x){try{e.dispatchEvent(new Event('change',{bubbles:true}))}catch(y){}}}
 function setInput(name,val){var e=control(name);if(!e)return false;e.value=val==null?'':val;e.setAttribute('value',e.value);change(e);return true}
 function ensureOption(e,val,label){if(!e||val===null||val===undefined||String(val)==='')return;var v=String(val),o=Array.from(e.options||[]).find(function(x){return String(x.value)===v});if(!o){o=new Option(label||v,v,true,true);e.add(o)}else{o.selected=true}}
 function setSelect(name,val,label){var e=document.querySelector('select[name="'+name+'"]');if(!e)return false;ensureOption(e,val,label);e.value=String(val==null?'':val);try{jQuery(e).selectpicker('refresh').selectpicker('val',String(val==null?'':val)).trigger('change')}catch(x){change(e)}return true}
 function setAny(names,val){for(var i=0;i<names.length;i++)if(setInput(names[i],val))return true;return false}
 function setAnySelect(names,val,label){for(var i=0;i<names.length;i++)if(setSelect(names[i],val,label))return true;return false}
 function markEdit(d){
   document.body.classList.add('ifc-edit-hydrated');document.body.dataset.ifcEditId=d.id;
   document.title=document.title.replace(/ایجاد فاکتور|Create Invoice/i,'ویرایش فاکتور');
   var hs=document.querySelectorAll('h1,h2,h3,h4,.page-title');hs.forEach(function(h){if(/ایجاد فاکتور|Create Invoice/i.test(h.textContent))h.textContent='ویرایش فاکتور'});
   /* IMPORTANT: target the real Perfex invoice form, never the first generic form on the page. */
   var f=document.querySelector('#invoice-form')||document.querySelector('form.invoice-form')||document.querySelector('form._transaction_form');
   if(f){
     var updateUrl=(window.admin_url||'/admin/')+'invoices/invoice/'+d.id;
     f.action=updateUrl;f.setAttribute('action',updateUrl);f.dataset.ifcEditId=d.id;
     var old=f.querySelector('input[name="isedit"]');if(!old){old=document.createElement('input');old.type='hidden';old.name='isedit';f.appendChild(old)}old.value=d.id;
     /* Perfex duplicate-number validator recognises an existing invoice by these attributes. */
     var num=f.querySelector('input[name="number"]');if(num){num.setAttribute('data-isedit','true');num.setAttribute('data-original-number',String(d.number==null?'':d.number));}
   }
 }
 function addLine(x,done){
   var s=document.querySelector('select[name="item_select"]');if(!s){setTimeout(function(){addLine(x,done)},120);return}
   if(!x.itemid){if(window.console)console.warn('IFC edit: product match missing for invoice line',x);done();return}
   ensureOption(s,x.itemid,x.description||('Item '+x.itemid));
   try{jQuery(s).selectpicker('refresh').selectpicker('val',String(x.itemid)).trigger('changed.bs.select').trigger('change')}catch(e){s.value=String(x.itemid);change(s)}
   setTimeout(function(){
     var r=document.querySelector('table.items tbody tr.main')||document.querySelector('tr.main');
     if(r){var q=r.querySelector('input[name="quantity"]');if(q)q.value=x.qty||1;var rate=r.querySelector('input[name="rate"]');if(rate){rate.readOnly=false;rate.disabled=false;rate.value=x.rate||0}var unit=r.querySelector('input[name="unit"]');if(unit)unit.value=x.unit||'';var desc=r.querySelector('textarea[name="description"],input[name="description"]');if(desc)desc.value=x.description||'';var ld=r.querySelector('textarea[name="long_description"],input[name="long_description"]');if(ld)ld.value=x.long_description||''}
     var b=document.querySelector('table.items tbody tr.main .ifc-add-item-button,table.items tbody tr.main button[onclick*="add_item_to_table"],tr.main button[onclick*="add_item_to_table"]');
     if(b)b.click();
     setTimeout(done,350);
   },550);
 }
 function hydrate(d){
   markEdit(d);
   setSelect('clientid',d.clientid,d.client_name||String(d.clientid));
   setSelect('invoice_type',d.invoice_type); setSelect('sale_agent',d.sale_agent); setSelect('invoice_marketer',d.invoice_marketer);
   setSelect('currency',d.currency); setSelect('discount_type',d.discount_type);
   setAny(['number'],d.number); setAny(['date'],d.date); setAny(['duedate'],d.duedate); setAny(['adminnote'],d.adminnote); setAny(['clientnote'],d.clientnote);
   if(d.custom_fields)Object.keys(d.custom_fields).forEach(function(k){var e=document.querySelector('[name="custom_fields[invoice]['+k+']"],[name="invoice['+k+']"]');if(e){if(e.tagName==='SELECT')setSelect(e.name,d.custom_fields[k]);else{e.value=d.custom_fields[k];change(e)}}});
   /* Remove any accidental preview/final lines from create-shell initialization, then replay persisted lines. */
   var finals=document.querySelectorAll('table.items tbody tr:not(.main):not(.ifc-line-detail-row)');finals.forEach(function(r){try{r.remove()}catch(e){}});
   var list=d.items||[],i=0;function next(){if(i>=list.length){try{if(typeof calculate_total==='function')calculate_total()}catch(e){};if(window.InvoiceFormCustomizer&&InvoiceFormCustomizer.apply)InvoiceFormCustomizer.apply();return}addLine(list[i++],next)}
   setTimeout(next,700);
 }
 /* 0.1.55: the create-shell URL must never turn an edit POST into a new invoice.
    Reassert the native update endpoint at submit time, after every other script/validator. */
 document.addEventListener('submit',function(e){
   var f=e.target;if(!f||!(f.matches('#invoice-form')||f.matches('form.invoice-form')||f.matches('form._transaction_form')))return;
   var edit=editId();if(!edit)return;
   var updateUrl=(window.admin_url||'/admin/')+'invoices/invoice/'+edit;
   f.action=updateUrl;f.setAttribute('action',updateUrl);
   var n=f.querySelector('input[name="number"]');if(n){n.setAttribute('data-isedit','true');if(!n.getAttribute('data-original-number'))n.setAttribute('data-original-number',n.value);}
 },true);
 ready(function(){jQuery.getJSON((window.admin_url||'/admin/')+'invoice_form_customizer/edit_payload/'+id+'?_='+(Date.now())).done(function(d){if(d&&d.success)hydrate(d);else if(window.console)console.error('IFC edit payload failed',d)}).fail(function(x){if(window.console)console.error('IFC edit payload HTTP error',x.status,x.responseText)})});
})();
