<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Product SQM Pricing
Description: قیمت‌گذاری خودکار محصولات بر اساس گروه کالا، نوع فروش، بازه زمانی و متراژ حاصل از سایز.
Version: 0.1.17.3
Requires at least: 2.3.*
Author: Custom
*/
define('PRODUCT_SQM_PRICING_MODULE_NAME', 'product_sqm_pricing');
register_activation_hook(PRODUCT_SQM_PRICING_MODULE_NAME, 'psp_activation_hook');
register_language_files(PRODUCT_SQM_PRICING_MODULE_NAME, [PRODUCT_SQM_PRICING_MODULE_NAME]);
hooks()->add_action('admin_init', 'psp_admin_init');
hooks()->add_action('app_admin_head', 'psp_admin_assets');
function psp_activation_hook(){ require_once(__DIR__.'/install.php'); }
function psp_admin_init(){
    $CI=&get_instance();
    // Upgrade schema safely on normal admin load; module updates do not always rerun activation hooks.
    if (!class_exists('Product_sqm_pricing_model', false)) { $CI->load->model('product_sqm_pricing/product_sqm_pricing_model'); }
    $CI->product_sqm_pricing_model->ensure_schema();
    if (is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('psp-pricing', [
            'name'=>_l('psp_menu'), 'href'=>admin_url('product_sqm_pricing'), 'icon'=>'fa fa-calculator', 'position'=>38
        ]);
    }
}
function psp_admin_assets(){
    // Warehouse commodity modal is AJAX/dynamic. Load the bridge from the admin HEAD
    // (the same reliable hook used by item_discount), then wait for the modal in JS.
    echo '<script>window.PSP_CONFIG='.json_encode([
        'calculateUrl'=>admin_url('product_sqm_pricing/calculate'),
        'rulesUrl'=>admin_url('product_sqm_pricing'),
        'fixedModeUrl'=>admin_url('product_sqm_pricing/fixed_mode'),
        'messages'=>[
            'missing'=>_l('psp_missing_rule'), 'invalidSize'=>_l('psp_invalid_size'), 'calculated'=>_l('psp_calculated')
        ]
    ]).';</script>';
    echo '<script defer src="'.module_dir_url(PRODUCT_SQM_PRICING_MODULE_NAME,'assets/js/product_pricing.js?v=0.1.17.3').'"></script>';
}
