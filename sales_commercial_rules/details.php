<?php defined('BASEPATH') or exit('No direct script access allowed');init_head();?>
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
.scr-step-card:nth-child(3n+1) .scr-step-card-head{background:#fff4e9}
.scr-step-card:nth-child(3n+3) .scr-step-card-head{background:#fff4e9}
.scr-step-card-title{font-size:16px;font-weight:500;color:#263142}
.scr-step-card-letter{font-size:21px;color:#e87813;font-weight:500}
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

.scr-step-count{margin-right:auto;color:#96765e;font-size:12px;padding:8px}.scr-step-meta{font-size:12px;color:#746a61;margin-bottom:13px}.scr-step-meta span{color:#e6b88e;padding:0 5px}.scr-step-range{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px;background:#fffaf5;border:1px solid #f9e9d8;border-radius:9px}.scr-step-range>div{display:flex;flex-direction:column;gap:5px;min-width:0}.scr-step-range small{font-weight:400;color:#8c8177;font-size:11px}.scr-step-range strong{font-weight:500;font-size:14px;white-space:nowrap}.scr-range-arrow{color:#f47a16;font-size:17px}.scr-step-adjust{display:flex;justify-content:space-between;align-items:center;padding:15px 3px 2px;font-size:12px;color:#756b62}.scr-step-adjust strong{color:#db690d;font-size:16px;font-weight:500}.scr-step-delete{color:#b34b45!important}.scr-step-card-actions{border-top:1px solid #f5e8dd;margin:0 13px;padding:12px 0!important}.scr-step-card-actions .btn{padding:5px 13px}.scr-step-card{transition:box-shadow .15s}.scr-step-card:hover{box-shadow:0 5px 18px rgba(128,79,33,.10)}

/* Reference-matched compact ranking cards */
.scr-reference-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;direction:rtl;margin:12px 0 13px;flex-wrap:wrap}
.scr-role-switch{display:flex;align-items:center;gap:0;background:#f6f7f9;border:1px solid #dfe4eb;border-radius:22px;padding:2px;min-width:290px;margin:auto}
.scr-role-switch button{flex:1;background:transparent;border:0;border-radius:20px;padding:8px 19px;color:#7c8797;font-size:12px;font-weight:400;cursor:pointer}
.scr-role-switch button.active{background:#ff8013;color:#fff;box-shadow:0 2px 5px #f47a1630}
.scr-sale-filter{display:flex;align-items:center;gap:8px;font-size:12px;color:#263142}
.scr-sale-filter select{width:190px;height:34px;border-radius:9px;font-size:12px}
.scr-add-step{border-radius:9px!important;padding:9px 14px!important;font-size:12px}
.scr-step-cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin-top:7px}
.scr-step-card{border:1px solid #e3e8ed;border-radius:12px;box-shadow:0 2px 8px #202a3508}
.scr-step-card-head,.scr-step-card:nth-child(3n+1) .scr-step-card-head,.scr-step-card:nth-child(3n+3) .scr-step-card-head{display:flex;flex-direction:row;align-items:center;justify-content:space-between;padding:11px 14px;min-height:69px;border-bottom:0;background:#f2f8ff}
.scr-step-card.grade-a .scr-step-card-head{background:#eaf9f1}.scr-step-card.grade-b .scr-step-card-head{background:#fff6e9}.scr-step-card.grade-c .scr-step-card-head{background:#edf7ff}
.scr-step-card-head-text{display:flex;flex-direction:column;gap:5px;min-width:0}
.scr-step-card-title{font-size:15px;font-weight:600;color:#182536}
.scr-step-card-head-text small{font-size:11px;font-weight:400;color:#77879a;white-space:normal;line-height:1.5}
.scr-step-card-letter{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#d9edff;color:#0872c9;font-size:27px;font-weight:600;flex-shrink:0;direction:ltr}
.grade-a .scr-step-card-letter{background:#c9f5df;color:#09935c}.grade-b .scr-step-card-letter{background:#ffe4bc;color:#ee8a06}.grade-c .scr-step-card-letter{background:#d8edff;color:#0872c9}
.scr-step-card-body{padding:8px 14px 4px}
.scr-step-card-line{padding:7px 0;align-items:center;min-height:32px;border-bottom:1px solid #eef1f4;font-size:12px}
.scr-step-card-line span:first-child{color:#46536a}.scr-step-card-line span:last-child{font-weight:400;color:#142239;direction:rtl;text-align:left;unicode-bidi:normal}
.scr-step-card-line:last-child{border-bottom:0}.scr-step-active{background:#c9f5df;color:#0d915d!important;padding:3px 10px;border-radius:15px;font-size:11px}
.scr-step-card-actions{display:flex;justify-content:flex-end;flex-direction:row-reverse;align-items:center;gap:10px;border-top:0!important;margin:0!important;padding:9px 14px 12px!important}
.scr-step-card-actions .scr-step-edit{color:#f47a16;border:1px solid #ffcfaa;border-radius:8px;padding:6px 13px;font-size:12px}
.scr-step-card-actions .scr-menu-toggle{border:1px solid #dbe2e9;color:#263142;border-radius:8px;min-width:32px;padding:5px 8px}
.scr-card-menu{position:relative;display:flex;align-items:center;gap:7px;flex-wrap:nowrap}.scr-card-delete{position:static;display:inline-flex;align-items:center;z-index:auto;background:#fff;padding:0;border:0;border-radius:7px;box-shadow:none;white-space:nowrap}.scr-card-delete .btn{border:1px solid #f1c6c6;color:#b34b45;background:#fff;border-radius:7px;padding:6px 12px}.scr-step-card-actions{min-height:52px;flex-wrap:wrap}.scr-step-card{overflow:visible}.scr-step-card-head{border-radius:12px 12px 0 0}
@media(max-width:850px){.scr-step-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.scr-role-switch{margin:0}}
@media(max-width:560px){.scr-step-cards{grid-template-columns:1fr}.scr-reference-toolbar{align-items:stretch}.scr-role-switch{width:100%}}
.scr-sale-section{margin:15px 0 24px}.scr-sale-section-heading{display:flex;align-items:center;justify-content:space-between;padding:11px 15px;background:#fff7ef;border:1px solid #f8dec5;border-radius:10px;color:#a85112;font-size:14px;font-weight:500}.scr-sale-section-heading i{color:#f47a16;margin-left:7px}.scr-sale-section-count{font-size:12px;color:#99745b;background:#fff;padding:4px 11px;border-radius:16px}.scr-card-sale-line span:last-child{color:#bd620e!important;font-weight:500!important}.scr-sale-section .scr-step-cards{margin-top:12px}.scr-sale-section .scr-step-card{overflow:hidden}
</style>
<style>
.scr-results-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin:18px 0 15px}.scr-results-sale-label{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:400}.scr-results-sale-label select{width:200px;max-width:100%;border-radius:9px}.scr-result-cards{margin-top:12px}.scr-result-card .scr-step-card-head-text{min-width:0}.scr-result-card .scr-step-card-title{overflow-wrap:anywhere}.scr-result-final{background:#fff8f0;border-radius:7px;padding:9px!important;color:#ba5c09}.scr-result-final span:last-child{color:#ba5c09!important;font-weight:600!important}.scr-result-card.grade-other .scr-step-card-head{background:#f3f6fa}.scr-result-card.grade-other .scr-step-card-letter{background:#e5eaf0;color:#65758b;font-size:20px}@media(max-width:650px){.scr-results-toolbar{align-items:stretch}.scr-results-roles{width:100%}.scr-results-sale-label{width:100%;justify-content:space-between}}
</style>
<?php $tab=$this->input->get('tab',true)?:'steps';if(!in_array($tab,['steps','ledger','results'],true))$tab='steps';?><div id="wrapper"><div class="content"><div class="tw-flex tw-items-center tw-justify-between tw-mb-3"><div><h4 class="tw-font-bold tw-text-xl"><?=_l('scr_ranking_details')?>: <?=html_escape($level['name'])?></h4><div class="text-muted"><?=html_escape($level['valid_from'])?> — <?=html_escape($level['valid_to'])?></div></div><a class="btn btn-default" href="<?=admin_url('sales_commercial_rules/rankings')?>"><i class="fa fa-arrow-right"></i> <?=_l('scr_back_to_rankings')?></a></div><div class="panel_s"><div class="panel-body"><ul class="nav nav-tabs" role="tablist"><li class="<?=$tab==='steps'?'active':''?>"><a href="#steps" data-toggle="tab"><?=_l('scr_ranking_steps')?></a></li><li class="<?=$tab==='ledger'?'active':''?>"><a href="#ledger" data-toggle="tab"><?=_l('scr_invoice_calculation_details')?></a></li><li class="<?=$tab==='results'?'active':''?>"><a href="#results" data-toggle="tab"><?=_l('scr_ranking_final_results')?></a></li></ul><div class="tab-content tw-mt-4">
<div class="tab-pane <?=$tab==='steps'?'active':''?>" id="steps">
<div class="scr-reference-toolbar"><div class="scr-role-switch" role="group" aria-label="گروه رتبه‌بندی"><button type="button" class="active" data-role="seller">کارشناسان فروش</button><button type="button" data-role="marketer">مدیران</button></div><div class="scr-sale-filter"><label for="scrCardSale">نوع فروش:</label><select class="form-control" id="scrCardSale"><option value="">همه انواع فروش</option><?php foreach($types as $type): ?><option value="<?=html_escape((string)$type['legacy_id'])?>"><?=html_escape($type['name'])?></option><?php endforeach; ?></select></div><button type="button" class="btn btn-primary scr-add-step" data-toggle="modal" data-target="#scrStepModal" onclick="scrStepNew()"><i class="fa fa-plus"></i> افزودن گام جدید</button></div>
<?php
$scrSaleNames=[];
foreach($types as $scrType){$scrSaleNames[(string)$scrType['legacy_id']]=(string)$scrType['name'];}
$scrSaleGroups=[];
$scrMarketingLabels=$marketing_labels??['normal'=>'عادی'];
foreach($steps as $scrRow){
  $scrSaleId=(string)(int)$scrRow['sale_type_id'];
  $scrMarketing=(string)($scrRow['marketing_type']??'normal');
  $scrIsManager=(string)($scrRow['ranking_role']??'seller')==='marketer';
  $scrIsOnline=mb_strpos((string)($scrSaleNames[$scrSaleId]??''),'آنلاین')!==false;
  $scrKey=$scrSaleId.'|'.($scrIsManager&&$scrIsOnline?$scrMarketing:'normal');
  $scrSaleGroups[$scrKey][]=$scrRow;
}
?>
<div id="scrStepGroups" dir="rtl">
<?php foreach($scrSaleGroups as $scrGroupKey=>$scrGroupRows): $scrParts=explode('|',$scrGroupKey,2);$scrSaleId=$scrParts[0];$scrGroupMarketing=$scrParts[1]??'normal';$scrGroupTitle=$scrSaleNames[$scrSaleId]??('نوع فروش '.$scrSaleId);if($scrGroupMarketing!=='normal'&&!empty($scrMarketingLabels[$scrGroupMarketing]))$scrGroupTitle.=' ('.$scrMarketingLabels[$scrGroupMarketing].')'; ?>
<section class="scr-sale-section" data-sale="<?=html_escape($scrSaleId)?>">
<div class="scr-sale-section-heading"><span><i class="fa fa-tags"></i> <?=html_escape($scrGroupTitle)?></span><span class="scr-sale-section-count"></span></div>
<div class="scr-step-cards" dir="rtl">
<?php foreach($scrGroupRows as $r): $role=(string)($r['ranking_role']??'seller');$kind=(string)($r['adjustment_type']??'percent');$adj=(float)($r['adjustment_value']??$r['percent']??0);$rawName=(string)$r['name'];$grade=preg_match('/(?:Plan|گام)\s*([ABC](?:\+)?)/iu',$rawName,$matches)?strtoupper($matches[1]):'';$gradeClass=($grade==='A'||$grade==='A+')?'grade-a':($grade==='B'?'grade-b':($grade==='C'?'grade-c':'grade-other')); ?>
<article class="scr-step-card <?=html_escape($gradeClass)?>" data-role="<?=html_escape($role)?>" data-marketing="<?=html_escape($r['marketing_type']??'normal')?>" data-sale="<?=(int)$r['sale_type_id']?>">
<div class="scr-step-card-head"><div class="scr-step-card-head-text"><span class="scr-step-card-title"><?=html_escape($grade?'گام '.$grade:$rawName)?></span><small>بازه فروش: <?=number_format((float)$r['from_amount'],0,'.',',')?> تا <?=number_format((float)$r['to_amount'],0,'.',',')?> د.ع</small></div><span class="scr-step-card-letter"><?=html_escape($grade?:'—')?></span></div>
<div class="scr-step-card-body"><div class="scr-step-card-line scr-card-sale-line"><span>نوع فروش</span><span><?=html_escape($scrGroupTitle)?></span></div><div class="scr-step-card-line"><span>نوع بازاریابی</span><span><?=html_escape($scrMarketingLabels[$r['marketing_type']??'normal']??'عادی')?></span></div><div class="scr-step-card-line"><span>نوع تعدیل</span><span><?=$kind==='fixed'?'مبلغ ثابت':'درصد'?></span></div><div class="scr-step-card-line"><span>مقدار تعدیل</span><span><?=html_escape(rtrim(rtrim(number_format($adj,3,'.',''),'0'),'.'))?> <?=$kind==='fixed'?'د.ع':'٪'?></span></div><div class="scr-step-card-line"><span>پورسانت پایه</span><span>طبق تنظیمات <?=($role==='marketer'?'مدیران':'کارشناسان')?></span></div><div class="scr-step-card-line"><span>وضعیت</span><span class="scr-step-active">فعال</span></div></div>
<div class="scr-step-card-actions"><button type="button" class="btn btn-default btn-sm scr-step-edit" data-toggle="modal" data-target="#scrStepModal" data-id="<?=(int)$r['id']?>" data-name="<?=html_escape($r['name'])?>" data-type="<?=(int)$r['sale_type_id']?>" data-role="<?=html_escape($role)?>" data-marketing="<?=html_escape($r['marketing_type']??'normal')?>" data-from="<?=html_escape($r['from_amount'])?>" data-to="<?=html_escape($r['to_amount'])?>" data-adjtype="<?=html_escape($kind)?>" data-adj="<?=html_escape($r['adjustment_value']??$r['percent'])?>">ویرایش <i class="fa fa-pencil"></i></button><div class="scr-card-menu"><button type="button" class="btn btn-default btn-sm scr-menu-toggle" aria-label="گزینه‌های بیشتر" aria-expanded="false">•••</button><form method="post" action="<?=admin_url('sales_commercial_rules/delete_ranking_step/'.(int)$r['id'])?>" class="scr-card-delete" style="display:none"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><?=form_hidden('level_id',$level['id'])?><button type="submit" class="btn btn-default btn-sm scr-step-delete" onclick="return confirm('آیا این گام حذف شود؟')">حذف گام</button></form></div></div>
</article><?php endforeach; ?>
</div></section><?php endforeach; ?>
<div class="scr-step-empty" id="scrStepEmpty" style="display:none">گامی برای فیلتر انتخاب‌شده یافت نشد.</div></div></div>
<div class="tab-pane <?=$tab==='ledger'?'active':''?>" id="ledger"><h4><?=_l('scr_invoice_calculation_details')?></h4><p class="text-muted">برای هر پرداخت، پورسانت کارشناسان از دفتر پورسانت و سهم مدیران از همان مبلغ مبنا و تنظیمات مدیران و زیرمجموعه‌ها نمایش داده می‌شود. سهم مدیران صرفاً نمایشی است و سند تسویه جدید ایجاد نمی‌کند.</p>
<?php $paymentRows=[];foreach(($beneficiaries??[]) as $b){$paymentRows[(int)$b['payment_id']][]=$b;} foreach(($ranking_manager_shares??[]) as $scrPaymentId=>$scrManagerRows){foreach($scrManagerRows as $scrManagerRow){$paymentRows[(int)$scrPaymentId][]=$scrManagerRow;}} ?>
<table class="table dt-table"><thead><tr><th>شماره فاکتور</th><th>تاریخ فاکتور</th><th>تاریخ پرداخت</th><th>نوع فروش</th><th>دریافت‌کننده</th><th>نقش ثبت‌شده</th><th>مبلغ مبنا</th><th>درصد مؤثر پایه</th><th>پورسانت پایه</th></tr></thead><tbody>
<?php foreach($ledger as $r): $entries=$paymentRows[(int)$r['payment_id']]??[];
if(!$entries)$entries=[['staff_name'=>$r['seller_name']??'—','staff_id'=>$r['seller_staff_id']??0,'role'=>'seller','paid_share'=>$r['paid_amount'],'commission_amount'=>0,'rule_percent'=>0]];
foreach($entries as $b):$base=(float)$b['paid_share'];$commission=(float)$b['commission_amount'];$percent=isset($b['rule_percent'])?(float)$b['rule_percent']:($base!=0?$commission*100/$base:0);$roleNames=['seller'=>'کارشناس فروش','marketer'=>'بازاریاب / معرف','manager'=>'مدیر فروش']; ?>
<tr><td><?=html_escape($r['invoice_number']?:$r['invoice_id'])?></td><td><?=html_escape($r['invoice_date'])?></td><td><?=html_escape($r['payment_date'])?></td><td><?=html_escape($r['sale_type_name']?:$r['sale_type_id'])?></td><td><?=html_escape($b['staff_name']?:$b['staff_id'])?></td><td><?=html_escape($roleNames[$b['role']]??$b['role'])?></td><td><?=number_format($base,0,'.',',')?> د.ع</td><td><?=rtrim(rtrim(number_format($percent,3,'.',''),'0'),'.')?>٪</td><td><?=number_format($commission,0,'.',',')?> د.ع</td></tr><?php endforeach;endforeach;?></tbody></table></div>
<div class="tab-pane <?=$tab==='results'?'active':''?>" id="results"><div class="tw-flex tw-justify-between tw-items-center tw-mb-3"><div><h4 class="tw-mt-0"><?=_l('scr_ranking_final_results')?></h4><p class="text-muted"><?=_l('scr_results_v6_help')?></p></div><?php if(!(int)($level['commission_paid']??0)):?><form method="post" action="<?=admin_url('sales_commercial_rules/calculate_ranking/'.(int)$level['id'])?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><button class="btn btn-success"><i class="fa fa-calculator"></i> <?=_l('scr_calculate_ranking')?></button></form><?php endif;?><?php if((int)($level['calculated']??0)===1 && !(int)($level['commission_paid']??0)):?><form method="post" action="<?=admin_url('sales_commercial_rules/mark_ranking_paid/'.(int)$level['id'])?>" class="tw-ml-2"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><button class="btn btn-primary" onclick="return confirm('<?=addslashes(_l('scr_mark_paid_confirm'))?>')"><i class="fa fa-check-circle"></i> <?=_l('scr_mark_commission_paid')?></button></form><?php elseif((int)($level['commission_paid']??0)===1):?><span class="label label-success tw-ml-2"><?=_l('scr_commission_paid')?> <?=html_escape($level['commission_paid_at']??'')?></span><?php endif;?></div><?php
$scrResultGroups=[];
foreach($results as $scrResult){
  $scrResultSale=(string)($scrResult['sale_type_id']??'0');
  $scrResultMarketing=($scrResult['ranking_role']??'seller')==='marketer'?($scrResult['marketing_type']??'normal'):'normal';$scrResultGroups[$scrResultSale.'|'.$scrResultMarketing][]=$scrResult;
}
$scrResultSaleNames=[];
foreach($types as $scrResultType){$scrResultSaleNames[(string)$scrResultType['legacy_id']]=(string)$scrResultType['name'];}
?>
<div class="scr-results-toolbar" dir="rtl">
 <div class="scr-role-switch scr-results-roles" role="group" aria-label="گروه نتایج"><button type="button" class="active" data-result-role="seller">کارشناسان فروش</button><button type="button" data-result-role="marketer">مدیران</button></div>
 <label class="scr-results-sale-label">نوع فروش: <select id="scrResultsSale" class="form-control input-sm"><option value="all">همه انواع فروش</option><?php foreach($types as $scrResultType):?><option value="<?=html_escape((string)$scrResultType['legacy_id'])?>"><?=html_escape($scrResultType['name'])?></option><?php endforeach;?></select></label>
</div>
<div id="scrResultGroups" dir="rtl">
<?php foreach($scrResultGroups as $scrResultGroupKey=>$scrResultRows):$scrResultParts=explode('|',$scrResultGroupKey,2);$scrSaleId=$scrResultParts[0];$scrResultMarketing=$scrResultParts[1]??'normal';$scrResultTitle=$scrResultSaleNames[$scrSaleId]??($scrResultRows[0]['sale_type_name']??('نوع فروش '.$scrSaleId));if($scrResultMarketing!=='normal')$scrResultTitle.=' ('.($marketing_labels[$scrResultMarketing]??$scrResultMarketing).')';?>
<section class="scr-sale-section scr-result-section" data-sale="<?=html_escape($scrSaleId)?>">
<div class="scr-sale-section-heading"><span><i class="fa fa-tags"></i> <?=html_escape($scrResultTitle)?></span><span class="scr-result-section-count"></span></div>
<div class="scr-step-cards scr-result-cards">
<?php foreach($scrResultRows as $r):
$scrRole=(string)($r['ranking_role']??'seller');
$scrStepName=(string)($r['step_name']??'');
$scrGrade='';if(preg_match('/(?:plan|گام)\s*(A\+|A|B|C)(?![a-z])/iu',$scrStepName,$scrMatch))$scrGrade=strtoupper($scrMatch[1]);
$scrGradeClass=$scrGrade==='A'||$scrGrade==='A+'?'grade-a':($scrGrade==='B'?'grade-b':($scrGrade==='C'?'grade-c':'grade-other'));
$scrAdjustment=(float)($r['adjustment_value']??0);
$scrAdjustmentText=($r['adjustment_type']??'percent')==='percent'?rtrim(rtrim(number_format($scrAdjustment,3,'.',''),'0'),'.').'%':number_format($scrAdjustment,0,'.',',').' د.ع';
?>
<article class="scr-step-card scr-result-card <?=html_escape($scrGradeClass)?>" data-role="<?=html_escape($scrRole)?>" data-sale="<?=html_escape($scrSaleId)?>">
<div class="scr-step-card-head"><div class="scr-step-card-head-text"><span class="scr-step-card-title"><?=html_escape($r['staff_name']?:$r['staff_id'])?></span><small><?=html_escape($scrResultTitle)?></small></div><span class="scr-step-card-letter"><?=html_escape($scrGrade?:'—')?></span></div>
<div class="scr-step-card-body">
<div class="scr-step-card-line"><span>گام اختصاص‌یافته</span><span><?=html_escape($scrStepName?:_l('scr_no_step'))?></span></div>
<div class="scr-step-card-line"><span>فروش پرداخت‌شده</span><span><?=number_format((float)($r['sales_amount']??0),0,'.',',')?> د.ع</span></div>
<div class="scr-step-card-line"><span>پورسانت پایه</span><span><?=number_format((float)($r['base_commission']??0),0,'.',',')?> د.ع</span></div>
<div class="scr-step-card-line"><span>تعدیل رتبه</span><span><?=html_escape($scrAdjustmentText)?></span></div>
<div class="scr-step-card-line"><span>مبلغ تعدیل</span><span><?=number_format((float)($r['adjustment_amount']??0),0,'.',',')?> د.ع</span></div>
<?php if((float)($r['reversal_amount']??0)!=0):?><div class="scr-step-card-line"><span>برگشت پورسانت</span><span><?=number_format((float)$r['reversal_amount'],0,'.',',')?> د.ع</span></div><?php endif;?>
<div class="scr-step-card-line scr-result-final"><span>پورسانت نهایی</span><span><?=number_format((float)($r['final_commission']??0),0,'.',',')?> د.ع</span></div>
<div class="scr-step-card-line"><span>وضعیت</span><span class="scr-step-active">محاسبه‌شده</span></div>
</div></article>
<?php endforeach;?></div></section><?php endforeach;?>
<div class="scr-step-empty" id="scrResultEmpty" style="display:none">نتیجه‌ای برای فیلتر انتخاب‌شده یافت نشد.</div>
</div>
<script>
(function(){function initResults(){var root=document.getElementById('scrResultGroups'),sale=document.getElementById('scrResultsSale');if(!root||!sale)return;var role='seller',buttons=document.querySelectorAll('.scr-results-roles [data-result-role]');function refresh(){var visible=0;root.querySelectorAll('.scr-result-section').forEach(function(section){var count=0;section.querySelectorAll('.scr-result-card').forEach(function(card){var show=card.getAttribute('data-role')===role&&(sale.value==='all'||card.getAttribute('data-sale')===sale.value);card.style.display=show?'':'none';if(show)count++});section.style.display=count?'':'none';var label=section.querySelector('.scr-result-section-count');if(label)label.textContent=count+' نتیجه';visible+=count});var empty=document.getElementById('scrResultEmpty');if(empty)empty.style.display=visible?'none':'block'}buttons.forEach(function(btn){btn.addEventListener('click',function(){role=btn.getAttribute('data-result-role');buttons.forEach(function(b){b.classList.toggle('active',b===btn)});refresh()})});sale.addEventListener('change',refresh);refresh()}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initResults);else initResults()})();
</script></div>
</div></div></div></div></div><?php if(is_admin()):?><div style="margin:12px 25px"><button type="button" class="btn btn-default" data-toggle="modal" data-target="#scrMarketingCategoriesModal">مدیریت انواع بازاریابی</button></div><div class="modal fade" id="scrMarketingCategoriesModal"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">تعریف و ویرایش انواع بازاریابی آنلاین</h4></div><div class="modal-body"><p class="text-muted">نوع جدید اضافه کنید؛ پس از نخستین ذخیره، سه عنوان پیش‌فرض نیز برای ویرایش در این بخش ظاهر می‌شوند. کد داخلی و سوابق قبلی ثابت می‌مانند.</p><?php foreach(($marketing_categories??[]) as $cat): if((int)$cat['id']===0)continue;?><form method="post" action="<?=admin_url('sales_commercial_rules/save_marketing_category/'.(int)$level['id'])?>" style="display:flex;gap:8px;margin-bottom:8px"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="<?=(int)$cat['id']?>"><input class="form-control" type="text" name="name" maxlength="150" required value="<?=html_escape($cat['name'])?>"><button class="btn btn-default" type="submit">ذخیره</button></form><?php endforeach;?><hr><form method="post" action="<?=admin_url('sales_commercial_rules/save_marketing_category/'.(int)$level['id'])?>" style="display:flex;gap:8px"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0"><input class="form-control" type="text" name="name" maxlength="150" required placeholder="عنوان نوع بازاریابی جدید"><button class="btn btn-primary" type="submit">افزودن</button></form></div></div></div></div><?php endif;?><div class="modal fade" id="scrStepModal"><div class="modal-dialog"><div class="modal-content"><form method="post" action="<?=admin_url('sales_commercial_rules/save_ranking_step/'.(int)$level['id'])?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0"><div class="modal-header"><button class="close" type="button" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=_l('scr_ranking_steps')?></h4></div><div class="modal-body"><?=render_input('name','scr_step_title','','text',['required'=>true])?><?=render_select('sale_type_id',$types,['legacy_id','name'],'scr_sale_type','',[],[],'','',false)?><?=render_select('ranking_role',[['id'=>'seller','name'=>_l('scr_role_seller')],['id'=>'marketer','name'=>_l('scr_role_marketer')]],['id','name'],'scr_ranking_role','seller')?><?=render_select('marketing_type',array_merge([['id'=>'normal','name'=>'عادی']],array_map(function($r){return ['id'=>$r['code'],'name'=>$r['name']];},$marketing_categories??[])),['id','name'],'نوع بازاریابی','normal')?><?=render_input('from_amount','scr_from_amount','','number',['step'=>'0.01','min'=>0,'required'=>true])?><?=render_input('to_amount','scr_to_amount','','number',['step'=>'0.01','min'=>0,'required'=>true])?><?=render_select('adjustment_type',[['id'=>'percent','name'=>_l('scr_adjustment_percent')],['id'=>'fixed','name'=>_l('scr_adjustment_fixed')]],['id','name'],'scr_adjustment_type','percent',[],[],'','',false)?><?=render_input('adjustment_value','scr_adjustment_value','','number',['step'=>'0.001','required'=>true])?></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=_l('scr_cancel_edit')?></button><button class="btn btn-primary"><?=_l('scr_saved')?></button></div></form></div></div></div><div style="margin:12px 25px"><a class="btn btn-default" href="<?=admin_url('sales_commercial_rules/commission_payment_ledger/'.(int)$level['id'])?>">دفتر مستقل پورسانت پرداخت‌ها</a></div><script id="scr-step-cards-script">document.addEventListener('DOMContentLoaded',function(){
var role='seller',sale=document.getElementById('scrCardSale'),sections=document.querySelectorAll('#scrStepGroups .scr-sale-section'),empty=document.getElementById('scrStepEmpty'),buttons=document.querySelectorAll('.scr-role-switch button');
function filter(){var total=0;sections.forEach(function(section){var visible=0;section.querySelectorAll('.scr-step-card').forEach(function(c){var ok=c.dataset.role===role&&(!sale.value||c.dataset.sale===sale.value);c.style.display=ok?'':'none';if(ok)visible++});section.style.display=visible?'':'none';var count=section.querySelector('.scr-sale-section-count');if(count)count.textContent=visible+' گام';total+=visible});empty.style.display=total?'none':'block'}
buttons.forEach(function(b){b.addEventListener('click',function(){role=b.dataset.role;buttons.forEach(function(x){x.classList.toggle('active',x===b)});filter()})});
sale.addEventListener('change',filter);
document.querySelectorAll('.scr-menu-toggle').forEach(function(b){b.addEventListener('click',function(){var f=b.parentNode.querySelector('.scr-card-delete');var open=f.style.display==='none';document.querySelectorAll('.scr-card-delete').forEach(function(x){x.style.display='none'});f.style.display=open?'inline-flex':'none';b.setAttribute('aria-expanded',open?'true':'false')})});
window.scrStepSelectedSale=function(){return sale.value};window.scrStepSelectedRole=function(){return role};filter()
});</script><?php init_tail();?><script>init_selectpicker();function scrStepNew(){var m=jQuery('#scrStepModal');m.find('form')[0].reset();m.find('[name=id]').val(0);m.find('[name=marketing_type]').val('normal').selectpicker('refresh');m.find('[name=ranking_role]').val(window.scrStepSelectedRole?window.scrStepSelectedRole():'seller').selectpicker('refresh');if(window.scrStepSelectedSale&&window.scrStepSelectedSale())m.find('[name=sale_type_id]').val(window.scrStepSelectedSale()).selectpicker('refresh');m.find('[name=adjustment_type]').val('percent').selectpicker('refresh');m.find('[name=sale_type_id]').val('').selectpicker('refresh')}jQuery('.scr-step-edit').click(function(){var b=jQuery(this),m=jQuery('#scrStepModal');m.find('[name=id]').val(b.data('id'));m.find('[name=name]').val(b.data('name'));m.find('[name=sale_type_id]').val(b.data('type')).selectpicker('refresh');m.find('[name=ranking_role]').val(b.data('role')).selectpicker('refresh');m.find('[name=marketing_type]').val(b.data('marketing')||'normal').selectpicker('refresh');m.find('[name=from_amount]').val(b.data('from'));m.find('[name=to_amount]').val(b.data('to'));m.find('[name=adjustment_type]').val(b.data('adjtype')).selectpicker('refresh');m.find('[name=adjustment_value]').val(b.data('adj'))});</script></body></html>
