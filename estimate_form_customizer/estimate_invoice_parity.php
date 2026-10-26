<?php
/**
 * Module Name: Estimate Invoice Parity
 * Description: Reuses the stable invoice form rules for estimate forms. Only stock caps are omitted on estimates.
 * Version: 0.1.1
 * Requires at least: 3.0.*
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Keep invoices unchanged: only BEFORE_ESTIMATE hooks use this function.
 * Delegates pricing/discount to the already-installed stable invoice guardrails,
 * then restores the customer-requested quantities verbatim. No stock reservation,
 * stock mutation or invoice hooks are registered by this module.
 */
function eip_estimate_guardrails($data) {
    if (!is_array($data)) return $data;
    if (!function_exists('ifc_server_guardrails')) {
        log_message('error', 'Estimate Invoice Parity: invoice_form_customizer is not active');
        return $data;
    }
    $original = [];
    foreach (['newitems','items'] as $bucket) {
        if (!isset($data[$bucket]) || !is_array($data[$bucket])) continue;
        foreach ($data[$bucket] as $key => $row) {
            if (is_array($row) && array_key_exists('qty', $row)) {
                $original[$bucket][$key] = $row['qty'];
            }
        }
    }

    // Accept the estimate-side sale type name without changing the payload schema.
    $working = $data;
    $syntheticSaleType = false;
    if (!isset($working['invoice_type'])) {
        foreach (['estimate_type','sale_type_id','sale_type'] as $key) {
            if (isset($working[$key]) && $working[$key] !== '') {
                $working['invoice_type'] = $working[$key];
                $syntheticSaleType = true;
                break;
            }
        }
    }
    $working = ifc_server_guardrails($working);
    if (!is_array($working)) return $data;
    if ($syntheticSaleType) unset($working['invoice_type']);

    // The exact original requested quantities are authoritative for estimates.
    // Do not alter positive values or silently round them to stock.
    foreach ($original as $bucket => $rows) {
        foreach ($rows as $key => $qty) {
            if (isset($working[$bucket][$key]) && is_array($working[$bucket][$key])) {
                $working[$bucket][$key]['qty'] = $qty;
            }
        }
    }
    return $working;
}

hooks()->add_filter('before_estimate_added', 'eip_estimate_guardrails', 9999);
hooks()->add_filter('before_estimate_updated', 'eip_estimate_guardrails', 9999);

function eip_is_estimate_form() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return (bool) preg_match('~/admin/estimates/estimate(?:/\d+)?/?$~i', $path);
}

function eip_estimate_assets() {
    if (!eip_is_estimate_form()) return;
    $module='estimate_invoice_parity';
    $version='0.1.1';
    $css=module_dir_url($module,'assets/css/estimate-form-customizer.css').'?v='.$version;
    $js=module_dir_url($module,'assets/js/estimate-form-customizer.js').'?v='.$version;
    $policy=admin_url('invoice_form_customizer/policy');
    echo '<link rel="stylesheet" href="'.html_escape($css).'" data-eip-version="'.$version.'">';
    // The policy endpoint is owned by the installed, confirmed invoice module.
    // Namespace independently so this cannot alter the invoice page initializer.
    echo '<script>(function(){
      window.EIPConfig={version:"0.1.1",policyUrl:'.json_encode($policy).'};
      if(window.__eipLoaderStarted)return;window.__eipLoaderStarted=true;
      var url='.json_encode($js).',tries=0,loading=false,loaded=false;
      function start(){
       if(loaded||loading)return;
       if(!window.jQuery||typeof window.jQuery.fn==="undefined"){
        if(++tries<400)setTimeout(start,50);return;
       }
       loading=true;
       var tag=document.createElement("script");tag.src=url;tag.async=false;
       tag.onload=function(){loaded=true;loading=false;document.documentElement.dataset.eipLoaded="1";
         if(window.EstimateInvoiceParity&&typeof window.EstimateInvoiceParity.apply==="function"){
          window.EstimateInvoiceParity.apply();[200,600,1400,2800].forEach(function(ms){setTimeout(window.EstimateInvoiceParity.apply,ms)});
         }
       };
       tag.onerror=function(){loading=false;document.documentElement.dataset.eipAssetError="1";
        if(++tries<4)setTimeout(start,400);
       };
       (document.head||document.documentElement).appendChild(tag);
      }
      start();document.addEventListener("DOMContentLoaded",start);
    })();</script>';
}
// Versions of Perfex with a protected header may bypass app_admin_head.
// Emitting the same loader in the footer is harmless because it has a singleton guard.
hooks()->add_action('app_admin_head', 'eip_estimate_assets', 70);
hooks()->add_action('app_admin_footer', 'eip_estimate_assets', 70);
