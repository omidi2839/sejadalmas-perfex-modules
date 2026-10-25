<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body" dir="rtl">
<h4 style="font-weight:400">ورود تجمیعی پرداخت‌ها</h4>
<p class="text-muted">فقط پرداخت‌های دارای تطبیق قطعی و مانده کافی قابل ثبت هستند. عودت‌ها و موارد مبهم ثبت نمی‌شوند.</p>
<?= form_open_multipart(admin_url('invoice_form_customizer/bulk_payments')) ?>
<div class="form-group"><label>فایل اکسل پرداخت‌ها</label><input type="file" name="payments_file" accept=".xlsx" required class="form-control"></div>
<button type="submit" class="btn btn-info">بررسی و تطبیق فایل</button><?= form_close() ?>
<?php foreach($errors as $e): ?><div class="alert alert-danger"><?= html_escape($e) ?></div><?php endforeach; ?>
<?php if($results): ?><div class="alert alert-info"><?php foreach($results as $r): ?><div>ردیف <?= (int)$r['row'] ?>: <?= html_escape($r['message']) ?></div><?php endforeach; ?></div><?php endif; ?>
<?php if($uploaded): ?>
<?php $ready=0;foreach($preview as $r){if($r['status']==='آماده ثبت قطعی')$ready++;} ?>
<p>کل ردیف‌ها: <?= count($preview) ?> | آماده ثبت: <?= $ready ?> | نیازمند بررسی: <?= count($preview)-$ready ?></p>
<div class="form-group"><label for="ifc-payment-filter">فیلتر وضعیت</label><select id="ifc-payment-filter" class="form-control" style="max-width:250px"><option value="all">همه</option><option value="ready" selected>آماده ثبت قطعی</option><option value="review">نیازمند بررسی</option></select></div>
<?= form_open(admin_url('invoice_form_customizer/bulk_payments'),['id'=>'ifc-payment-register']) ?>
<input type="hidden" name="ifc_payment_action" value="register"><input type="hidden" name="batch_token" value="<?= html_escape($batch_token) ?>">
<div class="form-group"><label>روش پرداخت</label><select name="paymentmode" class="form-control" style="max-width:320px" required><option value="">انتخاب روش پرداخت</option><?php foreach($modes as $m): ?><option value="<?= (int)$m['id'] ?>"><?= html_escape($m['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><button type="button" class="btn btn-default" id="ifc-select-all">انتخاب همه آماده‌ها</button> <button type="button" class="btn btn-default" id="ifc-select-none">لغو انتخاب</button> <button type="submit" class="btn btn-success" id="ifc-register-selected" <?= $ready?'':'disabled' ?>>ثبت پرداخت‌های انتخاب‌شده</button></div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>انتخاب</th><th>ردیف</th><th>شماره فاکتور</th><th>تاریخ</th><th>پرداخت</th><th>عودت</th><th>شناسه فاکتور</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($preview as $i=>$r): $ok=$r['status']==='آماده ثبت قطعی'; ?><tr data-ifc-status="<?= $ok?'ready':'review' ?>"><td><?php if($ok): ?><input type="checkbox" class="ifc-payment-check" name="selected[]" value="<?= (int)$i ?>"><?php endif; ?></td><td><?= (int)$r['row'] ?></td><td><?= html_escape($r['key']) ?></td><td><?= html_escape($r['date']) ?></td><td><?= number_format($r['paid']) ?></td><td><?= number_format($r['refund']) ?></td><td><?= html_escape($r['invoice_id']??'—') ?></td><td><?= html_escape($r['status']) ?><?php if($r['issues']): ?><small style="display:block;color:#a63b23"><?= html_escape($r['issues']) ?></small><?php endif; ?></td><td><?php if($ok): ?><button type="button" class="btn btn-default btn-xs ifc-payment-single">ثبت تکی</button><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><?= form_close() ?>
<script>
(function(){var f=document.getElementById('ifc-payment-register'),filter=document.getElementById('ifc-payment-filter');if(!f)return;
var checks=Array.prototype.slice.call(f.querySelectorAll('.ifc-payment-check'));
function apply(){Array.prototype.forEach.call(f.querySelectorAll('tr[data-ifc-status]'),function(tr){tr.style.display=(filter.value==='all'||tr.getAttribute('data-ifc-status')===filter.value)?'':'none';});}
filter.addEventListener('change',apply);apply();
document.getElementById('ifc-select-all').onclick=function(){checks.forEach(function(c){c.checked=true;});};
document.getElementById('ifc-select-none').onclick=function(){checks.forEach(function(c){c.checked=false;});};
f.querySelectorAll('.ifc-payment-single').forEach(function(btn){btn.onclick=function(){checks.forEach(function(c){c.checked=false;});btn.closest('tr').querySelector('.ifc-payment-check').checked=true;f.requestSubmit?f.requestSubmit():f.submit();};});
f.addEventListener('submit',function(e){var n=checks.filter(function(c){return c.checked;}).length;if(!n){e.preventDefault();alert('ابتدا یک ردیف را انتخاب کنید.');return;}if(!f.querySelector('[name="paymentmode"]').value){e.preventDefault();alert('روش پرداخت را انتخاب کنید.');return;}if(!confirm('ثبت قطعی '+n+' پرداخت انجام شود؟')){e.preventDefault();return;}f.querySelectorAll('button[type="submit"],.ifc-payment-single').forEach(function(b){b.disabled=true;});});
})();
</script>
<?php endif; ?>
</div></div></div></div><?php init_tail(); ?>
