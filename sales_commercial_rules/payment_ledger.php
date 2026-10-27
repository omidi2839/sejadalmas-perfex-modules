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
<?php?>
<div id="wrapper"><div class="content"><div class="panel_s"><div class="panel-body">
<h4>دفتر مستقل پورسانت پرداخت‌ها — <?=html_escape($level['name'])?></h4>
<p>محاسبه بر اساس پرداخت واقعی و قواعد پورسانت گروه کالا و نوع فروش انجام می‌شود. محاسبه مجدد رکورد تکراری نمی‌سازد.</p>
<form method="post" action="<?=admin_url('sales_commercial_rules/rebuild_commission_payments/'.(int)$level['id'])?>" style="display:inline"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><button class="btn btn-primary" <?=!empty($level['commission_paid'])?'disabled':''?>>بازسازی پورسانت پرداخت‌های دوره</button></form>
<a class="btn btn-default" href="<?=admin_url('sales_commercial_rules/ranking_details/'.(int)$level['id'])?>">بازگشت به رتبه‌بندی</a>
<?php $skipped=$this->session->flashdata('scr_commission_skipped');if($skipped):?><div class="alert alert-warning" style="margin-top:15px"><b>موارد نیازمند بررسی (بدون ثبت پورسانت):</b><ul><?php foreach($skipped as $e):?><li><?=html_escape($e)?></li><?php endforeach?></ul></div><?php endif?>
<div class="table-responsive"><table class="table dt-table"><thead><tr><th>شناسه پرداخت</th><th>فاکتور</th><th>تاریخ فاکتور</th><th>تاریخ پرداخت</th><th>کارشناس</th><th>نقش</th><th>گروه کالا</th><th>مبلغ مبنای پرداخت</th><th>درصد</th><th>پورسانت پایه</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><?=(int)$r['payment_id']?></td><td><?=(int)$r['invoice_id']?></td><td><?=html_escape($r['invoice_date'])?></td><td><?=html_escape($r['payment_date'])?></td><td><?=html_escape($r['staff_name'])?></td><td><?=($r['role']==='seller'?'فروشنده':'معرف')?></td><td><?=(int)$r['group_id']?></td><td><?=number_format((float)$r['paid_share'],2)?></td><td><?=html_escape($r['rule_percent'])?></td><td><?=number_format((float)$r['commission_amount'],2)?></td></tr><?php endforeach?></tbody></table></div>
</div></div></div></div><?php init_tail();?></body></html>