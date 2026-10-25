<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body" dir="rtl" style="font-weight:400">
<h4 style="font-weight:500">پرونده مغایرت قیمت و تخفیف فروشندگان</h4>
<p class="text-muted">فقط مغایرت‌های ثبت‌شده با تأیید کاربر نمایش داده می‌شوند. مغایرت به معنی تخلف قطعی نیست. این بخش مستقل از کمیسیون، رتبه‌بندی و موجودی است.</p>
<a class="btn btn-default btn-sm" href="<?= admin_url('invoice_form_customizer/bulk_import') ?>">بازگشت به ورود تجمیعی</a>
<style>
.ifc-case-reviewed-pending{background:#eef5ff !important;border-right:3px solid #79a7db}
.ifc-case-awaiting{background:#fff !important}
.ifc-case-state{display:inline-block;padding:3px 8px;border-radius:10px;font-size:11px;font-weight:400;margin-bottom:5px}
.ifc-case-reviewed-pending .ifc-case-state{background:#dceafb;color:#285b8d}
.ifc-case-awaiting .ifc-case-state{background:#f0f0f0;color:#666}
</style>
<div class="table-responsive" style="margin-top:20px"><table class="table table-bordered"><thead><tr><th>فروشنده</th><th>فاکتور دارای مغایرت قیمت</th><th>اقلام مغایرت قیمت</th><th>فاکتور دارای مغایرت تخفیف</th><th>اقلام مغایرت تخفیف</th><th>موارد تأییدشده</th></tr></thead><tbody>
<?php foreach($summary as $s): ?><tr><td><a href="<?= admin_url('invoice_form_customizer/bulk_seller_cases?seller_id='.(int)$s['seller_id']) ?>"><?= html_escape($s['name']) ?></a></td><td><?= count($s['price_invoices']) ?></td><td><?= $s['price_items'] ?></td><td><?= count($s['discount_invoices']) ?></td><td><?= $s['discount_items'] ?></td><td><?= $s['confirmed'] ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<h4 style="font-weight:500">سوابق مغایرت‌ها</h4><div class="table-responsive"><table class="table table-striped"><thead><tr><th>فاکتور</th><th>فروشنده</th><th>تاریخ</th><th>ردیف</th><th>نوع</th><th>فاکتور</th><th>قاعده</th><th>اختلاف</th><th>وضعیت و رسیدگی</th></tr></thead><tbody>
<?php foreach($cases as $r):
  $ifcHasDecision = in_array((string)$r['status'], ['confirmed','authorized'], true);
  $ifcRowClass = $ifcHasDecision ? 'ifc-case-reviewed-pending' : 'ifc-case-awaiting';
  $ifcStateText = $ifcHasDecision ? 'تصمیم ثبت‌شده؛ فاکتور هنوز نیازمند بررسی ثبت است' : 'در انتظار تعیین‌تکلیف';
?><tr class="<?= $ifcRowClass ?>"><td><?= html_escape($r['invoice_key']) ?></td><td><?= html_escape(trim(($r['firstname']??'').' '.($r['lastname']??''))) ?></td><td><?= html_escape($r['invoice_date']) ?></td><td><?= (int)$r['source_row'] ?></td><td><?= $r['case_type']==='price'?'قیمت':'تخفیف' ?></td><td><?= number_format((float)$r['invoice_value'],2) ?></td><td><?= number_format((float)$r['rule_value'],2) ?></td><td><?= number_format((float)$r['difference_value'],2) ?></td><td>
<div><span class="ifc-case-state"><?= html_escape($ifcStateText) ?></span></div><?= form_open(admin_url('invoice_form_customizer/bulk_case_review')) ?><input type="hidden" name="case_id" value="<?= (int)$r['id'] ?>">
<select name="status" class="form-control input-sm" aria-label="وضعیت مغایرت"><option value="detected" <?= $r['status']==='detected'?'selected':'' ?>>مغایرت شناسایی‌شده</option><option value="confirmed" <?= $r['status']==='confirmed'?'selected':'' ?>>تخلف تأییدشده</option><option value="authorized" <?= $r['status']==='authorized'?'selected':'' ?>>استثنای مجاز</option></select>
<input type="text" name="review_note" class="form-control input-sm" placeholder="دلیل تصمیم / مجوز" value="<?= html_escape($r['review_note']??'') ?>"><button type="submit" class="btn btn-default btn-xs" style="margin-top:5px">ذخیره تصمیم</button><?= form_close() ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div></div></div><?php init_tail(); ?>
