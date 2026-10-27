<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content"><div class="row"><div class="col-md-8 col-md-offset-2"><div class="panel_s"><div class="panel-body">
<h4 class="no-margin">تنظیمات فروش</h4><hr class="hr-panel-heading" />
<?php echo form_open(admin_url('sales_commercial_rules/sales_settings')); ?>
<div class="form-group"><label for="manual_adjustment_limit">سقف تعدیل دستی فاکتور (دینار)</label>
<input type="number" min="0" step="1" class="form-control" id="manual_adjustment_limit" name="manual_adjustment_limit" value="<?php echo html_escape($manual_adjustment_limit); ?>">
<p class="text-muted mtop10">این سقف برای تعدیل دستی صندوقدار، هم در مقدار مثبت و هم منفی اعمال می‌شود. تعدیل اتوماتیک مشمول این سقف نیست.</p></div>
<button type="submit" name="save_sales_settings" value="1" class="btn btn-primary">ذخیره</button>
<?php echo form_close(); ?>
<hr><h4>دستگاه‌های POS</h4>
<p class="text-muted">دستگاه‌های فعال در فرم ثبت پرداخت نقدی و سایر پرداخت‌های فاکتور نمایش داده می‌شوند.</p>
<?php echo form_open(admin_url('sales_commercial_rules/sales_settings')); ?>
<div class="row"><div class="col-md-9"><input class="form-control" name="pos_device_name" required placeholder="نام دستگاه POS؛ مثال: POS صندوق ۱"></div><div class="col-md-3"><button class="btn btn-primary btn-block" name="add_pos_device" value="1"><i class="fa fa-plus"></i> افزودن دستگاه</button></div></div>
<?php echo form_close(); ?>
<div class="table-responsive mtop20"><table class="table table-bordered"><thead><tr><th>نام دستگاه</th><th style="width:160px">وضعیت</th></tr></thead><tbody>
<?php foreach(($pos_devices??[]) as $d): ?><tr><td><?php echo html_escape($d['name']); ?></td><td><?php echo form_open(admin_url('sales_commercial_rules/sales_settings'),['style'=>'margin:0']); ?><input type="hidden" name="pos_device_id" value="<?php echo (int)$d['id']; ?>"><input type="hidden" name="active" value="<?php echo !empty($d['active'])?0:1; ?>"><button class="btn <?php echo !empty($d['active'])?'btn-success':'btn-default'; ?> btn-sm" name="toggle_pos_device" value="1"><?php echo !empty($d['active'])?'فعال':'غیرفعال'; ?></button><?php echo form_close(); ?></td></tr><?php endforeach; ?>
<?php if(empty($pos_devices)): ?><tr><td colspan="2" class="text-muted text-center">هنوز دستگاه POS تعریف نشده است.</td></tr><?php endif; ?>
</tbody></table></div>
</div></div></div></div></div></div>
<?php init_tail(); ?>
</body></html>
