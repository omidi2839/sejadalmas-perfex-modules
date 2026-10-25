<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Invoice_form_customizer extends AdminController {
 public function payment_deadline_settings(){
  if(!is_admin())access_denied('Settings');
  ifc_payment_deadline_schema();
  $notice='';
  if($this->input->post('save_payment_deadline')){
   if(!function_exists('get_instance'))show_404();
   $days=(int)$this->input->post('first_payment_days');
   if($days<1||$days>365){$notice='مهلت باید عددی بین ۱ تا ۳۶۵ روز باشد.';}
   else{
    update_option('ifc_first_payment_days',(string)$days);
    update_option('ifc_first_payment_enabled',$this->input->post('first_payment_enabled')==='1'?'1':'0');
    $notice='تنظیمات ذخیره شد. تغییرات فقط برای فاکتورهای جدید اعمال می‌شود.';
   }
  }
  redirect(admin_url('sales_commercial_rules/sales_settings')); 
 }

 public function operational_statuses(){
  $raw=trim((string)$this->input->get('ids'));
  $refsRaw=trim((string)$this->input->get('refs'));
  $ids=[];
  foreach(preg_split('/[^0-9]+/',$raw) as $v){$v=(int)$v;if($v>0)$ids[$v]=$v;}
  /* 0.1.64 fallback: the native invoice table does not always expose invoice_id in row links.
     Resolve the displayed formatted invoice number (prefix+number) server-side instead of guessing DOM indexes. */
  $refs=[];
  foreach(explode('|',$refsRaw) as $v){$v=trim(rawurldecode($v));if($v!=='')$refs[$v]=$v;}
  if($refs){
    $rows=$this->db->select('id,number,prefix')->from(db_prefix().'invoices')->order_by('id','DESC')->limit(500)->get()->result_array();
    foreach($rows as $r){
      $cands=[trim((string)($r['prefix']??'').(string)($r['number']??'')),trim((string)($r['number']??''))];
      foreach($refs as $ref){$nr=preg_replace('/\s+/u','',strip_tags($ref));foreach($cands as $c){if($nr!=='' && $nr===preg_replace('/\s+/u','',$c)){$ids[(int)$r['id']]=(int)$r['id'];}}}
    }
  }
  $ids=array_values($ids); if(count($ids)>200)$ids=array_slice($ids,0,200);
  $out=[]; foreach($ids as $id)$out[(string)$id]=['customer_delivery'=>false,'return'=>false];
  if($ids){
    $dt=db_prefix().'sdf_customer_deliveries';
    if($this->db->table_exists($dt)){
      $rows=$this->db->select('invoice_id')->where_in('invoice_id',$ids)->group_by('invoice_id')->get($dt)->result_array();
      foreach($rows as $r){$id=(int)$r['invoice_id'];if(isset($out[(string)$id]))$out[(string)$id]['customer_delivery']=true;}
    }
    $rt=db_prefix().'sdf_returns';
    if($this->db->table_exists($rt)){
      $rows=$this->db->select('invoice_id')->where_in('invoice_id',$ids)->group_by('invoice_id')->get($rt)->result_array();
      foreach($rows as $r){$id=(int)$r['invoice_id'];if(isset($out[(string)$id]))$out[(string)$id]['return']=true;}
    }
  }
  /* Map formatted refs too, so the browser can decorate rows even when the native table hides invoice_id. */
  $byRef=[];
  if($ids){
    $ir=$this->db->select('id,number,prefix')->where_in('id',$ids)->get(db_prefix().'invoices')->result_array();
    foreach($ir as $r){$k=trim((string)($r['prefix']??'').(string)($r['number']??''));$byRef[$k]=$out[(string)(int)$r['id']]??['customer_delivery'=>false,'return'=>false];}
  }
  return $this->json(['success'=>true,'statuses'=>$out,'by_ref'=>$byRef]);
 }
 public function edit_payload($id=0){
  $id=(int)$id; if(!$id){return $this->json(['success'=>false]);}
  $this->load->model('invoices_model'); $inv=$this->invoices_model->get($id);
  if(!$inv){return $this->json(['success'=>false,'message'=>'invoice_not_found']);}
  $clientName='';
  if(!empty($inv->clientid)){
    $c=$this->db->select('company')->where('userid',(int)$inv->clientid)->get(db_prefix().'clients')->row();
    if($c)$clientName=(string)$c->company;
  }
  $out=[
   'success'=>true,'id'=>$id,'clientid'=>(int)($inv->clientid??0),'client_name'=>$clientName,
   'number'=>(string)($inv->number??''),'prefix'=>(string)($inv->prefix??''),'date'=>(string)($inv->date??''),'duedate'=>(string)($inv->duedate??''),
   'currency'=>(string)($inv->currency??''),'sale_agent'=>(string)($inv->sale_agent??''),'invoice_type'=>(string)($inv->invoice_type??''),
   'invoice_marketer'=>(string)($inv->invoice_marketer??''),'adminnote'=>(string)($inv->adminnote??''),'clientnote'=>(string)($inv->clientnote??''),
   'discount_type'=>(string)($inv->discount_type??''),'allowed_payment_modes'=>$inv->allowed_payment_modes??'','items'=>[]
  ];
  /* Do not depend on invoices_model item decoration here. Read the persisted invoice lines
     directly; this is the one source guaranteed to contain the original edit values. */
  $lines=$this->db->where('rel_id',$id)->where('rel_type','invoice')->order_by('item_order','ASC')->get(db_prefix().'itemable')->result_array();
  foreach($lines as $a){
    $iid=0; $desc=trim((string)($a['description']??'')); $long=trim((string)($a['long_description']??''));
    if($long!==''){
      $r=$this->db->select('id,description,long_description')->where('long_description',$long)->limit(1)->get(db_prefix().'items')->row();
      if($r)$iid=(int)$r->id;
    }
    if(!$iid && $desc!==''){
      $r=$this->db->select('id,description,long_description')->where('description',$desc)->limit(1)->get(db_prefix().'items')->row();
      if($r)$iid=(int)$r->id;
    }
    $out['items'][]=['line_id'=>(int)($a['id']??0),'itemid'=>$iid,'description'=>$desc,'long_description'=>$long,
      'qty'=>(float)($a['qty']??1),'rate'=>(float)($a['rate']??0),'unit'=>(string)($a['unit']??'')];
  }
  /* Return invoice custom fields as persisted values; JS fills any matching invoice custom control. */
  $cf=[];
  if($this->db->table_exists(db_prefix().'customfieldsvalues')){
    $rows=$this->db->select('fieldid,value')->where('relid',$id)->where('fieldto','invoice')->get(db_prefix().'customfieldsvalues')->result_array();
    foreach($rows as $r)$cf[(string)$r['fieldid']]=$r['value'];
  }
  $out['custom_fields']=$cf;
  return $this->json($out);
 }
 public function policy(){
  $itemId=(int)$this->input->get('itemid'); $saleType=(int)$this->input->get('sale_type_id');
  $invoiceDate=$this->normalize_invoice_date((string)$this->input->get('invoice_date')); if(!$invoiceDate){$invoiceDate=date('Y-m-d');}
  $out=['success'=>false,'message'=>'کالا یافت نشد.']; $items=db_prefix().'items';
  $item=$this->db->where('id',$itemId)->get($items)->row_array(); if(!$item){return $this->json($out);}
  $stock=0.0; $inv=db_prefix().'inventory_manage';
  if($this->db->table_exists($inv)){$r=$this->db->select_sum('inventory_number','v')->where('commodity_id',$itemId)->get($inv)->row();$stock=$r?(float)$r->v:0.0;}
  $basePrice=0.0; foreach(['rate','selling_price','c_selling_price'] as $bf){if(array_key_exists($bf,$item)&&$item[$bf]!==null&&$item[$bf]!==''){$basePrice=(float)preg_replace('/[^0-9.\-]/','',str_replace(',','',(string)$item[$bf]));break;}} $price=$basePrice; $priceSource='base'; $pt=db_prefix().'item_sale_type_prices'; if($saleType&&$this->db->table_exists($pt)){$r=$this->db->where('itemid',$itemId)->where('sale_type_id',$saleType)->order_by('id','DESC')->get($pt)->row();if($r&&$r->price!==null&&$r->price!==''){$price=(float)preg_replace('/[^0-9.\-]/','',str_replace(',','',(string)$r->price));$priceSource='sale_type';}}
  $allowed=0.0;$base=0.0;$max=0.0;$found=false;$staff=(int)get_staff_user_id();
  if(is_dir(APPPATH.'../modules/sales_commercial_rules')){try{$this->load->model('sales_commercial_rules/sales_commercial_rules_model','scr_ifc');$pol=$this->scr_ifc->discount_policy($staff,$itemId,$invoiceDate);if(!empty($pol['found'])){$found=true;$base=(float)$pol['base_percent'];$max=(float)$pol['max_percent'];$allowed=(float)$pol['allowed_percent'];}}catch(Throwable $e){}}
  if(!$found){$dr=db_prefix().'scr_discount_rules';$dt=$invoiceDate;if($this->db->table_exists($dr)){$rule=$this->db->where('group_id',(int)$item['group_id'])->where('active',1)->where('valid_from <=',$dt)->where('valid_to >=',$dt)->order_by('id','DESC')->get($dr)->row_array();if($rule){$found=true;$base=(float)$rule['base_percent'];$max=(float)$rule['max_percent'];$allowed=$base;}}}
  $out=['success'=>true,'itemid'=>$itemId,'description'=>(string)$item['description'],'long_description'=>(string)$item['long_description'],'unit'=>(string)$item['unit'],'color'=>$this->item_color($item),'size'=>$this->item_size($item),'group_id'=>(int)$item['group_id'],'stock'=>$stock,'base_price'=>$basePrice,'price'=>$price,'price_source'=>$priceSource,'discount'=>['found'=>$found,'base_percent'=>$base,'max_percent'=>$max,'allowed_percent'=>$allowed,'resolved_date'=>$invoiceDate,'group_id'=>(int)$item['group_id']]];
  return $this->json($out);
 }
 private function normalize_invoice_date($v){
  $v=trim((string)$v); if($v==='')return null;
  $v=strtr($v,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  $v=str_replace(['.','/'],'-',$v);
  if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/',$v,$m)){
   $y=(int)$m[1];$mo=(int)$m[2];$d=(int)$m[3];
   if($y>=1900&&$y<=2200&&checkdate($mo,$d,$y))return sprintf('%04d-%02d-%02d',$y,$mo,$d);
  }
  if(function_exists('to_sql_date')){try{$x=to_sql_date($v);if(is_string($x)&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$x))return $x;}catch(Throwable $e){}}
  return null;
 }
 private function item_color($item){
  /* Warehouse currently posts the selected color as `color` on this installation;
     some Warehouse versions use `color_id`. Support both without reading descriptions. */
  $raw=$item['color_id']??($item['color']??0);
  $id=is_numeric($raw)?(int)$raw:0;
  $tables=[db_prefix().'ware_color',db_prefix().'colors'];
  if($id>0){
   foreach($tables as $t){
    if(!$this->db->table_exists($t)) continue;
    foreach(['color_id','id'] as $key){
     if(!$this->db->field_exists($key,$t)) continue;
     $r=$this->db->where($key,$id)->get($t)->row_array();
     if(!$r) continue;
     foreach(['color_name','name','color'] as $nf){
      if(isset($r[$nf]) && trim((string)$r[$nf])!==''){
       $name=trim((string)$r[$nf]);
       $name=preg_replace('/^#[0-9a-f]{3,8}\s*/i','',$name);
       if($name!=='') return $name;
      }
     }
     foreach(['color_code','code'] as $cf){if(isset($r[$cf])&&trim((string)$r[$cf])!=='') return trim((string)$r[$cf]);}
    }
   }
  }
  /* Non-numeric legacy value: e.g. "#ddab5f light-brown". */
  $legacy=trim((string)$raw);
  if($legacy!=='' && !is_numeric($legacy)){
   $legacy=preg_replace('/^#[0-9a-f]{3,8}\s*/i','',$legacy);
   return trim($legacy);
  }
  return '';
 }
 private function item_size($item){
  /* Warehouse commodity form stores the actual selected size ID in tblitems.size_id. */
  $id=(int)($item['size_id']??0);
  $t=db_prefix().'ware_size_type';
  if($id>0 && $this->db->table_exists($t)){
   $r=$this->db->where('size_type_id',$id)->get($t)->row_array();
   if($r){
    $name=trim((string)($r['size_name']??''));
    if($name!=='') return $name;
    $code=trim((string)($r['size_code']??''));
    if($code!=='') return $code;
   }
  }
  return '';
 }
 public function bulk_delete_invoices(){
  if(!has_permission('invoices','','delete')) return $this->json(['success'=>false,'message'=>'شما دسترسی حذف فاکتور را ندارید.']);
  if(strtoupper($_SERVER['REQUEST_METHOD']??'GET')!=='POST') return $this->json(['success'=>false,'message'=>'درخواست نامعتبر است.']);
  $ids=$this->input->post('ids');
  if(is_string($ids)) $ids=json_decode($ids,true);
  if(!is_array($ids)) $ids=[];
  $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));
  if(!$ids) return $this->json(['success'=>false,'message'=>'هیچ فاکتوری انتخاب نشده است.']);
  $this->load->model('invoices_model');
  $deleted=[];$skipped=[];$failed=[];
  foreach($ids as $id){
    $inv=$this->db->select('id,number,prefix,total')->where('id',$id)->get(db_prefix().'invoices')->row();
    if(!$inv){$failed[]=['id'=>$id,'reason'=>'فاکتور پیدا نشد.'];continue;}
    /* Safety: this feature is intended for test/unpaid invoices. Never delete an invoice that already has a payment. */
    $paid=0.0;
    if($this->db->table_exists(db_prefix().'invoicepaymentrecords')){
      $r=$this->db->select_sum('amount','v')->where('invoiceid',$id)->get(db_prefix().'invoicepaymentrecords')->row();
      $paid=$r?(float)$r->v:0.0;
    }
    if($paid>0){$skipped[]=['id'=>$id,'reason'=>'فاکتور دارای پرداخت است و برای جلوگیری از حذف اشتباه حذف نشد.'];continue;}
    try{
      $ok=$this->invoices_model->delete($id);
      // Never trust only the model return value: verify the invoice is actually gone.
      $stillExists=(int)$this->db->where('id',$id)->count_all_results(db_prefix().'invoices')>0;
      if($ok && !$stillExists){
        $deleted[]=$id;
      } else {
        $failed[]=['id'=>$id,'reason'=>$stillExists?'فاکتور پس از اجرای حذف هنوز در بانک اطلاعات وجود دارد.':'Perfex حذف فاکتور را نپذیرفت.'];
      }
    }catch(Throwable $e){$failed[]=['id'=>$id,'reason'=>$e->getMessage()];}
  }
  return $this->json(['success'=>count($deleted)>0,'deleted'=>$deleted,'skipped'=>$skipped,'failed'=>$failed,
    'message'=>count($deleted).' فاکتور حذف شد'.(count($skipped)?'، '.count($skipped).' فاکتور دارای پرداخت حذف نشد':'').(count($failed)?'، '.count($failed).' مورد ناموفق بود':'').'.']);
 }
 /* Keep the large bulk preview out of CodeIgniter's session payload. */
 private function ifc_preview_path($token){
  if(!is_string($token)||!preg_match('/^[a-f0-9]{48}$/D',$token))return null;
  return APPPATH.'cache/ifc_bulk_preview_'.hash('sha256',$token).'.json';
 }
 private function ifc_preview_get(){
  $token=$this->session->userdata('ifc_bulk_preview_token');
  $path=$this->ifc_preview_path($token);
  if(!$path||!is_file($path)||!is_readable($path))return [];
  $raw=@file_get_contents($path);
  if($raw===false)return [];
  $data=json_decode($raw,true);
  return is_array($data)?$data:[];
 }
 private function ifc_preview_set($groups){
  if(!is_array($groups)||!$groups)return false;
  $token=$this->session->userdata('ifc_bulk_preview_token');
  $path=$this->ifc_preview_path($token);
  if(!$path){$token=bin2hex(random_bytes(24));$path=$this->ifc_preview_path($token);}
  $json=json_encode($groups,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
  if($json===false||@file_put_contents($path,$json,LOCK_EX)===false)return false;
  @chmod($path,0600);
  $this->session->set_userdata('ifc_bulk_preview_token',$token);
  return true;
 }
 private function ifc_preview_clear(){
  $path=$this->ifc_preview_path($this->session->userdata('ifc_bulk_preview_token'));
  if($path&&is_file($path))@unlink($path);
  $this->session->unset_userdata('ifc_bulk_preview_token');
  $this->session->unset_userdata('ifc_bulk_verified_preview');
  $this->session->unset_userdata('ifc_bulk_saved_reviews');
 }
 public function bulk_historical_return(){
  if(!has_permission('invoices','','create'))access_denied('invoices');
  if(strtoupper($_SERVER['REQUEST_METHOD']??'')!=='POST')show_404();
  $key=trim((string)$this->input->post('invoice_key'));
  $groups=$this->ifc_preview_get();$group=null;
  foreach((is_array($groups)?$groups:[]) as $g){if((string)($g['invoice_key']??'')===$key){$group=$g;break;}}
  if(!$group){set_alert('danger','فاکتور در پیش‌نمایش پیدا نشد؛ فایل را مجدداً بارگذاری کنید.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  if($this->ifc_existing_invoice_ids([$key]) || $this->ifc_saved_case_keys([$key])){set_alert('danger','این شماره قبلاً ثبت یا تعیین‌تکلیف شده است.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  if(!is_dir(APPPATH.'../modules/sales_delivery_flow')){set_alert('danger','ماژول مرجوعی کالا نصب نیست.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  $this->load->model('sales_delivery_flow/sales_delivery_flow_model','ifc_sdf_history');
  $result=$this->ifc_sdf_history->save_historical_return($group);
  set_alert(!empty($result['ok'])?'success':'danger',!empty($result['ok'])?'مرجوعی تاریخی ثبت شد؛ بدون تغییر موجودی یا حساب مشتری.':($result['error']??'ثبت مرجوعی تاریخی ناموفق بود.'));
  redirect(admin_url('invoice_form_customizer/bulk_import'));
 }
 private function ifc_historical_return_keys($keys){
  $table=db_prefix().'sdf_historical_returns';$out=[];
  if(!$keys||!$this->db->table_exists($table))return $out;
  foreach(array_chunk(array_values(array_unique($keys)),250) as $part){foreach($this->db->select('invoice_key')->where_in('invoice_key',$part)->get($table)->result_array() as $r)$out[$r['invoice_key']]=true;}
  return $out;
 }
 public function bulk_import(){
  if(!has_permission('invoices','','create'))access_denied('invoices');
  $this->session->unset_userdata('ifc_bulk_verified_preview');
  $ajaxFinal=(string)$this->input->post('ifc_ajax_final')==='1';
  $data=['title'=>'ورود گروهی فاکتورها','preview'=>null,'errors'=>[],'created'=>[]];
  if($this->input->post('ifc_clear_preview')==='1'){
   // Only remove the current user's temporary preview. Never touch invoice or audit tables.
   $this->ifc_preview_clear();
   $data['preview']=[];
   $data['notice']='پیش‌نمایش قبلی پاک شد. فایل اصلاح‌شده را بارگذاری کنید.';
  }elseif($this->input->post('confirm_import')){
   $reviewKey=(string)$this->input->post('review_invoice_key');
   $reviewRaw=(string)$this->input->post('review_decisions');
   $review=json_decode($reviewRaw,true);
   $reviewGroups=$this->ifc_preview_get();
   $reviewGroup=null;
   foreach((is_array($reviewGroups)?$reviewGroups:[]) as $rg){if((string)$rg['invoice_key']===$reviewKey){$reviewGroup=$rg;break;}}
   $single=[$reviewGroup];$this->ifc_refresh_existing_status($single);$reviewGroup=$single[0];
   if($this->ifc_historical_return_keys([$reviewKey])){$data['errors'][]='مرجوعی تاریخی این شماره قبلاً ثبت شده است.';$data['preview']=$reviewGroups;if($ajaxFinal){$this->ifc_bulk_final_response($data);return;}$this->load->view('bulk_import',$data);return;}
   if(!empty($reviewGroup['historical_return_registered'])){$data['errors'][]='این فاکتور قبلاً به‌عنوان مرجوعی تاریخی ثبت شده است.';$data['preview']=$reviewGroups;if($ajaxFinal){$this->ifc_bulk_final_response($data);return;}$this->load->view('bulk_import',$data);return;}
   if(!empty($reviewGroup['existing_invoice_id'])){$data['errors'][]='این فاکتور قبلاً در سامانه ثبت شده و بررسی مجدد مجاز نیست.';$data['preview']=$reviewGroups;if($ajaxFinal){$this->ifc_bulk_final_response($data);return;}$this->load->view('bulk_import',$data);return;}
   $reviewError=$this->ifc_check_review($reviewGroup,$review);
   if($reviewError){$data['errors'][]=$reviewError;$data['preview']=is_array($reviewGroups)?$reviewGroups:[];if($ajaxFinal){$this->ifc_bulk_final_response($data);return;}$this->load->view('bulk_import',$data);return;}
   $hasRejected=in_array('reject',array_values($review),true);
   if($hasRejected && !$this->ifc_review_cases_saved($reviewKey,$review)){$data['errors'][]='ابتدا مغایرت‌های انتخاب‌شده را در پرونده فروشنده ثبت کنید.';$data['preview']=$reviewGroups;if($ajaxFinal){$this->ifc_bulk_final_response($data);return;}$this->load->view('bulk_import',$data);return;}
   $_POST['selected_invoices']=[$reviewKey];
   $selected=$this->input->post('selected_invoices');
   $selected=is_array($selected)?array_values(array_unique(array_map('strval',$selected))):[];
   $groups=$this->ifc_preview_get();
   if(!is_array($groups)||!$groups){$data['errors'][]='نشست پیش‌نمایش منقضی شده است؛ فایل را دوباره بارگذاری کنید.';}
   elseif(!$selected){$data['errors'][]='حداقل یک فاکتور را برای ثبت انتخاب کنید.';$data['preview']=$groups;}
   else{
    $chosen=[];foreach($groups as $g)if(in_array((string)$g['invoice_key'],$selected,true))$chosen[]=$g;
    if(count($chosen)!==count($selected)){$data['errors'][]='انتخاب فاکتورها معتبر نیست.';$data['preview']=$groups;}
    else{
     $data['created']=$this->create_bulk_invoices($chosen,$data['errors']);
     /* Preview already persisted on disk; do not enlarge session. */
     $data['preview']=$groups;
    }
   }
  }elseif(!empty($_FILES['invoice_file']['tmp_name'])){
   $this->ifc_preview_clear();
   try{$rows=$this->read_xlsx_rows($_FILES['invoice_file']['tmp_name']);$data['preview']=$this->validate_bulk_rows($rows,$data['errors']);
    if($data['preview']&&!$this->ifc_preview_set($data['preview'])){$data['errors'][]='ذخیره پیش‌نمایش روی سرور ناموفق بود؛ دسترسی نوشتن پوشه application/cache را بررسی کنید.';$data['preview']=[];}
   }catch(Throwable $e){$data['errors'][]='خواندن فایل Excel ناموفق بود: '.$e->getMessage();}
  }
  if($ajaxFinal){$this->ifc_bulk_final_response($data);return;}
  if($data['preview']===null){$old=$this->ifc_preview_get();if(is_array($old))$data['preview']=$old;}
  $this->ifc_refresh_existing_status($data['preview']);
  $this->load->view('bulk_import',$data);
 }

 private function ifc_bulk_final_response($data){
  $created=$data['created']??[];$errors=$data['errors']??[];
  $success=count($created)>0 && count($errors)===0;
  $this->output->set_status_header($success?200:422);
  $this->output->set_content_type('application/json','utf-8');
  $this->output->set_output(json_encode(['success'=>$success,'created'=>$created,'errors'=>$errors,'csrf_name'=>$this->security->get_csrf_token_name(),'csrf_hash'=>$this->security->get_csrf_hash()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
 }
 /* Payment imports are large; keep only a short token in the CI session. */
 private function ifc_payment_batch_path($token){
  if(!is_string($token)||!preg_match('/^[a-f0-9]{48}$/D',$token))return null;
  return APPPATH.'cache/ifc_payment_batch_'.hash('sha256',$token).'.json';
 }
 private function ifc_payment_batch_get(){
  $token=$this->session->userdata('ifc_payment_batch_token');
  $path=$this->ifc_payment_batch_path($token);
  if(!$path||!is_file($path)||!is_readable($path))return [];
  $raw=@file_get_contents($path);
  $batch=$raw===false?null:json_decode($raw,true);
  return is_array($batch)&&isset($batch['token'],$batch['rows'],$batch['file_hash'])?$batch:[];
 }
 private function ifc_payment_batch_set($batch){
  if(!is_array($batch)||empty($batch['token'])||!isset($batch['rows']))return false;
  $path=$this->ifc_payment_batch_path($batch['token']);
  if(!$path)return false;
  $json=json_encode($batch,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
  if($json===false||@file_put_contents($path,$json,LOCK_EX)===false)return false;
  @chmod($path,0600);
  $this->session->set_userdata('ifc_payment_batch_token',$batch['token']);
  $this->session->unset_userdata('ifc_payment_batch');
  return true;
 }
 public function bulk_payments(){
  if(!has_permission('invoices','','view'))access_denied('invoices');
  $data=['title'=>'ورود تجمیعی پرداخت‌ها','preview'=>[],'errors'=>[],'uploaded'=>false,'results'=>[]];
  $this->session->unset_userdata('ifc_payment_batch');
  if($this->input->post('ifc_payment_action')){
   if(!has_permission('payments','','create')&&!has_permission('invoices','','create'))access_denied('payments');
   $token=(string)$this->input->post('batch_token');
   $batch=$this->ifc_payment_batch_get();
   if(!is_array($batch)||!hash_equals((string)($batch['token']??''),$token))$data['errors'][]='نشست فایل منقضی شده؛ فایل را دوباره بارگذاری کنید.';
   else {
    $data['uploaded']=true;
    $mode=(int)$this->input->post('paymentmode');
    $selected=$this->input->post('selected');if(!is_array($selected))$selected=[];
    if(!$mode||!$this->db->where('id',$mode)->where('active',1)->count_all_results(db_prefix().'payment_modes'))$data['errors'][]='روش پرداخت فعال انتخاب کنید.';
    else {
     $this->load->model('payments_model');
     foreach(array_unique(array_map('intval',$selected)) as $idx){
      if(!isset($batch['rows'][$idx]))continue;
      $r=$batch['rows'][$idx];
      if(($r['status']??'')!=='آماده ثبت قطعی')continue;
      $id=(int)$r['invoice_id'];$amount=(float)$r['paid'];
      $signature=hash('sha256',$batch['file_hash'].'|'.$r['row'].'|'.$id.'|'.$r['date'].'|'.number_format($amount,4,'.',''));
      $ledger=$this->ifc_payment_import_ledger();
      $existing=$this->db->where('fingerprint',$signature)->get($ledger)->row_array();
      if($existing){$data['results'][]=['row'=>$r['row'],'message'=>'قبلاً پردازش شده؛ ثبت مجدد انجام نشد'];continue;}
      $this->db->trans_begin();
      $this->db->query('SELECT id FROM `'.db_prefix().'invoices` WHERE id=? FOR UPDATE',[$id]);
      $invoice=$this->db->where('id',$id)->get(db_prefix().'invoices')->row_array();
      $paidRow=$this->db->select_sum('amount')->where('invoiceid',$id)->get(db_prefix().'invoicepaymentrecords')->row_array();
      $balance=(float)($invoice['total']??0)-(float)($paidRow['amount']??0);
      if(!$invoice||$amount<=0||$balance+0.0001<$amount){$this->db->trans_rollback();$data['results'][]=['row'=>$r['row'],'message'=>'مانده فاکتور کافی نیست یا فاکتور یافت نشد'];continue;}
      $this->db->insert($ledger,['fingerprint'=>$signature,'invoice_id'=>$id,'source_row'=>(int)$r['row'],'payment_id'=>null,'created_at'=>date('Y-m-d H:i:s')]);
      if($this->db->affected_rows()!==1){$this->db->trans_rollback();$data['results'][]=['row'=>$r['row'],'message'=>'تراکنش تکراری'];continue;}
      try{
       /* Match the native invoice payment form payload. The installed encrypted
          Payments_model may read invoiceid from $data rather than argument 2.
          rel_id/rel_type are also provided for custom payment audit hooks. */
       $paymentData=['invoiceid'=>$id,'rel_id'=>$id,'rel_type'=>'invoice',
        'amount'=>$amount,'date'=>$r['date'],'paymentmode'=>$mode,
        'transactionid'=>'IFC-'.substr($signature,0,18),
        'note'=>'ورود تجمیعی پرداخت - ردیف '.$r['row']];
       $paymentId=$this->payments_model->add($paymentData,$id);
       if(!$paymentId)throw new Exception('مدل پرداخت شناسه ثبت را برنگرداند');
       $this->db->where('fingerprint',$signature)->update($ledger,['payment_id'=>(int)$paymentId]);
       if($this->db->trans_status()===false)throw new Exception('خطای پایگاه داده');
       $this->db->trans_commit();
       $data['results'][]=['row'=>$r['row'],'message'=>'پرداخت ثبت شد؛ شناسه '.(int)$paymentId];
      }catch(Throwable $e){$this->db->trans_rollback();$data['results'][]=['row'=>$r['row'],'message'=>'ثبت انجام نشد؛ نیازمند بررسی: '.$e->getMessage()];}
     }
    }
    $data['preview']=$this->ifc_refresh_payment_preview($batch['rows']);
    $batch['rows']=$data['preview'];if(!$this->ifc_payment_batch_set($batch))$data['errors'][]='ذخیره نتیجه پردازش ناموفق بود.';
   }
  }elseif(!empty($_FILES['payments_file']['tmp_name'])){
   $data['uploaded']=true;
   try{
    $rows=$this->read_xlsx_rows($_FILES['payments_file']['tmp_name']);
    $data['preview']=$this->ifc_preview_payments($rows,$data['errors']);
    $token=bin2hex(random_bytes(24));
    if(!$this->ifc_payment_batch_set(['token'=>$token,'file_hash'=>hash_file('sha256',$_FILES['payments_file']['tmp_name']),'rows'=>$data['preview']]))$data['errors'][]='ذخیره فایل بررسی ناموفق بود؛ دسترسی نوشتن پوشه cache را بررسی کنید.';
   }catch(Throwable $e){$data['errors'][]='خطا در خواندن فایل: '.$e->getMessage();}
  }
  $batch=$this->ifc_payment_batch_get();
  $data['batch_token']=is_array($batch)?($batch['token']??''):'';
  $data['modes']=$this->db->select('id,name')->where('active',1)->get(db_prefix().'payment_modes')->result_array();
  $this->load->view('bulk_payments',$data);
 }
 private function ifc_payment_import_ledger(){
  $t=db_prefix().'ifc_payment_import_ledger';
  $this->db->query('CREATE TABLE IF NOT EXISTS `'.$t.'` (`fingerprint` CHAR(64) NOT NULL PRIMARY KEY,`invoice_id` INT NOT NULL,`source_row` INT NOT NULL,`payment_id` INT NULL,`created_at` DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  return $t;
 }
 private function ifc_refresh_payment_preview($rows){
  $ledger=$this->ifc_payment_import_ledger();
  foreach($rows as &$r){
   if(($r['status']??'')==='آماده ثبت قطعی'&&$r['invoice_id']){
    $paid=$this->db->select_sum('amount')->where('invoiceid',(int)$r['invoice_id'])->get(db_prefix().'invoicepaymentrecords')->row_array();
    $inv=$this->db->select('total')->where('id',(int)$r['invoice_id'])->get(db_prefix().'invoices')->row_array();
    if(!$inv||(float)$inv['total']-(float)($paid['amount']??0)+0.0001<(float)$r['paid']){$r['status']='نیازمند بررسی';$r['issues']='مانده کافی نیست یا فاکتور تسویه شده است';}
   }
  }unset($r);return $rows;
 }
 private function ifc_preview_payments($rows,&$errors){
  if(!$rows){$errors[]='فایل خالی است.';return [];}
  $head=-1;$map=[];
  foreach($rows as $i=>$r){
   $found=[];foreach($r as $j=>$v){$v=trim((string)$v);foreach(['شماره فاکتور'=>'number','تاریخ پرداخت'=>'date','پرداخت شده'=>'paid','مردود'=>'refund'] as $fa=>$en)if($v===$fa)$found[$en]=$j;}
   if(count($found)===4){$head=$i;$map=$found;break;}
  }
  if($head<0){$errors[]='چهار ستون شماره فاکتور، تاریخ پرداخت، پرداخت شده و مردود پیدا نشد.';return [];}
  $out=[];$table=db_prefix().'invoices';
  foreach(array_slice($rows,$head+1,null,true) as $i=>$r){
   $get=function($k)use($r,$map){return trim((string)($r[$map[$k]]??''));};
   $key=$get('number');$paid=$get('paid');$refund=$get('refund');$date=$get('date');
   if($key===''&&$paid===''&&$refund==='')continue;
   if($key==='شماره فاکتور'||mb_strpos($key,'جمع')!==false)continue;
   $status='آماده بررسی';$issues=[];
   if($key===''||!preg_match('/^(?:HIST-)?[0-9]+$/iu',$key))$issues[]='شماره فاکتور نامعتبر یا خالی';
   $p=str_replace([',','٬',' '],'',$paid);$f=str_replace([',','٬',' '],'',$refund);
   if(($p!==''&&!is_numeric($p))||($f!==''&&!is_numeric($f)))$issues[]='مبلغ نامعتبر';
   $pv=is_numeric($p)?(float)$p:0;$fv=is_numeric($f)?(float)$f:0;
   if($pv<0||$fv<0||($pv<=0&&$fv<=0))$issues[]='مبلغ مثبت پرداخت یا عودت وجود ندارد';
   if($pv>0&&$fv>0)$issues[]='پرداخت و عودت در یک ردیف؛ نیازمند تفکیک';
   if(is_numeric($date)&&$date>20000&&$date<100000)$date=gmdate('Y-m-d',((int)$date-25569)*86400);
   elseif(preg_match('/^\d{4}-\d{2}-\d{2}/',$date))$date=substr($date,0,10);
   else $issues[]='تاریخ پرداخت نامعتبر';
   if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))$issues[]='تاریخ معتبر نیست';
   $invoice=null;
   if(!$issues || $key!==''){
    $number=preg_replace('/^HIST-/i','',$key);
    $candidates=$this->db->select('id,number,prefix,status,total')->where('number',$number)->limit(25)->get($table)->result_array();
    if(count($candidates)===1)$invoice=$candidates[0];
    elseif(count($candidates)>1)$issues[]='شماره فاکتور در سامانه یکتا نیست؛ تطبیق دستی لازم است';
    else $issues[]='فاکتور متناظر در سامانه یافت نشد';
   }
   if($invoice&&$fv>0)$issues[]='عودت نیازمند تطبیق با وضعیت مردودی فاکتور و تأیید پرونده مرجوعی است';
   if($invoice&&$pv>0&&!$issues){
    $pr=$this->db->select_sum('amount')->where('invoiceid',(int)$invoice['id'])->get(db_prefix().'invoicepaymentrecords')->row_array();
    if((float)$invoice['total']-(float)($pr['amount']??0)+0.0001<$pv)$issues[]='مبلغ بیشتر از مانده فاکتور است';
   }
   $status=$issues?'نیازمند بررسی':'آماده ثبت قطعی';
   $out[]=['row'=>$i+2,'key'=>$key,'date'=>$date,'paid'=>$pv,'refund'=>$fv,'invoice_id'=>$invoice['id']??null,'status'=>$status,'issues'=>implode('؛ ',$issues)];
  }
  if(!$out)$errors[]='هیچ ردیف تراکنش معتبری برای پیش‌نمایش یافت نشد.';
  return $out;
 }

 public function bulk_template(){
  if(!has_permission('invoices','','create')) access_denied('invoices');
  $f=module_dir_path(INVOICE_FORM_CUSTOMIZER_MODULE_NAME,'assets/templates/invoice_bulk_import_template.xlsx');
  if(!is_file($f)) show_404();
  $this->output->set_content_type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->set_header('Content-Disposition: attachment; filename="invoice_bulk_import_template.xlsx"')->set_output(file_get_contents($f));
 }
 private function ifc_case_table(){
  $name=db_prefix().'ifc_bulk_compliance_cases';
  $sql="CREATE TABLE IF NOT EXISTS `".$name."` (
   `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
   `invoice_key` VARCHAR(120) NOT NULL,
   `source_row` INT UNSIGNED NOT NULL,
   `item_id` INT UNSIGNED NOT NULL,
   `seller_id` INT UNSIGNED NOT NULL,
   `invoice_date` DATE NOT NULL,
   `case_type` VARCHAR(20) NOT NULL,
   `invoice_value` DECIMAL(20,4) NOT NULL,
   `rule_value` DECIMAL(20,4) NOT NULL,
   `difference_value` DECIMAL(20,4) NOT NULL,
   `status` VARCHAR(30) NOT NULL DEFAULT 'detected',
   `review_note` TEXT NULL,
   `reviewed_by` INT UNSIGNED NULL,
   `reviewed_at` DATETIME NULL,
   `created_by` INT UNSIGNED NOT NULL,
   `created_at` DATETIME NOT NULL,
   PRIMARY KEY (`id`), UNIQUE KEY `case_unique` (`invoice_key`,`source_row`,`case_type`),
   KEY `seller_cases` (`seller_id`,`case_type`,`status`), KEY `invoice_cases` (`invoice_key`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  $this->db->query($sql);return $name;
 }
 /* Durable approved invoice snapshot: the manager review must not depend on the upload session. */
 private function ifc_payload_table(){
  $t=db_prefix().'ifc_bulk_case_payloads';
  $this->db->query('CREATE TABLE IF NOT EXISTS `'.$t.'` (`invoice_key` VARCHAR(120) NOT NULL PRIMARY KEY, `payload` LONGTEXT NOT NULL, `created_at` DATETIME NOT NULL, `created_by` INT NOT NULL, `finalized_invoice_id` INT NULL, KEY `finalized_invoice_id` (`finalized_invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  return $t;
 }
 private function ifc_save_case_payload($key,$group){
  $json=json_encode($group,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
  if($json===false || !$group || empty($group['items']))return false;
  $t=$this->ifc_payload_table();
  $existing=$this->db->where('invoice_key',$key)->get($t)->row_array();
  if($existing)return hash_equals(hash('sha256',(string)$existing['payload']),hash('sha256',$json));
  return $this->db->insert($t,['invoice_key'=>$key,'payload'=>$json,'created_at'=>date('Y-m-d H:i:s'),'created_by'=>(int)get_staff_user_id()]);
 }
 private function ifc_finalize_reviewed_case($key){
  $cases=$this->db->select('status')->where('invoice_key',$key)->get($this->ifc_case_table())->result_array();
  if(!$cases)return ['state'=>'not_ready','message'=>'پرونده مغایرتی ندارد.'];
  foreach($cases as $case)if(!in_array($case['status'],['confirmed','authorized'],true))return ['state'=>'not_ready','message'=>'مغایرت‌های دیگری هنوز تعیین تکلیف نشده‌اند.'];
  $t=$this->ifc_payload_table();$row=$this->db->where('invoice_key',$key)->get($t)->row_array();
  if(!$row)return ['state'=>'missing','message'=>'اطلاعات اولیه این پرونده قدیمی ذخیره نشده است؛ برای بازیابی، فایل اصلی باید مجدداً در مسیر کنترل‌شده بارگذاری شود.'];
  $group=json_decode((string)$row['payload'],true);
  if(!is_array($group)||($group['invoice_key']??null)!==$key||empty($group['items']))return ['state'=>'invalid','message'=>'داده ذخیره‌شده فاکتور معتبر نیست.'];
  $existing=$this->ifc_existing_invoice_ids([$key]);
  if(!empty($existing[$key]))return ['state'=>'exists','message'=>'فاکتور قبلاً ایجاد شده است.'];
  $errors=[];$created=$this->create_bulk_invoices([$group],$errors);
  if($created){$this->db->where('invoice_key',$key)->update($t,['finalized_invoice_id'=>(int)$created[0]['id']]);return ['state'=>'created','message'=>'فاکتور '.$key.' با شناسه '.(int)$created[0]['id'].' ثبت شد.'];}
  return ['state'=>'error','message'=>implode('؛ ',$errors)?:'ثبت فاکتور ناموفق بود.'];
 }
 private function ifc_case_signature($payload){return hash_hmac('sha256',$payload,(string)$this->config->item('encryption_key'));}
 private function ifc_review_kinds(){return ['product','color','size','price','discount','seller','commission','ranking','row'];}
 private function ifc_check_review($group,$review){
  if(!is_array($group))return 'فاکتور در نشست بررسی پیدا نشد؛ فایل را دوباره بارگذاری کنید.';
  if(!empty($group['review_errors']))return 'فاکتور دارای خطای تطبیق است.';
  if(!is_array($review))return 'تصمیم‌های بررسی ارسال نشده‌اند.';
  $required=[];foreach($group['items'] as $it){foreach($this->ifc_review_kinds() as $kind)$required[(int)$it['source_row'].':'.$kind]=true;}
  if(count($review)!==count($required))return 'برای تمام ردیف‌ها باید تأیید یا مغایرت مشخص شود.';
  foreach($review as $k=>$v)if(!isset($required[$k])||!in_array($v,['approve','reject'],true))return 'تصمیم‌های بررسی نامعتبر است.';
  return '';
 }
 private function ifc_review_cases_saved($key,$review){
  $saved=$this->session->userdata('ifc_bulk_saved_reviews');
  return is_array($saved)&&isset($saved[$key])&&hash_equals($saved[$key],hash('sha256',json_encode($review)));
 }
 public function bulk_case_save(){
  if(!has_permission('invoices','','create'))access_denied('invoices');
  $key=(string)$this->input->post('review_invoice_key');
  $review=json_decode((string)$this->input->post('review_decisions'),true);
  $groups=$this->ifc_preview_get();$group=null;
  foreach((is_array($groups)?$groups:[]) as $g)if((string)$g['invoice_key']===$key){$group=$g;break;}
  if($this->ifc_historical_return_keys([$key])){set_alert('danger','این فاکتور قبلاً به‌عنوان مرجوعی تاریخی ثبت شده است.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  $savedCasesNow=$this->ifc_saved_case_keys([$key]);
  if(!empty($savedCasesNow[$key])){set_alert('warning','مغایرت این فاکتور قبلاً ثبت شده است؛ بررسی مجدد غیرفعال است.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  $existingNow=$this->ifc_existing_invoice_ids([$key]);if(isset($existingNow[$key])){set_alert('danger','فاکتور قبلاً در سامانه ثبت شده است؛ بررسی مجدد مجاز نیست.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  $error=$this->ifc_check_review($group,$review);
  if($error){set_alert('danger',$error);redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  if(!$this->ifc_save_case_payload($key,$group)){set_alert('danger','ذخیره پایدار اطلاعات فاکتور ناموفق بود؛ هیچ پرونده جدیدی ثبت نشد.');redirect(admin_url('invoice_form_customizer/bulk_import'));return;}
  $table=$this->ifc_case_table();$saved=0;
  foreach($group['items'] as $it){
   $row=(int)$it['source_row'];
   foreach($this->ifc_review_kinds() as $kind){
    if(($review[$row.':'.$kind]??'')!=='reject')continue;
    $value=0.0;$rule=0.0;
    if($kind==='price'){$value=(float)$it['historical_rate'];$rule=(float)$it['bank_rate'];}
    if($kind==='discount'){$value=(float)$it['discount_percent'];$rule=(float)$it['discount_allowed'];}
    $record=['invoice_key'=>substr($key,0,120),'source_row'=>$row,'item_id'=>(int)$it['item_id'],'seller_id'=>(int)$group['sale_agent'],'invoice_date'=>$group['date'],'case_type'=>$kind,'invoice_value'=>$value,'rule_value'=>$rule,'difference_value'=>abs($value-$rule),'status'=>'detected','review_note'=>'مغایرت با تصمیم صریح کاربر در بررسی فاکتور ثبت شد.','created_by'=>get_staff_user_id(),'created_at'=>date('Y-m-d H:i:s')];
    $exists=$this->db->where('invoice_key',$key)->where('source_row',$row)->where('case_type',$kind)->get($table)->row();
    if(!$exists){$this->db->insert($table,$record);$saved++;}
   }
  }
  $flags=$this->session->userdata('ifc_bulk_saved_reviews');if(!is_array($flags))$flags=[];
  $flags[$key]=hash('sha256',json_encode($review));$this->session->set_userdata('ifc_bulk_saved_reviews',$flags);
  set_alert('success',$saved.' مغایرت انتخاب‌شده ثبت شد. اکنون می‌توانید فاکتور را ثبت نهایی کنید.');
  redirect(admin_url('invoice_form_customizer/bulk_import'));
 }
 public function bulk_seller_cases(){
  if(!has_permission('invoices','','view'))access_denied('invoices');
  $table=$this->ifc_case_table();$seller=(int)$this->input->get('seller_id');
  $query=$this->db->select('c.*,s.firstname,s.lastname')->from($table.' c')->join(db_prefix().'staff s','s.staffid=c.seller_id','left');
  if($seller)$query->where('c.seller_id',$seller);
  $cases=$query->order_by('c.id','DESC')->limit(1000)->get()->result_array();
  $summary=[];
  foreach($cases as $r){$sid=(int)$r['seller_id'];if(!isset($summary[$sid]))$summary[$sid]=['seller_id'=>$sid,'name'=>trim(($r['firstname']??'').' '.($r['lastname']??'')),'price_items'=>0,'discount_items'=>0,'price_invoices'=>[],'discount_invoices'=>[],'confirmed'=>0];
   $type=$r['case_type'];$summary[$sid][$type.'_items']++;$summary[$sid][$type.'_invoices'][$r['invoice_key']]=true;
   if($r['status']==='confirmed')$summary[$sid]['confirmed']++;
  }
  $this->load->view('bulk_seller_cases',['title'=>'پرونده مغایرت‌های فروشندگان','cases'=>$cases,'summary'=>$summary,'seller_filter'=>$seller]);
 }
 public function bulk_case_review(){
  if(!has_permission('invoices','','edit'))access_denied('invoices');
  $id=(int)$this->input->post('case_id');$status=(string)$this->input->post('status');
  if(!in_array($status,['detected','confirmed','authorized'],true))show_error('وضعیت معتبر نیست.',400);
  $table=$this->ifc_case_table();$case=$this->db->where('id',$id)->get($table)->row_array();
  if(!$case)show_404();
  $this->db->where('id',$id)->update($table,['status'=>$status,'review_note'=>mb_substr((string)$this->input->post('review_note'),0,3000),'reviewed_by'=>get_staff_user_id(),'reviewed_at'=>date('Y-m-d H:i:s')]);
  if($this->db->affected_rows()<0){set_alert('danger','ثبت تصمیم ناموفق بود.');redirect(admin_url('invoice_form_customizer/bulk_seller_cases'));return;}
  if($status!=='detected'){
   $result=$this->ifc_finalize_reviewed_case((string)$case['invoice_key']);
   if($result['state']==='created')set_alert('success',$result['message']);
   else set_alert('warning','فاکتور '.(string)$case['invoice_key'].': '.$result['message']);
  }
  redirect(admin_url('invoice_form_customizer/bulk_seller_cases'));
 }
 private function read_xlsx_rows($file){
  if(!class_exists('ZipArchive'))throw new Exception('ZipArchive فعال نیست.');
  $z=new ZipArchive();if($z->open($file)!==true)throw new Exception('XLSX باز نشد.');
  $shared=[];$sx=$z->getFromName('xl/sharedStrings.xml');
  if($sx!==false){$d=new DOMDocument();if(@$d->loadXML($sx)){$xp=new DOMXPath($d);foreach($xp->query('//*[local-name()="si"]') as $si){$v='';foreach($xp->query('.//*[local-name()="t"]',$si) as $t)$v.=$t->textContent;$shared[]=$v;}}}
  $sheet=$z->getFromName('xl/worksheets/sheet1.xml');$z->close();if($sheet===false)throw new Exception('شیت اول پیدا نشد.');
  $d=new DOMDocument();if(!@$d->loadXML($sheet))throw new Exception('XML اکسل معتبر نیست.');$xp=new DOMXPath($d);$out=[];
  foreach($xp->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row){$vals=[];
   foreach($xp->query('./*[local-name()="c"]',$row) as $c){if(!preg_match('/^([A-Z]+)/',$c->getAttribute('r'),$m))continue;
    $col=$this->excel_col_index($m[1]);$type=$c->getAttribute('t');$v='';
    if($type==='inlineStr'){foreach($xp->query('.//*[local-name()="t"]',$c) as $t)$v.=$t->textContent;}
    else{$vn=$xp->query('./*[local-name()="v"]',$c);$v=$vn->length?$vn->item(0)->textContent:'';if($type==='s')$v=$shared[(int)$v]??'';}
    $vals[$col]=$v;
   }if($vals){$max=max(array_keys($vals));$line=[];for($i=0;$i<=$max;$i++)$line[]=$vals[$i]??'';$out[]=$line;}
  }return $out;
 }
 private function excel_col_index($letters){$n=0;foreach(str_split($letters) as $c)$n=$n*26+(ord($c)-64);return $n-1;}
 private function ifc_bulk_names(&$groups){
  $clients=[];$staff=[];$types=[];
  foreach($groups as &$g){
   $cid=(int)$g['customer_id'];if(!isset($clients[$cid])){$r=$this->db->select('company')->where('userid',$cid)->get(db_prefix().'clients')->row_array();$clients[$cid]=trim((string)($r['company']??''));}
   $g['customer_name']=$clients[$cid]?:'مشتری شماره '.$cid;
   $sid=(int)$g['sale_agent'];if(!isset($staff[$sid])){$r=$this->db->select('firstname,lastname')->where('staffid',$sid)->get(db_prefix().'staff')->row_array();$staff[$sid]=trim(($r['firstname']??'').' '.($r['lastname']??''));}
   $g['seller_name']=$staff[$sid]?:'فروشنده شماره '.$sid;
   $tid=(string)$g['invoice_type'];$g['sale_type_name']=$tid;
   if(is_numeric($tid)&&$this->db->table_exists(db_prefix().'scr_sale_types')){
    if(!isset($types[$tid])){$r=$this->db->select('name')->group_start()->where('id',(int)$tid)->or_where('legacy_id',(int)$tid)->group_end()->get(db_prefix().'scr_sale_types')->row_array();$types[$tid]=$r['name']??'';}
    if($types[$tid]!=='')$g['sale_type_name']=$types[$tid];
   }
  }unset($g);
 }
 private function ifc_bulk_commercial($groupId,$saleType,$date){
  static $cache=[];$cacheKey=$groupId.'|'.$saleType.'|'.$date;if(isset($cache[$cacheKey]))return $cache[$cacheKey];
  $out=['commission'=>'قاعده کمیسیون برای گروه و نوع فروش تطبیق نشد','ranking'=>'دوره رتبه‌بندی در تاریخ فاکتور پیدا نشد'];
  if(!is_dir(APPPATH.'../modules/sales_commercial_rules'))return $out;
  try{
   $this->load->model('sales_commercial_rules/sales_commercial_rules_model','ifc_bulk_scr');
   $rules=$this->ifc_bulk_scr->commissions();$matches=[];
   foreach($rules as $r)if((int)($r['group_id']??0)===(int)$groupId && (int)($r['active']??0)===1 && ((string)($r['sale_type_id']??'')===(string)$saleType || (string)($r['sale_type_name']??'')===(string)$saleType))$matches[]=$r;
   if(count($matches)===1)$out['commission']='تعرفه فروشنده: '.(float)$matches[0]['seller_percent'].'٪؛ بازاریاب: '.(float)$matches[0]['marketer_percent'].'٪';
   elseif(count($matches)>1)$out['commission']='چند تعرفه کمیسیون فعال؛ نیازمند بررسی';
   $levels=$this->ifc_bulk_scr->rankings();$valid=[];
   foreach($levels as $l)if(($l['valid_from']??'')<=$date && ($l['valid_to']??'')>=$date)$valid[]=$l;
   if(count($valid)===1){$steps=$this->ifc_bulk_scr->ranking_steps((int)$valid[0]['id']);$count=0;
    foreach($steps as $s)if((string)($s['sale_type_id']??'')===(string)$saleType || (string)($s['sale_type_name']??'')===(string)$saleType)$count++;
    $out['ranking']=$valid[0]['name'];
   }elseif(count($valid)>1)$out['ranking']='چند دوره رتبه‌بندی هم‌زمان';
  }catch(Throwable $e){$out=['commission'=>'استعلام کمیسیون ناموفق','ranking'=>'استعلام رتبه‌بندی ناموفق'];}
  return $cache[$cacheKey]=$out;
 }
 private function ifc_existing_invoice_ids($keys){
  $keys=array_values(array_unique(array_filter(array_map('strval',$keys),function($k){return $k!=='';})));
  if(!$keys)return [];
  $found=[];$map=db_prefix().'ifc_historical_invoice_map';$invoices=db_prefix().'invoices';
  if($this->db->table_exists($map)){
   foreach(array_chunk($keys,300) as $part){
    $rows=$this->db->select('m.invoice_key,m.invoice_id')->from($map.' m')->join($invoices.' i','i.id=m.invoice_id','inner')->where_in('m.invoice_key',$part)->get()->result_array();
    foreach($rows as $r)$found[(string)$r['invoice_key']]=(int)$r['invoice_id'];
   }
  }
  // Legacy imports can have a historical reference in adminnote but no map row.
  $wanted=array_fill_keys($keys,true);
  $q=$this->db->select('id,adminnote')->from($invoices)->like('adminnote','[Historical import reference: ','both')->get();
  foreach($q->result_array() as $r){
   if(preg_match_all('/\[Historical import reference: ([^\]\r\n]+)\]/u',(string)$r['adminnote'],$matches)){
    foreach($matches[1] as $key)if(isset($wanted[$key])&&!isset($found[$key]))$found[$key]=(int)$r['id'];
   }
  }
  return $found;
 }
 private function ifc_saved_case_keys($keys){
  $keys=array_values(array_unique(array_filter(array_map('strval',$keys))));
  if(!$keys)return [];
  $table=db_prefix().'ifc_bulk_compliance_cases';
  if(!$this->db->table_exists($table))return [];
  $found=[];
  foreach(array_chunk($keys,300) as $part){
   $rows=$this->db->select('invoice_key')->distinct()->from($table)->where_in('invoice_key',$part)->get()->result_array();
   foreach($rows as $row)$found[(string)$row['invoice_key']]=true;
  }
  return $found;
 }
 private function ifc_refresh_existing_status(&$groups){
  if(!is_array($groups)||!$groups)return;
  $keys=[];foreach($groups as $g)$keys[]=(string)($g['invoice_key']??'');
  $found=$this->ifc_existing_invoice_ids($keys);
  $cases=$this->ifc_saved_case_keys($keys);
  $returns=$this->ifc_historical_return_keys($keys);
  foreach($groups as &$g){$key=(string)($g['invoice_key']??'');$g['existing_invoice_id']=(int)($found[$key]??0);$g['saved_compliance_case']=!empty($cases[$key]);$g['historical_return_registered']=!empty($returns[$key]);}
  unset($g);
 }
 private function validate_bulk_rows($rows,&$errors){
  if(count($rows)<2){$errors[]='فایل فاقد ردیف اطلاعات است.';return [];}
  $headers=array_map(function($x){return strtolower(trim((string)$x));},array_shift($rows));$required=['invoice_key','customer_id','date','item_id','item_code','qty'];
  $map=[];foreach($headers as $i=>$h)$map[$h]=$i;foreach(['invoice_key','customer_id','date','qty','rate','historical_net'] as $h)if(!isset($map[$h]))$errors[]='ستون '.$h.' در فایل وجود ندارد.';if(!isset($map['item_id'])&&!isset($map['item_code']))$errors[]='حداقل یکی از ستون‌های item_id یا item_code لازم است.';if($errors)return [];
  $keyIndex=$map['invoice_key'];$uploadedKeys=[];
  foreach($rows as $r){$k=trim((string)($r[$keyIndex]??''));if($k!=='')$uploadedKeys[]=$k;}
  $existingIds=$this->ifc_existing_invoice_ids($uploadedKeys);
  $existingGroups=[];
  foreach($rows as $r){
   $k=trim((string)($r[$keyIndex]??''));if(!isset($existingIds[$k]))continue;
   if(!isset($existingGroups[$k])){
    $read=function($field)use($r,$map){return isset($map[$field])?trim((string)($r[$map[$field]]??'')):'';};
    $existingGroups[$k]=['invoice_key'=>$k,'existing_invoice_id'=>(int)$existingIds[$k], 'customer_name'=>'—','seller_name'=>'—','sale_type_name'=>'—','date'=>$read('date'),'items'=>[],'review_errors'=>[]];
   }
   $existingGroups[$k]['items'][]=['source_row'=>0];
  }
  $groups=[];$rowKeys=[];$lineNo=1;
  foreach($rows as $r){$lineNo++;$get=function($k)use($r,$map){return isset($map[$k])?trim((string)($r[$map[$k]]??'')):'';};$key=$get('invoice_key');if($key==='')continue;if(isset($existingIds[$key]))continue;$rowKeys[$lineNo]=$key;
   $cid=(int)$get('customer_id');$date=$get('date');$qty=(float)$get('qty');$iid=(int)$get('item_id');$code=$get('item_code');
   if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || strtotime($date)===false){$errors[]="ردیف {$lineNo}: تاریخ باید YYYY-MM-DD باشد.";continue;}
   if($get('rate')!=='' && !is_numeric($get('rate'))){$errors[]="ردیف {$lineNo}: قیمت تاریخی نامعتبر است.";continue;}
   if($get('historical_net')!=='' && !is_numeric($get('historical_net'))){$errors[]="ردیف {$lineNo}: مبلغ نهایی تاریخی موجود یا معتبر نیست.";continue;}
   if(!$cid||!$this->db->where('userid',$cid)->get(db_prefix().'clients')->row()){$errors[]="ردیف {$lineNo}: مشتری معتبر نیست.";continue;}
   if(!$iid&&$code!==''){$matches=$this->db->group_start()->where('commodity_code',$code)->or_where('sku_code',$code)->group_end()->get(db_prefix().'items')->result();if(count($matches)===1)$iid=(int)$matches[0]->id;elseif(count($matches)>1){$errors[]="ردیف {$lineNo}: کد {$code} دارای ".count($matches).' محصول است؛ شناسه item_id برای انتخاب رنگ و اندازه لازم است.';continue;}}
   $item=$iid?$this->db->where('id',$iid)->get(db_prefix().'items')->row_array():null;if(!$item){$errors[]="ردیف {$lineNo}: کالا با شناسه {$iid} و کد {$code} پیدا نشد.";continue;}if($qty<=0){$errors[]="ردیف {$lineNo}: تعداد باید بیشتر از صفر باشد.";continue;}
   if(!isset($groups[$key]))$groups[$key]=['invoice_key'=>$key,'customer_id'=>$cid,'date'=>$date,'due_date'=>$get('due_date'),'invoice_type'=>$get('invoice_type'),'sale_agent'=>$get('sale_agent'),'invoice_marketer'=>$get('invoice_marketer'),'admin_note'=>$get('admin_note'),'items'=>[]];
   if((int)$groups[$key]['customer_id']!==$cid)$errors[]="فاکتور {$key}: customer_id در ردیف‌ها یکسان نیست.";
   $allowed=null;$policyFound=false;
   if(is_dir(APPPATH.'../modules/sales_commercial_rules')){
    try { $this->load->model('sales_commercial_rules/sales_commercial_rules_model','scr_bulk_audit');
      $policy=$this->scr_bulk_audit->discount_policy((int)$get('sale_agent'),$iid,$date);
      if(is_array($policy)&&!empty($policy['found'])){$policyFound=true;$allowed=(float)$policy['allowed_percent'];}
    }catch(Throwable $auditError){}
   }
   $systemPrice=null;$systemPriceSource='تعرفه معتبر یافت نشد';
   if(is_dir(APPPATH.'../modules/product_sqm_pricing')){
    try{$this->load->model('product_sqm_pricing/product_sqm_pricing_model','ifc_sqm_audit');
     $rule=$this->ifc_sqm_audit->find_rule((int)($item['group_id']??0),$get('invoice_type'),$date,'',$item);
     if($rule){if((string)$rule->pricing_method==='fixed'){$systemPrice=(float)$rule->fixed_price;$systemPriceSource='تعرفه ثابت تاریخ فاکتور';}
      else{$area=$this->ifc_sqm_audit->parse_area((string)$this->item_size($item));
       if($area!==false){$systemPrice=(float)$rule->price_per_sqm*$area;$systemPriceSource='تعرفه مترمربعی تاریخ فاکتور';}
       else $systemPriceSource='تعرفه وجود دارد؛ اندازه قابل محاسبه نیست';}}
    }catch(Throwable $e){$systemPriceSource='استعلام تعرفه ناموفق';}
   }
   $groups[$key]['items'][]=['source_row'=>$lineNo,'source_item_id'=>(int)$get('item_id'),'source_product_name'=>$get('source_product_name'),'source_color'=>$get('source_color'),'source_size'=>$get('source_size'),'source_seller_name'=>$get('source_seller_name'),'item_code'=>$code,'commercial_context'=>$this->ifc_bulk_commercial((int)($item['group_id']??0),$get('invoice_type'),$date),'product_color'=>(string)($item['color']??''),'product_size'=>(string)$this->item_size($item),'product_group'=>(string)($item['group_id']??''),'historical_rate_supplied'=>$get('rate')!=='','item_id'=>$iid,'description'=>$item['description']??'','long_description'=>$item['long_description']??'','qty'=>$qty,'rate'=>$get('rate')!==''?(float)$get('rate'):(float)($item['rate']??0),'unit'=>$item['unit']??'','discount_percent'=>(float)$get('discount_percent'),'historical_net'=>$get('historical_net')!==''?(float)$get('historical_net'):null,'historical_rate'=>$get('rate')!==''?(float)$get('rate'):null,'bank_rate'=>$systemPrice,'bank_rate_source'=>$systemPriceSource,'discount_allowed'=>$allowed,'discount_policy_found'=>$policyFound,'discount_policy_state'=>$policyFound?'defined':'no_matching_rule'];
  }
  $unassigned=[];
  foreach($errors as $err){
   if(preg_match('/ردیف\s+(\d+)/u',$err,$m)){
    $row=(int)$m[1];$key=$rowKeys[$row]??null;
    if($key!==null && isset($groups[$key])){$groups[$key]['review_errors'][]=$err;continue;}
   }
   $unassigned[]=$err;
  }
  $errors=$unassigned;
  $result=array_values($groups);$this->ifc_bulk_names($result);return array_merge(array_values($existingGroups),$result);
 }
 private function ifc_bulk_map_table(){
  $t=db_prefix().'ifc_historical_invoice_map';
  if(!$this->db->table_exists($t))$this->db->query('CREATE TABLE IF NOT EXISTS `'.$t.'` (`invoice_key` VARCHAR(120) NOT NULL PRIMARY KEY, `invoice_id` INT NULL, `state` VARCHAR(20) NOT NULL DEFAULT \'pending\', `created_at` DATETIME NOT NULL, `created_by` INT NOT NULL, INDEX (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  return $t;
 }
 private function create_bulk_invoices($groups,&$errors){
  $this->load->model('invoices_model');$created=[];$t=$this->ifc_bulk_map_table();
  foreach($groups as $g){
   $key=trim((string)$g['invoice_key']);
   if($key===''||strlen($key)>120){$errors[]='شماره فاکتور نامعتبر است.';continue;}
   if(!empty($g['existing_invoice_id'])){$errors[]='فاکتور '.$key.' قبلاً در سامانه ثبت شده است؛ بررسی و ثبت دوباره ممنوع است.';continue;}
   if(!empty($g['review_errors'])||empty($g['items'])){$errors[]='فاکتور '.$key.' دارای خطای تطبیق یا بدون قلم است.';continue;}
   $existing=$this->db->where('invoice_key',$key)->get($t)->row_array();
   if($existing){
    $mappedId=(int)($existing['invoice_id']??0);
    $mappedExists=$mappedId>0 && (int)$this->db->where('id',$mappedId)->count_all_results(db_prefix().'invoices')>0;
    $linked=$this->db->like('adminnote','[Historical import reference: '.$key.']')->get(db_prefix().'invoices')->result_array();
    if($mappedExists || $linked){$errors[]='فاکتور '.$key.' قبلاً در سیستم ایجاد شده است (شناسه '.($mappedExists?$mappedId:(int)$linked[0]['id']).')؛ ثبت تکراری مسدود است.';continue;}
    if((string)$existing['state']==='pending' || (string)$existing['state']==='amount_review'){
     $this->db->where('invoice_key',$key)->delete($t);
     if($this->db->affected_rows()!==1){$errors[]='پاک‌سازی قفل ناقص فاکتور '.$key.' ناموفق بود.';continue;}
    }else{$errors[]='وضعیت ثبت فاکتور '.$key.' نیازمند بررسی است: '.$existing['state'];continue;}
   }
   $date=(string)$g['date'];
   if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4))){$errors[]='تاریخ میلادی فاکتور '.$key.' معتبر نیست.';continue;}
   $lines=[];$expected=0.0;$invalid=false;
   foreach($g['items'] as $i=>$it){
    $qty=(float)$it['qty'];$rate=(float)$it['rate'];$disc=(float)$it['discount_percent'];
    if((int)$it['item_id']<=0||$qty<=0||$rate<0||$disc<0||$disc>100){$invalid=true;$invalidReason='ردیف '.(int)($it['source_row']??($i+1)).': '.implode('، ',array_filter([(int)$it['item_id']<=0?'شناسه کالا نامعتبر':null,$qty<=0?'تعداد نامعتبر ('.$qty.')':null,$rate<0?'قیمت تأییدشده منفی ('.$rate.')':null,($disc<0||$disc>100)?'تخفیف خارج از بازه ('.$disc.')':null]));break;}
    $net=$qty*$rate*(1-$disc/100);
    // Approved price and discount override historical net when the review accepted the discrepancy.
    $expected+=$net;
    $lines[]=['itemid'=>(int)$it['item_id'],'description'=>(string)$it['description'],'long_description'=>(string)$it['long_description'],'qty'=>$qty,'rate'=>$rate,'unit'=>(string)$it['unit'],'order'=>$i+1,'discount_percent'=>$disc];
   }
   if($invalid||!$lines||$expected<=0){$errors[]='فاکتور '.$key.': '.($invalid?($invalidReason??'داده نامعتبر'):(!$lines?'هیچ قلم معتبری وجود ندارد':'مجموع قیمت پس از تخفیف مثبت نیست ('.number_format($expected,2,'.','').')')).'؛ ثبت مالی انجام نشد.';continue;}
   $this->db->trans_begin();$id=0;
   try{
    $this->db->insert($t,['invoice_key'=>$key,'invoice_id'=>null,'state'=>'pending','created_at'=>date('Y-m-d H:i:s'),'created_by'=>(int)get_staff_user_id()]);
    if($this->db->affected_rows()!==1)throw new Exception('قفل شماره فاکتور ایجاد نشد.');
    $data=['clientid'=>(int)$g['customer_id'],'date'=>$date,'duedate'=>($g['due_date']?:$date),'currency'=>get_base_currency()->id,'status'=>1,'adminnote'=>trim((string)$g['admin_note'].' [Historical import reference: '.$key.']'),'sale_agent'=>(int)$g['sale_agent'],'invoice_type'=>$g['invoice_type'],'invoice_marketer'=>$g['invoice_marketer'],'discount_percent'=>0,'discount_total'=>0,'discount_type'=>'','newitems'=>[]];
    // Create invoice header without passing item rows to the customized Perfex model: its item insertion sends NULL itemPercent.
    $GLOBALS['ifc_verified_historical_import_context']=true;
    try{$id=(int)$this->invoices_model->add($data);}finally{unset($GLOBALS['ifc_verified_historical_import_context']);}
    if(!$id)throw new Exception('ثبت فاکتور شناسه معتبر برنگرداند.');
    $itemable=db_prefix().'itemable';
    $actual=$this->db->where('rel_id',$id)->where('rel_type','invoice')->order_by('item_order','ASC')->get($itemable)->result_array();
    if(!$actual){
     // Insert the approved lines with all required custom columns explicitly populated.
     // Recover the approved lines only when NO invoice items were persisted.
     $fields=$this->db->list_fields($itemable);
     foreach($lines as $line){
      $row=['rel_id'=>$id,'rel_type'=>'invoice','description'=>$line['description'],'long_description'=>$line['long_description'],'qty'=>$line['qty'],'rate'=>$line['rate'],'unit'=>$line['unit'],'item_order'=>$line['order']];
      if(in_array('discount_percent',$fields,true))$row['discount_percent']=$line['discount_percent'];
      if(in_array('itemPercent',$fields,true)){
       if(abs($line['discount_percent']-round($line['discount_percent']))>0.0001)throw new Exception('درصد تخفیف اعشاری با ستون itemPercent عدد صحیح سازگار نیست.');
       $row['itemPercent']=(int)round($line['discount_percent']);
      }elseif(!in_array('discount_percent',$fields,true) && $line['discount_percent']>0)throw new Exception('ساختار اقلام فاکتور ستون تخفیف ردیفی ندارد؛ ثبت متوقف شد.');
      if(in_array('itemItemId',$fields,true))$row['itemItemId']=(int)$line['itemid'];
      if(in_array('is_optional',$fields,true))$row['is_optional']=0;
      if(in_array('is_selected',$fields,true))$row['is_selected']=1;
      if(!$this->db->insert($itemable,$row))throw new Exception('ثبت قلم فاکتور ناموفق بود.');
     }
     $actual=$this->db->where('rel_id',$id)->where('rel_type','invoice')->order_by('item_order','ASC')->get($itemable)->result_array();
    }
    if(count($actual)!==count($lines))throw new Exception('تعداد اقلام ذخیره‌شده با فایل برابر نیست.');
    $computed=0.0;
    foreach($actual as $i=>$row){
     $line=$lines[$i];$q=(float)$row['qty'];$r=(float)$row['rate'];
     $d=(float)($row['itemPercent']??$row['discount_percent']??0);
     if(abs($q-$line['qty'])>0.0001||abs($r-$line['rate'])>0.01||abs($d-$line['discount_percent'])>0.001)throw new Exception('قیمت، تعداد یا تخفیف ذخیره‌شده با داده تأییدشده متفاوت است.');
     $computed+=$q*$r*(1-$d/100);
    }
    if(abs($computed-$expected)>max(1,$expected*0.0001))throw new Exception('مبلغ اقلام ذخیره‌شده صحیح نیست.');
    $inv=$this->db->where('id',$id)->get(db_prefix().'invoices')->row_array();
    if(!$inv)throw new Exception('فاکتور ایجادشده یافت نشد.');
    if(abs((float)$inv['total']-$expected)>max(1,$expected*0.0001)){
     // Only repair an invoice with no payments, and only after exact line verification.
     $payments=(int)$this->db->where('invoiceid',$id)->count_all_results(db_prefix().'invoicepaymentrecords');
     if($payments)throw new Exception('برای فاکتور پرداخت ثبت شده؛ اصلاح مبلغ مجاز نیست.');
     $this->db->where('id',$id)->update(db_prefix().'invoices',['subtotal'=>$expected,'total'=>$expected,'status'=>1]);
    }else{$this->db->where('id',$id)->update(db_prefix().'invoices',['status'=>1]);}
    $this->db->where('invoice_key',$key)->update($t,['invoice_id'=>$id,'state'=>'created']);
    if($this->db->trans_status()===false)throw new Exception('خطای پایگاه داده در ثبت فاکتور.');
    $this->db->trans_commit();$created[]=['key'=>$key,'id'=>$id];
   }catch(Throwable $e){unset($GLOBALS['ifc_verified_historical_import_context']);$this->db->trans_rollback();$errors[]='فاکتور '.$key.': '.$e->getMessage();}
  }
  return $created;
 }

 private function json($x){$this->output->set_content_type('application/json')->set_output(json_encode($x,JSON_UNESCAPED_UNICODE));}
}
