<?php defined('BASEPATH') or exit('No direct script access allowed');
class Sales_delivery_flow_model extends App_Model
{
    private function permit_table(){ return db_prefix().'sdf_exit_permits_v2'; }
    public function ensure_schema(){
        $t=$this->permit_table();
        if(!$this->db->table_exists($t)){
            $sql='CREATE TABLE IF NOT EXISTS `'.$t.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`status` VARCHAR(30) NOT NULL DEFAULT "approved",`invoice_date` VARCHAR(32) NULL,`permit_date` VARCHAR(32) NULL,`warehouse_cutoff_id` INT NOT NULL DEFAULT 0,`note` TEXT NULL,`created_by` INT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `invoice_id` (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$this->db->char_set;
            $this->db->query($sql);
        }
        // Per-invoice-item storage location, retained independently of Warehouse internals.
        $locTable=db_prefix().'sdf_item_locations';
        if(!$this->db->table_exists($locTable)){
            $this->db->query('CREATE TABLE IF NOT EXISTS `'.$locTable.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`itemable_id` INT NOT NULL,`location` VARCHAR(255) NOT NULL DEFAULT "",`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `invoice_item` (`invoice_id`,`itemable_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$this->db->char_set);
        }
        // Delivery receipt schema. Module replacement does not re-run Perfex activation hooks,
        // therefore ensure_schema must also CREATE the receipt table when upgrading an already-active module.
        $ct=db_prefix().'sdf_customer_deliveries';
        if(!$this->db->table_exists($ct)){
            $sql='CREATE TABLE IF NOT EXISTS `'.$ct.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`goods_delivery_id` INT NULL,`recipient_name` VARCHAR(191) NULL,`recipient_phone` VARCHAR(80) NULL,`delivery_date` DATE NOT NULL,`delivery_method` VARCHAR(120) NULL,`carrier_company` VARCHAR(191) NULL,`carrier_contact` VARCHAR(120) NULL,`carrier_driver` VARCHAR(191) NULL,`carrier_vehicle` VARCHAR(120) NULL,`carrier_tracking` VARCHAR(191) NULL,`note` TEXT NULL,`created_by` INT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `invoice_id` (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$this->db->char_set;
            $this->db->query($sql);
        }
        if($this->db->table_exists($ct)){
            $cols=['goods_delivery_id'=>'INT NULL','recipient_name'=>'VARCHAR(191) NULL','recipient_phone'=>'VARCHAR(80) NULL','delivery_date'=>'DATE NULL','delivery_method'=>'VARCHAR(120) NULL','carrier_company'=>'VARCHAR(191) NULL','carrier_contact'=>'VARCHAR(120) NULL','carrier_driver'=>'VARCHAR(191) NULL','carrier_vehicle'=>'VARCHAR(120) NULL','carrier_tracking'=>'VARCHAR(191) NULL','note'=>'TEXT NULL','created_by'=>'INT NULL','created_at'=>'DATETIME NULL'];
            foreach($cols as $name=>$def){ if(!$this->db->field_exists($name,$ct)) $this->db->query('ALTER TABLE `'.$ct.'` ADD `'.$name.'` '.$def); }
        }
        // Product return / warehouse re-entry schema (0.2.17).
        $rt=db_prefix().'sdf_returns';
        if(!$this->db->table_exists($rt)) $this->db->query('CREATE TABLE `'.$rt.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_id` INT NOT NULL,`return_no` VARCHAR(60) NULL,`return_date` DATE NOT NULL,`reason` VARCHAR(255) NULL,`note` TEXT NULL,`total_amount` DECIMAL(18,4) NOT NULL DEFAULT 0,`created_by` INT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `invoice_id` (`invoice_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$this->db->char_set);
        $rlt=db_prefix().'sdf_return_lines';
        if(!$this->db->table_exists($rlt)) $this->db->query('CREATE TABLE `'.$rlt.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`return_id` INT NOT NULL,`invoice_id` INT NOT NULL,`itemable_id` INT NOT NULL,`commodity_id` INT NOT NULL DEFAULT 0,`warehouse_id` INT NOT NULL DEFAULT 0,`description` TEXT NULL,`qty` DECIMAL(18,4) NOT NULL DEFAULT 0,`unit_price` DECIMAL(18,4) NOT NULL DEFAULT 0,`discount_amount` DECIMAL(18,4) NOT NULL DEFAULT 0,`line_total` DECIMAL(18,4) NOT NULL DEFAULT 0,PRIMARY KEY (`id`),KEY `return_id` (`return_id`),KEY `invoice_id` (`invoice_id`),KEY `itemable_id` (`itemable_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$this->db->char_set);
        $rit=db_prefix().'sdf_return_inventory_log';
        if(!$this->db->table_exists($rit)) $this->db->query('CREATE TABLE `'.$rit.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`return_id` INT NOT NULL,`return_line_id` INT NOT NULL,`warehouse_id` INT NOT NULL,`commodity_id` INT NOT NULL,`qty` DECIMAL(18,4) NOT NULL,`inventory_manage_id` INT NULL,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),KEY `return_id` (`return_id`)) ENGINE=InnoDB DEFAULT CHARSET='.$this->db->char_set);
        // Sales Delivery Flow is the approval gate. Prevent Warehouse from auto-creating delivery notes on invoice creation/update.
        if(function_exists('update_option')){ update_option('auto_create_goods_delivery', 0); }
        // Repair dates saved by older SDF builds after an unintended calendar conversion.
        if($this->db->table_exists($t)){
            $rows=$this->db->select('id,invoice_id,invoice_date,permit_date,created_at')->get($t)->result_array();
            foreach($rows as $r){
                $bad=false;
                foreach(['invoice_date','permit_date'] as $f){ if(preg_match('/^(\d{4})-/',(string)($r[$f]??''),$m) && (int)$m[1]>2100)$bad=true; }
                if($bad){
                    $inv=$this->db->select('date')->where('id',(int)$r['invoice_id'])->get(db_prefix().'invoices')->row_array();
                    $raw=$this->native_invoice_date($inv['date']??'');
                    $opDate=!empty($r['created_at']) ? date('Y-m-d',strtotime($r['created_at'])) : date('Y-m-d');
                    if($raw)$this->db->where('id',(int)$r['id'])->update($t,['invoice_date'=>$raw,'permit_date'=>$opDate]);
                } elseif(!empty($r['invoice_date']) && !empty($r['permit_date']) && $r['permit_date']===$r['invoice_date'] && !empty($r['created_at'])) {
                    // 0.2.10 and older copied invoice date into permit date. Permit date is operational date.
                    $opDate=date('Y-m-d',strtotime($r['created_at']));
                    if($opDate)$this->db->where('id',(int)$r['id'])->update($t,['permit_date'=>$opDate]);
                }
            }
        }
        return $this->db->table_exists($t);
    }
    private function native_invoice_date($v){
        if(!$v)return '';
        $v=trim((string)$v);
        if(is_numeric($v) && (int)$v>1000000000) $v=date('Y-m-d',(int)$v);
        if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})/',$v,$m)) return $v;
        $y=(int)$m[1]; $mo=(int)$m[2]; $d=(int)$m[3];
        // A previous customization on this installation has, in some reads, treated an already-Gregorian
        // invoice date (e.g. 2026-..) as Jalali and converted it again, yielding years around 2647.
        // For those impossible invoice years, invert that accidental Jalali->Gregorian conversion.
        if($y>2100){
            $j=$this->gregorian_to_jalali($y,$mo,$d);
            if($j && $j[0]>=1900 && $j[0]<=2100) return sprintf('%04d-%02d-%02d',$j[0],$j[1],$j[2]);
        }
        return sprintf('%04d-%02d-%02d',$y,$mo,$d);
    }
    private function gregorian_to_jalali($gy,$gm,$gd){
        $gdm=[0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2=($gm>2)?($gy+1):$gy;
        $days=355666 + (365*$gy) + intdiv($gy2+3,4) - intdiv($gy2+99,100) + intdiv($gy2+399,400) + $gd + $gdm[$gm-1];
        $jy=-1595 + 33*intdiv($days,12053); $days%=12053;
        $jy += 4*intdiv($days,1461); $days%=1461;
        if($days>365){$jy += intdiv($days-1,365); $days=($days-1)%365;}
        if($days<186){$jm=1+intdiv($days,31);$jd=1+($days%31);}else{$jm=7+intdiv($days-186,30);$jd=1+(($days-186)%30);}
        return [$jy,$jm,$jd];
    }
    private function base_invoices(){return $this->db->select('i.id,i.number,i.clientid,i.date,i.total,i.status,i.currency,c.company,c.phonenumber,c.address,c.city,c.state,c.zip')->from(db_prefix().'invoices i')->join(db_prefix().'clients c','c.userid=i.clientid','left')->order_by('i.id','DESC')->limit(250)->get()->result_array();}
    public function delivery_queue(){return $this->decorate_batch($this->base_invoices());}
    public function customer_delivery_queue(){return array_values(array_filter($this->decorate_batch($this->base_invoices()),function($r){return $r['customer_count']>0;}));}
    public function historical_returns(){
        $t=db_prefix().'sdf_historical_returns';
        return $this->db->table_exists($t)?$this->db->order_by('id','DESC')->get($t)->result_array():[];
    }
    public function save_historical_return($group){
        $key=trim((string)($group['invoice_key']??''));
        if($key===''||strlen($key)>120)return ['ok'=>false,'error'=>'شماره فاکتور معتبر نیست.'];
        $items=$group['items']??[];if(!is_array($items)||!count($items))return ['ok'=>false,'error'=>'فاکتور فاقد اقلام است.'];
        $sum=0.0;$lines=[];
        foreach($items as $it){
            $qty=(float)($it['qty']??0);$rate=(float)($it['historical_rate']??$it['rate']??0);
            $net=isset($it['historical_net'])?(float)$it['historical_net']:($qty*$rate*(1-(float)($it['discount_percent']??0)/100));
            $sum+=$net;
            $lines[]=['source_row'=>(int)($it['source_row']??0),'commodity_id'=>(int)($it['item_id']??0),'item_code'=>(string)($it['item_code']??''),'description'=>(string)($it['source_product_name']??$it['description']??''),'qty'=>$qty,'unit_price'=>$rate,'line_total'=>$net];
        }
        if($sum>=-0.0001)return ['ok'=>false,'error'=>'جمع خالص اقلام منفی نیست؛ ثبت مرجوعی تاریخی مجاز نیست.'];
        $t=db_prefix().'sdf_historical_returns';$lt=db_prefix().'sdf_historical_return_lines';
        if(!$this->db->table_exists($t)){
            $this->db->query('CREATE TABLE IF NOT EXISTS `'.$t.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`invoice_key` VARCHAR(120) NOT NULL,`customer_id` INT NOT NULL DEFAULT 0,`invoice_date` DATE NOT NULL,`total_amount` DECIMAL(18,4) NOT NULL,`source` VARCHAR(60) NOT NULL DEFAULT \'bulk_import\',`created_by` INT NOT NULL DEFAULT 0,`created_at` DATETIME NOT NULL,PRIMARY KEY (`id`),UNIQUE KEY `invoice_key` (`invoice_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        if(!$this->db->table_exists($lt)){
            $this->db->query('CREATE TABLE IF NOT EXISTS `'.$lt.'` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,`return_id` INT UNSIGNED NOT NULL,`source_row` INT NOT NULL DEFAULT 0,`commodity_id` INT NOT NULL DEFAULT 0,`item_code` VARCHAR(120) NULL,`description` TEXT NULL,`qty` DECIMAL(18,4) NOT NULL DEFAULT 0,`unit_price` DECIMAL(18,4) NOT NULL DEFAULT 0,`line_total` DECIMAL(18,4) NOT NULL DEFAULT 0,PRIMARY KEY (`id`),KEY `return_id` (`return_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        if(!$this->db->table_exists($t)||!$this->db->table_exists($lt))return ['ok'=>false,'error'=>'جدول ثبت سوابق مرجوعی ایجاد نشد.'];
        $this->db->trans_begin();
        $ok=$this->db->insert($t,['invoice_key'=>$key,'customer_id'=>(int)($group['customer_id']??0),'invoice_date'=>(string)($group['date']??date('Y-m-d')),'total_amount'=>$sum,'source'=>'bulk_import','created_by'=>(int)get_staff_user_id(),'created_at'=>date('Y-m-d H:i:s')]);
        $id=$ok?(int)$this->db->insert_id():0;
        if($id){foreach($lines as $line){$line['return_id']=$id;if(!$this->db->insert($lt,$line)){$ok=false;break;}}}
        if(!$ok||$this->db->trans_status()===false){$this->db->trans_rollback();return ['ok'=>false,'error'=>'ثبت مرجوعی ناموفق بود یا شماره فاکتور تکراری است.'];}
        $this->db->trans_commit();return ['ok'=>true,'id'=>$id];
    }
    public function returns_queue(){
        $rows=$this->customer_delivery_queue();$rt=db_prefix().'sdf_returns';$lt=db_prefix().'sdf_return_lines';
        foreach($rows as &$r){$r['return_count']=0;$r['return_qty']=0;$r['return_state']='بدون مرجوعی';if($this->db->table_exists($rt)){$x=$this->db->select('COUNT(*) cnt',false)->where('invoice_id',(int)$r['id'])->get($rt)->row_array();$r['return_count']=(int)($x['cnt']??0);}if($this->db->table_exists($lt)){$x=$this->db->select('SUM(qty) q',false)->where('invoice_id',(int)$r['id'])->get($lt)->row_array();$r['return_qty']=(float)($x['q']??0);}if($r['return_qty']>0){$ctx=$this->invoice_context($r['id']);$delivered=0;foreach(($ctx['items']??[]) as $it)$delivered+=(float)($it['qty']??0);$r['return_state']=($delivered>0 && $r['return_qty']>=$delivered-0.0001)?'مرجوعی کامل':'مرجوعی جزئی';}}
        unset($r);return $rows;
    }
    private function decorate_batch($rows){
        if(!$rows)return [];$ids=array_map(function($r){return (int)$r['id'];},$rows);$permit=[];$wh=[];$cust=[];$paid=[];
        $pt=$this->permit_table(); if($this->db->table_exists($pt)){foreach($this->db->select('invoice_id,MAX(id) permit_id,MAX(warehouse_cutoff_id) warehouse_cutoff_id,COUNT(*) cnt',false)->where_in('invoice_id',$ids)->group_by('invoice_id')->get($pt)->result_array() as $r)$permit[(int)$r['invoice_id']]=$r;}
        $wt=db_prefix().'goods_delivery'; if($this->db->table_exists($wt)){foreach($this->db->select('invoice_id,id')->where_in('invoice_id',$ids)->get($wt)->result_array() as $r){$iid=(int)$r['invoice_id'];if(empty($permit[$iid]))continue;$cut=(int)$permit[$iid]['warehouse_cutoff_id'];if((int)$r['id']>$cut)$wh[$iid]=($wh[$iid]??0)+1;}}
        $ct=db_prefix().'sdf_customer_deliveries'; if($this->db->table_exists($ct)){foreach($this->db->select('invoice_id,COUNT(*) cnt',false)->where_in('invoice_id',$ids)->group_by('invoice_id')->get($ct)->result_array() as $r)$cust[(int)$r['invoice_id']]=(int)$r['cnt'];}
        $payt=db_prefix().'invoicepaymentrecords'; if($this->db->table_exists($payt)){foreach($this->db->select('invoiceid,SUM(amount) paid',false)->where_in('invoiceid',$ids)->group_by('invoiceid')->get($payt)->result_array() as $r)$paid[(int)$r['invoiceid']]=(float)$r['paid'];}
        foreach($rows as &$r){$id=(int)$r['id'];$r['display_date']=$this->native_invoice_date($r['date']);$r['has_permit']=isset($permit[$id]);$r['warehouse_count']=$wh[$id]??0;$r['customer_count']=$cust[$id]??0;$r['paid']=$paid[$id]??0;$r['remaining']=max(0,(float)$r['total']-$r['paid']);$r['payment_state']=$r['remaining']<=0.01?'تسویه کامل':($r['paid']>0?'پرداخت جزئی':'پرداخت نشده');$r['delivery_state']=$r['customer_count']?'تحویل مشتری':($r['warehouse_count']?'خارج‌شده / در حال ارسال':($r['has_permit']?'مجاز به خروج':'در انتظار مجوز'));}unset($r);return $rows;
    }
    private function parse_product_area($raw){
        $s=trim((string)$raw);
        $s=strtr($s,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٫'=>'.','،'=>',']);
        $s=str_replace(['×','X','x','*','✕','ـ'],'x',$s);
        $s=preg_replace('/\s+/u','',$s);
        if(preg_match('/([0-9]+(?:\.[0-9]+)?)x([0-9]+(?:\.[0-9]+)?)/',$s,$m)) return (float)$m[1]*(float)$m[2];
        return false;
    }
    private function resolve_item_id($it){
        foreach(['itemItemId','item_id','itemid','product_id','commodity_id'] as $f){
            if(isset($it[$f]) && is_numeric($it[$f]) && (int)$it[$f]>0) return (int)$it[$f];
        }
        // Native Perfex itemable does not necessarily retain the catalogue item id.
        // On this installation the invoice description is the system-generated product name,
        // so recover the product deterministically from tblitems instead of guessing a price.
        $desc=trim((string)($it['description']??'')); if($desc==='') return 0;
        $t=db_prefix().'items'; if(!$this->db->table_exists($t)) return 0;
        $fields=$this->db->list_fields($t);
        foreach(['description','commodity_code','long_description'] as $f){
            if(!in_array($f,$fields,true)) continue;
            $r=$this->db->select('id')->where($f,$desc)->order_by('id','DESC')->limit(1)->get($t)->row_array();
            if(!empty($r['id'])) return (int)$r['id'];
        }
        // Imported invoice rows often contain the catalogue code only inside the generated
        // Persian/Arabic description: "رقم المنتج 441". Resolve that code explicitly.
        if(preg_match('/(?:رقم\s*المنتج|کد\s*کالا|كد\s*المنتج)\s*[:：-]?\s*([A-Za-z0-9._-]+)/u',$desc,$m)){
            $code=trim($m[1]);
            foreach(['commodity_code','sku','code'] as $f){
                if(!in_array($f,$fields,true)) continue;
                $r=$this->db->select('id')->where($f,$code)->order_by('id','DESC')->limit(1)->get($t)->row_array();
                if(!empty($r['id'])) return (int)$r['id'];
            }
        }
        return 0;
    }
    private function resolve_item_rate($it,$invoice){
        $rate=(float)($it['rate']??0); if($rate>0)return $rate;
        $itemId=$this->resolve_item_id($it); if($itemId<=0)return 0.0;
        $saleType=(int)($invoice['invoice_type']??($invoice['sale_type_id']??0));
        $p=db_prefix();
        // Use the exact pricing source used by Sales Commercial Rules: itemid + sale_type_id.
        $t=$p.'item_sale_type_prices';
        if($saleType>0 && $this->db->table_exists($t)){
            $r=$this->db->where('itemid',$itemId)->where('sale_type_id',$saleType)->order_by('id','DESC')->get($t)->row_array();
            if($r && isset($r['price'])){$v=preg_replace('/[^0-9.\-]/','',str_replace(',','',(string)$r['price']));if(is_numeric($v)&&(float)$v>0)return (float)$v;}
        }
        // Same fallback as Sales Commercial Rules: group + product size + active SQM tariff.
        $itTable=$p.'items'; $sz=$p.'ware_size_type'; $rt=$p.'product_sqm_price_rules';
        if($saleType>0 && $this->db->table_exists($itTable) && $this->db->table_exists($rt)){
            $this->db->select('i.group_id,i.size_id',false)->from($itTable.' i');
            if($this->db->table_exists($sz) && $this->db->field_exists('size_id',$itTable)) $this->db->select('s.size_name',false)->join($sz.' s','s.size_type_id=i.size_id','left');
            else $this->db->select("'' AS size_name",false);
            $item=$this->db->where('i.id',$itemId)->get()->row_array();
            if($item && !empty($item['group_id'])){
                $area=$this->parse_product_area($item['size_name']??'');
                if($area!==false && $area>0){
                    $ruleKey=(string)$saleType; $stt=$p.'scr_sale_types';
                    if($this->db->table_exists($stt)){$sr=$this->db->group_start()->where('id',$saleType)->or_where('legacy_id',$saleType)->group_end()->order_by('id','ASC')->get($stt)->row_array();if($sr && isset($sr['legacy_id']) && (int)$sr['legacy_id']>0)$ruleKey=(string)(int)$sr['legacy_id'];}
                    $pricingDate=$this->native_invoice_date($invoice['date']??''); if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$pricingDate))$pricingDate=date('Y-m-d');
                    $this->db->from($rt)->where('group_id',(int)$item['group_id'])->where('sale_type_key',$ruleKey)->where('active',1)
                        ->group_start()->where('valid_from <=',$pricingDate)->or_where('valid_from IS NULL',null,false)->or_where('valid_from','')->group_end()
                        ->group_start()->where('valid_to >=',$pricingDate)->or_where('valid_to IS NULL',null,false)->or_where('valid_to','')->group_end()
                        ->order_by('valid_from','DESC')->order_by('id','DESC');
                    $rule=$this->db->get()->row_array(); if($rule && isset($rule['price_per_sqm']) && (float)$rule['price_per_sqm']>0)return round($area*(float)$rule['price_per_sqm'],4);
                }
            }
        }
        // Last safe fallback: native product rate (some imported products intentionally have zero here).
        if($this->db->table_exists($itTable)){$r=$this->db->select('rate')->where('id',$itemId)->get($itTable)->row_array();if((float)($r['rate']??0)>0)return (float)$r['rate'];}
        return 0.0;
    }

    private function normalize_invoice_items($items,$invoice){
        foreach($items as &$it){
            $qty=(float)($it['qty']??0);
            $rate=$this->resolve_item_rate($it,$invoice);
            $it['rate']=$rate;
            $it['line_subtotal']=$qty*$rate;
            $dp=0.0;$da=0.0;
            foreach(['itemPercent','discount_percent','discount_percentage','discount'] as $f){if(isset($it[$f])&&is_numeric($it[$f])&&(float)$it[$f]!=0){$dp=(float)$it[$f];break;}}
            foreach(['discount_amount','discount_total'] as $f){if(isset($it[$f])&&is_numeric($it[$f])&&(float)$it[$f]!=0){$da=(float)$it[$f];break;}}
            if($da<=0&&$dp>0)$da=$it['line_subtotal']*$dp/100;
            $it['discount_percent_value']=$dp;$it['discount_amount_value']=$da;$it['line_total']=max(0,$it['line_subtotal']-$da);
        }
        unset($it); return $items;
    }

    private function invoice_delivery_method($invoiceId){
        $cf=db_prefix().'customfields'; $cv=db_prefix().'customfieldsvalues';
        if(!$this->db->table_exists($cf)||!$this->db->table_exists($cv)) return '';
        $fields=$this->db->list_fields($cf);
        $this->db->select('cf.id,cf.name'.(in_array('slug',$fields,true)?',cf.slug':''))->from($cf.' cf')->where('cf.fieldto','invoice');
        $defs=$this->db->get()->result_array();
        foreach($defs as $d){
            $name=mb_strtolower(trim((string)($d['name']??'')),'UTF-8'); $slug=mb_strtolower(trim((string)($d['slug']??'')),'UTF-8');
            $match=($slug==='c_delivery_method'||strpos($slug,'delivery_method')!==false||strpos($name,'روش تحویل')!==false||strpos($name,'تحویل کالا')!==false);
            if(!$match) continue;
            $r=$this->db->select('value')->where('relid',(int)$invoiceId)->where('fieldid',(int)$d['id'])->order_by('id','DESC')->limit(1)->get($cv)->row_array();
            if(!empty($r['value'])) return trim((string)$r['value']);
        }
        return '';
    }

    public function invoice_context($id){
        $i=$this->db->select('i.*,c.company,c.phonenumber,c.address,c.city,c.state,c.zip')->from(db_prefix().'invoices i')->join(db_prefix().'clients c','c.userid=i.clientid','left')->where('i.id',(int)$id)->get()->row_array();if(!$i)return [];
        $p=$this->db->select_sum('amount','paid')->where('invoiceid',(int)$id)->get(db_prefix().'invoicepaymentrecords')->row_array();$i['paid']=(float)($p['paid']??0);$i['remaining']=max(0,(float)$i['total']-$i['paid']);$i['payment_state']=$i['remaining']<=0.01?'تسویه کامل':($i['paid']>0?'پرداخت جزئی':'پرداخت نشده');$i['display_date']=$this->native_invoice_date($i['date']);$rawItems=$this->db->where('rel_id',(int)$id)->where('rel_type','invoice')->order_by('item_order','ASC')->get(db_prefix().'itemable')->result_array();$i['items']=$this->normalize_invoice_items($rawItems,$i);$i['installment']=$this->installment_summary($id);$i['delivery_method']=$this->invoice_delivery_method($id);return $i;
    }
    public function item_locations($invoiceId){
        $table=db_prefix().'sdf_item_locations';
        if(!$this->db->table_exists($table))return [];
        $rows=$this->db->select('itemable_id,location')->where('invoice_id',(int)$invoiceId)->get($table)->result_array();
        $result=[];foreach($rows as $r)$result[(int)$r['itemable_id']]=$r['location'];return $result;
    }
    private function save_item_locations($invoiceId,$locations){
        if(!is_array($locations))return false;
        $table=db_prefix().'sdf_item_locations';
        if(!$this->db->table_exists($table))return false;
        $valid=$this->db->select('id')->where('rel_id',(int)$invoiceId)->where('rel_type','invoice')->get(db_prefix().'itemable')->result_array();
        foreach($valid as $row){
            $itemId=(int)$row['id'];
            $value=isset($locations[$itemId]) && is_scalar($locations[$itemId]) ? trim((string)$locations[$itemId]) : '';
            $value=mb_substr($value,0,255,'UTF-8');
            $existing=$this->db->where('invoice_id',(int)$invoiceId)->where('itemable_id',$itemId)->get($table)->row_array();
            if($existing){$this->db->where('id',(int)$existing['id'])->update($table,['location'=>$value]);}
            else{$this->db->insert($table,['invoice_id'=>(int)$invoiceId,'itemable_id'=>$itemId,'location'=>$value,'created_at'=>date('Y-m-d H:i:s')]);}
        }
        return true;
    }
    public function permit($id){$t=$this->permit_table();if(!$this->db->table_exists($t))return [];$r=$this->db->where('invoice_id',(int)$id)->order_by('id','DESC')->get($t)->row_array();return $r?:[];}
    public function create_permit($id,$note='',$permit_date=null,$locations=[]){
        if(!$this->ensure_schema())return false;if($this->permit($id))return true;
        $inv=$this->db->select('date')->where('id',(int)$id)->get(db_prefix().'invoices')->row_array();if(!$inv)return false;
        $cut=0;$wt=db_prefix().'goods_delivery';if($this->db->table_exists($wt)){$row=$this->db->select_max('id','mx')->where('invoice_id',(int)$id)->get($wt)->row_array();$cut=(int)($row['mx']??0);}
        $invoiceDate=$this->native_invoice_date($inv['date']??'');
        $data=['invoice_id'=>(int)$id,'status'=>'approved','invoice_date'=>$invoiceDate,'permit_date'=>date('Y-m-d'),'warehouse_cutoff_id'=>$cut,'note'=>$note,'created_by'=>get_staff_user_id(),'created_at'=>date('Y-m-d H:i:s')];
        $ok=$this->db->insert($this->permit_table(),$data);if(!$ok)return false;
        $this->save_item_locations($id,$locations);return !empty($this->permit($id));
    }
    public function warehouse_deliveries($id){
        $t=db_prefix().'goods_delivery';if(!$this->db->table_exists($t))return [];$permit=$this->permit($id);if(!$permit)return [];$cut=(int)($permit['warehouse_cutoff_id']??0);
        $rows=$this->db->where('invoice_id',(int)$id)->where('id >',$cut)->order_by('id','DESC')->get($t)->result_array();
        $inv=$this->db->select('date')->where('id',(int)$id)->get(db_prefix().'invoices')->row_array();
        $invoiceDate=$this->native_invoice_date($inv['date']??'');
        foreach($rows as &$r){
            $raw=$r['date_c']??($r['date_add']??($r['created_at']??''));
            $display=$this->native_invoice_date($raw);
            // Legacy Warehouse auto-create copied invoice date into exit date. Operational exit cannot predate permit.
            if($display===$invoiceDate && !empty($permit['permit_date'])) $display=$this->native_invoice_date($permit['permit_date']);
            $r['display_date']=$display;
        }unset($r);return $rows;
    }
    public function installment_summary($invoiceId){
        $pt=db_prefix().'sir_invoice_plans';$lt=db_prefix().'sir_invoice_plan_lines';
        if(!$this->db->table_exists($pt)||!$this->db->table_exists($lt))return ['is_installment'=>false];
        $p=$this->db->where('invoice_id',(int)$invoiceId)->where_in('status',['active','completed'])->get($pt)->row_array();
        if(!$p)return ['is_installment'=>false];
        $lines=$this->db->where('plan_id',(int)$p['id'])->order_by('sequence_no','ASC')->get($lt)->result_array();$inst=[];$paidCount=0;$paidAmount=0;
        foreach($lines as $l){if(($l['line_type']??'')!=='installment')continue;$l['display_due_date']=$this->native_invoice_date($l['due_date']??'');$inst[]=$l;if(($l['status']??'')==='paid')$paidCount++;$paidAmount+=(float)($l['paid_amount']??0);}
        $snap=json_decode((string)($p['rule_snapshot']??''),true)?:[];
        return ['is_installment'=>true,'title'=>$snap['title']??'برنامه اقساط','status'=>$p['status'],'down_payment'=>(float)($p['down_payment_total']??0),'installments_total'=>(float)($p['installments_total']??0),'financed_total'=>(float)($snap['financed_total']??$p['invoice_total']),'installment_count'=>count($inst),'paid_installment_count'=>$paidCount,'paid_installment_amount'=>$paidAmount,'installments'=>$inst];
    }
    public function warehouse_delivery_by_id($invoiceId,$deliveryId=0){$rows=$this->warehouse_deliveries($invoiceId);if(!$rows)return [];if(!$deliveryId)return $rows[0];foreach($rows as $r)if((int)$r['id']===(int)$deliveryId)return $r;return [];}

    public function create_warehouse_delivery($id){
        $permit=$this->permit($id); if(!$permit)return ['ok'=>false,'message'=>'ابتدا باید مجوز خروج صادر شود.'];
        $t=db_prefix().'goods_delivery'; if(!$this->db->table_exists($t))return ['ok'=>false,'message'=>'جدول حواله خروج Warehouse در دسترس نیست.'];
        $before=$this->db->select_max('id','mx')->where('invoice_id',(int)$id)->get($t)->row_array(); $beforeId=(int)($before['mx']??0);
        try{
            $CI=&get_instance(); $CI->load->model('warehouse/warehouse_model');
            if(!isset($CI->warehouse_model) || !method_exists($CI->warehouse_model,'auto_create_goods_delivery_with_invoice')) return ['ok'=>false,'message'=>'متد رسمی ایجاد حواله Warehouse در دسترس نیست.'];
            // Call the same official Warehouse model method used by its after_invoice_added hook.
            $CI->warehouse_model->auto_create_goods_delivery_with_invoice((int)$id);
        }catch(Throwable $e){ return ['ok'=>false,'message'=>'Warehouse: '.$e->getMessage()]; }
        $after=$this->db->select_max('id','mx')->where('invoice_id',(int)$id)->get($t)->row_array(); $afterId=(int)($after['mx']??0);
        if($afterId>$beforeId && $afterId>(int)($permit['warehouse_cutoff_id']??0)){
            // Warehouse's native auto-create may copy invoice date. Override only the newly-created exit document.
            $fields=$this->db->list_fields($t); $stamp=[];
            if(in_array('date_c',$fields,true)) $stamp['date_c']=date('Y-m-d');
            if(in_array('date_add',$fields,true)) $stamp['date_add']=date('Y-m-d H:i:s');
            if(in_array('created_at',$fields,true)) $stamp['created_at']=date('Y-m-d H:i:s');
            if($stamp) $this->db->where('id',$afterId)->update($t,$stamp);
            return ['ok'=>true,'id'=>$afterId];
        }
        // Some Warehouse builds may reuse/update an existing invoice delivery. In that case advance the permit cutoff only if a post-permit row is visible.
        $rows=$this->warehouse_deliveries($id); if(!empty($rows))return ['ok'=>true,'id'=>(int)$rows[0]['id']];
        return ['ok'=>false,'message'=>'Warehouse حواله جدیدی برای این فاکتور ایجاد نکرد.'];
    }
    public function customer_deliveries($id){$t=db_prefix().'sdf_customer_deliveries';return $this->db->table_exists($t)?$this->db->where('invoice_id',(int)$id)->order_by('id','DESC')->get($t)->result_array():[];}
    public function customer_delivery_by_id($invoiceId,$deliveryId){$t=db_prefix().'sdf_customer_deliveries';if(!$this->db->table_exists($t))return [];$r=$this->db->where('invoice_id',(int)$invoiceId)->where('id',(int)$deliveryId)->get($t)->row_array();return $r?:[];}
    public function add_customer_delivery($id,$data){
        $t=db_prefix().'sdf_customer_deliveries';
        if(!$this->db->table_exists($t)) return ['ok'=>false,'error'=>'جدول رسید تحویل مشتری وجود ندارد.'];
        // Browser/localized date controls can post YYYY/MM/DD or DD/MM/YYYY. MySQL DATE must receive YYYY-MM-DD.
        $rawDate=trim((string)($data['delivery_date']??''));
        $rawDate=strtr($rawDate,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']);
        $rawDate=str_replace('/','-',$rawDate);
        if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/',$rawDate,$m)){
            $data['delivery_date']=sprintf('%04d-%02d-%02d',(int)$m[1],(int)$m[2],(int)$m[3]);
        } elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/',$rawDate,$m)){
            $data['delivery_date']=sprintf('%04d-%02d-%02d',(int)$m[3],(int)$m[2],(int)$m[1]);
        } else {
            $data['delivery_date']=date('Y-m-d');
        }
        $data['invoice_id']=(int)$id;
        $data['created_by']=get_staff_user_id();
        $data['created_at']=date('Y-m-d H:i:s');
        // Be backward-compatible with installations whose existing table has not yet gained every 0.2.13 carrier field.
        $fields=$this->db->list_fields($t);
        $insert=[];
        foreach($data as $k=>$v){ if(in_array($k,$fields,true)) $insert[$k]=$v; }
        if(!$this->db->insert($t,$insert)){
            $e=$this->db->error();
            return ['ok'=>false,'error'=>!empty($e['message'])?$e['message']:'خطای دیتابیس هنگام ثبت رسید تحویل.'];
        }
        $newId=(int)$this->db->insert_id();
        return $newId>0 ? ['ok'=>true,'id'=>$newId] : ['ok'=>false,'error'=>'شناسه رسید تحویل ایجاد نشد.'];
    }
    public function invoice_returns($invoiceId){$t=db_prefix().'sdf_returns';return $this->db->table_exists($t)?$this->db->where('invoice_id',(int)$invoiceId)->order_by('id','DESC')->get($t)->result_array():[];}
    public function return_by_id($invoiceId,$returnId){$t=db_prefix().'sdf_returns';if(!$this->db->table_exists($t))return [];$r=$this->db->where('invoice_id',(int)$invoiceId)->where('id',(int)$returnId)->get($t)->row_array();return $r?:[];}
    public function return_lines($returnId){$t=db_prefix().'sdf_return_lines';return $this->db->table_exists($t)?$this->db->where('return_id',(int)$returnId)->order_by('id','ASC')->get($t)->result_array():[];}
    private function returned_qty_map($invoiceId){$t=db_prefix().'sdf_return_lines';$m=[];if(!$this->db->table_exists($t))return $m;foreach($this->db->select('itemable_id,SUM(qty) qty',false)->where('invoice_id',(int)$invoiceId)->group_by('itemable_id')->get($t)->result_array() as $r)$m[(int)$r['itemable_id']]=(float)$r['qty'];return $m;}
    private function outbound_warehouse_for_item($invoiceId,$commodityId,$itemableId=0){
        // Warehouse versions do not all store the product reference the same way in
        // goods_delivery_detail. Some store tblitems.id in commodity_code, while this
        // installation may store the actual commodity/SKU code (e.g. 441). Resolve
        // against the real post-permit delivery rows instead of matching only one form.
        $dt=db_prefix().'goods_delivery_detail';
        if(!$this->db->table_exists($dt)||$commodityId<=0)return 0;
        $fields=$this->db->list_fields($dt);
        if(!in_array('warehouse_id',$fields,true))return 0;
        $ids=[];foreach($this->warehouse_deliveries($invoiceId) as $g)$ids[]=(int)$g['id'];if(!$ids)return 0;
        $fk=in_array('goods_delivery_id',$fields,true)?'goods_delivery_id':(in_array('goods_delivery',$fields,true)?'goods_delivery':'');if(!$fk)return 0;

        $candidates=[(string)(int)$commodityId];
        $it=db_prefix().'items';
        if($this->db->table_exists($it)){
            $if=$this->db->list_fields($it);
            $select=['id']; foreach(['commodity_code','sku','code'] as $f) if(in_array($f,$if,true)) $select[]=$f;
            $prod=$this->db->select(implode(',',$select))->where('id',(int)$commodityId)->get($it)->row_array();
            if($prod){foreach(['commodity_code','sku','code'] as $f)if(isset($prod[$f])&&trim((string)$prod[$f])!=='')$candidates[]=trim((string)$prod[$f]);}
        }
        $candidates=array_values(array_unique($candidates));
        foreach(['commodity_id','item_id','itemid','product_id','commodity_code'] as $productField){
            if(!in_array($productField,$fields,true))continue;
            $r=$this->db->select('warehouse_id')->where_in($fk,$ids)->where_in($productField,$candidates)->order_by('id','DESC')->limit(1)->get($dt)->row_array();
            if((int)($r['warehouse_id']??0)>0)return (int)$r['warehouse_id'];
        }
        // Final deterministic fallback: Warehouse detail rows may keep only a textual
        // commodity name/description. Match the catalogue code token inside that text.
        $code='';
        if($this->db->table_exists($it)){
            $if=$this->db->list_fields($it);
            foreach(['commodity_code','sku','code'] as $cf){
                if(!in_array($cf,$if,true))continue;
                $pr=$this->db->select($cf)->where('id',(int)$commodityId)->get($it)->row_array();
                if(!empty($pr[$cf])){$code=trim((string)$pr[$cf]);break;}
            }
        }
        if($code!==''){
            foreach(['description','commodity_name','name','long_description'] as $tf){
                if(!in_array($tf,$fields,true))continue;
                $r=$this->db->select('warehouse_id')->where_in($fk,$ids)->like($tf,$code)->order_by('id','DESC')->limit(1)->get($dt)->row_array();
                if((int)($r['warehouse_id']??0)>0)return (int)$r['warehouse_id'];
            }
        }
        // Exact structural fallback for Warehouse builds that do not keep a usable product key
        // in goods_delivery_detail. Map the native invoice item order to the native delivery-detail
        // order. This is deterministic because the delivery was auto-created from this invoice.
        if($itemableId>0){
            $invoiceItemIds=[];
            foreach($this->db->select('id')->where('rel_id',(int)$invoiceId)->where('rel_type','invoice')->order_by('item_order','ASC')->order_by('id','ASC')->get(db_prefix().'itemable')->result_array() as $ir)$invoiceItemIds[]=(int)$ir['id'];
            $pos=array_search((int)$itemableId,$invoiceItemIds,true);
            if($pos!==false){
                foreach($ids as $deliveryId){
                    $details=$this->db->select('warehouse_id')->where($fk,(int)$deliveryId)->order_by('id','ASC')->get($dt)->result_array();
                    if(isset($details[$pos]) && (int)($details[$pos]['warehouse_id']??0)>0)return (int)$details[$pos]['warehouse_id'];
                }
            }
        }
        // If the whole outbound note uses one warehouse, that warehouse is unambiguous for all lines.
        $unique=[];
        foreach($this->db->select('warehouse_id')->where_in($fk,$ids)->where('warehouse_id >',0)->get($dt)->result_array() as $wr)$unique[(int)$wr['warehouse_id']]=true;
        if(count($unique)===1)return (int)array_key_first($unique);
        // Some Warehouse versions keep warehouse_id only on the goods_delivery header.
        $ht=db_prefix().'goods_delivery';
        if($this->db->table_exists($ht) && $this->db->field_exists('warehouse_id',$ht)){
            $h=$this->db->select('warehouse_id')->where_in('id',$ids)->where('warehouse_id >',0)->order_by('id','DESC')->limit(1)->get($ht)->row_array();
            if((int)($h['warehouse_id']??0)>0)return (int)$h['warehouse_id'];
        }
        return 0;
    }
    public function returnable_items($invoiceId){
        $ctx=$this->invoice_context($invoiceId);$done=$this->returned_qty_map($invoiceId);$out=[];
        foreach(($ctx['items']??[]) as $it){$iid=(int)($it['id']??0);if($iid<=0)continue;$commodity=$this->resolve_item_id($it);$del=(float)($it['qty']??0);$ret=(float)($done[$iid]??0);$remaining=max(0,$del-$ret);$it['itemable_id']=$iid;$it['commodity_id']=$commodity;$it['delivered_qty']=$del;$it['returned_qty']=$ret;$it['returnable_qty']=$remaining;$it['warehouse_id']=$this->outbound_warehouse_for_item($invoiceId,$commodity,$iid);$out[]=$it;}
        return $out;
    }
    private function add_inventory_qty($warehouseId,$commodityId,$qty){
        $t=db_prefix().'inventory_manage';if(!$this->db->table_exists($t))return ['ok'=>false,'error'=>'جدول موجودی Warehouse در دسترس نیست.'];$fields=$this->db->list_fields($t);if(!in_array('warehouse_id',$fields,true)||!in_array('commodity_id',$fields,true)||!in_array('inventory_number',$fields,true))return ['ok'=>false,'error'=>'ساختار جدول موجودی Warehouse شناخته نشد.'];
        $row=$this->db->where('warehouse_id',(int)$warehouseId)->where('commodity_id',(int)$commodityId)->get($t)->row_array();
        if($row){$new=(float)($row['inventory_number']??0)+(float)$qty;$this->db->where('id',(int)$row['id'])->update($t,['inventory_number'=>$new]);return ['ok'=>true,'id'=>(int)$row['id']];}
        $data=['warehouse_id'=>(int)$warehouseId,'commodity_id'=>(int)$commodityId,'inventory_number'=>(float)$qty];foreach(['lot_number','expiry_date'] as $f)if(in_array($f,$fields,true))$data[$f]='';$this->db->insert($t,$data);$id=(int)$this->db->insert_id();return $id?['ok'=>true,'id'=>$id]:['ok'=>false,'error'=>'ایجاد رکورد موجودی Warehouse انجام نشد.'];
    }
    public function invoice_return_state($invoiceId){
        $ctx=$this->invoice_context($invoiceId);$delivered=0;foreach(($ctx['items']??[]) as $it)$delivered+=(float)($it['qty']??0);
        $m=$this->returned_qty_map($invoiceId);$returned=0;foreach($m as $q)$returned+=(float)$q;
        if($returned<=0.0001)return ['state'=>'بدون مرجوعی','returned_qty'=>0,'delivered_qty'=>$delivered];
        return ['state'=>($delivered>0 && $returned>=$delivered-0.0001)?'مرجوعی کامل':'مرجوعی جزئی','returned_qty'=>$returned,'delivered_qty'=>$delivered];
    }
    public function invoice_lock_state($invoiceId){
        $invoiceId=(int)$invoiceId;$reasons=[];
        $payt=db_prefix().'invoicepaymentrecords';
        if($this->db->table_exists($payt) && $this->db->where('invoiceid',$invoiceId)->count_all_results($payt)>0)$reasons[]='پرداخت ثبت شده';
        $pt=db_prefix().'sir_invoice_plans';
        if($this->db->table_exists($pt) && $this->db->where('invoice_id',$invoiceId)->where_in('status',['active','completed'])->count_all_results($pt)>0)$reasons[]='برنامه اقساط ثبت شده';
        if($this->permit($invoiceId))$reasons[]='فرآیند خروج کالا آغاز شده';
        if($this->customer_deliveries($invoiceId))$reasons[]='تحویل مشتری ثبت شده';
        // 0.2.39: return itself is an independent Controlled Lock trigger.
        $rt=db_prefix().'sdf_returns';
        if($this->db->table_exists($rt) && $this->db->where('invoice_id',$invoiceId)->count_all_results($rt)>0)$reasons[]='مرجوعی ثبت شده';
        return ['locked'=>!empty($reasons),'reasons'=>$reasons,'message'=>!empty($reasons)?implode('، ',$reasons):''];
    }
    public function invoice_flags($ids){
        $out=[];foreach(array_unique(array_map('intval',(array)$ids)) as $id){if($id<=0)continue;$out[$id]=['lock'=>$this->invoice_lock_state($id),'return'=>$this->invoice_return_state($id)];}return $out;
    }
    public function invoice_flags_by_numbers($numbers){
        $out=[];$t=db_prefix().'invoices';if(!$this->db->table_exists($t))return $out;
        foreach((array)$numbers as $number){$number=trim((string)$number);if($number==='')continue;$row=$this->db->select('id,number')->where('number',$number)->order_by('id','DESC')->limit(1)->get($t)->row_array();if(!$row)continue;$id=(int)$row['id'];$out[(string)$number]=['invoice_id'=>$id,'lock'=>$this->invoice_lock_state($id),'return'=>$this->invoice_return_state($id)];}
        return $out;
    }

    // Repair legacy zero-rate native invoice rows only when the reconstructed historical line total
    // matches the stored invoice subtotal/total. This makes Native View/PDF read the same verified rates.
    public function repair_native_invoice_rates($invoiceId){
        $invoiceId=(int)$invoiceId;if($invoiceId<=0)return ['ok'=>false,'reason'=>'invalid'];$inv=$this->db->where('id',$invoiceId)->get(db_prefix().'invoices')->row_array();if(!$inv)return ['ok'=>false,'reason'=>'invoice'];
        $rows=$this->db->where('rel_id',$invoiceId)->where('rel_type','invoice')->order_by('item_order','ASC')->get(db_prefix().'itemable')->result_array();if(!$rows)return ['ok'=>false,'reason'=>'items'];
        $normalized=$this->normalize_invoice_items($rows,$inv);$sum=0.0;$updates=[];foreach($normalized as $idx=>$it){$sum+=(float)($it['line_total']??0);if((float)($rows[$idx]['rate']??0)<=0 && (float)($it['rate']??0)>0)$updates[(int)$rows[$idx]['id']]=(float)$it['rate'];}
        if(!$updates)return ['ok'=>true,'changed'=>0];
        $target=(float)($inv['subtotal']??0);if($target<=0)$target=(float)($inv['total']??0);$tol=max(1.0,abs($target)*0.0005);if($target<=0||abs($sum-$target)>$tol)return ['ok'=>false,'reason'=>'total_mismatch','expected'=>$target,'resolved'=>$sum];
        $this->db->trans_begin();
        foreach($updates as $id=>$rate){
            $this->db->where('id',$id)->where('rel_id',$invoiceId)->where('rel_type','invoice')->update(db_prefix().'itemable',['rate'=>$rate]);
        }
        if($this->db->trans_status()===false){$this->db->trans_rollback();return ['ok'=>false,'reason'=>'db'];}
        $this->db->trans_commit();
        // Verify persistence. Native Perfex View/PDF reads tblitemable.rate, so success means the stored value itself changed.
        $remainingZero=0;
        foreach($updates as $id=>$rate){$r=$this->db->select('rate')->where('id',$id)->where('rel_id',$invoiceId)->where('rel_type','invoice')->get(db_prefix().'itemable')->row_array();if((float)($r['rate']??0)<=0)$remainingZero++;}
        if($remainingZero>0)return ['ok'=>false,'reason'=>'verify_failed','changed'=>count($updates)-$remainingZero,'remaining_zero'=>$remainingZero];
        return ['ok'=>true,'changed'=>count($updates),'verified'=>true];
    }

    // 0.2.38 diagnostic only: explains the exact controlled-lock inputs without changing behavior.
    public function controlled_lock_diagnostic($invoiceId){
        $invoiceId=(int)$invoiceId;$facts=[];
        $payt=db_prefix().'invoicepaymentrecords';
        $facts['payment_count']=$this->db->table_exists($payt)?(int)$this->db->where('invoiceid',$invoiceId)->count_all_results($payt):0;
        $pt=db_prefix().'sir_invoice_plans';
        $facts['installment_plan_count']=$this->db->table_exists($pt)?(int)$this->db->where('invoice_id',$invoiceId)->where_in('status',['active','completed'])->count_all_results($pt):0;
        $facts['exit_started']=$this->permit($invoiceId)?true:false;
        $facts['customer_delivery_count']=count($this->customer_deliveries($invoiceId));
        $rt=db_prefix().'sdf_returns';
        $facts['return_count']=$this->db->table_exists($rt)?(int)$this->db->where('invoice_id',$invoiceId)->count_all_results($rt):0;
        $facts['should_be_locked']=($facts['payment_count']>0||$facts['installment_plan_count']>0||$facts['exit_started']||$facts['customer_delivery_count']>0||$facts['return_count']>0);
        $facts['current_model_locked']=!empty($this->invoice_lock_state($invoiceId)['locked']);
        $facts['return_missing_from_current_lock_model']=($facts['return_count']>0 && !in_array('مرجوعی ثبت شده',$this->invoice_lock_state($invoiceId)['reasons']??[],true));
        return $facts;
    }
    // 0.2.38 diagnostic only: dry-run of native rate repair, with no UPDATE.
    public function native_rate_diagnostic($invoiceId){
        $invoiceId=(int)$invoiceId;$inv=$this->db->where('id',$invoiceId)->get(db_prefix().'invoices')->row_array();if(!$inv)return ['ok'=>false,'reason'=>'invoice'];
        $rows=$this->db->where('rel_id',$invoiceId)->where('rel_type','invoice')->order_by('item_order','ASC')->order_by('id','ASC')->get(db_prefix().'itemable')->result_array();
        $normalized=$this->normalize_invoice_items($rows,$inv);$sum=0.0;$candidate=[];
        foreach($normalized as $idx=>$it){$sum+=(float)($it['line_total']??0);$candidate[]=['itemable_id'=>(int)($rows[$idx]['id']??0),'raw_rate'=>(float)($rows[$idx]['rate']??0),'resolved_rate'=>(float)($it['rate']??0),'qty'=>(float)($it['qty']??0),'resolved_line_total'=>(float)($it['line_total']??0),'discount_percent'=>(float)($it['discount_percent_value']??0),'discount_amount'=>(float)($it['discount_amount_value']??0)];}
        $target=(float)($inv['subtotal']??0);if($target<=0)$target=(float)($inv['total']??0);$tol=max(1.0,abs($target)*0.0005);
        return ['ok'=>true,'stored_subtotal'=>(float)($inv['subtotal']??0),'stored_total'=>(float)($inv['total']??0),'resolved_sum'=>$sum,'difference'=>$sum-$target,'tolerance'=>$tol,'repair_would_pass'=>($target>0&&abs($sum-$target)<=$tol),'items'=>$candidate];
    }

    public function return_financial_summary($invoiceId){
        $ctx=$this->invoice_context($invoiceId);$rt=db_prefix().'sdf_returns';$credit=0;
        if($this->db->table_exists($rt)){$r=$this->db->select_sum('total_amount','s')->where('invoice_id',(int)$invoiceId)->get($rt)->row_array();$credit=(float)($r['s']??0);}
        $remaining=(float)($ctx['remaining']??0);$offset=min($credit,$remaining);$refund=max(0,$credit-$remaining);
        return ['return_credit'=>$credit,'current_remaining'=>$remaining,'suggested_debt_offset'=>$offset,'potential_customer_credit'=>$refund,'status'=>$credit>0?'در انتظار تسویه مالی مرجوعی':'بدون مرجوعی مالی'];
    }

    public function create_return($invoiceId,$qtys,$reason='',$note=''){
        $this->ensure_schema();if(!$this->customer_deliveries($invoiceId))return ['ok'=>false,'error'=>'این فاکتور هنوز تحویل مشتری نشده است.'];$items=$this->returnable_items($invoiceId);$selected=[];
        foreach($items as $it){$iid=(int)$it['itemable_id'];$q=isset($qtys[$iid])?(float)str_replace(',','',(string)$qtys[$iid]):0;if($q<=0)continue;if($q>(float)$it['returnable_qty']+0.0001)return ['ok'=>false,'error'=>'تعداد مرجوعی «'.($it['description']??'کالا').'» بیشتر از تعداد قابل مرجوعی است.'];if((int)$it['commodity_id']<=0)return ['ok'=>false,'error'=>'شناسه کالای «'.($it['description']??'').'» برای برگشت موجودی پیدا نشد.'];if((int)$it['warehouse_id']<=0)return ['ok'=>false,'error'=>'انبار خروج کالای «'.($it['description']??'').'» پیدا نشد.'];$it['return_qty']=$q;$selected[]=$it;}
        if(!$selected)return ['ok'=>false,'error'=>'حداقل یک کالا و تعداد مرجوعی را انتخاب کنید.'];
        $rt=db_prefix().'sdf_returns';$lt=db_prefix().'sdf_return_lines';$log=db_prefix().'sdf_return_inventory_log';$this->db->trans_begin();
        $total=0;foreach($selected as $it){$unit=(float)($it['rate']??0);$line=max(0,$unit*(float)$it['return_qty']);$total+=$line;}
        $this->db->insert($rt,['invoice_id'=>(int)$invoiceId,'return_no'=>'RET-'.date('Ymd').'-'.(int)$invoiceId.'-'.date('His'),'return_date'=>date('Y-m-d'),'reason'=>trim((string)$reason),'note'=>$note,'total_amount'=>$total,'created_by'=>get_staff_user_id(),'created_at'=>date('Y-m-d H:i:s')]);$rid=(int)$this->db->insert_id();if(!$rid){$this->db->trans_rollback();return ['ok'=>false,'error'=>'سند مرجوعی ایجاد نشد.'];}
        foreach($selected as $it){$q=(float)$it['return_qty'];$unit=(float)($it['rate']??0);$origQty=max(0.0001,(float)($it['qty']??1));$disc=((float)($it['discount_amount_value']??0))*($q/$origQty);$line=max(0,$unit*$q-$disc);$this->db->insert($lt,['return_id'=>$rid,'invoice_id'=>(int)$invoiceId,'itemable_id'=>(int)$it['itemable_id'],'commodity_id'=>(int)$it['commodity_id'],'warehouse_id'=>(int)$it['warehouse_id'],'description'=>$it['description']??'','qty'=>$q,'unit_price'=>$unit,'discount_amount'=>$disc,'line_total'=>$line]);$lid=(int)$this->db->insert_id();$inv=$this->add_inventory_qty((int)$it['warehouse_id'],(int)$it['commodity_id'],$q);if(empty($inv['ok'])){$this->db->trans_rollback();return ['ok'=>false,'error'=>$inv['error']??'خطا در برگشت موجودی'];}$this->db->insert($log,['return_id'=>$rid,'return_line_id'=>$lid,'warehouse_id'=>(int)$it['warehouse_id'],'commodity_id'=>(int)$it['commodity_id'],'qty'=>$q,'inventory_manage_id'=>(int)$inv['id'],'created_at'=>date('Y-m-d H:i:s')]);}
        if($this->db->trans_status()===false){$this->db->trans_rollback();return ['ok'=>false,'error'=>'تراکنش مرجوعی کامل نشد.'];}$this->db->trans_commit();return ['ok'=>true,'id'=>$rid];
    }

}
