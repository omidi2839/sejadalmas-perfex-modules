<?php defined('BASEPATH') or exit('No direct script access allowed'); init_head();?>
<style id="scr-orange-theme">
:root{--scr-orange:#f47a16;--scr-orange-soft:#fff4e9;--scr-orange-border:#f8d9be}
#wrapper .content .panel_s{border-radius:13px;overflow:hidden;border:1px solid #f4e4d5;box-shadow:0 2px 12px rgba(100,60,25,.04)}
#wrapper .content .btn-primary{background:#f47a16!important;border-color:#f47a16!important;color:white!important;font-weight:400}
#wrapper .content .btn-primary:hover{background:#db690d!important}
#wrapper .content .nav-tabs>li.active>a{border-bottom:2px solid #f47a16!important;color:#db690d!important}
#wrapper .content .nav-tabs>li>a:hover{color:#f47a16}
#wrapper .content .table>thead>tr>th{background:#ffe2c6;color:#75421d;font-weight:500}
#wrapper .content .form-control:focus{border-color:#f47a16;box-shadow:0 0 0 2px rgba(244,122,22,.09)}
.scr-period-dates{display:flex!important;gap:9px;align-items:center;justify-content:flex-start;direction:rtl;white-space:nowrap}
.scr-period-dates .scr-date{direction:ltr;unicode-bidi:isolate;display:inline-block}
.scr-month-toggle[aria-expanded=true]{background:#fff5ec!important;border-color:#f7d4b7!important}
.scr-month-toggle .fa-calendar-o{color:#f47a16}
.scr-period-actions>a{border-color:#f47a16;color:#d9650a}
.scr-rank-panel .scr-rank-tools{background:#fffaf5;border:1px solid #f7e2d0;border-radius:10px;padding:12px}
.scr-step-filters{display:flex;gap:12px;align-items:end;flex-wrap:wrap;padding:12px;background:#fffaf5;border:1px solid #f7dfca;border-radius:11px;margin:12px 0}
.scr-step-filters label{font-size:12px;color:#66574c;margin:0;min-width:170px}
.scr-step-filters select{margin-top:6px}
.scr-step-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin:14px 0;direction:rtl}
.scr-step-card{border:1px solid #f2dfcc;border-radius:13px;overflow:hidden;background:#fff;box-shadow:0 2px 8px rgba(80,50,25,.04);min-width:0}
.scr-step-card-head{display:flex;justify-content:space-between;align-items:center;padding:13px;background:#fff4e9;border-bottom:1px solid #f2dfcc}
.scr-step-card:nth-child(3n+1) .scr-step-card-head{background:#eaf8f0}
.scr-step-card:nth-child(3n+3) .scr-step-card-head{background:#edf6ff}
.scr-step-card-title{font-size:16px;font-weight:600;color:#263142}
.scr-step-card-letter{font-size:21px;color:#e87813;font-weight:600}
.scr-step-card-body{padding:13px}
.scr-step-card-line{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid #f3f0ed;font-size:12px}
.scr-step-card-line:last-child{border:0}
.scr-step-card-line span:first-child{color:#766b62}
.scr-step-card-line span:last-child{font-weight:500;text-align:left;direction:ltr;unicode-bidi:isolate}
.scr-step-card-actions{display:flex;gap:8px;padding:10px 13px 13px}
.scr-step-card-actions .btn{font-weight:400!important;border-radius:7px}
.scr-step-card-actions .scr-step-edit{border-color:#f47a16;color:#d9650a}
.scr-step-empty{padding:30px;text-align:center;color:#8d8074}
@media(max-width:1100px){.scr-step-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){.scr-step-cards{grid-template-columns:1fr}.scr-step-filters label{flex:1;min-width:120px}}
</style>
<?php
$tabs=['policies'=>'سیاست‌های پورسانت','staff'=>'انتساب کارکنان','managers'=>'مدیران و زیرمجموعه‌ها','ledger'=>'دفتر کمیسیون','receipts'=>'رسیدهای پورسانت','reports'=>'گزارش‌ها','reversals'=>'پورسانت‌های برگشتی'];
$esc=function($v){return html_escape((string)$v);}; $pct=function($v){return rtrim(rtrim(number_format((float)$v,3,'.',''),'0'),'.');}; $money=function($v){return number_format((float)$v,0,'.',',').' د.ع';}; ?>
<div id="wrapper"><div class="content"><h4>پورسانت و رتبه‌بندی / کمیسیون</h4><div class="panel_s"><div class="panel-body">
<ul class="nav nav-tabs" style="margin-bottom:20px"><?php foreach($tabs as $k=>$label):?><li class="<?= $tab===$k?'active':'' ?>"><a href="<?=admin_url('sales_commercial_rules/commission_center?tab='.$k)?>"><?=$label?></a></li><?php endforeach?></ul>
<?php if($tab==='reversals'): ?>
<h4>پورسانت‌های لغوشده و برگشتی</h4><p class="text-muted">سوابق برگشت پورسانت بر اساس محاسبات ثبت‌شده سامانه؛ نمایش این بخش هیچ محاسبه جدیدی ایجاد نمی‌کند.</p>
<div class="table-responsive"><table class="table dt-table"><thead><tr><th>فاکتور</th><th>دریافت‌کننده</th><th>نقش</th><th>تاریخ برگشت</th><th>مبلغ پایه برگشتی</th><th>تعدیل رتبه</th><th>جمع برگشتی</th><th>وضعیت</th></tr></thead><tbody>
<?php foreach($reversals as $r): ?><tr><td><?= $esc($r['invoice_id']) ?></td><td><?= $esc($r['staff_name']?:$r['staff_id']) ?></td><td><?= $r['ranking_role']==='marketer'?'مدیر / معرف':'کارشناس' ?></td><td><?= $esc($r['cancel_date']) ?></td><td><?= $money($r['base_commission_reversal']) ?></td><td><?= $money($r['rank_adjustment_reversal']) ?></td><td class="text-danger"><?= $money($r['total_reversal']) ?></td><td><?= $r['status']==='applied'?'اعمال‌شده':'در انتظار اعمال' ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php elseif($tab==='policies'): ?>
<h4>سیاست‌های محاسبه پورسانت</h4>
<p class="text-muted">هر سیاست بر اساس گروه کالا، نوع فروش و بازه اعتبار میلادی تعریف می‌شود. قواعد موجود با تمام درصدهای قبلی حفظ شده‌اند.</p>
<button class="btn btn-primary" type="button" data-toggle="modal" data-target="#scrPolicyModal" onclick="scrPolicyNew()"><i class="fa fa-plus"></i> افزودن سیاست</button>
<hr><table class="table dt-table"><thead><tr><th>گروه کالا</th><th>نوع فروش</th><th>از تاریخ</th><th>تا تاریخ</th><th>درصد کارشناس</th><th>درصد بازاریاب</th><th>عملیات</th></tr></thead><tbody>
<?php foreach($rules as $r):?><tr><td><?=$esc($r['group_name']??'-')?></td><td><?=$esc($r['sale_type_name']??'-')?></td><td><?=$esc($r['valid_from']??'1000-01-01')?></td><td><?=$esc($r['valid_to']??'9999-12-31')?></td><td><?=$esc($pct($r['seller_percent']))?>%</td><td><?=$esc($pct($r['marketer_percent']))?>%</td><td><button class="btn btn-default btn-sm scr-policy-edit" type="button" onclick="scrPolicyEdit(this)" data-id="<?=(int)$r['id']?>" data-group="<?=(int)$r['group_id']?>" data-type="<?=(int)$r['sale_type_id']?>" data-from="<?=$esc($r['valid_from']??'1000-01-01')?>" data-to="<?=$esc($r['valid_to']??'9999-12-31')?>" data-seller="<?=$esc($r['seller_percent'])?>" data-marketer="<?=$esc($r['marketer_percent'])?>"><i class="fa fa-pencil"></i> ویرایش</button></td></tr><?php endforeach?></tbody></table>
<div class="modal fade" id="scrPolicyModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post" action="<?=admin_url('sales_commercial_rules/commissions')?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4>سیاست محاسبه پورسانت</h4></div><div class="modal-body">
<label>گروه کالا</label><select name="group_id" class="form-control" required><option value="">انتخاب کنید</option><?php foreach($groups as $g):?><option value="<?=(int)$g['id']?>"><?=$esc($g['name'])?></option><?php endforeach?></select>
<label style="margin-top:12px">نوع فروش</label><select name="sale_type_id" class="form-control" required><option value="">انتخاب کنید</option><?php foreach($types as $t):?><option value="<?=(int)($t['legacy_id']?:$t['id'])?>"><?=$esc($t['name'])?></option><?php endforeach?></select>
<div class="row" style="margin-top:12px"><div class="col-md-6"><?=render_input('valid_from','از تاریخ میلادی','','date',['required'=>true])?></div><div class="col-md-6"><?=render_input('valid_to','تا تاریخ میلادی','','date',['required'=>true])?></div></div>
<div class="row"><div class="col-md-6"><?=render_input('seller_percent','درصد پورسانت کارشناس','','number',['step'=>'0.01','min'=>0,'max'=>100,'required'=>true])?></div><div class="col-md-6"><?=render_input('marketer_percent','درصد پورسانت بازاریاب','','number',['step'=>'0.01','min'=>0,'max'=>100,'required'=>true])?></div></div>
</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">انصراف</button><button class="btn btn-primary" type="submit">ذخیره سیاست</button></div></form></div></div></div>

<?php elseif($tab==='managers'): ?>
<h4>انتساب مدیران به فروشندگان</h4>
<div class="alert alert-info">برای انتقال ارتباطات و درصد مشارکت ثبت‌شده در ماژول کمیسیون قدیمی، از دکمه زیر استفاده کنید. داده‌های فعلی حذف یا بازنویسی نمی‌شوند.</div>
<form method="post" action="<?=admin_url('sales_commercial_rules/commission_import_legacy_managers')?>" onsubmit="return confirm('ارتباطات قدیمی مدیران و فروشندگان بدون بازنویسی موارد موجود منتقل شوند؟');">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<button type="submit" class="btn btn-default"><i class="fa fa-download"></i> انتقال اطلاعات قبلی مدیران و درصد مشارکت</button></form><hr>

<p>مدیر، فروشنده زیرمجموعه و درصد مشارکت مدیر در پورسانت همان فروشنده را در این بخش تعیین کنید.</p>
<form method="post" action="<?=admin_url('sales_commercial_rules/commission_manager_save')?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0"><div class="row"><div class="col-md-4"><label>مدیر</label><select name="manager_staff_id" class="form-control" required><option value="">انتخاب مدیر</option><?php foreach($staff as $person):?><option value="<?=(int)$person['staffid']?>"><?=$esc(trim($person['firstname'].' '.$person['lastname']))?></option><?php endforeach?></select></div><div class="col-md-4"><label>فروشنده زیرمجموعه</label><select name="seller_staff_id" class="form-control" required><option value="">انتخاب فروشنده</option><?php foreach($staff as $person):?><option value="<?=(int)$person['staffid']?>"><?=$esc(trim($person['firstname'].' '.$person['lastname']))?></option><?php endforeach?></select></div><div class="col-md-2"><label>درصد مشارکت مدیر</label><input name="participation_percent" class="form-control" type="number" min="0" max="100" step="0.01" required></div><div class="col-md-2" style="padding-top:24px"><button class="btn btn-primary" type="submit">ذخیره ارتباط</button></div></div></form>
<hr><table class="table dt-table"><thead><tr><th>مدیر</th><th>فروشنده زیرمجموعه</th><th>درصد مشارکت مدیر</th><th>عملیات</th></tr></thead><tbody><?php foreach($hierarchy_rows as $h):?><tr><td><?=$esc($h['manager_name'])?></td><td><?=$esc($h['seller_name'])?></td><td><?=$esc($pct($h['participation_percent']??0))?>%</td><td><button type="button" class="btn btn-default btn-sm" onclick="scrManagerEdit(<?=(int)$h['id']?>,<?=(int)$h['manager_staff_id']?>,<?=(int)$h['seller_staff_id']?>,<?=(float)($h['participation_percent']??0)?>)">ویرایش</button></td></tr><?php endforeach?></tbody></table>
<script>function scrManagerEdit(id,manager,seller,pct){var f=jQuery('form[action$="commission_manager_save"]');f.find('[name=id]').val(id);f.find('[name=manager_staff_id]').val(manager);f.find('[name=seller_staff_id]').val(seller);f.find('[name=participation_percent]').val(pct);window.scrollTo({top:0,behavior:'smooth'});}</script>
<?php elseif($tab==='staff'): ?>
<h4>انتساب سیاست‌های پورسانت به کارکنان</h4>
<p class="text-muted">یک کارمند و یک یا چند سیاست را انتخاب کنید. بازه زمانی هر انتساب به‌صورت خودکار از سیاست مربوط گرفته می‌شود؛ تاریخ جداگانه وارد نمی‌شود.</p>
<form method="post" action="<?=admin_url('sales_commercial_rules/commission_center_save')?>">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<input type="hidden" name="kind" value="assignment">
<div class="row"><div class="col-md-4"><label>کارمند</label><select name="staff_id" class="form-control" required><option value="">انتخاب کارمند</option><?php foreach($staff as $person):?><option value="<?=(int)($person['staffid']??$person['id']??0)?>"><?=$esc(trim(($person['firstname']??'').' '.($person['lastname']??'')))?></option><?php endforeach?></select></div>
<div class="col-md-8"><label>سیاست‌های پورسانت (انتخاب با تیک)</label>
<div class="scr-policy-picker">
 <div class="scr-policy-toolbar"><input id="scrPolicySearch" class="form-control" type="search" placeholder="جستجوی گروه کالا یا نوع فروش…" oninput="scrFilterPolicies(this.value)"><span id="scrPolicySelectedCount" class="text-muted">۰ سیاست انتخاب‌شده</span><button type="button" class="btn btn-default btn-sm" onclick="scrToggleVisiblePolicies(true)">انتخاب موارد نمایشی</button><button type="button" class="btn btn-default btn-sm" onclick="scrToggleVisiblePolicies(false)">پاک‌کردن انتخاب</button></div>
 <div class="scr-policy-options" id="scrPolicyOptions">
 <?php foreach($policies as $policy):?><label class="scr-policy-option"><input type="checkbox" name="rule_ids[]" value="<?=(int)$policy['id']?>" onchange="scrUpdatePolicyCount()"><span class="scr-policy-desc"><strong><?=$esc($policy['group_name']??'گروه نامشخص')?></strong><span><?=$esc($policy['sale_type_name']??'نوع فروش نامشخص')?></span><small><?=$esc(($policy['valid_from']?:'1000-01-01').' تا '.($policy['valid_to']?:'9999-12-31'))?></small></span><span class="scr-policy-rates">کارشناس <?=$esc($pct($policy['seller_percent']??0))?>٪ | بازاریاب <?=$esc($pct($policy['marketer_percent']??0))?>٪</span></label><?php endforeach?>
 </div>
</div><small class="text-muted">با علامت‌زدن هر ردیف، چند سیاست را هم‌زمان انتخاب کنید.</small></div></div>
<button class="btn btn-primary" type="submit" style="margin-top:14px">ثبت سیاست‌های انتخاب‌شده</button></form>
<hr><table class="table dt-table"><thead><tr><th>کارمند</th><th>گروه کالا</th><th>نوع فروش</th><th>درصد کارشناس</th><th>درصد بازاریاب</th><th>از تاریخ</th><th>تا تاریخ</th><th>عملیات</th></tr></thead><tbody><?php foreach($assignments as $r):?><tr><td><?=$esc($r['staff_name'])?></td><td><?=$esc($r['group_name']??'-')?></td><td><?=$esc($r['sale_type_name']??'-')?></td><td><?=$esc($pct($r['seller_percent']??0))?>٪</td><td><?=$esc($pct($r['marketer_percent']??0))?>٪</td><td><?=$esc($r['valid_from'])?></td><td><?=$esc($r['valid_to'])?></td><td><form method="post" action="<?=admin_url('sales_commercial_rules/commission_assignment_delete')?>" onsubmit="return confirm('این انتساب کارمند به سیاست حذف شود؟');" style="display:inline"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="assignment_id" value="<?=(int)$r['id']?>"><button type="submit" class="btn btn-default btn-sm" title="حذف انتساب"><i class="fa fa-trash text-danger"></i> حذف</button></form></td></tr><?php endforeach?></tbody></table>

<?php elseif($tab==='ledger'): ?><h4>دفتر کمیسیون محاسبه‌شده</h4>
<form method="get" action="<?=admin_url('sales_commercial_rules/commission_center')?>" class="form-inline" style="margin-bottom:12px" id="scrLedgerPeriodFilter">
<input type="hidden" name="tab" value="ledger">
<label for="scr-ledger-filter-period">نمایش پورسانت‌های دوره رتبه‌بندی</label>
<select id="scr-ledger-filter-period" name="level_id" class="form-control" onchange="this.form.submit()">
<option value="">همه دوره‌ها</option>
<?php foreach($ranking_periods as $period):?><option value="<?=(int)$period['id']?>" <?=((int)($ledger_selected_period_id??0)===(int)$period['id'])?'selected':''?>><?=$esc(($period['name']??('دوره '.$period['id'])).' | '.$period['valid_from'].' تا '.$period['valid_to'])?></option><?php endforeach?>
</select>
</form>
<?php if(!empty($ledger_selected_period)):?><p class="text-muted">نمایش پورسانت‌های ثبت‌شده برای فاکتورهای مورخ <?=$esc($ledger_selected_period['valid_from'])?> تا <?=$esc($ledger_selected_period['valid_to'])?> (بر اساس تاریخ فاکتور).</p><?php endif ?>
<form method="post" action="<?=admin_url('sales_commercial_rules/commission_ledger_rebuild')?>" class="form-inline" style="margin-bottom:20px">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?>
<label for="scr-ledger-period">دوره رتبه‌بندی بر اساس تاریخ فاکتور</label>
<select id="scr-ledger-period" name="level_id" class="form-control" required><option value="">انتخاب دوره برای محاسبه</option><?php foreach($ranking_periods as $period):?><option value="<?=(int)$period['id']?>" <?=((int)($ledger_selected_period_id??0)===(int)$period['id'])?'selected':''?> ><?=$esc(($period['name']??('دوره '.$period['id'])).' | '.$period['valid_from'].' تا '.$period['valid_to'])?></option><?php endforeach?></select>
<button type="submit" class="btn btn-primary">محاسبه پورسانت پرداخت‌های دوره</button>
</form>
<?php $scrSkipped=$this->session->flashdata('scr_commission_skipped');if($scrSkipped):?><div class="alert alert-warning"><?=implode('<br>',array_map($esc,$scrSkipped))?></div><?php endif ?><table class="table dt-table"><thead><tr><th>شناسه پرداخت</th><th>فاکتور</th><th>کارمند</th><th>نقش</th><th>تاریخ پرداخت</th><th>مبلغ مبنا</th><th>درصد</th><th>پورسانت</th></tr></thead><tbody><?php foreach($ledger as $r):?><tr><td><button type="button" class="btn btn-link btn-sm scr-ledger-link" data-kind="payment" data-id="<?=(int)$r['payment_id']?>"><?=$esc($r['payment_id'])?></button></td><td><button type="button" class="btn btn-link btn-sm scr-ledger-link" data-kind="invoice" data-id="<?=(int)$r['invoice_id']?>"><?=$esc($ledger_invoice_numbers[(int)$r['invoice_id']]??$r['invoice_id'])?></button></td><td><?=$esc($r['staff_name'])?></td><td><?=$esc(($r['role']==='seller')?'فروشنده':(($r['role']==='marketer')?'بازاریاب':(($r['role']==='manager')?'مدیر':$r['role'])))?></td><td><?=$esc($r['payment_date'])?></td><td><?=$esc($money($r['paid_share']))?></td><td><?=$esc($pct($r['rule_percent']))?>٪</td><td><?=$esc($money($r['commission_amount']))?></td></tr><?php endforeach?></tbody></table><?php if(empty($ledger)):?><p class="text-muted">برای این دوره هنوز پورسانت ثبت‌شده‌ای وجود ندارد. در صورت وجود پرداخت، دکمه محاسبه پورسانت پرداخت‌های دوره را اجرا کنید.</p><?php endif ?><?php if(!empty($ledger_manager_shares)):?><h4 style="margin-top:28px">پورسانت مدیران از مبلغ مبنا</h4><p class="text-muted">سهم هر مدیر از اعمال درصد تعریف‌شده در بخش «مدیران و زیرمجموعه‌ها» بر مبلغ مبنای پورسانت همان پرداخت محاسبه می‌شود، نه پورسانت فروشنده. این جدول نمایشی است و سند تسویه مدیر ایجاد نمی‌کند.</p><table class="table dt-table"><thead><tr><th>فاکتور</th><th>شناسه پرداخت</th><th>فروشنده</th><th>مدیر</th><th>مبلغ مبنای پورسانت</th><th>درصد مشارکت مدیر</th><th>سهم مدیر</th></tr></thead><tbody><?php foreach($ledger_manager_shares as $m):?><tr><td><?=$esc($ledger_invoice_numbers[(int)$m['invoice_id']]??$m['invoice_id'])?></td><td><?=$esc($m['payment_id'])?></td><td><?=$esc($m['seller_name'])?></td><td><?=$esc($m['manager_name'])?></td><td><?=$esc($money($m['commission_base']))?></td><td><?=$esc($pct($m['percent']))?>٪</td><td><?=$esc($money($m['manager_amount']))?></td></tr><?php endforeach?></tbody></table><?php endif ?>
<div class="modal fade" id="scrLedgerReadOnlyModal" tabindex="-1" role="dialog" aria-labelledby="scrLedgerReadOnlyTitle"><div class="modal-dialog scr-ledger-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="بستن">&times;</button><h4 class="modal-title" id="scrLedgerReadOnlyTitle">اطلاعات</h4></div><div class="modal-body"><table class="table table-striped table-condensed" id="scrLedgerReadOnlyTable"></table><p class="text-muted" style="font-size:12px">این پنجره فقط برای مشاهده است و امکان ویرایش یا ثبت اطلاعات ندارد.</p></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">بستن</button></div></div></div></div>
<style>
#scrLedgerReadOnlyModal .scr-ledger-dialog{width:min(620px,calc(100vw - 28px));margin:35px auto}
#scrLedgerReadOnlyTable{width:100%;table-layout:fixed;direction:rtl}
#scrLedgerReadOnlyTable td{padding:10px 12px;vertical-align:top;overflow-wrap:anywhere;word-break:break-word;white-space:normal;direction:rtl}
#scrLedgerReadOnlyTable td:first-child{width:35%;font-weight:500}
#scrLedgerReadOnlyTable td:last-child{width:65%;text-align:left;direction:ltr;unicode-bidi:plaintext}
</style>
<script>
(function(){
 var invoiceDetails=<?=json_encode($ledger_invoice_details??[],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 var paymentDetails=<?=json_encode($ledger_payment_details??[],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 var invoiceNumbers=<?=json_encode($ledger_invoice_numbers??[],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 var statusNames={'1':'پرداخت نشده','2':'پرداخت شده','3':'پرداخت ناقص','4':'معوقه','5':'لغو شده','6':'پیش‌نویس'};
 var paymentModeNames=<?=json_encode($ledger_payment_mode_names??[],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
 var fields={invoice:[['number','شماره فاکتور'],['date','تاریخ فاکتور'],['duedate','سررسید'],['subtotal','جمع جزء'],['total','مبلغ فاکتور'],['status','وضعیت فاکتور']],payment:[['id','شناسه پرداخت'],['invoice_number','شماره فاکتور'],['date','تاریخ پرداخت'],['amount','مبلغ پرداخت'],['paymentmode','روش پرداخت'],['transactionid','شماره تراکنش'],['note','توضیحات']]};
 document.addEventListener('click',function(e){var btn=e.target.closest('.scr-ledger-link');if(!btn)return;e.preventDefault();var kind=btn.getAttribute('data-kind'),id=btn.getAttribute('data-id');var v=kind==='invoice'?invoiceDetails[id]:paymentDetails[id];var table=document.getElementById('scrLedgerReadOnlyTable');while(table.firstChild)table.removeChild(table.firstChild);document.getElementById('scrLedgerReadOnlyTitle').textContent=kind==='invoice'?'مشاهده فاکتور':'مشاهده پرداخت';if(!v){var row=table.insertRow();row.insertCell().textContent='اطلاعات این رکورد در دسترس نیست.';}else{v=Object.assign({},v);if(kind==='invoice')v.number=invoiceNumbers[id]||id;else v.invoice_number=invoiceNumbers[v.invoiceid]||v.invoiceid||'—';if(kind==='invoice')v.status=statusNames[String(v.status)]||('وضعیت '+v.status);else v.paymentmode=paymentModeNames[String(v.paymentmode)]||v.paymentmode||'—';fields[kind].forEach(function(f){if(v[f[0]]===undefined)return;var row=table.insertRow();var label=row.insertCell(),value=row.insertCell();label.textContent=f[1];value.textContent=v[f[0]]===null?'—':String(v[f[0]]);});}$('#scrLedgerReadOnlyModal').modal('show');});
})();
</script>
<p class="text-muted">دفتر فقط محاسبات ثبت‌شده را نمایش می‌دهد. بازسازی از صفحه دوره رتبه‌بندی انجام می‌شود.</p>
<?php elseif($tab==='receipts'): ?>
<h4>تسویه تجمیعی پورسانت‌ها</h4><p class="text-muted">اقلام تسویه‌نشده یک دریافت‌کننده را انتخاب کنید. هر قلم فقط یک‌بار قابل تسویه است.</p>
<div class="row"><div class="col-md-3"><label>دریافت‌کننده</label><select id="scrSettleStaff" class="form-control"><option value="">انتخاب کنید</option><?php $people=[];foreach(array_merge($settlement_rows,$manager_settlement_rows) as $r)$people[(int)$r['staff_id']]=$r['staff_name'];foreach($people as $id=>$name):?><option value="<?=$esc($id)?>"><?=$esc($name)?></option><?php endforeach?></select></div><div class="col-md-3"><label>از تاریخ پرداخت مبنا</label><input type="date" id="scrSettleFrom" class="form-control"></div><div class="col-md-3"><label>تا تاریخ پرداخت مبنا</label><input type="date" id="scrSettleTo" class="form-control"></div><div class="col-md-3"><label>جمع انتخاب‌شده</label><div id="scrSettleTotal" class="form-control">۰</div></div></div>
<form action="<?=admin_url('sales_commercial_rules/commission_batch_settle')?>" method="post" enctype="multipart/form-data" id="scrSettleForm" style="margin-top:15px">
<?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="staff_id" id="scrSettleStaffHidden">
<div style="max-height:360px;overflow:auto"><table class="table table-bordered"><thead><tr><th><input type="checkbox" id="scrSettleAll" title="انتخاب همه موارد فیلترشده"></th><th>دریافت‌کننده</th><th>نقش</th><th>تاریخ</th><th>مبلغ پورسانت</th><th>شماره فاکتور</th></tr></thead><tbody><?php foreach(array_merge($settlement_rows,$manager_settlement_rows) as $r):?><tr class="scr-settle-row" data-staff="<?=(int)$r['staff_id']?>" data-date="<?=$esc($r['payment_date'])?>"><td><input type="checkbox" class="scr-settle-check" name="<?=$r['role']==='manager'?'manager_keys[]':'commission_ids[]'?>" value="<?=$r['role']==='manager'?$esc($r['key']):(int)$r['id']?>" data-amount="<?=$esc($r['commission_amount'])?>"></td><td><?=$esc($r['staff_name'])?></td><td><?=$esc($r['role']==='manager'?'مدیر':'کارشناس / بازاریاب')?></td><td><?=$esc($r['payment_date'])?></td><td><?=$esc($money($r['commission_amount']))?></td><td><?=$esc($settlement_invoice_numbers[(int)$r['invoice_id']]??('فاکتور '.(int)$r['invoice_id']))?></td></tr><?php endforeach?></tbody></table></div>
<div class="row"><div class="col-md-3"><label>تاریخ پرداخت</label><input type="date" name="payment_date" class="form-control" required></div><div class="col-md-3"><label>روش پرداخت</label><select name="payment_method" class="form-control" required><option value="bank">انتقال بانکی</option><option value="cash">نقدی</option><option value="cheque">چک</option><option value="other">سایر</option></select></div><div class="col-md-3"><label>شناسه تراکنش</label><input name="transaction_ref" class="form-control" maxlength="255"></div><div class="col-md-3"><label>پیوست پرداخت (PDF / تصویر تا ۵MB)</label><input type="file" name="receipt_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div></div><div class="form-group"><label>توضیحات</label><textarea name="notes" class="form-control" rows="2"></textarea></div><button type="submit" class="btn btn-primary" id="scrSettleSubmit" disabled>ثبت پرداخت تجمیعی</button></form>
<hr><h4>سوابق پرداخت‌های تجمیعی</h4><table class="table table-bordered"><thead><tr><th>شناسه</th><th>دریافت‌کننده</th><th>تعداد اقلام</th><th>مبلغ</th><th>تاریخ پرداخت</th><th>روش</th><th>شماره پیگیری</th><th>پیوست</th></tr></thead><tbody><?php foreach($settlement_batches as $b):?><tr><td><?=(int)$b['id']?></td><td><?=$esc($b['staff_name'])?></td><td><?=(int)$b['item_count']?></td><td><?=$esc($money($b['amount']))?></td><td><?=$esc($b['payment_date'])?></td><td><?=$esc(['bank'=>'انتقال بانکی','cash'=>'نقدی','cheque'=>'چک','other'=>'سایر'][$b['payment_method']]??$b['payment_method'])?></td><td><?=$esc($b['transaction_ref'])?></td><td><?php if($b['attachment']):?><a href="<?=base_url('uploads/scr_commission_settlements/'.rawurlencode($b['attachment']))?>" target="_blank" rel="noopener">مشاهده</a><?php endif?></td></tr><?php endforeach?></tbody></table>
<script>(function(){var staff=document.getElementById('scrSettleStaff'),from=document.getElementById('scrSettleFrom'),to=document.getElementById('scrSettleTo'),all=document.getElementById('scrSettleAll'),rows=[].slice.call(document.querySelectorAll('.scr-settle-row')),checks=[].slice.call(document.querySelectorAll('.scr-settle-check'));function update(){document.getElementById('scrSettleStaffHidden').value=staff.value;var total=0,count=0;rows.forEach(function(r){var visible=!!staff.value&&r.dataset.staff===staff.value&&(!from.value||r.dataset.date>=from.value)&&(!to.value||r.dataset.date<=to.value);r.style.display=visible?'':'none';var c=r.querySelector('input');if(!visible)c.checked=false;if(c.checked){count++;total+=Number(c.dataset.amount)||0;}});document.getElementById('scrSettleTotal').textContent=total.toLocaleString('fa-IR',{maximumFractionDigits:0})+' د.ع';document.getElementById('scrSettleSubmit').disabled=!count;all.checked=false;}[staff,from,to].forEach(function(e){e.addEventListener('change',update)});checks.forEach(function(c){c.addEventListener('change',update)});all.addEventListener('change',function(){rows.forEach(function(r){if(r.style.display!=='none')r.querySelector('input').checked=all.checked});update()});document.getElementById('scrSettleForm').addEventListener('submit',function(e){if(!staff.value||!checks.some(function(c){return c.checked})||!confirm('پرداخت انتخاب‌شده ثبت و اقلام تسویه می‌شوند. تأیید می‌کنید؟'))e.preventDefault()});update()})();</script>
<?php else: ?><h4>گزارش پورسانت</h4><p>گزارش ریز پرداخت و پورسانت در تب «دفتر کمیسیون» و نتایج تعدیل‌شده در بخش «رتبه‌بندی» قابل مشاهده است.</p><a class="btn btn-default" href="<?=admin_url('sales_commercial_rules/rankings')?>">مشاهده رتبه‌بندی‌ها</a><?php endif ?>
</div></div></div></div><?php init_tail(); ?>
<script>
function scrPolicyNew(){var f=jQuery('#scrPolicyModal form');if(f.length){f[0].reset();f.find('[name=id]').val(0);}}
function scrPolicyEdit(button){var b=jQuery(button),f=jQuery('#scrPolicyModal form');if(!f.length){alert('فرم ویرایش پیدا نشد');return;}var fields={'id':'id','group_id':'group','sale_type_id':'type','valid_from':'from','valid_to':'to','seller_percent':'seller','marketer_percent':'marketer'};jQuery.each(fields,function(field,attr){f.find('[name="'+field+'"]').val(b.attr('data-'+attr)).trigger('change');});jQuery('#scrPolicyModal').modal('show');}
</script>

<style>
.scr-policy-picker{border:1px solid #dce1e7;border-radius:7px;overflow:hidden;background:#fff}
.scr-policy-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px;background:#f8fafc;border-bottom:1px solid #e8ebef}
.scr-policy-toolbar input{max-width:280px;flex:1;min-width:150px}.scr-policy-toolbar span{margin:0 8px}
.scr-policy-options{max-height:300px;overflow-y:auto}.scr-policy-option{display:flex;align-items:center;gap:12px;padding:11px 14px;margin:0;border-bottom:1px solid #edf0f3;cursor:pointer;font-weight:normal;flex-wrap:wrap}
.scr-policy-option:hover{background:#f7f9fc}.scr-policy-option:has(input:checked){background:#eff8f2}
.scr-policy-option input{width:17px;height:17px;margin:0;flex-shrink:0}.scr-policy-desc{display:flex;gap:8px;align-items:center;flex-wrap:wrap;flex:1}.scr-policy-desc small{color:#778}.scr-policy-rates{white-space:nowrap;color:#455}
</style>
<script>
function scrFilterPolicies(q){q=(q||'').trim().toLocaleLowerCase();jQuery('#scrPolicyOptions .scr-policy-option').each(function(){jQuery(this).toggle(jQuery(this).text().toLocaleLowerCase().indexOf(q)!==-1);});}
function scrUpdatePolicyCount(){var n=jQuery('#scrPolicyOptions input[type=checkbox]:checked').length;jQuery('#scrPolicySelectedCount').text(n+' سیاست انتخاب‌شده');}
function scrToggleVisiblePolicies(on){jQuery('#scrPolicyOptions .scr-policy-option:visible input[type=checkbox]').prop('checked',on);scrUpdatePolicyCount();}
</script>
