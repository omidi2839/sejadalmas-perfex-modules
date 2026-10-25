<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<div id="wrapper"><div class="content"><div class="row"><div class="col-md-8 col-md-offset-2"><div class="panel_s"><div class="panel-body" dir="rtl" style="text-align:right">
<h4 style="font-weight:400">کنترل مهلت اولین پرداخت</h4>
<p class="text-muted">اگر اولین پرداخت معتبر در مهلت مقرر ثبت نشود، فاکتور در سامانه کنترل مهلت پرداخت معلق می‌شود. با ثبت پرداخت مثبت، تعلیق خودکار رفع می‌شود.</p>
<?php if($notice): ?><div class="alert alert-info"><?php echo html_escape($notice); ?></div><?php endif; ?>
<?php echo form_open(admin_url('invoice_form_customizer/payment_deadline_settings')); ?>
<div class="checkbox"><label><input type="checkbox" name="first_payment_enabled" value="1" <?php echo $enabled?'checked':''; ?>> فعال‌سازی تعلیق خودکار</label></div>
<div class="form-group"><label for="first_payment_days">مهلت اولین پرداخت (روز تقویمی)</label><input id="first_payment_days" class="form-control" style="max-width:170px" type="number" min="1" max="365" name="first_payment_days" value="<?php echo (int)$days; ?>" required></div>
<p class="text-muted">مهلت از زمان ثبت نهایی فاکتور آغاز می‌شود. تغییر تنظیمات مهلت فاکتورهای قبلی را بازنویسی نمی‌کند. فاکتورهای تاریخی واردشده با زمینه تأییدشده ورود تجمیعی از ثبت مهلت مستثنا هستند.</p>
<button type="submit" name="save_payment_deadline" value="1" class="btn btn-primary">ذخیره تنظیمات</button>
<?php echo form_close(); ?>
<hr><p class="text-muted">توجه: وضعیت تعلیق در جدول مستقل ماژول ثبت می‌شود و وضعیت مالی استاندارد Perfex را تغییر نمی‌دهد. مسدودسازی عملیاتی خروج و تحویل کالا نیازمند اتصال ماژول‌های مربوطه به این وضعیت است.</p>
</div></div></div></div></div></div>
<?php init_tail(); ?>
