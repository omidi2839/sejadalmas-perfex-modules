<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Product_sqm_pricing extends AdminController {
    public function __construct(){ parent::__construct(); $this->load->model('product_sqm_pricing/product_sqm_pricing_model'); }
    public function index(){
        if (!is_admin()) access_denied('Product SQM Pricing');
        if ($this->input->post()) {
            $data=$this->input->post(NULL, true);
            $id=isset($data['id'])?(int)$data['id']:0;
            unset($data['id']);
            // Both dates are mandatory. Read them explicitly because Perfex date-picker
            // fields may be formatted according to the installation date format.
            $data['valid_from']=trim((string)$this->input->post('valid_from', true));
            $data['valid_to']=trim((string)$this->input->post('valid_to', true));
            if ($data['valid_from']==='' || $data['valid_to']==='') {
                set_alert('danger', _l('psp_dates_required'));
                redirect(admin_url('product_sqm_pricing'));
            }
            $ok=$id?$this->product_sqm_pricing_model->update_rule($id,$data):$this->product_sqm_pricing_model->add_rule($data);
            if ($ok) {
                set_alert('success', _l('psp_saved'));
            } else {
                $err=$this->product_sqm_pricing_model->get_last_error();
                set_alert('danger', $err ?: _l('psp_save_failed'));
            }
            redirect(admin_url('product_sqm_pricing'));
        }
        $data['title']=_l('psp_title');
        $data['rules']=$this->product_sqm_pricing_model->get_rules();
        $data['groups']=$this->product_sqm_pricing_model->get_item_groups();
        $data['sell_types']=$this->product_sqm_pricing_model->get_sell_types();
        $data['reeds_options']=$this->product_sqm_pricing_model->get_attribute_options('reeds','شانه');
        $data['density_options']=$this->product_sqm_pricing_model->get_attribute_options('density','تراکم');
        $data['weave_key']=$this->product_sqm_pricing_model->get_weave_key();
        $data['weave_options']=$this->product_sqm_pricing_model->get_attribute_options($data['weave_key'],'نوع بافت');
        $data['design_key']=$this->product_sqm_pricing_model->get_design_key();
        $data['design_options']=$this->product_sqm_pricing_model->get_attribute_options($data['design_key'],'طرح');
        $data['commodity_codes']=$this->product_sqm_pricing_model->get_commodity_codes();
        $data['size_options']=$this->product_sqm_pricing_model->get_size_options();
        $this->load->view('manage',$data);
    }
    public function delete($id){ if(!is_admin()) access_denied('Product SQM Pricing'); $this->product_sqm_pricing_model->delete_rule((int)$id); redirect(admin_url('product_sqm_pricing')); }
    public function history(){
        if (!is_staff_logged_in()) ajax_access_denied();
        $group=(int)$this->input->get('group_id',true); $type=trim((string)$this->input->get('sale_type_key',true));
        if(!$group || $type==='') return $this->json(['success'=>false,'message'=>'گروه کالا یا نوع فروش مشخص نیست.']);
        $dims=[]; foreach(['reeds','density','weave_type','commodity_code','size','design'] as $c) if($this->input->get($c)!==null)$dims[$c]=trim((string)$this->input->get($c,true));
        $rows=$this->product_sqm_pricing_model->get_price_history($group,$type,$dims);
        $g=$this->db->select('name')->where('id',$group)->get(db_prefix().'items_groups')->row();
        $sale=''; if($this->db->table_exists(db_prefix().'item_discount_sell_type')){$st=$this->db->select('st_name')->where('st_id',$type)->get(db_prefix().'item_discount_sell_type')->row();if($st)$sale=$st->st_name;}
        foreach($rows as $k=>$r){$rows[$k]['display_from']=_d($r['valid_from']);$rows[$k]['display_to']=$r['valid_to']?_d($r['valid_to']):'—';}
        return $this->json(['success'=>true,'group_name'=>$g?$g->name:'','sale_type_name'=>$sale,'rows'=>$rows]);
    }
    public function fixed_mode(){
        if (!is_staff_logged_in()) ajax_access_denied();
        $group_id=(int)$this->input->get('group_id', true);
        $group_name=trim((string)$this->input->get('group_name', true));
        $date=trim((string)$this->input->get('date', true)); if(!$date) $date=date('Y-m-d');
        $size=trim((string)$this->input->get('size', true));
        $design=trim((string)$this->input->get('design', true));
        // Warehouse/Product Unified Manager may expose a different value in the group select.
        // Resolve the canonical tblitems_groups.id from the visible group label as a second source of truth.
        if($group_name!==''){
            $gn=preg_replace('/\s+/u',' ',trim($group_name));
            $gr=$this->db->select('id,name')->where('name',$gn)->get(db_prefix().'items_groups')->row();
            if(!$gr){
                $all=$this->db->select('id,name')->get(db_prefix().'items_groups')->result();
                $norm=function($v){ $v=str_replace(['ي','ك','ۀ','ة'],['ی','ک','ه','ه'],(string)$v); return mb_strtolower(preg_replace('/\s+/u',' ',trim($v)),'UTF-8'); };
                foreach($all as $x){ if($norm($x->name)===$norm($gn)){ $gr=$x; break; } }
            }
            if($gr) $group_id=(int)$gr->id;
        }
        if(!$group_id) return $this->json(['success'=>true,'mode'=>'sqm','rules'=>[],'debug'=>'group_not_resolved']);
        $rows=$this->product_sqm_pricing_model->get_fixed_rules_for_group($group_id,$date);
        if(!$rows) return $this->json(['success'=>true,'mode'=>'sqm','rules'=>[]]);
        // IMPORTANT: this resolver is intentionally scoped ONLY to fixed-price mode.
        // Existing SQM / online / in-person calculation paths remain untouched.
        $product=[
            'commodity_code'=>trim((string)$this->input->get('commodity_code',true)),
            'reeds'=>trim((string)$this->input->get('reeds',true)),
            'density'=>trim((string)$this->input->get('density',true)),
            'weave_type'=>trim((string)$this->input->get('weave_type',true)),
            'design'=>$design,
            'size'=>$size,
        ];
        $norm=function($v){$v=str_replace(['ي','ك','ۀ','ة'],['ی','ک','ه','ه'],trim((string)$v));$v=str_replace(['×','X','*','✕'],'x',$v);$v=preg_replace('/\s+/u','',$v);return mb_strtolower($v,'UTF-8');};
        $dims=['commodity_code','design','size','reeds','density','weave_type'];
        $out=[];$chosen=[];
        foreach($rows as $r){
            $ok=true;$spec=0;
            foreach($dims as $d){
                $rv=trim((string)($r->$d??''));
                if($rv==='') continue; // generic fixed rule remains a valid fallback
                if($norm($rv)!==$norm($product[$d]??'')){ $ok=false; break; }
                $spec++;
            }
            if(!$ok) continue;
            $k=(string)$r->sale_type_key;
            // Higher specificity wins. For equal specificity preserve DB order
            // returned by get_fixed_rules_for_group (latest effective rule first).
            if(isset($chosen[$k]) && $chosen[$k]['spec'] >= $spec) continue;
            $chosen[$k]=['spec'=>$spec,'row'=>$r];
        }
        foreach($chosen as $x){$r=$x['row'];$out[]=['rule_id'=>(int)$r->id,'sale_type_key'=>(string)$r->sale_type_key,'sale_type_name'=>(string)$r->sale_type_name,'price'=>(float)$r->fixed_price];}
        return $this->json(['success'=>true,'mode'=>'fixed','group_id'=>$group_id,'group_name'=>$group_name,'rules'=>$out]);
    }
    public function calculate(){
        if (!is_staff_logged_in()) ajax_access_denied();
        // Calculation is read-only. Accept GET first so installations behind a
        // reauthentication/CSRF proxy do not reject the AJAX call with HTTP 419.
        $group_id=(int)$this->input->get_post('group_id', true);
        $sale_type_key=trim((string)$this->input->get_post('sale_type_key', true));
        $sale_type_name=trim((string)$this->input->get_post('sale_type_name', true));
        // Resolve the real item_discount sell-type ID when the dynamic Warehouse field posts a label/name instead of an ID.
        if ($this->db->table_exists(db_prefix().'item_discount_sell_type')) {
            $resolved=null;
            if ($sale_type_key !== '' && ctype_digit($sale_type_key)) {
                $resolved=$this->db->where('st_id',(int)$sale_type_key)->get(db_prefix().'item_discount_sell_type')->row();
            }
            if (!$resolved && $sale_type_name !== '') {
                $resolved=$this->db->where('st_name',$sale_type_name)->get(db_prefix().'item_discount_sell_type')->row();
            }
            if (!$resolved && $sale_type_key !== '') {
                $resolved=$this->db->where('st_name',$sale_type_key)->get(db_prefix().'item_discount_sell_type')->row();
            }
            if ($resolved) $sale_type_key=(string)$resolved->st_id;
        }
        $size=(string)$this->input->get_post('size', true);
        $date=(string)$this->input->get_post('date', true);
        if(!$date) $date=date('Y-m-d');
        $product=['item_id'=>(int)$this->input->get_post('item_id',true),'commodity_code'=>trim((string)$this->input->get_post('commodity_code',true)),'reeds'=>trim((string)$this->input->get_post('reeds',true)),'density'=>trim((string)$this->input->get_post('density',true)),'weave_type'=>trim((string)$this->input->get_post('weave_type',true)),'design'=>trim((string)$this->input->get_post('design',true)),'size'=>$size];
        $rule=$this->product_sqm_pricing_model->find_rule($group_id,$sale_type_key,$date,$sale_type_name,$product);
        if(!$rule) return $this->json(['success'=>false,'code'=>'missing_rule','message'=>_l('psp_missing_rule')]);
        $method=isset($rule->pricing_method)?$rule->pricing_method:'sqm';
        if($method==='fixed'){
            $price=round((float)$rule->fixed_price,4);
            return $this->json(['success'=>true,'pricing_method'=>'fixed','area'=>null,'fixed_price'=>$price,'price'=>$price,'rule_id'=>(int)$rule->id,'valid_from'=>$rule->valid_from,'valid_to'=>$rule->valid_to]);
        }
        $area=$this->product_sqm_pricing_model->parse_area($size);
        if($area===false || $area<=0) return $this->json(['success'=>false,'code'=>'invalid_size','message'=>_l('psp_invalid_size')]);
        $price=round($area*(float)$rule->price_per_sqm,4);
        return $this->json(['success'=>true,'pricing_method'=>'sqm','area'=>$area,'price_per_sqm'=>(float)$rule->price_per_sqm,'price'=>$price,'rule_id'=>(int)$rule->id,'valid_from'=>$rule->valid_from,'valid_to'=>$rule->valid_to]);
    }
    private function json($data){ $this->output->set_content_type('application/json')->set_output(json_encode($data)); }
}
