<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Sales_commercial_rules_model extends App_Model{
 // Normalize user-entered amounts (including Persian/Arabic numerals) before ranking-step validation.
 private function num($value){
  if($value===null || is_array($value) || is_object($value))return null;
  $v=trim((string)$value);
  if($v==='')return null;
  $v=strtr($v,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9','٫'=>'.','−'=>'-']);
  $v=str_replace([',','٬','،',' ',' '], '', $v);
  if(!preg_match('/^[+-]?(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/D',$v))return null;
  $n=(float)$v;
  return is_finite($n)?$n:null;
 }

 public function sale_types($active=null){if($active!==null)$this->db->where('active',(int)$active);return $this->db->order_by('id','ASC')->get(db_prefix().'scr_sale_types')->result_array();}
 public function save_sale_type($d){$id=(int)($d['id']??0);$name=trim((string)($d['name']??''));if($name==='')return ['ok'=>false,'message'=>_l('scr_sale_type_required')];$active=(int)($d['active']??1);$this->db->where('name',$name);if($id)$this->db->where('id !=',$id);if($this->db->get(db_prefix().'scr_sale_types')->row())return ['ok'=>false,'message'=>_l('scr_sale_type_duplicate')];$row=['name'=>$name,'active'=>$active?1:0,'updated_at'=>date('Y-m-d H:i:s')];if($id){if(!$this->db->where('id',$id)->get(db_prefix().'scr_sale_types')->row())return ['ok'=>false,'message'=>_l('scr_sale_type_not_found')];$this->db->where('id',$id)->update(db_prefix().'scr_sale_types',$row);}else{$row['created_at']=date('Y-m-d H:i:s');$this->db->insert(db_prefix().'scr_sale_types',$row);$id=$this->db->insert_id();$this->db->where('id',$id)->update(db_prefix().'scr_sale_types',['legacy_id'=>$id]);}$this->sync_type($id);return ['ok'=>true,'id'=>$id];}
 public function delete_sale_type($id){$id=(int)$id;$r=$this->db->where('id',$id)->get(db_prefix().'scr_sale_types')->row();if(!$r)return ['ok'=>false,'message'=>_l('scr_sale_type_not_found')];$sid=(int)($r->legacy_id?:$r->id);$refs=[];$checks=[[db_prefix().'item_sale_type_prices','sale_type_id'],[db_prefix().'invoices','invoice_type'],[db_prefix().'invoices','sale_type_id'],[db_prefix().'scr_commission_rules','sale_type_id']];foreach($checks as $x){if($this->db->table_exists($x[0])&&$this->db->field_exists($x[1],$x[0])&&$this->db->where($x[1],$sid)->limit(1)->get($x[0])->row()){$refs[]=$x[0].'.'.$x[1];}}if($refs){$this->db->where('id',$id)->update(db_prefix().'scr_sale_types',['active'=>0,'updated_at'=>date('Y-m-d H:i:s')]);$this->sync_type($id);return ['ok'=>false,'message'=>_l('scr_sale_type_in_use_disabled')];}$this->db->where('id',$id)->delete(db_prefix().'scr_sale_types');$t=db_prefix().'item_discount_sell_type';if($this->db->table_exists($t))$this->db->where('st_id',$sid)->delete($t);return ['ok'=>true];}
 private function sync_type($id){$r=$this->db->where('id',$id)->get(db_prefix().'scr_sale_types')->row();if(!$r)return;$lid=(int)($r->legacy_id?:$r->id);$t=db_prefix().'item_discount_sell_type';$x=$this->db->where('st_id',$lid)->get($t)->row();$d=['st_name'=>$r->name,'st_status'=>(int)$r->active];if($x)$this->db->where('st_id',$lid)->update($t,$d);else{$d['st_id']=$lid;$this->db->insert($t,$d);}}
 public function discounts($filters=[]){
  $this->db->select('d.*,g.name group_name')->from(db_prefix().'scr_discount_rules d')->join(db_prefix().'items_groups g','g.id=d.group_id','left');
  if(!empty($filters['group_id']))$this->db->where('d.group_id',(int)$filters['group_id']);
  if(isset($filters['base_percent']) && $filters['base_percent']!=='')$this->db->where('d.base_percent',(float)$filters['base_percent']);
  if(isset($filters['max_percent']) && $filters['max_percent']!=='')$this->db->where('d.max_percent',(float)$filters['max_percent']);
  if(!empty($filters['valid_from'])){ $v=$this->normalize_date($filters['valid_from']); if($v)$this->db->where('d.valid_from >=',$v); }
  if(!empty($filters['valid_to'])){ $v=$this->normalize_date($filters['valid_to']); if($v)$this->db->where('d.valid_to <=',$v); }
  return $this->db->order_by('d.id','DESC')->get()->result_array();
 }
 public function groups(){return $this->db->order_by('name','ASC')->get(db_prefix().'items_groups')->result_array();}
 private function normalize_date($v){
  $v=trim((string)$v); if($v==='')return false;
  $v=strtr($v,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  $v=str_replace(['/','.'],'-',$v); $parts=explode('-',$v);
  if(count($parts)===3 && (int)$parts[0]>=1200 && (int)$parts[0]<=1600){$g=$this->jalali_to_gregorian((int)$parts[0],(int)$parts[1],(int)$parts[2]);return sprintf('%04d-%02d-%02d',$g[0],$g[1],$g[2]);}
  $sql=to_sql_date($v); return $sql?:false;
 }
 private function jalali_to_gregorian($jy,$jm,$jd){
  $jy+=1595;$days=-355668+(365*$jy)+(intdiv($jy,33)*8)+intdiv((($jy%33)+3),4)+$jd+(($jm<7)?(($jm-1)*31):((($jm-7)*30)+186));
  $gy=400*intdiv($days,146097);$days%=146097;if($days>36524){$gy+=100*intdiv(--$days,36524);$days%=36524;if($days>=365)$days++;}$gy+=4*intdiv($days,1461);$days%=1461;if($days>365){$gy+=intdiv($days-1,365);$days=($days-1)%365;}$gd=$days+1;$sal=[0,31,(($gy%4==0&&$gy%100!=0)||($gy%400==0))?29:28,31,30,31,30,31,31,30,31,30,31];for($gm=1;$gm<=12&&$gd>$sal[$gm];$gm++)$gd-=$sal[$gm];return [$gy,$gm,$gd];
 }
 public function delete_discount($id){
  $id=(int)$id;if($id<=0)return false;
  return (bool)$this->db->where('id',$id)->delete(db_prefix().'scr_discount_rules');
 }
 public function save_discount($d){
  $id=(int)($d['id']??0);
  $rawGroup=trim((string)($d['group_id']??'')); $rawBase=trim((string)($d['base_percent']??'')); $rawMax=trim((string)($d['max_percent']??'')); $rawFrom=trim((string)($d['valid_from']??'')); $rawTo=trim((string)($d['valid_to']??''));
  if($rawGroup==='' || (int)$rawGroup<=0)return ['ok'=>false,'message'=>_l('scr_group_required')];
  if($rawBase==='')return ['ok'=>false,'message'=>_l('scr_base_required')];
  if($rawMax==='')return ['ok'=>false,'message'=>_l('scr_max_required')];
  if($rawFrom==='')return ['ok'=>false,'message'=>_l('scr_from_required')];
  if($rawTo==='')return ['ok'=>false,'message'=>_l('scr_to_required')];
  $rawBase=strtr($rawBase,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  $rawMax=strtr($rawMax,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  if(!is_numeric($rawBase)||!is_numeric($rawMax))return ['ok'=>false,'message'=>_l('scr_percent_numeric')];
  $base=(float)$rawBase;$max=(float)$rawMax;
  if($base<0||$base>100||$max<0||$max>100)return ['ok'=>false,'message'=>_l('scr_percent_range')];
  if($max<$base)return ['ok'=>false,'message'=>_l('scr_max_less_base')];
  $from=$this->normalize_date($rawFrom);$to=$this->normalize_date($rawTo);$g=(int)$rawGroup;
  if(!$from||!$to)return ['ok'=>false,'message'=>_l('scr_invalid_dates')];
  if($from>$to)return ['ok'=>false,'message'=>_l('scr_date_order')];
  $this->db->where('group_id',$g)->where('active',1)->where('valid_from <=',$to)->where('valid_to >=',$from);if($id)$this->db->where('id !=',$id);if($this->db->get(db_prefix().'scr_discount_rules')->row())return ['ok'=>false,'message'=>_l('scr_overlap')];
  $row=['group_id'=>$g,'base_percent'=>$base,'max_percent'=>$max,'valid_from'=>$from,'valid_to'=>$to,'active'=>(int)($d['active']??1),'updated_at'=>date('Y-m-d H:i:s')];if($id)$this->db->where('id',$id)->update(db_prefix().'scr_discount_rules',$row);else{$row['created_at']=date('Y-m-d H:i:s');$this->db->insert(db_prefix().'scr_discount_rules',$row);$id=$this->db->insert_id();}return ['ok'=>true,'id'=>$id];}

 public function staff_list(){return $this->db->select('staffid,firstname,lastname,active')->order_by('firstname','ASC')->order_by('lastname','ASC')->get(db_prefix().'staff')->result_array();}
 public function hierarchy(){if(!$this->db->field_exists('participation_percent',db_prefix().'scr_sales_hierarchy'))$this->db->query('ALTER TABLE `'.db_prefix().'scr_sales_hierarchy'.'` ADD `participation_percent` DECIMAL(7,3) NOT NULL DEFAULT 0');return $this->db->select("h.*,CONCAT(m.firstname,' ',m.lastname) manager_name,CONCAT(s.firstname,' ',s.lastname) seller_name")->from(db_prefix().'scr_sales_hierarchy h')->join(db_prefix().'staff m','m.staffid=h.manager_staff_id','left')->join(db_prefix().'staff s','s.staffid=h.seller_staff_id','left')->order_by('h.id','DESC')->get()->result_array();}
 public function save_hierarchy($d){$id=(int)($d['id']??0);$m=(int)($d['manager_staff_id']??0);$s=(int)($d['seller_staff_id']??0);if(!$m||!$s)return ['ok'=>false,'message'=>_l('scr_hierarchy_required')];if($m===$s)return ['ok'=>false,'message'=>_l('scr_hierarchy_same_staff')];$this->db->where('manager_staff_id',$m)->where('seller_staff_id',$s);if($id)$this->db->where('id !=',$id);if($this->db->get(db_prefix().'scr_sales_hierarchy')->row())return ['ok'=>false,'message'=>_l('scr_hierarchy_duplicate')];$row=['manager_staff_id'=>$m,'seller_staff_id'=>$s,'active'=>1,'updated_at'=>date('Y-m-d H:i:s')];if($id){$this->db->where('id',$id)->update(db_prefix().'scr_sales_hierarchy',$row);}else{$row['created_at']=date('Y-m-d H:i:s');$this->db->insert(db_prefix().'scr_sales_hierarchy',$row);$id=$this->db->insert_id();}return ['ok'=>true,'id'=>$id];}
 public function delete_hierarchy($id){return (bool)$this->db->where('id',(int)$id)->delete(db_prefix().'scr_sales_hierarchy');}
 private function ensure_manager_discount_authorizations(){
  $t=db_prefix().'scr_manager_discount_authorizations';
  if(!$this->db->table_exists($t)){
   $this->db->query("CREATE TABLE `{$t}` (`id` int NOT NULL AUTO_INCREMENT,`manager_staff_id` int NOT NULL,`staff_id` int NOT NULL,`can_use_max_discount` tinyint NOT NULL DEFAULT 0,`created_at` datetime NULL,`updated_at` datetime NULL,PRIMARY KEY (`id`),UNIQUE KEY `manager_staff` (`manager_staff_id`,`staff_id`),KEY `staff_id` (`staff_id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
  }
  return $t;
 }
 public function discount_access($managerId=null){
  $managerId=$managerId===null?(int)get_staff_user_id():(int)$managerId;
  $a=$this->ensure_manager_discount_authorizations();
  $h=db_prefix().'scr_sales_hierarchy';
  if(!$this->db->table_exists($h))return [];
  return $this->db->select("h.id hierarchy_id,h.manager_staff_id,s.staffid,s.firstname,s.lastname,s.active,CONCAT(m.firstname,' ',m.lastname) manager_name,COALESCE(a.can_use_max_discount,0) can_edit_line_discount",false)
   ->from($h.' h')->join(db_prefix().'staff s','s.staffid=h.seller_staff_id','inner')->join(db_prefix().'staff m','m.staffid=h.manager_staff_id','left')
   ->join($a.' a','a.manager_staff_id=h.manager_staff_id AND a.staff_id=h.seller_staff_id','left')
   ->where('h.active',1)->where('h.manager_staff_id',$managerId)->order_by('s.firstname','ASC')->order_by('s.lastname','ASC')->get()->result_array();
 }
 public function managers_with_subordinates(){
  $h=db_prefix().'scr_sales_hierarchy';if(!$this->db->table_exists($h))return [];
  return $this->db->select("h.manager_staff_id,CONCAT(m.firstname,' ',m.lastname) manager_name",false)->from($h.' h')->join(db_prefix().'staff m','m.staffid=h.manager_staff_id','left')->where('h.active',1)->group_by(['h.manager_staff_id','m.firstname','m.lastname'])->order_by('m.firstname','ASC')->order_by('m.lastname','ASC')->get()->result_array();
 }
 public function manager_owns_staff($managerId,$staffId){$h=db_prefix().'scr_sales_hierarchy';if(!$this->db->table_exists($h))return false;return (bool)$this->db->where('manager_staff_id',(int)$managerId)->where('seller_staff_id',(int)$staffId)->where('active',1)->get($h)->row();}
 public function save_discount_access($d,$managerId){
  $managerId=(int)$managerId;$allowed=array_map('intval',(array)($d['staff_ids']??[]));$now=date('Y-m-d H:i:s');$subs=$this->discount_access($managerId);$t=$this->ensure_manager_discount_authorizations();
  foreach($subs as $r){$sid=(int)$r['staffid'];$allow=in_array($sid,$allowed,true)?1:0;$ex=$this->db->where('manager_staff_id',$managerId)->where('staff_id',$sid)->get($t)->row();$row=['can_use_max_discount'=>$allow,'updated_at'=>$now];if($ex)$this->db->where('id',$ex->id)->update($t,$row);else{$row+=['manager_staff_id'=>$managerId,'staff_id'=>$sid,'created_at'=>$now];$this->db->insert($t,$row);}}
  return true;
 }
 public function staff_has_elevated_discount($staffId){
  $t=$this->ensure_manager_discount_authorizations();$h=db_prefix().'scr_sales_hierarchy';if(!$this->db->table_exists($h))return false;
  return (bool)$this->db->select('a.id')->from($t.' a')->join($h.' h','h.manager_staff_id=a.manager_staff_id AND h.seller_staff_id=a.staff_id','inner')->where('a.staff_id',(int)$staffId)->where('a.can_use_max_discount',1)->where('h.active',1)->limit(1)->get()->row();
 }
 public function ensure_discount_overrides(){
  $t=db_prefix().'scr_discount_overrides';
  if($this->db->table_exists($t))return true;
  $sql="CREATE TABLE IF NOT EXISTS `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`rule_id` INT NOT NULL,`kind` VARCHAR(16) NOT NULL,`percent` DECIMAL(8,2) NOT NULL,`valid_from` DATE NULL,`valid_to` DATE NULL,`active` TINYINT NOT NULL DEFAULT 1,`updated_by` INT NOT NULL,`updated_at` DATETIME NOT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `rule_kind` (`rule_id`,`kind`,`active`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  return (bool)$this->db->query($sql) && $this->db->table_exists($t);
 }
 public function discount_overrides($ruleId){
  if(!$this->ensure_discount_overrides())return [];
  return $this->db->where('rule_id',(int)$ruleId)->order_by('id','DESC')->get(db_prefix().'scr_discount_overrides')->result_array();
 }
 public function save_discount_override($data,$staffId){
  if(!is_admin())return ['ok'=>false,'message'=>'فقط مدیر سامانه مجاز به تنظیم تخفیف آزاد یا جشنواره‌ای است.'];
  if(!$this->ensure_discount_overrides())return ['ok'=>false,'message'=>'جدول تخفیفات ایجاد نشد.'];
  $id=(int)($data['id']??0);$ruleId=(int)($data['rule_id']??0);$kind=(string)($data['kind']??'');
  if(!in_array($kind,['festival','free','ceiling'],true))return ['ok'=>false,'message'=>'نوع تخفیف نامعتبر است.'];
  $rule=$this->db->where('id',$ruleId)->get(db_prefix().'scr_discount_rules')->row_array();
  if(!$rule)return ['ok'=>false,'message'=>'قانون تخفیف یافت نشد.'];
  $percent=$this->num($data['percent']??null);
  if($percent===null||$percent<0||$percent>100)return ['ok'=>false,'message'=>'درصد باید بین صفر و صد باشد.'];
  $from=null;$to=null;
  if($kind==='festival'){
   $from=$this->normalize_date($data['valid_from']??'');$to=$this->normalize_date($data['valid_to']??'');
   if(!$from||!$to||$from>$to||$from<$rule['valid_from']||$to>$rule['valid_to'])return ['ok'=>false,'message'=>'بازه جشنواره باید معتبر و در محدوده تاریخ قانون عادی باشد.'];
   $q=$this->db->where('rule_id',$ruleId)->where('kind','festival')->where('active',1)->where('valid_from <=',$to)->where('valid_to >=',$from);
   if($id)$q->where('id !=',$id);
   if($q->get(db_prefix().'scr_discount_overrides')->row())return ['ok'=>false,'message'=>'جشنواره هم‌پوشان برای این قانون وجود دارد.'];
  }elseif($kind==='free'){
   $q=$this->db->where('rule_id',$ruleId)->where('kind','free');if($id)$q->where('id !=',$id);
   if($q->get(db_prefix().'scr_discount_overrides')->row())return ['ok'=>false,'message'=>'مجوز آزاد این قانون قبلاً ایجاد شده؛ همان را ویرایش کنید.'];
  }
  else{
   $percent=(float)$rule['max_percent'];
   $q=$this->db->where('rule_id',$ruleId)->where('kind','ceiling');if($id)$q->where('id !=',$id);
   if($q->get(db_prefix().'scr_discount_overrides')->row())return ['ok'=>false,'message'=>'مجوز سقف این گروه قبلاً ثبت شده است؛ همان را ویرایش کنید.'];
  }
  $now=date('Y-m-d H:i:s');$row=['rule_id'=>$ruleId,'kind'=>$kind,'percent'=>$percent,'valid_from'=>$from,'valid_to'=>$to,'active'=>!empty($data['active'])?1:0,'updated_by'=>(int)$staffId,'updated_at'=>$now];
  if($id){$old=$this->db->where('id',$id)->where('rule_id',$ruleId)->where('kind',$kind)->get(db_prefix().'scr_discount_overrides')->row();if(!$old)return ['ok'=>false,'message'=>'رکورد معتبر نیست.'];$ok=$this->db->where('id',$id)->update(db_prefix().'scr_discount_overrides',$row);}
  else{$row['created_at']=$now;$ok=$this->db->insert(db_prefix().'scr_discount_overrides',$row);}
  return ['ok'=>(bool)$ok,'message'=>$ok?'ذخیره شد.':'ذخیره انجام نشد.'];
 }
 public function effective_discount_rule($r,$date){
  if(!$r||!$this->ensure_discount_overrides())return $r;
  $t=db_prefix().'scr_discount_overrides';
  $free=$this->db->where('rule_id',(int)$r['id'])->where('kind','free')->where('active',1)->order_by('id','DESC')->limit(1)->get($t)->row_array();
  if($free){$r['base_percent']=$free['percent'];$r['max_percent']=$free['percent'];$r['override_kind']='free';return $r;}
  $festival=$this->db->where('rule_id',(int)$r['id'])->where('kind','festival')->where('active',1)->where('valid_from <=',$date)->where('valid_to >=',$date)->order_by('id','DESC')->limit(1)->get($t)->row_array();
  if($festival){$r['base_percent']=$festival['percent'];$r['max_percent']=$festival['percent'];$r['override_kind']='festival';}
  return $r;
 }
 public function discount_rule_for_item($itemId,$date=null){
  $date=$date?$this->normalize_date($date):date('Y-m-d'); if(!$date)$date=date('Y-m-d');$item=$this->db->select('group_id')->where('id',(int)$itemId)->get(db_prefix().'items')->row();if(!$item||!(int)$item->group_id)return null;
  $r=$this->db->where('group_id',(int)$item->group_id)->where('active',1)->where('valid_from <=',$date)->where('valid_to >=',$date)->order_by('id','DESC')->get(db_prefix().'scr_discount_rules')->row_array();return $this->effective_discount_rule($r,$date);
 }
 public function discount_policy($staffId,$itemId,$date=null){$r=$this->discount_rule_for_item($itemId,$date);if(!$r)return ['found'=>false];$e=$this->staff_has_elevated_discount((int)$staffId);if(!$e&&empty($r['override_kind'])&&$this->ensure_discount_overrides())$e=(bool)$this->db->where('rule_id',(int)$r['id'])->where('kind','ceiling')->where('active',1)->limit(1)->get(db_prefix().'scr_discount_overrides')->row();return ['found'=>true,'base_percent'=>(float)$r['base_percent'],'max_percent'=>(float)$r['max_percent'],'elevated'=>$e,'allowed_percent'=>$e?(float)$r['max_percent']:(float)$r['base_percent']];}


 public function commissions(){scr_ensure_phase3_schema();return $this->db->select('c.*,g.name group_name,st.name sale_type_name')->from(db_prefix().'scr_commission_rules c')->join(db_prefix().'items_groups g','g.id=c.group_id','left')->join(db_prefix().'scr_sale_types st','st.legacy_id=c.sale_type_id OR st.id=c.sale_type_id','left')->order_by('c.id','DESC')->get()->result_array();}
 public function save_commission($d){
  scr_ensure_phase3_schema();$id=(int)($d['id']??0);$g=(int)($d['group_id']??0);$st=(int)($d['sale_type_id']??0);
  $sp=trim((string)($d['seller_percent']??''));$mp=trim((string)($d['marketer_percent']??''));
  $digits=['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9'];$sp=strtr($sp,$digits);$mp=strtr($mp,$digits);
  $from=(string)($d['valid_from']??'1000-01-01');$to=(string)($d['valid_to']??'9999-12-31');
  if(!$g||!$st||!is_numeric($sp)||!is_numeric($mp)||(float)$sp<0||(float)$sp>100||(float)$mp<0||(float)$mp>100)return ['ok'=>false,'message'=>'گروه کالا، نوع فروش یا درصد پورسانت نامعتبر است'];
  foreach([$from,$to] as $date){if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))return ['ok'=>false,'message'=>'تاریخ میلادی معتبر نیست'];}
  if($from>$to)return ['ok'=>false,'message'=>'بازه زمانی نامعتبر است'];
  $q=$this->db->where('group_id',$g)->where('sale_type_id',$st)->where('valid_from <=',$to)->where('valid_to >=',$from);
  if($id)$q->where('id !=',$id);
  $duplicate=$q->get(db_prefix().'scr_commission_rules')->row_array();
  if($duplicate)return ['ok'=>false,'message'=>'سیاست هم‌پوشان برای همین گروه کالا و نوع فروش وجود دارد؛ ابتدا بازه قبلی را اصلاح کنید'];
  $row=['group_id'=>$g,'sale_type_id'=>$st,'seller_percent'=>(float)$sp,'marketer_percent'=>(float)$mp,'valid_from'=>$from,'valid_to'=>$to,'active'=>(int)($d['active']??1),'updated_at'=>date('Y-m-d H:i:s')];
  if($id){$ok=$this->db->where('id',$id)->update(db_prefix().'scr_commission_rules',$row);return ['ok'=>(bool)$ok,'id'=>$id];}
  $row['created_at']=date('Y-m-d H:i:s');$row['legacy_id']=null;$ok=$this->db->insert(db_prefix().'scr_commission_rules',$row);
  return ['ok'=>(bool)$ok,'id'=>(int)$this->db->insert_id()];
 }
 public function delete_commission($id){return (bool)$this->db->where('id',(int)$id)->delete(db_prefix().'scr_commission_rules');}

 public function rankings(){scr_ensure_phase4_schema();return $this->db->order_by('id','DESC')->get(db_prefix().'scr_ranking_levels')->result_array();}
 public function ranking($id){scr_ensure_phase4_schema();return $this->db->where('id',(int)$id)->get(db_prefix().'scr_ranking_levels')->row_array();}
 public function save_ranking($d){scr_ensure_phase4_schema();$id=(int)($d['id']??0);$name=trim((string)($d['name']??''));$from=$this->normalize_date($d['valid_from']??'');$to=$this->normalize_date($d['valid_to']??'');if($name===''||!$from||!$to)return ['ok'=>false,'message'=>_l('scr_ranking_required')];if($from>$to)return ['ok'=>false,'message'=>_l('scr_date_order')];$row=['name'=>$name,'valid_from'=>$from,'valid_to'=>$to,'calculated'=>0,'updated_at'=>date('Y-m-d H:i:s')];if($id){$this->db->where('id',$id)->update(db_prefix().'scr_ranking_levels',$row);}else{$row['created_at']=date('Y-m-d H:i:s');$this->db->insert(db_prefix().'scr_ranking_levels',$row);$id=$this->db->insert_id();}return ['ok'=>true,'id'=>$id];}
 public function clone_ranking($sourceId,$name,$fromInput,$toInput){
  scr_ensure_phase4_schema();
  $source=$this->ranking((int)$sourceId);
  if(!$source)return ['ok'=>false,'message'=>'دوره مبدا پیدا نشد.'];
  $name=trim((string)$name);$from=$this->normalize_date($fromInput);$to=$this->normalize_date($toInput);
  if($name===''||!$from||!$to||$from>$to)return ['ok'=>false,'message'=>'عنوان و بازه تاریخ معتبر الزامی است.'];
  $steps=$this->ranking_steps((int)$sourceId);
  $now=date('Y-m-d H:i:s');$this->db->trans_begin();
  $this->db->insert(db_prefix().'scr_ranking_levels',['name'=>$name,'valid_from'=>$from,'valid_to'=>$to,'calculated'=>0,'commission_paid'=>0,'created_at'=>$now,'updated_at'=>$now]);
  $newId=(int)$this->db->insert_id();
  if(!$newId){$this->db->trans_rollback();return ['ok'=>false,'message'=>'ثبت دوره جدید انجام نشد.'];}
  foreach($steps as $step){
   $row=['level_id'=>$newId,'name'=>$step['name'],'sale_type_id'=>(int)$step['sale_type_id'],'ranking_role'=>$step['ranking_role'],'from_amount'=>$step['from_amount'],'to_amount'=>$step['to_amount'],'adjustment_type'=>$step['adjustment_type'],'adjustment_value'=>$step['adjustment_value'],'percent'=>$step['percent'],'created_at'=>$now,'updated_at'=>$now];
   $this->db->insert(db_prefix().'scr_ranking_steps',$row);
  }
  if($this->db->trans_status()===FALSE){$this->db->trans_rollback();return ['ok'=>false,'message'=>'کپی دوره و گام‌ها انجام نشد.'];}
  $this->db->trans_commit();return ['ok'=>true,'id'=>$newId,'count'=>count($steps)];
 }
 public function delete_ranking($id){$id=(int)$id;$this->db->where('level_id',$id)->delete(db_prefix().'scr_ranking_invoice_ledger');$this->db->where('level_id',$id)->delete(db_prefix().'scr_ranking_results');$this->db->where('level_id',$id)->delete(db_prefix().'scr_ranking_steps');return (bool)$this->db->where('id',$id)->delete(db_prefix().'scr_ranking_levels');}
 public function ranking_steps($level){scr_ensure_phase4_schema();return $this->db->select('s.*,st.name sale_type_name')->from(db_prefix().'scr_ranking_steps s')->join(db_prefix().'scr_sale_types st','st.legacy_id=s.sale_type_id OR st.id=s.sale_type_id','left')->where('s.level_id',(int)$level)->order_by('s.ranking_role','ASC')->order_by('s.sale_type_id','ASC')->order_by('s.from_amount','ASC')->get()->result_array();}
 public function scr_ensure_online_staff_stages(){
  $t=db_prefix().'scr_online_staff_stages';
  if($this->db->table_exists($t))return true;
  return (bool)$this->db->query("CREATE TABLE IF NOT EXISTS `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`staff_id` INT NOT NULL,`category_code` VARCHAR(80) NOT NULL,`effective_date` DATE NOT NULL,`changed_by` INT NOT NULL,`note` VARCHAR(500) NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `staff_date` (`staff_id`,`effective_date`,`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 }
 public function scr_online_staff_stages(){
  if(!$this->db->table_exists(db_prefix().'scr_online_staff_stages'))return [];
  $rows=$this->db->order_by('effective_date','ASC')->order_by('id','ASC')->get(db_prefix().'scr_online_staff_stages')->result_array();$out=[];
  foreach($rows as $r){if($r['effective_date']<=date('Y-m-d'))$out[(int)$r['staff_id']]=$r;}
  return $out;
 }
 public function scr_assign_online_staff_stage($d,$manager){
  if(!$this->scr_ensure_online_staff_stages())return ['ok'=>false,'message'=>'جدول وضعیت کارشناسان ایجاد نشد.'];
  $staff=(int)($d['staff_id']??0);$code=trim((string)($d['category_code']??''));$date=trim((string)($d['effective_date']??''));
  $dt=DateTime::createFromFormat('!Y-m-d',$date);
  if(!$staff||!$this->db->where('staffid',$staff)->count_all_results(db_prefix().'staff')||!$dt||$dt->format('Y-m-d')!==$date)return ['ok'=>false,'message'=>'کارشناس یا تاریخ معتبر نیست.'];
  if($date>date('Y-m-d'))return ['ok'=>false,'message'=>'تاریخ مؤثر آینده فعلاً پشتیبانی نمی‌شود.'];
  $codes=array_column($this->marketing_categories(),'code');$codes[]='normal';
  if(!in_array($code,$codes,true))return ['ok'=>false,'message'=>'نوع بازاریابی معتبر نیست.'];
  $ok=$this->db->insert(db_prefix().'scr_online_staff_stages',['staff_id'=>$staff,'category_code'=>$code,'effective_date'=>$date,'changed_by'=>(int)$manager,'note'=>mb_substr(trim((string)($d['note']??'')),0,500),'created_at'=>date('Y-m-d H:i:s')]);
  return ['ok'=>(bool)$ok,'message'=>$ok?'وضعیت کارشناس ثبت شد.':'ثبت وضعیت انجام نشد.'];
 }
 public function scr_delete_call_center_compensation($id){
  $t=db_prefix().'scr_call_center_compensation';
  if(!$this->db->table_exists($t)||$id<=0)return false;
  return (bool)$this->db->where('id',(int)$id)->delete($t);
 }
 public function scr_online_status_table(){
  $t=db_prefix().'scr_online_promotion_decisions';if($this->db->table_exists($t))return true;
  return (bool)$this->db->query("CREATE TABLE IF NOT EXISTS `{$t}` (`id` INT AUTO_INCREMENT PRIMARY KEY,`staff_id` INT NOT NULL,`stage` VARCHAR(32) NOT NULL,`decision` VARCHAR(16) NOT NULL,`reason` TEXT NULL,`manager_id` INT NOT NULL,`created_at` DATETIME NOT NULL,KEY `staff_stage` (`staff_id`,`stage`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 }

 public function scr_attribution_table(){
  $t=db_prefix().'scr_invoice_staff_attributions';
  if(!$this->db->table_exists($t))$this->db->query('CREATE TABLE `'.$t.'` (`invoice_id` INT NOT NULL PRIMARY KEY,`staff_id` INT NOT NULL,`verified_by` INT NOT NULL,`verified_at` DATETIME NOT NULL,KEY `staff_id` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  return $this->db->table_exists($t);
 }
 public function scr_online_status(){
  // Promotion eligibility must be derived exclusively from active promotion rules.
  $rules=[];
  foreach($this->scr_promotion_rules() as $rule){
   if(!empty($rule['active']) && !isset($rules[$rule['from_category_code']]))$rules[$rule['from_category_code']]=$rule;
  }
  if(!$rules)return [];
  $assignments=$this->scr_online_staff_stages();
  $out=[];$today=date('Y-m-d');$invoices=db_prefix().'invoices';
  if(isset($rules['call']) && $rules['call']['to_category_code']!=='call'){
   $rule=$rules['call'];
   foreach($this->scr_call_center_compensations() as $c){
    if(!(int)$c['active'])continue;
    if(isset($assignments[(int)$c['staff_id']]) && $assignments[(int)$c['staff_id']]['category_code']!=='call')continue;
    $start=substr((string)$c['period_start'],0,10);$days=max(1,(int)$rule['period_days']);$end=date('Y-m-d',strtotime($start.' +'.($days-1).' days'));
    $elapsed=max(0,min($days,(int)floor((strtotime($today)-strtotime($start))/86400)+1));$count=0;
    if($this->db->table_exists($invoices)){
     // Count invoices attributed to the actual salesperson, not the creator (addedfrom).
     // The assessment period begins at the compensation record's period_start;
     // the promotion rule supplies only the assessment duration and target.
     if($this->db->field_exists('sale_agent',$invoices) && $this->db->field_exists('date',$invoices)){
      // Imported invoices may have a corrupted future invoice date (e.g. 2648-01-02).
      // For such records use their actual creation date; normal invoice dates stay authoritative.
      $dateExpr = "CASE WHEN i.date IS NULL OR i.date < '2000-01-01' OR i.date > DATE_ADD(CURDATE(), INTERVAL 1 YEAR) THEN DATE(i.datecreated) ELSE i.date END";
      $this->db->select('COUNT(DISTINCT i.id) AS cnt',false)->from($invoices.' i')
       ->where('i.sale_agent',(int)$c['staff_id'])
       ->where($dateExpr.' >= '.$this->db->escape($start),null,false)
       ->where($dateExpr.' <= '.$this->db->escape($end),null,false);
      if($this->db->field_exists('status',$invoices))$this->db->where('i.status !=',5);
      $found=$this->db->get()->row_array();$count=(int)($found['cnt']??0);
      $map=db_prefix().'scr_invoice_staff_attributions';
      if($this->db->table_exists($map)){
       $this->db->select('COUNT(DISTINCT i.id) AS cnt',false)->from($map.' a')->join($invoices.' i','i.id=a.invoice_id','inner')
        ->where('a.staff_id',(int)$c['staff_id'])->where('i.sale_agent',0)
        ->where($dateExpr.' >= '.$this->db->escape($start),null,false)
        ->where($dateExpr.' <= '.$this->db->escape($end),null,false);
       if($this->db->field_exists('status',$invoices))$this->db->where('i.status !=',5);
       $mapped=$this->db->get()->row_array();$count+=(int)($mapped['cnt']??0);
      }
     }
    }
    $target=(int)$rule['target_count'];$eligible=$rule['criterion']==='invoice_count'&&$count>=$target;
    $out[]=['staff_id'=>(int)$c['staff_id'],'name'=>trim($c['firstname'].' '.$c['lastname']),'stage'=>'call','elapsed'=>$elapsed,'days'=>$days,'count'=>$count,'target'=>$target,'salary'=>(float)$c['fixed_salary'],'bonus'=>$eligible?(float)$c['target_bonus']:0,'eligible'=>$eligible,'ranks'=>[]];
   }
  }
  if(isset($rules['call_online']) && $rules['call_online']['criterion']==='rank_count' && $this->db->table_exists(db_prefix().'scr_ranking_levels')&&$this->db->table_exists(db_prefix().'scr_ranking_results')){
   $rule=$rules['call_online'];$required=max(1,(int)$rule['target_count']);
   $levels=$this->db->where('calculated',1)->where('valid_to <=',$today)->order_by('valid_to','DESC')->limit(max(30,$required))->get(db_prefix().'scr_ranking_levels')->result_array();
   foreach($this->db->select('staffid,firstname,lastname')->where('active',1)->get(db_prefix().'staff')->result_array() as $p){
    $name=trim($p['firstname'].' '.$p['lastname']);$assigned=$assignments[(int)$p['staffid']]??null;if($assigned){if($assigned['category_code']!=='call_online')continue;}elseif(!preg_match('/مرکز\s*تماس\s*آنلاین/u',$name)||preg_match('/مشتری/u',$name))continue;
    $ranks=[];foreach($levels as $l){
     $row=$this->db->select('s.name step_name')->from(db_prefix().'scr_ranking_results r')->join(db_prefix().'scr_ranking_steps s','s.id=r.step_id','left')->where('r.level_id',(int)$l['id'])->where('r.staff_id',(int)$p['staffid'])->where('r.ranking_role','seller')->get()->row_array();
     if(!$row)continue;$n=(string)$row['step_name'];$rank=preg_match('/(?:^|\s)(?:گام\s*)?A(?:\s|$)/iu',$n)?'A':(preg_match('/(?:^|\s)(?:گام\s*)?B(?:\s|$)/iu',$n)?'B':(preg_match('/(?:^|\s)(?:گام\s*)?C(?:\s|$)/iu',$n)?'C':'—'));
     $ranks[]=['name'=>$l['name'],'rank'=>$rank];if(count($ranks)>=$required)break;
    }
    $matching=0;foreach($ranks as $rr){if($rr['rank']===$rule['target_rank'])$matching++;elseif(!empty($rule['consecutive']))break;}
    $eligible=$matching>=$required;
    $out[]=['staff_id'=>(int)$p['staffid'],'name'=>$name,'stage'=>'call_online','eligible'=>$eligible,'ranks'=>$ranks,'target_rank'=>$rule['target_rank'],'target_count'=>$required,'consecutive'=>!empty($rule['consecutive'])];
   }
  }
  if($this->db->table_exists(db_prefix().'scr_online_promotion_decisions'))foreach($out as &$r){$r['decision']=$this->db->where('staff_id',$r['staff_id'])->where('stage',$r['stage'])->order_by('id','DESC')->get(db_prefix().'scr_online_promotion_decisions')->row_array();}unset($r);
  return $out;
 }
 public function scr_online_status_decide($d,$manager){
  if(!$this->scr_online_status_table())return ['ok'=>false,'message'=>'جدول تصمیم مدیر ایجاد نشد.'];
  $staff=(int)($d['staff_id']??0);$stage=(string)($d['stage']??'');$decision=(string)($d['decision']??'');$reason=trim((string)($d['reason']??''));
  if(!$staff||!in_array($stage,['call','call_online'],true)||!in_array($decision,['approve','reject'],true))return ['ok'=>false,'message'=>'اطلاعات تصمیم نامعتبر است.'];
  if($decision==='reject'&&$reason==='')return ['ok'=>false,'message'=>'دلیل عدم تأیید الزامی است.'];
  $matched=null;foreach($this->scr_online_status() as $r)if($r['staff_id']===$staff&&$r['stage']===$stage){$matched=$r;break;}
  if(!$matched)return ['ok'=>false,'message'=>'کارشناس یافت نشد.'];
  if($decision==='approve'&&!$matched['eligible'])return ['ok'=>false,'message'=>'شرط ارتقا هنوز محقق نشده است.'];
  $this->db->trans_begin();
  $ok=$this->db->insert(db_prefix().'scr_online_promotion_decisions',['staff_id'=>$staff,'stage'=>$stage,'decision'=>$decision,'reason'=>mb_substr($reason,0,2000),'manager_id'=>$manager,'created_at'=>date('Y-m-d H:i:s')]);
  if($ok&&$decision==='approve'){
   $rule=null;foreach($this->scr_promotion_rules() as $r){if(!empty($r['active'])&&$r['from_category_code']===$stage){$rule=$r;break;}}
   if(!$rule){$this->db->trans_rollback();return ['ok'=>false,'message'=>'قاعده فعال ارتقا پیدا نشد.'];}
   $r=$this->scr_assign_online_staff_stage(['staff_id'=>$staff,'category_code'=>$rule['to_category_code'],'effective_date'=>date('Y-m-d'),'note'=>'ارتقا با تأیید مدیر از مرحله '.$stage],$manager);
   if(!$r['ok']){$this->db->trans_rollback();return $r;}
  }
  if(!$ok||$this->db->trans_status()===FALSE){$this->db->trans_rollback();return ['ok'=>false,'message'=>'ذخیره تصمیم انجام نشد.'];}
  $this->db->trans_commit();
  return ['ok'=>true,'message'=>$decision==='approve'?'ارتقا تأیید و مرحله کارشناس به‌روزرسانی شد.':'عدم تأیید مدیر ثبت شد.'];
 }
 public function scr_ensure_call_center_compensation_table(){
  $t=db_prefix().'scr_call_center_compensation';
  if($this->db->table_exists($t))return true;
  return (bool)$this->db->query("CREATE TABLE IF NOT EXISTS `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`staff_id` INT NOT NULL,`period_start` DATE NOT NULL,`period_days` INT NOT NULL DEFAULT 28,`target_invoices` INT NOT NULL DEFAULT 10,`fixed_salary` DECIMAL(18,2) NOT NULL DEFAULT 0,`target_bonus` DECIMAL(18,2) NOT NULL DEFAULT 0,`active` TINYINT NOT NULL DEFAULT 1,`created_at` DATETIME NULL,PRIMARY KEY (`id`),KEY `staff_id` (`staff_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 }
 public function scr_call_center_compensations(){
  if(!$this->db->table_exists(db_prefix().'scr_call_center_compensation'))return [];
  return $this->db->select('c.*,s.firstname,s.lastname')->from(db_prefix().'scr_call_center_compensation c')->join(db_prefix().'staff s','s.staffid=c.staff_id','left')->order_by('c.id','DESC')->get()->result_array();
 }
 public function scr_save_call_center_compensation($d){
  if(!$this->scr_ensure_call_center_compensation_table())return ['ok'=>false,'message'=>'جدول تنظیمات پرداخت ایجاد نشد.'];
  $staff=(int)($d['staff_id']??0);$start=(string)($d['period_start']??'');
  $salary=(string)($d['fixed_salary']??'');$bonus=(string)($d['target_bonus']??'');
  $dt=DateTime::createFromFormat('!Y-m-d',$start);
  if(!$staff||!$this->db->where('staffid',$staff)->count_all_results(db_prefix().'staff')||!$dt||$dt->format('Y-m-d')!==$start||!is_numeric($salary)||!is_numeric($bonus)||(float)$salary<0||(float)$bonus<0)return ['ok'=>false,'message'=>'اطلاعات حقوق و پاداش معتبر نیست.'];
  $row=['staff_id'=>$staff,'period_start'=>$start,'fixed_salary'=>$salary,'target_bonus'=>$bonus,'active'=>!empty($d['active'])?1:0];
  $id=(int)($d['id']??0);$table=db_prefix().'scr_call_center_compensation';
  if($id){if(!$this->db->where('id',$id)->count_all_results($table))return ['ok'=>false,'message'=>'رکورد یافت نشد.'];$ok=$this->db->where('id',$id)->update($table,$row);}
  else{$row['period_days']=28;$row['target_invoices']=10;$row['created_at']=date('Y-m-d H:i:s');$ok=$this->db->insert($table,$row);}
  return ['ok'=>(bool)$ok,'message'=>$ok?'':'ذخیره انجام نشد.'];
 }
 public function scr_ensure_promotion_rules_table(){
  $t=db_prefix().'scr_promotion_rules';
  if($this->db->table_exists($t))return true;
  return (bool)$this->db->query("CREATE TABLE IF NOT EXISTS `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`from_category_code` VARCHAR(32) NOT NULL,`to_category_code` VARCHAR(32) NOT NULL,`criterion` VARCHAR(20) NOT NULL DEFAULT 'invoice_count',`target_count` INT NOT NULL DEFAULT 10,`period_days` INT NOT NULL DEFAULT 28,`target_rank` VARCHAR(20) NULL,`consecutive` TINYINT NOT NULL DEFAULT 0,`requires_manager_approval` TINYINT NOT NULL DEFAULT 1,`active` TINYINT NOT NULL DEFAULT 1,`created_at` DATETIME NULL,PRIMARY KEY (`id`)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
 }
 public function scr_promotion_rules(){
  if(!$this->db->table_exists(db_prefix().'scr_promotion_rules'))return [];
  return $this->db->order_by('id','ASC')->get(db_prefix().'scr_promotion_rules')->result_array();
 }
 public function scr_save_promotion_rule($d){
  if(!$this->scr_ensure_promotion_rules_table())return ['ok'=>false,'message'=>'جدول قواعد ارتقا ایجاد نشد.'];
  $from=trim((string)($d['from_category_code']??''));$to=trim((string)($d['to_category_code']??''));
  $criterion=(string)($d['criterion']??'invoice_count');$count=(int)($d['target_count']??0);$days=(int)($d['period_days']??0);
  if($from===''||$to===''||$from===$to||!in_array($criterion,['invoice_count','rank_count'],true)||$count<1||$count>100000||$days<1||$days>3650)return ['ok'=>false,'message'=>'مقادیر قاعده ارتقا معتبر نیست.'];
  $codes=array_column($this->marketing_categories(),'code');
  if(!in_array($from,$codes,true)||!in_array($to,$codes,true))return ['ok'=>false,'message'=>'نوع بازاریابی انتخاب‌شده وجود ندارد.'];
  $data=['from_category_code'=>$from,'to_category_code'=>$to,'criterion'=>$criterion,'target_count'=>$count,'period_days'=>$days,'target_rank'=>$criterion==='rank_count'?trim((string)($d['target_rank']??'A')):null,'consecutive'=>!empty($d['consecutive'])?1:0,'requires_manager_approval'=>!empty($d['requires_manager_approval'])?1:0,'active'=>!empty($d['active'])?1:0];
  if($criterion==='rank_count'&&$data['target_rank']==='')return ['ok'=>false,'message'=>'رتبه هدف الزامی است.'];
  $id=(int)($d['id']??0);$t=db_prefix().'scr_promotion_rules';
  if($id){if(!$this->db->where('id',$id)->get($t)->row())return ['ok'=>false,'message'=>'قاعده یافت نشد.'];$ok=$this->db->where('id',$id)->update($t,$data);}
  else{$data['created_at']=date('Y-m-d H:i:s');$ok=$this->db->insert($t,$data);}
  return ['ok'=>(bool)$ok,'message'=>$ok?'':'ذخیره قاعده انجام نشد.'];
 }
 public function scr_delete_promotion_rule($id){
  if(!$this->db->table_exists(db_prefix().'scr_promotion_rules'))return false;
  return (bool)$this->db->where('id',(int)$id)->delete(db_prefix().'scr_promotion_rules');
 }
 public function marketing_categories(){
  $table=db_prefix().'scr_marketing_categories';
  if(!$this->db->table_exists($table))return [
   ['id'=>0,'code'=>'call','name'=>'مرکز تماس','active'=>1],
   ['id'=>0,'code'=>'call_online','name'=>'مرکز تماس آنلاین','active'=>1],
   ['id'=>0,'code'=>'call_customer','name'=>'مرکز تماس آنلاین و مشتری','active'=>1]
  ];
  return $this->db->order_by('id','ASC')->get($table)->result_array();
 }
 public function marketing_category_labels(){ $labels=['normal'=>'عادی'];foreach($this->marketing_categories() as $r)$labels[$r['code']]=$r['name'];return $labels;}
 public function scr_create_marketing_category_table(){
  $table=db_prefix().'scr_marketing_categories';
  if($this->db->table_exists($table))return true;
  $sql="CREATE TABLE IF NOT EXISTS `{$table}` (`id` INT NOT NULL AUTO_INCREMENT,`code` VARCHAR(32) NOT NULL,`name` VARCHAR(150) NOT NULL,`active` TINYINT NOT NULL DEFAULT 1,`created_at` DATETIME NULL,PRIMARY KEY (`id`),UNIQUE KEY `code` (`code`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  if(!$this->db->query($sql))return false;
  foreach(['call'=>'مرکز تماس','call_online'=>'مرکز تماس آنلاین','call_customer'=>'مرکز تماس آنلاین و مشتری'] as $code=>$name){
   $this->db->where('code',$code);if(!$this->db->get($table)->row())$this->db->insert($table,['code'=>$code,'name'=>$name,'active'=>1,'created_at'=>date('Y-m-d H:i:s')]);
  }
  return true;
 }
 public function save_marketing_category($d){
  if(!$this->scr_create_marketing_category_table())return ['ok'=>false,'message'=>'ایجاد جدول انواع بازاریابی امکان‌پذیر نشد؛ دسترسی پایگاه داده را بررسی کنید.'];
  $id=(int)($d['id']??0);$name=trim((string)($d['name']??''));
  if($name===''||mb_strlen($name)>150)return ['ok'=>false,'message'=>'نام نوع بازاریابی معتبر نیست.'];
  $table=db_prefix().'scr_marketing_categories';
  $duplicate=$this->db->where('name',$name)->get($table)->row_array();
  if($duplicate&&(int)$duplicate['id']!==$id)return ['ok'=>false,'message'=>'این عنوان قبلاً ثبت شده است.'];
  if($id){$row=$this->db->where('id',$id)->get($table)->row_array();if(!$row)return ['ok'=>false,'message'=>'مورد پیدا نشد.'];$ok=$this->db->where('id',$id)->update($table,['name'=>$name]);}
  else{$ok=$this->db->insert($table,['code'=>'custom_'.substr(md5(uniqid('',true)),0,20),'name'=>$name,'active'=>1,'created_at'=>date('Y-m-d H:i:s')]);}
  return ['ok'=>(bool)$ok,'message'=>$ok?'':'ذخیره نوع بازاریابی انجام نشد.'];
 }
 public function save_ranking_step($d){scr_ensure_phase4_schema();$id=(int)($d['id']??0);$level=(int)($d['level_id']??0);$name=trim((string)($d['name']??''));$saleType=(int)($d['sale_type_id']??0);$role=($d['ranking_role']??'seller')==='marketer'?'marketer':'seller';$marketing=(string)($d['marketing_type']??'normal');$validMarketing=$this->marketing_category_labels();if(!isset($validMarketing[$marketing]))$marketing='normal';if($role!=='marketer')$marketing='normal';$from=$this->num($d['from_amount']??'');$to=$this->num($d['to_amount']??'');$type=($d['adjustment_type']??'percent')==='fixed'?'fixed':'percent';$val=$this->num($d['adjustment_value']??($d['percent']??''));if(!$level||!$saleType||$name===''||$from===null||$to===null||$val===null)return ['ok'=>false,'message'=>_l('scr_ranking_step_required_v6')];if($from<0||$to<$from)return ['ok'=>false,'message'=>_l('scr_ranking_amount_invalid')];if($type==='percent'&&($val < -100 || $val > 1000))return ['ok'=>false,'message'=>_l('scr_ranking_adjustment_invalid')];$this->db->where('level_id',$level)->where('sale_type_id',$saleType)->where('ranking_role',$role)->where('marketing_type',$marketing)->where('from_amount <=',$to)->where('to_amount >=',$from);if($id)$this->db->where('id !=',$id);if($this->db->get(db_prefix().'scr_ranking_steps')->row())return ['ok'=>false,'message'=>_l('scr_ranking_step_overlap_v6')];$row=['level_id'=>$level,'name'=>$name,'sale_type_id'=>$saleType,'ranking_role'=>$role,'marketing_type'=>$marketing,'from_amount'=>$from,'to_amount'=>$to,'adjustment_type'=>$type,'adjustment_value'=>$val,'percent'=>$type==='percent'?$val:0,'updated_at'=>date('Y-m-d H:i:s')];if($id)$this->db->where('id',$id)->update(db_prefix().'scr_ranking_steps',$row);else{$row['created_at']=date('Y-m-d H:i:s');$this->db->insert(db_prefix().'scr_ranking_steps',$row);$id=$this->db->insert_id();}$this->db->where('id',$level)->update(db_prefix().'scr_ranking_levels',['calculated'=>0,'updated_at'=>date('Y-m-d H:i:s')]);return ['ok'=>true,'id'=>$id];}
 public function delete_ranking_step($id){$r=$this->db->where('id',(int)$id)->get(db_prefix().'scr_ranking_steps')->row();$ok=(bool)$this->db->where('id',(int)$id)->delete(db_prefix().'scr_ranking_steps');if($ok&&$r)$this->db->where('id',(int)$r->level_id)->update(db_prefix().'scr_ranking_levels',['calculated'=>0]);return $ok;}
 public function ranking_results($level){scr_ensure_phase4_schema();return $this->db->select("r.*,CONCAT(s.firstname,' ',s.lastname) staff_name,stp.name step_name,st.name sale_type_name",false)->from(db_prefix().'scr_ranking_results r')->join(db_prefix().'staff s','s.staffid=r.staff_id','left')->join(db_prefix().'scr_ranking_steps stp','stp.id=r.step_id','left')->join(db_prefix().'scr_sale_types st','st.legacy_id=r.sale_type_id OR st.id=r.sale_type_id','left')->where('r.level_id',(int)$level)->order_by('r.ranking_role','ASC')->order_by('r.sale_type_id','ASC')->order_by('r.sales_amount','DESC')->get()->result_array();}
 public function scr_ranking_payment_beneficiaries($levelId){
  $this->scr_ensure_payment_commission_schema();
  $level=$this->ranking((int)$levelId);if(!$level)return [];
  return $this->db->select("c.payment_id,c.invoice_id,c.staff_id,c.role,SUM(c.paid_share) paid_share,SUM(c.commission_amount) commission_amount,CONCAT(s.firstname,' ',s.lastname) staff_name",false)
    ->from(db_prefix().'scr_payment_commissions c')->join(db_prefix().'staff s','s.staffid=c.staff_id','left')
    ->where('c.invoice_date >=',$level['valid_from'])->where('c.invoice_date <=',$level['valid_to'])
    ->group_by(['c.payment_id','c.invoice_id','c.staff_id','c.role','s.firstname','s.lastname'])->get()->result_array();
 }
 public function ranking_invoice_ledger($level){scr_ensure_phase4_schema();return $this->db->select("x.*,st.name sale_type_name,CONCAT(ss.firstname,' ',ss.lastname) seller_name,CONCAT(ms.firstname,' ',ms.lastname) marketer_name",false)->from(db_prefix().'scr_ranking_invoice_ledger x')->join(db_prefix().'scr_sale_types st','st.legacy_id=x.sale_type_id OR st.id=x.sale_type_id','left')->join(db_prefix().'staff ss','ss.staffid=x.seller_staff_id','left')->join(db_prefix().'staff ms','ms.staffid=x.marketer_staff_id','left')->where('x.level_id',(int)$level)->order_by('x.invoice_date','ASC')->order_by('x.payment_id','ASC')->get()->result_array();}
 private function commission_for_payment($paymentId,$type){
  // Independent SCR commission ledger. Never read the legacy commission module.
  $this->scr_ensure_payment_commission_schema();
  $r=$this->db->select_sum('commission_amount','v')->where('payment_id',(int)$paymentId)->where('role',$type===2?'marketer':'seller')->get(db_prefix().'scr_payment_commissions')->row();
  return $r?(float)$r->v:0.0;
 }
 private function ledger_staff_for_payment($paymentId,$type,$fallback=0){return (int)$fallback;}
 private function scr_ensure_payment_commission_schema(){
  $t=db_prefix().'scr_payment_commissions';
  if(!$this->db->table_exists($t))$this->db->query("CREATE TABLE `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`payment_id` INT NOT NULL,`invoice_id` INT NOT NULL,`staff_id` INT NOT NULL,`role` VARCHAR(12) NOT NULL,`group_id` INT NOT NULL,`sale_type_id` INT NOT NULL,`rule_id` INT NOT NULL,`invoice_date` DATE NOT NULL,`payment_date` DATE NOT NULL,`paid_share` DECIMAL(18,4) NOT NULL,`rule_percent` DECIMAL(9,3) NOT NULL,`commission_amount` DECIMAL(18,4) NOT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `payment_role_group` (`payment_id`,`role`,`group_id`),KEY `staff_date` (`staff_id`,`payment_date`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 }
 public function scr_rebuild_payment_commissions($levelId){
  $level=$this->ranking((int)$levelId);
  if(!$level)return ['ok'=>false,'message'=>'دوره یافت نشد'];
  if(!empty($level['commission_paid']))return ['ok'=>false,'message'=>'دوره تسویه‌شده قابل بازسازی نیست'];
  $this->scr_ensure_payment_commission_schema();scr_ensure_phase3_schema();
  $p=db_prefix().'invoicepaymentrecords';$i=db_prefix().'invoices';$ia=db_prefix().'itemable';$items=db_prefix().'items';
  if(!$this->db->table_exists($ia)||!$this->db->table_exists($items))return ['ok'=>false,'message'=>'جدول اقلام یا کالاها موجود نیست'];
  $dateExpr=$this->scr_effective_invoice_date_sql('i');
  $records=$this->db->select('p.id payment_id,p.amount,p.date payment_date,i.*,'.$dateExpr.' scr_invoice_date',false)
   ->from($p.' p')->join($i.' i','i.id=p.invoiceid')
   ->where($dateExpr.' >= '.$this->db->escape($level['valid_from']),null,false)
   ->where($dateExpr.' <= '.$this->db->escape($level['valid_to']),null,false)->get()->result_array();
  $done=0;$skipped=[];$t=db_prefix().'scr_payment_commissions';
  foreach($records as $r){
   $pid=(int)$r['payment_id'];$iid=(int)$r['id'];$r['date']=$r['scr_invoice_date'];$total=(float)$r['total'];$paid=(float)$r['amount'];
   if($total<=0||$paid<=0){$skipped[]=$pid.': مبلغ نامعتبر';continue;}
   $sale=(int)($r['invoice_type']??$r['sale_type_id']??0);
   $lines=$this->db->select('a.itemItemId,a.qty,a.rate,a.itemPercent,it.group_id',false)->from($ia.' a')->join($items.' it','it.id=a.itemItemId','left')->where('a.rel_type','invoice')->where('a.rel_id',$iid)->get()->result_array();
   if(!$lines){$skipped[]=$pid.': اقلام فاکتور پیدا نشد';continue;}
   $groups=[];$gross=0;
   foreach($lines as $line){$group=(int)($line['group_id']??0);$value=(float)$line['qty']*(float)$line['rate']*(1-(float)($line['itemPercent']??0)/100);if($group<=0||$value<=0)continue;$groups[$group]=($groups[$group]??0)+$value;$gross+=$value;}
   if($gross<=0){$skipped[]=$pid.': گروه کالایی معتبر نیست';continue;}
   $pending=[];$invalid=false;
   foreach($groups as $gid=>$value){
    $rule=$this->db->where('group_id',$gid)->where('sale_type_id',$sale)->where('active',1)->where('valid_from <=',$r['date'])->where('valid_to >=',$r['date'])->order_by('id','DESC')->get(db_prefix().'scr_commission_rules')->row_array();
    if(!$rule){$skipped[]=$pid.': قاعده پورسانت گروه '.$gid.' / نوع فروش '.$sale.' وجود ندارد';$invalid=true;break;}
    $share=$paid*$value/$gross;
    foreach([['seller','sale_agent','seller_percent'],['marketer','invoice_marketer','marketer_percent']] as $role){
     $staff=$role[0]==='seller'?$this->scr_resolved_seller($iid,(int)($r['sale_agent']??0)):(int)($r[$role[1]]??0);if($staff<=0)continue;
     if($role[0]==='seller'&&$this->scr_marketing_stage($staff,$sale,(string)$r['date'])==='call')continue;
     // A rule must be explicitly assigned to the beneficiary for the invoice date.
     $assignTable=db_prefix().'scr_commission_rule_staff';
     if(!$this->db->table_exists($assignTable)||!$this->db->where('rule_id',(int)$rule['id'])->where('staff_id',$staff)->where('valid_from <=',$r['date'])->where('valid_to >=',$r['date'])->count_all_results($assignTable)){
      $skipped[]=$pid.': سیاست گروه '.$gid.' به کارمند '.$staff.' منتسب نیست';
      continue;
     }
     $percent=(float)$rule[$role[2]];
     $pending[]=['payment_id'=>$pid,'invoice_id'=>$iid,'staff_id'=>$staff,'role'=>$role[0],'group_id'=>$gid,'sale_type_id'=>$sale,'rule_id'=>(int)$rule['id'],'invoice_date'=>$r['date'],'payment_date'=>$r['payment_date'],'paid_share'=>round($share,4),'rule_percent'=>$percent,'commission_amount'=>round($share*$percent/100,4),'created_at'=>date('Y-m-d H:i:s')];
    }
   }
   if($invalid)continue;
   // Stage-one call-centre sellers receive no sales commission. Their managers,
   // however, earn the configured participation on the actual payment amount.
   // Persist one independent manager entry per payment and manager; the existing
   // seller-based manager settlement path remains untouched for other sellers.
   $sellerId=$this->scr_resolved_seller($iid,(int)($r['sale_agent']??0));
   if($sellerId>0 && $this->scr_marketing_stage($sellerId,$sale,(string)$r['date'])==='call'){
    $hierarchyTable=db_prefix().'scr_sales_hierarchy';
    if($this->db->table_exists($hierarchyTable)){
     $managers=$this->db->select('manager_staff_id,participation_percent')
      ->where('seller_staff_id',$sellerId)->where('active',1)
      ->get($hierarchyTable)->result_array();
     $seenManagers=[];
     foreach($managers as $manager){
      $managerId=(int)$manager['manager_staff_id'];
      $percent=(float)$manager['participation_percent'];
      if($managerId<=0||$managerId===$sellerId||$percent<=0||isset($seenManagers[$managerId]))continue;
      $seenManagers[$managerId]=true;
      $pending[]=['payment_id'=>$pid,'invoice_id'=>$iid,'staff_id'=>$managerId,
       'role'=>'manager','group_id'=>$managerId,'sale_type_id'=>$sale,'rule_id'=>0,
       'invoice_date'=>$r['date'],'payment_date'=>$r['payment_date'],
       'paid_share'=>round($paid,4),'rule_percent'=>$percent,
       'commission_amount'=>round($paid*$percent/100,4),'created_at'=>date('Y-m-d H:i:s')];
     }
    }
   }
   $this->db->trans_begin();$this->db->where('payment_id',$pid)->delete($t);
   foreach($pending as $row)$this->db->insert($t,$row);
   if($this->db->trans_status()===false){$this->db->trans_rollback();$skipped[]=$pid.': خطای ذخیره';continue;}
   $this->db->trans_commit();$done++;
  }
  return ['ok'=>true,'count'=>$done,'skipped'=>$skipped];
 }
 public function scr_payment_commission_rows($levelId){
  $this->scr_ensure_payment_commission_schema();$level=$this->ranking((int)$levelId);if(!$level)return [];
  return $this->db->select('c.*,CONCAT(s.firstname," ",s.lastname) staff_name',false)->from(db_prefix().'scr_payment_commissions c')->join(db_prefix().'staff s','s.staffid=c.staff_id','left')->where('c.invoice_date >=',$level['valid_from'])->where('c.invoice_date <=',$level['valid_to'])->order_by('c.payment_id','DESC')->get()->result_array();
 }
 private function scr_effective_invoice_date_sql($alias='i'){
  // Keep normal Gregorian invoice dates; recover only invalid dates from creation timestamp.
  return "CASE WHEN {$alias}.date IS NULL OR {$alias}.date < '2000-01-01' OR {$alias}.date > DATE_ADD(CURDATE(), INTERVAL 1 YEAR) THEN DATE({$alias}.datecreated) ELSE {$alias}.date END";
 }
 private function scr_resolved_seller($invoiceId,$saleAgent){
  $saleAgent=(int)$saleAgent;
  if($saleAgent>0)return $saleAgent;
  $map=db_prefix().'scr_invoice_staff_attributions';
  if(!$this->db->table_exists($map))return 0;
  $r=$this->db->select('staff_id')->where('invoice_id',(int)$invoiceId)->get($map)->row_array();
  return (int)($r['staff_id']??0);
 }
 private function rebuild_ranking_ledger($level){
  $led=db_prefix().'scr_ranking_invoice_ledger';$p=db_prefix().'invoicepaymentrecords';$i=db_prefix().'invoices';
  $saleField=$this->db->field_exists('invoice_type',$i)?'invoice_type':($this->db->field_exists('sale_type_id',$i)?'sale_type_id':null);
  $sellerField=$this->db->field_exists('sale_agent',$i)?'sale_agent':null;
  $marketerField=$this->db->field_exists('invoice_marketer',$i)?'invoice_marketer':null;
  $dateExpr=$this->scr_effective_invoice_date_sql('i');
  $select='p.id payment_id,p.invoiceid invoice_id,p.amount paid_amount,p.date payment_date,'.$dateExpr.' invoice_date,i.number,i.prefix,i.formatted_number';
  $select.=','.($saleField?'i.'.$saleField:'0').' sale_type_id';
  $select.=','.($sellerField?'i.'.$sellerField:'0').' seller_id';
  $select.=','.($marketerField?'i.'.$marketerField:'0').' marketer_id';
  $query=$this->db->select($select,false)->from($p.' p')->join($i.' i','i.id=p.invoiceid','inner')
   ->where($dateExpr.' >= '.$this->db->escape($level['valid_from']),null,false)
   ->where($dateExpr.' <= '.$this->db->escape($level['valid_to']),null,false);
  if($this->db->field_exists('status',$i))$query->where('i.status !=',5);
  $rows=$query->order_by('i.id','ASC')->order_by('p.id','ASC')->get()->result_array();
  $this->db->trans_begin();$this->db->where('level_id',(int)$level['id'])->delete($led);
  $now=date('Y-m-d H:i:s');
  foreach($rows as $r){
   $pid=(int)$r['payment_id'];$iid=(int)$r['invoice_id'];
   $seller=$this->scr_resolved_seller($iid,(int)$r['seller_id']);
   $marketer=(int)$r['marketer_id'];
   $num=trim((string)($r['formatted_number']??''));if($num==='')$num=(string)($r['prefix']??'').(string)$r['number'];
   $this->db->insert($led,['level_id'=>(int)$level['id'],'payment_id'=>$pid,'invoice_id'=>$iid,'invoice_number'=>$num,
    'invoice_date'=>$r['invoice_date'],'payment_date'=>$r['payment_date'],'paid_amount'=>(float)$r['paid_amount'],
    'sale_type_id'=>(int)$r['sale_type_id'],'seller_staff_id'=>$seller,'marketer_staff_id'=>$marketer,
    'seller_base_commission'=>$this->commission_for_payment($pid,1),'marketer_base_commission'=>$this->commission_for_payment($pid,2),'created_at'=>$now]);
  }
  if($this->db->trans_status()===false){$this->db->trans_rollback();return;}
  $this->db->trans_commit();
 }

 private function scr_marketing_stage($staffId,$saleType,$invoiceDate=''){
  $staffId=(int)$staffId;if($staffId<=0)return 'normal';
  $sale=$this->db->group_start()->where('legacy_id',(int)$saleType)->or_where('id',(int)$saleType)->group_end()->get(db_prefix().'scr_sale_types')->row_array();
  $saleName=(string)($sale['name']??'');if(!preg_match('/آنلاین|online/iu',$saleName))return 'normal';
  if($this->db->table_exists(db_prefix().'scr_online_staff_stages')){
   $date=$invoiceDate?:date('Y-m-d');
   $assignment=$this->db->select('category_code')->where('staff_id',$staffId)->where('effective_date <=',$date)->order_by('effective_date','DESC')->order_by('id','DESC')->limit(1)->get(db_prefix().'scr_online_staff_stages')->row_array();
   if($assignment)return (string)$assignment['category_code'];
  }
  $staff=$this->db->select('firstname,lastname')->where('staffid',$staffId)->get(db_prefix().'staff')->row_array();
  $name=trim((string)($staff['firstname']??'').' '.(string)($staff['lastname']??''));
  // Match user-maintained category names first; longest label wins on overlapping names.
  $categories=$this->marketing_categories();usort($categories,function($a,$b){return mb_strlen($b['name'])-mb_strlen($a['name']);});
  foreach($categories as $category){if(!(int)$category['active'])continue;$label=trim((string)$category['name']);if($label!==''&&mb_stripos(preg_replace('/\s+/u',' ',$name),preg_replace('/\s+/u',' ',$label))!==false)return $category['code'];}
  // Backwards compatibility for established employee naming conventions.
  if(preg_match('/مرکز\s*تماس.*آنلاین.*مشتری/iu',$name))return 'call_customer';
  if(preg_match('/مرکز\s*تماس.*آنلاین/iu',$name))return 'call_online';
  if(preg_match('/مرکز\s*تماس/iu',$name))return 'call';
  return 'normal';
 }
 private function scr_manager_ranking_totals($ledger){
 $totals=[];$h=db_prefix().'scr_sales_hierarchy';
 if(!$this->db->table_exists($h))return $totals;
 $hier=[];
 foreach($this->db->where('active',1)->get($h)->result_array() as $r){
  $seller=(int)$r['seller_staff_id'];$manager=(int)$r['manager_staff_id'];
  if($seller<=0||$manager<=0||$seller===$manager)continue;
  $hier[$seller][$manager]=(float)($r['participation_percent']??0);
 }
 foreach($ledger as $x){
  $seller=(int)$x['seller_staff_id'];$type=(int)$x['sale_type_id'];$payment=(float)$x['paid_amount'];
  if($payment<=0)continue;
  foreach(($hier[$seller]??[]) as $manager=>$pct){
   if($pct<=0)continue;
   $stage=$this->scr_marketing_stage($seller,$type,(string)($x['invoice_date']??''));$key=$type.'|'.$manager.'|'.$stage;
   if(!isset($totals[$key]))$totals[$key]=['sales'=>0.0,'base'=>0.0];
   $totals[$key]['sales']+=$payment;
   $totals[$key]['base']+=round($payment*$pct/100,4);
  }
 }
 return $totals;
}
 public function calculate_ranking($levelId){scr_ensure_phase4_schema();$this->sync_canceled_commissions();$level=$this->ranking($levelId);if(!$level)return ['ok'=>false,'message'=>_l('scr_ranking_not_found')];if((int)($level['commission_paid']??0)===1)return ['ok'=>false,'message'=>_l('scr_paid_period_locked')];$steps=$this->ranking_steps($levelId);if(!$steps)return ['ok'=>false,'message'=>_l('scr_ranking_no_steps')];$p=db_prefix().'invoicepaymentrecords';$i=db_prefix().'invoices';if(!$this->db->table_exists($p)||!$this->db->table_exists($i))return ['ok'=>false,'message'=>_l('scr_ranking_payment_tables_missing')];$this->scr_rebuild_payment_commissions($levelId);$this->rebuild_ranking_ledger($level);$ledger=$this->ranking_invoice_ledger($levelId);$managerTotals=$this->scr_manager_ranking_totals($ledger);$groups=[];foreach($steps as $step){$marketing=(string)($step['marketing_type']??'normal');$key=$step['ranking_role'].'|'.(int)$step['sale_type_id'].'|'.$marketing;if(!isset($groups[$key]))$groups[$key]=['role'=>$step['ranking_role'],'sale_type_id'=>(int)$step['sale_type_id'],'marketing_type'=>$marketing,'steps'=>[]];$groups[$key]['steps'][]=$step;}$this->db->where('level_id',(int)$levelId)->delete(db_prefix().'scr_ranking_results');$now=date('Y-m-d H:i:s');$count=0;foreach($groups as $g){$role=$g['role'];$saleType=$g['sale_type_id'];$marketing=$g['marketing_type'];$staff=[];foreach($ledger as $x){if((int)$x['sale_type_id']!==$saleType)continue;if($this->scr_marketing_stage((int)$x['seller_staff_id'],$saleType,(string)($x['invoice_date']??''))!==$marketing)continue;$sid=$role==='marketer'?(int)$x['marketer_staff_id']:(int)$x['seller_staff_id'];if($sid<=0)continue;if(!isset($staff[$sid]))$staff[$sid]=['sales'=>0.0,'base'=>0.0];$staff[$sid]['sales']+=(float)$x['paid_amount'];$staff[$sid]['base']+=(float)($role==='marketer'?$x['marketer_base_commission']:$x['seller_base_commission']);}if($role==='marketer'){foreach($managerTotals as $mk=>$mv){$parts=explode('|',$mk);if((int)$parts[0]!==$saleType||($parts[2]??'normal')!==$marketing)continue;$managerId=(int)$parts[1];if(!isset($staff[$managerId]))$staff[$managerId]=['sales'=>0.0,'base'=>0.0];$staff[$managerId]['sales']+=(float)$mv['sales'];$staff[$managerId]['base']+=(float)$mv['base'];}}$pr=$this->db->select('staff_id')->where('ranking_role',$role)->where('sale_type_id',$saleType)->where('status','pending')->where('cancel_date <=',$level['valid_to'])->get(db_prefix().'scr_commission_reversals')->result_array();foreach($pr as $px){$ps=(int)$px['staff_id'];if($ps>0&&!isset($staff[$ps]))$staff[$ps]=['sales'=>0.0,'base'=>0.0];}foreach($staff as $sid=>$v){$step=null;foreach($g['steps'] as $x)if($v['sales']>=(float)$x['from_amount']&&$v['sales']<=(float)$x['to_amount']){$step=$x;break;}$type=$step?($step['adjustment_type']??'percent'):'percent';$val=$step?(float)($step['adjustment_value']??$step['percent']??0):0.0;if($v['sales']==0.0&&$v['base']==0.0){$step=null;$type='percent';$val=0.0;}$adj=$step?($type==='fixed'?$val:($v['base']*$val/100)):0.0;$reversal=$this->pending_reversal_amount((int)$sid,$role,$saleType,$level);$final=$v['base']+$adj+$reversal;$this->db->insert(db_prefix().'scr_ranking_results',['level_id'=>(int)$levelId,'step_id'=>$step?(int)$step['id']:null,'staff_id'=>(int)$sid,'sale_type_id'=>$saleType,'ranking_role'=>$role,'marketing_type'=>$marketing,'sales_amount'=>$v['sales'],'percent'=>$type==='percent'?$val:0,'base_commission'=>$v['base'],'adjustment_type'=>$type,'adjustment_value'=>$val,'adjustment_amount'=>$adj,'reversal_amount'=>$reversal,'final_commission'=>$final,'calculated_at'=>$now]);$this->apply_pending_reversals((int)$sid,$role,$saleType,(int)$levelId,$level);$count++;}}$this->db->where('id',(int)$levelId)->update(db_prefix().'scr_ranking_levels',['calculated'=>1,'updated_at'=>$now]);return ['ok'=>true,'count'=>$count];}

 // Live ranking: refresh only when the underlying payment aggregate changed.
 // Read-time reconciliation avoids expensive rebuilds inside Perfex payment hooks.
 public function scr_refresh_live_ranking($levelId){
  $level=$this->ranking((int)$levelId);
  if(!$level || (int)($level['commission_paid']??0)===1)return false;
  $p=db_prefix().'invoicepaymentrecords';
  if(!$this->db->table_exists($p))return false;
  $row=$this->db->select('COUNT(*) payment_count,COALESCE(SUM(amount),0) payment_total,COALESCE(MAX(id),0) last_payment',false)
   ->where('date >=',$level['valid_from'])->where('date <=',$level['valid_to'])->get($p)->row_array();
  $fingerprint=hash('sha256',implode('|',[(int)$row['payment_count'],(string)$row['payment_total'],(int)$row['last_payment'],(string)$level['updated_at']]));
  $key='scr_live_rank_'.$levelId;
  $cache=$this->db->table_exists(db_prefix().'options')?get_option($key):'';
  if($cache===$fingerprint && (int)$level['calculated']===1)return false;
  $result=$this->calculate_ranking((int)$levelId);
  if(!empty($result['ok'])){
   // updated_at changes during calculation; use the new level timestamp.
   $newLevel=$this->ranking((int)$levelId);
   $fingerprint=hash('sha256',implode('|',[(int)$row['payment_count'],(string)$row['payment_total'],(int)$row['last_payment'],(string)$newLevel['updated_at']]));
   update_option($key,$fingerprint);
   return true;
  }
  return false;
 }
 public function mark_ranking_paid($levelId){scr_ensure_phase4_schema();$r=$this->ranking($levelId);if(!$r||!(int)$r['calculated'])return ['ok'=>false,'message'=>_l('scr_must_calculate_before_paid')];$this->db->where('id',(int)$levelId)->update(db_prefix().'scr_ranking_levels',['commission_paid'=>1,'commission_paid_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);return ['ok'=>true];}
 public function canceled_commissions(){scr_ensure_phase4_schema();$this->sync_canceled_commissions();return $this->db->select("r.*,CONCAT(s.firstname,' ',s.lastname) staff_name,st.name sale_type_name,l.name source_level_name,al.name applied_level_name",false)->from(db_prefix().'scr_commission_reversals r')->join(db_prefix().'staff s','s.staffid=r.staff_id','left')->join(db_prefix().'scr_sale_types st','st.legacy_id=r.sale_type_id OR st.id=r.sale_type_id','left')->join(db_prefix().'scr_ranking_levels l','l.id=r.source_level_id','left')->join(db_prefix().'scr_ranking_levels al','al.id=r.applied_level_id','left')->order_by('r.cancel_date','DESC')->order_by('r.id','DESC')->get()->result_array();}
 private function invoice_is_canceled($invoiceId){$t=db_prefix().'invoices';if(!$this->db->table_exists($t)||!$this->db->field_exists('status',$t))return false;$r=$this->db->select('status')->where('id',(int)$invoiceId)->get($t)->row();return $r&&((int)$r->status===5);}
 private function source_reversal_amount($levelId,$staffId,$role,$saleType,$paymentBase){$r=$this->db->where('level_id',(int)$levelId)->where('staff_id',(int)$staffId)->where('ranking_role',$role)->where('sale_type_id',(int)$saleType)->get(db_prefix().'scr_ranking_results')->row_array();$base=(float)$paymentBase;if(!$r||$base==0)return [$base,0.0,$base];$totalBase=(float)$r['base_commission'];$rankPart=0.0;if($totalBase!=0)$rankPart=(float)$r['adjustment_amount']*($base/$totalBase);return [$base,$rankPart,$base+$rankPart];}
 private function create_reversal_from_ledger($x,$cancelDate,$legacyId=null,$description=''){$rv=db_prefix().'scr_commission_reversals';$level=$this->db->where('id',(int)$x['level_id'])->get(db_prefix().'scr_ranking_levels')->row_array();if(!$level||!(int)($level['commission_paid']??0))return;foreach([['seller','seller_staff_id','seller_base_commission'],['marketer','marketer_staff_id','marketer_base_commission']] as $m){$sid=(int)$x[$m[1]];$base=(float)$x[$m[2]];if($sid<=0||$base==0)continue;if($this->db->where('payment_id',(int)$x['payment_id'])->where('staff_id',$sid)->where('ranking_role',$m[0])->get($rv)->row())continue;[$b,$ra,$tot]=$this->source_reversal_amount((int)$x['level_id'],$sid,$m[0],(int)$x['sale_type_id'],$base);$this->db->insert($rv,['legacy_id'=>$legacyId,'payment_id'=>(int)$x['payment_id'],'invoice_id'=>(int)$x['invoice_id'],'source_level_id'=>(int)$x['level_id'],'staff_id'=>$sid,'ranking_role'=>$m[0],'sale_type_id'=>(int)$x['sale_type_id'],'cancel_date'=>$cancelDate,'base_commission_reversal'=>-$b,'rank_adjustment_reversal'=>-$ra,'total_reversal'=>-$tot,'status'=>'pending','description'=>$description,'created_at'=>date('Y-m-d H:i:s')]);$legacyId=null;}}
 public function sync_canceled_commissions(){scr_ensure_phase4_schema();$led=db_prefix().'scr_ranking_invoice_ledger';$levels=db_prefix().'scr_ranking_levels';$rows=$this->db->select('x.*')->from($led.' x')->join($levels.' l','l.id=x.level_id','inner')->where('l.commission_paid',1)->get()->result_array();foreach($rows as $x)if($this->invoice_is_canceled((int)$x['invoice_id'])){$inv=$this->db->select('date')->where('id',(int)$x['invoice_id'])->get(db_prefix().'invoices')->row();$this->create_reversal_from_ledger($x,date('Y-m-d'),null,_l('scr_auto_canceled_invoice_reversal'));}
  $old=db_prefix().'item_discount_after_pay_record_canceled_comission';if($this->db->table_exists($old)){foreach($this->db->get($old)->result_array() as $c){$legacy=(int)($c['aprc_id']??0);if($legacy&&$this->db->where('legacy_id',$legacy)->get(db_prefix().'scr_commission_reversals')->row())continue;$pid=(int)($c['aprc_payment']??0);$sid=(int)($c['aprc_staff']??0);$role=((int)($c['aprc_type']??1)===2)?'marketer':'seller';$x=$this->db->where('payment_id',$pid)->order_by('level_id','DESC')->get($led)->row_array();if(!$x)continue;$level=$this->db->where('id',(int)$x['level_id'])->get($levels)->row_array();if(!$level||!(int)($level['commission_paid']??0))continue;$base=(float)($c['aprc_comission']??0);if($base==0)$base=(float)$x[$role==='marketer'?'marketer_base_commission':'seller_base_commission'];[$b,$ra,$tot]=$this->source_reversal_amount((int)$x['level_id'],$sid,$role,(int)$x['sale_type_id'],$base);$this->db->insert(db_prefix().'scr_commission_reversals',['legacy_id'=>$legacy?:null,'payment_id'=>$pid,'invoice_id'=>(int)($c['aprc_invoice']??$x['invoice_id']),'source_level_id'=>(int)$x['level_id'],'staff_id'=>$sid,'ranking_role'=>$role,'sale_type_id'=>(int)$x['sale_type_id'],'cancel_date'=>($c['aprc_date_creat']??$c['aprc_date']??date('Y-m-d')),'base_commission_reversal'=>-$b,'rank_adjustment_reversal'=>-$ra,'total_reversal'=>-$tot,'status'=>((int)($c['pay']??0)===1?'applied':'pending'),'description'=>(string)($c['description']??''),'created_at'=>date('Y-m-d H:i:s')]);}}}
 public function pending_reversal_amount($staffId,$role,$saleType,$level){$q=$this->db->select_sum('total_reversal','v')->where('staff_id',(int)$staffId)->where('ranking_role',$role)->where('sale_type_id',(int)$saleType)->where('status','pending')->where('cancel_date <=',$level['valid_to']);$r=$q->get(db_prefix().'scr_commission_reversals')->row();return $r?(float)$r->v:0.0;}
 private function apply_pending_reversals($staffId,$role,$saleType,$levelId,$level){$this->db->where('staff_id',(int)$staffId)->where('ranking_role',$role)->where('sale_type_id',(int)$saleType)->where('status','pending')->where('cancel_date <=',$level['valid_to'])->update(db_prefix().'scr_commission_reversals',['status'=>'applied','applied_level_id'=>(int)$levelId,'applied_at'=>date('Y-m-d H:i:s')]);}

 private function valid_pos_device_id($id){$id=(int)$id;if($id<=0)return 0;$t=db_prefix().'scr_pos_devices';if(!$this->db->table_exists($t))return 0;$r=$this->db->select('id')->where('id',$id)->where('active',1)->get($t)->row();return $r?(int)$r->id:0;}
 public function capture_payment($paymentId,$staffId,$posHint=0){
  scr_ensure_cashier_schema();$pt=db_prefix().'invoicepaymentrecords';if(!$this->db->table_exists($pt))return false;$p=$this->db->where('id',(int)$paymentId)->get($pt)->row_array();if(!$p)return false;$date=!empty($p['date'])?$p['date']:date('Y-m-d');$session=$this->cashier_session($staffId,$date,true);if(!$session)return false;$row=['payment_id'=>(int)$paymentId,'invoice_id'=>(int)($p['invoiceid']??0),'staff_id'=>(int)$staffId,'session_id'=>(int)$session['id'],'payment_mode'=>(string)($p['paymentmode']??''),'pos_device_id'=>$this->valid_pos_device_id((int)($this->input->post('scr_pos_device_id')??$posHint)),'amount'=>(float)($p['amount']??0),'payment_date'=>$date,'created_at'=>date('Y-m-d H:i:s')];if($this->db->where('payment_id',(int)$paymentId)->get(db_prefix().'scr_cashier_payment_links')->row())$this->db->where('payment_id',(int)$paymentId)->update(db_prefix().'scr_cashier_payment_links',$row);else $this->db->insert(db_prefix().'scr_cashier_payment_links',$row);return true;
 }
 public function sync_payment_update($paymentId){
  scr_ensure_cashier_schema();$lt=db_prefix().'scr_cashier_payment_links';$pt=db_prefix().'invoicepaymentrecords';$link=$this->db->where('payment_id',(int)$paymentId)->get($lt)->row_array();if(!$link)return true;$p=$this->db->where('id',(int)$paymentId)->get($pt)->row_array();if(!$p)return true;$session=$this->db->where('id',(int)$link['session_id'])->get(db_prefix().'scr_cashier_sessions')->row_array();$row=['invoice_id'=>(int)($p['invoiceid']??$link['invoice_id']),'payment_mode'=>(string)($p['paymentmode']??$link['payment_mode']),'amount'=>(float)($p['amount']??$link['amount']),'payment_date'=>(string)($p['date']??$link['payment_date'])];$this->db->where('payment_id',(int)$paymentId)->update($lt,$row);if($session&&$session['status']!=='open')$this->mark_system_discrepancy($session,'ویرایش پرداخت شماره '.$paymentId.' پس از تحویل صندوق',0,(float)$row['amount']);return true;
 }
 public function sync_payment_delete($paymentId,$staffId=0){
  scr_ensure_cashier_schema();$lt=db_prefix().'scr_cashier_payment_links';$link=$this->db->where('payment_id',(int)$paymentId)->get($lt)->row_array();if(!$link)return true;$session=$this->db->where('id',(int)$link['session_id'])->get(db_prefix().'scr_cashier_sessions')->row_array();if($session&&$session['status']!=='open')$this->mark_system_discrepancy($session,'پرداخت شماره '.$paymentId.' به مبلغ '.number_format((float)$link['amount'],0,'.',',').' پس از تحویل/بستن صندوق حذف شد.',$staffId,(float)$link['amount']);else if($session)$this->cashier_audit($session['id'],$staffId,'payment_deleted','open','open','حذف پرداخت شماره '.$paymentId,(float)$link['amount']);$this->db->where('payment_id',(int)$paymentId)->delete($lt);return true;
 }
 private function mark_system_discrepancy($session,$note,$staffId=0,$amount=null){
  $from=(string)$session['status'];$to=in_array($from,['approved','approved_discrepancy'],true)?'system_review':'disputed';$old=trim((string)($session['accountant_note']??''));$full=trim($old."\n".date('Y-m-d H:i:s').' - '.$note);$this->db->where('id',(int)$session['id'])->update(db_prefix().'scr_cashier_sessions',['status'=>$to,'accountant_note'=>$full,'discrepancy_type'=>'system_payment','discrepancy_amount'=>$amount===null?(float)($session['discrepancy_amount']??0):(float)$amount,'updated_at'=>date('Y-m-d H:i:s')]);$this->cashier_audit($session['id'],$staffId,'system_payment_change',$from,$to,$note,$amount);
 }
 public function cashier_session($staffId,$date,$create=false){scr_ensure_cashier_schema();$r=$this->db->where('staff_id',(int)$staffId)->where('business_date',$date)->get(db_prefix().'scr_cashier_sessions')->row_array();if(!$r&&$create){$this->db->insert(db_prefix().'scr_cashier_sessions',['staff_id'=>(int)$staffId,'business_date'=>$date,'status'=>'open','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);$r=$this->db->where('id',$this->db->insert_id())->get(db_prefix().'scr_cashier_sessions')->row_array();}return $r;}
 public function payment_mode_category($mode){$r=$this->db->where('payment_mode',(string)$mode)->get(db_prefix().'scr_payment_mode_categories')->row_array();if($r)return $r;$label=$this->payment_mode_label($mode);$cat=preg_match('/(^|[^a-z])pos([^a-z]|$)/i',(string)$label)?'pos':'other';return ['payment_mode'=>(string)$mode,'category'=>$cat,'account_label'=>''];}
 public function cashier_report($staffId,$date){
  scr_ensure_cashier_schema();$session=$this->cashier_session($staffId,$date,true);$pt=db_prefix().'invoicepaymentrecords';$lt=db_prefix().'scr_cashier_payment_links';$rows=[];if($this->db->table_exists($pt)){$rows=$this->db->select('l.*,p.amount live_amount,p.paymentmode live_mode,p.date live_date,p.invoiceid live_invoice,i.number,i.prefix,pd.name pos_device_name',false)->from($lt.' l')->join($pt.' p','p.id=l.payment_id','inner')->join(db_prefix().'invoices i','i.id=p.invoiceid','left')->join(db_prefix().'scr_pos_devices pd','pd.id=l.pos_device_id','left')->where('l.session_id',(int)$session['id'])->order_by('p.id','ASC')->get()->result_array();}
  $tot=['cash'=>0.0,'pos'=>0.0,'bank'=>0.0,'other'=>0.0,'all'=>0.0];$modes=[];foreach($rows as &$r){$mode=(string)($r['live_mode']??$r['payment_mode']);$cat=$this->payment_mode_category($mode);$c=in_array($cat['category'],['cash','pos','bank','other'],true)?$cat['category']:'other';$a=(float)($r['live_amount']??$r['amount']);$r['category']=$c;$r['account_label']=$cat['account_label'];$r['mode_label']=$this->payment_mode_label($mode);$tot[$c]+=$a;$tot['all']+=$a;$device=trim((string)($r['pos_device_name']??''));$key=$mode.'|'.$c.'|'.$device;if(!isset($modes[$key]))$modes[$key]=['mode'=>$mode,'label'=>$r['mode_label'],'category'=>$c,'account_label'=>$cat['account_label'],'pos_device_name'=>$device,'amount'=>0.0,'count'=>0];$modes[$key]['amount']+=$a;$modes[$key]['count']++;}
  $this->db->where('id',(int)$session['id'])->update(db_prefix().'scr_cashier_sessions',['expected_cash'=>$tot['cash'],'updated_at'=>date('Y-m-d H:i:s')]);$session=$this->db->where('id',(int)$session['id'])->get(db_prefix().'scr_cashier_sessions')->row_array();$staff=$this->db->select("CONCAT(firstname,' ',lastname) name",false)->where('staffid',(int)$staffId)->get(db_prefix().'staff')->row_array();return ['session'=>$session,'rows'=>$rows,'totals'=>$tot,'modes'=>array_values($modes),'staff_name'=>$staff['name']??''];
 }
 private function payment_mode_label($mode){$t=db_prefix().'payment_modes';if($this->db->table_exists($t)&&ctype_digit((string)$mode)){$r=$this->db->where('id',(int)$mode)->get($t)->row_array();if($r&&!empty($r['name']))return $r['name'];}return (string)$mode;}
 public function cashier_sessions($date='',$status=''){scr_ensure_cashier_schema();$this->db->select("s.*,CONCAT(st.firstname,' ',st.lastname) staff_name,CONCAT(ap.firstname,' ',ap.lastname) approver_name",false)->from(db_prefix().'scr_cashier_sessions s')->join(db_prefix().'staff st','st.staffid=s.staff_id','left')->join(db_prefix().'staff ap','ap.staffid=s.approved_by','left');if($date)$this->db->where('s.business_date',$date);if($status)$this->db->where('s.status',$status);return $this->db->order_by('s.business_date','DESC')->order_by('s.id','DESC')->get()->result_array();}
 private function cashier_audit($sessionId,$staffId,$action,$from,$to,$note='',$amount=null){$this->db->insert(db_prefix().'scr_cashier_audit',['session_id'=>(int)$sessionId,'staff_id'=>(int)$staffId,'action'=>$action,'from_status'=>$from,'to_status'=>$to,'note'=>$note,'amount'=>$amount,'created_at'=>date('Y-m-d H:i:s')]);}
 public function submit_cashier($staffId,$date,$actual,$note){$r=$this->cashier_report($staffId,$date);$s=$r['session'];if($s['status']!=='open')return false;$actual=(float)$actual;$d=['status'=>'submitted','actual_cash'=>$actual,'cash_difference'=>$actual-(float)$r['totals']['cash'],'cashier_note'=>$note,'submitted_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];$ok=$this->db->where('id',(int)$s['id'])->update(db_prefix().'scr_cashier_sessions',$d);if($ok)$this->cashier_audit($s['id'],$staffId,'submit','open','submitted',$note,$actual);return $ok;}
 public function cashier_respond($staffId,$date,$response){$s=$this->cashier_session($staffId,$date,false);if(!$s||$s['status']!=='disputed'||trim((string)$response)==='')return false;$d=['status'=>'resubmitted','cashier_response'=>$response,'resubmitted_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];$ok=$this->db->where('id',(int)$s['id'])->update(db_prefix().'scr_cashier_sessions',$d);if($ok)$this->cashier_audit($s['id'],$staffId,'cashier_response','disputed','resubmitted',$response,null);return $ok;}
 public function review_cashier($id,$status,$note,$accountant,$dtype='',$damount=null){if(!in_array($status,['approved','disputed','approved_discrepancy'],true))return false;$s=$this->db->where('id',(int)$id)->get(db_prefix().'scr_cashier_sessions')->row_array();if(!$s||!in_array($s['status'],['submitted','resubmitted','system_review'],true))return false;$to=$status==='approved_discrepancy'?'approved_discrepancy':$status;$d=['status'=>$to,'accountant_note'=>$note,'updated_at'=>date('Y-m-d H:i:s')];if($status==='disputed'){$d['discrepancy_type']=$dtype?:'other';$d['discrepancy_amount']=(float)$damount;$d['approved_by']=null;$d['approved_at']=null;$d['closed_with_discrepancy']=0;}else{$d['approved_by']=(int)$accountant;$d['approved_at']=date('Y-m-d H:i:s');$d['closed_with_discrepancy']=$status==='approved_discrepancy'?1:0;if($status==='approved_discrepancy'){$d['discrepancy_type']=$dtype?:($s['discrepancy_type']?:'other');$d['discrepancy_amount']=$damount!==null?(float)$damount:(float)$s['discrepancy_amount'];}}
  $ok=$this->db->where('id',(int)$id)->update(db_prefix().'scr_cashier_sessions',$d);if($ok)$this->cashier_audit($id,$accountant,$status,$s['status'],$to,$note,$damount);return $ok;}
 public function cashier_audit_rows($sessionId){return $this->db->where('session_id',(int)$sessionId)->order_by('id','ASC')->get(db_prefix().'scr_cashier_audit')->result_array();}
 public function save_payment_mode_categories($data){scr_ensure_cashier_schema();foreach(($data['category']??[]) as $mode=>$cat){$cat=in_array($cat,['cash','pos','bank','other'],true)?$cat:'other';$row=['payment_mode'=>(string)$mode,'category'=>$cat,'account_label'=>trim((string)($data['account_label'][$mode]??'')),'updated_at'=>date('Y-m-d H:i:s')];if($this->db->where('payment_mode',(string)$mode)->get(db_prefix().'scr_payment_mode_categories')->row())$this->db->where('payment_mode',(string)$mode)->update(db_prefix().'scr_payment_mode_categories',$row);else $this->db->insert(db_prefix().'scr_payment_mode_categories',$row);}return true;}
 public function known_payment_modes(){scr_ensure_cashier_schema();$out=[];$t=db_prefix().'payment_modes';if($this->db->table_exists($t))foreach($this->db->get($t)->result_array() as $r)$out[(string)$r['id']]=['mode'=>(string)$r['id'],'label'=>$r['name']??(string)$r['id']];$pt=db_prefix().'invoicepaymentrecords';if($this->db->table_exists($pt))foreach($this->db->select('paymentmode')->distinct()->get($pt)->result_array() as $r){$m=(string)$r['paymentmode'];if(!isset($out[$m]))$out[$m]=['mode'=>$m,'label'=>$this->payment_mode_label($m)];}foreach($out as &$x){$c=$this->payment_mode_category($x['mode']);$x=array_merge($x,$c);}return array_values($out);}


 public function scr_goals_schema(){
  $t=db_prefix().'scr_monthly_goals';
  if(!$this->db->table_exists($t))$this->db->query("CREATE TABLE `{$t}` (`id` INT NOT NULL AUTO_INCREMENT,`month` CHAR(7) NOT NULL,`role` VARCHAR(12) NOT NULL,`staff_id` INT NOT NULL,`sale_type_id` INT NOT NULL DEFAULT 0,`target_amount` DECIMAL(18,2) NOT NULL DEFAULT 0,`created_at` DATETIME NULL,`updated_at` DATETIME NULL,PRIMARY KEY (`id`),UNIQUE KEY `goal_key` (`month`,`role`,`staff_id`,`sale_type_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 }
 public function scr_goal_month_status($month){
  $this->scr_goals_schema();$t=db_prefix().'scr_goal_month_closures';
  if(!$this->db->table_exists($t))$this->db->query("CREATE TABLE `{$t}` (`month` CHAR(7) NOT NULL PRIMARY KEY,`snapshot` LONGTEXT NOT NULL,`closed_at` DATETIME NOT NULL,`closed_by` INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  return $this->db->where('month',$month)->get($t)->row_array();
 }
 public function scr_goal_close_month($month,$staffId){
  if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/',$month))return false;
  if($this->scr_goal_month_status($month))return false;
  $rows=$this->scr_goals_report($month);
  return $this->db->insert(db_prefix().'scr_goal_month_closures',['month'=>$month,'snapshot'=>json_encode($rows,JSON_UNESCAPED_UNICODE),'closed_at'=>date('Y-m-d H:i:s'),'closed_by'=>(int)$staffId]);
 }
 public function scr_goal_reopen_month($month){
  $this->scr_goal_month_status($month);
  return $this->db->where('month',$month)->delete(db_prefix().'scr_goal_month_closures');
 }
 public function scr_goals_save($d){
  $this->scr_goals_schema();$month=trim((string)($d['month']??''));if($this->scr_goal_month_status($month))return ['ok'=>false,'message'=>'گزارش ماه بسته شده است'];$role=($d['role']??'seller')==='manager'?'manager':'seller';$staff=(int)($d['staff_id']??0);$type=(int)($d['sale_type_id']??0);$amount=$this->num($d['target_amount']??'');
  if(!preg_match('/^20[0-9]{2}-(0[1-9]|1[0-2])$/',$month)||!$staff||$amount===null||$amount<0)return ['ok'=>false,'message'=>'ماه، کارمند یا مبلغ هدف نامعتبر است'];
  if(!$this->db->where('staffid',$staff)->get(db_prefix().'staff')->row())return ['ok'=>false,'message'=>'کارمند یافت نشد'];
  $t=db_prefix().'scr_monthly_goals';$key=['month'=>$month,'role'=>$role,'staff_id'=>$staff,'sale_type_id'=>$type];$old=$this->db->where($key)->get($t)->row_array();$now=date('Y-m-d H:i:s');
  if($old)$this->db->where('id',$old['id'])->update($t,['target_amount'=>$amount,'updated_at'=>$now]);else $this->db->insert($t,$key+['target_amount'=>$amount,'created_at'=>$now,'updated_at'=>$now]);return ['ok'=>true];
 }
 public function scr_goals_delete($id){$this->scr_goals_schema();$goal=$this->db->where('id',(int)$id)->get(db_prefix().'scr_monthly_goals')->row_array();if(!$goal||$this->scr_goal_month_status($goal['month']))return false;return $this->db->where('id',(int)$id)->delete(db_prefix().'scr_monthly_goals');}
 public function scr_goals_copy($from,$to){
  $this->scr_goals_schema();if(!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/',$from)||!preg_match('/^20\d{2}-(0[1-9]|1[0-2])$/',$to)||$from===$to)return 0;
  $rows=$this->db->where('month',$from)->get(db_prefix().'scr_monthly_goals')->result_array();if($this->scr_goal_month_status($to))return 0;$n=0;
  foreach($rows as $r){$key=['month'=>$to,'role'=>$r['role'],'staff_id'=>$r['staff_id'],'sale_type_id'=>$r['sale_type_id']];if($this->db->where($key)->get(db_prefix().'scr_monthly_goals')->row())continue;$this->db->insert(db_prefix().'scr_monthly_goals',$key+['target_amount'=>$r['target_amount'],'created_at'=>date('Y-m-d H:i:s')]);$n++;}return $n;
 }
 public function scr_goals_report($month){
  $closed=$this->scr_goal_month_status($month);if($closed){$snapshot=json_decode($closed['snapshot'],true);return is_array($snapshot)?$snapshot:[];}
  $this->scr_goals_schema();$t=db_prefix().'scr_monthly_goals';$goals=$this->db->select("g.*,CONCAT(s.firstname,' ',s.lastname) staff_name,st.name sale_type_name",false)->from($t.' g')->join(db_prefix().'staff s','s.staffid=g.staff_id','left')->join(db_prefix().'scr_sale_types st','st.legacy_id=g.sale_type_id OR st.id=g.sale_type_id','left')->where('g.month',$month)->order_by('g.role','ASC')->order_by('g.staff_id','ASC')->get()->result_array();
  $start=$month.'-01';$end=date('Y-m-d',strtotime($start.' +1 month'));$p=db_prefix().'invoicepaymentrecords';$i=db_prefix().'invoices';$totals=[];
  if($this->db->table_exists($p)&&$this->db->table_exists($i)){
   $sf=$this->db->field_exists('sale_agent',$i)?'sale_agent':'addedfrom';$tf=$this->db->field_exists('invoice_type',$i)?'invoice_type':($this->db->field_exists('sale_type_id',$i)?'sale_type_id':null);
   $select='i.'.$sf.' seller_id,'.($tf?'i.'.$tf:'0').' sale_type_id,SUM(p.amount) amount';
   $q=$this->db->select($select,false)->from($p.' p')->join($i.' i','i.id=p.invoiceid')->where('p.date >=',$start)->where('p.date <',$end)->where('p.amount >',0);
   if($this->db->field_exists('status',$i))$q->where('i.status !=',5);
   foreach($q->group_by(['i.'.$sf]+($tf?['i.'.$tf]:[]))->get()->result_array() as $r){$sid=(int)$r['seller_id'];$type=(int)$r['sale_type_id'];if($sid>0)$totals[$sid][$type]=($totals[$sid][$type]??0)+(float)$r['amount'];}
  }
  $hier=[];$h=db_prefix().'scr_sales_hierarchy';if($this->db->table_exists($h))foreach($this->db->where('active',1)->get($h)->result_array() as $r)$hier[(int)$r['manager_staff_id']][(int)$r['seller_staff_id']]=true;
  foreach($goals as &$g){$staff=(int)$g['staff_id'];$type=(int)$g['sale_type_id'];$members=$g['role']==='manager'?array_keys($hier[$staff]??[]):[$staff];$actual=0;
   foreach(array_unique($members) as $sid){if($type===0)$actual+=array_sum($totals[$sid]??[]);else $actual+=(float)($totals[$sid][$type]??0);}
   $g['actual_amount']=$actual;$g['percent_achieved']=(float)$g['target_amount']>0?round(100*$actual/(float)$g['target_amount'],1):0;
  }unset($g);return $goals;
 }
}
