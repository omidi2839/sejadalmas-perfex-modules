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
<?php?><div id="wrapper"><div class="content"><h4 class="tw-font-bold tw-text-xl"><?=_l('scr_canceled_commissions')?></h4><div class="panel_s"><div class="panel-body"><p class="text-muted"><?=_l('scr_canceled_commissions_help')?></p><table class="table dt-table"><thead><tr><th>#</th><th><?=_l('scr_invoice_number')?></th><th><?=_l('scr_payment')?></th><th><?=_l('scr_cancel_date')?></th><th><?=_l('scr_staff')?></th><th><?=_l('scr_ranking_role')?></th><th><?=_l('scr_sale_type')?></th><th><?=_l('scr_source_period')?></th><th><?=_l('scr_base_reversal')?></th><th><?=_l('scr_rank_reversal')?></th><th><?=_l('scr_total_reversal')?></th><th><?=_l('scr_applied_period')?></th><th><?=_l('scr_status')?></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=(int)$r['id']?></td><td><?=html_escape($r['invoice_id'])?></td><td><?=html_escape($r['payment_id'])?></td><td><?=html_escape($r['cancel_date'])?></td><td><?=html_escape($r['staff_name']?:$r['staff_id'])?></td><td><?=$r['ranking_role']==='marketer'?_l('scr_role_marketer'):_l('scr_role_seller')?></td><td><?=html_escape($r['sale_type_name']?:$r['sale_type_id'])?></td><td><?=html_escape($r['source_level_name']?:'-')?></td><td><?=html_escape($r['base_commission_reversal'])?></td><td><?=html_escape($r['rank_adjustment_reversal'])?></td><td><strong><?=html_escape($r['total_reversal'])?></strong></td><td><?=html_escape($r['applied_level_name']?:'-')?></td><td><?=$r['status']==='applied'?_l('scr_reversal_applied'):_l('scr_reversal_pending')?></td></tr><?php endforeach?></tbody></table></div></div></div></div><?php init_tail();?></body></html>
