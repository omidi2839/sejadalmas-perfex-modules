<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Product_sqm_pricing_model extends App_Model {
    private $last_error='';
    public function get_last_error(){ return $this->last_error; }
    private function fail($msg){ $this->last_error=$msg; return false; }
    private function table(){ return db_prefix().'product_sqm_price_rules'; }
    public function ensure_schema(){
        $t=$this->table(); if(!$this->db->table_exists($t)) return;
        // Module ZIP replacement does not rerun Perfex activation hooks. A failed ALTER
        // must never take the whole admin area down; try migrations with DB debug off.
        $old_debug=isset($this->db->db_debug)?$this->db->db_debug:null;
        if($old_debug!==null)$this->db->db_debug=false;
        $cols=['pricing_method'=>"varchar(20) NOT NULL DEFAULT 'sqm'",'fixed_price'=>"decimal(20,4) NOT NULL DEFAULT 0",'reeds'=>'varchar(100) NULL','density'=>'varchar(100) NULL','weave_type'=>'varchar(191) NULL','commodity_code'=>'varchar(191) NULL','size'=>'varchar(191) NULL','design'=>'varchar(191) NULL'];
        foreach($cols as $c=>$def){
            if(!$this->db->field_exists($c,$t)){$this->db->query("ALTER TABLE `{$t}` ADD `{$c}` {$def}");}
        }
        if($old_debug!==null)$this->db->db_debug=$old_debug;
    }
    private function rule_dims(){
        $t=$this->table();$out=[];
        foreach(['reeds','density','weave_type','commodity_code','size','design'] as $c)if($this->db->field_exists($c,$t))$out[]=$c;
        return $out;
    }
    public function get_item_groups(){ return $this->db->order_by('name','asc')->get(db_prefix().'items_groups')->result(); }
    private function norm($v){ $v=str_replace(['ي','ك','ۀ','ة'],['ی','ک','ه','ه'],trim((string)$v)); return mb_strtolower(preg_replace('/\s+/u',' ',$v),'UTF-8'); }
    public function get_attribute_options($key,$name=''){
        $p=db_prefix(); $out=[];
        if($this->db->table_exists($p.'pum_attribute_options')){
            $keys=[$key];
            if($name!=='' && $this->db->table_exists($p.'pum_attribute_definitions')){
                foreach($this->db->get($p.'pum_attribute_definitions')->result() as $d){ if($this->norm($d->name)===$this->norm($name)) $keys[]=$d->attribute_key; }
            }
            $keys=array_values(array_unique(array_filter($keys)));
            if($keys){ $this->db->where_in('attribute_key',$keys); if($this->db->field_exists('active',$p.'pum_attribute_options'))$this->db->where('active',1); foreach($this->db->order_by('label','asc')->get($p.'pum_attribute_options')->result() as $r) if(trim((string)$r->label)!=='')$out[]=(string)$r->label; }
        }
        if(!$out && in_array($key,['reeds','density'],true) && $this->db->table_exists($p.'pum_product_attributes') && $this->db->field_exists($key,$p.'pum_product_attributes')){
            foreach($this->db->select($key)->where($key.' IS NOT NULL',null,false)->where($key.' !=','')->group_by($key)->order_by($key,'asc')->get($p.'pum_product_attributes')->result() as $r)$out[]=(string)$r->$key;
        }
        return array_values(array_unique($out));
    }
    public function get_design_key(){
        $t=db_prefix().'pum_attribute_definitions'; if(!$this->db->table_exists($t))return 'design';
        foreach($this->db->get($t)->result() as $d){ $n=$this->norm($d->name); if($d->attribute_key==='design'||$d->attribute_key==='pattern'||$n==='طرح'||mb_strpos($n,'طرح')!==false)return (string)$d->attribute_key; }
        return 'design';
    }
    public function get_weave_key(){
        $t=db_prefix().'pum_attribute_definitions'; if(!$this->db->table_exists($t))return 'weave_type';
        foreach($this->db->get($t)->result() as $d){ $n=$this->norm($d->name); if($d->attribute_key==='weave_type'||mb_strpos($n,'بافت')!==false)return (string)$d->attribute_key; }
        return 'weave_type';
    }
    public function get_size_options(){
        $t=db_prefix().'ware_size_type'; if(!$this->db->table_exists($t)) return [];
        $field=$this->db->field_exists('size_name',$t)?'size_name':($this->db->field_exists('name',$t)?'name':''); if($field==='') return [];
        $rows=$this->db->select($field)->where($field.' IS NOT NULL',null,false)->where($field.' !=','')->group_by($field)->order_by($field,'asc')->get($t)->result();
        return array_map(function($r) use($field){return (string)$r->$field;},$rows);
    }
    public function get_commodity_codes(){
        $t=db_prefix().'items'; if(!$this->db->table_exists($t)||!$this->db->field_exists('commodity_code',$t))return [];
        $rows=$this->db->select('commodity_code')->where('commodity_code IS NOT NULL',null,false)->where('commodity_code !=','')->group_by('commodity_code')->order_by('commodity_code','asc')->get($t)->result(); return array_map(function($r){return (string)$r->commodity_code;},$rows);
    }
    public function get_sell_types(){
        $t=db_prefix().'item_discount_sell_type';
        if(!$this->db->table_exists($t)) return [];
        if($this->db->field_exists('st_status',$t)) $this->db->where('st_status',1);
        return $this->db->order_by('st_id','asc')->get($t)->result();
    }
    public function get_rules(){
        $this->ensure_schema();
        $this->migrate_legacy_jalali_dates();
        $this->db->select('r.*,g.name as group_name')->from($this->table().' r')->join(db_prefix().'items_groups g','g.id=r.group_id','left')->order_by('r.id','desc');
        return $this->db->get()->result();
    }
    public function add_rule($d){
        $this->ensure_schema(); $this->last_error='';
        $d=$this->clean($d); if(!$this->validate_dates($d)) return false; $d['created_at']=date('Y-m-d H:i:s');
        if(!$this->validate_no_overlap($d)) return $this->fail(_l('psp_overlap'));
        if(!$this->db->insert($this->table(),$d)) return $this->fail(_l('psp_database_error'));
        return true;
    }
    public function update_rule($id,$d){
        $this->ensure_schema(); $this->last_error='';
        $d=$this->clean($d); if(!$this->validate_dates($d)) return false; $d['updated_at']=date('Y-m-d H:i:s');
        if(!$this->validate_no_overlap($d,$id)) return $this->fail(_l('psp_overlap'));
        if(!$this->db->where('id',$id)->update($this->table(),$d)) return $this->fail(_l('psp_database_error'));
        return true;
    }
    public function delete_rule($id){ return $this->db->where('id',$id)->delete($this->table()); }
    private function clean($d){
        $from=$this->normalize_date(isset($d['valid_from'])?$d['valid_from']:'');
        $to=$this->normalize_date(isset($d['valid_to'])?$d['valid_to']:'');
        
        $method=(isset($d['pricing_method']) && $d['pricing_method']==='fixed')?'fixed':'sqm';
        $sqm=(float)str_replace(',','',(string)($d['price_per_sqm'] ?? 0));
        $fixed=(float)str_replace(',','',(string)($d['fixed_price'] ?? 0));
        $out=['group_id'=>(int)$d['group_id'],'sale_type_key'=>trim($d['sale_type_key']),'sale_type_name'=>trim($d['sale_type_name'] ?? ''),'reeds'=>trim((string)($d['reeds']??'')),'density'=>trim((string)($d['density']??'')),'weave_type'=>trim((string)($d['weave_type']??'')),'commodity_code'=>trim((string)($d['commodity_code']??'')),'size'=>trim((string)($d['size']??'')),'pricing_method'=>$method,'price_per_sqm'=>$method==='sqm'?$sqm:0,'fixed_price'=>$method==='fixed'?$fixed:0,'valid_from'=>$from,'valid_to'=>$to,'active'=>isset($d['active'])?1:0]; if($this->db->field_exists('design',$this->table()))$out['design']=trim((string)($d['design']??'')); return $out;
    }
    private function normalize_date($value){
        $value=trim((string)$value);
        if($value==='') return '';
        $value=strtr($value,[
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'
        ]);
        $value=str_replace(['/','.'],'-',$value);
        // Persian installations may post Jalali values such as 1405-06-21. Never
        // interpret a 13xx/14xx year as Gregorian; convert it explicitly first.
        if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/',$value,$m)){
            $y=(int)$m[1]; $mo=(int)$m[2]; $d=(int)$m[3];
            if($y>=1200 && $y<=1600){
                $g=$this->jalali_to_gregorian($y,$mo,$d);
                return sprintf('%04d-%02d-%02d',$g[0],$g[1],$g[2]);
            }
            $iso=sprintf('%04d-%02d-%02d',$y,$mo,$d);
            $dt=DateTime::createFromFormat('!Y-m-d',$iso);
            if($dt && $dt->format('Y-m-d')===$iso) return $iso;
        }
        if(function_exists('to_sql_date')) {
            $sql=to_sql_date($value);
            if($sql && preg_match('/^\d{4}-\d{2}-\d{2}$/',$sql)) return $sql;
        }
        foreach(['Y-m-d','Y/m/d','d/m/Y','m/d/Y','d-m-Y','m-d-Y'] as $fmt){
            $dt=DateTime::createFromFormat('!'.$fmt,$value);
            if($dt) return $dt->format('Y-m-d');
        }
        return '';
    }
    private function jalali_to_gregorian($jy,$jm,$jd){
        $jy += 1595;
        $days = -355668 + (365 * $jy) + ((int)($jy / 33) * 8) + (int)((($jy % 33) + 3) / 4) + $jd;
        $days += ($jm < 7) ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186);
        $gy = 400 * (int)($days / 146097);
        $days %= 146097;
        if($days > 36524){
            $gy += 100 * (int)(--$days / 36524);
            $days %= 36524;
            if($days >= 365) $days++;
        }
        $gy += 4 * (int)($days / 1461);
        $days %= 1461;
        if($days > 365){
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a=[0,31,(($gy%4===0 && $gy%100!==0) || ($gy%400===0))?29:28,31,30,31,30,31,31,30,31,30,31];
        for($gm=1;$gm<=12 && $gd>$sal_a[$gm];$gm++) $gd-=$sal_a[$gm];
        return [$gy,$gm,$gd];
    }
    private function migrate_legacy_jalali_dates(){
        // Repair rows written by 0.1.10 and earlier where Jalali display values
        // (13xx/14xx) were stored directly in DATE columns.
        $rows=$this->db->select('id,valid_from,valid_to')->get($this->table())->result();
        foreach($rows as $r){
            $u=[];
            foreach(['valid_from','valid_to'] as $f){
                $v=(string)$r->$f;
                if(preg_match('/^(1[2345]\d{2})-(\d{1,2})-(\d{1,2})$/',$v)){
                    $n=$this->normalize_date($v);
                    if($n && $n!==$v) $u[$f]=$n;
                }
            }
            if($u) $this->db->where('id',(int)$r->id)->update($this->table(),$u);
        }
    }
    private function validate_dates($d){
        if(empty($d['valid_from']) || empty($d['valid_to'])) return $this->fail(_l('psp_dates_required'));
        if($d['valid_to'] < $d['valid_from']) return $this->fail(_l('psp_invalid_date_range'));
        return true;
    }
    public function find_rule($group,$type,$date,$type_name='',$product=[]){
        $this->ensure_schema(); $this->migrate_legacy_jalali_dates();
        $group=(int)$group; $type=trim((string)$type); $type_name=trim((string)$type_name);
        // Fetch all active rules for the group/date first. Warehouse/item_discount can
        // expose a sale type as ID in one screen and as its label in another.
        $rows=$this->db->where('group_id',$group)->where('active',1)
            ->where('valid_from <=',$date)->where('valid_to >=',$date)
            ->order_by('valid_from','desc')->order_by('id','desc')->get($this->table())->result();
        if(!$rows) return null;
        $norm=function($v){
            $v=trim((string)$v); $v=preg_replace('/\s+/u',' ',$v);
            return mb_strtolower($v,'UTF-8');
        };
        $keys=array_values(array_unique(array_filter([$norm($type),$norm($type_name)])));
        // If a numeric ID is known, also resolve its canonical label.
        if($type!=='' && ctype_digit($type) && $this->db->table_exists(db_prefix().'item_discount_sell_type')){
            $st=$this->db->select('st_name')->where('st_id',(int)$type)->get(db_prefix().'item_discount_sell_type')->row();
            if($st && $st->st_name) $keys[]=$norm($st->st_name);
        }
        $matched=[];
        foreach($rows as $r){
            $rk=$norm($r->sale_type_key); $rn=$norm($r->sale_type_name);
            if(in_array($rk,$keys,true) || ($rn!=='' && in_array($rn,$keys,true))) $matched[]=$r;
        }
        if(!$matched) return null;
        $ctx=$this->product_context($product);
        $eligible=[];
        foreach($matched as $r){
            $ok=true;$score=0;
            foreach(['reeds','density','weave_type','commodity_code','size','design'] as $c){$rv=trim((string)($r->$c??''));if($rv==='')continue;if($this->norm($rv)!==$this->norm($ctx[$c]??'')){$ok=false;break;}$score++;}
            if($ok){$r->_specificity=$score;$eligible[]=$r;}
        }
        if(!$eligible)return null;$matched=$eligible;
        // Most specific product rule wins; fixed price wins only inside equal specificity.
        usort($matched,function($a,$b){
            $as=(int)($a->_specificity??0);$bs=(int)($b->_specificity??0);if($as!==$bs)return $bs-$as;
            $af=(isset($a->pricing_method)&&$a->pricing_method==='fixed')?1:0;
            $bf=(isset($b->pricing_method)&&$b->pricing_method==='fixed')?1:0;
            if($af!==$bf) return $bf-$af;
            $c=strcmp((string)$b->valid_from,(string)$a->valid_from); if($c!==0)return $c;
            return ((int)$b->id)-((int)$a->id);
        });
        return $matched[0];
    }
    private function product_context($product){
        $ctx=['reeds'=>'','density'=>'','weave_type'=>'','commodity_code'=>'','size'=>'','design'=>'']; foreach($ctx as $k=>$v)if(isset($product[$k]))$ctx[$k]=trim((string)$product[$k]);
        $p=db_prefix();$item=null;$itemId=(int)($product['item_id']??0);$code=$ctx['commodity_code'];
        if($this->db->table_exists($p.'items')){if($itemId)$item=$this->db->where('id',$itemId)->get($p.'items')->row();elseif($code!==''&&$this->db->field_exists('commodity_code',$p.'items'))$item=$this->db->where('commodity_code',$code)->get($p.'items')->row();}
        if($item){if($ctx['size']==='' && isset($item->size_id) && $item->size_id && $this->db->table_exists($p.'ware_size_type')){ $sz=$this->db->where('size_type_id',(int)$item->size_id)->get($p.'ware_size_type')->row(); if($sz && isset($sz->size_name))$ctx['size']=(string)$sz->size_name; } if($ctx['commodity_code']===''&&isset($item->commodity_code))$ctx['commodity_code']=(string)$item->commodity_code;$id=(int)$item->id;
            if($this->db->table_exists($p.'pum_product_attributes')){$a=$this->db->where('item_id',$id)->get($p.'pum_product_attributes')->row();if($a){foreach(['reeds','density'] as $k)if($ctx[$k]===''&&isset($a->$k))$ctx[$k]=(string)$a->$k;}}
            $wk=$this->get_weave_key();if($ctx['weave_type']===''&&$this->db->table_exists($p.'pum_product_attribute_values')){$v=$this->db->where(['item_id'=>$id,'attribute_key'=>$wk])->get($p.'pum_product_attribute_values')->row();if($v)$ctx['weave_type']=(string)$v->value;}
            $dk=$this->get_design_key();if($ctx['design']===''&&$this->db->table_exists($p.'pum_product_attribute_values')){$v=$this->db->where(['item_id'=>$id,'attribute_key'=>$dk])->get($p.'pum_product_attribute_values')->row();if($v)$ctx['design']=(string)$v->value;}
            if($ctx['design']===''&&$this->db->table_exists($p.'pum_product_attributes')&&$this->db->field_exists('design',$p.'pum_product_attributes')){$a2=$this->db->where('item_id',$id)->get($p.'pum_product_attributes')->row();if($a2&&isset($a2->design))$ctx['design']=(string)$a2->design;}
        } return $ctx;
    }
    public function get_fixed_rules_for_group($group,$date){
        $this->migrate_legacy_jalali_dates();
        return $this->db->where('group_id',(int)$group)->where('active',1)
            ->where('pricing_method','fixed')->where('valid_from <=',$date)->where('valid_to >=',$date)
            ->order_by('valid_from','desc')->order_by('id','desc')->get($this->table())->result();
    }
    private function validate_no_overlap($d,$ignore=0){
        if(!$d['active']) return true;
        $this->db->where('group_id',$d['group_id'])->where('sale_type_key',$d['sale_type_key'])->where('active',1);
        foreach($this->rule_dims() as $c) $this->db->where("COALESCE(`{$c}`,'') =",(string)($d[$c]??''));
        if($ignore) $this->db->where('id !=',$ignore);
        $this->db->where('valid_to >=',$d['valid_from']);
        $this->db->where('valid_from <=',$d['valid_to']);
        return $this->db->get($this->table())->num_rows()===0;
    }
    public function get_price_history($group,$type,$dims=[]){
        $this->migrate_legacy_jalali_dates();
        $select=['id','group_id','sale_type_key','sale_type_name','reeds','density','weave_type','commodity_code','size']; if($this->db->field_exists('design',$this->table()))$select[]='design'; $select=array_merge($select,['pricing_method','price_per_sqm','fixed_price','valid_from','valid_to','active','created_at','updated_at']);
        $this->db->select(implode(',',$select))->where('group_id',(int)$group)->where('sale_type_key',(string)$type);
        foreach($this->rule_dims() as $c) if(array_key_exists($c,$dims)) $this->db->where("COALESCE(`{$c}`,'') =",(string)$dims[$c]);
        return $this->db->order_by('valid_from','asc')->order_by('id','asc')->get($this->table())->result_array();
    }
    public function parse_area($raw){
        $s=trim((string)$raw); if($s==='') return false;
        $fa=['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٫','،']; $en=['0','1','2','3','4','5','6','7','8','9','.',','];
        $s=str_replace($fa,$en,$s); $s=str_replace(['×','X','x','*','✕','ـ'], 'x', $s); $s=preg_replace('/\s+/u','',$s);
        // Size labels in Warehouse are often descriptive, e.g. "سته أمتار (۳×۲)".
        // Extract the first dimension pair from anywhere in the label/value.
        if(preg_match('/([0-9]+(?:\.[0-9]+)?)x([0-9]+(?:\.[0-9]+)?)/',$s,$m)) return (float)$m[1]*(float)$m[2];
        return false;
    }
}
