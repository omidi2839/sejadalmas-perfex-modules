<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<div id="wrapper"><div class="content"><div class="row"><div class="col-md-8 col-md-offset-2"><div class="panel_s"><div class="panel-body">
<h4 class="no-margin">تنظیمات فاکتور</h4><hr class="hr-panel-heading">
<form method="post">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<div class="form-group"><label for="manual_adjustment_limit">سقف تعدیل دستی فاکتور (دینار)</label>
<input type="number" min="0" step="1" class="form-control" id="manual_adjustment_limit" name="manual_adjustment_limit" value="<?=html_escape($limit)?>">
<p class="text-muted mtop10">این سقف برای تعدیل دستی مثبت و منفی اعمال می‌شود. برای مثال با مقدار ۵۰۰۰، بازه مجاز از ۵۰۰۰- تا ۵۰۰۰+ دینار است. تعدیل اتوماتیک مشمول این سقف نیست.</p></div>
<button type="submit" class="btn btn-primary">ذخیره</button>
</form></div></div></div></div></div></div>
<?php init_tail(); ?>
