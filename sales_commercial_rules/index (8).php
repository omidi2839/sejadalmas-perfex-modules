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
<?php?><div id="wrapper"><div class="content"><div class="tw-flex tw-justify-between tw-items-center tw-mb-3"><div class="scr-page-heading"><h4><?=_l('scr_rankings')?> <i class="fa fa-bar-chart"></i></h4><small>مدیریت دوره‌ها و گام‌های رتبه‌بندی فروش و مدیران</small></div><a class="btn btn-default" href="<?=admin_url('sales_commercial_rules/ranking_bulk_import')?>"><i class="fa fa-download"></i> ورود تجمیعی از اکسل</a> <button class="btn btn-primary" data-toggle="modal" data-target="#scrRankingModal" onclick="scrRankNew()"><i class="fa fa-plus"></i> <?=_l('scr_add')?></button></div><div class="panel_s"><div class="panel-body scr-rank-panel">
<div class="scr-rank-tools"><label>ماه دوره<select id="scrMonthFilter" class="form-control"><option value="">همه ماه‌ها</option><?php $months=[];foreach($levels as $r){$k=substr($r['valid_from'],0,7);$months[$k]=$k;}krsort($months);foreach($months as $k):?><option value="<?=html_escape($k)?>"><?=html_escape($k)?></option><?php endforeach?></select></label><label>وضعیت<select id="scrStatusFilter" class="form-control"><option value="">همه وضعیت‌ها</option><option value="done">محاسبه‌شده</option><option value="pending">محاسبه‌نشده</option></select></label><label class="scr-rank-search-label">جستجو<input id="scrRankSearch" class="form-control" placeholder="جستجو در دوره‌ها..."></label><span id="scrVisibleCount" class="scr-rank-count"></span></div>
<?php $groups=[];foreach($levels as $r){$month=substr($r['valid_from'],0,7);$groups[$month][]=$r;}krsort($groups);foreach($groups as $month=>$items):?>
<section class="scr-month-group" data-month="<?=html_escape($month)?>"><button type="button" class="scr-month-toggle" aria-expanded="<?=($month===array_key_first($groups))?'true':'false'?>"><span class="scr-month-label"><i class="fa fa-calendar-o"></i> <strong><?=html_escape($month)?></strong> <small class="scr-month-count"><?=count($items)?> دوره</small></span><i class="fa fa-chevron-down scr-chevron"></i></button><div class="scr-month-content" <?=($month===array_key_first($groups))?'':'style="display:none"'?>>
<?php foreach($items as $r): $parts=explode(' - ',$r['name']);$short=count($parts)>1?end($parts):$r['name']; ?>
<div class="scr-period" data-month="<?=html_escape($month)?>" data-status="<?=$r['calculated']?'done':'pending'?>" data-search="<?=html_escape(mb_strtolower($r['name'].' '.$r['valid_from'].' '.$r['valid_to'],'UTF-8'))?>"><div class="scr-period-main"><div class="scr-period-name"><i class="fa fa-calendar-o scr-inline-calendar"></i> <?=html_escape($short)?></div><div class="scr-period-dates"><span class="scr-date"><?=html_escape($r['valid_from'])?></span><span>تا</span><span class="scr-date"><?=html_escape($r['valid_to'])?></span></div></div><span class="scr-period-status <?=$r['calculated']?'is-done':'is-pending'?>"><i class="fa <?=$r['calculated']?'fa-check-circle':'fa-clock-o'?>"></i> <?=$r['calculated']?_l('scr_ranking_calculated_status'):_l('scr_ranking_not_calculated')?></span><div class="scr-period-actions"><a class="btn btn-default btn-sm" href="<?=admin_url('sales_commercial_rules/ranking_details/'.(int)$r['id'])?>"><i class="fa fa-angle-left"></i> جزئیات</a><div class="dropdown scr-period-more"><button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-label="گزینه‌های دوره"><i class="fa fa-ellipsis-h"></i></button><ul class="dropdown-menu dropdown-menu-left"><li><a href="#" class="scr-rank-edit" data-toggle="modal" data-target="#scrRankingModal" data-id="<?=(int)$r['id']?>" data-name="<?=html_escape($r['name'])?>" data-from="<?=html_escape(_d($r['valid_from']))?>" data-to="<?=html_escape(_d($r['valid_to']))?>"><i class="fa fa-pencil"></i> ویرایش</a></li><li><a href="#" class="scr-rank-clone" data-id="<?=(int)$r['id']?>" data-name="<?=html_escape($r['name'])?>" data-from="<?=html_escape(_d($r['valid_from']))?>" data-to="<?=html_escape(_d($r['valid_to']))?>"><i class="fa fa-copy"></i> کپی دوره و گام‌ها</a></li><li><form method="post" action="<?=admin_url('sales_commercial_rules/delete_ranking/'.(int)$r['id'])?>" onsubmit="return confirm(<?=html_escape(json_encode(_l('scr_delete_ranking_confirm'),JSON_UNESCAPED_UNICODE))?>)"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><button type="submit" class="scr-delete-action"><i class="fa fa-trash"></i> حذف دوره</button></form></li></ul></div></div></div>
<?php endforeach?></div></section><?php endforeach?>
<div id="scrRankEmpty" class="scr-rank-empty" style="display:none">دوره‌ای مطابق فیلترها یافت نشد.</div>
</div></div></div></div>
<div class="modal fade" id="scrCloneRankingModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post" id="scrCloneRankingForm" action=""><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">کپی دوره و تمام گام‌های زیرمجموعه</h4></div><div class="modal-body"><p class="text-muted">عنوان و تاریخ‌های دوره جدید را بررسی کنید. نتایج محاسبه و پرداخت‌ها کپی نمی‌شوند.</p><?=render_input('name','عنوان دوره جدید','','text',['required'=>true])?><?=render_date_input('valid_from','scr_from','',['required'=>true])?><?=render_date_input('valid_to','scr_to','',['required'=>true])?></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">انصراف</button><button type="submit" class="btn btn-primary">ثبت کپی دوره</button></div></form></div></div></div><div class="modal fade" id="scrRankingModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post" action="<?=admin_url('sales_commercial_rules/rankings')?>"><?=form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash())?><input type="hidden" name="id" value="0"><div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title" id="scrRankTitle"><?=_l('scr_add')?></h4></div><div class="modal-body"><?=render_input('name','scr_ranking_title','','text',['required'=>true])?><?=render_date_input('valid_from','scr_from','',['required'=>true])?><?=render_date_input('valid_to','scr_to','',['required'=>true])?></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal"><?=_l('scr_cancel_edit')?></button><button class="btn btn-primary"><?=_l('scr_saved')?></button></div></form></div></div></div>
<style>
.scr-rank-panel{padding:22px!important}.scr-rank-tools{display:flex;gap:14px;align-items:end;flex-wrap:wrap;margin-bottom:22px}.scr-rank-tools label{font-weight:400;min-width:180px;flex:1;max-width:260px;color:#566477;font-size:12px}.scr-rank-tools .form-control{margin-top:7px;border-radius:7px}.scr-rank-count{margin-right:auto;color:#8491a2;font-size:12px;padding-bottom:10px}.scr-month-group{border:1px solid #e5eaf0;border-radius:10px;margin:0 0 12px;overflow:visible;background:#fff}.scr-month-toggle{width:100%;border:0;background:#f8fafc;padding:15px 18px;text-align:right;display:flex;justify-content:space-between;align-items:center;color:#29364a;font-weight:500;border-radius:10px}.scr-month-toggle .fa-calendar-o{color:#718198;margin-left:9px}.scr-month-count{color:#8995a4;font-weight:400;margin-right:12px}.scr-chevron{transition:transform .2s;color:#8794a6}.scr-month-toggle[aria-expanded=true] .scr-chevron{transform:rotate(180deg)}.scr-month-content{padding:0 16px}.scr-period{display:flex;align-items:center;gap:18px;padding:14px 5px;border-top:1px solid #edf0f4;min-height:65px}.scr-period-main{flex:1;min-width:0}.scr-period-name{font-size:13px;color:#25364b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.scr-period-dates{font-size:11px;color:#8894a3;margin-top:6px;direction:ltr;text-align:right}.scr-period-dates span{margin:0 7px}.scr-period-status{font-size:11px;white-space:nowrap;padding:5px 9px;border-radius:6px}.scr-period-status.is-done{background:#edf7f1;color:#35815b}.scr-period-status.is-pending{background:#f3f5f7;color:#798696}.scr-period-actions{display:flex;align-items:center;gap:6px}.scr-period-actions .btn{border-radius:7px;font-size:12px}.scr-period-more{position:relative}.scr-period-more .dropdown-menu{right:auto;left:0;min-width:130px}.scr-delete-action{display:block;border:0;background:none;width:100%;text-align:right;padding:7px 18px;color:#ba4751}.scr-rank-empty{text-align:center;padding:30px;color:#8b97a6}@media(max-width:650px){.scr-rank-panel{padding:12px!important}.scr-period{flex-wrap:wrap;gap:9px}.scr-period-main{flex-basis:100%}.scr-period-actions{margin-right:auto}.scr-rank-tools label{max-width:none;min-width:140px}}
</style>
<style id="scr-ranking-visual-61">
/* Ranking dashboard visual system – 0.7.6.9.7.61 */
#wrapper .content .scr-page-heading{margin-right:auto;text-align:right}
.scr-page-heading h4{font-size:21px;font-weight:600!important;color:#182b40;margin:0 0 5px}
.scr-page-heading h4 i{color:#ff8218;margin-right:7px}
.scr-page-heading small{font-size:12px;color:#8190a4}
#wrapper .content .scr-rank-panel{padding:17px!important;background:#fff}
.scr-rank-tools{background:#fffaf5!important;border:1px solid #f6dcc7!important;padding:15px 18px!important;border-radius:11px!important;margin-bottom:12px!important;display:flex!important;align-items:end!important;gap:18px!important;direction:rtl}
.scr-rank-tools label{min-width:145px!important;max-width:220px!important;flex:1;font-size:12px!important;color:#3c4b5f!important}
.scr-rank-tools .scr-rank-search-label{max-width:310px!important;flex:1.5}
.scr-rank-tools .form-control{height:38px;border:1px solid #f1d7c0;border-radius:9px!important;background:white}
.scr-rank-count{margin-right:auto!important;margin-left:0!important;color:#4e6179!important;white-space:nowrap}
.scr-month-group{border:1px solid #e2e8ee!important;border-radius:11px!important;margin-bottom:10px!important;overflow:hidden!important}
.scr-month-toggle{padding:12px 19px!important;background:#f8fafc!important;min-height:47px!important;direction:rtl}
.scr-month-toggle[aria-expanded=true]{background:#fff3e8!important;border-bottom:1px solid #fbe2cb!important}
.scr-month-label{display:flex;gap:10px;align-items:center;font-size:17px}
.scr-month-label strong{font-weight:600;color:#172d45}
.scr-month-label .fa-calendar-o{display:inline-flex;align-items:center;justify-content:center;background:#ff861d;color:#fff!important;width:25px;height:25px;border-radius:50%;font-size:13px}
.scr-month-count{background:#f0f3f7;border-radius:20px;padding:5px 12px;margin:0!important;color:#63758a!important;font-size:12px}
.scr-month-toggle[aria-expanded=true] .scr-month-count{background:#ffead7;color:#ec730d!important}
.scr-month-content{padding:0 17px!important}
.scr-period{direction:rtl!important;display:flex!important;gap:16px!important;min-height:55px!important;padding:9px 5px!important}
.scr-period-main{flex:1!important;display:flex;align-items:center;justify-content:space-between;gap:15px;direction:rtl}
.scr-period-name{font-size:13px!important;font-weight:500;color:#182b40!important;white-space:normal!important}
.scr-inline-calendar{color:#ff8218;margin-left:8px}
.scr-period-dates{display:flex!important;align-items:center!important;gap:10px!important;direction:rtl!important;white-space:nowrap;font-size:12px!important;margin:0!important;color:#677b92!important}
.scr-period-dates span{margin:0!important}.scr-period-dates .scr-date{direction:ltr!important;unicode-bidi:isolate}
.scr-period-status{border-radius:30px!important;padding:7px 13px!important;font-size:11px!important}
.scr-period-status i{margin-left:5px}
.scr-period-status.is-done{background:#e4f7ec!important;color:#11844d!important}
.scr-period-status.is-pending{background:#f2f4f7!important;color:#637287!important}
.scr-period-actions{direction:rtl;min-width:120px;justify-content:flex-end}
.scr-period-actions>a{color:#f47a16!important;border-color:#f47a16!important;background:#fff!important;padding:6px 14px!important}
.scr-period-more .btn{border-color:#d9e0e8!important;color:#293c53!important}
@media(max-width:1000px){.scr-period-main{display:block}.scr-period-dates{margin-top:6px!important}.scr-rank-tools{flex-wrap:wrap!important}}
@media(max-width:650px){.scr-period{flex-wrap:wrap!important}.scr-period-main{flex-basis:100%!important}.scr-rank-tools label{min-width:130px!important}.scr-page-heading small{display:none}}
</style>
<?php init_tail();?><script>jQuery(function($){function filterRanks(){var m=$('#scrMonthFilter').val(),s=$('#scrStatusFilter').val(),q=($('#scrRankSearch').val()||'').toLowerCase().trim(),count=0;$('.scr-month-group').each(function(){var g=$(this),visible=0;g.find('.scr-period').each(function(){var r=$(this),ok=(!m||r.attr('data-month')===m)&&(!s||r.attr('data-status')===s)&&(!q||(r.attr('data-search')||'').toLowerCase().indexOf(q)>=0);r.toggle(ok);if(ok){count++;visible++}});g.toggle(visible>0);g.find('.scr-month-count').text(visible+' دوره');if(m||s||q){g.find('.scr-month-content').show();g.find('.scr-month-toggle').attr('aria-expanded','true')}});$('#scrVisibleCount').text(count+' دوره');$('#scrRankEmpty').toggle(count===0)}$('#scrMonthFilter,#scrStatusFilter').on('change',filterRanks);$('#scrRankSearch').on('input',filterRanks);$('.scr-month-toggle').on('click',function(){var b=$(this),expanded=b.attr('aria-expanded')==='true';b.attr('aria-expanded',expanded?'false':'true');b.next('.scr-month-content').slideToggle(150)});filterRanks()});init_datepicker();function scrRankNew(){var m=jQuery('#scrRankingModal');m.find('form')[0].reset();m.find('[name=id]').val(0);jQuery('#scrRankTitle').text('<?=addslashes(_l('scr_add'))?>')}jQuery('.scr-rank-edit').on('click',function(){var b=jQuery(this),m=jQuery('#scrRankingModal');m.find('[name=id]').val(b.data('id'));m.find('[name=name]').val(b.data('name'));m.find('[name=valid_from]').val(b.data('from'));m.find('[name=valid_to]').val(b.data('to'));jQuery('#scrRankTitle').text('<?=addslashes(_l('scr_edit'))?>')});
// Menus are positioned against the viewport rather than clipped by month containers.
(function($){var active=null,holder=null;
 function closeMenu(){if(active){active.hide().appendTo(holder);active.css({position:'',top:'',left:'',right:'',zIndex:''});active=null;holder=null;}}
 $('.scr-period-more .dropdown-toggle').attr('data-toggle','').off('click').on('click',function(e){e.preventDefault();e.stopPropagation();var button=$(this),menu=button.siblings('.dropdown-menu');if(active&&active[0]===menu[0]){closeMenu();return;}closeMenu();holder=menu.parent();active=menu;menu.appendTo('body').css({display:'block',position:'fixed',visibility:'hidden',zIndex:11000,right:'auto',left:'auto'});var rect=this.getBoundingClientRect(),w=menu.outerWidth(),h=menu.outerHeight(),top=rect.bottom+4;if(top+h>window.innerHeight-8)top=Math.max(8,rect.top-h-4);var left=Math.max(8,Math.min(rect.right-w,window.innerWidth-w-8));menu.css({top:top+'px',left:left+'px',visibility:'visible'});});
 $(document).on('click.scrRankMenu',function(e){if(active&&!$(e.target).closest(active).length)closeMenu();});$(window).on('scroll.scrRankMenu resize.scrRankMenu',closeMenu);
 $(document).on('click','.scr-rank-clone',function(e){e.preventDefault();var b=$(this),f=$('#scrCloneRankingForm');f.attr('action',<?=json_encode(admin_url('sales_commercial_rules/clone_ranking/'))?>+b.attr('data-id'));f.find('[name=name]').val(b.attr('data-name')+' - کپی');f.find('[name=valid_from]').val(b.attr('data-from'));f.find('[name=valid_to]').val(b.attr('data-to'));closeMenu();$('#scrCloneRankingModal').modal('show');});
})(jQuery);
</script></body></html>
