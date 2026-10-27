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
<?php?><div id="wrapper"><div class="content"><div class="tw-flex tw-justify-between tw-items-center tw-mb-3"><h4 class="tw-font-bold tw-text-xl"><?=_l('scr_commissions')?></h4><button class="btn btn-primary" data-toggle="modal" data-target="#scrCommissionModal" onclick="scrComNew()"><i class="fa fa-plus"></i> <?=_l('scr_add')?></button></div><div class="panel_s"><div class="panel-body"><table class="table dt-table"><thead><tr><th>#</th><th><?=_l('scr_group')?></th><th><?=_l('scr_sale_type')?></th><th><?=_l('scr_seller_percent')?></th><th><?=_l('scr_marketer_percent')?></th><th><?=_l('scr_options')?></th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=(int)$r['id']?></td><td><?=html_escape($r['group_name']?:'-')?></td><td><?=html_escape($r['sale_type_name']?:'-')?></td><td><?=html_escape($r['seller_percent'])?></td><td><?=html_escape($r['marketer_percent'])?></td><td><button class="btn btn-default btn-icon scr-com-edit" data-toggle="modal" data-target="#scrCommissionModal" data-id="<?=(int)$r['id']?>" data-group="<?=(int)$r['group_id']?>" data-type="<?=(int)$r['sale_type_id']?>" data-seller="<?=html_escape($r['seller_percent'])?>" data-marketer="<?=html_escape($r['marketer_percent'])?>"><i class="fa fa-pencil"></i></button> <form method="post" action="<?=admin_url('sales_commercial_rules/delete_commission/'.(int)$r['id'])?>" style="display:inline"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><button class="btn btn-danger btn-icon"><i class="fa fa-trash"></i></button></form></td></tr><?php endforeach?></tbody></table></div></div></div></div><div class="modal fade" id="scrCommissionModal"><div class="modal-dialog"><div class="modal-content"><form method="post"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0"><div class="modal-header"><button class="close" type="button" data-dismiss="modal">&times;</button><h4 class="modal-title"><?=_l('scr_commissions')?></h4></div><div class="modal-body"><?=render_select('group_id',$groups,['id','name'],'scr_group','',['required'=>true])?><?=render_select('sale_type_id',$types,['legacy_id','name'],'scr_sale_type','',['required'=>true])?><?=render_input('seller_percent','scr_seller_percent','','number',['step'=>'0.001','min'=>0,'max'=>100,'required'=>true])?><?=render_input('marketer_percent','scr_marketer_percent','','number',['step'=>'0.001','min'=>0,'max'=>100,'required'=>true])?></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=_l('scr_cancel_edit')?></button><button class="btn btn-primary"><?=_l('scr_saved')?></button></div></form></div></div></div><?php init_tail();?><script>init_selectpicker();function scrComNew(){var m=jQuery('#scrCommissionModal');m.find('form')[0].reset();m.find('[name=id]').val(0);m.find('select').val('').selectpicker('refresh')}jQuery('.scr-com-edit').click(function(){var b=jQuery(this),m=jQuery('#scrCommissionModal');m.find('[name=id]').val(b.data('id'));m.find('[name=group_id]').val(b.data('group')).selectpicker('refresh');m.find('[name=sale_type_id]').val(b.data('type')).selectpicker('refresh');m.find('[name=seller_percent]').val(b.data('seller'));m.find('[name=marketer_percent]').val(b.data('marketer'))});</script></body></html>