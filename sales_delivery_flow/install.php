<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI=&get_instance();
if(!$CI->db->table_exists(db_prefix().'sdf_exit_permits')){
 $CI->db->query('CREATE TABLE `'.db_prefix().'sdf_exit_permits` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT "approved",`permit_date` DATE NULL,`warehouse_cutoff_id` INT NULL,`note` TEXT NULL,`created_by` INT NULL,`created_at` DATETIME NOT NULL, PRIMARY KEY (`id`), KEY `invoice_id` (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}
if(!$CI->db->table_exists(db_prefix().'sdf_customer_deliveries')){
 $CI->db->query('CREATE TABLE `'.db_prefix().'sdf_customer_deliveries` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`goods_delivery_id` INT NULL,`recipient_name` VARCHAR(191) NULL,`recipient_phone` VARCHAR(80) NULL,`delivery_date` DATE NOT NULL,`delivery_method` VARCHAR(120) NULL,`carrier_company` VARCHAR(191) NULL,`carrier_contact` VARCHAR(120) NULL,`carrier_driver` VARCHAR(191) NULL,`carrier_vehicle` VARCHAR(120) NULL,`carrier_tracking` VARCHAR(191) NULL,`note` TEXT NULL,`created_by` INT NULL,`created_at` DATETIME NOT NULL, PRIMARY KEY (`id`), KEY `invoice_id` (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}
if(!$CI->db->table_exists(db_prefix().'sdf_exit_permits_v2')){
 $CI->db->query('CREATE TABLE `'.db_prefix().'sdf_exit_permits_v2` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT "approved",`invoice_date` VARCHAR(32) NULL,`permit_date` VARCHAR(32) NULL,`warehouse_cutoff_id` INT NOT NULL DEFAULT 0,`note` TEXT NULL,`created_by` INT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `invoice_id` (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}

if(!$CI->db->table_exists(db_prefix().'sdf_item_locations')){
 $CI->db->query('CREATE TABLE `'.db_prefix().'sdf_item_locations` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`itemable_id` INT NOT NULL,`location` VARCHAR(255) NOT NULL DEFAULT "",`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `invoice_item` (`invoice_id`,`itemable_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}
