(function($){'use strict';
/* 0.1.62 - Invoice list column alignment/performance fix.
   IMPORTANT: never hide TD cells by raw DOM index. Perfex/DataTables can keep internal/hidden
   columns that are not represented symmetrically in the rendered row; direct TD hiding caused
   customer/status to slide under date/customer and left due-date/status blank.
   Column visibility is now delegated to the DataTables API, which keeps header/body mapping intact. */
function norm(s){return String(s||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ').trim().toLowerCase()}
var REMOVE=['مجموع مالیات','مالیات','پروژه','برچسب ها','برچسب‌ها','روش تحویل کالا','روش تحویل','total tax','tax total','project','tags','delivery method'];
function shouldHide(t){t=norm(t);return REMOVE.some(function(x){x=norm(x);return t===x || t.indexOf(x)!==-1})}
function clean(){
 var table=document.querySelector('table.table-invoices');
 if(!table || !window.jQuery || !$.fn || !$.fn.dataTable)return;
 var $t=$(table);
 if(!$.fn.dataTable.isDataTable(table))return;
 var api=$t.DataTable();
 api.columns().every(function(){
   var h=this.header(); if(!h)return;
   if(shouldHide(h.textContent||'')){
     if(this.visible())this.visible(false,false);
   }
 });
 api.columns.adjust();
}
$(function(){setTimeout(clean,0);setTimeout(clean,350)});
$(document).on('draw.dt.ifcColumns xhr.dt.ifcColumns',function(e,settings){
 var n=settings&&settings.nTable;if(n&&n.classList&&n.classList.contains('table-invoices'))setTimeout(clean,0);
});
})(jQuery);

/* 0.1.35 - Cashier-focused invoice popup. Keeps Perfex actions/URLs intact, only changes presentation. */
(function($){'use strict';
 var oldInit=window.init_invoice;
 function n(s){return String(s||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ').trim().toLowerCase()}
 function ensureModal(){
   var m=document.getElementById('ifc-cashier-invoice-modal'); if(m)return m;
   $('body').append('<div class="modal fade ifc-cashier-modal" id="ifc-cashier-invoice-modal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-xl" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">مشاهده فاکتور</h4></div><div class="modal-body"><div class="ifc-cashier-loading"><i class="fa fa-spinner fa-spin"></i> در حال بارگذاری...</div><div class="ifc-cashier-modal-content"></div></div></div></div></div>');
   return document.getElementById('ifc-cashier-invoice-modal');
 }
 function textHas(el,words){var t=n(el.textContent);return words.some(function(w){return t.indexOf(n(w))!==-1})}
 function prune(root){
   if(!root)return;
   root.classList.add('ifc-cashier-view');
   /* Remove the work-management tabs that are irrelevant to the cashier. */
   var unwantedTabs=['وظایف','گزارش فعالیت','فعالیت','یادآورها','یادآور','یادداشت ها','یادداشت‌ها','notes','reminders','activity','tasks'];
   root.querySelectorAll('.nav-tabs li,.nav-tabs a').forEach(function(el){if(textHas(el,unwantedTabs)){var li=el.closest('li')||el;li.classList.add('ifc-cashier-hide')}});
   /* Hide edit/email/e-invoice controls; cashier needs payment + receipt/document actions only. */
   root.querySelectorAll('a,button').forEach(function(el){
     var t=n(el.textContent), title=n(el.getAttribute('title'));
     if(t==='e-invoice'||t.indexOf('e-invoice')>=0||title.indexOf('e-invoice')>=0||t==='ویرایش'||title==='ویرایش'||t.indexOf('ارسال ایمیل')>=0){el.classList.add('ifc-cashier-hide')}
   });
   /* PDF menu: cashier only needs download and print. */
   root.querySelectorAll('.dropdown-menu').forEach(function(menu){
     var mt=n(menu.textContent);
     if(mt.indexOf('pdf')>=0 && (mt.indexOf('دانلود')>=0||mt.indexOf('چاپ')>=0)){
       menu.querySelectorAll('li').forEach(function(li){var t=n(li.textContent);if(t && t.indexOf('دانلود')<0 && t.indexOf('چاپ')<0)li.classList.add('ifc-cashier-hide')});
     }
   });
   /* More menu: retain delivery receipt and file attachment, hide unrelated workflow actions. */
   root.querySelectorAll('.dropdown-menu').forEach(function(menu){
     var mt=n(menu.textContent);
     if(mt.indexOf('رسید تحویل کالا')>=0||mt.indexOf('پیوست فایل')>=0){
       menu.querySelectorAll('li').forEach(function(li){var t=n(li.textContent);if(t && t.indexOf('رسید تحویل کالا')<0 && t.indexOf('پیوست فایل')<0)li.classList.add('ifc-cashier-hide')});
     }
   });
   /* Hide notes/task/activity panes if Perfex left them in DOM despite tab removal. */
   root.querySelectorAll('.tab-pane').forEach(function(p){var id=n(p.id), cls=n(p.className);if(/task|activit|remind|note/.test(id+' '+cls))p.classList.add('ifc-cashier-hide')});
 }
 function openCashierInvoice(id){
   /* Perfex may call init_invoice() on page load with no id. Never open the cashier modal unless a real invoice was explicitly requested. */
   id=String(id==null?'':id).trim();
   if(!/^\d+$/.test(id))return false;
   var modal=ensureModal(),$m=$(modal),$body=$m.find('.ifc-cashier-modal-content'),$load=$m.find('.ifc-cashier-loading');
   $body.empty();$load.show();$m.modal('show');
   $body.load(admin_url+'invoices/get_invoice_data_ajax/'+encodeURIComponent(id),function(response,status){
     $load.hide();
     if(status==='error'){$body.html('<div class="alert alert-danger">نمایش فاکتور با خطا مواجه شد.</div>');return;}
     prune($body[0]);
     try{init_selectpicker();init_tooltips();}catch(e){}
   });
 }
 /* Override Perfex small-side preview only on invoice list. */
 window.init_invoice=function(id){return openCashierInvoice(id)};
 /* Invoice number is display-only for the cashier. The explicit "View" action remains the only preview trigger. */
 function disableInvoiceNumberLinks(){
   document.querySelectorAll('table.table-invoices tbody tr').forEach(function(tr){
     var td=tr.querySelector('td'); if(!td)return;
     var a=td.querySelector('a'); if(!a)return;
     var oc=a.getAttribute('onclick')||'';
     if(!/init_invoice\s*\(/i.test(oc))return;
     var sp=document.createElement('span');
     sp.className='ifc-invoice-number-text';
     sp.textContent=a.textContent;
     a.parentNode.replaceChild(sp,a);
   });
 }
 $(function(){disableInvoiceNumberLinks();setTimeout(disableInvoiceNumberLinks,250);setTimeout(disableInvoiceNumberLinks,900)});
 $(document).on('draw.dt xhr.dt',function(){setTimeout(disableInvoiceNumberLinks,30)});
 /* If another script restores the original function after us, capture invoice links as a fallback. */
 $(document).on('click.ifcCashier','table.table-invoices tbody a',function(e){
   var oc=this.getAttribute('onclick')||'',m=oc.match(/init_invoice\s*\(\s*['\"]?(\d+)/i);
   if(m){e.preventDefault();e.stopImmediatePropagation();openCashierInvoice(m[1]);return false;}
 });
})(jQuery);

/* 0.1.37 - Explicit View action must open cashier modal, never navigate to public invoice page. */
(function($){'use strict';
 function norm37(s){return String(s||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ').trim().toLowerCase()}
 function id37(a){
   var oc=a.getAttribute('onclick')||'', href=a.getAttribute('href')||'', m;
   m=oc.match(/init_invoice\s*\(\s*['\"]?(\d+)/i); if(m)return m[1];
   m=href.match(/\/admin\/invoices\/(?:list_invoices\/)?(\d+)(?:[\/?#]|$)/i); if(m)return m[1];
   m=href.match(/[?&](?:invoice_id|id)=(\d+)(?:&|$)/i); if(m)return m[1];
   return '';
 }
 function view37(a){var t=norm37(a.textContent),x=norm37(a.getAttribute('title')),y=norm37(a.getAttribute('aria-label'));return t==='مشاهده'||x==='مشاهده'||y==='مشاهده'||t==='view'||x==='view'||y==='view'}
 document.addEventListener('click',function(e){
   var a=e.target && e.target.closest ? e.target.closest('table.table-invoices tbody a') : null;
   if(!a || !view37(a))return;
   var id=id37(a); if(!/^\d+$/.test(id))return;
   e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
   if(typeof window.init_invoice==='function')window.init_invoice(id);
 },true);
})(jQuery);


/* 0.1.38 - Perfex row "View" uses the public invoice URL (/invoice/{id}/{hash}).
   Capture that explicit action before browser navigation and route it to the cashier popup. */
(function($){'use strict';
 function cleanText(s){return String(s||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ').trim().toLowerCase()}
 function publicInvoiceId(a){
   var href=a.getAttribute('href')||'', m=href.match(/\/invoice\/(\d+)\/[a-z0-9]+(?:[\/?#]|$)/i);
   return m?m[1]:'';
 }
 function isViewAction(a){
   var vals=[a.textContent,a.getAttribute('title'),a.getAttribute('data-original-title'),a.getAttribute('aria-label')].map(cleanText).join(' ');
   if(/(^| )(مشاهده|view)( |$)/i.test(vals))return true;
   /* In Perfex the row action can be an icon-only public invoice link. Since invoice-number links are
      already converted to plain text, a remaining public /invoice/{id}/{hash} link in the invoice row is View. */
   return !!publicInvoiceId(a) && !!a.closest('table.table-invoices tbody tr');
 }
 document.addEventListener('click',function(e){
   var a=e.target&&e.target.closest?e.target.closest('a'):null;
   if(!a||!a.closest('table.table-invoices tbody tr')||!isViewAction(a))return;
   var id=publicInvoiceId(a);
   if(!id){var oc=a.getAttribute('onclick')||'',m=oc.match(/init_invoice\s*\(\s*['\"]?(\d+)/i);id=m?m[1]:'';}
   if(!/^\d+$/.test(id))return;
   e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();
   if(typeof window.init_invoice==='function')window.init_invoice(id);
   return false;
 },true);
})(jQuery);


/* 0.1.39 - Edit invoice must leave cashier preview cleanly and perform a normal hard navigation.
   This prevents the preview modal/backdrop and delegated preview handlers from leaking into edit flow. */
(function($){'use strict';
 function txt39(s){return String(s||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ').trim().toLowerCase()}
 function invoiceId39(a){
   var href=a.getAttribute('href')||'',m;
   m=href.match(/\/admin\/invoices\/invoice\/(\d+)(?:[\/?#]|$)/i); if(m)return m[1];
   var modal=a.closest('#ifc-cashier-invoice-modal');
   if(modal){
     var edit=modal.querySelector('a[href*="/admin/invoices/invoice/"]');
     if(edit){m=(edit.getAttribute('href')||'').match(/\/admin\/invoices\/invoice\/(\d+)(?:[\/?#]|$)/i);if(m)return m[1];}
   }
   return '';
 }
 function isEdit39(a){
   var v=[a.textContent,a.getAttribute('title'),a.getAttribute('data-original-title'),a.getAttribute('aria-label')].map(txt39).join(' ');
   return /(^| )(ویرایش|edit)( |$)/i.test(v) || /\/admin\/invoices\/invoice\/\d+/i.test(a.getAttribute('href')||'');
 }
 function cleanup39(){
   try{$('#ifc-cashier-invoice-modal').modal('hide');}catch(e){}
   $('.modal-backdrop').remove();
   $('body').removeClass('modal-open').css({paddingRight:'',overflow:''});
 }
 document.addEventListener('click',function(e){
   var a=e.target&&e.target.closest?e.target.closest('a'):null;if(!a||!isEdit39(a))return;
   /* Only take over invoice edit actions from invoice list/cashier preview, never links inside the actual edit form. */
   if(!a.closest('#ifc-cashier-invoice-modal')&&!a.closest('table.table-invoices tbody tr'))return;
   var id=invoiceId39(a);if(!/^\d+$/.test(id))return;
   e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();cleanup39();
   var base=(typeof admin_url==='string'?admin_url:'/admin/');
   window.location.assign(base+'invoices/invoice/'+encodeURIComponent(id));
   return false;
 },true);
 $('#ifc-cashier-invoice-modal').on('hidden.bs.modal',cleanup39);
})(jQuery);


/* 0.1.53 - Cashier payment bridge.
   Perfex' native record_payment(id) renders into #invoice. The cashier preview lives in
   IFC's modal and intentionally has no #invoice target, so the native click appeared to do nothing.
   Keep Perfex behavior everywhere else; only while the IFC cashier modal is open, render the
   native payment form inside the same modal. */
(function($){'use strict';
 var nativeRecordPayment=window.record_payment;
 function modalOpen(){var m=document.getElementById('ifc-cashier-invoice-modal');return !!(m && ($(m).hasClass('in') || $(m).is(':visible')));}
 function paymentHost(){
   var m=document.getElementById('ifc-cashier-invoice-modal'); if(!m)return null;
   var host=m.querySelector('.ifc-cashier-modal-content'); return host?$(host):null;
 }
 function loadPayment(id){
   id=String(id==null?'':id).trim(); if(!/^\d+$/.test(id))return false;
   var $host=paymentHost(); if(!$host)return false;
   var $load=$('#ifc-cashier-invoice-modal .ifc-cashier-loading');
   $host.empty();$load.show();
   $host.load(admin_url+'invoices/record_invoice_payment_ajax/'+encodeURIComponent(id),function(response,status){
     $load.hide();
     if(status==='error'){$host.html('<div class="alert alert-danger">فرم پرداخت بارگذاری نشد.</div>');return;}
     try{if(typeof init_selectpicker==='function')init_selectpicker();if(typeof init_datepicker==='function')init_datepicker();if(typeof init_tooltips==='function')init_tooltips();}catch(e){}
     try{var f=$host.find('form').first();if(f.length&&typeof appValidateForm==='function'){/* Native response normally initializes its own validation. */}}catch(e){}
   });
   return false;
 }
 window.record_payment=function(id){
   if(modalOpen())return loadPayment(id);
   if(typeof nativeRecordPayment==='function')return nativeRecordPayment.apply(this,arguments);
   if(id!==undefined&&id!=='')$('#invoice').load(admin_url+'invoices/record_invoice_payment_ajax/'+id);
 };
 /* Capture the cashier payment button before any stale/native delegated handler can target #invoice. */
 document.addEventListener('click',function(e){
   if(!modalOpen())return;
   var el=e.target&&e.target.closest?e.target.closest('a,button'):null;if(!el||!el.closest('#ifc-cashier-invoice-modal'))return;
   var oc=el.getAttribute('onclick')||'',m=oc.match(/record_payment\s*\(\s*['"]?(\d+)/i);
   if(!m)return;
   e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();loadPayment(m[1]);return false;
 },true);
})(jQuery);


/* 0.1.54 - AJAX payment submit / no broken record_payment page.
   The native payment form posts to /admin/invoices/record_payment. On this installation the
   payment is persisted, but rendering the normal response ends with HTTP 500. Inside the IFC
   cashier modal we never need that full-page response. Submit once via AJAX, then reopen the
   invoice preview so the cashier stays in context and the refreshed paid/balance values are shown. */
(function($){'use strict';
 var busy=false;
 function modal(){return $('#ifc-cashier-invoice-modal');}
 function isPaymentForm(form){
   if(!form||!form.action)return false;
   return /\/admin\/invoices\/record_payment\/?(?:\?|$)/i.test(form.action);
 }
 function invoiceIdFromContext($form){
   var id=String($form.data('ifc-invoice-id')||modal().attr('data-ifc-payment-invoice-id')||'').trim();
   if(/^\d+$/.test(id))return id;
   var v=$form.find('input[name="invoiceid"],input[name="invoice_id"]').first().val();
   return /^\d+$/.test(String(v||''))?String(v):'';
 }
 function reopen(id){
   busy=false;
   if(typeof window.init_invoice==='function'&&id){window.init_invoice(id);return;}
   if(id)window.location.href=admin_url+'invoices#'+id;
 }
 /* Remember which invoice owns the payment form. */
 document.addEventListener('click',function(e){
   var el=e.target&&e.target.closest?e.target.closest('a,button'):null;
   if(!el||!el.closest('#ifc-cashier-invoice-modal'))return;
   var m=(el.getAttribute('onclick')||'').match(/record_payment\s*\(\s*['"]?(\d+)/i);
   if(m)modal().attr('data-ifc-payment-invoice-id',m[1]);
 },true);
 /* Capture submit before the browser can navigate to /admin/invoices/record_payment. */
 document.addEventListener('submit',function(e){
   var form=e.target;
   if(!form||!form.closest||!form.closest('#ifc-cashier-invoice-modal')||!isPaymentForm(form))return;
   e.preventDefault();e.stopPropagation();e.stopImmediatePropagation();
   if(busy)return false;busy=true;
   var $f=$(form),id=invoiceIdFromContext($f),$btn=$f.find('button[type="submit"],input[type="submit"]').first();
   $btn.prop('disabled',true);
   $.ajax({url:form.action,type:(form.method||'POST').toUpperCase(),data:$f.serialize(),dataType:'html'})
    .done(function(html,textStatus,xhr){
      /* A successful native response may be a redirect/full page. Either way refresh preview. */
      reopen(id);
    })
    .fail(function(xhr){
      /* Known server behavior: payment is committed, then response rendering returns 500.
         Re-read the invoice instead of navigating to the broken response page. */
      if(xhr&&xhr.status===500){reopen(id);return;}
      busy=false;$btn.prop('disabled',false);
      var host=modal().find('.ifc-cashier-modal-content');
      host.prepend('<div class="alert alert-danger">ثبت پرداخت با خطا مواجه شد. لطفاً دوباره بررسی کنید.</div>');
    });
   return false;
 },true);
})(jQuery);

/* 0.1.56 - Bulk invoice import entry point. */
(function($){'use strict';
 function addBulkButton(){
   if(document.getElementById('ifc-bulk-invoice-import'))return;
   var url=window.IFCBulkImportUrl;if(!url)return;
   var candidates=document.querySelectorAll('a.btn,button.btn');
   var anchor=null;
   Array.prototype.forEach.call(candidates,function(el){var t=(el.textContent||'').trim();if(!anchor&&(t.indexOf('ایجاد فاکتور')>=0||t.indexOf('New Invoice')>=0))anchor=el;});
   if(!anchor)return;
   var a=document.createElement('a');a.id='ifc-bulk-invoice-import';a.className='btn btn-info mright5';a.href=url;a.innerHTML='<i class="fa fa-file-excel-o"></i> ورود گروهی فاکتورها';
   anchor.parentNode.insertBefore(a,anchor);
   var payUrl=window.IFCBulkPaymentsUrl;
   if(payUrl&&!document.getElementById('ifc-bulk-payments-import')){
     var pay=document.createElement('a');pay.id='ifc-bulk-payments-import';pay.className='btn btn-default mright5';pay.href=payUrl;pay.textContent='ورود تجمیعی پرداخت‌ها';
     a.parentNode.insertBefore(pay,a.nextSibling);
   }
 }
 $(function(){addBulkButton();setTimeout(addBulkButton,500);setTimeout(addBulkButton,1500)});
})(jQuery);


/* 0.1.58 - Safe invoice deletion for test/unpaid invoices.
   Adds row selection + single/bulk delete on the invoice list. Paid invoices are rejected server-side. */
(function($){'use strict';
 function csrf(){
   var n=(window.app&&app.options&&app.options.csrfData&&app.options.csrfData.token_name)||'csrf_token_name';
   var h=(window.app&&app.options&&app.options.csrfData&&app.options.csrfData.hash)||$('input[name="'+n+'"]').first().val()||'';
   var o={};o[n]=h;return o;
 }
 function invoiceId(tr){
   var a=tr.querySelector('a[href*="/admin/invoices/invoice/"],a[href*="/invoice/"]'),m;
   if(a){m=(a.getAttribute('href')||'').match(/\/admin\/invoices\/invoice\/(\d+)|\/invoice\/(\d+)\//i);if(m)return m[1]||m[2]||'';}
   var all=tr.querySelectorAll('a');for(var i=0;i<all.length;i++){var oc=all[i].getAttribute('onclick')||'';m=oc.match(/init_invoice\s*\(\s*['"]?(\d+)/i);if(m)return m[1];}
   return '';
 }
 function decorate(){
   var table=document.querySelector('table.table-invoices');if(!table)return;
   var hr=table.querySelector('thead tr');if(hr&&!hr.querySelector('.ifc-del-select-head')){
     var th=document.createElement('th');th.className='ifc-del-select-head';th.style.width='34px';th.innerHTML='<input type="checkbox" id="ifc-select-all-invoices" title="انتخاب همه">';hr.insertBefore(th,hr.firstChild);
   }
   table.querySelectorAll('tbody tr').forEach(function(tr){
     if(tr.querySelector('.ifc-del-select-cell'))return;var id=invoiceId(tr);if(!/^\d+$/.test(id))return;
     var td=document.createElement('td');td.className='ifc-del-select-cell';td.innerHTML='<input type="checkbox" class="ifc-invoice-delete-check" value="'+id+'">';tr.insertBefore(td,tr.firstChild);
     var first=tr.children[1]||tr.children[0];if(first&&!first.querySelector('.ifc-row-delete')){
       var box=document.createElement('span');box.innerHTML=' <span class="text-muted"> | </span><a href="#" class="text-danger ifc-row-delete" data-id="'+id+'">حذف</a>';first.appendChild(box);
     }
   });
   if(!document.getElementById('ifc-delete-selected-invoices')){
     var bulk=document.getElementById('ifc-bulk-invoice-import');if(bulk){var b=document.createElement('button');b.type='button';b.id='ifc-delete-selected-invoices';b.className='btn btn-danger mright5';b.innerHTML='<i class="fa fa-trash"></i> حذف انتخاب‌شده';bulk.parentNode.insertBefore(b,bulk.nextSibling);}
   }
 }
 function del(ids){
   ids=(ids||[]).map(String).filter(function(x){return /^\d+$/.test(x)});if(!ids.length){alert('حداقل یک فاکتور را انتخاب کنید.');return;}
   if(!confirm('فاکتورهای انتخاب‌شده حذف شوند؟ فاکتور دارای پرداخت حذف نخواهد شد.'))return;
   var data=csrf();data.ids=JSON.stringify(ids);
   $.post(window.IFCBulkDeleteUrl,data).done(function(r){
     if(typeof r==='string'){try{r=JSON.parse(r)}catch(e){}}
     // Remove only invoices the server verified as deleted, before showing the message.
     if(r&&Array.isArray(r.deleted)){
       r.deleted.forEach(function(id){
         $('.ifc-invoice-delete-check[value="'+id+'"]').closest('tr').remove();
       });
     }
     alert((r&&r.message)||'عملیات حذف انجام شد.');
     // Force a full refresh so counters and the DataTable are rebuilt from the database.
     window.location.reload();
   }).fail(function(xhr){alert('حذف فاکتور با خطا مواجه شد. ('+(xhr.status||'')+')');});
 }
 $(document).on('change','#ifc-select-all-invoices',function(){$('.ifc-invoice-delete-check').prop('checked',this.checked)});
 $(document).on('click','.ifc-row-delete',function(e){e.preventDefault();del([$(this).data('id')]);});
 $(document).on('click','#ifc-delete-selected-invoices',function(){var ids=[];$('.ifc-invoice-delete-check:checked').each(function(){ids.push(this.value)});del(ids);});
 $(function(){decorate();setTimeout(decorate,500);setTimeout(decorate,1400)});
 $(document).on('draw.dt xhr.dt',function(){setTimeout(decorate,40)});
})(jQuery);

/* 0.1.64 - Financial status + independent delivery/return states.
   Robust invoice identity: use native id when present; otherwise resolve the displayed formatted invoice number server-side. */
(function($){'use strict';
 function n164(s){return String(s||'').replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\s+/g,' ').trim().toLowerCase()}
 function invoiceId164(tr){
   var els=tr.querySelectorAll('[data-invoice-id],[data-id],input[type="checkbox"][value],a,[onclick]'),m;
   for(var i=0;i<els.length;i++){
     var e=els[i], vals=[e.getAttribute('data-invoice-id'),e.getAttribute('data-id'),e.value,e.getAttribute('onclick'),e.getAttribute('href')];
     for(var j=0;j<vals.length;j++){var x=String(vals[j]||'');m=x.match(/init_invoice\s*\(\s*['\"]?(\d+)/i)||x.match(/\/admin\/invoices\/(?:invoice\/|list_invoices\/|delete\/)?(\d+)(?:[\/?#]|$)/i);if(m)return m[1];if((e.matches('input[type="checkbox"]')||e.hasAttribute('data-invoice-id'))&&/^\d+$/.test(x))return x;}
   } return '';
 }
 function statusCol164(api){var idx=-1;api.columns().every(function(i){var h=this.header();if(h&&n164(h.textContent)==='وضعیت')idx=i;});return idx;}
 function invoiceCol164(api){var idx=-1;api.columns().every(function(i){var h=this.header(),t=n164(h&&h.textContent);if(t.indexOf('فاکتور')!==-1&&t.indexOf('#')!==-1)idx=i;});return idx;}
 function cleanRef164(s){s=String(s||'').replace(/مشاهده|ویرایش|حذف/gi,' ').replace(/\|/g,' ').replace(/\s+/g,' ').trim();return (s.match(/[A-Za-zآ-ی_\-]*\s*[0-9۰-۹]+/)||[''])[0].replace(/\s+/g,'');}
 function clear164(cell){if(!cell)return;cell.querySelectorAll('.ifc-op-sep,.ifc-op-badge').forEach(function(x){x.remove();});}
 function badge164(text,cls){var s=document.createElement('span');s.className='label ifc-op-badge '+cls;s.textContent=text;return s;}
 function decorate164(cell,st){if(!cell||!st||(!st.customer_delivery&&!st.return))return;var sep=document.createElement('span');sep.className='ifc-op-sep';sep.textContent=' | ';cell.appendChild(sep);if(st.customer_delivery)cell.appendChild(badge164('تحویل به مشتری','label-info'));if(st.customer_delivery&&st.return){var s2=document.createElement('span');s2.className='ifc-op-sep';s2.textContent=' | ';cell.appendChild(s2);}if(st.return)cell.appendChild(badge164('مرجوعی','label-danger'));}
 function apply164(){
   var table=document.querySelector('table.table-invoices');if(!table||!$.fn.dataTable||!$.fn.dataTable.isDataTable(table))return;
   var api=$(table).DataTable(),sc=statusCol164(api),ic=invoiceCol164(api);if(sc<0)return;
   var rows=[],ids=[],refs=[];
   api.rows({page:'current'}).every(function(){var tr=this.node();if(!tr)return;var id=invoiceId164(tr),cell=api.cell(this.index(),sc).node(),ref='';clear164(cell);if(ic>=0){var icell=api.cell(this.index(),ic).node();ref=cleanRef164(icell&&icell.textContent);}rows.push({id:id,ref:ref,cell:cell});if(/^\d+$/.test(id))ids.push(id);if(ref)refs.push(encodeURIComponent(ref));});
   if(!rows.length||!window.IFCOperationalStatusesUrl)return;
   $.getJSON(window.IFCOperationalStatusesUrl,{ids:ids.join(','),refs:refs.join('|'),_:(Date.now())}).done(function(r){if(!r||!r.success)return;rows.forEach(function(x){var st=(x.id&&r.statuses&&r.statuses[x.id])||(x.ref&&r.by_ref&&r.by_ref[x.ref])||null;decorate164(x.cell,st);});});
 }
 $(function(){setTimeout(apply164,250);setTimeout(apply164,900);});
 $(document).on('draw.dt.ifcOperational xhr.dt.ifcOperational',function(e,settings){var n=settings&&settings.nTable;if(n&&n.classList&&n.classList.contains('table-invoices'))setTimeout(apply164,60);});
})(jQuery);


/* 0.1.87.3 - Invoice list only: convert displayed Jalali dates, including
   <span class="laniakea-date"> produced by the server, to Gregorian.
   This does not touch DataTables source, the DB, forms, or other modules. */
(function($){'use strict';
 var fmt, cache=Object.create(null);
 try {fmt=new Intl.DateTimeFormat('en-US-u-ca-persian-nu-latn',{year:'numeric',month:'numeric',day:'numeric',timeZone:'UTC'});}catch(e){return;}
 function ascii(s){return String(s||'').replace(/[۰-۹]/g,function(c){return String(c.charCodeAt(0)-1776);}).replace(/[٠-٩]/g,function(c){return String(c.charCodeAt(0)-1632);});}
 function parts(t){var out={};fmt.formatToParts(new Date(t)).forEach(function(x){if(/^(year|month|day)$/.test(x.type))out[x.type]=+x.value;});return out;}
 function convert(s){var m=ascii(s).trim().match(/^(13\d{2}|14\d{2})[-\/](\d{1,2})[-\/](\d{1,2})$/);if(!m)return null;
   var jy=+m[1],jm=+m[2],jd=+m[3],key=[jy,jm,jd].join('-');if(Object.prototype.hasOwnProperty.call(cache,key))return cache[key];
   var day=86400000,lo=Date.UTC(jy+620,0,1),hi=Date.UTC(jy+623,0,1);
   while(lo<hi){var mid=Math.floor((lo+hi)/day/2)*day,p=parts(mid),cmp=(p.year-jy)*500+(p.month-jm)*32+(p.day-jd);if(cmp<0)lo=mid+day;else hi=mid;}
   var p=parts(lo);if(p.year!==jy||p.month!==jm||p.day!==jd)return cache[key]=null;
   var d=new Date(lo),pad=function(v){return String(v).padStart(2,'0');};return cache[key]=d.getUTCFullYear()+'-'+pad(d.getUTCMonth()+1)+'-'+pad(d.getUTCDate());
 }
 function norm(s){return ascii(s).replace(/[\u200c\u200f\u202a-\u202e]/g,'').replace(/\s+/g,' ').trim().toLowerCase();}
 function apply(){var table=document.querySelector('table.table-invoices');if(!table)return;
   var heads=table.querySelectorAll('thead th'),cols=[];
   Array.prototype.forEach.call(heads,function(h,i){var t=norm(h.textContent);if(t==='تاریخ'||t==='تاریخ سررسید'||t==='سررسید'||t==='date'||t==='due date')cols.push(i);});
   if(!cols.length)return;
   Array.prototype.forEach.call(table.querySelectorAll('tbody tr'),function(tr){cols.forEach(function(i){var td=tr.cells[i];if(!td)return;
     /* Keep the Laniakea wrapper: the previous patch skipped all cells with children. */
     var target=td.querySelector('.laniakea-date')||td;
     if(target!==td && target.children.length)return;
     if(target===td && target.children.length)return;
     var current=target.textContent.trim(),next=convert(current);if(!next||next===current)return;
     target.textContent=next;target.setAttribute('data-ifc-original-jalali',current);
   });});
 }
 $(function(){setTimeout(apply,100);setTimeout(apply,600);});
 $(document).on('draw.dt.ifcGregorianFix xhr.dt.ifcGregorianFix',function(e,settings){var t=settings&&settings.nTable;if(t&&t.classList&&t.classList.contains('table-invoices'))setTimeout(apply,60);});
})(jQuery);
