<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body" dir="rtl">
<h3>گزارش تشخیص شمارش فاکتورها</h3>
<p>کارشناس: <?=html_escape(trim($staff['firstname'].' '.$staff['lastname']))?> | شناسه: <?=(int)$staff_id?></p>
<p>دوره حقوق و پاداش: <?=html_escape($start?:'تعریف نشده')?> تا <?=html_escape($end?:'تعریف نشده')?> | <?=(int)$days?> روز</p>
<p>قاعده ارتقا: <?=$rule?'فعال؛ هدف '.(int)$rule['target_count'].' فاکتور':'قاعده فعال یافت نشد'?></p>
<p class="text-muted">گزارش صرفاً خواندنی است. شمارنده‌ها کل بازه را بررسی می‌کنند؛ جدول ۱۵۰ فاکتور آخر بازه را نمایش می‌دهد.</p>
<?php if(!$period):?><div class="alert alert-warning">تنظیم فعال حقوق و پاداش برای کارشناس یافت نشد.</div><?php endif;?>
<h4>تطبیق شناسه کارشناس با ستون‌های فاکتور</h4>
<table class="table table-bordered"><thead><tr><th>ستون</th><th>تعداد تطبیق در بازه</th></tr></thead><tbody>
<?php foreach($matches as $f=>$n):?><tr><td><?=html_escape($f)?><?=$f==='sale_agent'?' (مبنای شمارش فعلی)':''?></td><td><?=(int)$n?></td></tr><?php endforeach;?></tbody></table>
<h4>فاکتورهای بازه و علت شمارش نشدن</h4>
<?php if(!$start):?><div class="alert alert-warning">تاریخ شروع دوره یافت نشد.</div><?php elseif(!in_array('date',$fields,true)):?><div class="alert alert-danger">ستون date موجود نیست.</div><?php else:?>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>شناسه</th><th>شماره</th><th>تاریخ</th><th>وضعیت</th><?php foreach($candidates as $f):?><th><?=html_escape($f)?></th><?php endforeach;?><th>نتیجه منطق فعلی</th></tr></thead><tbody>
<?php foreach($rows as $r):$cancelled=isset($r['status'])&&(int)$r['status']===5;$match=isset($r['sale_agent'])&&(int)$r['sale_agent']===(int)$staff_id;
$reason=!in_array('sale_agent',$fields,true)?'ستون sale_agent وجود ندارد':(!$match?'شناسه فروشنده برابر نیست':($cancelled?'لغوشده':'قابل شمارش'));?>
<tr><td><?=(int)$r['id']?></td><td><?=html_escape((string)($r['formatted_number']??(($r['prefix']??'').($r['number']??''))))?></td><td><?=html_escape($r['date'])?></td><td><?=html_escape((string)($r['status']??'—'))?></td>
<?php foreach($candidates as $f):?><td><?=html_escape((string)($r[$f]??'—'))?></td><?php endforeach;?><td><?=html_escape($reason)?></td></tr><?php endforeach;?>
<?php if(!$rows):?><tr><td colspan="<?=5+count($candidates)?>">فاکتوری در بازه یافت نشد.</td></tr><?php endif;?></tbody></table></div><?php endif;?>
<h4>بررسی منبع نمایش مأمور فروش</h4>
<p class="text-muted">مقادیر زیر فقط برای مدیر سامانه نمایش داده می‌شوند. اگر نام فروشنده در فاکتور دیده می‌شود اما ستون‌های اصلی صفرند، اطلاعات این بخش به شناسایی منبع کمک می‌کند.</p>
<h5>ستون‌های مرتبط با فروشنده در جدول فاکتور</h5>
<table class="table table-bordered"><thead><tr><th>فاکتور</th><th>نام ستون</th><th>مقدار</th></tr></thead><tbody>
<?php foreach($extra_invoice_values as $iid=>$vals):foreach($vals as $field=>$value):?><tr><td><?=(int)$iid?></td><td><?=html_escape($field)?></td><td><?=html_escape((string)$value)?></td></tr><?php endforeach;endforeach; ?>
</tbody></table>
<h5>فیلدهای سفارشی فاکتور</h5>
<table class="table table-bordered"><thead><tr><th>شناسه فیلد</th><th>عنوان</th></tr></thead><tbody><?php foreach($invoice_custom_fields as $f):?><tr><td><?=(int)$f['id']?></td><td><?=html_escape($f['name'])?></td></tr><?php endforeach;?></tbody></table>
<h5>مقادیر فیلدهای سفارشی فاکتورهای دوره</h5>
<table class="table table-bordered"><thead><tr><th>فاکتور</th><th>شناسه فیلد</th><th>مقدار</th></tr></thead><tbody>
<?php foreach($custom_values as $iid=>$vals):foreach($vals as $v):?><tr><td><?=(int)$iid?></td><td><?=(int)$v['fieldid']?></td><td><?=html_escape((string)$v['value'])?></td></tr><?php endforeach;endforeach;?></tbody></table>
<a class="btn btn-default" href="<?=admin_url('sales_commercial_rules/online_promotion?tab=status&section=monitor')?>">بازگشت</a>
</div></div></div></div><?php init_tail(); ?>