<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?><div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body">
<h4>تطبیق تأییدشده فروشنده فاکتورهای بدون شناسه</h4><p>فقط فاکتورهای فاقد فروشنده ثبت‌شده نمایش داده می‌شوند. انتساب باید با سند فاکتور توسط مدیر تأیید شود؛ داده اصلی فاکتور تغییر نمی‌کند.</p>
<a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=status')?>">بازگشت به وضعیت کارشناسان</a>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>شناسه</th><th>شماره نمایشی</th><th>تاریخ</th><th>انتساب تأییدشده</th></tr></thead><tbody>
<?php foreach($invoices as $inv): ?><tr><td><?= (int)$inv['id'] ?></td><td><?=html_escape($inv['formatted_number'])?></td><td><?=html_escape($inv['date'])?></td><td>
<?=form_open(admin_url('sales_commercial_rules/online_invoice_attributions'))?><input type="hidden" name="invoice_id" value="<?=(int)$inv['id']?>">
<select name="staff_id" class="form-control" style="max-width:320px;display:inline-block"><option value="0">بدون انتساب</option><?php foreach($staff as $p): ?><option value="<?=(int)$p['staffid']?>" <?=((int)$inv['staff_id']===(int)$p['staffid'])?'selected':''?>><?=html_escape(trim($p['firstname'].' '.$p['lastname']))?></option><?php endforeach; ?></select>
<button class="btn btn-default btn-sm" type="submit">ثبت تأیید مدیر</button><?=form_close()?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div></div></div><?php init_tail(); ?>
