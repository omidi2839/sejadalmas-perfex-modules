<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head(); ?>
<style>
/* Scoped orange design: no changes to other Perfex screens */
.scr-online-promotion>.panel-body{padding:28px 30px 32px;border-radius:14px}
.scr-online-promotion .scr-main-heading{font-size:21px;font-weight:600;color:#26354a;margin:0 0 22px;padding:0 0 16px;border-bottom:1px solid #f0e7e0;text-align:right}
.scr-online-promotion .scr-orange-tabs{margin-bottom:26px;border-bottom:1px solid #f0dfd0;display:flex;flex-wrap:wrap;gap:8px;padding:0}
.scr-online-promotion .scr-orange-tabs>li{float:none;margin-bottom:-1px}
.scr-online-promotion .scr-orange-tabs>li>a{border:1px solid transparent;border-radius:9px 9px 0 0;padding:12px 20px;color:#435168;font-weight:500;transition:background .15s,color .15s}
.scr-online-promotion .scr-orange-tabs>li.active>a,.scr-online-promotion .scr-orange-tabs>li.active>a:focus,.scr-online-promotion .scr-orange-tabs>li.active>a:hover{color:#ef740e;background:#fff7f0;border-color:#ffe0c5;border-bottom:2px solid #fa7a10}
.scr-online-promotion .scr-orange-tabs>li>a:hover{background:#fff8f2;color:#ed730b}
.scr-online-promotion h4:not(.scr-main-heading),.scr-online-promotion h5{color:#27374c;font-weight:600;margin:20px 0 16px}
.scr-online-promotion .table-responsive{border:1px solid #e6eaf0;border-radius:10px;margin:18px 0 26px;overflow-x:auto}
.scr-online-promotion .table{margin-bottom:0;background:#fff}
.scr-online-promotion .table>thead>tr>th{background:#fff2e7;color:#3c4858;border-bottom:1px solid #f4d7be;padding:13px 12px;white-space:nowrap;text-align:right}
.scr-online-promotion .table>tbody>tr>td{padding:12px;vertical-align:middle;border-color:#f0f1f4}
.scr-online-promotion .form-control{border:1px solid #d9dfe8;border-radius:9px;min-height:40px;box-shadow:0 1px 3px rgba(30,50,70,.04)}
.scr-online-promotion .form-control:focus{border-color:#ff963e;box-shadow:0 0 0 3px rgba(249,127,23,.11)}
.scr-online-promotion label{color:#344054;font-weight:500;margin-bottom:8px}
.scr-online-promotion .btn{border-radius:9px;font-weight:500;padding:9px 15px;transition:background .15s}
.scr-online-promotion .btn-primary{background:#fa8017;border-color:#fa8017;color:#fff}
.scr-online-promotion .btn-primary:hover,.scr-online-promotion .btn-primary:focus{background:#e96c05;border-color:#e96c05}
.scr-online-promotion .btn-default{background:#fff;border-color:#d8dee7;color:#394b62}
.scr-online-promotion .btn-default:hover{border-color:#f3a367;color:#e77715;background:#fffaf6}
.scr-online-promotion .btn-success{background:#fff1e3;border-color:#ffb879;color:#c85e00;color:#b65400}
.scr-online-promotion .btn-success:hover,.scr-online-promotion .btn-success:focus{background:#ffe6d0;border-color:#f99c48;color:#a74f00}
.scr-online-promotion .scr-comp-actions{display:flex;justify-content:flex-start;margin:12px 0 18px}
.scr-online-promotion .scr-comp-toggle{min-width:132px}
.scr-online-promotion hr{border-color:#f2e7df;margin:28px 0}
@media(max-width:767px){.scr-online-promotion>.panel-body{padding:16px}.scr-online-promotion .scr-orange-tabs>li>a{padding:10px 12px}}
.scr-status-section{background:#fff9f3;border:1px solid #ffe0c5;border-radius:12px;padding:18px;margin:20px 0}.scr-status-card{background:#fff;border:1px solid #e5e9ef;border-radius:12px;padding:20px;margin:12px 0}.scr-status-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.scr-status-badge{background:#e5f9ec;color:#12814a;border-radius:20px;padding:5px 12px}.scr-status-progress{background:#f0f1f4;height:9px;border-radius:15px;overflow:hidden;margin:8px 0}.scr-status-progress>div{height:100%;background:#fa8017}.scr-status-ranks{display:flex;gap:10px;flex-wrap:wrap}.scr-status-ranks>div{padding:10px 16px;background:#fff3e8;border:1px solid #ffd5b0;border-radius:9px;text-align:center}.scr-status-ranks strong{display:block;font-size:22px;color:#e97610}.scr-status-decision{border-top:1px solid #eceff3;margin-top:18px;padding-top:14px}.scr-status-decision form{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}.scr-status-decision input{flex:1;min-width:210px}.scr-stage-inline{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}.scr-inline-editor{background:#fff9f3;border:1px solid #ffd6b5;border-radius:12px;padding:20px;margin:20px 0}.scr-status-subtabs>li>a{font-size:14px}
</style>
<div id="wrapper"><div class="content"><div class="panel_s scr-online-promotion"><div class="panel-body">
<h4 class="scr-main-heading">قواعد ارتقاء آنلاین</h4>
<ul class="nav nav-tabs scr-orange-tabs">
<li class="<?= $tab==='categories'?'active':'' ?>"><a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=categories')?>">انواع بازاریابی</a></li>
<li class="<?= $tab==='rules'?'active':'' ?>"><a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=rules')?>">قواعد ارتقای کارشناس</a></li>
<li class="<?= $tab==='compensation'?'active':'' ?>"><a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=compensation')?>">حقوق ثابت و پاداش</a></li>
<li class="<?= $tab==='status'?'active':'' ?>"><a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=status')?>">وضعیت و ارتقای کارشناسان</a></li>
</ul>
<?php if($tab==='categories'): ?>
<h4 class="mbot20">انواع بازاریابی</h4>
<p class="text-muted">عنوان‌ها قابل تغییر هستند. شناسه داخلی هر نوع برای حفظ سوابق رتبه‌بندی ثابت می‌ماند.</p>
<?php foreach($categories as $category): ?>
<form method="post" action="<?=admin_url('sales_commercial_rules/marketing_category_save')?>" class="row" style="margin-bottom:12px">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<input type="hidden" name="id" value="<?=(int)$category['id']?>">
<div class="col-sm-8"><input class="form-control" type="text" name="name" required maxlength="150" value="<?=html_escape($category['name'])?>" <?=((int)$category['id']===0?'readonly':'')?>></div>
<div class="col-sm-4"><button type="submit" class="btn btn-default" <?=((int)$category['id']===0?'disabled':'')?>>ذخیره تغییرات</button></div>
</form>
<?php endforeach; ?>
<hr><h5>افزودن نوع بازاریابی</h5>
<form method="post" action="<?=admin_url('sales_commercial_rules/marketing_category_save')?>" class="row">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<input type="hidden" name="id" value="0">
<div class="col-sm-8"><input class="form-control" type="text" name="name" required maxlength="150" placeholder="عنوان نوع بازاریابی جدید"></div>
<div class="col-sm-4"><button type="submit" class="btn btn-primary">افزودن</button></div>
</form>
<?php elseif($tab==='rules'): ?>
<h4>قواعد ارتقای کارشناسان فروش آنلاین</h4>
<p class="text-muted">این بخش شرایط ارتقا را تعریف می‌کند؛ اعمال ارتقا و پرداخت پاداش نیازمند تأیید مدیر و فرآیند اجرایی جداگانه است.</p>
<?php if(!$schema_ok): ?><div class="alert alert-danger">جدول قواعد ارتقا ایجاد نشد؛ دسترسی پایگاه داده را بررسی کنید.</div><?php endif; ?>
<?php $labels=[];foreach($categories as $cat)$labels[$cat['code']]=$cat['name']; ?>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>از</th><th>به</th><th>معیار</th><th>هدف</th><th>بازه (روز)</th><th>تأیید مدیر</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($rules as $r): ?><tr><td><?=html_escape(isset($labels[$r['from_category_code']])?$labels[$r['from_category_code']]:$r['from_category_code'])?></td><td><?=html_escape(isset($labels[$r['to_category_code']])?$labels[$r['to_category_code']]:$r['to_category_code'])?></td><td><?=$r['criterion']==='invoice_count'?'تعداد فاکتور':'تعداد رتبه'?></td><td><?=(int)$r['target_count']?><?=$r['criterion']==='rank_count'?' × '.html_escape($r['target_rank']).(!empty($r['consecutive'])?' (متوالی)':''):''?></td><td><?=(int)$r['period_days']?></td><td><?=!empty($r['requires_manager_approval'])?'بله':'خیر'?></td><td><button class="btn btn-default btn-sm" type="button" onclick='scrEditPromotion(<?=json_encode($r,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>)'>ویرایش</button> <form style="display:inline" method="post" action="<?=admin_url('sales_commercial_rules/promotion_rule_delete')?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn btn-danger btn-sm" onclick="return confirm('قاعده حذف شود؟')">حذف</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<hr><h4>تعریف / ویرایش قاعده</h4>
<form method="post" action="<?=admin_url('sales_commercial_rules/promotion_rule_save')?>" id="scr-promotion-form">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0">
<div class="row"><div class="col-md-6 form-group"><label>از نوع بازاریابی</label><select class="form-control" name="from_category_code" required><?php foreach($categories as $cat): ?><option value="<?=html_escape($cat['code'])?>"><?=html_escape($cat['name'])?></option><?php endforeach; ?></select></div>
<div class="col-md-6 form-group"><label>به نوع بازاریابی</label><select class="form-control" name="to_category_code" required><?php foreach($categories as $cat): ?><option value="<?=html_escape($cat['code'])?>"><?=html_escape($cat['name'])?></option><?php endforeach; ?></select></div></div>
<div class="row"><div class="col-md-4 form-group"><label>معیار</label><select class="form-control" name="criterion" onchange="scrCriterion(this.value)"><option value="invoice_count">تعداد فاکتور</option><option value="rank_count">تعداد رتبه</option></select></div>
<div class="col-md-4 form-group"><label>تعداد هدف</label><input class="form-control" type="number" name="target_count" min="1" required value="10"></div><div class="col-md-4 form-group"><label>بازه زمانی (روز)</label><input class="form-control" type="number" name="period_days" min="1" required value="28"></div></div>
<div id="scr-rank-fields" style="display:none" class="row"><div class="col-md-4 form-group"><label>رتبه هدف</label><input class="form-control" name="target_rank" value="A"></div><div class="col-md-4 form-group"><label><input type="checkbox" name="consecutive" value="1"> دوره‌های متوالی</label></div></div>
<div class="row" style="margin:12px 0 18px"><div class="col-md-6" style="padding-right:0"><input type="hidden" name="requires_manager_approval" value="1"><button type="button" class="btn btn-success scr-promo-toggle" data-field="requires_manager_approval" aria-pressed="true" onclick="scrTogglePromotion(this)">✓ ارتقا با تأیید مدیر: بله</button></div><div class="col-md-6"><input type="hidden" name="active" value="1"><button type="button" class="btn btn-success scr-promo-toggle" data-field="active" aria-pressed="true" onclick="scrTogglePromotion(this)">✓ فعال: بله</button></div></div>
<button class="btn btn-primary" type="submit" <?=$schema_ok?'':'disabled'?>>ذخیره قاعده</button> <button class="btn btn-default" type="button" onclick="scrResetPromotion()">قاعده جدید</button></form>
<?php elseif($tab==='status'): ?>
<?php
$subtab = (string)($this->input->get('section') ?: 'monitor');
if (!in_array($subtab, ['monitor','assignment'], true)) $subtab='monitor';
$stageLabels=['normal'=>'آنلاین عادی'];
foreach($stage_categories as $cat) $stageLabels[$cat['code']]=$cat['name'];
$eligibleStaff=[];
foreach($stage_staff as $person){
  $id=(int)$person['staffid'];
  $name=trim($person['firstname'].' '.$person['lastname']);
  // Only online sales personnel, or those explicitly assigned to an online marketing category.
  if(preg_match('/آنلاین|مرکز\s*تماس|مشتریان/u',$name) || isset($stage_assignments[$id])) $eligibleStaff[]=$person;
}
?>
<ul class="nav nav-tabs scr-orange-tabs scr-status-subtabs" style="margin:18px 0 24px">
<li class="<?=$subtab==='monitor'?'active':''?>"><a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=status&section=monitor')?>">رصد عملکرد و ارتقا</a></li>
<li class="<?=$subtab==='assignment'?'active':''?>"><a href="<?=admin_url('sales_commercial_rules/online_promotion?tab=status&section=assignment')?>">تعیین وضعیت کارشناسان آنلاین</a></li>
</ul>
<?php if($subtab==='assignment'): ?>
<h4>تعیین وضعیت فعلی کارشناسان آنلاین</h4>
<p class="text-muted">کارشناسان موجود را مستقیماً به مرحله واقعی‌شان نسبت دهید؛ نیازی نیست از مرحله مرکز تماس شروع کنند. تاریخ مؤثر، سوابق محاسبات گذشته را حفظ می‌کند.</p>
<form method="post" action="<?=admin_url('sales_commercial_rules/online_staff_stage_save')?>" class="scr-stage-assignment">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<div class="row"><div class="col-md-4 form-group"><label>کارشناس</label><select class="form-control" name="staff_id" required><option value="">انتخاب کارشناس</option><?php foreach($eligibleStaff as $person): ?><option value="<?=(int)$person['staffid']?>"><?=html_escape(trim($person['firstname'].' '.$person['lastname']))?></option><?php endforeach; ?></select></div>
<div class="col-md-4 form-group"><label>مرحله فعلی / نوع بازاریابی</label><select class="form-control" name="category_code" required><option value="normal">آنلاین عادی</option><?php foreach($stage_categories as $cat): ?><option value="<?=html_escape($cat['code'])?>"><?=html_escape($cat['name'])?></option><?php endforeach; ?></select></div>
<div class="col-md-4 form-group"><label>تاریخ مؤثر</label><input class="form-control" type="date" name="effective_date" value="<?=date('Y-m-d')?>" required></div></div>
<div class="row"><div class="col-md-9 form-group"><label>توضیحات تغییر وضعیت</label><input class="form-control" name="note" maxlength="500" placeholder="دلیل تعیین مرحله یا ارتقا"></div><div class="col-md-3" style="padding-top:25px"><button class="btn btn-primary" type="submit">ثبت وضعیت کارشناس</button></div></div></form>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>کارشناس</th><th>وضعیت فعلی</th><th>تاریخ مؤثر</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($eligibleStaff as $person):$a=$stage_assignments[(int)$person['staffid']]??null; ?>
<tr><td><?=html_escape(trim($person['firstname'].' '.$person['lastname']))?></td><td><?=html_escape($a?($stageLabels[$a['category_code']]??$a['category_code']):'تعیین نشده')?></td><td><?=html_escape($a['effective_date']??'—')?></td><td><button type="button" class="btn btn-default btn-sm" onclick="scrSelectStage(<?=(int)$person['staffid']?>,<?=html_escape(json_encode($a['category_code']??'normal'))?>)">تعیین / تغییر وضعیت</button></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php else: ?>
<h4>وضعیت و ارتقای کارشناسان</h4><p><a href="<?=admin_url('sales_commercial_rules/online_invoice_attributions')?>">تطبیق فاکتورهای فاقد شناسه فروشنده</a></p>
<p class="text-muted">شرایط ارزیابی فقط از قواعد ارتقای فعال خوانده می‌شوند؛ پس از تحقق شرط و تأیید مدیر، مرحله کارشناس از تاریخ تأیید به‌روزرسانی می‌شود.</p><?php if(empty($status_rows)): ?><div class="alert alert-info">هیچ وضعیت ارتقایی برای نمایش وجود ندارد. ابتدا در تب «قواعد ارتقای کارشناس» قاعده فعال تعریف کنید.</div><?php endif; ?>
<?php foreach(['call','call_online'] as $stage):
$title='آنلاین ('.($stageLabels[$stage]??$stage).')'; ?>
<div class="scr-status-section"><h4><?=html_escape($title)?></h4>
<?php $found=false;foreach($status_rows as $r):if($r['stage']!==$stage)continue;$found=true; ?>
<div class="scr-status-card"><div class="scr-status-heading"><strong><?=html_escape($r['name'])?></strong><span class="scr-status-badge"><?=$r['eligible']?'واجد شرایط ارتقا':'در حال ارزیابی'?></span></div>
<?php if($stage==='call'): ?><div class="row"><div class="col-sm-6"><p>زمان سپری‌شده: <?=(int)$r['elapsed']?> از <?=(int)$r['days']?> روز</p><div class="scr-status-progress"><div style="width:<?=min(100,round(100*$r['elapsed']/max(1,$r['days'])))?>%"></div></div></div><div class="col-sm-6"><p>فاکتورها: <?=(int)$r['count']?> از <?=(int)$r['target']?></p><div class="scr-status-progress"><div style="width:<?=min(100,round(100*$r['count']/max(1,$r['target'])))?>%"></div></div></div></div><p>حقوق ثابت: <?=number_format($r['salary'])?> دینار | پاداش قابل پرداخت: <?=number_format($r['bonus'])?> دینار</p>
<?php else: ?><div class="scr-status-ranks"><?php foreach(array_reverse($r['ranks']) as $rank): ?><div><small><?=html_escape($rank['name'])?></small><strong><?=html_escape($rank['rank'])?></strong></div><?php endforeach; ?><?php if(!$r['ranks']): ?>نتیجه محاسبه‌شده‌ای یافت نشد.<?php endif; ?></div><p>شرط ارتقا: <?= (int)$r['target_count'] ?> رتبه <?=html_escape($r['target_rank'])?> <?=!empty($r['consecutive'])?'متوالی':''?></p><?php endif; ?>
<div class="scr-status-decision">
<div class="scr-stage-inline">
<strong>وضعیت فعلی:</strong> <?=html_escape($stageLabels[$stage_assignments[(int)$r['staff_id']]['category_code']??$stage]??$stage)?>
<button type="button" class="btn btn-default btn-sm" onclick="scrOpenStageEditor(this,<?=(int)$r['staff_id']?>,<?=html_escape(json_encode($stage_assignments[(int)$r['staff_id']]['category_code']??$stage))?>)">تغییر وضعیت کارشناس</button>
</div>
آخرین تصمیم مدیر: <strong><?=empty($r['decision'])?'در انتظار بررسی':($r['decision']['decision']==='approve'?'تأیید ارتقا':'عدم تأیید')?></strong>
<form method="post" action="<?=admin_url('sales_commercial_rules/online_status_decision')?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="staff_id" value="<?=(int)$r['staff_id']?>"><input type="hidden" name="stage" value="<?=html_escape($stage)?>"><input class="form-control" name="reason" placeholder="توضیحات مدیر (برای عدم تأیید الزامی است)"><button class="btn btn-primary" name="decision" value="approve" <?=$r['eligible']?'':'disabled'?>>تأیید ارتقا</button><button class="btn btn-default" name="decision" value="reject">عدم تأیید</button></form></div></div>
<?php endforeach;if(!$found): ?><p class="text-muted">کارشناسی در این مرحله یافت نشد.</p><?php endif; ?></div>
<?php endforeach; ?>
<?php endif; ?>
<div id="scr-stage-inline-editor" class="scr-inline-editor" style="display:none">
<h4>تغییر وضعیت کارشناس</h4>
<form method="post" action="<?=admin_url('sales_commercial_rules/online_staff_stage_save')?>" id="scr-inline-stage-form">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<input type="hidden" name="staff_id" value="">
<div class="row"><div class="col-md-5 form-group"><label>وضعیت جدید</label><select class="form-control" name="category_code"><option value="normal">آنلاین عادی</option><?php foreach($stage_categories as $cat): ?><option value="<?=html_escape($cat['code'])?>"><?=html_escape($cat['name'])?></option><?php endforeach; ?></select></div>
<div class="col-md-4 form-group"><label>تاریخ مؤثر</label><input type="date" class="form-control" name="effective_date" value="<?=date('Y-m-d')?>" required></div>
<div class="col-md-3 form-group"><label>عملیات</label><button type="submit" class="btn btn-primary form-control">ثبت تغییر وضعیت</button></div></div>
<div class="form-group"><label>دلیل تغییر</label><input class="form-control" name="note" maxlength="500" placeholder="دلیل تغییر وضعیت"></div>
</form></div>
<script>function scrOpenStageEditor(button,id,code){var e=document.getElementById('scr-stage-inline-editor');var f=document.getElementById('scr-inline-stage-form');f.elements.staff_id.value=id;f.elements.category_code.value=code;button.closest('.scr-status-card').appendChild(e);e.style.display='block';e.scrollIntoView({behavior:'smooth',block:'center'});}</script>
<?php else: ?>
<h4>حقوق ثابت و پاداش کارشناسان مرکز تماس</h4>
<p class="text-muted">این تب فقط حقوق ثابت و مبلغ پاداش را ثبت می‌کند. هدف فاکتور و بازه ارزیابی صرفاً در تب «قواعد ارتقای کارشناس» تعریف می‌شود.</p>
<?php if(!$schema_ok): ?><div class="alert alert-danger">جدول تنظیمات پرداخت ایجاد نشد.</div><?php endif; ?>
<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>کارشناس</th><th>شروع دوره</th><th>حقوق ثابت</th><th>پاداش</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($compensations as $c): ?><tr><td><?=html_escape(trim(($c['firstname']??'').' '.($c['lastname']??'')))?></td><td><?=html_escape($c['period_start'])?></td><td><?=number_format((float)$c['fixed_salary'])?></td><td><?=number_format((float)$c['target_bonus'])?></td><td><?=!empty($c['active'])?'فعال':'غیرفعال'?></td><td><button type="button" class="btn btn-default btn-sm" onclick='scrEditCompensation(<?=json_encode($c,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>)'>ویرایش</button> <form method="post" style="display:inline" action="<?=admin_url('sales_commercial_rules/online_compensation_delete')?>" onsubmit="return confirm('تنظیم حقوق و پاداش حذف شود؟')"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="<?=(int)$c['id']?>"><button type="submit" class="btn btn-danger btn-sm">حذف</button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<hr><h4>تعریف / ویرایش حقوق و پاداش</h4>
<form method="post" action="<?=admin_url('sales_commercial_rules/online_compensation_save')?>" id="scr-comp-form">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0">
<div class="row"><div class="col-md-6 form-group"><label>کارشناس</label><select class="form-control" name="staff_id" required><option value="">انتخاب کارشناس</option><?php foreach($staff as $person): ?><option value="<?=(int)$person['staffid']?>"><?=html_escape(trim($person['firstname'].' '.$person['lastname']))?></option><?php endforeach; ?></select></div>
<div class="col-md-6 form-group"><label>تاریخ شروع دوره</label><input type="date" class="form-control" name="period_start" required value="<?=date('Y-m-d')?>"></div></div>
<div class="row"><div class="col-md-6 form-group"><label>حقوق ثابت (دینار)</label><input type="number" class="form-control" name="fixed_salary" min="0" step="0.01" value="600000" required></div><div class="col-md-6 form-group"><label>پاداش عملکرد (دینار)</label><input type="number" class="form-control" name="target_bonus" min="0" step="0.01" value="150000" required></div></div>
<div class="scr-comp-actions"><input type="hidden" name="active" value="1"><button type="button" class="btn btn-success scr-comp-toggle" aria-pressed="true" onclick="scrToggleCompensation(this)">✓ فعال: بله</button></div>
<button type="submit" class="btn btn-primary" <?=$schema_ok?'':'disabled'?>>ذخیره تنظیمات</button> <button type="button" class="btn btn-default" onclick="scrResetCompensation()">تنظیم جدید</button>
</form>
<script>
function scrSetCompensationActive(value){var f=document.getElementById('scr-comp-form');var on=Number(value)===1;f.elements['active'].value=on?'1':'0';var b=f.querySelector('.scr-comp-toggle');if(b){b.classList.toggle('btn-success',on);b.classList.toggle('btn-default',!on);b.setAttribute('aria-pressed',on?'true':'false');b.textContent=(on?'✓ ':'')+'فعال: '+(on?'بله':'خیر');}}
function scrToggleCompensation(){var f=document.getElementById('scr-comp-form');scrSetCompensationActive(f.elements['active'].value==='1'?0:1);}
function scrEditCompensation(c){var f=document.getElementById('scr-comp-form');['id','staff_id','period_start','fixed_salary','target_bonus'].forEach(function(k){f.elements[k].value=c[k];});scrSetCompensationActive(c.active);f.scrollIntoView({behavior:'smooth'});}
function scrResetCompensation(){var f=document.getElementById('scr-comp-form');f.reset();f.elements.id.value='0';scrSetCompensationActive(1);}
</script>
<?php endif; ?>
</div></div></div></div>
<script>function scrSelectStage(staff,code){var f=document.querySelector('.scr-stage-assignment');if(!f)return;f.elements.staff_id.value=staff;f.elements.category_code.value=code;f.scrollIntoView({behavior:'smooth',block:'center'});}</script>
<?php if($tab==='rules'): ?><script>function scrCriterion(v){document.getElementById('scr-rank-fields').style.display=v==='rank_count'?'block':'none';}function scrSetPromotionToggle(field,value){var f=document.getElementById('scr-promotion-form');var v=Number(value)===1;f.elements[field].value=v?'1':'0';var b=f.querySelector('.scr-promo-toggle[data-field="'+field+'"]');if(b){b.className='btn scr-promo-toggle '+(v?'btn-success':'btn-default');b.setAttribute('aria-pressed',v?'true':'false');b.textContent=(v?'✓ ':'')+(field==='active'?'فعال':'ارتقا با تأیید مدیر')+': '+(v?'بله':'خیر');}}function scrTogglePromotion(button){var field=button.getAttribute('data-field');var f=document.getElementById('scr-promotion-form');scrSetPromotionToggle(field,f.elements[field].value==='1'?0:1);}function scrResetPromotion(){var f=document.getElementById('scr-promotion-form');f.reset();f.elements.id.value='0';scrSetPromotionToggle('active',1);scrSetPromotionToggle('requires_manager_approval',1);scrCriterion('invoice_count');}function scrEditPromotion(r){var f=document.getElementById('scr-promotion-form');['id','from_category_code','to_category_code','criterion','target_count','period_days','target_rank'].forEach(function(k){if(f.elements[k])f.elements[k].value=r[k]||'';});f.elements.consecutive.checked=!!Number(r.consecutive);scrSetPromotionToggle('requires_manager_approval',r.requires_manager_approval);scrSetPromotionToggle('active',r.active);scrCriterion(r.criterion);f.scrollIntoView({behavior:'smooth'});}</script>
<?php endif; ?>
<?php init_tail(); ?>
