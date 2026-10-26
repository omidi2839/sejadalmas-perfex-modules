<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body" style="direction:rtl;text-align:right">
<h3>تشخیص ریشه‌ای فاکتور 0.2.40 — Lock / Rate / Native Runtime — ۰.۲.۳۶</h3>
<p>این نسخه هیچ Capture، Hook، JavaScript یا شنود درخواست فاکتور ندارد. فاکتور مستقیماً از دیتابیس Perfex انتخاب و همان لحظه بررسی می‌شود.</p>
<form method="get" action="<?php echo admin_url('sales_delivery_flow/runtime_diagnostic'); ?>" class="form-inline" style="margin:18px 0">
 <label>شماره نمایشی فاکتور:</label>
 <input name="invoice_number" value="<?php echo html_escape($searched_number??''); ?>" class="form-control" style="min-width:220px" placeholder="مثلاً شماره فاکتور">
 <button type="submit" class="btn btn-primary">یافتن مستقیم از دیتابیس</button>
</form>
<?php if(!empty($snapshot)){ ?>
 <?php if(!empty($snapshot['error'])){ ?><div class="alert alert-danger">فاکتور با ID انتخاب‌شده در tblinvoices پیدا نشد.</div><?php } else { ?>
 <div class="alert alert-success"><strong>فاکتور انتخاب‌شده:</strong> ID داخلی <?php echo (int)$snapshot['invoice_id']; ?> — شماره <?php echo html_escape((string)($snapshot['invoice']['number']??'')); ?> — مرجوعی: <?php echo html_escape((string)($snapshot['return_state']['state']??'')); ?> — Lock: <?php echo !empty($snapshot['lock_state']['locked'])?'بله':'خیر'; ?></div>
 <div style="margin-bottom:12px"><a class="btn btn-success" target="_blank" href="<?php echo admin_url('sales_delivery_flow/runtime_diagnostic_json?invoice_id='.(int)$snapshot['invoice_id']); ?>">بازکردن JSON کامل همین فاکتور</a></div>
 <h5>Snapshot مستقیم دیتابیس</h5><pre style="direction:ltr;text-align:left;max-height:700px;overflow:auto"><?php echo html_escape(json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)); ?></pre>
 <?php } ?>
<?php } ?>
<h4 style="margin-top:25px">فاکتورهای دارای مرجوعی واقعی</h4>
<?php if(empty($returned_invoices)){ ?><div class="alert alert-warning">در جدول مرجوعی ماژول، فاکتوری پیدا نشد.</div><?php } else { ?>
<div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>ID</th><th>شماره فاکتور</th><th>تاریخ</th><th>جمع</th><th>تخفیف کل</th><th>آخرین مرجوعی</th><th>انتخاب</th></tr></thead><tbody>
<?php foreach($returned_invoices as $r){ ?><tr><td><?php echo (int)$r['id']; ?></td><td><?php echo html_escape((string)$r['number']); ?></td><td><?php echo html_escape((string)$r['date']); ?></td><td><?php echo html_escape((string)$r['total']); ?></td><td><?php echo html_escape((string)($r['discount_total']??'')); ?></td><td><?php echo html_escape((string)($r['return_created_at']??'')); ?></td><td><a class="btn btn-xs btn-primary" href="<?php echo admin_url('sales_delivery_flow/runtime_diagnostic?invoice_id='.(int)$r['id']); ?>">بررسی مستقیم</a></td></tr><?php } ?>
</tbody></table></div><?php } ?>
<h4 style="margin-top:25px">آخرین ۸۰ فاکتور دیتابیس</h4>
<div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>ID</th><th>شماره</th><th>تاریخ</th><th>Subtotal</th><th>Total</th><th>تخفیف</th><th>انتخاب</th></tr></thead><tbody>
<?php foreach($recent_invoices as $r){ ?><tr><td><?php echo (int)$r['id']; ?></td><td><?php echo html_escape((string)$r['number']); ?></td><td><?php echo html_escape((string)$r['date']); ?></td><td><?php echo html_escape((string)$r['subtotal']); ?></td><td><?php echo html_escape((string)$r['total']); ?></td><td><?php echo html_escape((string)($r['discount_total']??'')); ?></td><td><a class="btn btn-xs btn-default" href="<?php echo admin_url('sales_delivery_flow/runtime_diagnostic?invoice_id='.(int)$r['id']); ?>">بررسی مستقیم</a></td></tr><?php } ?>
</tbody></table></div>
<div class="alert alert-info">برای تست اصلی، از جدول «فاکتورهای دارای مرجوعی واقعی» همان فاکتور مشکل‌دار را انتخاب کنید. هیچ ID دستی لازم نیست.</div>
</div></div></div></div>
<?php init_tail(); ?>
