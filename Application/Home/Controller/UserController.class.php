<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-22
 */namespace Home\Controller;
use Think\Page;
use Think\Verify;
class UserController extends BaseController {
    
 
    //食参客
    //海参下午茶
    public function index(){
        $maps['cate_id'] = 1;
        $info = D('activity')->where($maps)->order("activity_id desc")->find();
        $this->assign('info',$info);
        $this->display();
    }
        //活动列表
        public function activity_1(){
        if(session('uid')){
            $cate_id = $_REQUEST['cate_id'];
            if($cate_id){
                 $maps['cate_id'] = $cate_id;
            }          
            $info = M('activity')->where($maps)->order('update_time desc')->limit("10")->select();
            foreach ($info as $key => $value) {
                $mapss['activity_id']=$value['activity_id'];
                $mapss['status']=1;
                $counts=D("sign_up")->where($mapss)->count();            
                $info[$key]['enter_count']= $counts;               
            }
            $this->assign('info',$info);
            $this->display();
             }else{
         $this->display("Member/login");
            }
        }
    public function ajax_activity_1(){
        $page = $_REQUEST['p'];
        $start = $page*10;     
        $info = M('activity')->order('update_time desc')->limit("$start,10")->select();
        $this->assign('info',$info);
        $this->display();
    }
    //马上预约
    public function activity(){
        if(session('uid')){
            $activity_id = $_REQUEST['activity_id'];
            $maps['activity_id'] = $activity_id;
            $info = M('activity')->where($maps)->find();
            $this->assign('info',$info);
            $this->display();
        }else{
            $this->display("Member/login");
        }
    }
    //支付
    public function pay(){
        if(session('uid')){
            $uid=session('uid');
            $activity_id = $_REQUEST['activity_id'];
            $maps['activity_id'] = $activity_id;
            $info = M('activity')->where($maps)->find();
            $maps['uid']=$uid;
            $maps['status'] = 1;
            $maps['pay_status'] = 2;
            $res=D("sign_up")->where($maps)->find();
            if($res==false){
                $this->assign("info",$info);
                $this->display();
            }else{
                $this->assign("info",$info);
                $this->success("已支付",U('Home/User/activity'));
            }
        }else{
         $this->display("Member/login");
        }
    }
    //预约成功
    public function imm_order(){
        $uid=session('uid');
        $activity_id = $_REQUEST['activity_id'];
        $price1 = $_REQUEST['payMoney'];
        $title = $_REQUEST['title'];
        $r['sn'] = date('YmdHis').rand(1000,9999);
        $r['uid'] = $uid;
        $r['activity_id'] = $activity_id;
        $r['price'] = $price1;
        $r['status'] = 0;
        $r['pay_status'] = 1;
        $r['create_time'] = time();
        $result = D("sign_up")->add($r);
        if ($result) {
            $data = array('code'=>1, 'fee'=>$price1, 'trade_no'=>$r['sn'], 'title'=>$title);
        }else{
            $data = array('code'=>0, 'fee'=>$price1, 'trade_no'=>$r['sn'], 'title'=>$title);
        }
        echo json_encode($data);
        exit;
    }

    //跳转页面
    public function jsapi(){
        ini_set('date.timezone','Asia/Shanghai');
        vendor("WxPay.JsApiPay");

        $fee = $_REQUEST['fee'];
        $trade_no = $_REQUEST['trade_no'];
        
        $fee = $fee*100;
        $trade_no = $trade_no;
        $tools = new \JsApiPay();

        //①、获取用户openid
        // $openId = $tools->GetOpenid();
        $openId = session('openid');
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("参公馆");
        $input->SetAttach("参公馆");
        $input->SetOut_trade_no($trade_no);
        $input->SetTotal_fee($fee);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("参公馆");
        $notify_url = U('user/pay_true',array('trade_no'=>$trade_no));
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $editAddress = $tools->GetEditAddressParameters();

        $this->assign('trade_no',$trade_no);
        $this->assign('openId',$openId);
        $this->assign('jsApiParameters',$jsApiParameters);
        $this->assign('editAddress',$editAddress);
        $this->display(); 
    }

     //跳转页面
    public function pay_success(){
        // $trade_no = $_REQUEST['trade_no'];
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $obj = simplexml_load_string($xml);
        $trade_no = $obj->out_trade_no;

        $data['pay_status'] = 2;
        $data['update_time'] = time();
        $result = D('sign_up')->where('sn='.$trade_no)->save($data);
        // if ($result) {
        //     $this->display('user/pay_success');
        // }
    }

    //跳转页面  支付成功跳转通知页面 
    public function pay_true(){
        $this->display();
    }



    //下次预约
    public function apply(){
        if(session('uid')){
            $this->display();
        }else{
         $this->display("Member/login");
        }
    }
    public function apply_a(){
        $name = $_REQUEST['name'];
        $mobile = $_REQUEST['mobile'];
        session('uid',4);
        $uid=session('uid');
        $maps['uid']=$uid;
        $res=D("appaly")->where($maps)->find();
        if($res==false){
            $r['sn'] = date('YmdHis').rand(1000,9999);
            $r['uid'] = $uid;
            $r['activity_id'] = 0;
            $r['price'] = 0;
            $r['status'] = 0;
            $r['create_time'] = time();
            $r['mobile'] = $mobile;
            $r['name'] = $name;
            D("appaly")->add($r);
            $this->success("报名成功",U('Home/User/index'));
        }else{
            $this->error('报名失败');
        }   
    }


    //食参客分享
    public function Diners_share(){
        if(session('uid')){
        $url = C('http_urls');
        $type = $_REQUEST['type'];
        if($type==1){
            $maps['is_top'] = 1;           
            $info = D('share')->where($maps)->order("id desc")->limit("10")->select();
        }else if($type==2){
            $maps['user_id'] = session("uid");
            $info = D('share')->where($maps)->order("id desc")->limit("10")->select();
        }else{
            $info = D('share')->where($maps)->order("id desc")->limit("10")->select();
        }
        
        foreach ($info as $key => $value) {
            if($value['cate_id']==1){
                    $info[$key]['t'] = "泡发窍门";
                }else if($value['cate_id']==2){
                    $info[$key]['t'] = "厨艺分享";
                }else if($value['cate_id']==3){
                    $info[$key]['t'] = "食参感受";
                }else if($value['cate_id']==4){
                    $info[$key]['t'] = "其他分享";           
                }
                $info[$key]['zana'] = $value['zan'] + 1;
                $where['pid'] =$value['id'];        
                $info[$key]['data'] = M('share_pic')->where($where)->order("id desc")->limit("3")->select();
        }
        $this->assign('url',$url);
        $this->assign('info',$info);
        $this->display();
         }else{
         $this->display("Member/login");
        }
    }
    public function ajax_Diners(){
        $page = $_REQUEST['p'];
        $start = $page*10;     
        $url = C('url');
        $type = $_REQUEST['type'];
        $maps['status'] = 1;
        if($type==1){
            $maps['is_top'] = 1;
            $info = D('share')->where($maps)->order("id desc")->limit("$start,10")->select();
        }else if($type==2){
            $maps['user_id'] = session("uid");
            $info = D('share')->where($maps)->order("id desc")->limit("$start,10")->select();
        }else{
            $info = D('share')->where($where)->order("id desc")->limit("$start,10")->select();
        }        
        foreach ($info as $key => $value) {
            if($value['cate_id']==1){
            $info[$key]['t'] = "泡发窍门";
        }else if($value['cate_id']==2){
            $info[$key]['t'] = "厨艺分享";
        }else if($value['cate_id']==3){
            $info[$key]['t'] = "食参感受";
        }else if($value['cate_id']==4){
            $info[$key]['t'] = "其他分享";           
        }
        $info[$key]['zana'] = $value['zan'] + 1;
        $where['pid'] =$value['id'];        
        $info[$key]['data'] = M('share_pic')->where($where)->order("id desc")->limit("3")->select();
    }
   
        $this->assign('url',$url);
        $this->assign('info',$info);

        $this->display();
    }
        //我要分享
        public function share(){
             if(session('uid')){
            $this->display();
             }else{
         $this->display("Member/login");
            }
        }
        public function share_a(){
            $r['title']= $_REQUEST['title'];
            $r['cate_id'] = $_REQUEST['cate_id'];
            $r['content'] = $_REQUEST['content'];
            $r['create_time'] = time();
            $r['update_time'] = time();
            $r['user_id'] = session('uid');
            
            $add = M('share')->add($r);
          //  echo M()->getlastsql(); 

            if($add){
                 $pics = $_REQUEST['images'];
                 // $info = M('share')->where($r)->find();
                    foreach ($pics as $key => $value) {
                        $maps['pid'] = $add;
                        $maps['image'] = $pics[$key];
                        $maps['create_time'] = time();
                        $pic = M('share_pic')->add($maps);

                    }          
                 $this->success("分享成功",U('Home/User/Diners_share'));
            }else{
                $this->error("分享失败");
            }          
        }
    //评价你的顾问
    public function consultant_Evaluation(){
        if(session('uid')){
            $maps['role_id'] = 5;
            $star = $_REQUEST['stars'];
            $shop = $_REQUEST['shops'];
            $search = $_REQUEST['search'];
            if(empty($search)){
                    if($star==0){

                }else{
                    $maps['star'] = $_REQUEST['stars'];
                }
                if($shop==0){

                }else{
                    $maps['store_id'] = $shop;
                }            
            }else{
                $maps['name'] =  $search;
                $shop = 0;
                $star = 0;
            }
            $info = D('admin')->where($maps)->select();
            foreach ($info as $key => $value) {
                $maps['store_id'] = $value['store_id'];
                $r = D('store')->where($maps)->find();
                $info[$key]['store_name'] = $r['store_name'];
                $info[$key]['zana'] = $value['zan'] + 1;
                $info[$key]['caia'] = $value['cai'] + 1;
                if($value['star']==0){
                    $info[$key]['stars'] = '<li class="fl star-a"></li><li class="fl star-a"></li><li class="fl star-a "></li><li class="fl star-a"></li><li class="fl star-a"></li>';
                }elseif ($value['star']==1) {
                    $info[$key]['stars'] = '<li class="fl star-a star"></li><li class="fl star-a "></li><li class="fl star-a "></li><li class="fl star-a"></li><li class="fl star-a"></li>';
                }elseif ($value['star']==2) {
                    $info[$key]['stars'] = '<li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a "></li><li class="fl star-a"></li><li class="fl star-a"></li>';
                }elseif ($value['star']==3) {
                    $info[$key]['stars'] = '<li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a"></li><li class="fl star-a"></li>';
                }elseif ($value['star']==4) {
                    $info[$key]['stars'] = '<li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a"></li>';
                }elseif ($value['star']==5) {
                    $info[$key]['stars'] = '<li class="fl star-a star"></li><li class="fl star-a star"></li><li class="fl star-a    star"></li><li class="fl star-a"></li><li class="fl star-a star"></li>';
                    }
                $mapss['admin_id'] = $value['admin_id'];
                $mapss['user_id'] = session('uid');
                $r = D('zan')->where($mapss)->find();
                if($r){
                    $info[$key]['types'] = $r['type'];
                }else{
                    $info[$key]['types'] = 2;
                }
            }
            $data = D('store')->select();
            $this->assign('data',$data);
            $this->assign('info',$info);
            $this->assign('star',$star);
            $this->assign('shop',$shop);
            $this->assign("search", $search);
            $this->display();
         }else{
         $this->display("Member/login");
        }
    }

        //服务顾问详情
        public function consultant(){
        if(session('uid')){
            
        $this->display();
         }else{
         $this->display("Member/login");
        }
        }
        //投诉
        public function complaint (){
            if(session('uid')){
                $maps['admin_id'] = $_REQUEST['admin_id'];
                $info = D('admin')->where($maps)->find();
                $this->assign('where',$where);
                $this->assign('info',$info);
                $this->display();
             }else{
                $this->display("Member/login");
            }
        }
        public function do_complaint(){
            $maps['admin_id'] = $_REQUEST['id'];
            $data = D('admin')->where($maps)->find();
            $mapss['user_id'] = session('uid');
            $info = D('users')->where($mapss)->find();
            $where['uid'] = session('uid');//投诉人ID
            $where['username'] = $info['nickname'];//投诉人名称
            $where['mobile'] = $info['mobile'];//投诉人手机号
            $where['create_time'] = time();
            $where['content'] = $_REQUEST['content'];//投诉内容
            $where['store_id'] = $data['store_id'];//投诉对象所属门店ID
            $where['target_id'] = $_REQUEST['id'];//投诉对象ID
            $where['target_name'] = $data['name'];//投诉对象名称
            $where['type'] = "服务顾问";//投诉类型
            $add = D('complaint')->add($where);
            if($add){
                $this->success("投诉成功",U('Home/User/consultant_Evaluation'));
            }else{
                $this->error("投诉失败");
            }
        }

    //参观原产地
    public function visit(){
       if(session('uid')){
        $maps['cate_id'] = 2;
        $info = D('activity')->where($maps)->order("activity_id desc")->select();
        $this->assign('info',$info);
        $this->display();
         }else{
         $this->display("Member/login");
        }
    }
         //马上预约
        public function order(){
           if(session('uid')){
            $maps['activity_id'] = $_REQUEST['activity_id'];
            $info = D('activity')->where($maps)->find();
            $this->assign('info',$info);
            $this->display();
         }else{
         $this->display("Member/login");
        }
        }
                // //预约成功（重复）
                // public function imm_order(){
                //     $this->display();
                // }

     //vip会员商城
    public function vip_index(){
        if(session('uid')){
        $info = M('goods')->where('is_recommend=1')->order('last_update desc')->limit('10')->select();
        $this->assign('info',$info);
        $this->display();
         }else{
         $this->display("Member/login");
        }
       
    }
    public function ajax_vip(){
        $page = $_REQUEST['p'];
        $start = $page*10; 
        $maps['is_recommend'] = 1;
        $maps['type'] = 1;  
        $info = D('goods')->where('is_recommend=1')->order('last_update desc')->limit("$start,10")->select();
        $this->assign('info',$info);
        $this->display();
    }

    //商品详情
    public function product_Details(){
        C('TOKEN_ON',true);        
        $goodsLogic = new \Home\Logic\GoodsLogic();
        $goods_id = I("get.id");
        $goods = M('Goods')->where("goods_id = $goods_id")->find();
        if(empty($goods)){
            $this->tp404('此商品不存在或者已下架');
        }
        if($goods['brand_id']){
            $brnad = M('brand')->where("id =".$goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }
        $goods_images_list = M('GoodsImages')->where("goods_id = $goods_id")->select(); // 商品 图册        
        $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        $goods_attr_list = M('GoodsAttr')->where("goods_id = $goods_id")->select(); // 查询商品属性表                        
        $filter_spec = $goodsLogic->get_spec($goods_id);  
         
        $spec_goods_price  = M('spec_goods_price')->where("goods_id = $goods_id")->getField("key,price,store_count"); // 规格 对应 价格 库存表
        //M('Goods')->where("goods_id=$goods_id")->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数
        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计     
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $goods['sale_num'] = M('order_goods')->where("goods_id=$goods_id and is_send=1")->count();
        
        //商品促销
        if($goods['prom_type'] == 1)
        {
            $prom_goods = M('prom_goods')->where("id = {$goods['prom_id']}  AND is_close=0")->find();
            $this->assign('prom_goods',$prom_goods);// 商品促销
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);
            $flash_sale = M('flash_sale')->where("id = {$goods['prom_id']}")->find();
            $this->assign('flash_sale',$flash_sale);
        }         
        
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        $this->assign('goods_attribute',$goods_attribute);//属性值     
        $this->assign('goods_attr_list',$goods_attr_list);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $goods['discount'] = round($goods['shop_price']/$goods['market_price'],2)*10;
        $this->assign('goods',$goods);
        $this->display();
     }
    //购物车
    public function shopCar(){
        $this->display();
    }  
            //确定订单
            public function confirmation(){
                $this->display();
            }
                //选择优惠劵
                public function discount_coupon_1(){
                    $this->display();
                }
                //提交成功
                public function sub_order(){
                    $r['user_id'] = session('uid');
                    $uid = session('uid');
                    $where = D('users')->where($r)->find();
                    $total = $_REQUEST['total_fee'];
                    $info = D('user_address')->where($r)->find();
                    if($info){
                       
                    }else{
                        $this->success("请输入地址",U('Home/User/new_addi')); 
                    }
                   if (IS_POST) {
                    ///dump($_REQUEST);
                    $getKeScore=getKeScore($uid);

                    if($getKeScore>$total){
                        $maps['address_id'] = $_REQUEST['address_id'];
                        $info = D('user_address')->where($maps)->find();
                        $data['order_sn']=date('YmdHis').rand(1000,9999);
                        $data['user_id'] = session('uid');
                        $data['pay_name'] = "积分兑换";
                        $data['address'] = $info['address'];
                        $data['consignee'] = $info['consignee'];
                        $data['mobile'] = $info['mobile'];
                        $data['add_time'] = time();
                        $data['pay_time'] = time();
                        $data['type'] = 1 ;
                        $data['user_note'] = $_REQUEST['user_note'];
                        $data['send_goods_time'] = $_REQUEST['send_goods_time'];
                        $data['pay_status'] = 1;
                        $data['weixin'] = session('weixin');
                        $add = D('order')->add($data);
                            
                        $cate_id=$_POST['cart_id'];
                        foreach ($cate_id as $key => $value) {
                            
                            $cate_ids=$value;
                            $mapsss['id']=$cate_ids;

                           $cart=D("Cart")->where($mapsss)->find();
                           //dump($cart);
                           //exit;
                           $datas['order_id'] = $add;
                           $datas['goods_sn'] = $cart['goods_sn'];
                           $datas['goods_id'] = $cart['goods_id'];
                           $datas['goods_name'] = $cart['goods_name'];
                           $datas['goods_num'] = $cart['goods_num'];
                           $datas['goods_points'] = $cart['goods_points'];
                           $datas['spec_key'] = $cart['spec_key'];
                           $datas['spec_key_name'] = $cart['spec_key_name'];
                           $datas['sku'] = $cart['sku'];
                           $adds = D('order_goods')->add($datas);
                           //echo M()->getlastsql();
                           if($adds){
                                //清空购物车数据
                                D("cart")->where($mapsss)->delete();
                           }
                        }
                        $day = time();
                    //echo $total;
                        accountLog($uid,0,$total,'积分兑换减少',0,3,1); 
                         $mobile= $where['mobile'];
                         if($where['xingming']){
                            $name = $where['xingming'];
                         }else{
                            $name = $where['nickname'];
                         }
                         $content="尊敬的会员".$name."，您的晓芹海参会员卡于".date('Y-m-d H:i:s',$day)."消费".$total."积分。买海参找晓芹！晓芹热线：4006990605。";
                         $res= sendsmss($mobile,$content);

                        // $mapps['goods_points'] = $where['goods_ponts'] - $total;
                        // D('users')->where("user_id=$uid")->save($mapps);
                        
                        $this->success("操作成功！",U('Home/Member/my_order'));       
                         
                    }else{
                        $this->error("积分不够！");
                    }

                }else{
                    $this->display();
                }
                   
                }
                //地址列表
                public function site_list1(){
                    $maps['user_id'] = session('uid');
                    $info = D('user_address')->where($maps)->order("is_default desc")->select();
                    $this->assign("info",$info); 
                    $this->display();
                }
    // 新增地址
    // public function new_addi(){
    //  $this->assign("source",$_REQUEST['source']);

    //     if (IS_POST) {

    //         $data['user_id']=session('uid');
    //         if(D("user_address")->where($data)->find()==false){
    //              $data['is_default']=1;
    //         }

    //         $data['consignee']=$_POST['consignee'];
    //         $data['mobile']=$_POST['mobile'];
    //         $data['address']=$_POST['address'];      

    //         $user_address=D("user_address")->add($data);

    //         if($user_address){
    //             if($_POST['source']=='cart2'){
    //                 $this->success("添加成功！",U('Home/Cart/cart2'));       
    //             }else{
    //                 $this->success("添加成功！");
    //             }

    //         }else{
    //             $this->error("添加失败！");
    //         }

    //         exit;
    //     }
    // $this->display();
    // }
    public function new_addi(){
        $maps['address_id'] = $_REQUEST['address_id'];
        $info = D("user_address")->where($maps)->find();

        $this->assign("source",$_REQUEST['source']);
        $this->assign('info',$info);
        $this->display();
    }
    public function doUpload(){      
        /****
        *获取活动id
        ****/
        set_time_limit(0);
      
        //这里判断文件是否符合规则
        $uploadFile = $_FILES['file'];
        if(empty($uploadFile)){
            $this->ajaxReturn(array('status' => -1, 'info' => '上传失败'));
        }
        if($uploadFile['size'] == 0){
            $this->ajaxReturn(array('status' => -1, 'info' => '上传失败'));
        }
        if($uploadFile['size'] < $minSize*1024*1024){
            $this->ajaxReturn(array('status' => -1, 'info' => '文件不能小于'.$minSize.'MB'));
            //return false;
            //echo json_encode(array('success' => 0, 'error' => '文件不能小于'.$minSize.'MB'));exit;
        }
        $img_info = getimagesize($uploadFile['tmp_name']);
        if($img_info['0'] < $minWidth){
            $this->ajaxReturn(array('status' => -2, 'info' => '文件不能小于'.$minSize.'MB'));
            //echo json_encode(array('success' => 0, 'error' => '宽不能低于'.$minWidth.'px'));exit;
        }else if($img_info['1'] < $minHeight){
            $this->ajaxReturn(array('status' => -3, 'info' => '文件不能小于'.$minSize.'MB'));
            //echo json_encode(array('success' => 0, 'error' => '高不能低于'.$minHeight.'px'));exit;
        }
        $uploadList = $this->upload($_FILES);
       
        $this->ajaxReturn(array('status' => 1, 'info' => $uploadList['file']));
        /*$data['mid'] = $matchlist['id'];
        $data['uid'] = $_SESSION['uid'];
        $data['author'] = $_SESSION['name'];
        $data['status'] = 0;
        $res = M("pics")->where($data)->order('id desc')->find();

        $dat['savepath'] = $uploadList[0]['savename'];
        $dat['size']     = $uploadList[0]['size'];
        $dat['pid']      = $res['id'];
        $dat['uid']      = $_SESSION['uid'];
        $ress = D("Attach")->add($dat);
        
        //判断如果有添加数据成功更新一条
        if($ress){
            $datas['pic'] = $dat['savepath'];
            M("pics")->where(' id = '.$res['id'])->save($datas);
        }
        echo $res;*/
    }

    public function upload(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Public/upload/'; // 设置附件上传根目录
        $upload->savePath  =     'news/'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功
            return $info;    
        }
    }
    public function do_a(){
        $id = $_REQUEST['id'];
        $data['zan']  = $_REQUEST['zan'];
        $maps['admin_id'] = $id;
        $info = M('share')->where($maps)->save($data);
        $news = array('code' =>1 ,'msg'=>'2222','data'=>$info);
        echo json_encode($news,true);exit;
    }
    public function do_b(){
        $id = $_REQUEST['id'];
        $data['zan']  = $_REQUEST['zan'];
        $maps['admin_id'] = $id;
        $info = M('admin')->where($maps)->save($data);
        $maps['user_id'] = session('uid');
        $maps['type'] = 1;
        $infos = D('zan')->where($maps)-find();
        if($infos){
             D('zan')->where($maps)-delete();
        }else{
             D('zan')->add($maps);
        }
        $news = array('code' =>1 ,'msg'=>'2222','data'=>$info);
        echo json_encode($news,true);exit;
    }
    public function do_c(){
        $maps['admin_id'] = $_REQUEST['id'];
        $data['cai']  = $_REQUEST['cai'];
        $info = D('admin')->where($maps)->save($data);
         $maps['user_id'] = session('uid');
        $maps['type'] = 0;
        $infos = D('zan')->where($maps)-find();
        if($infos){
             D('zan')->where($maps)-delete();
        }else{
             D('zan')->add($maps);
        }
        $news = array('code' =>1 ,'msg'=>'2222','data'=>$info);
        // echo json_encode($news,true);exit;
    }
    public function do_address(){
        $maps['address_id'] = $_REQUEST['address_id'];
        $info = D('user_address')->where($maps)->delete();
        
    }
    public function do_addi(){
        $data['user_id'] = session('uid');
        $info = D('user_address')->where($data)->count();
        if($info==0){
            $data['is_default'] = 1;
        }
        $data['mobile'] = $_REQUEST['mobile'];
        $data['user_id'] = session('uid');
        $data['consignee'] = $_REQUEST['name'];
        $data['address'] = $_REQUEST['content'];
        $address = $_REQUEST['address_id'];
        if(empty($address)){
           $add = D('user_address')->add($data);
           if($add){

              if($_POST['source']=='cart2'){
                    $this->success("添加成功！",U('Home/Cart/Cart2')); 
             }else{
                 $this->success("添加成功！",U('Home/User/site_list1'));      
             }     
           }else{
            $this->error("添加失败！");
           } 
        }else{
            $maps['address_id'] = $_REQUEST['address_id'];

            $add = D('user_address')->where($maps)->save($data);
            if($add){
             $this->success("修改成功！",U('Home/User/site_list1'));      
           }else{
            $this->error("修改失败！");
           } 
        }
    }
    public function less_address(){
        $address_id = $_REQUEST['address_id'];
        $maps['user_id'] = session('uid');
        $info = D('user_address')->where($maps)->select();
        foreach ($info as $key => $value) {
            $r['address_id'] = $value['address_id'];
           if($value['address_id']==$address_id){            
            $value['is_default'] = 1;         
           }else{
            $value['is_default'] = 0;
           }
            $add = D('user_address')->where($r)->save($value);
        }
    }
}