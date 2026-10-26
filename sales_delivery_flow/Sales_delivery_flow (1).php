<?php defined('BASEPATH') or exit('No direct script access allowed');
class Sales_delivery_flow extends AdminController{
 public function __construct(){parent::__construct();$this->load->model('sales_delivery_flow/sales_delivery_flow_model','sdf');$this->sdf->ensure_schema();}
 public function index(){$data['title']='خروج و ارسال کالا';$data['invoices']=$this->sdf->delivery_queue();$this->load->view('manage',$data);}
 public function deliveries(){redirect(admin_url('sales_delivery_flow'));}
 public function returns(){$data['title']='مرجوعی کالا';$data['invoices']=$this->sdf->returns_queue();$data['historical_returns']=$this->sdf->historical_returns();$this->load->view('returns',$data);}
 public function return_invoice($id){
  $invoice=$this->invoices_model->get($id);if(!$invoice)show_404();
  $data=['title'=>'ثبت مرجوعی - فاکتور '.$invoice->number,'invoice'=>$invoice,'context'=>$this->sdf->invoice_context($id),'return_items'=>$this->sdf->returnable_items($id),'returns'=>$this->sdf->invoice_returns($id),'financial'=>$this->sdf->return_financial_summary($id),'lock_state'=>$this->sdf->invoice_lock_state($id)];
  $this->load->view('return_invoice',$data);
 }
 public function create_return($id){
  if(!$this->input->post()){show_404();return;}
  $r=$this->sdf->create_return((int)$id,(array)$this->input->post('return_qty'),$this->input->post('reason',false),$this->input->post('note',false));
  if(!empty($r['ok'])){set_alert('success','مرجوعی ثبت شد و موجودی اقلام به انبار برگشت. رسید ورود مرجوعی ایجاد شد.');redirect(admin_url('sales_delivery_flow/print_return/'.$id.'/'.(int)$r['id'].'?created=1'));return;}
  set_alert('danger','ثبت مرجوعی انجام نشد: '.($r['error']??'خطای نامشخص'));redirect(admin_url('sales_delivery_flow/return_invoice/'.$id));
 }
 public function print_return($id,$returnId){
  $invoice=$this->invoices_model->get($id);if(!$invoice)show_404();$ret=$this->sdf->return_by_id($id,$returnId);if(!$ret)show_404();
  $data=['invoice'=>$invoice,'context'=>$this->sdf->invoice_context($id),'return'=>$ret,'lines'=>$this->sdf->return_lines($returnId)];$this->load->view('print_return',$data);
 }
 public function invoice($id){
  $invoice=$this->invoices_model->get($id);if(!$invoice)show_404();
  // Keep warehouse execution on the already-proven invoice route. This avoids custom action-route 404s on Perfex installations with restrictive routing.
  if($this->input->post('sdf_action',true)==='warehouse_exit'){
   $r=$this->sdf->create_warehouse_delivery((int)$id);
   if(!empty($r['ok']))set_alert('success','خروج واقعی از انبار ثبت شد و مرحله ارسال و تحویل فعال شد.');
   else set_alert('danger','ثبت خروج انبار انجام نشد: '.($r['message']??'خطای نامشخص'));
   redirect(admin_url('sales_delivery_flow/invoice/'.$id.'?tab=exit')); return;
  }
  $data=['title'=>'فرآیند تحویل کالا - '.$invoice->number,'invoice'=>$invoice,'context'=>$this->sdf->invoice_context($id),'permit'=>$this->sdf->permit($id),'warehouse_deliveries'=>$this->sdf->warehouse_deliveries($id),'customer_deliveries'=>$this->sdf->customer_deliveries($id),'item_locations'=>$this->sdf->item_locations($id)];$this->load->view('invoice',$data);
 }
 public function print_warehouse_exit($id,$deliveryId=0){
  $invoice=$this->invoices_model->get($id);if(!$invoice)show_404();$permit=$this->sdf->permit($id);if(!$permit)show_404();$delivery=$this->sdf->warehouse_delivery_by_id($id,$deliveryId);if(!$delivery)show_404();
  $data=['invoice'=>$invoice,'context'=>$this->sdf->invoice_context($id),'permit'=>$permit,'delivery'=>$delivery,'item_locations'=>$this->sdf->item_locations($id)];$this->load->view('print_warehouse_exit',$data);
 }
 public function print_customer_delivery($id,$deliveryId){
  $invoice=$this->invoices_model->get($id);if(!$invoice)show_404();$delivery=$this->sdf->customer_delivery_by_id($id,$deliveryId);if(!$delivery)show_404();
  $data=['invoice'=>$invoice,'context'=>$this->sdf->invoice_context($id),'delivery'=>$delivery,'warehouse_delivery'=>$this->sdf->warehouse_delivery_by_id($id,(int)($delivery['goods_delivery_id']??0))];$this->load->view('print_customer_delivery',$data);
 }
 public function permit($id){if($this->input->post()){if($this->sdf->create_permit($id,$this->input->post('note',false),$this->input->post('permit_date',true),$this->input->post('item_locations',false))){set_alert('success','مجوز خروج کالا ثبت شد و مرحله خروج از انبار فعال شد.');}else{set_alert('danger','ثبت مجوز خروج انجام نشد. لطفاً دوباره تلاش کنید.');}}redirect(admin_url('sales_delivery_flow/invoice/'.$id.'?tab=exit'));}
 public function warehouse_exit($id){
  if(!$this->input->post())show_404();
  $r=$this->sdf->create_warehouse_delivery((int)$id);
  if(!empty($r['ok']))set_alert('success','خروج واقعی از انبار ثبت شد و مرحله ارسال و تحویل فعال شد.');
  else set_alert('danger','ثبت خروج انبار انجام نشد: '.($r['message']??'خطای نامشخص'));
  redirect(admin_url('sales_delivery_flow/invoice/'.$id.'?tab=exit'));
 }
 public function native_invoice_fix(){
  if(!is_staff_logged_in())show_404();
  $id=(int)$this->input->get('invoice_id');
  $number=trim((string)$this->input->get('invoice_number',true));
  if($id<=0 && $number!==''){
   $digits=preg_replace('/[^0-9]/','',$number);
   if($digits!==''){$r=$this->db->select('id')->where('number',(int)$digits)->order_by('id','DESC')->limit(1)->get(db_prefix().'invoices')->row_array();if($r)$id=(int)$r['id'];}
  }
  if($id<=0){$this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>false,'error'=>'invoice_id']));return;}
  $repair=$this->sdf->repair_native_invoice_rates($id);
  $ctx=$this->sdf->invoice_context($id);
  $lock=$this->sdf->invoice_lock_state($id);
  $items=[];foreach((array)($ctx['items']??[]) as $x){$items[]=['itemable_id'=>(int)($x['id']??0),'description'=>(string)($x['description']??''),'qty'=>(float)($x['qty']??0),'rate'=>(float)($x['rate']??0),'line_total'=>(float)($x['line_total']??0),'discount_percent'=>(float)($x['discount_percent_value']??0)];}
  $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>true,'invoice_id'=>$id,'repair'=>$repair,'locked'=>!empty($lock['locked']),'lock'=>$lock,'items'=>$items],JSON_UNESCAPED_UNICODE));
 }
 public function invoice_flags(){
  $ids=$this->input->get('ids',true);$ids=is_array($ids)?$ids:array_filter(array_map('intval',explode(',',(string)$ids)));
  header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'flags'=>$this->sdf->invoice_flags($ids)],JSON_UNESCAPED_UNICODE);exit;
 }
 public function invoice_flags_by_numbers(){
  if(!is_staff_logged_in())show_404();
  $raw=(string)$this->input->get('numbers',true);$numbers=array_values(array_unique(array_filter(array_map('trim',explode(',',$raw)),'strlen')));if(count($numbers)>100)$numbers=array_slice($numbers,0,100);
  header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true,'flags'=>$this->sdf->invoice_flags_by_numbers($numbers)],JSON_UNESCAPED_UNICODE);exit;
 }
 public function delivered($id){
  if(!$this->input->post()){show_404();return;}
  $result=$this->sdf->add_customer_delivery($id,[
   'goods_delivery_id'=>$this->input->post('goods_delivery_id')?:null,
   'recipient_name'=>$this->input->post('recipient_name',true),
   'recipient_phone'=>$this->input->post('recipient_phone',true),
   'delivery_date'=>$this->input->post('delivery_date',true)?:date('Y-m-d'),
   'delivery_method'=>$this->input->post('delivery_method',true),
   'carrier_company'=>$this->input->post('carrier_company',true),
   'carrier_contact'=>$this->input->post('carrier_contact',true),
   'carrier_driver'=>$this->input->post('carrier_driver',true),
   'carrier_vehicle'=>$this->input->post('carrier_vehicle',true),
   'carrier_tracking'=>$this->input->post('carrier_tracking',true),
   'note'=>$this->input->post('note',false)
  ]);
  if(!empty($result['ok']) && !empty($result['id'])){
   $deliveryId=(int)$result['id'];
   set_alert('success','رسید تحویل مشتری ثبت شد. رسید چاپی ایجاد شد.');
   redirect(admin_url('sales_delivery_flow/print_customer_delivery/'.$id.'/'.$deliveryId.'?created=1'));return;
  }
  set_alert('danger','ثبت رسید تحویل مشتری انجام نشد: '.($result['error']??'خطای نامشخص'));
  redirect(admin_url('sales_delivery_flow/invoice/'.$id.'?tab=delivery'));
 }
 public function runtime_diagnostic(){
  if(!is_staff_logged_in()) access_denied('Sales Delivery Flow');
  $invoiceId=(int)$this->input->get('invoice_id');
  $number=trim((string)$this->input->get('invoice_number',true));
  if($invoiceId<=0 && $number!==''){
   $r=$this->db->select('id')->where('number',$number)->order_by('id','DESC')->limit(1)->get(db_prefix().'invoices')->row_array();
   if($r)$invoiceId=(int)$r['id'];
  }
  // Direct DB discovery: recent invoices + invoices that have actual SDF return records.
  $recent=$this->db->select('i.id,i.number,i.date,i.clientid,i.subtotal,i.total,i.discount_percent,i.discount_total,i.status')
   ->from(db_prefix().'invoices i')->order_by('i.id','DESC')->limit(80)->get()->result_array();
  $returned=[];$rt=db_prefix().'sdf_returns';
  if($this->db->table_exists($rt)){
   $returned=$this->db->select('i.id,i.number,i.date,i.clientid,i.subtotal,i.total,i.discount_percent,i.discount_total,i.status,MAX(r.id) return_id,MAX(r.created_at) return_created_at',false)
    ->from(db_prefix().'invoices i')->join($rt.' r','r.invoice_id=i.id','inner')->group_by('i.id')->order_by('return_id','DESC')->limit(80)->get()->result_array();
  }
  $snapshot=$invoiceId>0?$this->sdf_runtime_snapshot($invoiceId):null;
  $data=['title'=>'تشخیص مستقیم دیتابیس فاکتور','recent_invoices'=>$recent,'returned_invoices'=>$returned,'selected_invoice_id'=>$invoiceId,'snapshot'=>$snapshot,'searched_number'=>$number];
  $this->load->view('runtime_diagnostic',$data);
 }
 private function sdf_runtime_snapshot($invoiceId){
  $invoiceId=(int)$invoiceId;if($invoiceId<=0)return null;
  $inv=$this->db->where('id',$invoiceId)->get(db_prefix().'invoices')->row_array();if(!$inv)return ['error'=>'invoice_not_found','invoice_id'=>$invoiceId];
  $raw=$this->db->where('rel_id',$invoiceId)->where('rel_type','invoice')->order_by('item_order','ASC')->order_by('id','ASC')->get(db_prefix().'itemable')->result_array();
  $pt=db_prefix().'invoicepaymentrecords';$payments=$this->db->table_exists($pt)?$this->db->where('invoiceid',$invoiceId)->order_by('id','ASC')->get($pt)->result_array():[];
  $returns=$this->sdf->invoice_returns($invoiceId);
  $ctx=$this->sdf->invoice_context($invoiceId);
  return [
   'module_version'=>'0.2.40','invoice_id'=>$invoiceId,'invoice'=>$inv,
   'invoice_table_fields'=>$this->db->list_fields(db_prefix().'invoices'),
   'itemable_table_fields'=>$this->db->list_fields(db_prefix().'itemable'),
   'raw_items'=>$raw,'normalized_context'=>$ctx,'payments'=>$payments,'returns'=>$returns,
   'return_state'=>$this->sdf->invoice_return_state($invoiceId),'lock_state'=>$this->sdf->invoice_lock_state($invoiceId),
   'controlled_lock_diagnostic'=>$this->sdf->controlled_lock_diagnostic($invoiceId),
   'rate_diagnostic'=>$this->sdf->native_rate_diagnostic($invoiceId),
   'module_runtime_diagnostic'=>[
      'hard_lock_guard_registered'=>false,
      'invoice_list_footer_registered'=>false,
      'rate_repair_guard_registered'=>true,
      'current_staff_is_admin'=>is_admin()?true:false,
      'note'=>'نسخه 0.2.40 تشخیصی است و رفتار Native را تغییر نمی‌دهد.'
   ],
   'routes'=>['native_view'=>admin_url('invoices/list_invoices/'.$invoiceId),'native_edit'=>admin_url('invoices/invoice/'.$invoiceId),'native_delete'=>admin_url('invoices/delete/'.$invoiceId),'native_pdf'=>admin_url('invoices/pdf/'.$invoiceId),'delivery_flow'=>admin_url('sales_delivery_flow/invoice/'.$invoiceId),'return_flow'=>admin_url('sales_delivery_flow/return_invoice/'.$invoiceId)]
  ];
 }
 public function runtime_diagnostic_json(){
  if(!is_staff_logged_in()) access_denied('Sales Delivery Flow');
  $invoiceId=(int)$this->input->get('invoice_id');
  $this->output->set_content_type('application/json')->set_output(json_encode(['ok'=>$invoiceId>0,'server'=>$invoiceId>0?$this->sdf_runtime_snapshot($invoiceId):null],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
 }

}
