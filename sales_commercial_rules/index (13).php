<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head();
$goal_tab=(isset($goal_tab)&&$goal_tab==='settings')?'settings':'report';
$goal_closed=!empty($goal_closed);
$period_title=$start_date.' تا '.$end_date;
$period_url=admin_url('sales_commercial_rules/monthly_goals?start_date='.rawurlencode($start_date).'&end_date='.rawurlencode($end_date));
?>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body" dir="rtl">
<style>.scr-goal-title{color:#182c43;font-size:21px;margin:0 0 20px}.scr-goal-panel{border:1px solid #ffdcc0;border-radius:12px;padding:18px;margin-bottom:18px;background:#fffaf6}.scr-goal-panel label{display:block;font-weight:400;color:#526174;margin-bottom:6px}.scr-goal-btn{background:#ff8215;color:white;border:0;border-radius:8px;padding:9px 18px}.scr-goal-btn:hover{color:white;background:#ec7000}.scr-goal-table th{background:#ffe5ce;color:#713d13}.scr-goal-table td,.scr-goal-table th{text-align:right;padding:12px!important}.scr-goal-progress{height:8px;background:#eef0f4;border-radius:20px;overflow:hidden;min-width:100px}.scr-goal-progress span{display:block;height:100%;background:#ff8215}.scr-goal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px}.scr-goal-help{font-size:12px;color:#697c8c;margin:8px 0}.scr-goal-tabs{display:flex;gap:22px;border-bottom:1px solid #e3e7ee;margin:0 0 20px}.scr-goal-tab{display:inline-block;padding:13px 18px;color:#34465b;border-bottom:3px solid transparent}.scr-goal-tab.active{color:#f97316;border-bottom-color:#f97316}.scr-goal-tab-content{display:none}.scr-goal-tab-content.active{display:block}.scr-multi{position:relative}.scr-multi-toggle{width:100%;text-align:right;background:#fff}.scr-multi-menu{display:none;position:absolute;z-index:1050;top:100%;right:0;left:0;background:white;border:1px solid #d7dee8;border-radius:8px;box-shadow:0 8px 25px #162b4320;max-height:280px;overflow:auto;padding:10px}.scr-multi-menu.open{display:block}.scr-multi-menu label{display:flex;gap:8px;align-items:center;padding:6px;cursor:pointer}.scr-multi-menu input{margin:0}.scr-multi-actions{display:flex;justify-content:space-between;gap:8px;padding:6px;border-bottom:1px solid #eee}.scr-multi-actions button{border:0;background:transparent;color:#f97316}</style>
<style>
.scr-goal-date-row{display:grid;grid-template-columns:repeat(2,minmax(185px,1fr)) auto;gap:12px;align-items:end;max-width:750px}
.scr-goal-date-row .scr-goal-btn{height:35px}
.scr-goal-period-label{font-size:13px;color:#516176}
.scr-goal-period-label strong{color:#26384a}
.scr-goal-period-help{margin-top:9px;color:#65768b;font-size:12px}
.scr-goal-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:15px;align-items:start}
.scr-goal-form-date{direction:ltr;text-align:left}
.scr-goal-table td,.scr-goal-table th{vertical-align:middle!important}
.scr-goal-tabs{display:flex;gap:14px;border-bottom:1px solid #e8edf2;margin-bottom:20px}
.scr-goal-tab{display:inline-block;padding:10px 14px;color:#526174;border-bottom:2px solid transparent;text-decoration:none}
.scr-goal-tabs .scr-goal-tab.active{border-color:#ff8215;color:#f47b10;font-weight:600}
.scr-goal-tab-content{display:none}.scr-goal-tab-content.active{display:block}
.scr-multi{position:relative}.scr-multi-toggle{text-align:right;background:white;min-height:35px}
.scr-multi-menu{display:none;position:absolute;z-index:101;width:100%;max-height:260px;overflow-y:auto;background:#fff;border:1px solid #dfe5ee;border-radius:9px;padding:11px;box-shadow:0 9px 30px #24364c20}
.scr-multi-menu.open{display:block}.scr-multi-actions{display:flex;justify-content:space-between;gap:8px;margin-bottom:8px}
.scr-multi-actions button{border:0;background:#fff2e4;padding:5px 10px;border-radius:6px}
.scr-goal-staff-item{display:flex!important;align-items:center;gap:8px;margin:4px 0!important;font-weight:normal!important}
.scr-goal-help{color:#65768b;font-size:12px;margin:10px 0 0}
.scr-goal-progress{height:6px;min-width:70px;border-radius:6px;background:#edf2f7;margin-bottom:4px;overflow:hidden}
.scr-goal-progress span{display:block;height:100%;background:#ff8215;border-radius:6px}
.scr-goal-empty td{text-align:center;padding:18px!important}
@media(max-width:650px){.scr-goal-date-row{grid-template-columns:1fr 1fr}.scr-goal-date-row .scr-goal-btn{grid-column:1/-1}}
</style>
<h3 class="scr-goal-title">اهداف فروش دوره‌ای</h3>
<nav class="scr-goal-tabs">
<a class="scr-goal-tab <?= $goal_tab==='report'?'active':'' ?>" href="<?= html_escape($period_url.'&tab=report') ?>">گزارش اهداف فروش</a>
<a class="scr-goal-tab <?= $goal_tab==='settings'?'active':'' ?>" href="<?= html_escape($period_url.'&tab=settings') ?>">تنظیمات اهداف فروش</a>
</nav>
<div class="scr-goal-tab-content <?= $goal_tab==='report'?'active':'' ?>">
<div class="scr-goal-panel">
<form method="get" action="<?= admin_url('sales_commercial_rules/monthly_goals') ?>">
<input type="hidden" name="tab" value="report">
<div class="scr-goal-date-row">
 <div><label for="scr-goal-report-start">از تاریخ</label><input class="form-control scr-goal-form-date" type="date" id="scr-goal-report-start" name="start_date" value="<?= html_escape($start_date) ?>" required></div>
 <div><label for="scr-goal-report-end">تا تاریخ</label><input class="form-control scr-goal-form-date" type="date" id="scr-goal-report-end" name="end_date" value="<?= html_escape($end_date) ?>" required></div>
 <button class="scr-goal-btn" type="submit">نمایش گزارش</button>
</div>
</form><p class="scr-goal-period-help">گزارش مربوط به اهداف تعریف‌شده برای همین بازه است؛ تاریخ پایان نیز در محاسبات منظور می‌شود.</p>
</div>
<div class="scr-goal-panel" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
 <div class="scr-goal-period-label"><strong>بازه <?= html_escape($period_title) ?></strong>
 <span class="label <?= $goal_closed?'label-success':'label-default' ?>"><?= $goal_closed?'بسته‌شده':'در جریان' ?></span>
 <?php if($goal_closed): ?><div class="scr-goal-help">مقادیر تحقق این بازه در زمان بستن ثبت و ثابت شده‌اند.</div><?php endif; ?></div>
 <form method="post" action="<?= admin_url('sales_commercial_rules/monthly_goal_month_action') ?>" onsubmit="return confirm('آیا از تغییر وضعیت گزارش این بازه مطمئن هستید؟')">
 <?php echo form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()); ?>
 <input type="hidden" name="start_date" value="<?= html_escape($start_date) ?>"><input type="hidden" name="end_date" value="<?= html_escape($end_date) ?>">
 <input type="hidden" name="action" value="<?= $goal_closed?'reopen':'close' ?>">
 <button class="btn <?= $goal_closed?'btn-default':'btn-warning' ?>" type="submit"><?= $goal_closed?'بازگشایی بازه':'بستن گزارش بازه' ?></button>
 </form>
</div>
<div class="scr-goal-filter-panel">
 <div class="scr-goal-switch" id="scr-goal-role-tabs"><button type="button" class="scr-goal-tab active" data-role="seller">کارشناسان فروش</button><button type="button" class="scr-goal-tab" data-role="manager">مدیران فروش</button></div>
 <div class="scr-goal-type-switch" id="scr-goal-type-tabs"><button type="button" class="scr-goal-tab active" data-type="all">همه انواع فروش</button><?php foreach($types as $t): ?><button type="button" class="scr-goal-tab" data-type="<?= (int)($t['legacy_id']?:$t['id']) ?>"><?= html_escape($t['name']) ?></button><?php endforeach; ?></div>
</div>
<div class="scr-goal-panel scr-goal-results"><h4>عملکرد بازه <?= html_escape($period_title) ?></h4>
<div class="table-responsive"><table class="table table-bordered scr-goal-table"><thead><tr><th>شخص</th><th>نقش</th><th>نوع فروش</th><th>هدف (د.ع)</th><th>محقق‌شده (د.ع)</th><th>باقیمانده (د.ع)</th><th>تحقق</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($rows as $r): $pct=(float)($r['percent_achieved']??0); ?>
<tr data-goal-role="<?= html_escape($r['role']) ?>" data-goal-type="<?= (int)$r['sale_type_id'] ?>">
 <td><?= html_escape($r['staff_name']) ?></td><td><?= $r['role']==='manager'?'مدیر':'کارشناس' ?></td>
 <td><?= (int)$r['sale_type_id']===0?'همه انواع فروش':html_escape($r['sale_type_name']?:'نامشخص') ?></td>
 <td><?= number_format((float)$r['target_amount'],0) ?></td><td><?= number_format((float)$r['actual_amount'],0) ?></td>
 <td><?= number_format(max(0,(float)$r['target_amount']-(float)$r['actual_amount']),0) ?></td>
 <td><div class="scr-goal-progress"><span style="width:<?= min(100,max(0,$pct)) ?>%"></span></div><?= number_format($pct,1) ?>٪</td>
 <td><?php if(!$goal_closed): ?><form method="post" action="<?= admin_url('sales_commercial_rules/delete_monthly_goal/'.(int)$r['id']) ?>" onsubmit="return confirm('هدف حذف شود؟')">
 <?php echo form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()); ?>
 <input type="hidden" name="start_date" value="<?= html_escape($start_date) ?>"><input type="hidden" name="end_date" value="<?= html_escape($end_date) ?>">
 <button class="btn btn-default btn-sm" type="submit">حذف</button></form><?php else: ?>—<?php endif; ?></td>
</tr><?php endforeach; ?>
<?php if(!$rows): ?><tr class="scr-goal-empty"><td colspan="8">برای این بازه هنوز هدفی ثبت نشده است.</td></tr><?php endif; ?>
</tbody></table><p id="scr-goal-filter-empty" style="display:none;text-align:center;padding:15px;color:#697c8c">موردی برای این تفکیک یافت نشد.</p></div></div>
</div>
<div class="scr-goal-tab-content <?= $goal_tab==='settings'?'active':'' ?>">
<?php if($goal_closed): ?>
<div class="alert alert-warning">این بازه بسته شده است؛ برای اصلاح اهداف ابتدا آن را از تب گزارش بازگشایی کنید.</div>
<?php else: ?>
<div class="scr-goal-panel"><h4>تعریف یا اصلاح هدف</h4>
<form method="post" action="<?= admin_url('sales_commercial_rules/save_monthly_goal') ?>">
<?php echo form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()); ?>
<div class="scr-goal-grid">
 <div><label>از تاریخ</label><input class="form-control scr-goal-form-date" type="date" name="start_date" value="<?= html_escape($start_date) ?>" required></div>
 <div><label>تا تاریخ</label><input class="form-control scr-goal-form-date" type="date" name="end_date" value="<?= html_escape($end_date) ?>" required></div>
 <div><label>نقش</label><select class="form-control" name="role"><option value="seller">کارشناس فروش</option><option value="manager">مدیر فروش</option></select></div>
 <div class="scr-multi"><label>کارمندان</label><button type="button" id="scr-goal-staff-toggle" class="form-control scr-multi-toggle" aria-expanded="false">انتخاب کارشناسان یا مدیران</button>
  <div class="scr-multi-menu" id="scr-goal-staff-menu"><div class="scr-multi-actions"><button type="button" id="scr-goal-select-all">انتخاب همه</button><button type="button" id="scr-goal-clear-all">لغو انتخاب</button></div>
  <input type="text" id="scr-goal-staff-search" class="form-control" placeholder="جستجوی کارمند" autocomplete="off">
  <?php foreach($staff as $s): ?><label class="scr-goal-staff-item"><input type="checkbox" name="staff_ids[]" value="<?= (int)$s['staffid'] ?>"><span><?= html_escape(trim($s['firstname'].' '.$s['lastname'])) ?></span></label><?php endforeach; ?>
  </div><div class="scr-goal-help" id="scr-goal-selected-count">هیچ فردی انتخاب نشده است</div></div>
 <div><label>نوع فروش</label><select class="form-control" name="sale_type_id"><option value="0">مجموع همه انواع فروش</option><?php foreach($types as $t): ?><option value="<?= (int)($t['legacy_id']?:$t['id']) ?>"><?= html_escape($t['name']) ?></option><?php endforeach; ?></select></div>
 <div><label>هدف (د.ع)</label><input class="form-control" name="target_amount" type="number" step="0.01" min="0" required></div>
</div><div style="margin-top:14px"><button class="scr-goal-btn" type="submit">ذخیره اهداف</button></div></form>
<p class="scr-goal-help">هر هدف بر مبنای ترکیب بازه شروع و پایان، نقش، کارمند و نوع فروش مستقل است. ثبت دوباره همین ترکیب، هدف قبلی را به‌روزرسانی می‌کند.</p>
</div>
<div class="scr-goal-panel"><h4>اهداف ثبت‌شده بازه <?= html_escape($period_title) ?></h4>
<div class="table-responsive"><table class="table table-bordered scr-goal-table"><thead><tr><th>شخص</th><th>نقش</th><th>نوع فروش</th><th>هدف (د.ع)</th><th>محقق‌شده (د.ع)</th><th>باقیمانده (د.ع)</th><th>تحقق</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($rows as $r): $pct=(float)($r['percent_achieved']??0); ?><tr>
<td><?= html_escape($r['staff_name']) ?></td><td><?= $r['role']==='manager'?'مدیر':'کارشناس' ?></td>
<td><?= (int)$r['sale_type_id']===0?'همه انواع فروش':html_escape($r['sale_type_name']?:'نامشخص') ?></td>
<td><?= number_format((float)$r['target_amount'],0) ?></td><td><?= number_format((float)$r['actual_amount'],0) ?></td><td><?= number_format(max(0,(float)$r['target_amount']-(float)$r['actual_amount']),0) ?></td>
<td><div class="scr-goal-progress"><span style="width:<?= min(100,max(0,$pct)) ?>%"></span></div><?= number_format($pct,1) ?>٪</td>
<td><form method="post" action="<?= admin_url('sales_commercial_rules/delete_monthly_goal/'.(int)$r['id']) ?>" onsubmit="return confirm('هدف حذف شود؟')">
<?php echo form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()); ?>
<input type="hidden" name="start_date" value="<?= html_escape($start_date) ?>"><input type="hidden" name="end_date" value="<?= html_escape($end_date) ?>">
<button class="btn btn-default btn-sm" type="submit">حذف</button></form></td>
</tr><?php endforeach; ?><?php if(!$rows): ?><tr class="scr-goal-empty"><td colspan="8">برای این بازه هدفی ثبت نشده است.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="scr-goal-panel"><h4>کپی اهداف یک بازه به بازه دیگر</h4>
<form method="post" action="<?= admin_url('sales_commercial_rules/copy_monthly_goals') ?>">
<?php echo form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()); ?>
<div class="scr-goal-grid">
<div><label>مبدأ از تاریخ</label><input class="form-control scr-goal-form-date" name="from_start_date" type="date" value="<?= html_escape($start_date) ?>" required></div>
<div><label>مبدأ تا تاریخ</label><input class="form-control scr-goal-form-date" name="from_end_date" type="date" value="<?= html_escape($end_date) ?>" required></div>
<div><label>مقصد از تاریخ</label><input class="form-control scr-goal-form-date" name="to_start_date" type="date" required></div>
<div><label>مقصد تا تاریخ</label><input class="form-control scr-goal-form-date" name="to_end_date" type="date" required></div>
</div><p class="scr-goal-help">اهداف موجود در بازه مقصد تغییر نمی‌کنند.</p>
<button class="scr-goal-btn" type="submit">کپی اهداف</button></form></div>
<div class="scr-goal-panel"><h4>ورود تجمیعی اهداف</h4>
<p class="scr-goal-help">قالب جدید CSV: start_date,end_date,role,staff_id,sale_type_id,target_amount — مقدار صفر برای مجموع انواع فروش؛ فایل‌های ماهانه قدیمی نیز پذیرفته می‌شوند.</p>
<a class="btn btn-default" download="sales_period_goals_template.csv" href="data:text/csv;charset=utf-8,start_date%2Cend_date%2Crole%2Cstaff_id%2Csale_type_id%2Ctarget_amount%0A2026-10-01%2C2026-10-31%2Cseller%2C1%2C0%2C7000000%0A">دریافت نمونه CSV</a>
<form method="post" enctype="multipart/form-data" action="<?= admin_url('sales_commercial_rules/import_monthly_goals') ?>" style="margin-top:12px">
<?php echo form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()); ?>
<input type="file" name="goals_file" accept=".csv" required><button class="scr-goal-btn" type="submit" style="margin-top:12px">ورود اطلاعات</button></form></div>
<?php endif; ?>
</div>
</div></div></div></div>
<script>
(function(){
 var toggle=document.getElementById('scr-goal-staff-toggle'),menu=document.getElementById('scr-goal-staff-menu');
 if(!toggle||!menu)return;
 var checks=Array.prototype.slice.call(menu.querySelectorAll('input[type=checkbox]')),
 count=document.getElementById('scr-goal-selected-count'),search=document.getElementById('scr-goal-staff-search');
 function update(){var n=checks.filter(function(x){return x.checked}).length;count.textContent=n?n+' نفر انتخاب شده‌اند':'هیچ فردی انتخاب نشده است';toggle.textContent=n?n+' نفر انتخاب شده‌اند':'انتخاب کارشناسان یا مدیران';}
 toggle.addEventListener('click',function(){var open=menu.classList.toggle('open');toggle.setAttribute('aria-expanded',open?'true':'false');});
 document.getElementById('scr-goal-select-all').addEventListener('click',function(){checks.forEach(function(x){if(x.closest('label').style.display!=='none')x.checked=true});update()});
 document.getElementById('scr-goal-clear-all').addEventListener('click',function(){checks.forEach(function(x){x.checked=false});update()});
 checks.forEach(function(x){x.addEventListener('change',update)});
 search.addEventListener('input',function(){var q=this.value.trim().toLowerCase();checks.forEach(function(x){var label=x.closest('label');label.style.display=label.textContent.toLowerCase().indexOf(q)>=0?'flex':'none'})});
 document.addEventListener('click',function(e){if(!menu.contains(e.target)&&e.target!==toggle){menu.classList.remove('open');toggle.setAttribute('aria-expanded','false')}});
 var form=toggle.closest('form');form.addEventListener('submit',function(e){if(!checks.some(function(x){return x.checked})){e.preventDefault();alert('حداقل یک کارمند انتخاب کنید')}});
 update();
})();
(function(){
 var role='seller',type='all',r=document.getElementById('scr-goal-role-tabs'),t=document.getElementById('scr-goal-type-tabs');if(!r||!t)return;
 function draw(){var count=0;document.querySelectorAll('.scr-goal-tab-content.active table.scr-goal-table tbody tr[data-goal-role]').forEach(function(tr){var visible=tr.getAttribute('data-goal-role')===role&&(type==='all'||tr.getAttribute('data-goal-type')===type);tr.style.display=visible?'':'none';if(visible)count++});var e=document.getElementById('scr-goal-filter-empty');if(e)e.style.display=count?'none':'block';}
 [[r,'role'],[t,'type']].forEach(function(x){x[0].addEventListener('click',function(e){var b=e.target.closest('button[data-'+x[1]+']');if(!b)return;x[0].querySelectorAll('button').forEach(function(z){z.classList.remove('active')});b.classList.add('active');if(x[1]==='role')role=b.dataset.role;else type=b.dataset.type;draw()})});draw();
})();
(function(){
 var groups=document.querySelectorAll('form');for(var i=0;i<groups.length;i++){
  var start=groups[i].querySelector('input[type="date"][name="start_date"],input[type="date"][name="from_start_date"]');
  var end=groups[i].querySelector('input[type="date"][name="end_date"],input[type="date"][name="from_end_date"]');
  if(!start||!end)continue;
  groups[i].addEventListener('submit',function(e){
   var s=this.querySelector('input[type="date"][name="start_date"],input[type="date"][name="from_start_date"]');
   var t=this.querySelector('input[type="date"][name="end_date"],input[type="date"][name="from_end_date"]');
   if(s&&t&&s.value&&t.value&&s.value>t.value){e.preventDefault();alert('تاریخ پایان نباید قبل از تاریخ شروع باشد');}
  });
 }
})();
</script>
<?php init_tail(); ?>
