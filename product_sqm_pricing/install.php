<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI=&get_instance();
$table=db_prefix().'product_sqm_price_rules';
if (!$CI->db->table_exists($table)) {
    $CI->db->query("CREATE TABLE `{$table}` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `group_id` int(11) NOT NULL,
      `sale_type_key` varchar(191) NOT NULL,
      `sale_type_name` varchar(191) DEFAULT NULL,
      `pricing_method` varchar(20) NOT NULL DEFAULT 'sqm',
      `price_per_sqm` decimal(20,4) NOT NULL DEFAULT 0,
      `fixed_price` decimal(20,4) NOT NULL DEFAULT 0,
      `valid_from` date NOT NULL,
      `valid_to` date DEFAULT NULL,
      `active` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` datetime NOT NULL,
      `updated_at` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `lookup_rule` (`group_id`,`sale_type_key`,`valid_from`,`valid_to`,`active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=".$CI->db->char_set.';');
}

// v0.1.9: tariff periods are closed intervals; valid_to is mandatory.
if ($CI->db->table_exists($table) && $CI->db->field_exists('valid_to',$table)) {
    // Existing incomplete rows are left visible for cleanup, but new saves require valid_to.
}

// v0.1.13.0: fixed-price rules alongside square-meter rules.
if ($CI->db->table_exists($table)) {
    if (!$CI->db->field_exists('pricing_method',$table)) $CI->db->query("ALTER TABLE `{$table}` ADD `pricing_method` varchar(20) NOT NULL DEFAULT 'sqm' AFTER `sale_type_name`");
    if (!$CI->db->field_exists('fixed_price',$table)) $CI->db->query("ALTER TABLE `{$table}` ADD `fixed_price` decimal(20,4) NOT NULL DEFAULT 0 AFTER `price_per_sqm`");
}

// v0.1.15.0: optional product dimensions for more specific tariff rules.
if ($CI->db->table_exists($table)) {
    if (!$CI->db->field_exists('reeds',$table)) $CI->db->query("ALTER TABLE `{$table}` ADD `reeds` varchar(100) NULL AFTER `sale_type_name`");
    if (!$CI->db->field_exists('density',$table)) $CI->db->query("ALTER TABLE `{$table}` ADD `density` varchar(100) NULL AFTER `reeds`");
    if (!$CI->db->field_exists('weave_type',$table)) $CI->db->query("ALTER TABLE `{$table}` ADD `weave_type` varchar(191) NULL AFTER `density`");
    if (!$CI->db->field_exists('commodity_code',$table)) $CI->db->query("ALTER TABLE `{$table}` ADD `commodity_code` varchar(191) NULL AFTER `weave_type`");
}

// v0.1.16.0: optional exact product size for tariff specificity.
if ($CI->db->table_exists($table) && !$CI->db->field_exists('size',$table)) { $CI->db->query("ALTER TABLE `{$table}` ADD `size` varchar(191) NULL AFTER `commodity_code`"); }

// v0.1.17.0: optional product design/pattern for tariff specificity.
if ($CI->db->table_exists($table) && !$CI->db->field_exists('design',$table)) { $CI->db->query("ALTER TABLE `{$table}` ADD `design` varchar(191) NULL AFTER `size`"); }
