<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: قواعد تجاری فروش
Description: ماژول مستقل انواع فروش، قیمت نوع فروش و تخفیف گروه کالایی
Version: 0.7.6.9.7.75
Requires at least: 2.3.*
Author: Custom Development
*/
define('SCR_MODULE_NAME','sales_commercial_rules');
register_activation_hook(SCR_MODULE_NAME,'scr_activation_hook');
register_language_files(SCR_MODULE_NAME,[SCR_MODULE_NAME]);
hooks()->add_action('admin_init','scr_admin_init');
hooks()->add_action('admin_init','scr_register_cashier_permissions');
hooks()->add_action('before_render_invoice_template','scr_invoice_fields');
hooks()->add_action('app_admin_footer','scr_invoice_footer');
hooks()->add_action('app_admin_footer','scr_sales_menu_cleanup_footer',999);
hooks()->add_action('app_admin_footer','scr_payment_delete_route_footer',1001);
hooks()->add_filter('before_invoice_added','scr_enforce_sale_price');
hooks()->add_filter('before_update_invoice','scr_enforce_sale_price',20,2);
// Compatibility only for older/custom builds that used the non-core tag.
hooks()->add_filter('before_invoice_updated','scr_enforce_sale_price');
hooks()->add_action('after_payment_added','scr_block_returned_invoice_payment',1);
hooks()->add_action('after_payment_added','scr_capture_cashier_payment',20);
hooks()->add_action('after_payment_updated','scr_sync_cashier_payment_updated');
// 0.7.6.9.7.31: persist the commercial price into native tblitemable.rate.
hooks()->add_action('after_invoice_added','scr_repair_native_invoice_rates_from_commercial_price',2);
hooks()->add_action('invoice_updated','scr_repair_native_invoice_rates_after_update',2);
hooks()->add_action('admin_init','scr_repair_legacy_zero_rate_invoices',40);
function scr_activation_hook(){ $CI=&get_instance(); require_once __DIR__.'/install.php'; }
function scr_old_module_active(){ $CI=&get_instance(); return isset($CI->app_modules) && $CI->app_modules->is_active('item_discount'); }

function scr_register_cashier_permissions(){
 if(!function_exists('register_staff_capabilities')) return;
 register_staff_capabilities('scr_cashier',[
  'name'=>_l('scr_cashier_permissions'),
  'capabilities'=>[
   'view_own'=>_l('scr_perm_cashier_own'),
   'review'=>_l('scr_perm_accountant_review'),
   'settings'=>_l('scr_perm_cashier_settings'),
  ],
 ]);
}
function scr_cashier_can_own(){return is_admin() || (function_exists('has_permission') && has_permission('scr_cashier','','view_own'));}
function scr_cashier_can_review(){return is_admin() || (function_exists('has_permission') && has_permission('scr_cashier','','review'));}
function scr_cashier_can_settings(){return is_admin() || (function_exists('has_permission') && has_permission('scr_cashier','','settings'));}

function scr_admin_init(){
 scr_ensure_phase2_schema();
 scr_ensure_phase3_schema();
 scr_ensure_phase4_schema();
 scr_ensure_commission_center_schema();
 scr_ensure_cashier_schema();
 scr_ensure_full_legacy_migration();
 $CI=&get_instance();
 $CI->app_menu->add_sidebar_menu_item('scr',['name'=>_l('scr_title'),'icon'=>'fa fa-tags','position'=>31]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-sale-types','name'=>_l('scr_sale_types'),'href'=>admin_url('sales_commercial_rules/sale_types'),'position'=>1]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-discounts','name'=>_l('scr_discounts'),'href'=>admin_url('sales_commercial_rules/discounts'),'position'=>2]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-hierarchy','name'=>'زیرمجموعه فروش','href'=>admin_url('sales_commercial_rules/hierarchy'),'position'=>3]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-access','name'=>_l('scr_access'),'href'=>admin_url('sales_commercial_rules/access'),'position'=>4]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-commissions','name'=>'کمیسیون','href'=>admin_url('sales_commercial_rules/commission_center'),'position'=>5]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-rankings','name'=>_l('scr_rankings'),'href'=>admin_url('sales_commercial_rules/rankings'),'position'=>6]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-online-promotion','name'=>'قواعد ارتقاء آنلاین','href'=>admin_url('sales_commercial_rules/online_promotion'),'position'=>8]);
 $CI->app_menu->add_sidebar_children_item('scr',['slug'=>'scr-monthly-goals','name'=>'اهداف فروش دوره‌ای','href'=>admin_url('sales_commercial_rules/monthly_goals'),'position'=>7]);
 // 0.7.1: Daily cashier handover is an operational Sales function, not a Commercial Rules setting.
 // Register it under Perfex's native Sales menu.
 if(scr_cashier_can_own()||scr_cashier_can_review()||scr_cashier_can_settings()) $CI->app_menu->add_sidebar_children_item('sales',['slug'=>'scr-cashier','name'=>_l('scr_cashier_daily'),'href'=>admin_url('sales_commercial_rules/cashier'),'position'=>90]);
 // 0.7.6.9.7.29: Delivery operations are part of the fixed Sales navigation.
 // Do not gate these items behind app_modules->is_active() during admin_init: on this installation
 // that runtime check is false/too early even though Sales Delivery Flow is installed and active,
 // which was the root cause of the missing menu items in 0.7.6.9.7.27.
 $CI->app_menu->add_sidebar_children_item('sales',['slug'=>'sdf-exit-shipping','name'=>'خروج و ارسال کالا','href'=>admin_url('sales_delivery_flow'),'position'=>92]);
 $CI->app_menu->add_sidebar_children_item('sales',['slug'=>'sdf-product-returns','name'=>'مرجوعی کالا','href'=>admin_url('sales_delivery_flow/returns'),'position'=>93]);
 if(is_admin()||scr_cashier_can_settings()) $CI->app_menu->add_sidebar_children_item('sales',['slug'=>'scr-sales-settings','name'=>'تنظیمات فروش','href'=>admin_url('sales_commercial_rules/sales_settings'),'position'=>94]);
 if(scr_cashier_can_own()||scr_cashier_can_review()) $CI->app_menu->add_sidebar_children_item('sales',['slug'=>'scr-sales-requests','name'=>'دریافتی از فروشندگان','href'=>admin_url('sales_commercial_rules/sales_requests'),'position'=>89]);
 // 0.7.6.3: Keep Sales focused on the operational sales cycle. Hide native entries that are not used here, and expose Sales Contracts under Sales.
 if(method_exists($CI->app_menu,'remove_sidebar_children_item')){
  foreach(['proposals','proposal','credit_notes','credit-notes','creditnotes','items','products','subscriptions','subscription'] as $scrSalesChild){
   $CI->app_menu->remove_sidebar_children_item('sales',$scrSalesChild);
  }
 }
 if(is_admin() || (function_exists('has_permission') && has_permission('contracts','','view')) || (function_exists('has_permission') && has_permission('contracts','','view_own'))){
  $CI->app_menu->add_sidebar_children_item('sales',['slug'=>'scr-sales-contracts','name'=>'قراردادهای فروش','href'=>admin_url('contracts'),'position'=>91]);
 }
}


// 0.7.6.4: Perfex builds some native Sales children after module admin_init on some installations.
// Therefore server-side removal alone can run too early. Clean the rendered Sales submenu as a final UI pass.
function scr_sales_menu_cleanup_footer(){
 if(!is_staff_logged_in()) return;
 ?>
 <script>
 (function(){
   function normalizePath(href){
     try { return new URL(href, window.location.origin).pathname.replace(/\/+$/,'').toLowerCase(); }
     catch(e){ return String(href||'').split('?')[0].replace(/\/+$/,'').toLowerCase(); }
   }
   function cleanupSalesMenu(){
     var blocked = [
       '/admin/proposals',
       '/admin/credit_notes',
       '/admin/invoice_items',
       '/admin/subscriptions'
     ];
     document.querySelectorAll('#side-menu a[href], .sidebar-menu a[href], aside a[href]').forEach(function(a){
       var p = normalizePath(a.getAttribute('href'));
       if(!blocked.some(function(x){ return p === x || p.endsWith(x); })) return;
       var li = a.closest('li');
       if(li) li.remove(); else a.remove();
     });
   }
   if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', cleanupSalesMenu);
   else cleanupSalesMenu();
   setTimeout(cleanupSalesMenu, 300);
   setTimeout(cleanupSalesMenu, 1200);
 })();
 </script>
 <?php
}

// 0.7.6.9.7.26: On the native Payments list only, route delete links through a controlled SCR endpoint.
// The installation deletes the native payment row successfully but then another post-delete path throws HTTP 500
// before Perfex can redirect. This keeps the scope narrow and does not alter payment create/update/POS flows.
function scr_payment_delete_route_footer(){
 if(!is_staff_logged_in())return;
 ?>
 <script>
 (function(){
   function patchPaymentDeleteLinks(){
     var path=(window.location.pathname||'').replace(/\/+$/,'').toLowerCase();
     if(path!=='/admin/payments')return;
     document.querySelectorAll('a[href*="/admin/payments/delete/"]').forEach(function(a){
       var href=a.getAttribute('href')||'';
       var m=href.match(/\/admin\/payments\/delete\/(\d+)/i);
       if(!m)return;
       a.setAttribute('href', <?php echo json_encode(admin_url('sales_commercial_rules/delete_payment_safe/')); ?> + m[1]);
     });
   }
   if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',patchPaymentDeleteLinks);else patchPaymentDeleteLinks();
   setTimeout(patchPaymentDeleteLinks,250);setTimeout(patchPaymentDeleteLinks,1000);
   if(window.MutationObserver){new MutationObserver(patchPaymentDeleteLinks).observe(document.body,{childList:true,subtree:true});}
 })();
 </script>
 <?php
}

function scr_ensure_cashier_schema(){
 $CI=&get_instance();$p=db_prefix();
 $s=$p.'scr_cashier_sessions';if(!$CI->db->table_exists($s))$CI->db->query("CREATE TABLE `{$s}` (`id` int NOT NULL AUTO_INCREMENT,`staff_id` int NOT NULL,`business_date` date NOT NULL,`status` varchar(20) NOT NULL DEFAULT 'open',`expected_cash` decimal(18,2) NOT NULL DEFAULT 0,`actual_cash` decimal(18,2) NULL,`cash_difference` decimal(18,2) NULL,`cashier_note` text NULL,`accountant_note` text NULL,`submitted_at` datetime NULL,`approved_by` int NULL,`approved_at` datetime NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `staff_date` (`staff_id`,`business_date`),KEY `status` (`status`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 $l=$p.'scr_cashier_payment_links';if(!$CI->db->table_exists($l))$CI->db->query("CREATE TABLE `{$l}` (`id` int NOT NULL AUTO_INCREMENT,`payment_id` int NOT NULL,`invoice_id` int NOT NULL DEFAULT 0,`staff_id` int NOT NULL,`session_id` int NOT NULL,`payment_mode` varchar(100) NULL,`amount` decimal(18,2) NOT NULL DEFAULT 0,`payment_date` date NULL,`created_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `payment_id` (`payment_id`),KEY `session_id` (`session_id`),KEY `staff_date` (`staff_id`,`payment_date`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 $m=$p.'scr_payment_mode_categories';if(!$CI->db->table_exists($m))$CI->db->query("CREATE TABLE `{$m}` (`id` int NOT NULL AUTO_INCREMENT,`payment_mode` varchar(100) NOT NULL,`category` varchar(20) NOT NULL DEFAULT 'other',`account_label` varchar(255) NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `payment_mode` (`payment_mode`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 $pd=$p.'scr_pos_devices';if(!$CI->db->table_exists($pd))$CI->db->query("CREATE TABLE `{$pd}` (`id` int NOT NULL AUTO_INCREMENT,`name` varchar(255) NOT NULL,`active` tinyint NOT NULL DEFAULT 1,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),KEY `active` (`active`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 if($CI->db->table_exists($l)&&!$CI->db->field_exists('pos_device_id',$l))$CI->db->query("ALTER TABLE `{$l}` ADD `pos_device_id` INT NOT NULL DEFAULT 0 AFTER `payment_mode`");
 if(!$CI->db->field_exists('discrepancy_type',$s))$CI->db->query("ALTER TABLE `{$s}` ADD `discrepancy_type` VARCHAR(50) NULL AFTER `accountant_note`");
 if(!$CI->db->field_exists('discrepancy_amount',$s))$CI->db->query("ALTER TABLE `{$s}` ADD `discrepancy_amount` DECIMAL(18,2) NULL AFTER `discrepancy_type`");
 if(!$CI->db->field_exists('cashier_response',$s))$CI->db->query("ALTER TABLE `{$s}` ADD `cashier_response` TEXT NULL AFTER `discrepancy_amount`");
 if(!$CI->db->field_exists('resubmitted_at',$s))$CI->db->query("ALTER TABLE `{$s}` ADD `resubmitted_at` DATETIME NULL AFTER `cashier_response`");
 if(!$CI->db->field_exists('closed_with_discrepancy',$s))$CI->db->query("ALTER TABLE `{$s}` ADD `closed_with_discrepancy` TINYINT NOT NULL DEFAULT 0 AFTER `approved_at`");
 $a=$p.'scr_cashier_audit';if(!$CI->db->table_exists($a))$CI->db->query("CREATE TABLE `{$a}` (`id` int NOT NULL AUTO_INCREMENT,`session_id` int NOT NULL,`staff_id` int NOT NULL,`action` varchar(50) NOT NULL,`from_status` varchar(30) NULL,`to_status` varchar(30) NULL,`note` text NULL,`amount` decimal(18,2) NULL,`created_at` datetime NOT NULL,PRIMARY KEY (`id`),KEY `session_id` (`session_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");

 $rq=$p.'sps_sales_requests';if(!$CI->db->table_exists($rq))$CI->db->query("CREATE TABLE `{$rq}` (`id` int NOT NULL AUTO_INCREMENT,`seller_staff_id` int NOT NULL,`client_id` int NOT NULL DEFAULT 0,`sale_type_id` int NOT NULL DEFAULT 0,`status` varchar(30) NOT NULL DEFAULT 'submitted',`items_json` longtext NOT NULL,`seller_note` text NULL,`cashier_note` text NULL,`cashier_staff_id` int NULL,`target_type` varchar(20) NULL,`target_id` int NULL,`created_at` datetime NOT NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),KEY `status` (`status`),KEY `seller_staff_id` (`seller_staff_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
}
function scr_block_returned_invoice_payment($payload){
 // 0.7.6.9.7.30 — hard server-side safety net. A returned invoice must not accept new money.
 // Native Perfex payment controller is encoded on this installation, so enforce at the first reliable native hook.
 try{
  $payment_id=scr_payment_hook_id($payload);if(!$payment_id)return;
  $CI=&get_instance();$pt=db_prefix().'invoicepaymentrecords';$rt=db_prefix().'sdf_returns';if(!$CI->db->table_exists($pt)||!$CI->db->table_exists($rt))return;
  $p=$CI->db->where('id',(int)$payment_id)->get($pt)->row_array();if(!$p)return;$invoiceId=(int)($p['invoiceid']??0);if(!$invoiceId)return;
  if($CI->db->where('invoice_id',$invoiceId)->count_all_results($rt)<=0)return;
  $CI->db->where('id',(int)$payment_id)->delete($pt);
  $lt=db_prefix().'scr_cashier_payment_links';if($CI->db->table_exists($lt))$CI->db->where('payment_id',(int)$payment_id)->delete($lt);
  if(isset($CI->invoices_model)&&method_exists($CI->invoices_model,'update_invoice_status'))$CI->invoices_model->update_invoice_status($invoiceId,true);
  elseif(method_exists($CI,'load')){try{$CI->load->model('invoices_model');if(method_exists($CI->invoices_model,'update_invoice_status'))$CI->invoices_model->update_invoice_status($invoiceId,true);}catch(Throwable $e){}}
  if(function_exists('set_alert'))set_alert('warning','این فاکتور دارای مرجوعی است و تا تعیین تکلیف تسویه مالی مرجوعی، دریافت وجه جدید مجاز نیست.');
  if(function_exists('log_message'))log_message('error','SCR blocked and removed payment '.$payment_id.' for returned invoice '.$invoiceId);
 }catch(Throwable $e){if(function_exists('log_message'))log_message('error','SCR return payment guard: '.$e->getMessage());}
}

function scr_capture_cashier_payment($payload){
 // 0.7.6.9.7.14: Never allow the auxiliary cashier/POS capture to break Perfex's native payment save.
 // Different Perfex builds pass either a numeric payment id or an array/object payload to this hook.
 try{
  $payment_id=scr_payment_hook_id($payload);
  if(!$payment_id||!is_staff_logged_in())return;
  scr_ensure_cashier_schema();$CI=&get_instance();$CI->load->model('sales_commercial_rules/Sales_commercial_rules_model','scr_cashier_model');
  // 0.7.6.9.7.21: Native Perfex payment AJAX can bypass the form submit event, so the hidden POS field may not reach POST.
  // Keep the selected device in a short-lived same-site cookie as a capture-only fallback; native payment flow remains untouched.
  $pos_hint=(int)($CI->input->post('scr_pos_device_id')??0);
  if($pos_hint<=0 && isset($_COOKIE['scr_pos_device_hint'])) $pos_hint=(int)$_COOKIE['scr_pos_device_hint'];
  $CI->scr_cashier_model->capture_payment((int)$payment_id,(int)get_staff_user_id(),$pos_hint);
  if(isset($_COOKIE['scr_pos_device_hint'])){setcookie('scr_pos_device_hint','',time()-3600,'/');unset($_COOKIE['scr_pos_device_hint']);}
 }catch(Throwable $e){
  if(function_exists('log_message'))log_message('error','SCR cashier capture after payment: '.$e->getMessage());
 }
}
function scr_payment_hook_id($payload){if(is_numeric($payload))return (int)$payload;if(is_array($payload)){foreach(['paymentid','payment_id','id'] as $k)if(isset($payload[$k]))return (int)$payload[$k];}if(is_object($payload)){foreach(['paymentid','payment_id','id'] as $k)if(isset($payload->$k))return (int)$payload->$k;}return 0;}
function scr_sync_cashier_payment_updated($payload){$id=scr_payment_hook_id($payload);if(!$id)return;scr_ensure_cashier_schema();$CI=&get_instance();$CI->load->model('sales_commercial_rules/Sales_commercial_rules_model','scr_cashier_model');$CI->scr_cashier_model->sync_payment_update($id);}
function scr_sync_cashier_payment_deleted($payload){
 // 0.7.6.9.7.23: Keep the native Perfex delete request completely lightweight.
 // The payment is already deleted when this hook runs; only remove its cashier link.
 // Do not run schema migration, model loading, audit/discrepancy logic or redirects inside this native hook.
 try{
  $id=scr_payment_hook_id($payload);if(!$id)return;
  $CI=&get_instance();
  $lt=db_prefix().'scr_cashier_payment_links';
  if($CI->db->table_exists($lt))$CI->db->where('payment_id',(int)$id)->delete($lt);
 }catch(Throwable $e){
  if(function_exists('log_message'))log_message('error','SCR lightweight cashier unlink after payment delete: '.$e->getMessage());
 }
}
function scr_ensure_phase2_schema(){
 $CI=&get_instance();
 $h=db_prefix().'scr_sales_hierarchy';
 if(!$CI->db->table_exists($h))$CI->db->query("CREATE TABLE `{$h}` (`id` int NOT NULL AUTO_INCREMENT,`manager_staff_id` int NOT NULL,`seller_staff_id` int NOT NULL,`active` tinyint NOT NULL DEFAULT 1,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `manager_seller` (`manager_staff_id`,`seller_staff_id`),UNIQUE KEY `legacy_id` (`legacy_id`),KEY `seller` (`seller_staff_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 $a=db_prefix().'scr_discount_access';
 if(!$CI->db->table_exists($a))$CI->db->query("CREATE TABLE `{$a}` (`id` int NOT NULL AUTO_INCREMENT,`manager_staff_id` int NOT NULL DEFAULT 0,`staff_id` int NOT NULL,`can_edit_line_discount` tinyint NOT NULL DEFAULT 0,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `manager_staff` (`manager_staff_id`,`staff_id`),KEY `staff_id` (`staff_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 elseif(!$CI->db->field_exists('manager_staff_id',$a)){$CI->db->query("ALTER TABLE `{$a}` ADD `manager_staff_id` INT NOT NULL DEFAULT 0 AFTER `id`");}
 $old=db_prefix().'item_discount_set_middle_staff';
 if($CI->db->table_exists($old))foreach($CI->db->get($old)->result() as $r){$legacy=(int)($r->middle_id??0);$m=(int)($r->middle_manager??0);$st=(int)($r->middle_staff??0);if(!$m||!$st||$m==$st)continue;$exists=$legacy?$CI->db->where('legacy_id',$legacy)->get($h)->row():null;if(!$exists){$pair=$CI->db->where('manager_staff_id',$m)->where('seller_staff_id',$st)->get($h)->row();if(!$pair)$CI->db->insert($h,['manager_staff_id'=>$m,'seller_staff_id'=>$st,'active'=>1,'legacy_id'=>$legacy?:null,'created_at'=>date('Y-m-d H:i:s')]);}}
}
function scr_ensure_phase3_schema(){
 $CI=&get_instance();$t=db_prefix().'scr_commission_rules';
 if(!$CI->db->table_exists($t))$CI->db->query("CREATE TABLE `{$t}` (`id` int NOT NULL AUTO_INCREMENT,`group_id` int NOT NULL,`sale_type_id` int NOT NULL,`seller_percent` decimal(8,3) NOT NULL DEFAULT 0,`marketer_percent` decimal(8,3) NOT NULL DEFAULT 0,`active` tinyint NOT NULL DEFAULT 1,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `group_sale` (`group_id`,`sale_type_id`),UNIQUE KEY `legacy_id` (`legacy_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 // Upgrade existing rules in place; preserve IDs, rates and legacy mappings.
 if(!$CI->db->field_exists('valid_from',$t))$CI->db->query("ALTER TABLE `{$t}` ADD `valid_from` DATE NOT NULL DEFAULT '1000-01-01'");
 if(!$CI->db->field_exists('valid_to',$t))$CI->db->query("ALTER TABLE `{$t}` ADD `valid_to` DATE NOT NULL DEFAULT '9999-12-31'");
 $idx=$CI->db->query("SHOW INDEX FROM `{$t}` WHERE Key_name = 'group_sale'")->result_array();
 if($idx)$CI->db->query("ALTER TABLE `{$t}` DROP INDEX `group_sale`");

 // Legacy commission data is an import source only. Never re-import after the independent table has been adopted,
 // otherwise a deliberately deleted rule would be resurrected from item_discount.
 if(get_option('scr_phase3_legacy_migrated')!=='1'){
  $localCount=(int)$CI->db->count_all($t);
  if($localCount===0){$old=db_prefix().'item_discount_comissions';if($CI->db->table_exists($old))foreach($CI->db->get($old)->result() as $r){$legacy=(int)($r->comission_id??0);$g=(int)($r->comission_product_group??0);$st=(int)($r->comission_selltype??0);if(!$g||!$st)continue;if(!$CI->db->where('group_id',$g)->where('sale_type_id',$st)->get($t)->row())$CI->db->insert($t,['group_id'=>$g,'sale_type_id'=>$st,'seller_percent'=>(float)($r->comission_seller_percent??0),'marketer_percent'=>(float)($r->comission_marketer_percent??0),'active'=>1,'legacy_id'=>$legacy?:null,'created_at'=>date('Y-m-d H:i:s')]);}}
  if(function_exists('add_option')){if(get_option('scr_phase3_legacy_migrated')===false)add_option('scr_phase3_legacy_migrated','1');else update_option('scr_phase3_legacy_migrated','1');}
 }
}
function scr_ensure_commission_center_schema(){
 $CI=&get_instance();
 $at=db_prefix().'scr_commission_rule_staff';
 if(!$CI->db->table_exists($at))$CI->db->query("CREATE TABLE `{$at}` (`id` INT NOT NULL AUTO_INCREMENT,`rule_id` INT NOT NULL,`staff_id` INT NOT NULL,`valid_from` DATE NOT NULL,`valid_to` DATE NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `staff_rule` (`staff_id`,`rule_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$t=db_prefix().'scr_commission_policies';
 if(!$CI->db->table_exists($t))$CI->db->query("CREATE TABLE `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`name` VARCHAR(255) NOT NULL,`valid_from` DATE NOT NULL,`valid_to` DATE NOT NULL,`seller_percent` DECIMAL(9,3) NOT NULL DEFAULT 0,`manager_percent` DECIMAL(9,3) NOT NULL DEFAULT 0,`active` TINYINT NOT NULL DEFAULT 1,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $a=db_prefix().'scr_commission_policy_staff';
 if(!$CI->db->table_exists($a))$CI->db->query("CREATE TABLE `{$a}` (`id` INT NOT NULL AUTO_INCREMENT,`policy_id` INT NOT NULL,`staff_id` INT NOT NULL,`valid_from` DATE NOT NULL,`valid_to` DATE NOT NULL,PRIMARY KEY (`id`),KEY `staff_dates` (`staff_id`,`valid_from`,`valid_to`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $r=db_prefix().'scr_commission_receipts';
 if(!$CI->db->table_exists($r))$CI->db->query("CREATE TABLE `{$r}` (`id` INT NOT NULL AUTO_INCREMENT,`staff_id` INT NOT NULL,`date_from` DATE NOT NULL,`date_to` DATE NOT NULL,`amount` DECIMAL(18,4) NOT NULL DEFAULT 0,`status` VARCHAR(20) NOT NULL DEFAULT 'draft',`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function scr_ensure_batch_settlement_schema(){
 $CI=&get_instance();$b=db_prefix().'scr_commission_settlements';$l=db_prefix().'scr_commission_settlement_items';
 if(!$CI->db->table_exists($b))$CI->db->query("CREATE TABLE `{$b}` (`id` INT AUTO_INCREMENT PRIMARY KEY,`staff_id` INT NOT NULL,`amount` DECIMAL(18,4) NOT NULL,`payment_date` DATE NOT NULL,`payment_method` VARCHAR(100) NOT NULL,`transaction_ref` VARCHAR(255) NULL,`attachment` VARCHAR(255) NULL,`notes` TEXT NULL,`created_by` INT NOT NULL,`created_at` DATETIME NOT NULL,KEY `staff_date` (`staff_id`,`payment_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 if(!$CI->db->table_exists($l))$CI->db->query("CREATE TABLE `{$l}` (`id` INT AUTO_INCREMENT PRIMARY KEY,`settlement_id` INT NOT NULL,`commission_id` INT NOT NULL,`amount` DECIMAL(18,4) NOT NULL,UNIQUE KEY `one_settlement_per_commission` (`commission_id`),KEY `settlement_id` (`settlement_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function scr_ensure_manager_settlement_schema(){
 $CI=&get_instance();$t=db_prefix().'scr_manager_settlement_items';
 if(!$CI->db->table_exists($t))$CI->db->query("CREATE TABLE `{$t}` (`id` INT AUTO_INCREMENT PRIMARY KEY,`settlement_id` INT NOT NULL,`commission_id` INT NOT NULL,`hierarchy_id` INT NOT NULL,`manager_staff_id` INT NOT NULL,`percent_snapshot` DECIMAL(8,3) NOT NULL,`amount` DECIMAL(18,4) NOT NULL,UNIQUE KEY `manager_commission_once` (`commission_id`,`hierarchy_id`),KEY `settlement_id` (`settlement_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function scr_ensure_phase4_schema(){
 $CI=&get_instance();
 $l=db_prefix().'scr_ranking_levels';
 if(!$CI->db->table_exists($l))$CI->db->query("CREATE TABLE `{$l}` (`id` int NOT NULL AUTO_INCREMENT,`name` varchar(300) NOT NULL,`valid_from` date NOT NULL,`valid_to` date NOT NULL,`calculated` tinyint NOT NULL DEFAULT 0,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `legacy_id` (`legacy_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 if(!$CI->db->field_exists('sale_type_id',$l))$CI->db->query("ALTER TABLE `{$l}` ADD `sale_type_id` INT NOT NULL DEFAULT 0 AFTER `valid_to`");
 if(!$CI->db->field_exists('ranking_role',$l))$CI->db->query("ALTER TABLE `{$l}` ADD `ranking_role` VARCHAR(20) NOT NULL DEFAULT 'seller' AFTER `sale_type_id`");
 $st=db_prefix().'scr_ranking_steps';
 if(!$CI->db->table_exists($st))$CI->db->query("CREATE TABLE `{$st}` (`id` int NOT NULL AUTO_INCREMENT,`level_id` int NOT NULL,`name` varchar(300) NOT NULL,`from_amount` decimal(18,2) NOT NULL DEFAULT 0,`to_amount` decimal(18,2) NOT NULL DEFAULT 0,`percent` decimal(8,3) NOT NULL DEFAULT 0,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),KEY `level_id` (`level_id`),UNIQUE KEY `legacy_id` (`legacy_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 $rs=db_prefix().'scr_ranking_results';
 if(!$CI->db->table_exists($rs))$CI->db->query("CREATE TABLE `{$rs}` (`id` int NOT NULL AUTO_INCREMENT,`level_id` int NOT NULL,`step_id` int NULL,`staff_id` int NOT NULL,`sales_amount` decimal(18,2) NOT NULL DEFAULT 0,`percent` decimal(8,3) NOT NULL DEFAULT 0,`base_commission` decimal(18,2) NOT NULL DEFAULT 0,`adjustment_type` varchar(20) NOT NULL DEFAULT 'percent',`adjustment_value` decimal(18,3) NOT NULL DEFAULT 0,`adjustment_amount` decimal(18,2) NOT NULL DEFAULT 0,`final_commission` decimal(18,2) NOT NULL DEFAULT 0,`calculated_at` datetime NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `level_staff` (`level_id`,`staff_id`),KEY `step_id` (`step_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 foreach(['base_commission'=>"decimal(18,2) NOT NULL DEFAULT 0",'adjustment_type'=>"varchar(20) NOT NULL DEFAULT 'percent'",'adjustment_value'=>"decimal(18,3) NOT NULL DEFAULT 0",'adjustment_amount'=>"decimal(18,2) NOT NULL DEFAULT 0",'reversal_amount'=>"decimal(18,2) NOT NULL DEFAULT 0",'final_commission'=>"decimal(18,2) NOT NULL DEFAULT 0"] as $c=>$def){if(!$CI->db->field_exists($c,$rs))$CI->db->query("ALTER TABLE `{$rs}` ADD `{$c}` {$def}");}
 if(!$CI->db->field_exists('adjustment_type',$st))$CI->db->query("ALTER TABLE `{$st}` ADD `adjustment_type` varchar(20) NOT NULL DEFAULT 'percent' AFTER `to_amount`");
 if(!$CI->db->field_exists('adjustment_value',$st))$CI->db->query("ALTER TABLE `{$st}` ADD `adjustment_value` decimal(18,3) NOT NULL DEFAULT 0 AFTER `adjustment_type`");
 // 0.6.0: sale type and ranked role belong to each step, not the period.
 if(!$CI->db->field_exists('sale_type_id',$st))$CI->db->query("ALTER TABLE `{$st}` ADD `sale_type_id` INT NOT NULL DEFAULT 0 AFTER `name`");
 if(!$CI->db->field_exists('ranking_role',$st))$CI->db->query("ALTER TABLE `{$st}` ADD `ranking_role` VARCHAR(20) NOT NULL DEFAULT 'seller' AFTER `sale_type_id`");
 foreach(['sale_type_id'=>"int NOT NULL DEFAULT 0",'ranking_role'=>"varchar(20) NOT NULL DEFAULT 'seller'"] as $c=>$def){if(!$CI->db->field_exists($c,$rs))$CI->db->query("ALTER TABLE `{$rs}` ADD `{$c}` {$def}");}
 // Old 0.5.2 periods are migrated once: their sale type/role is copied to existing steps, preserving all periods and steps.
 if(get_option('scr_phase6_step_scope_migrated')!=='1'){
  if($CI->db->field_exists('sale_type_id',$l)&&$CI->db->field_exists('ranking_role',$l))$CI->db->query("UPDATE `{$st}` s INNER JOIN `{$l}` l ON l.id=s.level_id SET s.sale_type_id=l.sale_type_id,s.ranking_role=l.ranking_role WHERE s.sale_type_id=0");
  if(function_exists('add_option')){if(get_option('scr_phase6_step_scope_migrated')===false)add_option('scr_phase6_step_scope_migrated','1');else update_option('scr_phase6_step_scope_migrated','1');}
 }
 $led=db_prefix().'scr_ranking_invoice_ledger';
 if(!$CI->db->table_exists($led))$CI->db->query("CREATE TABLE `{$led}` (`id` int NOT NULL AUTO_INCREMENT,`level_id` int NOT NULL,`payment_id` int NOT NULL,`invoice_id` int NOT NULL,`invoice_number` varchar(120) NULL,`invoice_date` date NULL,`payment_date` date NULL,`paid_amount` decimal(18,2) NOT NULL DEFAULT 0,`sale_type_id` int NOT NULL DEFAULT 0,`seller_staff_id` int NOT NULL DEFAULT 0,`marketer_staff_id` int NOT NULL DEFAULT 0,`seller_base_commission` decimal(18,2) NOT NULL DEFAULT 0,`marketer_base_commission` decimal(18,2) NOT NULL DEFAULT 0,`created_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `level_payment` (`level_id`,`payment_id`),KEY `invoice_id` (`invoice_id`),KEY `sale_type_id` (`sale_type_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 // Results are now unique per person + role + sale type inside a period.
 $idx=$CI->db->query("SHOW INDEX FROM `{$rs}` WHERE Key_name='level_staff'")->result();if($idx)$CI->db->query("ALTER TABLE `{$rs}` DROP INDEX `level_staff`");
 $idx2=$CI->db->query("SHOW INDEX FROM `{$rs}` WHERE Key_name='level_staff_role_type'")->result();$newIdx=$CI->db->query("SHOW INDEX FROM `{$rs}` WHERE Key_name='scr_level_staff_role_type_marketing'")->result();if(!$idx2&&!$newIdx)$CI->db->query("ALTER TABLE `{$rs}` ADD UNIQUE KEY `level_staff_role_type` (`level_id`,`staff_id`,`ranking_role`,`sale_type_id`)");
 // Marketing categories are initialized lazily; never run category DDL on ranking page loads.
 // Online marketing segmentation. Legacy rows remain in the normal category.
 foreach([$st,$rs] as $scrTable){if(!$CI->db->field_exists('marketing_type',$scrTable))$CI->db->query("ALTER TABLE `{$scrTable}` ADD `marketing_type` VARCHAR(32) NOT NULL DEFAULT 'normal'");}
 $scrIdx=$CI->db->query("SHOW INDEX FROM `{$rs}` WHERE Key_name='level_staff_role_type'")->result();if($scrIdx)$CI->db->query("ALTER TABLE `{$rs}` DROP INDEX `level_staff_role_type`");
 $scrIdx=$CI->db->query("SHOW INDEX FROM `{$rs}` WHERE Key_name='scr_level_staff_role_type_marketing'")->result();if(!$scrIdx)$CI->db->query("ALTER TABLE `{$rs}` ADD UNIQUE KEY `scr_level_staff_role_type_marketing` (`level_id`,`staff_id`,`ranking_role`,`sale_type_id`,`marketing_type`)");
 // 0.6.2 cancellation/reversal accounting. Paid periods are immutable; later cancellations become negative carry-forward rows.
 if(!$CI->db->field_exists('commission_paid',$l))$CI->db->query("ALTER TABLE `{$l}` ADD `commission_paid` TINYINT NOT NULL DEFAULT 0 AFTER `calculated`, ADD `commission_paid_at` DATETIME NULL AFTER `commission_paid`");
 $rv=db_prefix().'scr_commission_reversals';
 if(!$CI->db->table_exists($rv))$CI->db->query("CREATE TABLE `{$rv}` (`id` int NOT NULL AUTO_INCREMENT,`legacy_id` int NULL,`payment_id` int NOT NULL DEFAULT 0,`invoice_id` int NOT NULL DEFAULT 0,`source_level_id` int NULL,`applied_level_id` int NULL,`staff_id` int NOT NULL DEFAULT 0,`ranking_role` varchar(20) NOT NULL DEFAULT 'seller',`sale_type_id` int NOT NULL DEFAULT 0,`cancel_date` date NULL,`base_commission_reversal` decimal(18,2) NOT NULL DEFAULT 0,`rank_adjustment_reversal` decimal(18,2) NOT NULL DEFAULT 0,`total_reversal` decimal(18,2) NOT NULL DEFAULT 0,`status` varchar(20) NOT NULL DEFAULT 'pending',`description` varchar(3000) NULL,`created_at` datetime NULL,`applied_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `legacy_id` (`legacy_id`),UNIQUE KEY `payment_staff_role` (`payment_id`,`staff_id`,`ranking_role`),KEY `status` (`status`),KEY `source_level` (`source_level_id`),KEY `applied_level` (`applied_level_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 if(get_option('scr_phase4_legacy_migrated')!=='1'){
  $oldL=db_prefix().'item_discount_levels';
  if($CI->db->table_exists($oldL) && (int)$CI->db->count_all($l)===0){foreach($CI->db->get($oldL)->result() as $r){$CI->db->insert($l,['name'=>(string)($r->level_name??''),'valid_from'=>(string)($r->level_from??date('Y-m-d')),'valid_to'=>(string)($r->level_end??date('Y-m-d')),'calculated'=>(int)($r->level_calculated??0),'legacy_id'=>(int)($r->level_id??0)?:null,'created_at'=>date('Y-m-d H:i:s')]);}}
  $oldS=db_prefix().'item_discount_level_steps';
  if($CI->db->table_exists($oldS) && (int)$CI->db->count_all($st)===0){foreach($CI->db->get($oldS)->result() as $r){$legacyLevel=(int)($r->step_level??0);$nl=$CI->db->where('legacy_id',$legacyLevel)->get($l)->row();if(!$nl)continue;$CI->db->insert($st,['level_id'=>(int)$nl->id,'name'=>(string)($r->step_name??''),'from_amount'=>(float)($r->step_from_price??0),'to_amount'=>(float)($r->step_until_price??0),'percent'=>(float)($r->step_percent??0),'legacy_id'=>(int)($r->step_id??0)?:null,'created_at'=>date('Y-m-d H:i:s')]);}}
  if(function_exists('add_option')){if(get_option('scr_phase4_legacy_migrated')===false)add_option('scr_phase4_legacy_migrated','1');else update_option('scr_phase4_legacy_migrated','1');}
 }
}

function scr_ensure_full_legacy_migration(){
 $CI=&get_instance();
 if(get_option('scr_full_legacy_migrated_v1')==='1')return;
 $now=date('Y-m-d H:i:s');
 // Preserve complete legacy business data in module-owned snapshot tables before the licensed module is retired.
 $legacy=['item_discount_discount','item_discount_role','item_discount_set_middle_staff','item_discount_levels','item_discount_sell_type','item_discount_level_steps','item_discount_calculated_staff_level_steps','item_discount_comissions','item_discount_after_pay_record_comission','item_discount_after_pay_record_canceled_comission','item_sale_type_prices'];
 foreach($legacy as $name){$src=db_prefix().$name;if(!$CI->db->table_exists($src))continue;$dst=db_prefix().'scr_legacy_'.$name;if(!$CI->db->table_exists($dst))$CI->db->query("CREATE TABLE `{$dst}` LIKE `{$src}`");$CI->db->query("INSERT IGNORE INTO `{$dst}` SELECT * FROM `{$src}`");}
 // Sale types: import every missing legacy row.
 $src=db_prefix().'item_discount_sell_type';$dst=db_prefix().'scr_sale_types';if($CI->db->table_exists($src))foreach($CI->db->get($src)->result() as $r){$id=(int)$r->st_id;if(!$CI->db->where('legacy_id',$id)->get($dst)->row())$CI->db->insert($dst,['name'=>(string)$r->st_name,'active'=>(int)$r->st_status,'legacy_id'=>$id,'created_at'=>$now]);}
 // Discount rules.
 $src=db_prefix().'item_discount_discount';$dst=db_prefix().'scr_discount_rules';if($CI->db->table_exists($src))foreach($CI->db->get($src)->result() as $r){$id=(int)$r->dis_id;if(!$CI->db->where('legacy_id',$id)->get($dst)->row() && !empty($r->dis_from)&&!empty($r->dis_until))$CI->db->insert($dst,['group_id'=>(int)$r->dis_group,'base_percent'=>(float)$r->dis_percent,'max_percent'=>(float)$r->dis_top_percent,'valid_from'=>$r->dis_from,'valid_to'=>$r->dis_until,'active'=>1,'legacy_id'=>$id,'created_at'=>$now]);}
 // Manager/seller hierarchy.
 $src=db_prefix().'item_discount_set_middle_staff';$dst=db_prefix().'scr_sales_hierarchy';if($CI->db->table_exists($src))foreach($CI->db->get($src)->result() as $r){$id=(int)$r->middle_id;$m=(int)$r->middle_manager;$st=(int)$r->middle_staff;if(!$m||!$st||$m===$st)continue;if(!$CI->db->where('legacy_id',$id)->get($dst)->row()&&!$CI->db->where('manager_staff_id',$m)->where('seller_staff_id',$st)->get($dst)->row())$CI->db->insert($dst,['manager_staff_id'=>$m,'seller_staff_id'=>$st,'active'=>1,'legacy_id'=>$id,'created_at'=>$now]);}
 // Discount permission from Perfex staff_permissions used by the old module.
 $sp=db_prefix().'staff_permissions';$h=db_prefix().'scr_sales_hierarchy';$a=db_prefix().'scr_discount_access';if($CI->db->table_exists($sp)&&$CI->db->table_exists($h)){foreach($CI->db->where('feature','item_discount')->where('capability','change_item_discount')->get($sp)->result() as $pr){$sid=(int)$pr->staff_id;$pairs=$CI->db->where('seller_staff_id',$sid)->get($h)->result();foreach($pairs as $pair){$m=(int)$pair->manager_staff_id;$x=$CI->db->where('manager_staff_id',$m)->where('staff_id',$sid)->get($a)->row();if($x)$CI->db->where('id',$x->id)->update($a,['can_edit_line_discount'=>1,'updated_at'=>$now]);else $CI->db->insert($a,['manager_staff_id'=>$m,'staff_id'=>$sid,'can_edit_line_discount'=>1,'created_at'=>$now]);}}}
 // Commission rules.
 $src=db_prefix().'item_discount_comissions';$dst=db_prefix().'scr_commission_rules';if($CI->db->table_exists($src))foreach($CI->db->get($src)->result() as $r){$id=(int)$r->comission_id;$g=(int)$r->comission_product_group;$st=(int)$r->comission_selltype;if(!$g||!$st)continue;if(!$CI->db->where('legacy_id',$id)->get($dst)->row()&&!$CI->db->where('group_id',$g)->where('sale_type_id',$st)->get($dst)->row())$CI->db->insert($dst,['group_id'=>$g,'sale_type_id'=>$st,'seller_percent'=>(float)$r->comission_seller_percent,'marketer_percent'=>(float)$r->comission_marketer_percent,'active'=>1,'legacy_id'=>$id,'created_at'=>$now]);}
 // Ranking periods and every step. Do not require local tables to be empty.
 $srcL=db_prefix().'item_discount_levels';$dstL=db_prefix().'scr_ranking_levels';if($CI->db->table_exists($srcL))foreach($CI->db->get($srcL)->result() as $r){$id=(int)$r->level_id;if(!$CI->db->where('legacy_id',$id)->get($dstL)->row())$CI->db->insert($dstL,['name'=>(string)$r->level_name,'valid_from'=>$r->level_from,'valid_to'=>$r->level_end,'calculated'=>(int)$r->level_calculated,'legacy_id'=>$id,'created_at'=>$now]);}
 $srcS=db_prefix().'item_discount_level_steps';$dstS=db_prefix().'scr_ranking_steps';if($CI->db->table_exists($srcS))foreach($CI->db->get($srcS)->result() as $r){$id=(int)$r->step_id;if($CI->db->where('legacy_id',$id)->get($dstS)->row())continue;$nl=$CI->db->where('legacy_id',(int)$r->step_level)->get($dstL)->row();if(!$nl)continue;$CI->db->insert($dstS,['level_id'=>(int)$nl->id,'name'=>(string)$r->step_name,'sale_type_id'=>0,'ranking_role'=>'seller','from_amount'=>(float)$r->step_from_price,'to_amount'=>(float)$r->step_until_price,'adjustment_type'=>'percent','adjustment_value'=>(float)$r->step_percent,'percent'=>(float)$r->step_percent,'legacy_id'=>$id,'created_at'=>$now]);}
 if(function_exists('add_option')){if(get_option('scr_full_legacy_migrated_v1')===false)add_option('scr_full_legacy_migrated_v1','1');else update_option('scr_full_legacy_migrated_v1','1');}
}

function scr_parse_product_area($raw){
 $s=trim((string)$raw);
 $s=strtr($s,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٫'=>'.','،'=>',']);
 $s=str_replace(['×','X','x','*','✕','ـ'],'x',$s);
 $s=preg_replace('/\s+/u','',$s);
 if(preg_match('/([0-9]+(?:\.[0-9]+)?)x([0-9]+(?:\.[0-9]+)?)/',$s,$m))return (float)$m[1]*(float)$m[2];
 return false;
}
function scr_sale_price($itemid,$saleType,$pricingDate=null){
 $CI=&get_instance();$p=db_prefix();$itemid=(int)$itemid;$saleType=(int)$saleType;if(!$itemid||!$saleType)return null;
 // Explicit item-level price always has priority.
 $t=$p.'item_sale_type_prices';
 if($CI->db->table_exists($t)){
  $r=$CI->db->where('itemid',$itemid)->where('sale_type_id',$saleType)->order_by('id','DESC')->get($t)->row();
  if($r&&$r->price!==''&&$r->price!==null){$v=preg_replace('/[^0-9.\-]/','',str_replace(',','',(string)$r->price));if(is_numeric($v)&&(float)$v>0)return (float)$v;}
 }
 // 0.6.7: imported PUM products normally have no item-level price. Derive it from
 // group + actual size + the active square-metre rule, exactly like Product Unified Manager.
 $it=$p.'items';$sz=$p.'ware_size_type';$rt=$p.'product_sqm_price_rules';
 if(!$CI->db->table_exists($it)||!$CI->db->table_exists($rt))return null;
 $CI->db->select('i.group_id,i.size_id',false)->from($it.' i');
 if($CI->db->table_exists($sz)&&$CI->db->field_exists('size_id',$it))$CI->db->select('s.size_name',false)->join($sz.' s','s.size_type_id=i.size_id','left');else $CI->db->select("'' AS size_name",false);
 $item=$CI->db->where('i.id',$itemid)->get()->row_array();if(!$item||empty($item['group_id']))return null;
 $area=scr_parse_product_area($item['size_name']??'');if($area===false||$area<=0)return null;
 // invoice_type can be either the legacy sale-type id or SCR's own row id. Rules use the legacy key.
 $ruleKey=(string)$saleType;$stt=$p.'scr_sale_types';
 if($CI->db->table_exists($stt)){$sr=$CI->db->group_start()->where('id',$saleType)->or_where('legacy_id',$saleType)->group_end()->order_by('id','ASC')->get($stt)->row();if($sr&&isset($sr->legacy_id)&&(int)$sr->legacy_id>0)$ruleKey=(string)(int)$sr->legacy_id;}
 // 0.7.5.5: Perfex date inputs may submit 2026/01/01 (and localized digits), while SQL rules use YYYY-MM-DD.
 // Normalize the invoice date before rule lookup; only fall back to today when the submitted value is genuinely invalid.
 $pricingDate=trim((string)$pricingDate);
 $pricingDate=strtr($pricingDate,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','/'=>'-','.'=>'-']);
 if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/',$pricingDate,$dm)){
  $yy=(int)$dm[1];$mm=(int)$dm[2];$dd=(int)$dm[3];
  $pricingDate=checkdate($mm,$dd,$yy)?sprintf('%04d-%02d-%02d',$yy,$mm,$dd):'';
 }
 if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$pricingDate))$pricingDate=date('Y-m-d');
 $CI->db->from($rt)->where('group_id',(int)$item['group_id'])->where('sale_type_key',$ruleKey)->where('active',1)
  ->group_start()->where('valid_from <=',$pricingDate)->or_where('valid_from IS NULL',null,false)->or_where('valid_from','')->group_end()
  ->group_start()->where('valid_to >=',$pricingDate)->or_where('valid_to IS NULL',null,false)->or_where('valid_to','')->group_end()
  ->order_by('valid_from','DESC')->order_by('id','DESC');
 $rule=$CI->db->get()->row_array();if(!$rule||!isset($rule['price_per_sqm']))return null;
 $ppm=(float)$rule['price_per_sqm'];if($ppm<=0)return null;return round($area*$ppm,4);
}
function scr_enforce_sale_price($data){
 /* 0.7.6.9.7.2 — invoice-save fix.
    The manual adjustment UI is intentionally NOT submitted as an extra POST/DB field.
    Perfex receives only its native `adjustment` value, which already contains
    auto + manual adjustment. This avoids unknown-column/validation failures. */
 if(!is_array($data))return $data;
 unset($data['scr_manual_adjustment'],$data['scr_auto_adjustment']);
 $st=(int)($data['invoice_type']??$data['sale_type_id']??0);if(!$st)return $data;
 foreach(['newitems','items'] as $b){
  if(empty($data[$b])||!is_array($data[$b]))continue;
  foreach($data[$b] as $k=>$line){
   if(!is_array($line))continue;$iid=0;
   foreach(['itemItemId','itemid','item_id','id','product_id','commodity_id'] as $f){if(!empty($line[$f])&&is_numeric($line[$f])){$iid=(int)$line[$f];break;}}
   if(!$iid)continue;
   try{$pricingDate=(string)($data['date']??'');$p=scr_sale_price($iid,$st,$pricingDate);if($p!==null)$data[$b][$k]['rate']=$p;}
   catch(Throwable $e){log_message('error','SCR invoice price lookup item '.$iid.': '.$e->getMessage());}
  }
 }
 return $data;
}
function scr_repair_native_invoice_rates_from_commercial_price($invoiceId){
 $CI=&get_instance();$invoiceId=(int)$invoiceId;if($invoiceId<=0)return false;
 $inv=$CI->db->where('id',$invoiceId)->get(db_prefix().'invoices')->row_array();if(!$inv)return false;
 $saleType=(int)($inv['invoice_type']??$inv['sale_type_id']??0);if($saleType<=0)return false;
 $rows=$CI->db->where('rel_id',$invoiceId)->where('rel_type','invoice')->order_by('item_order','ASC')->order_by('id','ASC')->get(db_prefix().'itemable')->result_array();if(!$rows)return false;
 $updates=[];$sum=0.0;$allResolved=true;
 foreach($rows as $r){$qty=(float)($r['qty']??0);$rate=(float)($r['rate']??0);$iid=0;
  foreach(['itemItemId','itemid','item_id','product_id','commodity_id'] as $f){if(isset($r[$f])&&is_numeric($r[$f])&&(int)$r[$f]>0){$iid=(int)$r[$f];break;}}
  if($rate<=0 && $iid>0){$p=scr_sale_price($iid,$saleType,(string)($inv['date']??''));if($p!==null&&(float)$p>0){$rate=(float)$p;$updates[(int)$r['id']]=$rate;}else{$allResolved=false;}}
  if($rate<=0)$allResolved=false;$sum += $qty*$rate;
 }
 if(!$updates||!$allResolved)return false;
 $target=(float)($inv['subtotal']??0);if($target<=0)return false;$tol=max(1.0,abs($target)*0.00001);
 if(abs($sum-$target)>$tol){log_message('error','SCR rate repair refused invoice '.$invoiceId.' subtotal='.$target.' resolved='.$sum);return false;}
 $CI->db->trans_begin();foreach($updates as $itemableId=>$rate){$CI->db->where('id',$itemableId)->where('rel_id',$invoiceId)->where('rel_type','invoice')->where('rate <=',0)->update(db_prefix().'itemable',['rate'=>$rate]);}
 if($CI->db->trans_status()===false){$CI->db->trans_rollback();return false;}$CI->db->trans_commit();
 foreach($updates as $itemableId=>$rate){$v=$CI->db->select('rate')->where('id',$itemableId)->get(db_prefix().'itemable')->row_array();if((float)($v['rate']??0)<=0)return false;}
 return true;
}
function scr_repair_native_invoice_rates_after_update($payload){$id=0;if(is_array($payload))$id=(int)($payload['id']??$payload['invoice_id']??0);elseif(is_numeric($payload))$id=(int)$payload;if($id>0)scr_repair_native_invoice_rates_from_commercial_price($id);}
function scr_repair_legacy_zero_rate_invoices(){
 if(!is_staff_logged_in())return;$CI=&get_instance();$it=db_prefix().'itemable';if(!$CI->db->table_exists($it)||!$CI->db->field_exists('itemItemId',$it))return;
 $rows=$CI->db->select('DISTINCT rel_id',false)->from($it)->where('rel_type','invoice')->where('rate <=',0)->where('itemItemId >',0)->limit(100)->get()->result_array();
 foreach($rows as $r){$id=(int)($r['rel_id']??0);if($id>0)scr_repair_native_invoice_rates_from_commercial_price($id);}
}

function scr_invoice_fields($invoice){
 if(scr_old_module_active()) return; // prevent duplicate fields during parallel validation
 $CI=&get_instance(); $CI->load->model('sales_commercial_rules/Sales_commercial_rules_model'); $CI->load->model('staff_model');
 $types=$CI->Sales_commercial_rules_model->sale_types(1); $staff=$CI->staff_model->get(); $selected=$invoice->invoice_type??'';
 echo '<div class="form-group row"><div class="col-md-6">'.render_select('invoice_type',$types,['id','name'],'scr_invoice_sale_type',$selected,['required'=>true],[],'','',true).'</div>';
 echo '<div class="col-md-6">'.render_select('invoice_marketer',$staff,['staffid',['firstname','lastname']],'scr_invoice_marketer',$invoice->invoice_marketer??'',[],[],'','',true).'</div></div>';
}
function scr_invoice_footer(){
 $uri=$_SERVER['REQUEST_URI']??''; if(strpos($uri,'/admin/invoices')===false && strpos($uri,'/admin/estimates')===false)return;
 $CI=&get_instance();
 $url=admin_url('sales_commercial_rules/get_price');$meta=admin_url('sales_commercial_rules/product_picker_meta');
 /* 0.7.6.1 — conservative seller-request bridge. Keep the proven 0.7.5.9
    estimate/invoice UI path untouched; only make request payload construction safe
    and enrich the AJAX customer value with its display text. */
 $requestPayload=null;$rid=isset($_GET['sps_request'])?(int)$_GET['sps_request']:0;
 if($rid&&$CI->db->table_exists(db_prefix().'sps_sales_requests')){
  $rr=$CI->db->where('id',$rid)->get(db_prefix().'sps_sales_requests')->row_array();
  if($rr){
   $requestPayload=$rr;
   $items=json_decode($rr['items_json'],true);$requestPayload['items']=is_array($items)?$items:[];
   if(!empty($rr['client_id'])&&$CI->db->table_exists(db_prefix().'clients')){
    $cl=$CI->db->select('company')->where('userid',(int)$rr['client_id'])->get(db_prefix().'clients')->row_array();
    $requestPayload['client_name']=$cl?($cl['company']??''):'';
    if(!empty($rr['seller_staff_id'])&&$CI->db->table_exists(db_prefix().'staff')){
     $sf=$CI->db->select("CONCAT(firstname,' ',lastname) name",false)->where('staffid',(int)$rr['seller_staff_id'])->get(db_prefix().'staff')->row_array();
     $requestPayload['seller_name']=$sf?($sf['name']??''):'';
    }
    foreach($requestPayload['items'] as &$it){if(!isset($it['id']))$it['id']=$it['item_id']??($it['product_id']??($it['commodity_id']??0));if(!isset($it['qty']))$it['qty']=$it['quantity']??1;}unset($it);
   }
  }
 }
 $quickCustomer=admin_url('sales_commercial_rules/quick_customer_create');
 $nativeFix=admin_url('sales_delivery_flow/native_invoice_fix');
  $manualLimit=(float)get_option('scr_invoice_manual_adjustment_limit'); if($manualLimit<=0)$manualLimit=5000;
 $posDevices=[];$pdt=db_prefix().'scr_pos_devices';if($CI->db->table_exists($pdt))$posDevices=$CI->db->where('active',1)->order_by('name','ASC')->get($pdt)->result_array();
 $paymentModes=[];$pmt=db_prefix().'payment_modes';if($CI->db->table_exists($pmt)){foreach($CI->db->get($pmt)->result_array() as $pm){$mid=(string)($pm['id']??'');if($mid==='')continue;$cat='';$ct=db_prefix().'scr_payment_mode_categories';if($CI->db->table_exists($ct)){$cr=$CI->db->where('payment_mode',$mid)->get($ct)->row_array();$cat=(string)($cr['category']??'');}$paymentModes[]=['id'=>$mid,'name'=>(string)($pm['name']??$mid),'category'=>$cat];}}
 echo '<script>window.SCR_PAYMENT_MODES='.json_encode($paymentModes,JSON_UNESCAPED_UNICODE).';window.SCR_PAYMENT_META_URL='.json_encode(admin_url('sales_commercial_rules/payment_meta')).';window.SCR_RETURN_LOCK_URL='.json_encode(admin_url('sales_commercial_rules/return_payment_lock')).';window.SCR_POS_DEVICES='.json_encode($posDevices,JSON_UNESCAPED_UNICODE).';window.SCR_PRICE_URL='.json_encode($url).';window.SCR_DISCOUNT_URL='.json_encode(admin_url('sales_commercial_rules/discount_policy')).';window.SCR_PRODUCT_META_URL='.json_encode($meta).';window.SCR_SALES_REQUEST='.json_encode($requestPayload,JSON_UNESCAPED_UNICODE).';window.SCR_QUICK_CUSTOMER_URL='.json_encode($quickCustomer).';window.SCR_NATIVE_INVOICE_FIX_URL='.json_encode($nativeFix).';window.SCR_MANUAL_ADJUSTMENT_LIMIT='.json_encode($manualLimit).';window.SCR_IS_INVOICE='.(strpos($uri,'/admin/invoices')!==false?'true':'false').';</script>';
 echo <<<'JS'
<style>
#scr-product-filters{direction:rtl;background:#fffaf4;border:1px solid #f2dfc9;border-radius:8px;padding:10px 12px;margin:8px 0 12px;display:none}.scr-pf-title{font-weight:700;color:#8a4b00;margin-bottom:8px}.scr-pf-grid{display:grid;grid-template-columns:repeat(4,minmax(130px,1fr));gap:8px}.scr-pf-grid label{font-size:12px;color:#725a43;margin-bottom:3px;display:block}.scr-pf-grid select,.scr-pf-grid input{height:34px}.scr-pf-actions{margin-top:9px;text-align:left;display:flex;align-items:center;justify-content:space-between;gap:8px}.scr-pf-count{font-size:12px;color:#7a6755;padding-top:3px}
/* 0.7.4.1 compact invoice draft row: visual-only, pricing/state logic untouched */
.scr-draft-row>td{padding:7px 6px!important;vertical-align:middle!important}.scr-draft-row input.form-control,.scr-draft-row select.form-control,.scr-draft-row .form-control{height:36px!important;min-height:36px!important;padding:4px 8px!important;font-size:13px!important;border-radius:6px!important;line-height:1.25!important}.scr-draft-row textarea.form-control{height:54px!important;min-height:54px!important;padding:6px 8px!important;line-height:1.35!important;resize:none!important}.scr-draft-row input[name="quantity"],.scr-draft-row input[name="rate"]{max-width:112px!important;margin-inline:auto!important}.scr-draft-row input[name="discount"],.scr-draft-row input[name*="discount"]{max-width:78px!important;margin-inline:auto!important}.scr-draft-actions{display:flex!important;align-items:center!important;justify-content:center!important;gap:6px!important;white-space:nowrap!important}.scr-draft-row .scr-compact-add,.scr-draft-row #scrDraftReset{width:34px!important;height:34px!important;min-width:34px!important;max-width:34px!important;min-height:34px!important;max-height:34px!important;padding:0!important;margin:0!important;border-radius:7px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;font-size:14px!important;line-height:1!important;box-shadow:none!important}.scr-draft-row .scr-compact-add i,.scr-draft-row #scrDraftReset i{margin:0!important;font-size:14px!important;line-height:1!important}.scr-draft-row .scr-price-note{display:block!important;min-height:0!important;margin-top:3px!important;font-size:10px!important;line-height:1.3!important;font-weight:600!important;white-space:normal}.scr-draft-row .text-muted,.scr-draft-row small{font-size:10px!important;line-height:1.3!important}.scr-draft-row .bootstrap-select,.scr-draft-row .bootstrap-select>.dropdown-toggle{height:36px!important;min-height:36px!important}.scr-draft-row .bootstrap-select>.dropdown-toggle{padding:4px 8px!important;border-radius:6px!important;font-size:13px!important}
.scr-draft-reset{border-radius:8px!important}.scr-price-note{min-height:18px}
@media(max-width:900px){.scr-pf-grid{grid-template-columns:1fr 1fr}.scr-draft-row>td{padding:7px 4px!important}}

/* 0.7.6.9 quick customer creation on invoice */
/* 0.7.6.9.2 layout-only: keep the proven 0.7.6.9 DOM and customer modal; position only the + button inside the customer control area so it cannot consume/move a Bootstrap grid slot. */
.scr-client-inline{position:relative!important;display:block!important;width:100%!important}.scr-client-inline .form-group{width:100%!important;margin-bottom:0!important;padding-left:46px!important}.scr-client-inline .bootstrap-select{width:100%!important}.scr-client-add{position:absolute!important;left:0!important;bottom:0!important;z-index:8!important;width:38px!important;height:38px!important;min-width:38px!important;border-radius:8px!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;font-size:18px!important;padding:0!important;margin:0!important}.scr-qc-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 14px}.scr-qc-grid .form-group{margin-bottom:0}.scr-qc-full{grid-column:1/-1}.scr-qc-modal .modal-content{border-radius:12px;overflow:hidden}.scr-qc-modal .modal-header{background:#fafafa}.scr-qc-modal label{font-weight:600}.scr-qc-required{color:#e3342f}@media(max-width:700px){.scr-qc-grid{grid-template-columns:1fr}.scr-qc-full{grid-column:auto}}
.scr-adjustments-grid{display:grid!important;grid-template-columns:1fr 1fr!important;gap:10px!important;direction:rtl!important}.scr-adjustments-grid .control-label{display:block!important;margin-bottom:6px!important}.scr-adjustments-grid input{width:100%!important}@media(max-width:700px){.scr-adjustments-grid{grid-template-columns:1fr!important}}
</style>
<script>(function(){'use strict';var ep=window.SCR_PRICE_URL,dp=window.SCR_DISCOUNT_URL,mp=window.SCR_PRODUCT_META_URL;if(!ep)return;
/* 0.7.6.9.7.32: reliable bridge through this module's proven footer. It does not add/remove table cells. */
(function(){var u=window.SCR_NATIVE_INVOICE_FIX_URL;if(!u||!window.SCR_IS_INVOICE)return;
 function latin(s){return String(s||'').replace(/[۰-۹]/g,function(c){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)}).replace(/[٠-٩]/g,function(c){return '٠١٢٣٤٥٦٧٨٩'.indexOf(c)})}
 function idFromPage(){var m=location.pathname.match(/\/invoices\/(?:list_invoices|invoice|pdf)\/(\d+)/i);if(m)return parseInt(m[1],10);var all=document.querySelectorAll('a[href]');for(var i=0;i<all.length;i++){m=all[i].href.match(/\/invoices\/(?:list_invoices|invoice|delete|pdf)\/(\d+)/i);if(m)return parseInt(m[1],10)}return 0}
 function money(v){var n=Number(v||0);return isFinite(n)?Math.round(n).toLocaleString('en-US'):String(v||'')}
 function applyDetail(j){if(!j||!j.ok)return;var tables=document.querySelectorAll('table');for(var ti=0;ti<tables.length;ti++){var t=tables[ti],hs=Array.prototype.map.call(t.querySelectorAll('thead th'),function(x){return (x.textContent||'').replace(/\s+/g,' ').trim()}),pi=-1,qi=-1,di=-1,ai=-1;for(var h=0;h<hs.length;h++){if(/قیمت|rate/i.test(hs[h]))pi=h;if(/تعداد|qty/i.test(hs[h]))qi=h;if(/تخفیف|discount/i.test(hs[h]))di=h;if(/مبلغ|amount|total/i.test(hs[h]))ai=h}if(pi<0||!t.querySelector('tbody tr'))continue;var rs=t.querySelectorAll('tbody tr');for(var r=0;r<rs.length&&r<j.items.length;r++){var cs=rs[r].querySelectorAll('td'),x=j.items[r];if(cs[pi])cs[pi].textContent=money(x.rate);if(qi>=0&&cs[qi])cs[qi].textContent=String(x.qty);if(di>=0&&cs[di])cs[di].textContent=String(x.discount_percent||0);if(ai>=0&&cs[ai])cs[ai].textContent=money(x.line_total)}break}
  if(j.locked){document.querySelectorAll('a[href],button').forEach(function(a){var h=a.getAttribute('href')||'',tx=(a.textContent||'').replace(/\s+/g,' ').trim();if(/\/invoices\/(?:invoice|delete)\/'+j.invoice_id+'(?:\D|$)/i.test(h)||/^(ویرایش|حذف|Edit|Delete)$/i.test(tx)){a.style.setProperty('display','none','important');a.setAttribute('data-scr-locked','1')}})}
 }
 function run(){var id=idFromPage();if(!id)return;fetch(u+'?invoice_id='+encodeURIComponent(id),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'}).then(function(r){return r.json()}).then(applyDetail).catch(function(){})}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(run,250)});else setTimeout(run,250);setTimeout(run,1200);setTimeout(run,3000);
})();
function q(s,r){return(r||document).querySelector(s)} function qa(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))}
function st(){var e=q('select[name="invoice_type"]');return e?String(e.value||''):''}
function invoiceDate(){var e=q('input[name="date"]');return e?String(e.value||'').trim():''}
function setRate(r,p){if(!r)return;r.value=p;r.setAttribute('value',p);r.dispatchEvent(new Event('input',{bubbles:true}));}
/* 0.7.4.4: Perfex may asynchronously restore the item's base rate (often 0)
   after item_select has already fired. Keep the resolved sale price authoritative
   during that short hydration window, then release the field for normal editing. */
var draftPriceLock=null;
function stopDraftPriceLock(){if(draftPriceLock&&draftPriceLock.timer)clearInterval(draftPriceLock.timer);draftPriceLock=null}
function lockDraftPrice(itemId,rate,price,token){
 stopDraftPriceLock();if(!rate)return;var expected=Number(price);if(!isFinite(expected)||expected<=0)return;
 var lock={item:String(itemId),expected:expected,token:token,until:Date.now()+2600,timer:null};draftPriceLock=lock;
 function guard(){if(draftPriceLock!==lock||token!==draftToken||Date.now()>lock.until){stopDraftPriceLock();return}var sel=q('select[name=\"item_select\"]');if(!sel||String(sel.value||'')!==lock.item){stopDraftPriceLock();return}var current=Number(String(rate.value||'').replace(/,/g,''));if(!isFinite(current)||Math.abs(current-lock.expected)>0.0001)setRate(rate,lock.expected)}
 guard();lock.timer=setInterval(guard,80);setTimeout(function(){if(draftPriceLock===lock)stopDraftPriceLock()},2750);
}
function note(r,m,ok){if(!r)return;var p=r.parentNode||r,n=p.querySelector('.scr-price-note');if(!n){n=document.createElement('small');n.className='scr-price-note';n.style.cssText='display:block;direction:rtl;margin-top:3px;font-weight:600';p.appendChild(n)}n.textContent=m||'';n.style.color=ok?'#16803c':'#b45309'}
var priceCache={},priceInflight={};
function get(i,cb){var s=st(),d=invoiceDate();if(!s||!i){cb&&cb({success:false,message:'ابتدا نوع فروش را انتخاب کنید.'});return}var key=s+'|'+i+'|'+d;if(priceCache[key]){cb(priceCache[key]);return}if(priceInflight[key]){priceInflight[key].push(cb);return}priceInflight[key]=[cb];fetch(ep+'?itemid='+encodeURIComponent(i)+'&sale_type_id='+encodeURIComponent(s)+'&invoice_date='+encodeURIComponent(d),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'}).then(function(x){if(!x.ok)throw new Error('HTTP '+x.status);return x.json()}).then(function(j){priceCache[key]=j;var a=priceInflight[key]||[];delete priceInflight[key];a.forEach(function(f){f(j)})}).catch(function(){var j={success:false,message:'خطا در دریافت قیمت نوع فروش.'},a=priceInflight[key]||[];delete priceInflight[key];a.forEach(function(f){f(j)})})}
var draftToken=0;
function applyDraftPrice(itemId,attempt,token){if(token!==draftToken||!itemId||!st())return;var sel=q('select[name="item_select"]');if(!sel||String(sel.value||'')!==String(itemId))return;var r=q('input[name="rate"]');var desc=q('textarea[name="description"],input[name="description"]');if((!r||!desc||!String(desc.value||'').trim())&&attempt<8){setTimeout(function(){applyDraftPrice(itemId,attempt+1,token)},120);return}if(!r)return;get(itemId,function(j){if(token!==draftToken)return;if(j&&j.success&&Number(j.price)>0){setRate(r,j.price);lockDraftPrice(itemId,r,j.price,token);setTimeout(function(){if(token!==draftToken)return;var v=Number(String(r.value||'').replace(/,/g,''));if(isFinite(v)&&Math.abs(v-Number(j.price))<0.0001)note(r,'قیمت نوع فروش «'+(j.sale_type_name||'انتخاب‌شده')+'» اعمال شد.',true);else note(r,'قیمت دریافت شد اما توسط فرم بازنویسی شد؛ در حال تثبیت قیمت…',false)},300)}else note(r,(j&&j.message)||'قیمت نوع فروش تعریف نشده است.',false)})}
function rows(force){qa('input[name*="[rate]"]').forEach(function(r){var row=r.closest('tr')||r.closest('.item')||r.parentNode,h=row&&q('input[name*="[itemid]"],input[name*="[item_id]"]',row);if(!h||!h.value||!st())return;var sg=st()+'|'+h.value+'|'+invoiceDate();if(!force&&r.dataset.scrSig===sg)return;r.dataset.scrSig=sg;get(h.value,function(j){var p=j&&j.success?Number(j.price):NaN;if(isFinite(p)&&p>0){setRate(r,p);note(r,'قیمت نوع فروش اعمال شد.',true)}else if(force){note(r,(j&&j.message)||'قیمت نوع فروش تعریف نشده است؛ قیمت فعلی ردیف حفظ شد.',false)}})})}
function clearDraft(keepFilters){stopDraftPriceLock();draftToken++;var sel=q('select[name="item_select"]');if(sel){/* 0.7.5.9: clear the native selector silently. Do NOT call selectpicker('val','') here because that emits changed.bs.select and Estimate Form Customizer can re-hydrate the just-cleared product. */sel.value='';if(window.jQuery){try{var $sel=window.jQuery(sel);$sel.selectpicker('refresh');$sel.selectpicker('render')}catch(e){}}}['input[name="rate"]','textarea[name="description"]','input[name="description"]','textarea[name="long_description"]','input[name="long_description"]','input[name="unit"]'].forEach(function(x){var e=q(x);if(e){e.value='';e.dispatchEvent(new Event('input',{bubbles:true}))}});var qty=q('input[name="quantity"]');if(qty){qty.value='1';qty.dispatchEvent(new Event('input',{bubbles:true}))}qa('.scr-price-note').forEach(function(n){var r=n.closest('td,.form-group');if(!r||!q('input[name*="[rate]"]',r))n.remove()});if(!keepFilters&&box){var c=q('#scrPfClear');if(c)c.click()}}
window.SCR_clearDraftSelection=function(){clearDraft(true)};
function compactDraftUI(add,draftRow){if(!add||!draftRow)return;draftRow.classList.add('scr-draft-row');add.classList.add('scr-compact-add');['width','height','min-width','max-width','min-height','max-height'].forEach(function(k){add.style.setProperty(k,'34px','important')});add.style.setProperty('padding','0','important');add.style.setProperty('margin','0','important');add.style.setProperty('border-radius','7px','important');add.style.setProperty('display','inline-flex','important');add.style.setProperty('align-items','center','important');add.style.setProperty('justify-content','center','important');qa('input.form-control,select.form-control,.bootstrap-select>.dropdown-toggle',draftRow).forEach(function(e){e.style.setProperty('height','36px','important');e.style.setProperty('min-height','36px','important');e.style.setProperty('padding','4px 8px','important');e.style.setProperty('font-size','13px','important');e.style.setProperty('border-radius','6px','important')});qa('textarea.form-control',draftRow).forEach(function(e){e.style.setProperty('height','54px','important');e.style.setProperty('min-height','54px','important');e.style.setProperty('padding','6px 8px','important');e.style.setProperty('resize','none','important')})}
function installDraftReset(){
 /* 0.7.6.8: Estimates are fully owned by Estimate Form Customizer. Invoice keeps
    the proven SCR/native controls; estimate gets no SCR button injection/binding. */
 if(location.pathname.indexOf('/admin/estimates/')!==-1)return;
 /* 0.7.6.7: Never move/re-parent/clone/replace Perfex's native green Add control.
    Estimate Form Customizer and Perfex both rely on its original DOM ancestry.
    The reset button is inserted as a sibling only. */
 var candidates=qa('button,a').filter(function(b){var oc=b.getAttribute('onclick')||'',cl=b.className||'';return /add_item_to_table/i.test(oc)||/add_item_to_table/.test(cl)}),add=candidates[0];
 if(!add){var r=q('input[name="rate"]');if(r){var row=r.closest('tr');add=row&&q('button.btn-success',row)}}
 if(!add)return;
 preservePricesAcrossAdd(add);
 var draftRow=add.closest('tr');if(!draftRow)return;
 compactDraftUI(add,draftRow);
 var old=q('#scrDraftReset');
 if(old){if(old.previousElementSibling!==add){old.remove();}else return;}
 var b=document.createElement('button');b.type='button';b.id='scrDraftReset';b.className='btn btn-default scr-draft-reset';b.title='پاک کردن کالای انتخاب‌شده';b.innerHTML='<i class="fa fa-times"></i>';
 b.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();clearDraft(true)});
 add.insertAdjacentElement('afterend',b);
 ['width','height','min-width','max-width','min-height','max-height'].forEach(function(k){b.style.setProperty(k,'34px','important')});
 b.style.setProperty('padding','0','important');b.style.setProperty('margin-inline-start','6px','important');b.style.setProperty('display','inline-flex','important');b.style.setProperty('align-items','center','important');b.style.setProperty('justify-content','center','important');
}

/* 0.7.5.2: Perfex rebuilds the invoice items table when the green Add button is
   used. On this installation that rebuild can copy the item's native/base rate (0)
   back into rows that already had an SCR sale price. Preserve only positive prices
   that existed immediately before Add, and seed the newly-created row from the
   resolved draft price. This does not recalculate or overwrite legitimate non-zero
   edits. */
function invoiceRateInputs(){return qa('input[name*="[rate]"]').filter(function(r){return !/^rate$/i.test(r.name||'')})}
function positiveNumber(v){var n=Number(String(v==null?'':v).replace(/,/g,''));return isFinite(n)&&n>0?n:0}
/* 0.7.5.3: after SCR restores a protected rate, notify Perfex's native invoice
   calculator as well. The previous fix preserved visible rates but Perfex totals
   could remain at zero because its own calculate_total() was not re-run. */
var scrTotalsTimer=null;
function recalcInvoiceTotals(){
 clearTimeout(scrTotalsTimer);
 scrTotalsTimer=setTimeout(function(){
  invoiceRateInputs().forEach(function(r){r.dispatchEvent(new Event('change',{bubbles:true}))});
  if(typeof window.calculate_total==='function'){try{window.calculate_total()}catch(e){}}
 },35);
}
function preservePricesAcrossAdd(add){if(!add||add.dataset.scrPreserveBound==='1')return;add.dataset.scrPreserveBound='1';
 add.addEventListener('click',function(){
  var draft=q('input[name="rate"]'),draftPrice=positiveNumber(draft&&draft.value),before=invoiceRateInputs(),snap=[];
  before.forEach(function(r){var v=positiveNumber(r.value);if(v>0)snap.push({el:r,name:r.name,value:v})});
  function restore(){
   snap.forEach(function(x){var r=(x.el&&document.documentElement.contains(x.el))?x.el:q('input[name="'+String(x.name).replace(/(["\\])/g,'\\$1')+'"]');if(r&&positiveNumber(r.value)<=0)setRate(r,x.value)});
   var now=invoiceRateInputs(),fresh=now.filter(function(r){return before.indexOf(r)===-1});
   if(draftPrice>0&&fresh.length){var r=fresh[fresh.length-1];if(positiveNumber(r.value)<=0)setRate(r,draftPrice)}
   recalcInvoiceTotals();
  }
  [40,120,280,600,1100].forEach(function(ms){setTimeout(restore,ms)});
 },true)
}
var draftDiscountLock=null,discountCache={};
function stopDraftDiscountLock(){if(draftDiscountLock&&draftDiscountLock.timer)clearInterval(draftDiscountLock.timer);draftDiscountLock=null}
function discountInput(){return q('input[name="discount"],input[name="discount_percent"],input[name*="[discount]"]')}
function setDiscount(el,v){if(!el)return;el.value=String(v);el.setAttribute('value',String(v));try{el.dispatchEvent(new Event('input',{bubbles:true}))}catch(e){}try{el.dispatchEvent(new Event('change',{bubbles:true}))}catch(e){}}
function discountNote(el,msg,ok){if(!el)return;var p=el.parentNode||el,n=p.querySelector('.scr-discount-note');if(!n){n=document.createElement('small');n.className='scr-discount-note';n.style.cssText='display:block;direction:rtl;margin-top:3px;font-weight:600;font-size:10px;line-height:1.3';p.appendChild(n)}n.textContent=msg||'';n.style.color=ok?'#16803c':'#b45309'}
function getDiscount(itemId,cb){if(!dp||!itemId){cb&&cb({found:false});return}var d=invoiceDate(),key=String(itemId)+'|'+d;if(discountCache[key]){cb(discountCache[key]);return}fetch(dp+'?itemid='+encodeURIComponent(itemId)+'&date='+encodeURIComponent(d),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'}).then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json()}).then(function(j){discountCache[key]=j;cb&&cb(j)}).catch(function(){cb&&cb({found:false,error:true})})}
function lockDraftDiscount(itemId,el,value,token){stopDraftDiscountLock();var expected=Number(value);if(!el||!isFinite(expected))return;var lock={item:String(itemId),expected:expected,token:token,until:Date.now()+4200,timer:null};draftDiscountLock=lock;function guard(){if(draftDiscountLock!==lock||token!==draftToken||Date.now()>lock.until){stopDraftDiscountLock();return}var sel=q('select[name="item_select"]');if(!sel||String(sel.value||'')!==lock.item){stopDraftDiscountLock();return}var cur=Number(String(el.value||'').replace(',','.'));if(!isFinite(cur)||Math.abs(cur-lock.expected)>.0001)setDiscount(el,lock.expected)}guard();lock.timer=setInterval(guard,100);setTimeout(function(){if(draftDiscountLock===lock)stopDraftDiscountLock()},4350)}
function applyDraftDiscount(itemId,attempt,token){if(token!==draftToken||!itemId)return;var sel=q('select[name="item_select"]');if(!sel||String(sel.value||'')!==String(itemId))return;var el=discountInput();if(!el&&attempt<12){setTimeout(function(){applyDraftDiscount(itemId,attempt+1,token)},120);return}if(!el)return;getDiscount(itemId,function(j){if(token!==draftToken)return;if(j&&j.found){var v=Number(j.base_percent);if(!isFinite(v))v=0;setDiscount(el,v);lockDraftDiscount(itemId,el,v,token);discountNote(el,'تخفیف گروه کالایی بر اساس تاریخ فاکتور: '+v+'٪'+(j.max_percent!=null?' (سقف '+Number(j.max_percent)+'٪)':''),true)}else{setDiscount(el,0);discountNote(el,'برای این گروه کالا در تاریخ فاکتور قانون تخفیف فعالی تعریف نشده است.',false)}})}
function onProductChange(sel){stopDraftPriceLock();stopDraftDiscountLock();var id=String(sel.value||'');draftToken++;if(!id)return;var t=draftToken;setTimeout(function(){applyDraftPrice(id,0,t);applyDraftDiscount(id,0,t)},40)}
var meta=null,box=null,allProductOptions=null;
function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function opts(a){return '<option value="">همه</option>'+a.map(function(x){return '<option value="'+esc(x.id)+'">'+esc(x.name)+'</option>'}).join('')}
function buildFilters(){var sel=q('select[name="item_select"]');if(!sel||box||!mp)return;allProductOptions=Array.prototype.map.call(sel.options,function(o){return o.cloneNode(true)});box=document.createElement('div');box.id='scr-product-filters';box.innerHTML='<div class="scr-pf-title"><i class="fa fa-filter"></i> فیلتر سریع انتخاب کالا</div><div class="scr-pf-grid"><div><label>کد کالا / رقم المنتج</label><input id="scrPfCode" class="form-control" placeholder="مثلاً S113"></div><div><label>کلکسیون</label><select id="scrPfGroup" class="form-control"></select></div><div><label>رنگ</label><select id="scrPfColor" class="form-control"></select></div><div><label>اندازه</label><select id="scrPfSize" class="form-control"></select></div></div><div class="scr-pf-actions"><span class="scr-pf-count" id="scrPfCount"></span><button type="button" class="btn btn-default btn-xs" id="scrPfClear"><i class="fa fa-eraser"></i> پاک کردن فیلترها</button></div>';var host=sel.closest('.form-group')||sel.parentNode;host.parentNode.insertBefore(box,host);fetch(mp+'?_='+Date.now(),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(x=>x.json()).then(function(j){if(!j||!j.success)throw new Error();meta=j;box.style.display='block';q('#scrPfGroup').innerHTML=opts(j.groups||[]);q('#scrPfColor').innerHTML=opts(j.colors||[]);q('#scrPfSize').innerHTML=opts(j.sizes||[]);['#scrPfGroup','#scrPfColor','#scrPfSize'].forEach(function(x){q(x).addEventListener('change',filterProducts)});var codeTimer=null;q('#scrPfCode').addEventListener('input',function(){clearTimeout(codeTimer);codeTimer=setTimeout(filterProducts,160)});q('#scrPfClear').addEventListener('click',function(){q('#scrPfGroup').value='';q('#scrPfColor').value='';q('#scrPfSize').value='';q('#scrPfCode').value='';filterProducts()});filterProducts()}).catch(function(){box.style.display='none'})}
var filterBusy=false,filterTimer=null,lastAllowed=null,lastFilterActive=false;
function refreshProductPicker(sel){if(!window.jQuery)return;var $s=window.jQuery(sel);try{/* 0.7.5.7: never destroy Perfex's picker on estimates; EFC owns its wrapper/layout. Rebuild only the menu from the native options. */if(!$s.data('selectpicker')&&typeof window.appSelectPicker==='function')window.appSelectPicker($s);$s.selectpicker('refresh');$s.selectpicker('render');var w=$s.closest('.bootstrap-select');if(w.length){w.css({display:'block',width:'100%',visibility:'visible'});w.find('button.dropdown-toggle').css({display:'block',width:'100%',visibility:'visible'})}}catch(e){try{$s.trigger('change')}catch(x){}}}
/* 0.7.6.9.7: true faceted/cascading filters. Each dropdown is rebuilt from
   products matching the OTHER active filters, so group/color/size remain mutually
   dependent while the code search constrains all three. */
function scrPfMatch(x,g,c,z,code,skip){return (skip==='g'||!g||String(x.group_id)===g)&&(skip==='c'||!c||String(x.color_id)===c)&&(skip==='z'||!z||String(x.size_id)===z)&&(!code||String(x.code||'').toLowerCase().indexOf(code)!==-1)}
function scrPfFacet(selectId,list,key,skip,g,c,z,code){var el=q(selectId);if(!el)return;var old=String(el.value||''),seen={};(meta.products||[]).forEach(function(x){if(scrPfMatch(x,g,c,z,code,skip)){var v=String(x[key]||'');if(v)seen[v]=1}});var html='<option value="">همه</option>';(list||[]).forEach(function(x){var v=String(x.id);if(seen[v])html+='<option value="'+esc(v)+'">'+esc(x.name)+'</option>'});el.innerHTML=html;if(old&&seen[old])el.value=old;else el.value=''}
function filterProducts(){if(filterBusy||!meta)return;var sel=q('select[name="item_select"]');if(!sel)return;if(!allProductOptions)allProductOptions=Array.prototype.map.call(sel.options,function(o){return o.cloneNode(true)});var ge=q('#scrPfGroup'),ce=q('#scrPfColor'),ze=q('#scrPfSize'),ke=q('#scrPfCode');var g=ge.value,c=ce.value,z=ze.value,code=ke.value.trim().toLowerCase();
 /* Rebuild each facet against the other filters. Read values again afterwards in
    case a now-impossible selection was cleared. */
 filterBusy=true;scrPfFacet('#scrPfGroup',meta.groups||[],'group_id','g',g,c,z,code);scrPfFacet('#scrPfColor',meta.colors||[],'color_id','c',g,c,z,code);scrPfFacet('#scrPfSize',meta.sizes||[],'size_id','z',g,c,z,code);filterBusy=false;
 g=ge.value;c=ce.value;z=ze.value;var active=!!(g||c||z||code),allowed={},n=0;(meta.products||[]).forEach(function(x){var ok=scrPfMatch(x,g,c,z,code,'');if(ok){allowed[String(x.id)]=1;n++}});lastAllowed=allowed;lastFilterActive=active;var current=String(sel.value||''),frag=document.createDocumentFragment();
var empty=document.createElement('option');empty.value='';empty.textContent='افزودن کالا';frag.appendChild(empty);
(meta.products||[]).forEach(function(x){var v=String(x.id);if(active&&!allowed[v])return;var o=document.createElement('option');o.value=v;o.textContent=String(x.name||x.code||('کالا #'+v));o.setAttribute('data-scr-canonical','1');frag.appendChild(o)});
filterBusy=true;while(sel.firstChild)sel.removeChild(sel.firstChild);sel.appendChild(frag);if(current&&(!active||allowed[current]))sel.value=current;else if(current){sel.value='';draftToken++}filterBusy=false;q('#scrPfCount').textContent=n.toLocaleString('fa-IR')+' کالا';refreshProductPicker(sel)}
function enforceFilteredPicker(){if(!lastFilterActive||filterBusy)return;clearTimeout(filterTimer);filterTimer=setTimeout(function(){var sel=q('select[name="item_select"]');if(!sel||!lastAllowed)return;var bad=Array.prototype.some.call(sel.options,function(o){return o.value&&!lastAllowed[String(o.value)]});if(bad)filterProducts()},30)}
function rqSetSelect(sel,val,label,fire){if(!sel||val==null||String(val)==='')return false;var v=String(val),has=Array.prototype.some.call(sel.options||[],function(o){return String(o.value)===v});if(!has&&label){var o=document.createElement('option');o.value=v;o.textContent=String(label);sel.appendChild(o);has=true}if(!has)return false;sel.value=v;if(window.jQuery){try{var $s=window.jQuery(sel);$s.val(v);if($s.data('selectpicker')){$s.selectpicker('refresh');$s.selectpicker('render')}}catch(e){}}if(fire)sel.dispatchEvent(new Event('change',{bubbles:true}));return true}
function rqSellerSelect(){var direct=q('select[name="invoice_seller"],select[name="seller_staff_id"],select[name="seller_id"],select[name="seller"],select[name="salesman"],select[name="sales_person"],select[name*="seller_staff"],select[name*="salesman"],select[name*="sales_person"]');if(direct)return direct;var labels=qa('label,.control-label');for(var i=0;i<labels.length;i++){var t=String(labels[i].textContent||'').replace(/\s+/g,' ').trim();if(/مامور\s*فروش|مأمور\s*فروش|فروشنده/.test(t)){var b=labels[i].closest('.form-group,.form-group-select,.col-md-12,.col-md-8,.col-md-6,.col-md-5,.col-md-4,.col-md-3')||labels[i].parentNode,ss=b&&q('select',b);if(ss)return ss}}return null}
function hydrateSalesRequest(){var rq=window.SCR_SALES_REQUEST;if(!rq||window.__scrRqHydrated||window.__scrRqHydrating)return;var client=q('select[name="clientid"]'),typ=q('select[name="invoice_type"]'),sel=q('select[name="item_select"]');if(!client||!typ||!sel){setTimeout(hydrateSalesRequest,250);return}window.__scrRqHydrating=true;rqSetSelect(client,rq.client_id,rq.client_name,true);rqSetSelect(typ,rq.sale_type_id,'',true);var seller=rqSellerSelect();if(seller&&rq.seller_staff_id)rqSetSelect(seller,rq.seller_staff_id,rq.seller_name,true);var list=Array.isArray(rq.items)?rq.items.slice():[],i=0;function finish(){window.__scrRqHydrating=false;window.__scrRqHydrated=true}function next(){if(i>=list.length){finish();return}var x=list[i++],id=String(x.id||x.item_id||x.product_id||'');if(!id){next();return}var s=q('select[name="item_select"]');if(!s){i--;setTimeout(next,250);return}if(!Array.prototype.some.call(s.options,function(o){return String(o.value)===id})){var o=document.createElement('option');o.value=id;o.textContent=x.name||('کالا #'+id);s.appendChild(o)}s.value=id;if(window.jQuery)try{var $s=window.jQuery(s);$s.val(id);if($s.data('selectpicker')){$s.selectpicker('refresh');$s.selectpicker('render')}}catch(e){}s.dispatchEvent(new Event('change',{bubbles:true}));setTimeout(function(){var qty=q('input[name="quantity"]');if(qty){qty.value=String(x.qty||x.quantity||1);qty.dispatchEvent(new Event('input',{bubbles:true}))}var rate=q('input[name="rate"]');if(rate&&(!typ||!typ.value)&&Number(x.price)>0)setRate(rate,x.price);var add=qa('button,a').filter(function(b){return /add_item_to_table/i.test(b.getAttribute('onclick')||'')})[0];if(!add){var rr=rate&&rate.closest('tr');add=rr&&q('button.btn-success',rr)}if(!add){i--;setTimeout(next,300);return}add.click();setTimeout(next,600)},750)}setTimeout(next,500)}
function scrEsc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]})}
function installQuickCustomer(){
 if(!window.SCR_IS_INVOICE||q('#scrQuickCustomerBtn'))return;
 var sel=q('select[name="clientid"]');if(!sel)return;
 var host=sel.closest('.form-group')||sel.parentNode;if(!host)return;
 var parent=host.parentNode,wrap=document.createElement('div');wrap.className='scr-client-inline';
 parent.insertBefore(wrap,host);wrap.appendChild(host);
 var b=document.createElement('button');b.type='button';b.id='scrQuickCustomerBtn';b.className='btn btn-default scr-client-add';b.title='ایجاد مشتری جدید';b.setAttribute('aria-label','ایجاد مشتری جدید');b.innerHTML='<i class="fa fa-plus"></i>';wrap.appendChild(b);
 b.addEventListener('click',openQuickCustomer);
}
function quickCustomerModal(){
 var m=q('#scrQuickCustomerModal');if(m)return m;
 var d=document.createElement('div');d.innerHTML='<div class="modal fade scr-qc-modal" id="scrQuickCustomerModal" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg" role="document"><div class="modal-content" dir="rtl"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="بستن"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">ایجاد سریع مشتری</h4></div><form id="scrQuickCustomerForm"><div class="modal-body"><div class="alert alert-danger" id="scrQcError" style="display:none"></div><div class="scr-qc-grid"><div class="form-group"><label>نام <span class="scr-qc-required">*</span></label><input class="form-control" name="firstname" required></div><div class="form-group"><label>نام خانوادگی <span class="scr-qc-required">*</span></label><input class="form-control" name="lastname" required></div><div class="form-group"><label>شماره موبایل <span class="scr-qc-required">*</span></label><input class="form-control" name="phonenumber" inputmode="tel" required></div><div class="form-group"><label>استان</label><input class="form-control" name="state"></div><div class="form-group"><label>شهر</label><input class="form-control" name="city"></div><div class="form-group"><label>منطقه</label><input class="form-control" name="region"></div><div class="form-group scr-qc-full"><label>آدرس</label><textarea class="form-control" name="address" rows="2"></textarea></div><div class="form-group scr-qc-full"><label>نحوه آشنایی</label><input class="form-control" name="referral"></div></div><small class="text-muted" style="display:block;margin-top:12px">این اطلاعات مستقیماً در پرونده مشتریان سامانه ثبت می‌شود.</small></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">انصراف</button><button type="submit" class="btn btn-primary" id="scrQcSave">ذخیره و انتخاب مشتری</button></div></form></div></div></div>';
 document.body.appendChild(d.firstChild);m=q('#scrQuickCustomerModal');q('#scrQuickCustomerForm').addEventListener('submit',saveQuickCustomer);return m;
}
function openQuickCustomer(){var m=quickCustomerModal();var f=q('#scrQuickCustomerForm');if(f)f.reset();var e=q('#scrQcError');if(e){e.style.display='none';e.textContent=''}if(window.jQuery)window.jQuery(m).modal('show');}
function saveQuickCustomer(ev){
 ev.preventDefault();var f=ev.currentTarget,btn=q('#scrQcSave'),err=q('#scrQcError');if(err){err.style.display='none';err.textContent=''}
 var fd=new FormData(f);if(window.csrfData&&csrfData.token_name)fd.append(csrfData.token_name,csrfData.hash);
 if(btn){btn.disabled=true;btn.dataset.old=btn.textContent;btn.textContent='در حال ذخیره...'}
 fetch(window.SCR_QUICK_CUSTOMER_URL,{method:'POST',body:fd,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json()}).then(function(j){if(!j||!j.success)throw new Error((j&&j.message)||'ثبت مشتری انجام نشد.');var sel=q('select[name="clientid"]');if(sel){var id=String(j.id),opt=Array.prototype.find.call(sel.options,function(o){return String(o.value)===id});if(!opt){opt=document.createElement('option');opt.value=id;opt.textContent=j.name||('مشتری #'+id);sel.appendChild(opt)}sel.value=id;if(window.jQuery){try{var $s=window.jQuery(sel);$s.val(id);if($s.data('selectpicker')){$s.selectpicker('refresh');$s.selectpicker('render')}else $s.trigger('change')}catch(e){}}sel.dispatchEvent(new Event('change',{bubbles:true}))}if(window.jQuery)window.jQuery('#scrQuickCustomerModal').modal('hide');}).catch(function(ex){if(err){err.textContent=ex.message||'خطا در ثبت مشتری.';err.style.display='block'}}).finally(function(){if(btn){btn.disabled=false;btn.textContent=btn.dataset.old||'ذخیره و انتخاب مشتری'}});
}
/* 0.7.6.9.5: reliable invoice auto/manual adjustment.
   Read the already-calculated Perfex GRAND TOTAL from its visible total row and
   recover the pre-adjustment amount by subtracting the current native adjustment.
   This avoids guessing Perfex subtotal selectors and keeps taxes/discounts intact. */
var scrAutoAdjBusy=false,scrAutoAdjInstalled=false;
function scrNum(v){
 var s=String(v==null?'':v).trim();if(!s)return NaN;
 var fa='۰۱۲۳۴۵۶۷۸۹',ar='٠١٢٣٤٥٦٧٨٩';
 s=s.replace(/[۰-۹]/g,function(c){return String(fa.indexOf(c))}).replace(/[٠-٩]/g,function(c){return String(ar.indexOf(c))});
 s=s.replace(/[٬،,]/g,'').replace(/[٫]/g,'.').replace(/[^0-9.\-]/g,'');
 if(!s||s==='-'||s==='.'||s==='-.')return NaN;var n=Number(s);return isFinite(n)?n:NaN
}
function scrVal(v){var n=scrNum(v);return isFinite(n)?n:0}
function scrFmt(v){var n=Math.round(Number(v)*100)/100;if(Math.abs(n)<0.005)n=0;return n.toFixed(2).replace(/\.00$/,'').replace(/(\.\d)0$/,'$1')}
function scrNativeAdjustment(){return q('input[name="adjustment"]')}
function scrDigitsLatin(s){var fa='۰۱۲۳۴۵۶۷۸۹',ar='٠١٢٣٤٥٦٧٨٩';return String(s||'').replace(/[۰-۹]/g,function(c){return String(fa.indexOf(c))}).replace(/[٠-٩]/g,function(c){return String(ar.indexOf(c))})}
function scrAmountFromRow(el){
 var t=scrDigitsLatin(el&&el.textContent||'').replace(/[٬،]/g,',').replace(/[٫]/g,'.');
 var m=t.match(/-?\d[\d,]*(?:\.\d+)?/g);if(!m||!m.length)return NaN;
 for(var i=m.length-1;i>=0;i--){var n=Number(m[i].replace(/,/g,''));if(isFinite(n))return n}return NaN
}
function scrFindLabeledAmount(words){
 var nodes=qa('tr,.row,.form-group');
 for(var i=nodes.length-1;i>=0;i--){var tx=(nodes[i].textContent||'').replace(/\s+/g,' ').trim();if(!tx)continue;var hit=false;for(var w=0;w<words.length;w++)if(tx.indexOf(words[w])!==-1){hit=true;break}if(!hit)continue;var n=scrAmountFromRow(nodes[i]);if(isFinite(n))return n}
 return NaN
}
function scrDisplayedGrandTotal(){return scrFindLabeledAmount(['جمع کل','Grand Total','Total'])}
function scrPreAdjustmentTotal(){
 var native=scrNativeAdjustment(),cur=scrVal(native&&native.value),grand=scrDisplayedGrandTotal();
 if(isFinite(grand))return grand-cur;
 /* Secondary fallback: the visible pre-adjustment/subtotal row. */
 return scrFindLabeledAmount(['جمع جزئی','Subtotal']);
}
function scrNotifyNativeAdjustment(native){
 try{native.dispatchEvent(new Event('input',{bubbles:true}))}catch(e){}
 try{native.dispatchEvent(new Event('change',{bubbles:true}))}catch(e){}
 try{native.dispatchEvent(new Event('keyup',{bubbles:true}))}catch(e){}
}
function scrClampManual(showMessage){var manual=q('#scrManualAdjustment');if(!manual)return 0;var lim=Math.abs(Number(window.SCR_MANUAL_ADJUSTMENT_LIMIT||5000)),v=scrVal(manual.value),c=Math.max(-lim,Math.min(lim,v));if(Math.abs(c-v)>0.004){manual.value=scrFmt(c);if(showMessage&&window.alert_float)alert_float('warning','حداکثر تعدیل دستی مجاز '+lim.toLocaleString('en-US')+' دینار است.')}return c}
function scrSyncAutoAdjustment(recalc){
 if(scrAutoAdjBusy||!window.SCR_IS_INVOICE)return;var native=scrNativeAdjustment(),manual=q('#scrManualAdjustment'),auto=q('#scrAutoAdjustment');if(!native||!manual||!auto)return;
 var current=scrVal(native.value),base=scrPreAdjustmentTotal();if(!isFinite(base))return;
 var rounded=Math.round(base/1000)*1000,automatic=rounded-base,manualVal=scrClampManual(false),combined=automatic+manualVal;
 auto.value=scrFmt(automatic);
 if(Math.abs(combined-current)>0.004){
   native.value=scrFmt(combined);native.setAttribute('value',native.value);
   scrAutoAdjBusy=true;
   scrNotifyNativeAdjustment(native);
   try{if(recalc&&typeof window.calculate_total==='function')window.calculate_total()}catch(e){}
   scrAutoAdjBusy=false;
 }
}
function installAutoAdjustment(){
 if(!window.SCR_IS_INVOICE)return;var native=scrNativeAdjustment();if(!native)return;
 if(!q('#scrManualAdjustment')){
   var host=native.closest('.form-group')||native.parentNode;if(!host)return;
   /* Preserve an existing native adjustment as manual value when editing. */
   var initialManual=scrVal(native.value);
   var manual=document.createElement('input');manual.type='number';manual.step='0.01';manual.id='scrManualAdjustment';manual.className=native.className||'form-control';manual.value=scrFmt(initialManual);manual.setAttribute('dir','ltr');manual.setAttribute('aria-label','تعدیل دستی');var lim=Math.abs(Number(window.SCR_MANUAL_ADJUSTMENT_LIMIT||5000));manual.min=-lim;manual.max=lim;manual.setAttribute('data-limit',String(lim));
   var auto=document.createElement('input');auto.type='text';auto.id='scrAutoAdjustment';auto.className=native.className||'form-control';auto.readOnly=true;auto.value='0';auto.setAttribute('dir','ltr');auto.setAttribute('aria-label','تعدیل اتوماتیک');
   var box=document.createElement('div');box.className='scr-adjustments-grid';
   var a=document.createElement('div');a.innerHTML='<label class="control-label">تعدیل اتوماتیک</label>';a.appendChild(auto);
   var m=document.createElement('div');m.innerHTML='<label class="control-label">تعدیل دستی</label>';m.appendChild(manual);box.appendChild(a);box.appendChild(m);
   host.insertBefore(box,native);native.style.setProperty('display','none','important');
   manual.addEventListener('input',function(){scrClampManual(false);scrSyncAutoAdjustment(true)});manual.addEventListener('change',function(){scrClampManual(true);scrSyncAutoAdjustment(true)});
 }
 if(!scrAutoAdjInstalled){
   scrAutoAdjInstalled=true;
   var old=window.calculate_total;if(typeof old==='function'&&!old.__scrAutoWrapped){var wrapped=function(){var r=old.apply(this,arguments);if(!scrAutoAdjBusy)setTimeout(function(){scrSyncAutoAdjustment(false)},20);return r};wrapped.__scrAutoWrapped=true;window.calculate_total=wrapped}
   var form=native.closest('form');if(form)form.addEventListener('submit',function(){scrClampManual(true);scrSyncAutoAdjustment(false)},true);
 }
 setTimeout(function(){scrSyncAutoAdjustment(true)},120);
}
function start(){buildFilters();installDraftReset();installQuickCustomer();installAutoAdjustment();setTimeout(hydrateSalesRequest,900);document.addEventListener('change',function(e){if(!e.target)return;if(e.target.matches('select[name="item_select"]'))onProductChange(e.target);if(e.target.matches('select[name="invoice_type"]')){stopDraftPriceLock();priceCache={};draftToken++;var x=q('select[name="item_select"]');if(x&&x.value)onProductChange(x);rows(true)}if(e.target.matches('input[name="date"]')){stopDraftPriceLock();stopDraftDiscountLock();priceCache={};discountCache={};draftToken++;var x=q('select[name="item_select"]');if(x&&x.value)onProductChange(x);rows(true);recalcInvoiceTotals()}},false);var moTimer=null;new MutationObserver(function(){clearTimeout(moTimer);moTimer=setTimeout(function(){buildFilters();installDraftReset();installQuickCustomer();installAutoAdjustment();enforceFilteredPicker()},60)}).observe(document.body,{childList:true,subtree:true});rows(true)}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();})();</script>

<style>
/* 0.7.6.9.7.15 — payment UX; preserve native submit and force only the visible native amount editable in partial mode. */
.scr-payment-extra-row{display:grid!important;grid-template-columns:minmax(180px,260px) minmax(180px,260px)!important;gap:12px!important;justify-content:start!important;direction:rtl!important;margin:0 0 12px!important}
.scr-payment-extra-row .form-group{margin:0!important}.scr-payment-extra-row .control-label{color:#9a5700!important;font-weight:700!important;font-size:13px!important;margin-bottom:5px!important;display:block!important}
.scr-payment-extra-row select,.scr-payment-extra-row .bootstrap-select>.dropdown-toggle{border:1px solid #ead1ad!important;border-radius:7px!important;background:#fff!important;min-height:40px!important;height:40px!important;width:100%!important;box-shadow:none!important}
@media(max-width:800px){.scr-payment-extra-row{grid-template-columns:1fr!important}}
</style>
<script>
/* 0.7.6.9.7.15 — preserve the native Perfex payment form and submit path exactly.
   We only add two UI selectors and toggle readonly on the REAL native amount input. */
(function(){
 function norm(s){return String(s||'').replace(/\s+/g,' ').trim()}
 function invoiceRef(root){
  var h=root.querySelector('input[name="invoiceid"],input[name="invoice_id"]');if(h&&parseInt(h.value,10)>0)return {invoice_id:parseInt(h.value,10)};
  var f=root.querySelector('form');var a=f?String(f.getAttribute('action')||''):'';var m=a.match(/(?:record_payment|payment|add_payment)[\/\-](\d+)/i);if(m)return {invoice_id:parseInt(m[1],10)};
  var tm=norm(root.textContent).match(/(?:فاکتور|invoice)\s*#?\s*([0-9۰-۹٠-٩]+)/i);if(tm){var n=tm[1].replace(/[۰-۹]/g,function(c){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)}).replace(/[٠-٩]/g,function(c){return '٠١٢٣٤٥٦٧٨٩'.indexOf(c)});return {invoice_number:n}}
  return {};
 }
 function enforceReturnLock(root){if(!window.SCR_RETURN_LOCK_URL||!root||root.dataset.scrReturnCheck==='1')return;root.dataset.scrReturnCheck='1';var ref=invoiceRef(root),qs=[];if(ref.invoice_id)qs.push('invoice_id='+encodeURIComponent(ref.invoice_id));if(ref.invoice_number)qs.push('invoice_number='+encodeURIComponent(ref.invoice_number));if(!qs.length){root.dataset.scrReturnCheck='';return;}
  fetch(window.SCR_RETURN_LOCK_URL+'?'+qs.join('&'),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'}).then(function(r){return r.json()}).then(function(j){if(!j||!j.locked)return;var form=root.querySelector('form');if(!form)return;form.dataset.scrReturnLocked='1';if(!form.querySelector('.scr-return-payment-lock')){var box=document.createElement('div');box.className='alert alert-warning scr-return-payment-lock';box.style.direction='rtl';box.innerHTML='<strong>پرداخت متوقف است.</strong> این فاکتور دارای مرجوعی است و ابتدا باید تسویه مالی مرجوعی تعیین تکلیف شود.';form.insertBefore(box,form.firstChild)}form.querySelectorAll('button[type="submit"],input[type="submit"],.btn-success').forEach(function(b){b.disabled=true;b.style.setProperty('display','none','important')});form.addEventListener('submit',function(e){e.preventDefault();e.stopImmediatePropagation();alert('این فاکتور دارای مرجوعی است و دریافت وجه جدید مجاز نیست.');},true);}).catch(function(){root.dataset.scrReturnCheck='';});
 }
 function byLabel(root,re,tag){var labs=root.querySelectorAll('label,.control-label');for(var i=0;i<labs.length;i++){if(!re.test(norm(labs[i].textContent)))continue;var g=labs[i].closest('.form-group,[class*="col-md-"],.row')||labs[i].parentNode;var x=g&&g.querySelector(tag||'input,select');if(x)return x}return null}
 function amountOf(root){
  var a=Array.prototype.slice.call(root.querySelectorAll('input[name="amount"],input[name="payment_amount"]')).filter(function(x){return x.type!=='hidden'&&x.offsetParent!==null});
  if(a.length)return a[0];
  var x=byLabel(root,/مبلغ\s*دریافت\s*شده|مبلغ\s*دریافت|amount/i,'input');return x&&x.type!=='hidden'?x:null
 }
 function modeOf(root){return root.querySelector('select[name="paymentmode"]')||root.querySelector('select[name="payment_mode"]')||byLabel(root,/روش\s*پرداخت|payment\s*method/i,'select')}
 function install(root){
  if(!root)return;enforceReturnLock(root);
  if(root.querySelector('.scr-payment-extra-row'))return;
  var amount=amountOf(root),mode=modeOf(root);if(!amount||!mode)return;
  var form=amount.closest('form')||mode.closest('form');if(!form)return;
  var initial=amount.value;
  var extra=document.createElement('div');extra.className='scr-payment-extra-row';
  extra.innerHTML='<div class="form-group"><label class="control-label">نوع دریافت</label><select class="form-control scr-payment-kind"><option value="full">پرداخت کامل</option><option value="partial">پرداخت بخشی از مبلغ</option></select></div><div class="form-group"><label class="control-label">دستگاه POS</label><select class="form-control scr-payment-pos"><option value="">انتخاب دستگاه POS</option></select></div>';
  var firstRow=amount.closest('.row')||mode.closest('.row');
  if(firstRow&&firstRow.parentNode)firstRow.parentNode.insertBefore(extra,firstRow);else form.insertBefore(extra,form.firstChild);
  var kind=extra.querySelector('.scr-payment-kind'),pos=extra.querySelector('.scr-payment-pos');
  (window.SCR_POS_DEVICES||[]).forEach(function(d){var o=document.createElement('option');o.value=String(d.id);o.textContent=d.name;pos.appendChild(o)});
  /* 7.21: persist only the POS device id so the server-side after_payment_added hook can associate it even when Perfex submits via its native AJAX handler. */
  function rememberPos(){var v=String(pos.value||'');document.cookie='scr_pos_device_hint='+encodeURIComponent(v)+'; path=/; SameSite=Lax'}
  pos.addEventListener('change',rememberPos);
  /* 7.19: Perfex may omit a custom offline payment mode from this modal. Restore configured modes, especially POS. */
  (window.SCR_PAYMENT_MODES||[]).forEach(function(pm){
   var exists=Array.prototype.some.call(mode.options,function(o){return String(o.value)===String(pm.id)});
   if(!exists){var o=document.createElement('option');o.value=String(pm.id);o.textContent=pm.name;mode.appendChild(o)}
  });
  if(window.jQuery&&jQuery.fn.selectpicker){try{jQuery(mode).selectpicker('refresh')}catch(e){}}
  /* 7.20: never add SCR metadata fields before native Perfex submission. 7.19 did so
     during modal installation and this changed the payload seen by the native payment AJAX path. */
  function selectedModeMeta(){var v=String(mode.value||''),label=mode.options&&mode.selectedIndex>=0?mode.options[mode.selectedIndex].text:'';var pm=(window.SCR_PAYMENT_MODES||[]).filter(function(x){return String(x.id)===v})[0];return {label:label,category:pm?String(pm.category||''):''}}
  function syncPosVisibility(){var m=selectedModeMeta(),isPos=m.category==='pos'||/\bpos\b/i.test(m.label);var g=pos.closest('.form-group');if(g)g.style.display=isPos?'':'none';if(!isPos){pos.value='';if(window.jQuery&&jQuery.fn.selectpicker){try{jQuery(pos).selectpicker('refresh')}catch(e){}}}}
  mode.addEventListener('change',syncPosVisibility);
  syncPosVisibility();
  var partialGuard=null, mirror=null;
  function ensureMirror(){
   if(mirror&&document.documentElement.contains(mirror))return mirror;
   mirror=document.createElement('input');
   mirror.type='text'; mirror.className=amount.className; mirror.setAttribute('inputmode','decimal');
   mirror.setAttribute('autocomplete','off'); mirror.setAttribute('aria-label','مبلغ دریافت شده');
   mirror.style.cssText=amount.style.cssText; mirror.style.display='none'; mirror.style.width='100%';
   mirror.value=amount.value;
   amount.parentNode.insertBefore(mirror,amount.nextSibling);
   mirror.addEventListener('input',function(){amount.value=mirror.value;amount.setAttribute('value',mirror.value);if(window.jQuery){try{jQuery(amount).val(mirror.value)}catch(e){}}});
   return mirror;
  }
  function editable(on){
   amount.disabled=false;amount.removeAttribute('disabled');
   var m=ensureMirror();
   if(on){
    /* Keep Perfex's real amount input intact for native submit, but use a nameless
       editable twin for typing. This bypasses customizers that forcibly restore readonly. */
    m.value=amount.value; m.style.display='block';
    amount.style.display='none';
    m.readOnly=false;m.disabled=false;m.removeAttribute('readonly');m.removeAttribute('disabled');
   }else{
    if(partialGuard){clearInterval(partialGuard);partialGuard=null}
    m.style.display='none'; amount.style.display='';
    if(initial!==''&&initial!=null){amount.value=initial;amount.setAttribute('value',initial)}
   }
  }
  function setPartial(on){
   editable(on);
   if(partialGuard){clearInterval(partialGuard);partialGuard=null}
   if(on){
    partialGuard=setInterval(function(){
     if(!document.documentElement.contains(amount)||kind.value!=='partial'){clearInterval(partialGuard);partialGuard=null;return}
     amount.disabled=false;amount.removeAttribute('disabled');
     var m=ensureMirror(); if(m.style.display==='none'){m.style.display='block';amount.style.display='none'}
     /* Keep the REAL Perfex amount synchronized continuously. Some invoice customizers
        restore the outstanding total after change/input events; the mirror must win. */
     amount.value=m.value; amount.setAttribute('value',m.value); if(window.jQuery){try{jQuery(amount).val(m.value)}catch(e){}}
    },150);
    setTimeout(function(){var m=ensureMirror();m.focus();try{m.select()}catch(e){}},30)
   }
  }
  kind.addEventListener('change',function(){setPartial(this.value==='partial')});
  function syncPartialAmount(){
   if(kind.value!=='partial'||!mirror)return;
   amount.disabled=false;amount.removeAttribute('disabled');amount.readOnly=false;amount.removeAttribute('readonly');
   amount.value=mirror.value;amount.setAttribute('value',mirror.value);
   if(window.jQuery){try{jQuery(amount).val(mirror.value)}catch(e){}}
  }
  /* Perfex payment modal may be submitted by a button/AJAX handler before the native
     form submit event. Synchronize in capture phase before click handlers serialize it. */
  form.addEventListener('mousedown',syncPartialAmount,true);
  form.addEventListener('pointerdown',syncPartialAmount,true);
  form.addEventListener('click',syncPartialAmount,true);
  // Keep custom metadata OUT of Perfex form until the actual native submit event.
  // This avoids changing native validation/serialization during normal modal use.
  form.addEventListener('submit',function(){
    if(mirror&&kind.value==='partial'){syncPartialAmount()}
    amount.disabled=false;amount.removeAttribute('disabled');
    if(kind.value==='partial'){amount.readOnly=false;amount.removeAttribute('readonly')}
    /* Add custom metadata only at the same late submit point used by the last
       known-good 7.18 payment path. */
    var hk=form.querySelector('input[name="scr_payment_kind"]');if(!hk){hk=document.createElement('input');hk.type='hidden';hk.name='scr_payment_kind';form.appendChild(hk)}hk.value=kind.value;
    var hp=form.querySelector('input[name="scr_pos_device_id"]');if(!hp){hp=document.createElement('input');hp.type='hidden';hp.name='scr_pos_device_id';form.appendChild(hp)}hp.value=pos.value||'';
  },true);
  if(window.jQuery&&jQuery.fn.selectpicker){try{jQuery(kind).selectpicker();jQuery(pos).selectpicker()}catch(e){}}
 }
 function scan(){var roots=document.querySelectorAll('.modal.in,.modal.show,.modal[style*="display: block"]');for(var i=0;i<roots.length;i++)install(roots[i])}
 document.addEventListener('shown.bs.modal',function(e){setTimeout(function(){install(e.target)},40)},true);
 if(window.jQuery)jQuery(document).on('shown.bs.modal',function(e){setTimeout(function(){install(e.target)},40)});
 new MutationObserver(function(){setTimeout(scan,30)}).observe(document.body,{childList:true,subtree:true});
 scan();
})();

/* 0.7.6.9.7.18 — received payments table: raw Gregorian payment date + captured POS device. */
(function(){
 function txt(x){return String((x&&x.textContent)||'').replace(/\s+/g,' ').trim()}
 function findTable(){
  var ts=document.querySelectorAll('.modal.in table,.modal.show table,.modal[style*="display: block"] table');
  for(var i=0;i<ts.length;i++){
   var h=txt(ts[i].querySelector('thead'));
   if(/#\s*پرداخت|شیوه\s*پرداخت/.test(h)&&/تاریخ/.test(h)&&/مبلغ/.test(h))return ts[i];
  }
  return null;
 }
 function enhance(){
  var t=findTable();if(!t||t.dataset.scrPayMeta==='1'||!window.SCR_PAYMENT_META_URL)return;
  var rows=Array.prototype.slice.call(t.querySelectorAll('tbody tr')),ids=[];
  rows.forEach(function(r){var c=r.querySelector('td');var id=parseInt(txt(c).replace(/[^0-9]/g,''),10);if(id)ids.push(id)});
  if(!ids.length)return;
  t.dataset.scrPayMeta='loading';
  var sep=window.SCR_PAYMENT_META_URL.indexOf('?')>=0?'&':'?';
  fetch(window.SCR_PAYMENT_META_URL+sep+'ids='+encodeURIComponent(ids.join(',')),{credentials:'same-origin'})
   .then(function(r){return r.json()}).then(function(j){
    var map=(j&&j.rows)||{};
    var ths=t.querySelectorAll('thead th'),dateIdx=-1;
    for(var k=0;k<ths.length;k++)if(/تاریخ/.test(txt(ths[k]))){dateIdx=k;break}
    if(!t.querySelector('thead th.scr-pos-col')){var th=document.createElement('th');th.className='scr-pos-col';th.textContent='دستگاه POS';t.querySelector('thead tr').appendChild(th)}
    rows.forEach(function(r){
     var cells=r.querySelectorAll('td'),id=parseInt(txt(cells[0]).replace(/[^0-9]/g,''),10),m=map[String(id)]||{};
     if(dateIdx>=0&&cells[dateIdx]&&m.date)cells[dateIdx].textContent=m.date;
     var td=r.querySelector('td.scr-pos-col');if(!td){td=document.createElement('td');td.className='scr-pos-col';r.appendChild(td)}td.textContent=m.pos_name||'—';
    });
    t.dataset.scrPayMeta='1';
   }).catch(function(){t.dataset.scrPayMeta='';});
 }
 document.addEventListener('shown.bs.modal',function(){setTimeout(enhance,120)},true);
 if(window.jQuery)jQuery(document).on('shown.bs.modal',function(){setTimeout(enhance,120)});
 new MutationObserver(function(){setTimeout(enhance,80)}).observe(document.body,{childList:true,subtree:true});
 setTimeout(enhance,300);
 /* 7.19: also show captured POS device on the main Admin > Payments list. */
 function enhancePaymentsPage(){
  if(!/\/admin\/payments(?:\/|$|\?)/.test(location.pathname+location.search)||!window.SCR_PAYMENT_META_URL)return;
  var tables=document.querySelectorAll('table');
  Array.prototype.forEach.call(tables,function(t){
   var h=txt(t.querySelector('thead'));if(!/#\s*پرداخت/.test(h)||!/شیوه\s*پرداخت/.test(h)||t.dataset.scrPosList==='1')return;
   var rows=Array.prototype.slice.call(t.querySelectorAll('tbody tr')),ids=[];rows.forEach(function(r){var c=r.querySelector('td');var id=parseInt(txt(c).replace(/[^0-9]/g,''),10);if(id)ids.push(id)});if(!ids.length)return;
   t.dataset.scrPosList='loading';var sep=window.SCR_PAYMENT_META_URL.indexOf('?')>=0?'&':'?';fetch(window.SCR_PAYMENT_META_URL+sep+'ids='+encodeURIComponent(ids.join(',')),{credentials:'same-origin'}).then(function(r){return r.json()}).then(function(j){
    var map=(j&&j.rows)||{};if(!t.querySelector('thead th.scr-pos-col')){var th=document.createElement('th');th.className='scr-pos-col';th.textContent='دستگاه POS';t.querySelector('thead tr').appendChild(th)}
    rows.forEach(function(r){var c=r.querySelector('td'),id=parseInt(txt(c).replace(/[^0-9]/g,''),10),m=map[String(id)]||{},td=document.createElement('td');td.className='scr-pos-col';td.textContent=m.pos_name||'—';r.appendChild(td)});t.dataset.scrPosList='1';
   }).catch(function(){t.dataset.scrPosList=''})
  })
 }
 new MutationObserver(function(){setTimeout(enhancePaymentsPage,120)}).observe(document.body,{childList:true,subtree:true});setTimeout(enhancePaymentsPage,500);
})();
</script>
JS;
}
