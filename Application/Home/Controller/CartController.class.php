<?php

namespace Home\Controller;

class CartController extends BaseController {
    
    public $cartLogic; // 购物车逻辑操作类
    public $user_id = 0;
    public $user = array();    
    /**
     * 初始化函数
     */
    public function _initialize() {       
        parent::_initialize();
        $this->cartLogic = new \Home\Logic\CartLogic();
        
        $uid = session('uid');
      
        if(!empty($uid))
        {
            //$uid = session('uid');
          //  echo $uid;
                $user = M('users')->where("user_id = {$uid}")->find();
                session('user',$user);  //覆盖session 中的 user
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
            // 给用户计算会员价 登录前后不一样
            if($user){
                $user[discount] = (empty($user[discount])) ? 1 : $user[discount];
                M('Cart')->execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$this->session_id}') and prom_type = 0");
            }                    
        }                        
    }

    public function cart(){           
        $this->display();
    }
    
    public function index(){
    	$this->display('cart');
    }

    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id"); // 商品id
        $goods_num = I("goods_num");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格   

        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id); // 将商品加入购物车                     
        exit(json_encode($result));
        // $data['user_id'] = $_REQUEST['goods_id'];
        // $r = D('cart')->add($data);

    }    
    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {       
        $ids = I("ids"); // 商品 ids        
        $result = M("Cart")->where(" id in ($ids)")->delete(); // 删除id为5的用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态       
        exit(json_encode($return_arr));
    }    
    /*
     * ajax 请求获取购物车列表
     */
    public function ajaxCartList()
    {    
        $post_goods_num = I("goods_num"); // goods_num 购物车商品数量
        $post_cart_select = I("cart_select"); // 购物车选中状态                               
        $where = " session_id = '$this->session_id' "; // 默认按照 session_id 查询
        $this->user_id && $where = " user_id = ".$this->user_id; // 如果这个用户已经等了则按照用户id查询
        
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected,prom_type,prom_id"); 
        
        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {   
                $data['goods_num'] = $val < 1 ? 1 : $val;
                
                if($cartList[$key]['prom_type'] == 1) //限时抢购 不能超过购买数量
                {
                    $flash_sale = M('flash_sale')->where("id = {$cartList[$key]['prom_id']}")->find();
                    $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                }
                
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;                               
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected'])) 
                    M('Cart')->where("id = $key")->save($data);
            }
            $this->assign('select_all', $_POST['select_all']); // 全选框
        }
                
        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 选中的商品        
        if(empty($result['total_price']))
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
        
        $this->assign('cartList', $result['cartList']); // 购物车的商品                
        $this->assign('total_price', $result['total_price']); // 总计
        $this->display('ajax_cart_list');
    }
    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {        
        
        if($this->user_id == 0)
            $this->error('请先登陆',U('Home/Member/login'));
        
        if($this->cartLogic->cart_count($this->user_id,1) == 0 ) 
            $this->error ('你的购物车没有选中商品','Cart/cart');
        
        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品        

       // dump($result);
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司                
        
        $Model = new \Think\Model(); // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的               
        $sql = "select c1.name,c1.money,c1.condition, c2.* from __PREFIX__coupon as c1 inner join __PREFIX__coupon_list as c2  on c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0  where c2.uid = {$this->user_id}  and ".time()." < c1.use_end_time and c1.condition <= {$result['total_price']['total_fee']}";
        $couponList = $Model->query($sql);
        $uid = session('uid');
        $maps['user_id'] = $uid;
        $maps['is_default'] = 1;
        $info = D('user_address')->where($maps)->find();
        $this->assign('info',$info);      
        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品                
        $this->assign('total_price', $result['total_price']); // 总计
       
            $this->display("confirmation");
                                  
        
    }
   
    /*
     * ajax 获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress(){
        $model = M('UserAddress');
        $address_list = $model->where('user_id = '.$this->user_id.' AND is_pickup = 0')->select();
        if($address_list){
        	$area_id = array();
        	foreach ($address_list as $val){
        		$area_id[] = $val['province'];
                        $area_id[] = $val['city'];
                        $area_id[] = $val['district'];
                        $area_id[] = $val['twon'];                        
        	}    
                $area_id = array_filter($area_id);
        	$area_id = implode(',', $area_id);
        	$regionList = M('region')->where("id in ($area_id)")->getField('id,name');
        	$this->assign('regionList', $regionList);
        }
        $address_where['is_default'] = 1;
        $c = $model->where('user_id = '.$this->user_id.' AND is_default=1 AND is_pickup = 0')->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;
                     
        $this->assign('address_list', $address_list);
        $this->display('ajax_address');
    }

    public function test(){
        $user_id = 18991;
        echo crc32($user_id);
    }

    /**
     * @author dyr
     * @time 2016.08.22
     * 获取自提点信息
     */
    public function ajaxPickup()
    {
        $address_list = D('UserAddress')->getUserPickup($this->user_id);
        $province_id = I('province_id');
        $city_id = I('city_id');
        $district_id = I('district_id');
        if (empty($province_id) || empty($city_id) || empty($district_id)) {
            exit("<script>alert('参数错误');</script>");
        }
        $pickup_list = D('Pickup')->getPickupItemByPCD($province_id, $city_id, $district_id);
        $this->assign('pickup_list', $pickup_list);
        $this->assign('address_list', $address_list);
        $this->display('ajax_pickup');
    }

    /**
     * @author dyr
     * @time 2016.08.22
     * 更换自提点
     */
    public function replace_pickup()
    {
        $province_id = I('get.province_id');
        $city_id = I('get.city_id');
        $district_id = I('get.district_id');
        $region_model = M('region');
        $call_back = I('get.call_back');
        if (IS_POST) {
            echo "<script>parent.{$call_back}('success');</script>";
            exit(); // 成功
        }
        $address = array('province' => $province_id, 'city' => $city_id, 'district' => $district_id);
        $p = $region_model->where(array('parent_id' => 0, 'level' => 1))->select();
        $c = $region_model->where(array('parent_id' => $province_id, 'level' => 2))->select();
        $d = $region_model->where(array('parent_id' => $city_id, 'level' => 3))->select();
        $this->assign('province', $p);
        $this->assign('city', $c);
        $this->assign('district', $d);
        $this->assign('address', $address);
        $this->assign('call_back', $call_back);
        $this->display();
    }

    /**
     * @author dyr
     * @time 2016.08.22
     * 更换自提点
     */
    public function ajax_PickupPoint()
    {
        $province_id = I('province_id');
        $city_id = I('city_id');
        $district_id = I('district_id');
        $pick_up_model = D('Pickup');
        $pick_up_list = $pick_up_model->getPickupListByPCD($province_id,$city_id,$district_id);
        exit(json_encode($pick_up_list));
    }


    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
                                
        if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        $address_id = I("address_id"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号        
        $invoice_title = I('invoice_title'); // 发票
        $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id =  I("coupon_id"); //  优惠券id
        $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("pay_points",0); //  使用积分
        $user_money =  I("user_money",0); //  使用余额        
        $user_money = $user_money ? $user_money : 0;

        if($this->cartLogic->cart_count($this->user_id,1) == 0 ) exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        // if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
        if(!$shipping_code) exit(json_encode(array('status'=>-4,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态
		
		$address = M('UserAddress')->where("address_id = $address_id")->find();
		$order_goods = M('cart')->where("user_id = {$this->user_id} and selected = 1")->select();
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,0,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$couponCode);
                
		if($result['status'] < 0)	
			exit(json_encode($result));      	
	// 订单满额优惠活动		                
        $order_prom = get_order_promotion($result['result']['order_amount']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;
        
        $car_price = array(
            'postFee'      => $result['result']['shipping_price'], // 物流费
            'couponFee'    => $result['result']['coupon_price'], // 优惠券            
            'balance'      => $result['result']['user_money'], // 使用用户余额
            'pointsFee'    => $result['result']['integral_money'], // 积分支付            
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],// 商品价格            
            'order_prom_id' => $result['result']['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱
        );
       
        // 提交订单        
        if($_REQUEST['act'] == 'submit_order')
        {  
            if(empty($coupon_id) && !empty($couponCode))
               $coupon_id = M('CouponList')->where("`code`='$couponCode'")->getField('id');                    
            $result = $this->cartLogic->addOrder($this->user_id,$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price); // 添加订单                        
            exit(json_encode($result));            
        }
            $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
            exit(json_encode($return_arr));           
    }	
    /**
     * ajax 获取订单商品价格 或者提交 订单
	 * 已经用心方法 这个方法 cart9  准备作废
     */
   
    /*
     * 订单支付页面
     */
    public function cart4(){
        $order_id = I('order_id');        
        $order = M('Order')->where("order_id = $order_id")->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Home/User/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
        }      
        
        $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and  scene in(0,2)")->select();                        
        $paymentList = convert_arr_key($paymentList, 'code');
        
        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);            
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);        
            }                
        }                
        
        $bank_img = include 'Application/Home/Conf/bank.php'; // 银行对应图片             
        $this->assign('paymentList',$paymentList);        
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);        
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        $this->display();                   
    }
 
    
    //ajax 请求购物车列表
    public function header_cart_list()
    {
    	$cart_result = $this->cartLogic->cartList($this->user, $this->session_id,0,1);
    	if(empty($cart_result['total_price']))
    		$cart_result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0);
    	
    	$this->assign('cartList', $cart_result['cartList']); // 购物车的商品
    	$this->assign('cart_total_price', $cart_result['total_price']); // 总计
        $template = I('template','header_cart_list');    	 
        $this->display($template);		 
    }
    //读取表
    public function shopCart(){
        $this->display();
    }
    //商城首页
    public function vip_index(){
        if(session('uid')){
            $maps['is_recommend'] = 1;
            $maps['type'] = array('neq',2);
            // $maps['weixin'] = session('weixin');
            $info = D('goods')->where($maps)->order('last_update desc')->limit('10')->select();
            $this->assign('info',$info);
            $this->display();
         }else{
         $this->display("Member/login");
        }
    }
    public function do_cart(){
        $this->display("shopCar");
    }
    //积分兑换
    public function new_integral(){
         if(session('uid')){
                // $maps['store_count'] =  array('gt' , 0);
                $map['user_id'] = session('uid');
                $user = D('users')->where($map)->find();
                $maps['is_on_sale'] = 1;
                // $maps['type'] = 1;
                
                // $maps['type'] = array('in','0,1');//微信积分类型及混合类型
                $maps['_string'] = 'type=1 OR type=0';//微信积分类型及混合类型
                
                // $maps['weixin'] = session("weixin");
                $info = D("goods")->where($maps)->order("sort desc")->select();
                $this->assign('info',$info);
                $this->assign('user',$user);
                $points = getKeScore($user['user_id']);

                $this->assign('points',$points);
                $this->display();
         }else{
         $this->display("Member/login");
        }
    }
    //商品详情
    public function new_integral_conversion(){
        $maps['goods_id'] = $_REQUEST['goods_id'];
        $info = D('goods')->where($maps)->find();
        $uid = session('uid');
        $total = getKeScore($uid);
        if($info['goods_points']>$total){
            $this->error("积分不足");
        }
        $map['user_id'] = session('uid');
        $map['is_default'] = 1;
        $infos = D('user_address')->where($map)->find();
        $mapps['is_forbid'] = 0;
        $mapps['allow_pick'] = array('in','2,4');
        $r = D('store')->where($mapps)->select();
        //以服务顾问排行门店名称

        if ($uid) {
            //获取客户服务顾问
            $first_lead = M('users')->where('user_id='.$uid)->getField('first_leader');
            //获取客户服务顾问所在门店ID
            $store_id = M('admin')->where('admin_id='.$first_lead)->getField('store_id');
            $where['store_id'] = $store_id;
            $where['is_forbid'] = 0;
            $where['allow_pick'] = array('in','3,4');
            $newStore = M('store')->where($where)->find();
        }
        if ($newStore) {
            $where1['store_id'] = array('NEQ', $store_id);
        }
        $where1['is_forbid'] = 0;
        $where1['allow_pick'] = array('in','3,4');
        $res = D('store')->where($where1)->select();
        if ($newStore) {
            array_unshift($res,$newStore);
        }
        $this->assign('r',$r);
        $this->assign('res',$res);
        $this->assign('info',$info);
        $this->assign('infos',$infos);
        $this->display();
    }
    
    //兑换成功
    public function add(){
        $uid = session('uid');
        $total = getKeScore($uid);
        // 判断积分是否充足  $_REQUEST['final_points']  总消耗积分
        if($_REQUEST['final_points']>$total){
            $this->error("积分不足");
        }
        $maps['goods_id'] = $_REQUEST['goods_id'];
        $goods = D('goods')->where($maps)->find();
        $data['user_id'] = session('uid');
        $info = D('user_address')->where($data)->find();     
        $data['mobile'] = $_REQUEST['mobile'];
        $data['user_id'] = session('uid');
        $data['consignee'] = $_REQUEST['name'];
        $data['address'] = $_REQUEST['content'];
        if(empty($_REQUEST['name'])){
            $this->error("收货人未填");
        }
        if(empty($_REQUEST['mobile'])){
            $this->error("收货电话未填");
        }         
        if($_REQUEST['zt']){
            $datas['type'] = 1;
            if(empty($_REQUEST['store_id'])){
                $this->error("请选择门店");
            }
            $store_id = $_REQUEST['store_id'];
        }else{
            $datas['type'] = 2;
            if(empty($_REQUEST['content'])){
                $this->error("收货地址未填");
            } 
            //当兑换方式为送货时候默认门店为所属门店
            $store_id = $_REQUEST['new_store_id'];
        }

        //判断库存是否充足
        $this->checkGoodsNum($_REQUEST['goods_id'],$_REQUEST['duhuan_num'],$store_id,4);

        if(!$_REQUEST['zt']){
            if($info==false){
                $add = D('user_address')->add($data);
            }else{
                $maps['is_default'] = 1;
                $maps['user_id'] = session('uid');
                $add = D('user_address')->where($maps)->save($data);
            }
        }
        //开启事务
        $tranDb = M();
        $tranDb->startTrans();

        // 订单编号
        $order_sn = date('YmdHis').rand(1000,9999);

        $total = $_REQUEST['final_points'];                     
        $datas['user_id'] = session('uid');
        $datas['goods_id'] = $goods['goods_id'];
        $datas['goods_name'] = $goods['goods_name'];
        $datas['pay_points'] = $_REQUEST['final_points'];
        $datas['mobile'] = $_REQUEST['mobile'];
        $datas['consignee'] = $_REQUEST['name'];
        $datas['address'] = $_REQUEST['content'];       
        $datas['create_time'] =time();
        $datas['store_id'] = $store_id;
        $datas['goods_num'] = $_REQUEST['duhuan_num'];//数量
        $datas['pay_status'] = 0;//支付状态

        $cid = M('users')->where('user_id='.$datas['user_id'])->getField('first_leader');
        $datas['cid'] = $cid;//服务顾问ID

        $datas['order_sn'] = $order_sn;//订单编号

        $datas['source'] = 1;
        $total_points = getKeScore($user['user_id']);

        $add = D("send_points")->add($datas);
        //添加积分兑换子表
        if ($add) {
            $datas1['send_points_id'] = $add;
            $datas1['goods_id'] = $goods['goods_id'];
            $datas1['goods_num'] = $_REQUEST['duhuan_num'];
            $datas1['points'] = $_REQUEST['final_points'];
            $datas1['goods_name'] = $goods['goods_name'];
            $datas1['order_sn'] = $order_sn;
            $datas1['create_time'] = time();
            
            $add1 = D("send_points_detail")->add($datas1);
        }

        if ($add && $add1) {
            $tranDb->commit();
        }else{
            $tranDb->rollback();
            $this->error("兑换失败");
        }
       /* //修改库存
        $getInfo = jskc_new($goods['goods_id'],$datas['goods_num'],$store_id,4,1);


        if (!$getInfo) {
            $this->error("兑换失败");
            exit;
        }


        //新增门店流水记录
        $getInfo11 = addWaterRecord($goods['goods_id'],$datas['goods_num'],$store_id,2);
        if (!$getInfo11) {
            $this->error("兑换失败");
            exit;
        }

        //新增门店出库记录
        $result_outRecord = store_stock_out($store_id,$order_sn);
        if ($result_outRecord) {
            //新增门店出库记录详情表
           store_stock_out_detail($result_outRecord,$goods['goods_id'],$goods['goods_name'],$datas['goods_num']);
        }

        accountLog($datas['user_id'],0,$total,'积分兑换减少',0,3,1); 
        getTypes($datas['user_id'],0,2);//更换风格*/
        $mobile =  $_REQUEST['mobile'];
        $r['user_id'] = session('uid');
        $where = D('users')->where($r)->find();
        $level_id = $where['level'];
        // $use_points = getKeScore($r['user_id']);//可用积分
        $level_name = M('user_level')->where("level_id = $level_id")->getField('level_name');
        if($where['xingming']){
            $name = $where['xingming'];
        }else{
            $name = $where['nickname'];
        }
        // $content="尊敬的".$level_name.$name."，此次兑换共使用".$total."积分，剩余积分".$use_points."，消费赠积分，好礼兑不停！关注公众号，看看您有多少积分吧…";
        $content="尊敬的".$level_name.$name."，此次兑换共使用".$total."积分，消费赠积分，好礼兑不停！关注公众号，看看您有多少积分吧…";
        $user_mobile = getUserMobile(session('uid'));
        $res=sendsmss($user_mobile,$content);
        if($add){
            $this->redirect('Cart/new_success', array('goods_id' =>  $maps['goods_id']));
        }else{
            $this->error("兑换失败");
        }
    }
    //兑换列表
    public function new_history(){
        $maps['user_id'] = session('uid');
        $info = D('send_points')->where($maps)->order("id desc")->select();
        if (!$info) {
           $this->error('暂无数据',U('Home/cart/new_integral'));
           die;
        }
        $this->assign('info',$info);
        $this->display();

    }
    public function ajax_integral(){
        $type = $_REQUEST['p'];
        if($type==1){
            $map['user_id'] = session('uid');
            $maps['is_on_sale'] = 1;
            $maps['type'] = 1;
            $maps['store_count'] =  array('neq','0');
            $maps['weixin'] = session('weixin');
            $info = D("goods")->where($maps)->order("sort desc")->select(); 
        }elseif($type==2){
             $map['user_id'] = session('uid');
            $maps['is_on_sale'] = 1;
            $maps['type'] = 1;
                 $maps['weixin'] = session('weixin');
            $info = D("goods")->where($maps)->order("sort desc")->select(); 
        }
            $this->assign('info',$info);
            $this->display();
    }
    public function new_success(){
        $maps['goods_id'] = $_REQUEST['goods_id'];
        $info = D('goods')->where($maps)->find();
        $this->assign('info',$info);
        $this->display();
    }
}

