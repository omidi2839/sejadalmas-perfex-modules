<?php
defined('BASEPATH') or exit('No direct script access allowed');
$tables=[
 'scr_sale_types'=>"CREATE TABLE `".db_prefix()."scr_sale_types` (`id` int NOT NULL AUTO_INCREMENT,`name` varchar(300) NOT NULL,`active` tinyint NOT NULL DEFAULT 1,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `legacy_id` (`legacy_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci",
 'scr_discount_rules'=>"CREATE TABLE `".db_prefix()."scr_discount_rules` (`id` int NOT NULL AUTO_INCREMENT,`group_id` int NOT NULL,`base_percent` decimal(8,2) NOT NULL DEFAULT 0,`max_percent` decimal(8,2) NOT NULL DEFAULT 0,`valid_from` date NOT NULL,`valid_to` date NOT NULL,`active` tinyint NOT NULL DEFAULT 1,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),KEY `group_date` (`group_id`,`valid_from`,`valid_to`),UNIQUE KEY `legacy_id` (`legacy_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci"
]; foreach($tables as $n=>$sql)if(!$CI->db->table_exists(db_prefix().$n))$CI->db->query($sql);
// Compatibility schemas used by Warehouse/Product SQM and existing historical invoices.
if(!$CI->db->table_exists(db_prefix().'item_discount_sell_type'))$CI->db->query("CREATE TABLE `".db_prefix()."item_discount_sell_type` (`st_id` int NOT NULL AUTO_INCREMENT,`st_name` varchar(300),`st_status` int,PRIMARY KEY (`st_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
if(!$CI->db->table_exists(db_prefix().'item_sale_type_prices'))$CI->db->query("CREATE TABLE `".db_prefix()."item_sale_type_prices` (`id` int unsigned NOT NULL AUTO_INCREMENT,`itemid` int unsigned NOT NULL,`sale_type_id` int unsigned NOT NULL,`price` varchar(100) NOT NULL DEFAULT '0',`order` tinyint NULL,PRIMARY KEY (`id`),KEY `item_sale` (`itemid`,`sale_type_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
if(!$CI->db->field_exists('invoice_type',db_prefix().'invoices'))$CI->db->query("ALTER TABLE `".db_prefix()."invoices` ADD `invoice_type` INT NOT NULL DEFAULT 0");
if(!$CI->db->field_exists('invoice_marketer',db_prefix().'invoices'))$CI->db->query("ALTER TABLE `".db_prefix()."invoices` ADD `invoice_marketer` INT NOT NULL DEFAULT 0");
// One-time non-destructive migration from old module data.
$old=db_prefix().'item_discount_sell_type'; if($CI->db->table_exists($old)){foreach($CI->db->get($old)->result() as $r){$exists=$CI->db->where('legacy_id',$r->st_id)->get(db_prefix().'scr_sale_types')->row();if(!$exists)$CI->db->insert(db_prefix().'scr_sale_types',['name'=>$r->st_name,'active'=>(int)$r->st_status,'legacy_id'=>(int)$r->st_id,'created_at'=>date('Y-m-d H:i:s')]);}}
$od=db_prefix().'item_discount_discount'; if($CI->db->table_exists($od)){foreach($CI->db->get($od)->result() as $r){$exists=$CI->db->where('legacy_id',$r->dis_id)->get(db_prefix().'scr_discount_rules')->row();if(!$exists&&$r->dis_from&&$r->dis_until)$CI->db->insert(db_prefix().'scr_discount_rules',['group_id'=>(int)$r->dis_group,'base_percent'=>(float)$r->dis_percent,'max_percent'=>(float)$r->dis_top_percent,'valid_from'=>$r->dis_from,'valid_to'=>$r->dis_until,'active'=>1,'legacy_id'=>(int)$r->dis_id,'created_at'=>date('Y-m-d H:i:s')]);}}

// Phase 2: sales hierarchy and discount-edit access.
$h=db_prefix().'scr_sales_hierarchy';
if(!$CI->db->table_exists($h))$CI->db->query("CREATE TABLE `{$h}` (`id` int NOT NULL AUTO_INCREMENT,`manager_staff_id` int NOT NULL,`seller_staff_id` int NOT NULL,`active` tinyint NOT NULL DEFAULT 1,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `manager_seller` (`manager_staff_id`,`seller_staff_id`),UNIQUE KEY `legacy_id` (`legacy_id`),KEY `seller` (`seller_staff_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
$a=db_prefix().'scr_discount_access';
if(!$CI->db->table_exists($a))$CI->db->query("CREATE TABLE `{$a}` (`id` int NOT NULL AUTO_INCREMENT,`manager_staff_id` int NOT NULL DEFAULT 0,`staff_id` int NOT NULL,`can_edit_line_discount` tinyint NOT NULL DEFAULT 0,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `manager_staff` (`manager_staff_id`,`staff_id`),KEY `staff_id` (`staff_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
elseif(!$CI->db->field_exists('manager_staff_id',$a))$CI->db->query("ALTER TABLE `{$a}` ADD `manager_staff_id` INT NOT NULL DEFAULT 0 AFTER `id`");
$oldh=db_prefix().'item_discount_set_middle_staff';if($CI->db->table_exists($oldh)){foreach($CI->db->get($oldh)->result() as $r){$legacy=(int)$r->middle_id;$m=(int)$r->middle_manager;$st=(int)$r->middle_staff;if(!$m||!$st||$m==$st)continue;$exists=$CI->db->where('legacy_id',$legacy)->get($h)->row();if(!$exists&&!$CI->db->where('manager_staff_id',$m)->where('seller_staff_id',$st)->get($h)->row())$CI->db->insert($h,['manager_staff_id'=>$m,'seller_staff_id'=>$st,'active'=>1,'legacy_id'=>$legacy,'created_at'=>date('Y-m-d H:i:s')]);}}

// Phase 3: commission rules (non-destructive migration from item_discount).
$ct=db_prefix().'scr_commission_rules';
if(!$CI->db->table_exists($ct))$CI->db->query("CREATE TABLE `{$ct}` (`id` int NOT NULL AUTO_INCREMENT,`group_id` int NOT NULL,`sale_type_id` int NOT NULL,`seller_percent` decimal(8,3) NOT NULL DEFAULT 0,`marketer_percent` decimal(8,3) NOT NULL DEFAULT 0,`active` tinyint NOT NULL DEFAULT 1,`legacy_id` int NULL,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `group_sale` (`group_id`,`sale_type_id`),UNIQUE KEY `legacy_id` (`legacy_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
// IMPORTANT: legacy commission import is controlled only by scr_ensure_phase3_schema().
// Do not import here on every activation/update; doing so can resurrect a rule the user deliberately deleted.
if(function_exists('scr_ensure_phase3_schema')) scr_ensure_phase3_schema();

// Phase 4 schema is maintained by the module bootstrap to support upgrades without destructive remigration.
if(function_exists('scr_ensure_phase4_schema')) scr_ensure_phase4_schema();

if(function_exists('scr_ensure_cashier_schema')) scr_ensure_cashier_schema();
