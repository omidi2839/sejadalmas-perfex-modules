<?php
/**
 * Module Name: فرآیند تحویل کالا
 * Description: مدیریت یکپارچه مجوز خروج، خروج/ارسال کالا و تحویل مشتری از زیر منوی فروش
 * Version: 0.2.46
 * Requires at least: 2.3.*
 */
defined('BASEPATH') or exit('No direct script access allowed');
define('SDF_MODULE_NAME','sales_delivery_flow');
register_activation_hook(SDF_MODULE_NAME,'sdf_activate');
function sdf_activate(){ require_once __DIR__.'/install.php'; }
// Register after the native Sales menu and Sales Commercial Rules have finished building their children.
hooks()->add_action('admin_init','sdf_runtime_server_trace',1);
// 0.2.39: Controlled Lock is enforced server-side for non-admin staff. Administrators are the explicit exception.
hooks()->add_action('admin_init','sdf_invoice_hard_lock_guard',5);
hooks()->add_action('admin_init','sdf_native_invoice_rate_repair_guard',6);
// 0.2.40: one-time verified repair is independent of the Native invoice URL/AJAX route.
hooks()->add_action('admin_init','sdf_verified_zero_rate_migration',7);
hooks()->add_action('admin_init','sdf_admin_init',999);
// 0.2.40: UI lock only hides actions/selection; it never inserts/removes/reorders table cells.
// 0.2.41: do not depend on footer JavaScript for invoice locking. Modify the native DataTable row on the server.
hooks()->add_filter('invoices_table_row_data','sdf_locked_invoice_table_row',999,2);
// 0.2.41: repair/refresh native invoice item rates at the model object boundary used by View/PDF.
hooks()->add_filter('get_invoice','sdf_native_invoice_object_rates',999,1);
hooks()->add_filter('before_admin_view_invoice_pdf','sdf_native_invoice_object_rates',999,1);
hooks()->add_filter('invoice_html_pdf_data','sdf_native_invoice_object_rates',999,1);
// Footer lock disabled: previous diagnostics proved footer execution is not reliable on this installation.
// hooks()->add_action('app_admin_footer','sdf_controlled_lock_footer',998);
// 0.2.37: removed invoice-list DOM mutation that hid Edit/Delete/checkboxes and could disturb DataTable rendering.
// hooks()->add_action('app_admin_footer','sdf_invoice_lifecycle_footer',999);
hooks()->add_action('after_invoice_view_as_client_link','sdf_invoice_direct_diagnostic_probe',1);
hooks()->add_action('after_invoice_view_as_client_link','sdf_invoice_tab');
hooks()->add_action('invoice_add_good_delivery_tab_content','sdf_invoice_tab_content',20);
// Sales menu is centrally managed by Sales Commercial Rules.

// 0.2.34: server-first diagnostic. This does not depend on any browser/footer hook.
function sdf_runtime_server_trace(){
 if(!is_staff_logged_in()) return;
 $CI=&get_instance();
 // 0.2.35: persist runtime evidence in DB, not Session. This executes server-side on admin_init.
 $table=db_prefix().'sdf_runtime_diagnostic_trace';
 $CI->db->query("CREATE TABLE IF NOT EXISTS `{$table}` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`created_at` DATETIME NOT NULL,`staff_id` INT NOT NULL DEFAULT 0,`request_uri` TEXT NULL,`request_method` VARCHAR(12) NULL,`is_ajax` TINYINT(1) NOT NULL DEFAULT 0,`invoice_id` INT NOT NULL DEFAULT 0,`invoice_number` VARCHAR(100) NULL,`snapshot_json` LONGTEXT NULL,PRIMARY KEY (`id`),KEY `invoice_id` (`invoice_id`),KEY `created_at` (`created_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $uri='/'.trim((string)$CI->uri->uri_string(),'/');
 if(stripos($uri,'/invoices')===false && stripos($uri,'/sales_delivery_flow')===false) return;
 // Do not let the diagnostic page itself become the "latest invoice" evidence.
 if(stripos($uri,'/sales_delivery_flow/runtime_diagnostic')!==false) return;
 $id=0;
 $patterns=[
  '~/(?:admin/)?invoices/(?:list_invoices|invoice|delete|pdf|get_invoice_data_ajax)/(\d+)~i',
  '~/(?:admin/)?invoices/(?:record_payment|payment)/(\d+)~i',
  '~/sales_delivery_flow/(?:invoice|return_invoice|print_return|print_warehouse_exit|print_customer_delivery)/(\d+)~i'
 ];
 foreach($patterns as $rx){if(preg_match($rx,$uri,$m)){ $id=(int)$m[1]; break; }}
 $snap=['uri'=>$uri,'referer'=>(string)($_SERVER['HTTP_REFERER']??'')]; $num=null;
 if($id>0){
  $inv=$CI->db->where('id',$id)->get(db_prefix().'invoices')->row_array();
  if($inv){
   $num=(string)($inv['number']??'');
   $items=$CI->db->where('rel_id',$id)->where('rel_type','invoice')->order_by('item_order','ASC')->order_by('id','ASC')->get(db_prefix().'itemable')->result_array();
   $snap['invoice']=$inv; $snap['raw_items']=$items;
   $CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_trace_db_model');
   $snap['lock']=$CI->sdf_trace_db_model->invoice_lock_state($id);
   $snap['return']=$CI->sdf_trace_db_model->invoice_return_state($id);
   $pt=db_prefix().'invoicepaymentrecords'; $snap['payments']=$CI->db->table_exists($pt)?$CI->db->where('invoiceid',$id)->order_by('id','ASC')->get($pt)->result_array():[];
   $snap['routes']=['view'=>admin_url('invoices/list_invoices/'.$id),'edit'=>admin_url('invoices/invoice/'.$id),'delete'=>admin_url('invoices/delete/'.$id),'pdf'=>admin_url('invoices/pdf/'.$id)];
  }
 }
 $CI->db->insert($table,[
  'created_at'=>date('Y-m-d H:i:s'),'staff_id'=>(int)get_staff_user_id(),'request_uri'=>$uri,
  'request_method'=>strtoupper((string)($_SERVER['REQUEST_METHOD']??'')),'is_ajax'=>$CI->input->is_ajax_request()?1:0,
  'invoice_id'=>$id,'invoice_number'=>$num,'snapshot_json'=>json_encode($snap,JSON_UNESCAPED_UNICODE)
 ]);
 // keep table small
 $CI->db->query("DELETE FROM `{$table}` WHERE id NOT IN (SELECT id FROM (SELECT id FROM `{$table}` ORDER BY id DESC LIMIT 200) x)");
}
function sdf_invoice_direct_diagnostic_probe($invoice){
 if(!$invoice||empty($invoice->id))return; $id=(int)$invoice->id; $num=(string)($invoice->number??'');
 echo '<span id="sdf-direct-probe" data-invoice-id="'.$id.'" data-invoice-number="'.html_escape($num).'" style="display:none"></span>';
 echo '<script>(function(){try{var e=document.getElementById("sdf-direct-probe");if(!e)return;var x={source:"after_invoice_view_as_client_link",time:new Date().toISOString(),invoice_id:parseInt(e.getAttribute("data-invoice-id")||"0",10),invoice_number:e.getAttribute("data-invoice-number")||"",url:location.href,title:document.title};localStorage.setItem("sdf_runtime_direct_invoice",JSON.stringify(x));}catch(_){}})();</script>';
}

function sdf_admin_init(){
 $CI=&get_instance();
 if(!(is_admin() || has_permission('invoices','','view'))) return;
 // Both operational screens live under the existing Sales menu; no standalone sidebar menu is created.
 $CI->app_menu->add_sidebar_children_item('sales',[
  'slug'=>'sdf-exit-shipping','name'=>'خروج و ارسال کالا','href'=>admin_url('sales_delivery_flow'),'position'=>70,'icon'=>'fa fa-truck'
 ]);
 $CI->app_menu->add_sidebar_children_item('sales',[
  'slug'=>'sdf-product-returns','name'=>'مرجوعی کالا','href'=>admin_url('sales_delivery_flow/returns'),'position'=>71,'icon'=>'fa fa-check-square-o'
 ]);
}
function sdf_invoice_tab($invoice){
 if(!$invoice||empty($invoice->id)) return;
 echo '<li role="presentation"><a href="'.admin_url('sales_delivery_flow/invoice/'.$invoice->id).'">فرآیند تحویل کالا</a></li>';
}
function sdf_invoice_tab_content($invoice_id){}



// LEGACY/DISABLED in 0.2.37: operational lock must not disable native Edit/Delete. Kept only for rollback/reference.
// This server-side guard blocks direct POSTs to Perfex's invoice edit endpoint as well as UI edits.
function sdf_native_invoice_rate_repair_guard(){
 if(!is_staff_logged_in())return;$CI=&get_instance();$uri=trim((string)$CI->uri->uri_string(),'/');$id=0;
 // Native routes are inconsistent across full-page, modal and AJAX rendering. Resolve the id from URI, GET or POST.
 if(preg_match('~(?:^|/)invoices/(?:list_invoices|pdf|invoice|get_invoice_data_ajax)/(\d+)(?:/|$)~i',$uri,$m))$id=(int)$m[1];
 if($id<=0){foreach(['invoice_id','invoiceid','id'] as $k){$v=$CI->input->get_post($k,true);if(is_numeric($v)&&(int)$v>0){$id=(int)$v;break;}}}
 if($id<=0)return;
 $CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_rate_repair');
 $r=$CI->sdf_rate_repair->repair_native_invoice_rates($id);
 if(empty($r['ok']) && function_exists('log_message'))log_message('error','SDF native rate repair invoice '.$id.': '.json_encode($r,JSON_UNESCAPED_UNICODE));
}

// Repair legacy zero-rate rows once, before any invoice table/modal/PDF needs them.
// Safety: repair_native_invoice_rates writes only when reconstructed line totals match the stored invoice subtotal.
function sdf_verified_zero_rate_migration(){
 if(!is_staff_logged_in())return;$CI=&get_instance();
 $key='sdf_verified_rate_migration_0241';
 $it=db_prefix().'itemable';$iv=db_prefix().'invoices';
 if(!$CI->db->table_exists($it)||!$CI->db->table_exists($iv)){update_option($key,'done');return;}
 $rows=$CI->db->select('DISTINCT rel_id',false)->from($it)->where('rel_type','invoice')->where('rate <=',0)->where('rel_id >',0)->limit(500)->get()->result_array();
 $CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_rate_migration');
 $changed=0;$failed=[];
 foreach($rows as $r){$id=(int)($r['rel_id']??0);if($id<=0)continue;$x=$CI->sdf_rate_migration->repair_native_invoice_rates($id);if(!empty($x['ok']))$changed+=(int)($x['changed']??0);elseif(($x['reason']??'')!=='items')$failed[$id]=$x;}
 // 0.2.41: intentionally do not set a permanent done flag; legacy zero-rate rows may appear after module upgrades/imports.
 if(function_exists('log_message'))log_message('error','SDF 0.2.40 verified rate migration: changed='.$changed.' failed='.json_encode($failed,JSON_UNESCAPED_UNICODE));
}


// 0.2.41: Native DataTable filter. This changes only HTML inside existing cells; column count/order is untouched.
function sdf_locked_invoice_table_row($row,$aRow){
 if(!is_array($row)||!is_array($aRow))return $row;
 // 0.2.44: the customized invoice header has 7 visible columns
 // (checkbox, invoice, amount, date, customer, due date, status), while the
 // server row reaching this filter has only 5 cells (checkbox/invoice/amount/customer/status).
 // Restore ONLY the two missing native values; do not reorder or rewrite any existing cell.
 // This preserves the working price bridge and Controlled Lock from 0.2.43.
 if(count($row)===5){
   $invoiceDate=(string)($aRow['date']??'');
   $dueDate=(string)($aRow['duedate']??'');
   if($invoiceDate!=='' && function_exists('_d'))$invoiceDate=_d($invoiceDate);
   if($dueDate!=='' && function_exists('_d'))$dueDate=_d($dueDate);
   array_splice($row,3,0,[$invoiceDate!==''?$invoiceDate:'-']);
   array_splice($row,5,0,[$dueDate!==''?$dueDate:'-']);
 }

 $id=0;foreach(['id','invoiceid','invoice_id'] as $k){if(isset($aRow[$k])&&is_numeric($aRow[$k])){$id=(int)$aRow[$k];break;}}
 // Customized invoice tables may omit the id key from aRow. Recover it from the native view/edit/delete URL already present in row HTML.
 if($id<=0){$blob=implode(' ',array_map(function($v){return is_string($v)?$v:'';},$row));if(preg_match('~(?:list_invoices|invoice|delete)/(\d+)~i',$blob,$m))$id=(int)$m[1];}
 if($id<=0)return $row;
 $CI=&get_instance();$CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_table_lock');
 $st=$CI->sdf_table_lock->invoice_lock_state($id);if(empty($st['locked']))return $row;
 foreach($row as $i=>$html){
  if(!is_string($html))continue;
  // Remove native Edit/Delete anchors only. Keep View and every TD/DataTable column intact.
  $html=preg_replace('~<a\\b[^>]*href=["\\\'][^"\\\']*/invoices/(?:invoice|delete)/'.$id.'(?:[^"\\\']*)["\\\'][^>]*>.*?</a>~isu','',$html);
  // Perfex may render action links with relative URLs or translated text in the invoice-number cell.
  $html=preg_replace('~<a\\b(?=[^>]*(?:href=["\\\'][^"\\\']*(?:invoice|delete)/'.$id.'|class=["\\\'][^"\\\']*(?:_edit|_delete)))[^>]*>.*?</a>~isu','',$html);
  // Customized Perfex rows may use onclick/# actions instead of a direct delete URL. Remove only anchors whose action text/handler is Edit/Delete for this invoice.
  $html=preg_replace_callback('~<a\b[^>]*>.*?</a>~isu',function($m) use($id){$a=$m[0];$plain=trim(strip_tags($a));if(preg_match('~(?:delete_invoice|delete\s*\(|/invoices/(?:invoice|delete)/'.$id.')~i',$a))return '';if(preg_match('~^(?:ویرایش|حذف|Edit|Delete)$~iu',$plain))return '';return $a;},$html);
  // Locked invoices cannot participate in bulk delete. Disable only the checkbox element, not its cell.
  $html=preg_replace('~<input\\b([^>]*type=["\\\']checkbox["\\\'][^>]*)>~isu','<input$1 disabled="disabled" data-sdf-locked="1">',$html);
  $row[$i]=$html;
 }
 return $row;
}

// 0.2.41: Repair persisted rates and refresh the already-loaded invoice object so the same request renders correct prices.
function sdf_native_invoice_object_rates($invoice){
 if(!$invoice)return $invoice;$id=(int)(is_object($invoice)?($invoice->id??0):($invoice['id']??0));if($id<=0)return $invoice;
 $CI=&get_instance();$CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_object_rate');
 $CI->sdf_object_rate->repair_native_invoice_rates($id);
 $dbRows=$CI->db->where('rel_id',$id)->where('rel_type','invoice')->order_by('item_order','ASC')->order_by('id','ASC')->get(db_prefix().'itemable')->result_array();
 $rates=[];foreach($dbRows as $r)$rates[(int)$r['id']]=(float)$r['rate'];
 if(is_object($invoice)&&isset($invoice->items)&&is_array($invoice->items))foreach($invoice->items as &$it){$iid=(int)(is_object($it)?($it->id??0):($it['id']??0));if($iid&&isset($rates[$iid])){if(is_object($it))$it->rate=$rates[$iid];else $it['rate']=$rates[$iid];}}unset($it);
 if(is_array($invoice)&&isset($invoice['items'])&&is_array($invoice['items']))foreach($invoice['items'] as &$it){$iid=(int)(is_object($it)?($it->id??0):($it['id']??0));if($iid&&isset($rates[$iid])){if(is_object($it))$it->rate=$rates[$iid];else $it['rate']=$rates[$iid];}}unset($it);
 return $invoice;
}

function sdf_invoice_hard_lock_guard(){
 if(!is_staff_logged_in()) return;
 // Controlled Lock applies to everyone by default. Admin override must be explicit per request.
 $adminOverride=is_admin() && (string)get_instance()->input->get('sdf_admin_override',true)==='1';
 if($adminOverride) return;
 $CI=&get_instance();
 $uri=trim((string)$CI->uri->uri_string(),'/');
 // 0.2.25: bulk actions remain available for normal invoices. Only locked invoice ids are removed from the submitted selection.
 if(preg_match('~(?:^|/)invoices/bulk_action(?:/|$)~i',$uri) && $CI->input->post()){
   $key=is_array($CI->input->post('ids'))?'ids':(is_array($CI->input->post('invoice_ids'))?'invoice_ids':'');
   if($key){
     $ids=(array)$CI->input->post($key);$CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_lifecycle');$allowed=[];$blocked=0;
     foreach($ids as $bid){$bid=(int)$bid;if($bid<=0)continue;$bst=$CI->sdf_lifecycle->invoice_lock_state($bid);if(!empty($bst['locked'])){$blocked++;continue;}$allowed[]=$bid;}
     $_POST[$key]=$allowed;
     if($blocked)set_alert('warning',$blocked.' فاکتور قفل عملیاتی از عملیات گروهی کنار گذاشته شد؛ سایر فاکتورهای انتخاب‌شده طبق معمول پردازش می‌شوند.');
     if(!$allowed){redirect(admin_url('invoices'));exit;}
   }
 }
 $id=0;$action='';
 // Native Perfex edit route: /admin/invoices/invoice/{id}
 if(preg_match('~(?:^|/)invoices/invoice/(\d+)(?:/|$)~i',$uri,$m)){ $id=(int)$m[1]; $action='edit'; }
 // Native Perfex delete route: /admin/invoices/delete/{id}
 elseif(preg_match('~(?:^|/)invoices/delete/(\d+)(?:/|$)~i',$uri,$m)){ $id=(int)$m[1]; $action='delete'; }
 if($id<=0)return;
 $CI->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf_lifecycle');
 $st=$CI->sdf_lifecycle->invoice_lock_state($id);
 if(empty($st['locked']))return;
 // Block both opening the edit screen and any direct save/delete request. Viewing remains available via list_invoices/{id}.
 set_alert('warning','این فاکتور وارد چرخه عملیاتی شده و برای حفظ سازگاری پرداخت، اقساط، تحویل و مرجوعی، ویرایش و حذف آن غیرفعال است. '.($st['message']??''));
 redirect(admin_url('invoices/list_invoices/'.$id)); exit;
}
function sdf_controlled_lock_footer(){
 if(!is_staff_logged_in())return;$CI=&get_instance();$uri='/'.trim((string)$CI->uri->uri_string(),'/');
 if(!preg_match('~^/(?:admin/)?invoices/?$~i',$uri))return;
 $endpoint=admin_url('sales_delivery_flow/invoice_flags_by_numbers');
 ?>
 <script>
 (function(){
  var endpoint=<?php echo json_encode($endpoint); ?>,pending=false;
  function norm(s){return String(s||'').replace(/\s+/g,' ').trim()}
  function latin(s){return String(s||'').replace(/[۰-۹]/g,function(c){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)}).replace(/[٠-٩]/g,function(c){return '٠١٢٣٤٥٦٧٨٩'.indexOf(c)})}
  function invoiceNo(row){
   var a=row.querySelector('a[href*="invoices/list_invoices/"]');
   if(a){var t=latin(norm(a.textContent));var m=t.match(/([A-Za-z_-]*\d+)$/);if(m)return m[1].replace(/^[A-Za-z_-]+/,'')}
   var first=row.querySelector('td');if(!first)return '';var t=latin(norm(first.textContent));var m=t.match(/([0-9]+)(?!.*[0-9])/);return m?m[1]:'';
  }
  function apply(){if(pending)return;var table=document.querySelector('table.table-invoices,table[id*="invoice"],table');if(!table)return;var rows=[].slice.call(table.querySelectorAll('tbody tr')),nums=[];
   rows.forEach(function(r){var n=invoiceNo(r);if(n)nums.push(n)});nums=[].filter.call(new Set(nums),function(x){return x});if(!nums.length)return;pending=true;
   fetch(endpoint+'?numbers='+encodeURIComponent(nums.join(',')),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'}).then(function(r){return r.json()}).then(function(j){var map=(j&&j.rows)||j||{};rows.forEach(function(r){var n=invoiceNo(r),f=map[n];if(!f||!f.lock||!f.lock.locked)return;
     r.setAttribute('data-sdf-locked','1');
     r.querySelectorAll('a').forEach(function(a){var h=String(a.getAttribute('href')||''),t=norm(a.textContent);if(/\/invoices\/(?:invoice|delete)\//i.test(h)||/^(?:ویرایش|حذف|edit|delete)$/i.test(t))a.style.setProperty('display','none','important')});
     var cb=r.querySelector('input[type="checkbox"]');if(cb){cb.checked=false;cb.disabled=true;cb.style.visibility='hidden'}
   });pending=false;}).catch(function(){pending=false})
  }
  var tm=null;function schedule(){clearTimeout(tm);tm=setTimeout(apply,120)}
  document.addEventListener('DOMContentLoaded',schedule);new MutationObserver(schedule).observe(document.body,{childList:true,subtree:true});setTimeout(apply,300);
 })();
 </script>
 <?php
}

function sdf_invoice_lifecycle_footer(){
 if(!is_staff_logged_in())return;
 // 0.2.32: bootstrap diagnostic runtime from this already-proven module-level footer callback.
 // Unlike 0.2.31, no hook is registered dynamically from admin_init.
 sdf_runtime_diagnostic_capture();
 $CI=&get_instance();$uri='/'.trim((string)$CI->uri->uri_string(),'/');
 // This UI pass is intentionally restricted to the invoice LIST only. It never adds/removes table cells,
 // so it cannot shift Date/Customer/Due date/Status columns.
 if(!preg_match('~^/admin/invoices/?$~i',$uri) && !preg_match('~^/invoices/?$~i',$uri))return;
 $flagsUrl=admin_url('sales_delivery_flow/invoice_flags_by_numbers');
 ?>
 <script>
 (function(){
   var endpoint=<?php echo json_encode($flagsUrl); ?>,cache={},busy=false,timer=null;
   function norm(s){return String(s||'').replace(/\s+/g,' ').trim()}
   function invoiceNumber(row){
     if(!row)return '';
     var first=row.querySelector('td');if(!first)return '';
     var m=norm(first.textContent).match(/(?:فاکتور|invoice)\s*#?\s*([0-9۰-۹٠-٩]+)/i);if(!m)return '';
     return m[1].replace(/[۰-۹]/g,function(c){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)}).replace(/[٠-٩]/g,function(c){return '٠١٢٣٤٥٦٧٨٩'.indexOf(c)});
   }
   function rows(){return Array.prototype.slice.call(document.querySelectorAll('table tbody tr')).filter(function(r){return invoiceNumber(r)!==''})}
   function applyRow(row,flag){if(!flag||!flag.lock||!flag.lock.locked)return;var first=row.querySelector('td');if(!first)return;
     // Touch controls only; never touch TD/TH structure or cell contents.
     first.querySelectorAll('a,button').forEach(function(a){var t=norm(a.textContent);if(t==='ویرایش'||t==='حذف'||/^edit$/i.test(t)||/^delete$/i.test(t)){a.style.setProperty('display','none','important');a.setAttribute('aria-disabled','true')}});
     var cb=first.querySelector('input[type="checkbox"]')||row.querySelector('input[type="checkbox"]');if(cb){cb.checked=false;cb.disabled=true;cb.setAttribute('data-sdf-locked','1');cb.title='این فاکتور قفل عملیاتی است';}
   }
   function apply(){rows().forEach(function(r){var n=invoiceNumber(r);if(n&&cache[n])applyRow(r,cache[n])})}
   function load(){if(busy)return;var ns=[];rows().forEach(function(r){var n=invoiceNumber(r);if(n&&!cache[n]&&ns.indexOf(n)<0)ns.push(n)});if(!ns.length){apply();return}busy=true;
     fetch(endpoint+'?numbers='+encodeURIComponent(ns.join(',')),{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json()}).then(function(j){if(j&&j.ok){Object.keys(j.flags||{}).forEach(function(k){cache[k]=j.flags[k]});apply()}}).catch(function(){}).finally(function(){busy=false});
   }
   function schedule(){clearTimeout(timer);timer=setTimeout(load,100)}
   if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',load);else load();
   new MutationObserver(schedule).observe(document.body,{childList:true,subtree:true});setTimeout(load,500);setTimeout(load,1400);
 })();
 </script>
 <?php
}

// 0.1.3: Some customized Perfex installations finish/rebuild the Sales submenu after admin_init.
// Keep the normal App_menu registration above, and add a narrow rendered-menu fallback only when
// the two SDF entries are still missing. This mirrors the late UI pass already used by Sales Commercial Rules.
function sdf_sales_menu_footer_fallback(){
 if(!is_staff_logged_in()) return;
 if(!(is_admin() || (function_exists('has_permission') && has_permission('invoices','','view')))) return;
 $exitUrl = admin_url('sales_delivery_flow');
 $deliveryUrl = admin_url('sales_delivery_flow/deliveries');
 ?>
 <script>
 (function(){
   var exitUrl = <?php echo json_encode($exitUrl); ?>;
   var deliveryUrl = <?php echo json_encode($deliveryUrl); ?>;
   function norm(h){
     try{return new URL(h,window.location.origin).pathname.replace(/\/+$/,'').toLowerCase();}
     catch(e){return String(h||'').split('?')[0].replace(/\/+$/,'').toLowerCase();}
   }
   function exists(url){
     var target=norm(url), found=false;
     document.querySelectorAll('#side-menu a[href], .sidebar-menu a[href], aside a[href]').forEach(function(a){
       if(norm(a.getAttribute('href'))===target) found=true;
     });
     return found;
   }
   function findSalesSubmenu(){
     // The invoices entry is a stable child of the Sales menu on this installation.
     var links=document.querySelectorAll('#side-menu a[href], .sidebar-menu a[href], aside a[href]');
     for(var i=0;i<links.length;i++){
       var p=norm(links[i].getAttribute('href'));
       if(p.endsWith('/admin/invoices')){
         var li=links[i].closest('li');
         if(li && li.parentElement && /^(UL|OL)$/i.test(li.parentElement.tagName)) return li.parentElement;
       }
     }
     return null;
   }
   function addItem(ul,url,label,icon){
     if(exists(url)) return;
     var li=document.createElement('li');
     li.className='sdf-sales-menu-item';
     var a=document.createElement('a'); a.href=url;
     var i=document.createElement('i'); i.className=icon+' menu-icon';
     var span=document.createElement('span'); span.className='menu-text'; span.textContent=label;
     a.appendChild(i); a.appendChild(span); li.appendChild(a); ul.appendChild(li);
   }
   function ensure(){
     var ul=findSalesSubmenu(); if(!ul) return;
     addItem(ul,exitUrl,'خروج و ارسال کالا','fa fa-truck');
     addItem(ul,deliveryUrl,'تحویل کالا به مشتری','fa fa-check-square-o');
   }
   if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',ensure); else ensure();
   setTimeout(ensure,300); setTimeout(ensure,1200); setTimeout(ensure,2500);
 })();
 </script>
 <?php
}


// 0.2.29 diagnostic only: persistent global runtime capture.
// Runs on every admin page so invoice-list AJAX/modal navigation cannot escape capture.
// Read-only: it stores browser evidence in localStorage only.
function sdf_runtime_diagnostic_capture(){
 if(!is_staff_logged_in()) return;
 ?>
 <script>
 (function(){
   try{window.__sdfRuntimePhpInjected=(window.__sdfRuntimePhpInjected||0)+1;localStorage.setItem('sdf_runtime_diag_injected','script-entered '+new Date().toISOString())}catch(_e){}
   if(window.__sdfRuntimeDiag232)return; window.__sdfRuntimeDiag232=1;
   try{localStorage.setItem('sdf_runtime_diag_hook','0.2.32 proven-footer | js-executed '+new Date().toISOString());localStorage.setItem('sdf_runtime_diag_storage','write-ok')}catch(e){try{window.__sdfRuntimeStorageError=String(e)}catch(_e){}}
   var KEY='sdf_runtime_diag_last', HIST='sdf_runtime_diag_history', ERR='sdf_runtime_diag_error', NET='sdf_runtime_diag_network';
   function txt(x){return String(x||'').replace(/\s+/g,' ').trim()}
   function latin(x){return String(x||'').replace(/[۰-۹]/g,function(c){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)}).replace(/[٠-٩]/g,function(c){return '٠١٢٣٤٥٦٧٨٩'.indexOf(c)})}
   function idFrom(s){s=latin(String(s||''));var ps=[/(?:\/|^)invoices\/(?:list_invoices|invoice|pdf|delete|get_invoice_data_ajax)\/(\d+)/i,/[?&](?:invoice_id|invoiceid|id)=(\d+)/i,/\/sales_delivery_flow\/(?:invoice|return_invoice)\/(\d+)/i,/\binit_invoice\s*\(\s*(\d+)/i,/\binvoice(?:_id|id)?[^0-9]{0,8}(\d+)/i];for(var i=0;i<ps.length;i++){var m=s.match(ps[i]);if(m)return parseInt(m[1],10)}return 0}
   function numFrom(s){var m=latin(txt(s)).match(/(?:فاکتور|invoice)\s*(?:شماره|no\.?|#)?\s*[:#-]?\s*([0-9]+)/i);return m?m[1]:''}
   function netRead(){try{return JSON.parse(localStorage.getItem(NET)||'[]')}catch(e){return []}}
   function netAdd(url,kind){try{var id=idFrom(url);if(!id&&!/invoice/i.test(String(url)))return;var a=netRead();a.push({ts:new Date().toISOString(),kind:kind,url:String(url||''),invoice_id:id});if(a.length>80)a=a.slice(-80);localStorage.setItem(NET,JSON.stringify(a))}catch(e){}}
   try{var XO=XMLHttpRequest.prototype.open;XMLHttpRequest.prototype.open=function(m,u){netAdd(u,'xhr');return XO.apply(this,arguments)}}catch(e){}
   try{var OF=window.fetch;if(OF)window.fetch=function(input){netAdd(typeof input==='string'?input:(input&&input.url)||'','fetch');return OF.apply(this,arguments)}}catch(e){}
   function identities(){var out=[],seen={};function add(id,num,source,raw){id=parseInt(id||0,10)||0;num=latin(num||'');var k=id+'|'+num;if((id||num)&&!seen[k]){seen[k]=1;out.push({invoice_id:id,invoice_number:num,source:source,raw:txt(raw||'').slice(0,500)})}}
     add(idFrom(location.href),numFrom(document.title+' '+document.body.innerText.slice(0,3000)),'page',location.href);
     netRead().slice(-40).forEach(function(n){if(n.invoice_id)add(n.invoice_id,'','network',n.url)});
     document.querySelectorAll('a,button,[onclick],[data-id],[data-invoice-id],[data-invoiceid]').forEach(function(e){var raw=[e.getAttribute('href'),e.getAttribute('onclick'),e.getAttribute('data-id'),e.getAttribute('data-invoice-id'),e.getAttribute('data-invoiceid'),e.outerHTML&&e.outerHTML.slice(0,1200)].join(' '),id=idFrom(raw);if(id)add(id,numFrom((e.closest('tr')&&e.closest('tr').innerText)||e.innerText||''),'element',raw)});
     document.querySelectorAll('tr').forEach(function(r){var raw=r.innerHTML||'',id=idFrom(raw),num=numFrom(r.innerText||'');if(id||num)add(id,num,'table-row',raw)});return out.slice(0,100)}
   function snap(reason){try{var o={schema:'sdf-runtime-diagnostic-0.2.32',ts:new Date().toISOString(),reason:reason||'',url:location.href,title:document.title,identities:identities(),network:netRead(),tables:[],visibleModals:[],forms:[],anchors:[]};
     document.querySelectorAll('table').forEach(function(t,ti){var z={index:ti,id:t.id||'',className:t.className||'',headers:[],header_count:0,rows:[]};t.querySelectorAll('thead th').forEach(function(h){z.headers.push(txt(h.innerText))});z.header_count=z.headers.length;Array.from(t.querySelectorAll('tbody tr')).slice(0,40).forEach(function(r,ri){var q={index:ri,text:txt(r.innerText),cell_count:r.querySelectorAll('td').length,cells:[],links:[],identity:null};r.querySelectorAll('td').forEach(function(c,ci){q.cells.push({index:ci,text:txt(c.innerText),colspan:c.colSpan||1,display:getComputedStyle(c).display})});r.querySelectorAll('a').forEach(function(a){q.links.push({text:txt(a.innerText),href:a.href||'',onclick:a.getAttribute('onclick')||'',visible:a.offsetParent!==null,display:getComputedStyle(a).display})});var raw=r.innerHTML||'',n=numFrom(r.innerText||''),id=idFrom(raw);if(n||id)q.identity={invoice_id:id,invoice_number:n};z.rows.push(q)});o.tables.push(z)});
     document.querySelectorAll('.modal').forEach(function(m){if(m.offsetParent!==null)o.visibleModals.push({id:m.id||'',className:m.className||'',identity:{invoice_id:idFrom(m.innerHTML||''),invoice_number:numFrom(m.innerText||'')},text:txt(m.innerText).slice(0,20000),html:m.innerHTML.slice(0,70000)})});
     document.querySelectorAll('form').forEach(function(f){if(f.offsetParent!==null)o.forms.push({id:f.id||'',action:f.action||'',method:f.method||'',identity:{invoice_id:idFrom((f.action||'')+' '+(f.innerHTML||'')),invoice_number:numFrom(f.innerText||'')},text:txt(f.innerText).slice(0,10000),inputs:Array.from(f.elements||[]).slice(0,200).map(function(i){return {name:i.name||'',type:i.type||'',value:i.value||'',disabled:!!i.disabled}})})});
     Array.from(document.querySelectorAll('a,button')).filter(function(a){return /پرداخت|ویرایش|حذف|چاپ|print|pdf|payment|invoice|فاکتور/i.test((a.innerText||'')+' '+(a.getAttribute('href')||'')+' '+(a.getAttribute('onclick')||''))}).slice(0,300).forEach(function(a){var raw=(a.getAttribute('href')||'')+' '+(a.getAttribute('onclick')||'');o.anchors.push({text:txt(a.innerText),href:a.getAttribute('href')||'',onclick:a.getAttribute('onclick')||'',invoice_id:idFrom(raw),className:a.className||'',visible:a.offsetParent!==null,display:getComputedStyle(a).display})});
     localStorage.setItem(KEY,JSON.stringify(o));var hist=[];try{hist=JSON.parse(localStorage.getItem(HIST)||'[]')}catch(e){};hist.push(o);if(hist.length>20)hist=hist.slice(-20);localStorage.setItem(HIST,JSON.stringify(hist));
   }catch(e){try{localStorage.setItem(ERR,String(e&&e.stack||e))}catch(_){}}}
   function delayed(r){setTimeout(function(){snap(r)},120);setTimeout(function(){snap(r+'-settled')},900)}
   if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){delayed('dom-ready')});else delayed('page-ready');
   document.addEventListener('click',function(e){var a=e.target&&e.target.closest?e.target.closest('a,button,[onclick]'):null;if(a){var raw=(a.getAttribute('href')||'')+' '+(a.getAttribute('onclick')||'')+' '+(a.outerHTML||'');var id=idFrom(raw);if(id)netAdd(raw,'click');if(id||/پرداخت|ویرایش|حذف|چاپ|print|pdf|payment|invoice|فاکتور/i.test(raw+' '+(a.innerText||'')))snap('before-action-click');setTimeout(function(){snap('after-action-click')},500)}},true);
   document.addEventListener('shown.bs.modal',function(){delayed('modal')},true);
   try{new MutationObserver(function(ms){var hit=ms.some(function(m){return Array.from(m.addedNodes||[]).some(function(n){return n.nodeType===1&&(n.matches&&n.matches('.modal,table,[id*=invoice],[class*=invoice]')||n.querySelector&&n.querySelector('.modal,table,[id*=invoice],[class*=invoice]'))})});if(hit)delayed('mutation')}).observe(document.documentElement,{childList:true,subtree:true})}catch(e){}
   // 0.2.29: bind diagnostic controls from the same global admin-footer runtime as capture.
   function bindDiagnosticUI(){
     var refresh=document.getElementById('refreshDiag'), clear=document.getElementById('clearDiag'), download=document.getElementById('downloadDiag');
     if(!refresh||refresh.getAttribute('data-sdf-bound')==='1')return;
     var endpoint=<?php echo json_encode(admin_url('sales_delivery_flow/runtime_diagnostic_json')); ?>;
     var state={identities:[],servers:[],browser:null};
     function out(id,v){var e=document.getElementById(id);if(e)e.textContent=JSON.stringify(v,null,2)}
     function read(){
       var last=null,hist=[];
       try{last=JSON.parse(localStorage.getItem(KEY)||'null');hist=JSON.parse(localStorage.getItem(HIST)||'[]')}catch(e){localStorage.setItem(ERR,String(e))}
       var all=[];if(last)all.push(last);(hist||[]).slice().reverse().forEach(function(x){all.push(x)});
       var ids=[],seen={};all.forEach(function(s){(s.identities||[]).forEach(function(x){var k=(x.invoice_id||0)+'|'+(x.invoice_number||'');if(!seen[k]&&(x.invoice_id||x.invoice_number)){seen[k]=1;ids.push(x)}})});
       state.identities=ids.slice(0,30);state.browser={last:last,history:hist,network:netRead(),error:localStorage.getItem(ERR)||null};
       out('identityDiag',state.identities);out('browserDiag',state.browser);loadServers();
     }
     function loadServers(){
       state.servers=[];out('serverDiag',state.servers);
       var jobs=state.identities.slice(0,12).map(function(x){var u=endpoint+'?invoice_id='+encodeURIComponent(x.invoice_id||0)+'&invoice_number='+encodeURIComponent(x.invoice_number||'');return fetch(u,{credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json()}).then(function(j){state.servers.push(j.server||j)}).catch(function(e){state.servers.push({error:String(e),identity:x})})});
       Promise.all(jobs).then(function(){out('serverDiag',state.servers)})
     }
     refresh.setAttribute('data-sdf-bound','1');clear.setAttribute('data-sdf-bound','1');download.setAttribute('data-sdf-bound','1');
     refresh.addEventListener('click',function(e){e.preventDefault();snap('manual-refresh');setTimeout(read,150)});
     clear.addEventListener('click',function(e){e.preventDefault();localStorage.removeItem(KEY);localStorage.removeItem(HIST);localStorage.removeItem(ERR);localStorage.removeItem(NET);out('identityDiag',[]);out('serverDiag',[]);state={identities:[],servers:[],browser:{last:null,history:[],network:[],error:null}};out('browserDiag',state.browser)});
     download.addEventListener('click',function(e){e.preventDefault();read();setTimeout(function(){var payload={diagnostic_version:'0.2.31',generated_at:new Date().toISOString(),identities:state.identities,servers:state.servers,browser:state.browser};var b=new Blob([JSON.stringify(payload,null,2)],{type:'application/json;charset=utf-8'}),a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='sales_runtime_diagnostic_0.2.31.json';document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(a.href)},1000)},350)});
     var st=document.getElementById('diagRuntimeStatus');if(st){st.className='alert alert-success';st.textContent='وضعیت Runtime: متصل — دکمه‌ها و Capture فعال هستند.'}
     read();
   }
   if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',bindDiagnosticUI);else bindDiagnosticUI();
   setTimeout(bindDiagnosticUI,500);
 })();
 </script>
 <?php
}
