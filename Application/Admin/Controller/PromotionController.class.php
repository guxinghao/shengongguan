<?php
/**
 * Author: ericyang      
 * Date: 2017-05-21
 */

namespace Admin\Controller;
use Admin\Logic\OrderLogic;
use Think\AjaxPage;
use Admin\Logic\GoodsLogic;

class PromotionController extends BaseController {

    public  $order_status;
    public  $pay_status;
    public  $shipping_status;
    /*
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
        C('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_status = C('ORDER_STATUS');
        $this->pay_status = C('PAY_STATUS');
        $this->shipping_status = C('SHIPPING_STATUS');
        // 订单 支付 发货状态
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
    }


    /*
     *订单首页
     */
    public function index(){
        $begin = date('Y/m/d',(time()-30*60*60*24));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));  
        $this->assign('timegap',$begin.'-'.$end);
        $this->display();
    }

    /*
     *Ajax首页
     */
    public function ajaxindex(){
        $orderLogic = new OrderLogic();       
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }
        // 搜索条件
        $condition = array();
        I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
        I('order_sn') ? $condition['order_sn'] = trim(I('order_sn')) : false;
        I('order_status') != '' ? $condition['order_status'] = I('order_status') : false;
        I('pay_status') != '' ? $condition['pay_status'] = I('pay_status') : false;
        I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status') : false;
        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = M('order')->where($condition)->count();

        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $Page  = new AjaxPage($count,$pageCount);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }
        $show = $Page->show();
        //获取订单列表
        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    /**
     * 入库列表
     */
	public function prom_goods_list()
	{  
		// $Ad =  M('in_storage_stocks');

		$keywords=I("keywords");
		$user_name=I("user_name");
        $in_storage_type=I("status");  //入库类型 0 新增入库 1 退货入库 2 转库入库
        $repertory_id_outer=I("repertory_id_outer");  //出库仓库ID
        $repertory_id=I("repertory_id");  //入库仓库ID

		$start_time=I("start_time");
        $end_time=I("end_time");

        $sku = urldecode(I("sku"));
        $goods_name = urldecode(I("goods_name"));
        //入库类型查询条件
		if(!empty($in_storage_type)){

			if($in_storage_type==1){
				$maps['tp_in_storage_stocks.in_storage_type']=0;
			}elseif($in_storage_type==2){
				$maps['tp_in_storage_stocks.in_storage_type']=1;
			}else{
                $maps['tp_in_storage_stocks.in_storage_type']=2;
            }
		}

        //入库时间查询条件
		if($start_time!=''&&$end_time!=''){
			$start_time=strtotime($start_time);
            $end_time=strtotime($end_time)+24*3600-1;

			$maps['tp_in_storage_stocks.create_time']=array(array('gt',$start_time),array('lt',$end_time));

		}elseif($start_time!=''){
			$start_time=strtotime($start_time);
			$maps['tp_in_storage_stocks.create_time']=array('gt',$start_time);
		}elseif($end_time!=''){
			$end_time=strtotime($end_time)+24*3600-1;
			$maps['tp_in_storage_stocks.create_time']=array('lt',$end_time);
		}
        //出库仓库搜索条件
        if(!empty($repertory_id_outer)){
            $maps['tp_in_storage_stocks.repertory_id_outer']=$repertory_id_outer;
        }

        //入库仓库搜索条件
        if(!empty($repertory_id)){
            $maps['tp_in_storage_stocks.repertory_id']=$repertory_id;
        }

        //商品编号搜索
        if($sku){
            $getgood['sku'] = $sku;
            $goods_id = M('spec_goods_price')->where($getgood)->getField('goods_id');
            $maps['tp_warehousing_detail.goods_id']=$goods_id;
        }
        //商品名称搜索
        if(!empty($goods_name)){
            $maps['tp_warehousing_detail.goods_name'] = array('like',"%".$goods_name."%");
        }

        $count = M('in_storage_stocks')->join('tp_warehousing_detail ON tp_warehousing_detail.storage_stocks_id = tp_in_storage_stocks.id','right')->where($maps)->count();
        // $count = $Ad->where($maps)->count();

        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page = new \Think\Page($count,$pageCount);
        $list = M('in_storage_stocks')->join('tp_warehousing_detail ON tp_warehousing_detail.storage_stocks_id = tp_in_storage_stocks.id','right')->field('tp_in_storage_stocks.*,  tp_warehousing_detail.goods_id, tp_warehousing_detail.goods_name, tp_warehousing_detail.count')->where($maps)->order('tp_in_storage_stocks.id desc')->limit($Page->firstRow.','.$Page->listRows)->select(); 
        if (I('repertory_id_outer')) {
            $Page->parameter['repertory_id_outer'] = I('repertory_id_outer');
            $this->assign('repertory_id_outer',$repertory_id_outer);
        }

        if (I('repertory_id')) {
            $Page->parameter['repertory_id'] = I('repertory_id');
            $this->assign('repertory_id',$repertory_id);
        }

        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode($pageCount);
            $this->assign('_pageCount',$pageCount);
        }

        if ($in_storage_type) {
            $Page->parameter['in_storage_type'] = urlencode($in_storage_type);
            $this->assign('in_storage_type',$in_storage_type);
        }

        if (I("start_time")) {
            $Page->parameter['start_time'] = I("start_time");
            $this->assign('start_time',I("start_time"));
        }

        if (I("end_time")) {
            $Page->parameter['end_time'] = I("end_time");
            $this->assign('end_time',I("end_time"));
        }

        if (I("sku")) {
            $Page->parameter['sku'] = urlencode(urldecode(I("sku")));
            $this->assign('sku',urldecode(I("sku")));
        }

        if (I("goods_name")) {
            $Page->parameter['goods_name'] = urlencode(urldecode(I("goods_name")));
            $this->assign('goods_name',urldecode(I("goods_name")));
        }

        $now = date('Y-m-d', time());
        $this->assign('now', $now);
        //获取仓库列表
        $repertory_name = M('repertory')->where('status=0 and is_del=0')->field('id,repertory_name')->select();
        $this->assign('list',$list);
        $this->assign('repertory_name',$repertory_name);
        $show = $Page->show();
        $this->assign('page',$show);
        $this->display();
	}
	
	public function prom_goods_info()
	{
		$id=$_REQUEST['id'];
        $maps['id']=$id;
        $book=M("in_storage_stocks")->where($maps)->find();

        $where['storage_stocks_id']=$id;
        $book_goods=D("warehousing_detail")->where($where)->select();
        $coust="";
        foreach ($book_goods as $key => $value) {
            $cost_price = M('goods')->where('goods_id='.$value['goods_id'])->getField('cost_price');
            $book_goods[$key]['cost_price'] = $cost_price;//成本价
            $book_goods[$key]['total_money'] = $cost_price*$value['count'];//成本价
            $coust+=$value['count']*$cost_price;
        }
        $book['coustprice']=$coust;

        $this->assign('book',$book);
        $this->assign('book_goods',$book_goods);
        $this->display();
	}
	
	public function prom_goods_save()
	{
		$prom_id = I('id');
		$data = I('post.');
		$data['start_time'] = strtotime($data['start_time']);
		$data['end_time'] = strtotime($data['end_time']);
		$data['group'] = implode(',', $data['group']);
		if($prom_id){
			M('prom_goods')->where("id=$prom_id")->save($data);
			$last_id = $prom_id;
			adminLog("管理员修改了商品促销 ".I('name'));
		}else{
			$last_id = M('prom_goods')->add($data);
			adminLog("管理员添加了商品促销 ".I('name'));
		}
		
		if(is_array($data['goods_id'])){
			$goods_id = implode(',', $data['goods_id']);
			if($prom_id>0){
				M("goods")->where("prom_id=$prom_id and prom_type=3")->save(array('prom_id'=>0,'prom_type'=>0));
			}
			M("goods")->where("goods_id in($goods_id)")->save(array('prom_id'=>$last_id,'prom_type'=>3));
		}
		$this->success('编辑促销活动成功',U('Promotion/prom_goods_list'));
	}
	
	public function prom_goods_del()
	{
		$prom_id = I('id');                
                $order_goods = M('order_goods')->where("prom_type = 3 and prom_id = $prom_id")->find();
                if(!empty($order_goods))
                {
                    $this->error("该活动有订单参与不能删除!");    
                }                
		M("goods")->where("prom_id=$prom_id and prom_type=3")->save(array('prom_id'=>0,'prom_type'=>0));
		M('prom_goods')->where("id=$prom_id")->delete();
		$this->success('删除活动成功',U('Promotion/prom_goods_list'));
	}
    

    
        /**
         * 入库
         */
	public function prom_order_list()
	{
	   $Ad =  M('order');
       $maps1['pay_status']=1;
       $list = $Ad->order('order_id desc')->where($maps1)->select();
       //echo M()->getlastsql();
       //exit;

        

       if($list){
            $order_id=formatArray($list,'order_id');
            $maps['order_id']=array('in',$order_id);
            $count = $Ad->where($maps)->count();

            $pageCount = $_GET['pageCount'];

            if ($pageCount==='undefined' || !$pageCount) {
                $pageCount = 25;
            }

            $Page = new \Think\Page($count,$pageCount);        
            $list = $Ad->order('order_id desc')->where($maps)->limit($Page->firstRow.','.$Page->listRows)->select();
            $show = $Page->show();
            $this->assign('page',$show);
            $this->assign('list',$list);
       }else{

       }
        
         $this->display();
        
	}
	
	public function prom_order_info(){
		$this->assign('min_date',date('Y-m-d'));
		$level = M('user_level')->select();
		$this->assign('level',$level);
		$prom_id = I('id');
		$info['start_time'] = date('Y-m-d');
		$info['end_time'] = date('Y-m-d',time()+3600*24*60);
		if($prom_id>0){
			$info = M('prom_order')->where("id=$prom_id")->find();
			$info['start_time'] = date('Y-m-d',$info['start_time']);
			$info['end_time'] = date('Y-m-d',$info['end_time']);
		}
		$this->assign('info',$info);
		$this->assign('min_date',date('Y-m-d'));
		$this->initEditor();
		$this->display();
	}
	
	public function prom_order_save(){
		$prom_id = I('id');
		$data = I('post.');
		$data['start_time'] = strtotime($data['start_time']);
		$data['end_time'] = strtotime($data['end_time']);
		$data['group'] = implode(',', $data['group']);
		if($prom_id){
			M('prom_order')->where("id=$prom_id")->save($data);
			adminLog("管理员修改了商品促销 ".I('name'));
		}else{
			M('prom_order')->add($data);
			adminLog("管理员添加了商品促销 ".I('name'));
		}
		$this->success('编辑促销活动成功',U('Promotion/prom_order_list'));
	}
	
	public function prom_order_del()
	{
		$prom_id = I('id');                                
                $order = M('order')->where("order_prom_id = $prom_id")->find();
                if(!empty($order))
                {
                    $this->error("该活动有订单参与不能删除!");    
                }
                                
		M('prom_order')->where("id=$prom_id")->delete();
		$this->success('删除活动成功',U('Promotion/prom_order_list'));
	}
	
    public function group_buy_list(){
    	$Ad =  M('group_buy');
    	$count = $Ad->count();
        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page = new \Think\Page($count,$pageCount);        
    	$res = $Ad->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	if($res){
    		foreach ($res as $val){
    			$val['start_time'] = date('Y-m-d H:i',$val['start_time']);
    			$val['end_time'] = date('Y-m-d H:i',$val['end_time']);
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->display();
    }

     public function order_buy_list(){
        $Ad =  M('replenishment');
	     $keywords=I("keywords");
	     $user_name=I("user_name");
	     $start_time=I("start_time");
	     $status=I("status");
	     $end_time=I("end_time");
	     if(!empty($keywords)){
		     $maps['order_sn']=array('like','%'.$keywords.'%');
	     }
	     if(!empty($user_name)){
		     	$vmaps['name']=$user_name;
		     $admin=D("Admin")->where($vmaps)->find();
		     if($admin){
			     $maps['cid']=$admin['admin_id'];
		     }else{
			     $maps['cid']=0;
		     }
	     }
	     if(!empty($status)){

		     if($status==1){
			     $maps['shipping_status']=0;
			     //$maps['pay_status']=1;
		     }elseif($status==2){
			     $maps['shipping_status']=1;
		     }
	     }
         // 门店查询条件
        if(!empty($_POST['store_id'])){
            $maps['store_id'] = $_POST['store_id'];
            $this->assign('store_id',$_POST['store_id']);
        }

	     if($start_time!=''&&$end_time!=''){
		     $start_time=strtotime($start_time);
		     $end_time=strtotime($end_time)-24*3600-10;

		     $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));

	     }elseif($start_time!=''){
		     $start_time=strtotime($start_time);
		     $maps['add_time']=array('gt',$start_time);
	     }elseif($end_time!=''){
		     $end_time=strtotime($end_time)-24*3600-10;
		     $maps['add_time']=array('lt',$end_time);
	     }



        // $maps['pay_status']=0;
        $count = $Ad->where($maps)->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page = new \Think\Page($count,$pageCount);        
        $list = $Ad->order('id desc')->where($maps)->limit($Page->firstRow.','.$Page->listRows)->select();
	    // echo M()->getlastsql();
        // foreach ($list as $key => $value) {
        //     $maps['admin_id']=$value['cid'];
        //     $admin=D("Admin")->where($maps)->find();

        //     $list[$key]['ordername']=$admin['name'];
        // }
        
        //dump($list);
        //所有门店列表
        $store = D('store')->field('store_name,store_id')->select();
        
        $this->assign('list',$list);
        $this->assign('store',$store);
        $show = $Page->show();
        $this->assign('page',$show);
        $this->display();
    }

    //查看订货详情
    public function book_order_info(){
        $id=$_REQUEST['id'];
        $maps['id']=$id;
        $book=M("replenishment")->where($maps)->find();

        // $maps1['admin_id']=$book['cid'];
        // $admin=D("Admin")->where($maps1)->find();

        // $list[$key]['ordername']=$admin['name'];
        $maps1['replenishment_order_id']=$id;
        $book_goods=D("replenishment_detail_final")->where($maps1)->select();
        $this->assign('book',$book);
        $this->assign('book_goods',$book_goods);
        $this->display();
    }
    //发货操作
    public function add_order(){
        $id=$_REQUEST['order_id'];
        $data['order_id']=$id;
        $data['shipping_status']=1;
        $data['shipping_uid']=session('admin_id');
        $data['shipping_time']=time();
        $book=D("book")->save($data);
        if($book){
            $this->success("发货成功",U("Promotion/order_buy_list"));
        }else{
              $this->success("发货失败");
        }
    }

     //发货操作
    public function add_store(){
        $id=$_REQUEST['order_id'];
        $data['order_id']=$id;
        $data['pay_status']=1;
        //$data['shipping_uid']=session('admin_id');
        $data['confirm_time']=time();
        $book=D("book")->save($data);
        if($book){

            
                $maps['order_id']=$id;

                $list=D("book_goods")->where($maps)->select();
                //dump($list);

                if($list){
                    $book=D("book")->where($maps)->find(); 
                    $mapss['admin_id']=$book['cid'];
                    $admin=D("admin")->where($mapss)->find();

                    foreach ($list as $key => $value) {
                        $data1['store_id']=$admin['store_id'];
                        $data1['goods_id']=$value['goods_id'];
                        $data1['goods_name']=$value['goods_name'];
                        $data1['goods_sn']=$value['goods_sn'];
                        $data1['goods_price']=$value['goods_price'];
                        $data1['sku']=$value['sku'];
                        $data1['spec_key']=$value['spec_key'];
                        $data1['spec_key_name']=$value['spec_key_name'];
                        $data1['into_stock']=$value['goods_num'];

                        D("store_data")->add($data1);
                       // echo M()->getlastsql();
                    }
                }
            
            $this->success("入库成功",U("Promotion/prom_goods_list"));
        }else{
              $this->success("入库失败");
        }
    }

    public function group_buy(){
    	$act = I('GET.act','add');
    	$groupbuy_id = I('get.id');
    	$group_info = array();
    	$group_info['start_time'] = date('Y-m-d');
    	$group_info['end_time'] = date('Y-m-d',time()+3600*365);
    	if($groupbuy_id){
    		$group_info = D('group_buy')->where('id='.$groupbuy_id)->find();
    		$group_info['start_time'] = date('Y-m-d H:i',$group_info['start_time']);
    		$group_info['end_time'] = date('Y-m-d H:i',$group_info['end_time']);
    		$act = 'edit';
    	}
    	$this->assign('min_date',date('Y-m-d'));
    	$this->assign('info',$group_info);
    	$this->assign('act',$act);
    	$this->display();
    }
    
    public function groupbuyHandle(){
    	$data = I('post.');
    	$data['groupbuy_intro'] = htmlspecialchars(stripslashes($_POST['groupbuy_intro']));
    	$data['start_time'] = strtotime($data['start_time']);
    	$data['end_time'] = strtotime($data['end_time']);
    	if($data['act'] == 'del'){
    		$r = D('group_buy')->where('id='.$data['id'])->delete();
    		M('goods')->where("prom_type=2 and prom_id=".$data['id'])->save(array('prom_id'=>0,'prom_type'=>0));
    		if($r) exit(json_encode(1));
    	}
    	if($data['act'] == 'add'){
    		$r = D('group_buy')->add($data);
    		M('goods')->where("goods_id=".$data['goods_id'])->save(array('prom_id'=>$r,'prom_type'=>2));
    	}
    	if($data['act'] == 'edit'){
    		$r = D('group_buy')->where('id='.$data['id'])->save($data);
    		M('goods')->where("prom_type=2 and prom_id=".$data['id'])->save(array('prom_id'=>0,'prom_type'=>0));
    		M('goods')->where("goods_id=".$data['goods_id'])->save(array('prom_id'=>$data['id'],'prom_type'=>2));
    	}
    	if($r){
    		$this->success("操作成功",U('Admin/Promotion/group_buy_list'));
    	}else{
    		$this->error("操作失败",U('Admin/Promotion/group_buy_list'));
    	}
    }
    
    public function get_goods(){
    	$prom_id = I('id');
    	$count = M('goods')->where("prom_id=$prom_id and prom_type=3")->count(); 
        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page  = new \Think\Page($count,$pageCount);
    	$goodsList = M('goods')->where("prom_id=$prom_id and prom_type=3")->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->assign('goodsList',$goodsList);
    	$this->display(); 
    }   
    
    public function search_goods(){
    	$GoodsLogic = new \Admin\Logic\GoodsLogic;
    	$brandList = $GoodsLogic->getSortBrands();
    	$this->assign('brandList',$brandList);
    	$categoryList = $GoodsLogic->getSortCategory();
    	$this->assign('categoryList',$categoryList);
    	
    	$goods_id = I('goods_id');
    	$where = ' is_on_sale = 1 and prom_type=0 and store_count>0 ';//搜索条件
    	if(!empty($goods_id)){
    		$where .= " and goods_id not in ($goods_id) ";
    	}
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));
    		$grandson_ids = getCatGrandson(I('cat_id'));
    		$where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
    	}
    	if(I('brand_id')){
    		$this->assign('brand_id',I('brand_id'));
    		$where = "$where and brand_id = ".I('brand_id');
    	}
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}
    	$count = M('goods')->where($where)->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page  = new \Think\Page($count,$pageCount);

    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$show = $Page->show();//分页显示输出
    	$this->assign('page',$show);//赋值分页输出
    	$this->assign('goodsList',$goodsList);
    	$tpl = I('get.tpl','search_goods');
    	$this->display($tpl);
    }
    
    //限时抢购
    public function flash_sale(){
    	
    }
    
    public function flash_sale_info(){
    	if(IS_POST){
    		$data = I('post.');
    		$data['start_time'] = strtotime($data['start_time']);
    		$data['end_time'] = strtotime($data['end_time']);
    		if(empty($data['id'])){
    			$r = M('flash_sale')->add($data);
    			M('goods')->where("goods_id=".$data['goods_id'])->save(array('prom_id'=>$r,'prom_type'=>1));
    			adminLog("管理员添加抢购活动 ".$data['name']);
    		}else{
    			$r = M('flash_sale')->where("id=".$data['id'])->save($data);
    			M('goods')->where("prom_type=1 and prom_id=".$data['id'])->save(array('prom_id'=>0,'prom_type'=>0));
    			M('goods')->where("goods_id=".$data['goods_id'])->save(array('prom_id'=>$data['id'],'prom_type'=>1));
    		}
    		if($r){
    			$this->success('编辑抢购活动成功',U('Promotion/flash_sale'));
    			exit;
    		}else{
    			$this->error('编辑抢购活动失败',U('Promotion/flash_sale'));
    		}
    	}
    	$id = I('id');
        $info['start_time'] = date('Y-m-d H:i:s');
    	$info['end_time'] = date('Y-m-d 23:59:59',time()+3600*24*60);
    	if($id>0){
    		$info = M('flash_sale')->where("id=$id")->find();
    		$info['start_time'] = date('Y-m-d H:i',$info['start_time']);
    		$info['end_time'] = date('Y-m-d H:i',$info['end_time']);
    	}
    	$this->assign('info',$info);
    	$this->assign('min_date',date('Y-m-d'));
    	$this->display();
    }
    
    public function flash_sale_del(){
    	$id = I('del_id');
    	if($id){
    		M('flash_sale')->where("id=$id")->delete();
    		M('goods')->where("prom_type=1 and prom_id=$id")->save(array('prom_id'=>0,'prom_type'=>0));
    		 exit(json_encode(1));
    	}else{
    		 exit(json_encode(0));
    	}
    }
    
    private function initEditor()
    {
    	$this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'promotion')));
    	$this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'promotion')));
    	$this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'promotion')));
    	$this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'promotion')));
    	$this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'promotion')));
    	$this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'promotion')));
    	$this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'promotion')));
    	$this->assign("URL_Home", "");
    }
    
}