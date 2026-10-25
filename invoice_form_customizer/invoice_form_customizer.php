<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Invoice Form Customizer
Description: Minimal invoice form plus controlled product selection, stock, sale price and discount guardrails.
Version: 0.1.87.4
Requires at least: 3.0.*
*/
define('INVOICE_FORM_CUSTOMIZER_MODULE_NAME','invoice_form_customizer');

/* 0.1.51 - EDIT SAFE SHELL
 * Never render Perfex' crashing GET /admin/invoices/invoice/{id}. Redirect only GET edit
 * requests to the known-good create shell and let IFC hydrate it. POST is left untouched so
 * Perfex' native update pipeline remains authoritative. Create without ifc_edit is unchanged.
 */
function ifc_0151_safe_edit_shell(){
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') return;
 $uri=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'';
 if(preg_match('~/admin/invoices/invoice/(\d+)/?$~',$uri,$m)){
   redirect(admin_url('invoices/invoice?ifc_edit='.(int)$m[1]));
 }
}
hooks()->add_action('admin_init','ifc_0151_safe_edit_shell',1);
hooks()->add_action('app_admin_head','ifc_admin_head_assets');
hooks()->add_action('app_admin_footer','ifc_admin_footer_assets');
/* Historical bulk-import audit: read-only navigation; does not modify other modules. */
hooks()->add_action('admin_init','ifc_bulk_compliance_menu',60);

function ifc_bulk_compliance_menu(){
 if(!has_permission('invoices','','view'))return;
 $CI=&get_instance();
 if(!isset($CI->app_menu))return;
 $CI->app_menu->add_sidebar_children_item('sales',[
  'slug'=>'ifc-bulk-seller-compliance',
  'name'=>'پرونده مغایرت فروشندگان',
  'href'=>admin_url('invoice_form_customizer/bulk_seller_cases'),
  'position'=>99,
 ]);
}

hooks()->add_filter('before_invoice_added','ifc_server_guardrails',50);
hooks()->add_filter('before_invoice_updated','ifc_server_guardrails',50);
function ifc_request_flags(){
 $uri=$_SERVER['REQUEST_URI']??'';
 $is_form=strpos($uri,'/admin/invoices/invoice')!==false;
 $is_list=preg_match('~/admin/invoices(?:\?|$|/?)~',$uri) && !$is_form;
 return [$is_form,$is_list];
}
/* 0.1.45: invoice edit can terminate before Perfex reaches app_admin_footer.
   Load the form customizer from app_admin_head with a jQuery-safe deferred loader,
   so Create/Edit customization is no longer dependent on the footer being rendered. */
function ifc_admin_head_assets(){
 list($is_form,$is_list)=ifc_request_flags();
 if(!$is_form)return;
 echo '<link rel="stylesheet" href="'.module_dir_url(INVOICE_FORM_CUSTOMIZER_MODULE_NAME,'assets/css/invoice-form-customizer.css').'?v=0.1.64">';
 $src=module_dir_url(INVOICE_FORM_CUSTOMIZER_MODULE_NAME,'assets/js/invoice-form-customizer.js').'?v=0.1.64';
 $policy=admin_url('invoice_form_customizer/policy');
 echo '<script>window.InvoiceFormCustomizerConfig={version:"0.1.64",policyUrl:'.json_encode($policy).'};(function(){
var src='.json_encode($src).',loaded=false,loading=false,tries=0;
function jq(){return !!(window.jQuery&&window.jQuery.fn&&typeof window.jQuery.ajax==="function"&&typeof window.jQuery.ajaxSetup==="function");}
function apply(){try{if(window.InvoiceFormCustomizer&&typeof window.InvoiceFormCustomizer.apply==="function")window.InvoiceFormCustomizer.apply();}catch(e){if(window.console)console.warn("IFC apply",e);}}
function load(){if(loaded||loading)return;if(!jq()){wait();return;}loading=true;var x=document.createElement("script");x.src=src;x.async=false;x.onload=function(){loaded=true;loading=false;document.body&&document.body.classList.add("ifc-active");apply();[100,350,800,1600,3000,5500].forEach(function(ms){setTimeout(apply,ms);});};x.onerror=function(){loading=false;setTimeout(wait,150);};(document.head||document.documentElement).appendChild(x);}
function wait(){if(loaded)return;if(jq()){load();return;}tries++;if(tries<400)setTimeout(wait,50);}
wait();document.addEventListener("DOMContentLoaded",wait);
})();</script>';
}
function ifc_admin_footer_assets(){
 list($is_form,$is_list)=ifc_request_flags();
 if(!$is_list)return;
 echo '<link rel="stylesheet" href="'.module_dir_url(INVOICE_FORM_CUSTOMIZER_MODULE_NAME,'assets/css/invoice-form-customizer.css').'?v=0.1.64">';
 echo '<script src="'.module_dir_url(INVOICE_FORM_CUSTOMIZER_MODULE_NAME,'assets/js/invoice-list-customizer.js').'?v=0.1.64"></script>';
 echo '<script>window.IFCBulkPaymentsUrl='.json_encode(admin_url('invoice_form_customizer/bulk_payments')).';window.IFCBulkImportUrl='.json_encode(admin_url('invoice_form_customizer/bulk_import')).';window.IFCBulkDeleteUrl='.json_encode(admin_url('invoice_form_customizer/bulk_delete_invoices')).';window.IFCOperationalStatusesUrl='.json_encode(admin_url('invoice_form_customizer/operational_statuses')).';</script>';
}
function ifc_server_guardrails($data){
 /* Reserved for the future historical importer: bypass stock, repricing and discount caps only within a verified server-side import context. */
 if(!empty($GLOBALS['ifc_verified_historical_import_context'])) return $data;
 if(!is_array($data))return $data;$CI=&get_instance();$st=(int)($data['invoice_type']??$data['sale_type_id']??0);$itemsT=db_prefix().'items';$invT=db_prefix().'inventory_manage';$priceT=db_prefix().'item_sale_type_prices';$discT=db_prefix().'scr_discount_rules';$staff=(int)get_staff_user_id();
 foreach(['newitems','items'] as $bucket){if(empty($data[$bucket])||!is_array($data[$bucket]))continue;foreach($data[$bucket] as $k=>$line){if(!is_array($line))continue;$iid=(int)($line['itemid']??$line['item_id']??0);if(!$iid)continue;
   /* Base item rate is authoritative when no sale type override exists. */$it=$CI->db->where('id',$iid)->get($itemsT)->row_array();$base=0.0;if($it){foreach(['rate','selling_price','c_selling_price'] as $bf){if(array_key_exists($bf,$it)&&$it[$bf]!==null&&$it[$bf]!==''){$base=(float)$it[$bf];break;}}}$finalRate=$base;if($st&&$CI->db->table_exists($priceT)){$p=$CI->db->where('itemid',$iid)->where('sale_type_id',$st)->order_by('id','DESC')->get($priceT)->row();if($p&&$p->price!==null&&$p->price!=='')$finalRate=(float)$p->price;}$data[$bucket][$k]['rate']=$finalRate;
   if($CI->db->table_exists($invT)){$r=$CI->db->select_sum('inventory_number','v')->where('commodity_id',$iid)->get($invT)->row();$stock=$r?(float)$r->v:0;$qty=(float)($line['qty']??0);if($qty>$stock)$data[$bucket][$k]['qty']=$stock;}
   $item=$CI->db->select('group_id')->where('id',$iid)->get($itemsT)->row();if($item&&$CI->db->table_exists($discT)){$invoiceDate=trim((string)($data['date']??''));if($invoiceDate!==''&&function_exists('to_sql_date')){$invoiceDate=to_sql_date($invoiceDate);}if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$invoiceDate)){$invoiceDate=date('Y-m-d');}$rule=$CI->db->where('group_id',(int)$item->group_id)->where('active',1)->where('valid_from <=',$invoiceDate)->where('valid_to >=',$invoiceDate)->order_by('id','DESC')->get($discT)->row();$allowed=0.0;if($rule){$allowed=(float)$rule->base_percent;$a=db_prefix().'scr_manager_discount_authorizations';$h=db_prefix().'scr_sales_hierarchy';$elevated=false;if($CI->db->table_exists($a)){if($CI->db->table_exists($h)){$elevated=(bool)$CI->db->select('a.id')->from($a.' a')->join($h.' h','h.manager_staff_id=a.manager_staff_id AND h.seller_staff_id=a.staff_id','inner')->where('a.staff_id',$staff)->where('a.can_use_max_discount',1)->where('h.active',1)->limit(1)->get()->row();}else{$elevated=(bool)$CI->db->where('staff_id',$staff)->where('can_use_max_discount',1)->limit(1)->get($a)->row();}}if($elevated)$allowed=(float)$rule->max_percent;}foreach($data[$bucket][$k] as $f=>$v){if(stripos($f,'discount')!==false&&is_numeric($v)&&((float)$v>$allowed))$data[$bucket][$k][$f]=$allowed;}}
 }} return $data;
}

/* IFC 0.1.75: first-payment deadline. Does not alter native invoice financial status. */
hooks()->add_action('app_admin_footer','ifc_payment_deadline_sales_settings_section',90);
hooks()->add_action('after_invoice_added','ifc_payment_deadline_track');
hooks()->add_action('after_cron_run','ifc_payment_deadline_reconcile');
hooks()->add_action('after_payment_added','ifc_payment_deadline_payment');
function ifc_payment_deadline_schema(){
 $CI=&get_instance();$t=db_prefix().'ifc_payment_deadline';
 if(!$CI->db->table_exists($t))$CI->db->query("CREATE TABLE IF NOT EXISTS `".$t."` (`invoice_id` INT NOT NULL PRIMARY KEY,`created_at` DATETIME NOT NULL,`deadline_at` DATETIME NOT NULL,`suspended_at` DATETIME NULL,`resumed_at` DATETIME NULL,`is_suspended` TINYINT(1) NOT NULL DEFAULT 0,`first_payment_id` INT NULL,INDEX (`deadline_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function ifc_payment_deadline_days(){ $n=(int)get_option('ifc_first_payment_days');return $n>0?min($n,365):5; }
function ifc_payment_deadline_enabled(){ $v=get_option('ifc_first_payment_enabled');return $v===false||$v===''||$v==='1'; }
/* Render settings inside the existing Sales Settings page (owned by Sales Commercial Rules).
   Do not add sidebar entries or modify the other module. */
function ifc_payment_deadline_sales_settings_section(){
 $path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'';
 if(!preg_match('~/admin/sales_commercial_rules/sales_settings/?$~',$path)||!is_admin())return;
 $days=ifc_payment_deadline_days();$enabled=ifc_payment_deadline_enabled();
 $url=admin_url('invoice_form_customizer/payment_deadline_settings');
 $CI=&get_instance();
 $csrfName=$CI->security->get_csrf_token_name();$csrfHash=$CI->security->get_csrf_hash();
 echo '<section id="ifc-payment-deadline-section" style="display:none;direction:rtl;text-align:right">';
 echo '<hr><h4 style="font-weight:400">مهلت پرداخت فاکتور</h4>';
 echo '<p class="text-muted">فاکتور بدون اولین پرداخت معتبر پس از پایان مهلت تعیین‌شده معلق می‌شود و با اولین پرداخت معتبر به‌صورت خودکار از تعلیق خارج می‌شود.</p>';
 echo '<form method="post" action="'.html_escape($url).'">';
 echo '<input type="hidden" name="'.html_escape($csrfName).'" value="'.html_escape($csrfHash).'">';
 echo '<div class="checkbox"><label><input type="checkbox" name="first_payment_enabled" value="1" '.($enabled?'checked':'').'> فعال‌سازی تعلیق خودکار فاکتور</label></div>';
 echo '<div class="form-group"><label for="ifc-first-payment-days">مهلت ثبت اولین پرداخت (روز تقویمی)</label><input class="form-control" id="ifc-first-payment-days" type="number" min="1" max="365" name="first_payment_days" value="'.(int)$days.'" style="max-width:180px" required></div>';
 echo '<p class="text-muted">پیش‌فرض ۵ روز است. تغییر این مقدار مهلت فاکتورهای قبلی را تغییر نمی‌دهد.</p>';
 echo '<button class="btn btn-primary" type="submit" name="save_payment_deadline" value="1">ذخیره</button></form></section>';
 echo '<script>(function(){function move(){var sec=document.getElementById("ifc-payment-deadline-section");if(!sec)return;var hs=document.querySelectorAll("#wrapper h4");for(var i=0;i<hs.length;i++){if((hs[i].textContent||"").indexOf("دستگاه‌های POS")!==-1){var hr=hs[i].previousElementSibling;if(hr&&hr.tagName==="HR")hr.parentNode.insertBefore(sec,hr);else hs[i].parentNode.insertBefore(sec,hs[i]);sec.style.display="block";return;}}}if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",move);else move();})();</script>';
}
function ifc_payment_deadline_track($invoice_id){
 if(!ifc_payment_deadline_enabled()||!$invoice_id||!empty($GLOBALS['ifc_verified_historical_import_context']))return;
 $CI=&get_instance();ifc_payment_deadline_schema();$t=db_prefix().'ifc_payment_deadline';
 if($CI->db->where('invoice_id',(int)$invoice_id)->count_all_results($t))return;
 $now=date('Y-m-d H:i:s');$end=date('Y-m-d H:i:s',strtotime($now.' +'.ifc_payment_deadline_days().' days'));
 $CI->db->insert($t,['invoice_id'=>(int)$invoice_id,'created_at'=>$now,'deadline_at'=>$end,'is_suspended'=>0]);
}
function ifc_payment_deadline_payment($payment_id){ifc_payment_deadline_reconcile();}
function ifc_payment_deadline_reconcile(){
 if(!ifc_payment_deadline_enabled())return;
 $CI=&get_instance();ifc_payment_deadline_schema();$t=db_prefix().'ifc_payment_deadline';$p=db_prefix().'invoicepaymentrecords';
 if(!$CI->db->table_exists($p))return;
 $rows=$CI->db->select('invoice_id,deadline_at,is_suspended')->get($t)->result_array();$now=date('Y-m-d H:i:s');
 foreach($rows as $r){
  $id=(int)$r['invoice_id'];$invoice=$CI->db->select('id,status')->where('id',$id)->get(db_prefix().'invoices')->row();if(!$invoice)continue;
  $payment=$CI->db->select('id')->where('invoiceid',$id)->where('amount >',0)->order_by('id','ASC')->limit(1)->get($p)->row();
  if($payment){if((int)$r['is_suspended'])$CI->db->where('invoice_id',$id)->update($t,['is_suspended'=>0,'resumed_at'=>$now,'first_payment_id'=>(int)$payment->id]);else $CI->db->where('invoice_id',$id)->where('first_payment_id IS NULL',null,false)->update($t,['first_payment_id'=>(int)$payment->id]);continue;}
  if(in_array((int)$invoice->status,[2,5,6],true))continue;
  if(!(int)$r['is_suspended'] && $r['deadline_at']<=$now)$CI->db->where('invoice_id',$id)->update($t,['is_suspended'=>1,'suspended_at'=>$now]);
 }
}
