<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<style>
.ifc-audit .ifc-flag{color:#a33b18;background:#fff0e7;border-radius:6px;padding:4px 8px;margin:0 3px}.ifc-audit{font-family:inherit}.ifc-audit h4,.ifc-audit strong{font-weight:500}.ifc-audit .modal-dialog{width:92%;max-width:1080px;margin:3vh auto}.ifc-audit .modal-content{border-radius:17px;overflow:hidden}.ifc-audit .modal-header{background:#fff3e7;border-bottom:1px solid #f4d9bd;padding:19px 23px}.ifc-audit .modal-body{max-height:70vh;overflow-y:auto;overflow-x:hidden;background:#fffdfb;padding:18px 22px}.ifc-audit .ifc-panel{border:1px solid #f0dccb;border-radius:12px;margin-bottom:12px;background:#fff;overflow:hidden}.ifc-audit .ifc-panel summary{cursor:pointer;list-style:none;padding:14px 18px;background:#fff5eb;display:flex;justify-content:space-between;gap:10px;align-items:center;color:#7c4a24}.ifc-audit .ifc-panel summary::-webkit-details-marker{display:none}.ifc-audit .ifc-panel summary:before{content:'▾';margin-left:7px}.ifc-audit .ifc-panel:not([open]) summary:before{content:'▸'}.ifc-audit .ifc-panel-body{padding:8px 16px 14px}.ifc-audit .ifc-audit-line{display:grid;grid-template-columns:90px minmax(0,1fr) minmax(0,1fr) 92px;gap:12px;align-items:center;padding:12px 2px;border-bottom:1px solid #f2eae3}.ifc-audit .ifc-audit-line:last-child{border-bottom:0}.ifc-audit .ifc-label{color:#99613a}.ifc-audit .ifc-value{overflow-wrap:anywhere;line-height:1.8}.ifc-audit .ifc-value small{display:block;color:#8c8a88}.ifc-audit .ifc-decisions{display:flex;gap:5px;justify-content:flex-end}.ifc-audit .ifc-decisions button{border:1px solid #e8d9cc;border-radius:8px;background:#fff;padding:6px 10px;color:#805e4a}.ifc-audit .ifc-decisions button[data-choice="approve"].active{background:#e7f6ec;color:#167345;border-color:#8dd8ad}.ifc-audit .ifc-decisions button[data-choice="reject"].active{background:#ffebeb;color:#b42323;border-color:#f0a5a5}.ifc-audit .ifc-meta{font-size:12px;color:#7d8590}.ifc-audit .ifc-warn{color:#a63232;font-size:12px;padding:7px 10px;background:#fff1ef;border-radius:7px;margin:7px 0}.ifc-audit .ifc-status{font-size:12px;color:#987152}.ifc-audit .ifc-list-table td,.ifc-audit .ifc-list-table th{padding:12px 10px!important}.ifc-audit .ifc-review-btn{border:1px solid #e9bb8c;color:#9c5c29;background:#fff9f2;border-radius:9px}.ifc-audit .ifc-col-head{font-size:11px;color:#9e8e81}.ifc-audit .ifc-number{direction:ltr;display:inline-block;unicode-bidi:isolate}@media(max-width:720px){.ifc-audit .ifc-audit-line{grid-template-columns:68px 1fr 1fr;gap:6px}.ifc-audit .ifc-decisions{grid-column:2/-1;justify-content:flex-start}.ifc-audit .modal-dialog{width:97%}}
</style>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body ifc-audit" dir="rtl">
<h4>ورود تجمیعی فاکتورها <small class="text-muted">— بررسی و ثبت انتخابی</small></h4>
<div class="alert alert-warning">ثبت نهایی فقط برای فاکتورهای انتخاب‌شده انجام می‌شود. ابتدا یک فاکتور بدون مغایرت را آزمایش کنید.</div>
<a class="btn btn-default btn-sm" href="<?= admin_url('invoice_form_customizer/bulk_template') ?>">دریافت قالب ورود فاکتور</a> <a class="btn btn-default btn-sm" href="<?= admin_url('invoice_form_customizer/bulk_seller_cases') ?>">پرونده مغایرت فروشندگان</a> <a class="btn btn-default btn-sm" href="<?= admin_url('invoices') ?>">بازگشت</a><hr>
<?php if(!empty($notice)): ?><div class="alert alert-success"><?= html_escape($notice) ?></div><?php endif; ?>
<?php if(!empty($preview)): ?>
<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:12px 0">
 <span class="text-muted">پیش‌نمایش ذخیره‌شده از فایل قبلی فعال است. برای اعمال اصلاحات اکسل، فایل جدید را دوباره بارگذاری کنید.</span>
 <?= form_open(admin_url('invoice_form_customizer/bulk_import'),['class'=>'ifc-clear-preview-form','style'=>'display:inline-block;margin:0']) ?>
 <input type="hidden" name="ifc_clear_preview" value="1">
 <button type="submit" class="btn btn-default btn-sm">پاک‌کردن پیش‌نمایش قبلی</button>
 <?= form_close() ?>
</div>
<?php endif; ?>
<div class="ifc-upload-area style="border:1px solid #e8e8e8;border-radius:10px;padding:16px;margin:16px 0">
<?= form_open_multipart(admin_url('invoice_form_customizer/bulk_import'),['class'=>'ifc-replace-preview-form']) ?><div class="form-group"><label>فایل اکسل فاکتورها</label><input type="file" name="invoice_file" accept=".xlsx" class="form-control" required></div><button type="submit" class="btn btn-info"><?= !empty($preview)?'بارگذاری فایل جدید و جایگزینی پیش‌نمایش':'بارگذاری و نمایش پیش‌نمایش' ?></button><?= form_close() ?>
</div>
<?php if(!empty($preview)): ?>
<?php
$approved=[];$savedCases=[];$historicalReturns=[];$reviewWithIssues=[];$reviewClean=[];
foreach($preview as $ix=>$g){
 if(!empty($g['existing_invoice_id'])){$approved[$ix]=$g;continue;}
 if(!empty($g['historical_return_registered'])){$historicalReturns[$ix]=$g;continue;}
 if(!empty($g['saved_compliance_case'])){$savedCases[$ix]=$g;continue;}
 $issues=!empty($g['review_errors']);
 foreach(($g['items']??[]) as $it){
  if($it['historical_rate']!==null && $it['bank_rate']!==null && (float)$it['historical_rate']+0.0001<(float)$it['bank_rate'])$issues=true;
  if(!empty($it['discount_policy_found']) && $it['discount_allowed']!==null && (float)$it['discount_percent']>(float)$it['discount_allowed']+0.0001)$issues=true;
 }
 if($issues)$reviewWithIssues[$ix]=$g;else $reviewClean[$ix]=$g;
}
?>
<div class="alert alert-info">فاکتورهای شناسایی‌شده: <?= count($preview) ?> | تأییدشده در سامانه: <?= count($approved) ?> | مغایرت ثبت‌شده: <?= count($savedCases) ?> | مرجوعی تاریخی: <?= count($historicalReturns) ?> | نیازمند بررسی: <?= count($reviewWithIssues)+count($reviewClean) ?></div>
<ul class="nav nav-tabs" role="tablist" style="margin:12px 0">
<li class="active"><a href="#ifc-tab-review" data-toggle="tab">در انتظار بررسی (<?= count($reviewWithIssues)+count($reviewClean) ?>)</a></li>
<li><a href="#ifc-tab-approved" data-toggle="tab">ثبت‌شده‌ها (<?= count($approved) ?>)</a></li>
<li><a href="#ifc-tab-returns" data-toggle="tab">مرجوعی تاریخی (<?= count($historicalReturns) ?>)</a></li>
<li><a href="#ifc-tab-cases" data-toggle="tab">مغایرت ثبت‌شده (<?= count($savedCases) ?>)</a></li>
</ul>
<div class="tab-content">
<div class="tab-pane active" id="ifc-tab-review">
<ul class="nav nav-tabs" role="tablist" style="margin:12px 0 16px">
<li class="active"><a href="#ifc-review-issues" data-toggle="tab" style="color:#a33b18">دارای مغایرت یا خطای تطبیق (<?= count($reviewWithIssues) ?>)</a></li>
<li><a href="#ifc-review-clean" data-toggle="tab" style="color:#24764d">بدون مغایرت (<?= count($reviewClean) ?>)</a></li>
</ul>
<div class="tab-content">
<?php foreach([['id'=>'ifc-review-issues','rows'=>$reviewWithIssues,'active'=>true],['id'=>'ifc-review-clean','rows'=>$reviewClean,'active'=>false]] as $section): ?>
<div class="tab-pane <?= $section['active']?'active':'' ?>" id="<?= $section['id'] ?>">
<div class="table-responsive"><table class="table table-bordered ifc-list-table"><thead><tr><th>فاکتور</th><th>مشتری</th><th>تاریخ</th><th>فروشنده</th><th>نوع فروش</th><th>اقلام</th><th>وضعیت</th></tr></thead><tbody>
<?php foreach($section['rows'] as $ix=>$g): ?><tr><td><?= html_escape($g['invoice_key']) ?></td><td><?= html_escape($g['customer_name']) ?></td><td><?= html_escape($g['date']) ?></td><td><?= html_escape($g['seller_name']) ?></td><td><?= html_escape($g['sale_type_name']) ?></td><td><?= count($g['items']) ?></td><td><button type="button" class="btn btn-sm ifc-review-btn" data-toggle="modal" data-target="#ifcModal<?= $ix ?>">بررسی</button></td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
<?php endforeach; ?>
</div></div>
<div class="tab-pane" id="ifc-tab-returns"><table class="table table-bordered"><thead><tr><th>شماره فاکتور</th><th>مشتری</th><th>تاریخ</th><th>وضعیت</th></tr></thead><tbody><?php foreach($historicalReturns as $g): ?><tr style="background:#eef4fa"><td><?= html_escape($g['invoice_key']) ?></td><td><?= html_escape($g['customer_name']) ?></td><td><?= html_escape($g['date']) ?></td><td>مرجوعی تاریخی ثبت‌شده — بررسی غیرفعال</td></tr><?php endforeach; ?></tbody></table></div>
<div class="tab-pane" id="ifc-tab-cases"><div class="table-responsive"><table class="table table-bordered ifc-list-table"><thead><tr><th>شماره فاکتور</th><th>مشتری</th><th>تاریخ</th><th>فروشنده</th><th>وضعیت</th></tr></thead><tbody>
<?php foreach($savedCases as $g): ?><tr style="background:#fff3e5"><td><?= html_escape($g['invoice_key']) ?></td><td><?= html_escape($g['customer_name']) ?></td><td><?= html_escape($g['date']) ?></td><td><?= html_escape($g['seller_name']) ?></td><td><span style="color:#a65d14">مغایرت ثبت‌شده — بررسی مجدد غیرفعال</span></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="tab-pane" id="ifc-tab-approved"><div class="table-responsive"><table class="table table-bordered ifc-list-table"><thead><tr><th>شماره فاکتور فایل</th><th>شناسه در سامانه</th><th>تاریخ فایل</th><th>اقلام فایل</th><th>وضعیت</th></tr></thead><tbody>
<?php foreach($approved as $g): ?><tr style="background:#edf8ef"><td><?= html_escape($g['invoice_key']) ?></td><td><?= (int)$g['existing_invoice_id'] ?></td><td><?= html_escape($g['date']) ?></td><td><?= count($g['items']) ?></td><td><span style="color:#167345">✓ تأییدشده — ثبت‌شده در سامانه</span></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<?php foreach(array_replace($reviewWithIssues,$reviewClean) as $ix=>$g): ?>
<div class="modal fade" id="ifcModal<?= $ix ?>" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog"><div class="modal-content" dir="rtl">
<div class="modal-header"><button type="button" class="close pull-left" data-dismiss="modal">&times;</button><h4>فاکتور <?= html_escape($g['invoice_key']) ?> <small>— <?= count($g['items']) ?> قلم</small></h4><div class="ifc-meta">مشتری: <?= html_escape($g['customer_name']) ?> | فروشنده: <?= html_escape($g['seller_name']) ?> | نوع فروش: <?= html_escape($g['sale_type_name']) ?> | تاریخ: <?= html_escape($g['date']) ?></div></div>
<div class="modal-body">
<?php foreach(($g['review_errors']??[]) as $invoiceError): ?><div class="ifc-warn"><?= html_escape($invoiceError) ?></div><?php endforeach; ?>
<?php
$caseItems=[];
foreach($g['items'] as $ci){$p=$ci['historical_rate'];$t=$ci['bank_rate'];$d=(float)$ci['discount_percent'];$a=$ci['discount_allowed'];$found=!empty($ci['discount_policy_found']);
$caseItems[]=['row'=>(int)$ci['source_row'],'item_id'=>(int)$ci['item_id'],
'price'=>['detected'=>$p!==null&&$t!==null&&(float)$p+0.0001<(float)$t,'invoice'=>$p,'rule'=>$t],
'discount'=>['detected'=>$found&&$a!==null&&$d>(float)$a+0.0001,'invoice'=>$d,'rule'=>$a]];
}
$casePayload=base64_encode(json_encode(['invoice_key'=>$g['invoice_key'],'seller_id'=>(int)$g['sale_agent'],'date'=>$g['date'],'items'=>$caseItems],JSON_UNESCAPED_UNICODE));
$caseSignature=hash_hmac('sha256',$casePayload,(string)$this->config->item('encryption_key'));
?>
<?php foreach($g['items'] as $itemIndex=>$it):
 $hr=$it['historical_rate'];$sp=$it['bank_rate'];$dp=(float)$it['discount_percent'];$allowed=$it['discount_allowed'];$hasPolicy=!empty($it['discount_policy_found']);
 $fmt=function($n){if($n===null||$n==='')return '—';return number_format((float)$n,((float)$n==(float)(int)$n?0:2),'.',',');};
 $priceViolation=($hr!==null && $sp!==null && (float)$hr+0.0001<(float)$sp);
 $discountViolation=($hasPolicy && $allowed!==null && $dp>(float)$allowed+0.0001);
 $priceStatus=$hr===null?'قیمت فاکتور در قالب خالی است':($sp===null?'تعرفه معتبر قابل محاسبه نبود':((float)$hr<(float)$sp-0.0001?'کمتر از تعرفه؛ مغایرت':((float)$hr>(float)$sp+0.0001?'بالاتر از تعرفه؛ مجاز':'برابر؛ مجاز')));
 $discountStatus=$hasPolicy?($dp<=(float)$allowed+0.00001?'در محدوده مجاز':'بالاتر از سقف'):'در تاریخ فاکتور قاعده تخفیف منطبق پیدا نشد';
 $cc=$it['commercial_context']??[];
 $sourceName=trim((string)($it['source_product_name']??''));
 $fileCode=trim((string)($it['item_code']??''));
 $fileId=(int)($it['source_item_id']??$it['item_id']??0);
 $fileProduct=$sourceName!==''?$sourceName:('کد ثبت‌شده در فایل: '.$fileCode.' | شناسه ثبت‌شده در فایل: '.$fileId);
 $productName=trim((string)($it['description']??'').' '.(string)($it['long_description']??''));
 $sourceColor=trim((string)($it['source_color']??''));$sourceSize=trim((string)($it['source_size']??''));
 $sourceSeller=trim((string)($it['source_seller_name']??''));
 $fileSeller=$sourceSeller!==''?$sourceSeller:('شناسه فروشنده در فایل: '.(string)($g['sale_agent']??''));
 $sourceColorText=$sourceColor!==''?$sourceColor:'در فایل ورودی رنگ مستقلی ثبت نشده است';
 $sourceSizeText=$sourceSize!==''?$sourceSize:'در فایل ورودی اندازه مستقلی ثبت نشده است';
 $lines=[
 ['product','کالا',$sourceName?:'شرح کالای فاکتور در فایل وارد نشده',$productName?:'نام محصول سامانه خوانده نشد','کد فایل: '.$fileCode.' | شناسه فایل: '.$fileId.' | تعداد فایل: '.$fmt($it['qty'])],
 ['color','رنگ',$sourceColor?:'رنگ فاکتور استخراج نشده',(string)$it['product_color'],''],
 ['size','اندازه',$sourceSize?:'اندازه فاکتور استخراج نشده',(string)$it['product_size'],''],
 ['price','قیمت',$fmt($hr),$sp===null?'تعرفه محاسبه نشد':$fmt($sp),$priceStatus.' — '.($it['bank_rate_source']??'')],
 ['discount','تخفیف',$fmt($dp).'٪',$hasPolicy?$fmt($allowed).'٪':'بدون قاعده تخفیف در تاریخ فاکتور',$discountStatus],
 ['seller','فروشنده',$sourceSeller?:'نام فروشنده در فایل ثبت نشده',$g['seller_name'],'نوع فروش: '.$g['sale_type_name']],
 ['commission','کمیسیون','گروه کالا / نوع فروش',$cc['commission']??'قاعده خوانده نشد','استعلام خواندنی'],
 ['ranking','رتبه‌بندی','تاریخ فاکتور',$cc['ranking']??'دوره خوانده نشد','']
 ]; ?>
<details class="ifc-panel ifc-review-item" data-source-row="<?= (int)$it['source_row'] ?>" <?= $itemIndex===0?'open':'' ?>>
<summary><span>قلم <?= $itemIndex+1 ?> — <?= html_escape($sourceName?:$it['item_code']) ?></span><span class="ifc-status"><?php if($priceViolation): ?><span class="ifc-flag">مغایرت قیمت</span><?php endif; ?><?php if($discountViolation): ?><span class="ifc-flag">مغایرت تخفیف</span><?php endif; ?><?= (!$priceViolation&&!$discountViolation)?'بدون مغایرت قطعی در کنترل‌های قابل استعلام':'نیازمند رسیدگی' ?></span></summary>
<div class="ifc-panel-body">
<div class="ifc-audit-line ifc-col-head"><span></span><span>اطلاعات فاکتور</span><span>اطلاعات سامانه</span><span>تصمیم</span></div>
<?php foreach($lines as $line): ?>
<div class="ifc-audit-line" data-kind="<?= html_escape($line[0]) ?>"><div class="ifc-label"><?= html_escape($line[1]) ?></div><div class="ifc-value"><?= html_escape($line[2]) ?></div><div class="ifc-value"><?= html_escape($line[3]) ?><?php if($line[4]!==''): ?><small><?= html_escape($line[4]) ?></small><?php endif; ?></div><div class="ifc-decisions"><button type="button" data-choice="approve" title="تأیید">✓</button><button type="button" data-choice="reject" title="رد">✕</button></div></div>
<?php endforeach; ?>
<div class="ifc-audit-line" data-kind="row"><div class="ifc-label">کل قلم</div><div class="ifc-value">مبلغ خالص: <?= $fmt($it['historical_net']) ?></div><div class="ifc-value ifc-status">تصمیم نهایی قلم</div><div class="ifc-decisions"><button type="button" data-choice="approve">✓</button><button type="button" data-choice="reject">✕</button></div></div>
<?php foreach(($it['review_errors']??[]) as $err): ?><div class="ifc-warn"><?= html_escape($err) ?></div><?php endforeach; ?>
</div></details>
<?php endforeach; ?>
</div><div class="modal-footer">
<div class="ifc-review-counter" style="margin-bottom:10px;color:#87634b">ابتدا برای همه موارد ✓ یا ✕ انتخاب کنید.</div>
<?= form_open(admin_url('invoice_form_customizer/bulk_case_save'),['class'=>'ifc-review-form ifc-case-form','style'=>'display:inline-block']) ?>
<input type="hidden" name="review_invoice_key" value="<?= html_escape($g['invoice_key']) ?>">
<input type="hidden" name="review_decisions" class="ifc-review-json" value="">
<button type="submit" class="btn btn-warning btn-sm ifc-save-cases" disabled>ثبت مغایرت‌ها در پرونده فروشنده</button>
<?= form_close() ?>
<?php if(empty($g['review_errors'])): ?>
<?= form_open(admin_url('invoice_form_customizer/bulk_import'),['class'=>'ifc-review-form ifc-final-form','style'=>'display:inline-block;margin:0 8px']) ?>
<input type="hidden" name="review_invoice_key" value="<?= html_escape($g['invoice_key']) ?>">
<input type="hidden" name="review_decisions" class="ifc-review-json" value="">
<input type="hidden" name="ifc_ajax_final" value="1">
<input type="hidden" name="confirm_import" value="1">
<button type="submit" class="btn btn-success btn-sm ifc-final-button" disabled>ثبت نهایی این فاکتور</button>
<?= form_close() ?>
<?php else: ?><span class="text-danger">ثبت نهایی تا رفع خطاهای این فاکتور غیرفعال است.</span><?php endif; ?>
<?php $historicalNet=0;foreach($g['items'] as $retItem){$historicalNet+=isset($retItem['historical_net'])?(float)$retItem['historical_net']:((float)$retItem['qty']*(float)$retItem['rate']*(1-(float)$retItem['discount_percent']/100));} ?>
<?php if($historicalNet < -0.0001): ?>
<?= form_open(admin_url('invoice_form_customizer/bulk_historical_return'),['style'=>'display:inline-block;margin:0 8px','onsubmit'=>"return confirm('مرجوعی تاریخی بدون تغییر موجودی و حساب مشتری ثبت شود؟');"]) ?>
<input type="hidden" name="invoice_key" value="<?= html_escape($g['invoice_key']) ?>">
<button type="submit" class="btn btn-info btn-sm">ثبت مرجوعی تاریخی</button><?= form_close() ?>
<?php endif; ?>
<div class="ifc-final-result" role="status" aria-live="polite" style="display:none;white-space:pre-wrap;margin:10px 0;padding:10px;border-radius:8px"></div><button type="button" class="btn btn-default" data-dismiss="modal">بستن</button></div>
</div></div></div>
<?php endforeach; ?>
<?php endif; ?>
<?php if(!empty($created)): ?><div class="alert alert-success">فاکتورهای ثبت‌شده: <?php foreach($created as $c): ?> <span style="margin:0 8px"><?= html_escape($c['key']) ?> (شناسه <?= (int)$c['id'] ?>)</span><?php endforeach; ?></div><?php endif; ?>
<?php foreach($errors as $e): ?><div class="alert alert-danger"><?= html_escape($e) ?></div><?php endforeach; ?>
</div></div></div></div>
<script>
(function(){
 document.addEventListener('submit',function(e){
  if(e.target.classList.contains('ifc-clear-preview-form')){
   if(!confirm('فقط پیش‌نمایش موقت پاک شود؟ فاکتورهای ثبت‌شده و سوابق مغایرت حذف نمی‌شوند.'))e.preventDefault();
  }
  if(e.target.classList.contains('ifc-replace-preview-form') && document.querySelector('.ifc-list-table')){
   if(!confirm('فایل جدید جایگزین پیش‌نمایش قبلی شود؟ فاکتورهای ثبت‌شده در سامانه حذف نخواهند شد.'))e.preventDefault();
  }
 });
 function update(modal){
  var lines=Array.prototype.slice.call(modal.querySelectorAll('.ifc-review-item .ifc-audit-line[data-kind]'));
  var decisions={},rejected=0;
  lines.forEach(function(line){var b=line.querySelector('.ifc-decisions button.active');if(!b)return;var row=line.closest('.ifc-review-item').getAttribute('data-source-row');decisions[row+':'+line.getAttribute('data-kind')]=b.getAttribute('data-choice');if(b.getAttribute('data-choice')==='reject')rejected++;});
  var complete=Object.keys(decisions).length===lines.length && lines.length>0;
  modal.querySelectorAll('.ifc-review-json').forEach(function(i){i.value=JSON.stringify(decisions)});
  var c=modal.querySelector('.ifc-review-counter');if(c)c.textContent='بررسی '+Object.keys(decisions).length+' از '+lines.length+' مورد — '+rejected+' مغایرت';
  var caseButton=modal.querySelector('.ifc-save-cases');if(caseButton)caseButton.disabled=!complete||rejected===0;
  var finalButton=modal.querySelector('.ifc-final-button');if(finalButton)finalButton.disabled=!complete;
 }
 document.addEventListener('click',function(e){var b=e.target.closest('.ifc-decisions button');if(!b)return;var line=b.closest('.ifc-audit-line');if(!line)return;line.querySelectorAll('button').forEach(function(x){x.classList.remove('active')});b.classList.add('active');update(b.closest('.modal'));});
 document.addEventListener('submit',function(e){
  var f=e.target;if(!f.classList.contains('ifc-review-form'))return;
  var modal=f.closest('.modal');update(modal);
  var button=f.querySelector('button[type=submit]');
  if(button.disabled){e.preventDefault();return;}
  if(!f.classList.contains('ifc-final-form'))return;
  e.preventDefault();
  if(!confirm('فاکتور با همین اطلاعات تاریخی ثبت نهایی شود؟'))return;
  var result=modal.querySelector('.ifc-final-result');
  result.style.display='block';result.style.background='#fff6e8';result.style.color='#79502a';result.textContent='در حال ثبت فاکتور؛ لطفاً منتظر پاسخ سرور بمانید…';
  button.disabled=true;
  var fd=new FormData(f);
  fetch(f.action,{method:'POST',body:fd,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}})
   .then(function(r){return r.text().then(function(t){var x;try{x=JSON.parse(t)}catch(err){throw new Error('پاسخ سرور JSON نیست (HTTP '+r.status+'). احتمال خطای PHP، نشست یا محدودیت زمان اجرا؛ ابتدای پاسخ: '+t.replace(/<[^>]*>/g,' ').slice(0,250));}return x;});})
   .then(function(x){
     if(x.csrf_name&&x.csrf_hash){document.querySelectorAll('input[name="'+x.csrf_name+'"]').forEach(function(i){i.value=x.csrf_hash;});}
     if(x.success&&x.created&&x.created.length){var ids=x.created.map(function(z){return z.key+' — شناسه '+z.id;}).join('، ');result.style.background='#e8f7ee';result.style.color='#17643a';result.textContent='ثبت موفق: '+ids;button.textContent='ثبت شد';setTimeout(function(){window.location.reload();},600);return;}
     result.style.background='#fff0ee';result.style.color='#a12824';result.textContent='ثبت انجام نشد: '+((x.errors||[]).join(' | ')||'پاسخ بدون نتیجه از سرور');button.disabled=false;
   }).catch(function(err){result.style.background='#fff0ee';result.style.color='#a12824';result.textContent='خطا در درخواست ثبت: '+err.message+'؛ پیش از تلاش مجدد، وجود فاکتور را بررسی کنید.';button.disabled=false;});
 });
})();
</script>
<?php init_tail(); ?>
