<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Sales_commercial_rules extends AdminController{
 public function __construct(){parent::__construct();$this->load->model('sales_commercial_rules/Sales_commercial_rules_model','scr');}
 public function sale_types(){if($this->input->post()){$r=$this->scr->save_sale_type($this->input->post());set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_saved'):$r['message']);redirect(admin_url('sales_commercial_rules/sale_types'));}$data['types']=$this->scr->sale_types();$this->load->view('sale_types/index',$data);}
 public function delete_sale_type($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$r=$this->scr->delete_sale_type((int)$id);set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_sale_type_deleted'):$r['message']);redirect(admin_url('sales_commercial_rules/sale_types'));}
 public function discounts(){
  if($this->input->post()){
   $r=$this->scr->save_discount($this->input->post());
   set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_saved'):$r['message']);
   redirect(admin_url('sales_commercial_rules/discounts'));
  }
  $filters=[
   'group_id'=>$this->input->get('filter_group_id',true),
   'base_percent'=>$this->input->get('filter_base_percent',true),
   'max_percent'=>$this->input->get('filter_max_percent',true),
   'valid_from'=>$this->input->get('filter_valid_from',true),
   'valid_to'=>$this->input->get('filter_valid_to',true),
  ];
  $data['filters']=$filters;
  $data['rules']=$this->scr->discounts($filters);
  $data['groups']=$this->scr->groups();
  $data['overrides']=[];foreach($data['rules'] as $rule)$data['overrides'][(int)$rule['id']]=$this->scr->discount_overrides((int)$rule['id']);
  $this->load->view('discounts/index',$data);
 }
 public function discount_override(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  $r=$this->scr->save_discount_override($this->input->post(),(int)get_staff_user_id());
  set_alert($r['ok']?'success':'danger',$r['message']);
  redirect(admin_url('sales_commercial_rules/discounts'));
 }
 public function delete_discount($id=0){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  $r=$this->scr->delete_discount((int)$id);
  set_alert($r?'success':'danger',$r?_l('scr_deleted'):_l('scr_delete_failed'));
  redirect(admin_url('sales_commercial_rules/discounts'));
 }
 public function product_picker_meta(){
  $p=db_prefix();$it=$p.'items';$out=['success'=>true,'products'=>[],'groups'=>[],'colors'=>[],'sizes'=>[]];
  if(!$this->db->table_exists($it)){$out=['success'=>false,'message'=>'جدول کالاها در دسترس نیست.'];$this->output->set_content_type('application/json')->set_output(json_encode($out,JSON_UNESCAPED_UNICODE));return;}
  $has=function($f,$t) { return $this->db->field_exists($f,$t); };
  $sel=['i.id','i.description'];
  foreach(['commodity_code','group_id','color_id','size_id'] as $f)$sel[]=$has($f,$it)?('i.'.$f):("NULL AS ".$f);
  $this->db->select(implode(',',$sel),false)->from($it.' i');
  $gt=$p.'items_groups';$ct=$p.'ware_color';$st=$p.'ware_size_type';
  if($this->db->table_exists($gt)&&$has('group_id',$it)){$this->db->select('g.name AS group_name',false)->join($gt.' g','g.id=i.group_id','left');}else $this->db->select("'' AS group_name",false);
  if($this->db->table_exists($ct)&&$has('color_id',$it)){$this->db->select('c.color_name AS color_name',false)->join($ct.' c','c.color_id=i.color_id','left');}else $this->db->select("'' AS color_name",false);
  if($this->db->table_exists($st)&&$has('size_id',$it)){$this->db->select('s.size_name AS size_name',false)->join($st.' s','s.size_type_id=i.size_id','left');}else $this->db->select("'' AS size_name",false);
  $rows=$this->db->order_by('i.id','ASC')->get()->result_array();$g=[];$c=[];$z=[];
  foreach($rows as $r){$x=['id'=>(int)$r['id'],'name'=>(string)($r['description']??''),'code'=>(string)($r['commodity_code']??''),'group_id'=>(int)($r['group_id']??0),'group'=>(string)($r['group_name']??''),'color_id'=>(int)($r['color_id']??0),'color'=>(string)($r['color_name']??''),'size_id'=>(int)($r['size_id']??0),'size'=>(string)($r['size_name']??'')];$out['products'][]=$x;if($x['group_id']&&$x['group']!=='')$g[$x['group_id']]=$x['group'];if($x['color_id']&&$x['color']!=='')$c[$x['color_id']]=$x['color'];if($x['size_id']&&$x['size']!=='')$z[$x['size_id']]=$x['size'];}
  asort($g,SORT_NATURAL|SORT_FLAG_CASE);asort($c,SORT_NATURAL|SORT_FLAG_CASE);asort($z,SORT_NATURAL|SORT_FLAG_CASE);
  foreach($g as $id=>$name)$out['groups'][]=['id'=>(int)$id,'name'=>$name];foreach($c as $id=>$name)$out['colors'][]=['id'=>(int)$id,'name'=>$name];foreach($z as $id=>$name)$out['sizes'][]=['id'=>(int)$id,'name'=>$name];
  $this->output->set_content_type('application/json')->set_output(json_encode($out,JSON_UNESCAPED_UNICODE));
 }
 public function get_price(){ $iid=(int)$this->input->get('itemid');$st=(int)$this->input->get('sale_type_id');$pricingDate=(string)$this->input->get('invoice_date');$p=scr_sale_price($iid,$st,$pricingDate);$out=['success'=>false,'message'=>_l('scr_price_missing')];if($p!==null){$name='';$r=$this->db->group_start()->where('legacy_id',$st)->or_where('id',$st)->group_end()->get(db_prefix().'scr_sale_types')->row();if($r)$name=$r->name;$out=['success'=>true,'price'=>$p,'sale_type_name'=>$name];}$this->output->set_content_type('application/json')->set_output(json_encode($out,JSON_UNESCAPED_UNICODE));}
 public function quick_customer_create(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  $this->output->set_content_type('application/json');
  $first=trim((string)$this->input->post('firstname',true));$last=trim((string)$this->input->post('lastname',true));$phone=trim((string)$this->input->post('phonenumber',true));
  if($first===''||$last===''||$phone===''){$this->output->set_output(json_encode(['success'=>false,'message'=>'نام، نام خانوادگی و شماره موبایل الزامی است.'],JSON_UNESCAPED_UNICODE));return;}
  $data=['company'=>trim($first.' '.$last),'phonenumber'=>$phone,'state'=>trim((string)$this->input->post('state',true)),'city'=>trim((string)$this->input->post('city',true)),'address'=>trim((string)$this->input->post('address',true)),'country'=>0,'firstname'=>$first,'lastname'=>$last,'contact_phonenumber'=>$phone,'email'=>''];
  $this->load->model('clients_model');
  try{$id=(int)$this->clients_model->add($data);}catch(Throwable $e){$id=0;log_message('error','SCR quick customer: '.$e->getMessage());}
  if(!$id){$this->output->set_output(json_encode(['success'=>false,'message'=>'ثبت مشتری در بخش مشتریان سامانه انجام نشد.'],JSON_UNESCAPED_UNICODE));return;}
  // Keep these values tied to the EXISTING customer custom fields. Never create parallel fields.
  $custom=['منطقه'=>trim((string)$this->input->post('region',true)),'نحوه آشنایی'=>trim((string)$this->input->post('referral',true))];
  $cf=db_prefix().'customfields';$cv=db_prefix().'customfieldsvalues';$missing=[];
  if($this->db->table_exists($cf)&&$this->db->table_exists($cv))foreach($custom as $label=>$value){
   if($value==='')continue;$rows=$this->db->where('fieldto','customers')->get($cf)->result_array();$field=null;
   $norm=function($v){$v=str_replace(['ي','ك','‌'],['ی','ک',' '],(string)$v);return preg_replace('/\s+/u',' ',trim($v));};$want=$norm($label);
   foreach($rows as $r){if($norm($r['name']??'')===$want){$field=$r;break;}}
   if(!$field){$missing[]=$label;continue;}$fid=(int)$field['id'];$existing=$this->db->where(['relid'=>$id,'fieldid'=>$fid,'fieldto'=>'customers'])->get($cv)->row_array();
   if($existing)$this->db->where('id',(int)$existing['id'])->update($cv,['value'=>$value]);else $this->db->insert($cv,['relid'=>$id,'fieldid'=>$fid,'fieldto'=>'customers','value'=>$value]);
  }
  // Ensure a primary contact exists with the same core identity/mobile, using Perfex's own contact model.
  $ct=db_prefix().'contacts';if($this->db->table_exists($ct)&&!$this->db->where('userid',$id)->count_all_results($ct)){
   try{$this->load->model('contacts_model');$this->contacts_model->add(['firstname'=>$first,'lastname'=>$last,'email'=>'','phonenumber'=>$phone,'is_primary'=>1,'active'=>1],$id,true);}catch(Throwable $e){log_message('error','SCR quick contact: '.$e->getMessage());}
  }
  $this->output->set_output(json_encode(['success'=>true,'id'=>$id,'name'=>trim($first.' '.$last),'missing_fields'=>$missing],JSON_UNESCAPED_UNICODE));
 }

 public function hierarchy(){
  if($this->input->post()){$r=$this->scr->save_hierarchy($this->input->post());set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_saved'):$r['message']);redirect(admin_url('sales_commercial_rules/hierarchy'));}
  $data['staff']=$this->scr->staff_list();$data['rows']=$this->scr->hierarchy();$this->load->view('hierarchy/index',$data);
 }
 public function delete_hierarchy($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$ok=$this->scr->delete_hierarchy((int)$id);set_alert($ok?'success':'danger',$ok?_l('scr_deleted'):_l('scr_delete_failed'));redirect(admin_url('sales_commercial_rules/hierarchy'));}
 public function access(){
  $current=(int)get_staff_user_id();$manager=(int)($this->input->get_post('manager_staff_id')?:$current);
  if(!is_admin())$manager=$current;
  if($this->input->post()){$this->scr->save_discount_access($this->input->post(),$manager);set_alert('success',_l('scr_saved'));redirect(admin_url('sales_commercial_rules/access'.(is_admin()&&$manager?'?manager_staff_id='.$manager:'')));}
  $data['manager_id']=$manager;$data['managers']=is_admin()?$this->scr->managers_with_subordinates():[];$data['rows']=$this->scr->discount_access($manager);$this->load->view('access/index',$data);
 }
 public function discount_policy(){
  $item=(int)$this->input->get('itemid');$date=$this->input->get('date',true);$p=$this->scr->discount_policy((int)get_staff_user_id(),$item,$date?:null);$this->output->set_content_type('application/json')->set_output(json_encode($p,JSON_UNESCAPED_UNICODE));
 }

 public function commission_import_legacy_managers(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('invoices');
  $legacy=db_prefix().'commission_hierarchy';
  if(!$this->db->table_exists($legacy)){
   set_alert('danger','جدول اطلاعات مدیران ماژول کمیسیون قبلی یافت نشد.');
   redirect(admin_url('sales_commercial_rules/commission_center?tab=managers'));return;
  }
  $old=$this->db->get($legacy)->result_array();$added=0;$updated=0;$skipped=0;
  $this->scr->hierarchy();
  foreach($old as $r){
   $seller=(int)($r['salesman']??0);$manager=(int)($r['coordinator']??0);
   $percent=str_replace('٫','.',strtr(trim((string)($r['percent']??'')),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٪'=>'']));
   if(!$seller||!$manager||$seller===$manager||!is_numeric($percent)||(float)$percent<0||(float)$percent>100){$skipped++;continue;}
   if(!$this->db->where('staffid',$seller)->count_all_results(db_prefix().'staff')||!$this->db->where('staffid',$manager)->count_all_results(db_prefix().'staff')){$skipped++;continue;}
   $existing=$this->db->where('manager_staff_id',$manager)->where('seller_staff_id',$seller)->get(db_prefix().'scr_sales_hierarchy')->row_array();
   if($existing){$skipped++;continue;}
   $result=$this->scr->save_hierarchy(['manager_staff_id'=>$manager,'seller_staff_id'=>$seller]);
   if(!empty($result['ok'])){$this->db->where('id',(int)$result['id'])->update(db_prefix().'scr_sales_hierarchy',['participation_percent'=>(float)$percent]);$added++;}else{$skipped++;}
  }
  set_alert('success','انتقال ارتباطات قدیمی: '.$added.' مورد افزوده شد؛ '.$skipped.' مورد تکراری یا نامعتبر بدون تغییر باقی ماند.');
  redirect(admin_url('sales_commercial_rules/commission_center?tab=managers'));
 }
 public function commission_manager_save(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('invoices');
  $d=$this->input->post();$percent=trim((string)($d['participation_percent']??''));
  $percent=strtr($percent,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
  if(!is_numeric($percent)||(float)$percent<0||(float)$percent>100){set_alert('danger','درصد مشارکت نامعتبر است');redirect(admin_url('sales_commercial_rules/commission_center?tab=managers'));return;}
  $r=$this->scr->save_hierarchy($d);
  if($r['ok']){$this->scr->hierarchy();$this->db->where('id',(int)$r['id'])->update(db_prefix().'scr_sales_hierarchy',['participation_percent'=>(float)$percent]);}
  set_alert($r['ok']?'success':'danger',$r['ok']?'ارتباط و درصد مشارکت ذخیره شد':$r['message']);
  redirect(admin_url('sales_commercial_rules/commission_center?tab=managers'));
 }
 public function commission_batch_settle(){
  if(!is_admin())access_denied('invoices');
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  scr_ensure_commission_center_schema();scr_ensure_batch_settlement_schema();
  $ids=array_values(array_unique(array_filter(array_map('intval',(array)$this->input->post('commission_ids')))));
  $managerKeys=array_values(array_unique(array_filter((array)$this->input->post('manager_keys'),function($v){return is_string($v)&&preg_match('/^m[1-9][0-9]*_[1-9][0-9]*$/',$v);})));
  scr_ensure_manager_settlement_schema();
  $date=(string)$this->input->post('payment_date',true);$method=trim((string)$this->input->post('payment_method',true));
  $ref=trim((string)$this->input->post('transaction_ref',true));$notes=trim((string)$this->input->post('notes',true));
  $staff=(int)$this->input->post('staff_id');
  $validDate=DateTime::createFromFormat('!Y-m-d',$date);
  if((!$ids&&!$managerKeys)||count($ids)+count($managerKeys)>500||$staff<=0||!$validDate||$validDate->format('Y-m-d')!==$date||!in_array($method,['bank','cash','cheque','other'],true)){
   set_alert('danger','اطلاعات تسویه نامعتبر است');redirect(admin_url('sales_commercial_rules/commission_center?tab=receipts'));return;
  }
  $uploadName=null;
  if(!empty($_FILES['receipt_file']['name'])){
   $file=$_FILES['receipt_file'];$ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
   if($file['error']!==UPLOAD_ERR_OK||!in_array($ext,['pdf','jpg','jpeg','png'],true)||$file['size']>5242880||!is_uploaded_file($file['tmp_name'])){
    set_alert('danger','فایل پیوست نامعتبر است (PDF یا تصویر تا ۵ مگابایت)');redirect(admin_url('sales_commercial_rules/commission_center?tab=receipts'));return;
   }
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
   if(!in_array($mime,['application/pdf','image/jpeg','image/png'],true)){
    set_alert('danger','نوع فایل پیوست مجاز نیست');redirect(admin_url('sales_commercial_rules/commission_center?tab=receipts'));return;
   }
   $dir=FCPATH.'uploads/scr_commission_settlements/';if(!is_dir($dir)&&!mkdir($dir,0755,true))show_error('پوشه پیوست قابل ایجاد نیست');
   $uploadName=bin2hex(random_bytes(16)).'.'.$ext;
   if(!move_uploaded_file($file['tmp_name'],$dir.$uploadName))show_error('ذخیره پیوست ناموفق بود');
  }
  $t=db_prefix().'scr_payment_commissions';$items=db_prefix().'scr_commission_settlement_items';$batch=db_prefix().'scr_commission_settlements';
  $this->db->trans_begin();
  $rows=$this->db->query('SELECT c.id,c.staff_id,c.commission_amount FROM `'.$t.'` c LEFT JOIN `'.$items.'` x ON x.commission_id=c.id WHERE c.id IN ('.implode(',',$ids).') AND x.id IS NULL FOR UPDATE')->result_array();
  // Resolve manager entitlements on the server; never trust posted amounts or percentages.
  $managerRows=[];$managerTotal=0;$managerOk=true;
  if($managerKeys){
   $hierarchyMap=[];foreach($this->scr->hierarchy() as $h)$hierarchyMap[(int)$h['id']]=$h;
   $managerTable=db_prefix().'scr_manager_settlement_items';
   foreach($managerKeys as $key){
    if(!preg_match('/^m([0-9]+)_([0-9]+)$/',$key,$m)){$managerOk=false;break;}
    $commissionId=(int)$m[1];$hierarchyId=(int)$m[2];$h=$hierarchyMap[$hierarchyId]??null;
    if(!$h||(int)$h['manager_staff_id']!==$staff||(int)($h['active']??1)!==1||(float)$h['participation_percent']<=0){$managerOk=false;break;}
    $source=$this->db->query('SELECT id,staff_id,role,paid_share FROM `'.$t.'` WHERE id=? FOR UPDATE',[$commissionId])->row_array();
    if(!$source||$source['role']!=='seller'||(int)$source['staff_id']!==(int)$h['seller_staff_id']){$managerOk=false;break;}
    $already=$this->db->where(['commission_id'=>$commissionId,'hierarchy_id'=>$hierarchyId])->count_all_results($managerTable);
    $amount=round((float)$source['paid_share']*(float)$h['participation_percent']/100,4);
    if($already||$amount<=0){$managerOk=false;break;}
    $managerRows[]=['commission_id'=>$commissionId,'hierarchy_id'=>$hierarchyId,'manager_staff_id'=>$staff,'percent_snapshot'=>$h['participation_percent'],'amount'=>$amount];$managerTotal+=$amount;
   }
  }
  $total=0;$ok=$managerOk&&count($rows)===count($ids);
  foreach($rows as $r){if((int)$r['staff_id']!==$staff||(float)$r['commission_amount']<=0)$ok=false;$total+=(float)$r['commission_amount'];}
  if($ok){$total+=$managerTotal;$this->db->insert($batch,['staff_id'=>$staff,'amount'=>round($total,4),'payment_date'=>$date,'payment_method'=>$method,'transaction_ref'=>$ref,'attachment'=>$uploadName,'notes'=>$notes,'created_by'=>get_staff_user_id(),'created_at'=>date('Y-m-d H:i:s')]);$bid=$this->db->insert_id();foreach($rows as $r)$this->db->insert($items,['settlement_id'=>$bid,'commission_id'=>(int)$r['id'],'amount'=>$r['commission_amount']]);foreach($managerRows as $mr)$this->db->insert(db_prefix().'scr_manager_settlement_items',array_merge(['settlement_id'=>$bid],$mr));}
  if(!$ok||$this->db->trans_status()===false){$this->db->trans_rollback();if($uploadName)@unlink(FCPATH.'uploads/scr_commission_settlements/'.$uploadName);set_alert('danger','تسویه انجام نشد؛ اقلام انتخابی را دوباره بررسی کنید');}
  else{$this->db->trans_commit();set_alert('success','پرداخت تجمیعی ثبت شد');}
  redirect(admin_url('sales_commercial_rules/commission_center?tab=receipts'));
 }
 public function commission_center(){
  if(!is_admin()&&!has_permission('invoices','','view'))access_denied('invoices');
  scr_ensure_commission_center_schema();
  $tab=(string)$this->input->get('tab');if(!in_array($tab,['policies','staff','managers','ledger','receipts','reports','reversals'],true))$tab='policies';
  $data=['tab'=>$tab,'rules'=>$this->scr->commissions(),'groups'=>$this->scr->groups(),'types'=>$this->scr->sale_types(),'staff'=>$this->scr->staff_list()];
  $data['hierarchy_rows']=$this->scr->hierarchy();
  $data['reversals']=$tab==='reversals'?$this->scr->canceled_commissions():[];
  $data['policies']=$data['rules'];$data['ranking_periods']=$this->scr->rankings();
  // The ledger period is a read-only GET filter; never rebuild commissions on selection.
  $data['ledger_selected_period_id']=($tab==='ledger')?(int)$this->input->get('level_id'):0;
  $data['ledger_selected_period']=null;
  foreach($data['ranking_periods'] as $scrPeriod){
   if((int)$scrPeriod['id']===$data['ledger_selected_period_id']){$data['ledger_selected_period']=$scrPeriod;break;}
  }

  // Build the staff-assignment view from already loaded policy/staff metadata.
  // Avoid an unconditional multi-table join: on some deployed Perfex databases the
  // optional legacy sale-type columns/tables are absent and that query causes HTTP 500.
  $data['assignments']=[];
  $assignTable=db_prefix().'scr_commission_rule_staff';
  if($this->db->table_exists($assignTable)){
   $ruleMap=[];foreach($data['rules'] as $rule){$ruleMap[(int)$rule['id']]=$rule;}
   $staffMap=[];foreach($data['staff'] as $person){$sid=(int)($person['staffid']??$person['id']??0);$staffMap[$sid]=trim((string)($person['firstname']??'').' '.(string)($person['lastname']??''));}
   foreach($this->db->order_by('id','DESC')->get($assignTable)->result_array() as $assignment){
    $rule=$ruleMap[(int)$assignment['rule_id']]??null;
    if(!$rule)continue;
    $assignment['group_name']=$rule['group_name']??'—';
    $assignment['sale_type_name']=$rule['sale_type_name']??'—';
    $assignment['staff_name']=$staffMap[(int)$assignment['staff_id']]??('کارمند '.(int)$assignment['staff_id']);
    $assignment['seller_percent']=$rule['seller_percent']??0;
    $assignment['marketer_percent']=$rule['marketer_percent']??0;
    $data['assignments'][]=$assignment;
   }
  }
  scr_ensure_batch_settlement_schema();scr_ensure_manager_settlement_schema();
  $data['settlement_rows']=$this->db->select('c.id,c.staff_id,c.role,c.payment_date,c.invoice_id,c.commission_amount,CONCAT(s.firstname," ",s.lastname) staff_name',false)->from(db_prefix().'scr_payment_commissions c')->join(db_prefix().'staff s','s.staffid=c.staff_id','left')->join(db_prefix().'scr_commission_settlement_items si','si.commission_id=c.id','left')->where('si.id IS NULL',null,false)->order_by('c.payment_date','DESC')->limit(3000)->get()->result_array();
  $data['manager_settlement_rows']=[];
  $managerPaid=[];foreach($this->db->select('commission_id,hierarchy_id')->get(db_prefix().'scr_manager_settlement_items')->result_array() as $paid)$managerPaid[$paid['commission_id'].'_'.$paid['hierarchy_id']]=true;
  $managerSources=$this->db->select('c.id,c.staff_id,c.invoice_id,c.payment_date,c.paid_share')->from(db_prefix().'scr_payment_commissions c')->where('c.role','seller')->order_by('c.payment_date','DESC')->limit(3000)->get()->result_array();
  foreach($managerSources as $source){foreach($data['hierarchy_rows'] as $h){
   if((int)($h['active']??1)!==1||(int)$h['seller_staff_id']!==(int)$source['staff_id']||(float)$h['participation_percent']<=0)continue;
   $key=$source['id'].'_'.$h['id'];if(isset($managerPaid[$key]))continue;
   $amount=round((float)$source['paid_share']*(float)$h['participation_percent']/100,4);if($amount<=0)continue;
   $data['manager_settlement_rows'][]=['key'=>'m'.$key,'staff_id'=>$h['manager_staff_id'],'staff_name'=>$h['manager_name'],'role'=>'manager','payment_date'=>$source['payment_date'],'invoice_id'=>$source['invoice_id'],'commission_amount'=>$amount];
  }}
  $allInvoiceIds=array_values(array_unique(array_filter(array_map('intval',array_merge(array_column($data['settlement_rows'],'invoice_id'),array_column($data['manager_settlement_rows'],'invoice_id'))))));
  $data['settlement_invoice_numbers']=[];
  if($allInvoiceIds){$invT=db_prefix().'invoices';$fields=['id'];foreach(['formatted_number','prefix','number'] as $f)if($this->db->field_exists($f,$invT))$fields[]=$f;
   foreach($this->db->select(implode(',',$fields))->where_in('id',$allInvoiceIds)->get($invT)->result_array() as $inv){$n=trim((string)($inv['formatted_number']??''));if($n==='')$n=(string)($inv['prefix']??'').(string)($inv['number']??'');$data['settlement_invoice_numbers'][(int)$inv['id']]=$n?:('فاکتور '.(int)$inv['id']);}}
  $data['settlement_batches']=$this->db->select('b.*,CONCAT(s.firstname," ",s.lastname) staff_name,COUNT(i.id) item_count',false)->from(db_prefix().'scr_commission_settlements b')->join(db_prefix().'staff s','s.staffid=b.staff_id','left')->join(db_prefix().'scr_commission_settlement_items i','i.settlement_id=b.id','left')->group_by('b.id')->order_by('b.id','DESC')->limit(300)->get()->result_array();
  foreach($data['settlement_batches'] as &$batchRow){$managerCount=$this->db->where('settlement_id',(int)$batchRow['id'])->count_all_results(db_prefix().'scr_manager_settlement_items');$batchRow['item_count']+=(int)$managerCount;}unset($batchRow);
  $data['receipts']=$this->db->select('r.*,CONCAT(s.firstname," ",s.lastname) staff_name',false)->from(db_prefix().'scr_commission_receipts r')->join(db_prefix().'staff s','s.staffid=r.staff_id')->order_by('r.id','DESC')->get()->result_array();
  $data['ledger']=[];
  if($this->db->table_exists(db_prefix().'scr_payment_commissions')){
   $scrLedgerQuery=$this->db->select('c.*,CONCAT(s.firstname," ",s.lastname) staff_name',false)
    ->from(db_prefix().'scr_payment_commissions c')
    ->join(db_prefix().'staff s','s.staffid=c.staff_id','left');
   if($data['ledger_selected_period']){
    // Join by the actual invoice ID, not the display number or payment date.
    $scrLedgerQuery->join(db_prefix().'invoices inv','inv.id=c.invoice_id','inner')
     ->where('inv.date >=',$data['ledger_selected_period']['valid_from'])
     ->where('inv.date <=',$data['ledger_selected_period']['valid_to']);
   }
   $data['ledger']=$scrLedgerQuery->order_by('c.id','DESC')->limit(5000)->get()->result_array();
  }
  // Resolve the customer-facing invoice number without changing stored ledger IDs.
  $data['ledger_invoice_numbers']=[];
  if(!empty($data['ledger'])){
   $ids=array_values(array_unique(array_filter(array_map('intval',array_column($data['ledger'],'invoice_id')))));
   if($ids){
    $invoiceTable=db_prefix().'invoices';
    $fields=['id'];
    foreach(['formatted_number','prefix','number'] as $field){if($this->db->field_exists($field,$invoiceTable))$fields[]=$field;}
    foreach($this->db->select(implode(',',$fields))->where_in('id',$ids)->get($invoiceTable)->result_array() as $inv){
     $display=trim((string)($inv['formatted_number']??''));
     if($display==='')$display=(string)($inv['prefix']??'').(string)($inv['number']??'');
     $data['ledger_invoice_numbers'][(int)$inv['id']]=$display!==''?$display:(string)$inv['id'];
    }
   }
  }
  // Read-only summaries for ledger popups; never link to edit actions.
  $data['ledger_invoice_details']=[];$data['ledger_payment_details']=[];
  if(!empty($data['ledger'])){
   $invoiceIds=array_values(array_unique(array_filter(array_map('intval',array_column($data['ledger'],'invoice_id')))));
   $paymentIds=array_values(array_unique(array_filter(array_map('intval',array_column($data['ledger'],'payment_id')))));
   if($invoiceIds){
    $t=db_prefix().'invoices';$cols=['id','clientid','date','duedate','subtotal','total','status'];
    $cols=array_values(array_filter($cols,function($c)use($t){return $this->db->field_exists($c,$t);}));
    foreach($this->db->select(implode(',',$cols))->where_in('id',$invoiceIds)->get($t)->result_array() as $v)$data['ledger_invoice_details'][(int)$v['id']]=$v;
   }
   if($paymentIds){
    $t=db_prefix().'invoicepaymentrecords';
    if($this->db->table_exists($t)){
     $cols=['id','invoiceid','amount','date','paymentmode','transactionid','note'];
     $cols=array_values(array_filter($cols,function($c)use($t){return $this->db->field_exists($c,$t);}));
     foreach($this->db->select(implode(',',$cols))->where_in('id',$paymentIds)->get($t)->result_array() as $v)$data['ledger_payment_details'][(int)$v['id']]=$v;
    }
   }
  }
  // Calculate each manager's participation independently from the payment commission base (paid_share).
  $data['ledger_manager_shares']=[];
  if(!empty($data['ledger'])){
   $hierarchy=$data['hierarchy_rows'];
   $bySeller=[];foreach($hierarchy as $h){if((int)($h['active']??1)!==1)continue;$bySeller[(int)$h['seller_staff_id']][]=$h;}
   foreach($data['ledger'] as $entry){
    if(($entry['role']??'')!=='seller')continue;
    foreach(($bySeller[(int)$entry['staff_id']]??[]) as $h){
     $percent=(float)($h['participation_percent']??0);
     if($percent<=0)continue;
     $data['ledger_manager_shares'][]=['payment_id'=>$entry['payment_id'],'invoice_id'=>$entry['invoice_id'],'manager_name'=>$h['manager_name'],'seller_name'=>$entry['staff_name'],'percent'=>$percent,'commission_base'=>(float)$entry['paid_share'],'manager_amount'=>round((float)$entry['paid_share']*$percent/100,4)];
    }
   }
  }
  $data['ledger_payment_mode_names']=[];
  $modeTable=db_prefix().'payment_modes';
  if($this->db->table_exists($modeTable)){
   foreach($this->db->select('id,name')->get($modeTable)->result_array() as $mode){$data['ledger_payment_mode_names'][(string)$mode['id']]=$mode['name'];}
  }
  $this->load->view('commissions/center',$data);
 }
 public function commission_ledger_rebuild(){
  if(!is_admin()||strtoupper($this->input->method(TRUE))!=='POST')show_404();
  $level=(int)$this->input->post('level_id');
  $r=$this->scr->scr_rebuild_payment_commissions($level);
  if(!empty($r['skipped']))$this->session->set_flashdata('scr_commission_skipped',array_slice($r['skipped'],0,30));
  set_alert($r['ok']?'success':'danger',$r['ok']?('پرداخت‌های بررسی‌شده: '.(int)$r['count'].'؛ نیازمند بررسی: '.count($r['skipped']??[])):$r['message']);
  redirect(admin_url('sales_commercial_rules/commission_center?tab=ledger&level_id='.$level));
 }
 public function commission_assignment_delete(){
  if(!is_admin()||strtoupper($this->input->method(TRUE))!=='POST')show_404();
  scr_ensure_commission_center_schema();
  $id=(int)$this->input->post('assignment_id');
  $table=db_prefix().'scr_commission_rule_staff';
  if($id<=0||!$this->db->table_exists($table)){set_alert('danger','انتساب نامعتبر است');redirect(admin_url('sales_commercial_rules/commission_center?tab=staff'));return;}
  $existing=$this->db->where('id',$id)->get($table)->row_array();
  if(!$existing){set_alert('danger','انتساب پیدا نشد');redirect(admin_url('sales_commercial_rules/commission_center?tab=staff'));return;}
  $this->db->where('id',$id)->delete($table);
  set_alert($this->db->affected_rows()===1?'success':'danger',$this->db->affected_rows()===1?'انتساب حذف شد':'حذف انتساب انجام نشد');
  redirect(admin_url('sales_commercial_rules/commission_center?tab=staff'));
 }
 public function commission_center_save(){
  if(!is_admin()||strtoupper($this->input->method(TRUE))!=='POST')show_404();
  scr_ensure_commission_center_schema();$kind=(string)$this->input->post('kind');$ok=false;$message='اطلاعات نامعتبر است';
  if($kind==='policy'){
   $name=trim((string)$this->input->post('name'));$from=(string)$this->input->post('valid_from');$to=(string)$this->input->post('valid_to');$seller=(float)$this->input->post('seller_percent');$manager=(float)$this->input->post('manager_percent');
   if($name!==''&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$to)&&$from<=$to&&$seller>=0&&$seller<=100&&$manager>=0&&$manager<=100){$ok=$this->db->insert(db_prefix().'scr_commission_policies',['name'=>$name,'valid_from'=>$from,'valid_to'=>$to,'seller_percent'=>$seller,'manager_percent'=>$manager,'active'=>1]);}
  }elseif($kind==='assignment'){
   $staff=(int)$this->input->post('staff_id');$ids=$this->input->post('rule_ids');
   if(!is_array($ids))$ids=[];
   $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));
   if($staff>0&&$ids&&$this->db->where('staffid',$staff)->count_all_results(db_prefix().'staff')){
    $added=0;$skipped=0;$this->db->trans_begin();
    foreach($ids as $rid){
     $rule=$this->db->where('id',$rid)->get(db_prefix().'scr_commission_rules')->row_array();
     if(!$rule){$skipped++;continue;}
     $from=$rule['valid_from']??'1000-01-01';$to=$rule['valid_to']??'9999-12-31';
     if(!$from)$from='1000-01-01';if(!$to)$to='9999-12-31';
     if($this->db->where('staff_id',$staff)->where('rule_id',$rid)->count_all_results(db_prefix().'scr_commission_rule_staff')){$skipped++;continue;}
     if($this->db->insert(db_prefix().'scr_commission_rule_staff',['rule_id'=>$rid,'staff_id'=>$staff,'valid_from'=>$from,'valid_to'=>$to]))$added++;else $skipped++;
    }
    if($this->db->trans_status()===false){$this->db->trans_rollback();$message='خطا در ذخیره انتساب‌ها';}
    else{$this->db->trans_commit();$ok=$added>0;$message=$added.' سیاست ثبت شد؛ '.$skipped.' مورد تکراری یا نامعتبر بود';}
   }else $message='کارمند و حداقل یک سیاست را انتخاب کنید';
  }
  set_alert($ok?'success':'danger',$kind==='assignment'?$message:($ok?'ثبت شد':$message));redirect(admin_url('sales_commercial_rules/commission_center?tab='.($kind==='policy'?'policies':'staff')));
 }
 public function commissions(){
  if($this->input->post()){$r=$this->scr->save_commission($this->input->post());set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_saved'):$r['message']);redirect(admin_url('sales_commercial_rules/commission_center?tab=policies'));}
  $data['rows']=$this->scr->commissions();$data['groups']=$this->scr->groups();$data['types']=$this->scr->sale_types();$this->load->view('commissions/index',$data);
 }
 public function delete_commission($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$ok=$this->scr->delete_commission((int)$id);set_alert($ok?'success':'danger',$ok?_l('scr_deleted'):_l('scr_delete_failed'));redirect(admin_url('sales_commercial_rules/commissions'));}

 public function rankings(){
  if($this->input->post()){$r=$this->scr->save_ranking($this->input->post());set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_saved'):$r['message']);redirect(admin_url('sales_commercial_rules/rankings'));}
  $data['levels']=$this->scr->rankings();$data['types']=$this->scr->sale_types();$this->load->view('rankings/index',$data);
 }
 public function ranking_bulk_import(){
  if(!is_admin())access_denied('invoices');
  $data=['errors'=>[],'preview'=>[],'ready'=>false];
  if(strtoupper($this->input->method(TRUE))==='POST' && $this->input->post('confirm_import')){
   $rows=$this->session->userdata('scr_ranking_import_preview');
   if(!is_array($rows)||!count($rows)){set_alert('danger','ابتدا فایل را بررسی کنید');redirect(admin_url('sales_commercial_rules/ranking_bulk_import'));}
   $this->db->trans_begin();$ids=[];$failed='';
   foreach($rows as $r){$key=$r['period'].'|'.$r['from'].'|'.$r['to'];
    if(!isset($ids[$key])){$found=$this->db->where('name',$r['period'])->where('valid_from',$r['from'])->where('valid_to',$r['to'])->get(db_prefix().'scr_ranking_levels')->row_array();
     if($found){$failed='دوره تکراری است: '.$r['period'];break;}
     $res=$this->scr->save_ranking(['name'=>$r['period'],'valid_from'=>$r['from'],'valid_to'=>$r['to']]);if(!$res['ok']){$failed=$res['message'];break;}$ids[$key]=$res['id'];}
    $res=$this->scr->save_ranking_step(['level_id'=>$ids[$key],'name'=>$r['step'],'sale_type_id'=>$r['sale_type'],'ranking_role'=>$r['role'],'from_amount'=>$r['min'],'to_amount'=>$r['max'],'adjustment_type'=>$r['adjust_type'],'adjustment_value'=>$r['adjust']]);
    if(!$res['ok']){$failed='گام '.$r['step'].' در '.$r['period'].': '.$res['message'];break;}
   }
   if($failed!==''||$this->db->trans_status()===false){$this->db->trans_rollback();set_alert('danger',$failed?:'خطا در ذخیره‌سازی؛ هیچ ردیفی ثبت نشد');}
   else{$this->db->trans_commit();$this->session->unset_userdata('scr_ranking_import_preview');set_alert('success','ورود تجمیعی با موفقیت انجام شد');}
   redirect(admin_url('sales_commercial_rules/ranking_bulk_import'));
  }
  if(!empty($_FILES['ranking_file']['tmp_name'])){
   $file=$_FILES['ranking_file'];$ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));$raw=[];
   if($file['error']!==UPLOAD_ERR_OK||$file['size']>4*1024*1024)$data['errors'][]='فایل نامعتبر یا بزرگ‌تر از ۴ مگابایت است';
   elseif($ext==='csv'){$fp=fopen($file['tmp_name'],'r');while(($line=fgetcsv($fp,0,','))!==false){$raw[]=$line;if(count($raw)>5001)break;}fclose($fp);}
   elseif($ext==='xlsx'&&class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')){
    try{$book=\PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);$raw=$book->getActiveSheet()->toArray(null,true,true,false);}catch(\Throwable $e){$data['errors'][]='خواندن فایل اکسل ناموفق بود';}
   }elseif($ext==='xlsx'&&class_exists('ZipArchive')&&function_exists('simplexml_load_string')){
    $zip=new \ZipArchive();if($zip->open($file['tmp_name'])===true){
     $shared=[];$strings=$zip->getFromName('xl/sharedStrings.xml');if($strings!==false){$xml=@simplexml_load_string($strings);if($xml)foreach($xml->si as $si){$shared[]=trim(implode('',array_map('strval',$si->xpath('.//t')?:[])));}}
     $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');if($sheet!==false){$xml=@simplexml_load_string($sheet);if($xml){$xml->registerXPathNamespace('x','http://schemas.openxmlformats.org/spreadsheetml/2006/main');foreach($xml->xpath('//x:sheetData/x:row')?:[] as $row){$line=[];foreach($row->c as $cell){$ref=(string)$cell['r'];preg_match('/^[A-Z]+/',$ref,$match);$col=0;foreach(str_split($match[0]??'') as $letter)$col=$col*26+ord($letter)-64;$col--;if($col<0||$col>50)continue;$type=(string)$cell['t'];$value=(string)$cell->v;if($type==='s')$value=$shared[(int)$value]??'';elseif($type==='inlineStr')$value=(string)$cell->is->t;$line[$col]=$value;}if($line){$max=max(array_keys($line));$raw[]=array_map(function($n)use($line){return $line[$n]??'';},range(0,$max));}if(count($raw)>5001)break;}}}
     $zip->close();if(!$raw)$data['errors'][]='خواندن شیت اول فایل اکسل ناموفق بود';
    }else $data['errors'][]='فایل XLSX معتبر نیست';
   }else $data['errors'][]='فرمت فایل پشتیبانی نمی‌شود؛ CSV یا XLSX ارسال کنید';
   if(count($raw)>5001)$data['errors'][]='حداکثر ۵۰۰۰ ردیف قابل ورود است';
   if(!$data['errors']){
    $head=array_map(function($x){return strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',(string)$x)));},array_shift($raw)?:[]);
    $required=['period','from','to','role','sale_type','step','min','max','adjust_type','adjust'];
    $positions=[];foreach($required as $col){$ix=array_search($col,$head,true);if($ix===false)$data['errors'][]='ستون لازم یافت نشد: '.$col;else $positions[$col]=$ix;}
    $seen=[];$ranges=[];$types=[];foreach($this->scr->sale_types() as $t){$types[(int)$t['id']]=true;if(!empty($t['legacy_id']))$types[(int)$t['legacy_id']]=true;}
    if(!$data['errors'])foreach($raw as $i=>$line){if(!count(array_filter($line,function($v){return trim((string)$v)!=='';})))continue;$r=[];foreach($positions as $col=>$ix)$r[$col]=trim((string)($line[$ix]??''));$lineNo=$i+2;
     if($r['period']===''||$r['step']===''||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$r['from'])||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$r['to'])||$r['from']>$r['to']||!in_array($r['role'],['seller','marketer'],true)||!isset($types[(int)$r['sale_type']])||!in_array($r['adjust_type'],['percent','fixed'],true)||!is_numeric($r['min'])||!is_numeric($r['max'])||!is_numeric($r['adjust'])||(float)$r['min']<0||(float)$r['max']<(float)$r['min']||($r['adjust_type']==='percent'&&((float)$r['adjust']< -100||(float)$r['adjust']>1000))){$data['errors'][]='مقادیر نامعتبر در ردیف '.$lineNo;continue;}
     $r['sale_type']=(int)$r['sale_type'];$key=$r['period'].'|'.$r['from'].'|'.$r['to'].'|'.$r['role'].'|'.$r['sale_type'];
     foreach($ranges[$key]??[] as $previous){if((float)$r['min']<=(float)$previous['max']&&(float)$r['max']>=(float)$previous['min']){$data['errors'][]='هم‌پوشانی بازه مبلغ در ردیف '.$lineNo;break;}}
     $ranges[$key][]=$r;$data['preview'][]=$r;
    }
    if(count($data['preview'])===0)$data['errors'][]='هیچ گام معتبری یافت نشد';
    if(!$data['errors']){foreach($data['preview'] as $r){$existing=$this->db->where('name',$r['period'])->where('valid_from',$r['from'])->where('valid_to',$r['to'])->get(db_prefix().'scr_ranking_levels')->row_array();if($existing){$data['errors'][]='دوره از قبل وجود دارد: '.$r['period'];break;}}}
    if(!$data['errors']){$this->session->set_userdata('scr_ranking_import_preview',$data['preview']);$data['ready']=true;}else $this->session->unset_userdata('scr_ranking_import_preview');
   }
  }
  $this->load->view('rankings/bulk_import',$data);
 }
 public function ranking_details($id=0){
  $id=(int)$id;$level=$this->scr->ranking($id);if(!$level)show_404();
  if(($this->input->get('tab')==='results') && (int)($level['commission_paid']??0)!==1){$this->scr->scr_refresh_live_ranking($id);$level=$this->scr->ranking($id);}
  $data['level']=$level;$data['types']=$this->scr->sale_types();$data['marketing_categories']=$this->scr->marketing_categories();$data['marketing_labels']=$this->scr->marketing_category_labels();$data['steps']=$this->scr->ranking_steps($id);$data['ledger']=$this->scr->ranking_invoice_ledger($id);$data['beneficiaries']=$this->scr->scr_ranking_payment_beneficiaries($id);
  // Reuse the same seller-payment base and hierarchy participation rules as the commission center.
  // These are display-only rows; do not create commission or settlement records.
  $data['ranking_manager_shares']=[];
  $scrHierarchyBySeller=[];
  foreach($this->scr->hierarchy() as $scrH){
   if((int)($scrH['active']??1)!==1 || (float)($scrH['participation_percent']??0)<=0)continue;
   $scrHierarchyBySeller[(int)$scrH['seller_staff_id']][]=$scrH;
  }
  foreach($data['ledger'] as $scrInvoicePayment){
   $scrSeller=(int)($scrInvoicePayment['seller_staff_id']??0);
   $scrPaid=(float)($scrInvoicePayment['paid_amount']??0);
   if($scrSeller<=0||$scrPaid<=0)continue;
   foreach(($scrHierarchyBySeller[$scrSeller]??[]) as $scrH){
    $scrPercent=(float)$scrH['participation_percent'];
    $data['ranking_manager_shares'][(int)$scrInvoicePayment['payment_id']][]=[
     'invoice_id'=>(int)$scrInvoicePayment['invoice_id'],
     'staff_name'=>$scrH['manager_name'],
     'staff_id'=>(int)$scrH['manager_staff_id'],
     'role'=>'manager',
     'paid_share'=>$scrPaid,
     'rule_percent'=>$scrPercent,
     'commission_amount'=>round($scrPaid*$scrPercent/100,4),
    ];
   }
  }$data['results']=$this->scr->ranking_results($id);
  $this->load->view('rankings/details',$data);
 }
 public function commission_payment_ledger($id=0){
  if(!is_admin()&&!has_permission('invoices','','view'))access_denied('invoices');
  $data['level']=$this->scr->ranking((int)$id);if(!$data['level'])show_404();
  $data['rows']=$this->scr->scr_payment_commission_rows((int)$id);
  $this->load->view('commissions/payment_ledger',$data);
 }
 public function rebuild_commission_payments($id=0){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('invoices');
  $r=$this->scr->scr_rebuild_payment_commissions((int)$id);
  $msg=$r['ok']?'پرداخت‌های بررسی‌شده: '.$r['count'].'؛ موارد نیازمند بررسی: '.count($r['skipped']??[]):$r['message'];
  if(!empty($r['skipped']))$this->session->set_flashdata('scr_commission_skipped',$r['skipped']);
  set_alert($r['ok']?'success':'danger',$msg);
  redirect(admin_url('sales_commercial_rules/commission_payment_ledger/'.(int)$id));
 }
 public function clone_ranking($id=0){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('invoices');
  $r=$this->scr->clone_ranking((int)$id,$this->input->post('name'),$this->input->post('valid_from'),$this->input->post('valid_to'));
  set_alert($r['ok']?'success':'danger',$r['ok']?'دوره همراه با '.(int)$r['count'].' گام کپی شد.':$r['message']);
  redirect(admin_url('sales_commercial_rules/rankings'));
 }
 public function delete_ranking($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$ok=$this->scr->delete_ranking((int)$id);set_alert($ok?'success':'danger',$ok?_l('scr_deleted'):_l('scr_delete_failed'));redirect(admin_url('sales_commercial_rules/rankings'));}

 public function online_invoice_attributions(){
  if(!is_admin())access_denied('Settings');
  $this->scr->scr_attribution_table();
  $t=db_prefix().'scr_invoice_staff_attributions';$i=db_prefix().'invoices';
  if(strtoupper($this->input->method(TRUE))==='POST'){
   $id=(int)$this->input->post('invoice_id');$staff=(int)$this->input->post('staff_id');
   $inv=$this->db->select('id,sale_agent')->where('id',$id)->get($i)->row_array();
   $person=$staff?$this->db->where('staffid',$staff)->get(db_prefix().'staff')->row_array():null;
   if(!$inv||($staff&&!$person)||(int)$inv['sale_agent']!==0){set_alert('danger','فاکتور یا کارشناس نامعتبر است، یا فروشنده فاکتور از قبل ثبت شده است.');}
   else{
    $this->db->where('invoice_id',$id)->delete($t);
    if($staff)$this->db->insert($t,['invoice_id'=>$id,'staff_id'=>$staff,'verified_by'=>(int)get_staff_user_id(),'verified_at'=>date('Y-m-d H:i:s')]);
    set_alert('success','انتساب تأییدشده ذخیره شد.');
   }
   redirect(admin_url('sales_commercial_rules/online_invoice_attributions'));
  }
  $data['staff']=$this->db->select('staffid,firstname,lastname')->where('active',1)->get(db_prefix().'staff')->result_array();
  $data['invoices']=$this->db->select('i.id,i.formatted_number,i.date,i.sale_agent,a.staff_id',false)->from($i.' i')->join($t.' a','a.invoice_id=i.id','left')->where('i.date >=',date('Y-m-d',strtotime('-60 days')))->where('i.sale_agent',0)->order_by('i.id','DESC')->limit(150)->get()->result_array();
  $this->load->view('online_promotion/attributions',$data);
 }
 public function online_promotion(){
  if(!is_admin())access_denied('Settings');
  $tab=(string)$this->input->get('tab');
  if(!in_array($tab,['categories','rules','compensation','status'],true))$tab='categories';
  $data=['tab'=>$tab];
  if($tab==='categories'){
   $this->scr->scr_create_marketing_category_table();
   $data['categories']=$this->scr->marketing_categories();
  }elseif($tab==='rules'){
   $data['schema_ok']=$this->scr->scr_ensure_promotion_rules_table();
   $data['categories']=$this->scr->marketing_categories();
   $data['rules']=$this->scr->scr_promotion_rules();
  }elseif($tab==='status'){
   $data['status_rows']=$this->scr->scr_online_status();
   $data['stage_assignments']=$this->scr->scr_online_staff_stages();
   $data['stage_categories']=$this->scr->marketing_categories();
   $data['stage_staff']=$this->db->select('staffid,firstname,lastname')->where('active',1)->order_by('firstname','ASC')->get(db_prefix().'staff')->result_array();
  }else{
   $data['schema_ok']=$this->scr->scr_ensure_call_center_compensation_table();
   $data['compensations']=$this->scr->scr_call_center_compensations();
   $data['staff']=$this->db->select('staffid,firstname,lastname')->where('active',1)->order_by('firstname','ASC')->get(db_prefix().'staff')->result_array();
  }
  $this->load->view('online_promotion/index',$data);
 }
 public function online_status_decision(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $r=$this->scr->scr_online_status_decide($this->input->post(),(int)get_staff_user_id());
  set_alert($r['ok']?'success':'danger',$r['message']);
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=status'));
 }
 public function online_staff_stage_save(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $r=$this->scr->scr_assign_online_staff_stage($this->input->post(),(int)get_staff_user_id());
  set_alert($r['ok']?'success':'danger',$r['message']);
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=status'));
 }
 public function online_compensation_delete(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $ok=$this->scr->scr_delete_call_center_compensation((int)$this->input->post('id'));
  set_alert($ok?'success':'danger',$ok?'تنظیم حقوق و پاداش حذف شد.':'حذف انجام نشد.');
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=compensation'));
 }
 public function online_compensation_save(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $r=$this->scr->scr_save_call_center_compensation($this->input->post());
  set_alert($r['ok']?'success':'danger',$r['ok']?'حقوق ثابت و پاداش ذخیره شد.':$r['message']);
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=compensation'));
 }
 public function promotion_rules(){
  if(!is_admin())access_denied('Settings');
  $data['schema_ok']=$this->scr->scr_ensure_promotion_rules_table();
  $data['categories']=$this->scr->marketing_categories();
  $data['rules']=$this->scr->scr_promotion_rules();
  $this->load->view('promotions/index',$data);
 }
 public function promotion_rule_save(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $r=$this->scr->scr_save_promotion_rule($this->input->post());
  set_alert($r['ok']?'success':'danger',$r['ok']?'قاعده ارتقا ذخیره شد.':$r['message']);
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=rules'));
 }
 public function promotion_rule_delete(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $ok=$this->scr->scr_delete_promotion_rule((int)$this->input->post('id'));
  set_alert($ok?'success':'danger',$ok?'قاعده حذف شد.':'حذف قاعده انجام نشد.');
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=rules'));
 }
 public function marketing_categories(){
  if(!is_admin())access_denied('Settings');
  $this->scr->scr_create_marketing_category_table();
  $data['categories']=$this->scr->marketing_categories();
  $this->load->view('marketing_categories/index',$data);
 }
 public function marketing_category_save(){
  if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  if(!is_admin())access_denied('Settings');
  $result=$this->scr->save_marketing_category($this->input->post());
  set_alert($result['ok']?'success':'danger',$result['ok']?'نوع بازاریابی ذخیره شد.':$result['message']);
  redirect(admin_url('sales_commercial_rules/online_promotion?tab=categories'));
 }
 public function save_marketing_category($levelId=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();if(!is_admin())access_denied('Settings');$r=$this->scr->save_marketing_category($this->input->post());set_alert($r['ok']?'success':'danger',$r['ok']?'نوع بازاریابی ذخیره شد.':$r['message']);redirect(admin_url('sales_commercial_rules/ranking_details/'.(int)$levelId.'?tab=steps'));}
 public function save_ranking_step($levelId=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$d=$this->input->post();$d['level_id']=(int)$levelId;$r=$this->scr->save_ranking_step($d);set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_saved'):$r['message']);redirect(admin_url('sales_commercial_rules/ranking_details/'.(int)$levelId.'?tab=steps'));}
 public function delete_ranking_step($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$level=(int)$this->input->post('level_id');$ok=$this->scr->delete_ranking_step((int)$id);set_alert($ok?'success':'danger',$ok?_l('scr_deleted'):_l('scr_delete_failed'));redirect(admin_url('sales_commercial_rules/ranking_details/'.$level.'?tab=steps'));}
 public function calculate_ranking($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$r=$this->scr->calculate_ranking((int)$id);set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_ranking_calculated'):$r['message']);redirect(admin_url('sales_commercial_rules/ranking_details/'.(int)$id.'?tab=results'));}

 public function mark_ranking_paid($id=0){if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$r=$this->scr->mark_ranking_paid((int)$id);set_alert($r['ok']?'success':'danger',$r['ok']?_l('scr_commission_marked_paid'):$r['message']);redirect(admin_url('sales_commercial_rules/ranking_details/'.(int)$id.'?tab=results'));}
 public function canceled_commissions(){$data['rows']=$this->scr->canceled_commissions();$this->load->view('canceled_commissions/index',$data);}

 public function sales_settings(){
  if(!is_admin()&&!scr_cashier_can_settings()) access_denied('scr_cashier');
  if($this->input->post('add_pos_device')){
   $name=trim((string)$this->input->post('pos_device_name',true));if($name!==''){$t=db_prefix().'scr_pos_devices';$this->db->insert($t,['name'=>$name,'active'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);set_alert('success','دستگاه POS اضافه شد.');}redirect(admin_url('sales_commercial_rules/sales_settings'));
  }
  if($this->input->post('toggle_pos_device')){
   $id=(int)$this->input->post('pos_device_id');$active=(int)$this->input->post('active');$this->db->where('id',$id)->update(db_prefix().'scr_pos_devices',['active'=>$active?1:0,'updated_at'=>date('Y-m-d H:i:s')]);set_alert('success','وضعیت دستگاه POS به‌روزرسانی شد.');redirect(admin_url('sales_commercial_rules/sales_settings'));
  }
  if($this->input->post('save_sales_settings')){
   $limit=abs((float)$this->input->post('manual_adjustment_limit'));
   if($limit<=0)$limit=5000;
   update_option('scr_invoice_manual_adjustment_limit',$limit);
   set_alert('success','تنظیمات فروش ذخیره شد.');
   redirect(admin_url('sales_commercial_rules/sales_settings'));
  }
  $data['manual_adjustment_limit']=(float)get_option('scr_invoice_manual_adjustment_limit');
  if($data['manual_adjustment_limit']<=0)$data['manual_adjustment_limit']=5000;
  scr_ensure_cashier_schema();$data['pos_devices']=$this->db->order_by('id','DESC')->get(db_prefix().'scr_pos_devices')->result_array();
  $this->load->view('sales_settings/index',$data);
 }


 public function return_payment_lock(){
  if(!is_staff_logged_in())show_404();$id=(int)$this->input->get('invoice_id');$number=trim((string)$this->input->get('invoice_number',true));
  $it=db_prefix().'invoices';$rt=db_prefix().'sdf_returns';if($id<=0&&$number!==''&&$this->db->table_exists($it)){$r=$this->db->select('id')->where('number',$number)->order_by('id','DESC')->limit(1)->get($it)->row_array();$id=(int)($r['id']??0);}
  $locked=false;if($id>0&&$this->db->table_exists($rt))$locked=$this->db->where('invoice_id',$id)->count_all_results($rt)>0;
  $this->output->set_content_type('application/json')->set_output(json_encode(['invoice_id'=>$id,'locked'=>$locked],JSON_UNESCAPED_UNICODE));
 }

 public function payment_meta(){
  if(!is_staff_logged_in()) show_404();
  scr_ensure_cashier_schema();
  $raw=(string)$this->input->get('ids',true);$ids=array_values(array_unique(array_filter(array_map('intval',explode(',',$raw)))));
  if(count($ids)>100)$ids=array_slice($ids,0,100);$rows=[];
  if($ids){
   $pt=db_prefix().'invoicepaymentrecords';$lt=db_prefix().'scr_cashier_payment_links';$dt=db_prefix().'scr_pos_devices';
   $this->db->select('p.id,p.date,d.name AS pos_name',false)->from($pt.' p')->join($lt.' l','l.payment_id=p.id','left')->join($dt.' d','d.id=l.pos_device_id','left')->where_in('p.id',$ids);
   foreach($this->db->get()->result_array() as $r)$rows[(string)(int)$r['id']]=['date'=>(string)$r['date'],'pos_name'=>(string)($r['pos_name']??'')];
  }
  $this->output->set_content_type('application/json')->set_output(json_encode(['rows'=>$rows],JSON_UNESCAPED_UNICODE));
 }

 public function delete_payment_safe($id=0){
  if(!is_staff_logged_in())show_404();
  $id=(int)$id;if($id<=0)show_404();
  // Mirror the native Payments permission as closely as possible without invoking its failing post-delete chain.
  if(!is_admin() && function_exists('has_permission') && !has_permission('payments','','delete') && !has_permission('invoices','','delete')) access_denied('payments');
  $pt=db_prefix().'invoicepaymentrecords';
  if(!$this->db->table_exists($pt)){set_alert('warning','جدول پرداخت‌ها در دسترس نیست.');redirect(admin_url('payments'));}
  $payment=$this->db->where('id',$id)->get($pt)->row_array();
  if(!$payment){set_alert('warning','پرداخت موردنظر یافت نشد.');redirect(admin_url('payments'));}
  $invoiceId=(int)($payment['invoiceid']??0);
  $this->db->trans_start();
  // Remove only SCR auxiliary metadata first; no hooks/audits are fired from this safe route.
  $lt=db_prefix().'scr_cashier_payment_links';
  if($this->db->table_exists($lt))$this->db->where('payment_id',$id)->delete($lt);
  $this->db->where('id',$id)->delete($pt);
  $deleted=$this->db->affected_rows()>0;
  $this->db->trans_complete();
  if($deleted && $this->db->trans_status()!==false){
   // Recalculate native invoice status after the payment row is gone, but do not run payment-delete hooks.
   if($invoiceId>0 && function_exists('update_invoice_status')){try{update_invoice_status($invoiceId);}catch(Throwable $e){}}
   set_alert('success','پرداخت با موفقیت حذف شد.');
  }else set_alert('warning','مشکل در حذف پرداخت');
  redirect(admin_url('payments'));
 }

 public function cashier(){
  if(!scr_cashier_can_own()&&!scr_cashier_can_review()&&!scr_cashier_can_settings()) access_denied('scr_cashier');
  $date=$this->input->get('date',true)?:date('Y-m-d');$staff=(int)get_staff_user_id();
  if($this->input->post('submit_cashier')){if(!scr_cashier_can_own())access_denied('scr_cashier');$ok=$this->scr->submit_cashier($staff,$date,$this->input->post('actual_cash'),$this->input->post('cashier_note',true));set_alert($ok?'success':'warning',$ok?_l('scr_cashier_submitted'):_l('scr_action_not_allowed'));redirect(admin_url('sales_commercial_rules/cashier?date='.$date));}
  if($this->input->post('cashier_response_submit')){if(!scr_cashier_can_own())access_denied('scr_cashier');$ok=$this->scr->cashier_respond($staff,$date,$this->input->post('cashier_response',true));set_alert($ok?'success':'warning',$ok?_l('scr_cashier_resubmitted'):_l('scr_action_not_allowed'));redirect(admin_url('sales_commercial_rules/cashier?date='.$date));}
  if($this->input->post('save_modes')){if(!scr_cashier_can_settings())access_denied('scr_cashier');$this->scr->save_payment_mode_categories($this->input->post());set_alert('success',_l('scr_saved'));redirect(admin_url('sales_commercial_rules/cashier?date='.$date));}
  $data['date']=$date;$data['can_own']=scr_cashier_can_own();$data['can_review']=scr_cashier_can_review();$data['can_settings']=scr_cashier_can_settings();
  $data['report']=$data['can_own']?$this->scr->cashier_report($staff,$date):null;$data['modes']=$data['can_settings']?$this->scr->known_payment_modes():[];$data['sessions']=$data['can_review']?$this->scr->cashier_sessions($this->input->get('all_date',true)?:'',$this->input->get('status',true)?:''):[];$this->load->view('cashier/index',$data);
 }
 public function cashier_review($id=0){if(!scr_cashier_can_review()||strtoupper($this->input->method(TRUE))!=='POST')show_404();$status=$this->input->post('decision',true);$ok=$this->scr->review_cashier((int)$id,$status,$this->input->post('accountant_note',true),(int)get_staff_user_id(),$this->input->post('discrepancy_type',true),$this->input->post('discrepancy_amount'));set_alert($ok?'success':'warning',$ok?_l('scr_saved'):_l('scr_action_not_allowed'));redirect(admin_url('sales_commercial_rules/cashier'));}
 public function cashier_print($id=0){$s=$this->db->where('id',(int)$id)->get(db_prefix().'scr_cashier_sessions')->row_array();if(!$s)show_404();$own=scr_cashier_can_own()&&(int)$s['staff_id']===(int)get_staff_user_id();if(!$own&&!scr_cashier_can_review())show_404();$data['report']=$this->scr->cashier_report((int)$s['staff_id'],$s['business_date']);$this->load->view('cashier/print',$data);}



 public function sales_requests(){
  if(!scr_cashier_can_own()&&!scr_cashier_can_review())access_denied('scr_cashier');scr_ensure_cashier_schema();
  $t=db_prefix().'sps_sales_requests';$data['rows']=[];if($this->db->table_exists($t)){$this->db->select($t.'.*,CONCAT(s.firstname," ",s.lastname) seller_name,c.company client_name,st.name sale_type_name',false)->from($t)->join(db_prefix().'staff s','s.staffid='.$t.'.seller_staff_id','left')->join(db_prefix().'clients c','c.userid='.$t.'.client_id','left')->join(db_prefix().'scr_sale_types st','st.id='.$t.'.sale_type_id','left')->order_by($t.'.id','DESC');$data['rows']=$this->db->get()->result_array();}
  $this->load->view('sales_requests/index',$data);
 }
 public function sales_request_action($id=0){
  if(!scr_cashier_can_own()&&!scr_cashier_can_review())access_denied('scr_cashier');scr_ensure_cashier_schema();$t=db_prefix().'sps_sales_requests';$r=$this->db->where('id',(int)$id)->get($t)->row_array();if(!$r)show_404();$act=(string)$this->input->get('do',true);
  if($act==='return'){$this->db->where('id',(int)$id)->update($t,['status'=>'returned','cashier_staff_id'=>(int)get_staff_user_id(),'cashier_note'=>(string)$this->input->get('note',true),'updated_at'=>date('Y-m-d H:i:s')]);set_alert('success','درخواست به فروشنده برگشت داده شد.');redirect(admin_url('sales_commercial_rules/sales_requests'));}
  if($act==='invoice'||$act==='estimate'){$this->db->where('id',(int)$id)->update($t,['status'=>'reviewing','cashier_staff_id'=>(int)get_staff_user_id(),'target_type'=>$act,'updated_at'=>date('Y-m-d H:i:s')]);$url=$act==='invoice'?admin_url('invoices/invoice?sps_request='.(int)$id):admin_url('estimates/estimate?sps_request='.(int)$id);redirect($url);}
  redirect(admin_url('sales_commercial_rules/sales_requests'));
 }

 public function monthly_goals(){
  if(!is_admin())access_denied('invoices');$month=(string)$this->input->get('month',true);if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/',$month))$month=date('Y-m');
  $data=['month'=>$month,'rows'=>$this->scr->scr_goals_report($month),'staff'=>$this->scr->staff_list(),'types'=>$this->scr->sale_types(),'goal_tab'=>$this->input->get('tab',true)==='settings'?'settings':'report','goal_closed'=>$this->scr->scr_goal_month_status($month)];$this->load->view('goals/index',$data);
 }
 public function monthly_goal_month_action(){
  if(!is_admin())access_denied('invoices');if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  $month=(string)$this->input->post('month');$action=(string)$this->input->post('action');
  if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/',$month))show_404();
  $ok=$action==='close'?$this->scr->scr_goal_close_month($month,get_staff_user_id()):($action==='reopen'?$this->scr->scr_goal_reopen_month($month):false);
  set_alert($ok?'success':'warning',$ok?'وضعیت گزارش ماه تغییر کرد':'امکان تغییر وضعیت ماه وجود ندارد');
  redirect(admin_url('sales_commercial_rules/monthly_goals?month='.rawurlencode($month).'&tab=report'));
 }
 public function save_monthly_goal(){
  if(!is_admin())access_denied('invoices');if(strtoupper($this->input->method(TRUE))!=='POST')show_404();
  $data=$this->input->post();$ids=isset($data['staff_ids'])&&is_array($data['staff_ids'])?$data['staff_ids']:[];
  if(!$ids&&isset($data['staff_id']))$ids=[$data['staff_id']];
  $ids=array_values(array_unique(array_filter(array_map('intval',$ids),function($id){return $id>0;})));
  $saved=0;$errors=[];
  if(!$ids)$errors[]='حداقل یک کارمند انتخاب کنید';
  if(count($ids)>1000)$errors[]='تعداد افراد انتخاب‌شده بیش از حد مجاز است';
  if(!$errors){foreach($ids as $id){$item=$data;$item['staff_id']=$id;unset($item['staff_ids']);$r=$this->scr->scr_goals_save($item);if($r['ok'])$saved++;else $errors[]='کارمند '.$id.': '.$r['message'];}}
  set_alert($errors?'warning':'success',$saved.' هدف ذخیره شد'.($errors?'؛ '.count($errors).' خطا: '.implode('، ',array_slice($errors,0,3)):''));
  redirect(admin_url('sales_commercial_rules/monthly_goals?month='.rawurlencode((string)$this->input->post('month')).'&tab=settings'));
 }
 public function delete_monthly_goal($id=0){if(!is_admin())access_denied('invoices');if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$this->scr->scr_goals_delete($id);redirect(admin_url('sales_commercial_rules/monthly_goals?tab=settings'));}
 public function copy_monthly_goals(){if(!is_admin())access_denied('invoices');if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$n=$this->scr->scr_goals_copy((string)$this->input->post('from_month'),(string)$this->input->post('to_month'));set_alert('success',$n.' هدف کپی شد');redirect(admin_url('sales_commercial_rules/monthly_goals?month='.rawurlencode((string)$this->input->post('to_month')).'&tab=settings'));}
 public function import_monthly_goals(){
  if(!is_admin())access_denied('invoices');if(strtoupper($this->input->method(TRUE))!=='POST')show_404();$file=$_FILES['goals_file']??null;$count=0;$errors=[];
  if(!$file||$file['error']!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name'])||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='csv'){set_alert('danger','فایل CSV معتبر انتخاب کنید');redirect(admin_url('sales_commercial_rules/monthly_goals'));return;}
  $fh=fopen($file['tmp_name'],'r');$head=fgetcsv($fh);$expected=['month','role','staff_id','sale_type_id','target_amount'];if($head)$head[0]=preg_replace('/^\xEF\xBB\xBF/','',$head[0]);
  if($head!==$expected){fclose($fh);set_alert('danger','ستون‌ها باید مطابق الگوی CSV باشند');redirect(admin_url('sales_commercial_rules/monthly_goals'));return;}
  while(($row=fgetcsv($fh))!==false){if(count($row)!==5){$errors[]='ستون نامعتبر';continue;}$r=$this->scr->scr_goals_save(array_combine($expected,$row));if($r['ok'])$count++;else $errors[]=$r['message'];}fclose($fh);set_alert($errors?'warning':'success',$count.' هدف وارد شد؛ '.count($errors).' خطا');redirect(admin_url('sales_commercial_rules/monthly_goals'));
 }
}
