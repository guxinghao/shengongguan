<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-22
 */namespace Home\Controller;
use Think\Page;
use Think\Verify;
class MemberController extends BaseController {
//注册验证   
    public function getCode(){       
        $rand=rand('1000','9999');
        $mobile=$_REQUEST['mobile'];
        $date = date("Y-m-d");
        $maps['mobile'] = $mobile;
        $maps['create_time'] = $date;
        $maps['type'] = 0;
        $count = D('send_log')->where($maps)->count();

        if($count>5){
            echo '3';
        }else{
                $content="验证码：";
                $content.=$rand;
               $res= sendsmss($mobile,$content);
                if($res==true){
                    $where['type'] = 0;
                    $where['rand'] = $rand;
                    $where['mobile'] = $mobile;
                    $where['create_time'] = date("Y-m-d");
                    $r = D('send_log')->add($where); 
                echo '1';
         }else{
                echo '0';
       }
    }
        }
//登录验证
    public function getCode1(){
        $rand=rand('1000','9999');
        $mobile=$_REQUEST['mobile'];
        $date = date("Y-m-d");
        $maps['mobile'] = $mobile;
        $maps['create_time'] = $date;
        $maps['type'] = 0;
        $map['mobile'] = $mobile;
        $count = D('send_log')->where($maps)->count();
        $info = D('users')->where($maps)->find();
        if($count>5){
            echo '3';
        }else{
            if($info){
                $content="验证码：";
                $content.=$rand;
               $res= sendsmss($mobile,$content);
                if($res==true){
                    // $where['type'] = 1;
                    $where['rand'] = $rand;
                    $where['mobile'] = $mobile;
                    $where['create_time'] = date("Y-m-d");
                    $where['type'] = 0;
                    $r = D('send_log')->add($where); 
                     echo '1';
            }else{
                $this->error('不存在该用户');
            }
               
         }else{
             echo '0';
       }
    }
        }
     //会员中心 
    public function index(){
        if(session('uid')){
            $uid=session('uid');
            $maps['user_id'] = $uid;
            $info = M('coupon_list')->where($maps)->count();
            $r = M('users')->where($maps)->find();
            $head = session('headimgurl')?session('headimgurl'):$r['head_pic'];
            $this->assign('r',$r);
            $this->assign('info',$info);
            $this->assign('head',$head);
            $this->display();
        }else{
            $this->display("Member/login");
        }
    }

    //我的积分
    public function integral(){
         if(session('uid')){
        $uid=session('uid');
        $maps['user_id'] = $uid;
        $r = M('users')->where($maps)->find();
        $info = M('account_log')->where($maps)->order("change_time desc")->limit("10")->select();
        foreach ($info as $key => $value) {
            if($value['pay_points'] > -1){
                $data[$key]['pay_points'] = $value['pay_points'];
                $data[$key]['desc'] =  $value['desc'];
                $data[$key]['change_time'] = $value['change_time'];
                $data[$key]['user_money'] = $value['user_money'];
                $data[$key]['type'] = $value['type'];
            }
        }
        $this->assign('r',$r);
        $this->assign('data',$data);
        $this->display();
         }else{
        $this->display("Member/login");
        }
    }
        //我的优惠券
        public function discount_coupon(){
            $info = M('coupon_list')->order("use_time desc")->limit("20")->select();
            $this->assign('info',$info);
            $this->display();
        }
          public function ajax_coupon(){
            $page = $_REQUEST['p'];   
            $start = $page*10;
            $info = M('coupon')->order("use_end_time desc")->limit("$start,20")->select();
            $this->assign('info',$info);
            $this->display();
        }
        //个人信息
        public function my_information(){
            $maps['user_id'] = session('uid');
            $info  = D('users')->where($maps)->find();
            $maps['is_default'] = 1;
            $address = D("user_address")->where($maps)->find();
            $head_pic = session('headimgurl')?session('headimgurl'):$info['head_pic'];
            $this->assign('head_pic',$head_pic);
            $this->assign('info',$info);
            $this->assign('address',$address);
            $this->display();
        }
            
        //我的海参
        public function my_trepang(){
            $uid=session('uid');
            $maps['uid'] = $uid;
            $info = M('send_list')->where($maps)->order("id desc")->limit("20")->select();
            $r = M('deposit_list')->where($maps)->order("id desc")->limit("20")->select();
            $this->assign('info',$info);
            $this->assign('r',$r);
            $this->display();
        }
         public function ajax_send(){
            $uid=session('uid');
            $page = $_REQUEST['p'];   
            $start = $page*20;
            $maps['user_id'] = $uid;
            $info = M('send_list')->where($maps)->order("id desc")->limit("$start,20")->select();
            $this->assign('info',$info);
            $this->display();
        }
        public function ajax_deposit(){
            $uid=session('uid');
            $page = $_REQUEST['p'];   
            $start = $page*20;
            $maps['user_id'] = $uid;
            $r = M('send_list')->where($maps)->order("id desc")->limit("$start,20")->select();
            $this->assign('r',$r);
            $this->display();
        }

            //我要泡发
            public function my_soak(){
                $id = $_REQUEST['id'];
                 $maps['id'] = $id;
                $this->display();
            }
            //查看发泡进度
            public function soak_schedule(){
                $id = $_REQUEST['id'];
                $maps['id'] = $id;
                $info = M("send_list")->where($maps)->find();
                $this->assign('info',$info);
                $this->display();
            }
            //直接送货
            public function deliver_goods(){
                $uid = session('uid');
                $r['user_id'] = $uid;
                $r['is_default'] = 1;
                $maps['user'] = $uid;
                $info = D('users')->where($maps)->find();
                $where =D('user_address')->where($r)->find();
                $where['a'] = $_REQUEST['id'];
                $this->assign('info',$info);                
                $this->assign('where',$where);
                $this->display();
            }
            //送货申请成功
            public function deliver_goods1(){
                $uid = session('uid');                  
                $info = D('deposit_list')->where($maps)->find();
                $r['user_id'] = $uid;
                $r['sn'] = $info['sn'];
                $r['type'] = $_REQUEST['type'];
                $r['create_time'] = time();
                $add = D('send_address')->add($r);
                $this->display();
            }
            //我的订单
            public function my_order(){
                $uid=session('uid');
                $type = $_REQUEST['type'];
                $maps['user_id'] = $uid;
                if($type==1){
                    $maps['pay_status'] = 0;
                    
                }elseif($type==2){
                    $maps['pay_status'] = 1;
                    $maps['shipping_status'] = 0;
                }elseif($type==3){
                    $maps['pay_status'] = 1;
                    $maps['shipping_status'] = 1;
                    $maps['confirm_time'] = 0;
                }elseif($type==4){
                    $maps['pay_status'] = 1;
                    $maps['shipping_status'] = 1;
                    $maps['confirm_time'] = array("gt",0);
                }
                $info = M('order')->where($maps)->order("order_id desc")->limit("20")->select();
                foreach ($info as $key => $value) {
                    if($value['pay_status'] == 0){
                        $info[$key]['a'] = "待付款";
                        $info[$key]['b'] = "去支付";
                        // $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                    }elseif ($value['pay_status'] == 1&& $value['shipping_status'] == 0) {
                        $info[$key]['a'] = "待发货";
                        $info[$key]['b'] = "等待发货";
                        // $info[$key]['c'] ="comment/order_id/".$value['order_id'];
                    }elseif ($value['pay_status'] == 1&& $value['shipping_status'] == 1&& $value['confirm_time'] == 0) {
                        $info[$key]['a'] = "待收货";
                        $info[$key]['b'] = "确认收货";
                        // $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                    }else{
                         $info[$key]['a'] = "待评论";
                         $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                         $info[$key]['b'] = "去评论";
                    }
                    $mapss['order_id'] = $value['order_id'];
                    $goods=D("order_goods")->where($mapss)->select();
                    $info[$key]['goods']=$goods;
                }
                $this->assign("info",$info);
                $this->display();
            }
            public function ajax_order(){
                $page = $_REQUEST['p'];   
                $start = $page*10;

                $uid=session('uid');
                $type = $_REQUEST['type'];
                $maps['user_id'] = $uid;
                if($type==1){
                    $maps['pay_status'] = 0;
                    
                }elseif($type==2){
                    $maps['pay_status'] = 1;
                    $maps['shipping_status'] = 0;
                }elseif($type==3){
                    $maps['pay_status'] = 1;
                    $maps['shipping_status'] = 1;
                    $maps['confirm_time'] = 0;
                }elseif($type==4){
                    $maps['pay_status'] = 1;
                    $maps['shipping_status'] = 1;
                    $maps['confirm_time'] = array("gt",0);
                }
                $info = M('order')->where($maps)->order("order_id desc")->limit("$start,20")->select();
                foreach ($info as $key => $value) {
                    if($value['pay_status'] == 0){
                        $info[$key]['a'] = "待付款";
                        $info[$key]['b'] = "去支付";
                        $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                    }elseif ($value['pay_status'] == 1&& $value['shipping_status'] == 0) {
                        $info[$key]['a'] = "待发货";
                        $info[$key]['b'] = "等待发货";
                        $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                    }elseif ($value['pay_status'] == 1&& $value['shipping_status'] == 1&& $value['confirm_time'] == 0) {
                        $info[$key]['a'] = "待收货";
                        $info[$key]['b'] = "确认收货";
                       $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                    }else{
                         $info[$key]['a'] = "待评论";
                         $info[$key]['c'] = "{:U('Member/comment')}";
                        $info[$key]['c'] = "comment/order_id/".$value['order_id'];
                    }
                }
                $this->assign("info",$info);
                $this->display();
            }
            //评论
            public function comment(){
                $maps['order_id'] = $_REQUEST['order_id'];
                $info = D('order_goods')->where($maps)->find();
                $this->assign('info',$info);
                $this->display();
            }
            public function do_comment(){
                $maps['goods_id'] = $_REQUEST['goods_id'];

                $info = D('goods')->where($maps)->find();
                $data['goods_id'] = $info['goods_id'];
                $data['content'] = $_REQUEST['content'];
                $data['add_time'] = time();
                $data['mode'] = "商品";
                $r['user_id'] = session('uid');
                $infos = D('users')->where($r)->find();
                $data['username'] = $infos['nickname'];
                $add = D('comment')->add($data);
                if($add){
                    $this->success("提交评论成功",U('Home/Member/index'));
                }else{
                    $this->error("提交失败");
                }

            }

            //我的参友
            public function friend_ticket(){
                $maps['second_leader'] = session('uid');
                $info = D('users')->where($maps)->order("user_id desc")->limit("10")->select();
                $count = D('users')->where($maps)->count();
                $r['user_id'] = session('uid');
                $r['type'] = 1;
                $where = D("account_log")->where($r)->sum("pay_points");
                if($where==null){
                    $where ="0";
                }
                foreach ($info as $key => $value) {
                    if($value['level']==1){
                        $info[$key]['le'] = "普通会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/jb.png";
                    }elseif($value['level']==2){
                        $info[$key]['le'] = "银卡会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/jhy.png";
                    }elseif($value['level']==3){
                        $info[$key]['le'] = "金卡会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/yhy.png";
                    }elseif($value['level']==4){
                        $info[$key]['le'] = "白金会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/jhy.png";
                    }elseif($value['level']==5){
                        $info[$key]['le'] = "钻石会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/zs.png";
                    }
                }
                $this->assign('where',$where);
                $this->assign('count',$count);
                $this->assign('info',$info);
                $this->display();
            }
             public function ajax_ticket(){
                $page = $_REQUEST['p'];   
                $start = $page*10;
                $maps['second_leader'] = session('uid');
                $info = D('users')->where($maps)->order("user_id desc")->limit("start,10")->select();

                foreach ($info as $key => $value) {
                    if($value['level']==1){
                        $info[$key]['le'] = "普通会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/jb.png";
                    }elseif($value['level']==2){
                        $info[$key]['le'] = "银卡会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/jhy.png";
                    }elseif($value['level']==3){
                        $info[$key]['le'] = "金卡会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/yhy.png";
                    }elseif($value['level']==4){
                        $info[$key]['le'] = "白金会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/jhy.png";
                    }elseif($value['level']==5){
                        $info[$key]['le'] = "钻石会员";
                        $info[$key]['src'] = "/Template/wx/default/Static/img/zs.png";
                    }
                }
                $this->assign('where',$where);
                $this->assign('count',$count);
                $this->assign('info',$info);
                $this->display();
            }
            //意见反馈
            public function feedback(){
                $this->display();
            }
            public function dofeedback(){
                $uid=session('uid');
                $maps['user_id'] = $uid;
                $maps['content'] = $_REQUEST['content'];
                $maps['type'] = $_REQUEST['yj'];
                $maps['create_time'] = time();
                 $add = D('my_commit')->add($maps);
                if($add){
                    $this->success("提交评论成功",U('Home/Member/index'));
                }else{
                    $this->error("提交评论失败");
                }
               
            }
            //了解参公馆
            public function know(){
                $this->display();
            }
            //我的消息
            public function my_News(){
                $maps['cat_id'] = 4;
                $info = D('app_article')->where($maps)->order("article_id desc")->limit("10")->select();
                 $this->assign('info',$info);
                $this->display();
            }
            //我的收藏
            public function collection(){
                $uid=session('uid');
                $maps['user_id'] = $uid;
                $info = D('goods_collect')->where($maps)->select();
                foreach ($info as $key => $value) {
                    $mapss['goods_id']=$value['goods_id'] ;
                    $where = D('goods')->where($mapss)->find();
                    $r[$key] = $where;
                }
                $this->assign('r',$r);
                $this->display();
            }
        //会员登录
            public function login(){
                $this->display();
            }
            public function login1(){
                if(session('uid')>0){
                    $this->error("您已登录",U('Home/Member/index'));
                }
                $mobile = I('post.mobile');
                $rand = I('post.rand');
                $maps['mobile']= $mobile;
                $info = D('users')->where($maps)->find();
                $infos = D('send_log')->where($maps)->order("id desc")->find();
                if($info){
                    if($infos['rand'] == $rand){   
                        session('uid',$info['user_id']);
                        $type=session('type');
                        $openid=session('openid');
                        if(!empty($openid)){
                           if($type==1){
                                 $datas['openid1']= session('openid');
                            }else{
                                 $datas['openid2']= session('openid');
                            }
                            //查询有相同openid的数据有则修改数据
                            $re = D("Users")->where($datas)->find();
                            if ($re) {
                                $res['user_id'] = $re['user_id'];
                                $res['openid1'] = '';
                                $res['openid2'] = '';
                                D("Users")->save($res);
                            }
                            
                            //修改头像
                            $datas['head_pic']= session('head_pic');
                            $datas['update_time']= time();
                            $mapss['user_id']=$info['user_id'];
                            D("Users")->where($mapss)->save($datas);
                            $this->success("尊敬的参公馆会员，您的信息已匹配成功！",U('Home/Member/index'));
                        }else{
                             $this->success("欢迎回来！今天的海参，您吃了么？",U('Home/Member/index'));
                         }                       
                    }else{
                         $this->error("验证码错误");
                    }
                }else{  
                         if($infos['rand'] ==  $rand){
                            $type=session('type');
                            $openid=session('openid');
                            if(!empty($openid)){
                                if($type==1){
                                     $where['openid1']= session('openid');
                                }else{
                                     $where['openid2']= session('openid');
                                }
                            }
                            $inc_type =  I('get.inc_type','basic');
                            $config = tpCache($inc_type);
                            $shop_no=$config["shop_no"];
                            $store['shop_no'] =  $shop_no;
                            $mapps = D('store')->where($store)->find();
                            $where['store_id'] = $mapps['store_id'];
                            $where['mobile'] = $mobile;
                            $where['reg_time'] = time();
                            $r = D('users')->add($where);
                            getTypes($r,0,1);
                            accountLog($r,0,30,'绑定公众号成功+30积分',0,1); 
                            session('uid',$r);
                            $content="您的电子会员卡已激活，并送30积分，消费时请报手机号码，一边吃海参，一边赚积分！详询4006990605（晓芹海参）";
                            $res= sendsmss($mobile,$content);
                                $this->success("恭喜阁下，成为参公馆新晋会员！",U('Home/Member/index'));
                    }else{                       
                         $this->error("验证码错误");
                        }                 
                }
            }
            //手机注册1
                public function register(){
                    $this->display();
                }
                //修改密码
                public function reset_Pwd(){
                    $mobile = $_POST['mobile'];
                    $rand = $_POST['rand'];
                    $maps['mobile'] = $mobile;
                    $info = D('send_log')->where($maps)->order('id desc')->find();
                    if($info['rand'] ==  $rand){
                            $mobile = $_POST['mobile'];
                             $this->assign('mobile',$mobile);
                             $this->display();
                    }else{
                        $this->error("验证码错误");
                    }                    
                }
                //修改密码
                public function sign_Up1(){
                    $mobile = $_POST['mobile'];
                    $password = $_POST['password'];
                    $password1 = $_POST['password_a'];
                    $maps['mobile'] = $mobile;
                    $info = D('users')->where($maps)->find();
                    if($info){
                        if($password ==$password1){
                        $where['mobile'] = $mobile;
                        $data['password'] = md5($password);
                        $r = D('users')->where($where)->save($data);
                         $this->success("修改密码成功",U('Home/Member/login'));                        
                    }else{
                        // echo $password;
                       $this->error("密码不相同");
                        }
                    }else{
                        $this->error("号码不存在");
                    }                    
                }
            //忘记密码
                public function find_Pwd(){
                    $this->display();
                }
                 //注册密码
                public function register2(){
                    $mobile = $_POST['mobile'];
                    $rand = $_POST['rand'];
                    $maps['mobile'] = $mobile;
                    $r = D('users')->where($maps)->find();
                    $maps['type'] = 0;
                    $info = D('send_log')->where($maps)->order('id desc')->find();
                    if(empty($r)){
                         if($info['rand'] ==  $rand){
                            $type=session('type');
                            $openid=session('openid');
                            if(!empty($openid)){
                                if($type==1){
                                     $where['openid1']= session('openid');
                                }else{
                                     $where['openid2']= session('openid');
                                }
                            }
                            $where['mobile'] = $mobile;
                            $r = D('users')->add($where);
                            accountLog($r,0,30,'注册成功+30积分',2); 
                            session('uid',$r);
                            $content="您的电子会员卡已激活，并送30积分，消费时请报手机号码，一边吃海参，一边赚积分！详询4006990605（晓芹海参）";
                            getType($r,0,0);
                            $res= sendsmss($mobile,$content);
                                $this->success("注册成功",U('Home/Member/index'));
                    }else{                       
                         $this->error("验证码错误");
                        } 
                    }else{
                        
                        session('uid',$r['user_id']);
                        $type=session('type');
                        $openid=session('openid');
                        if(!empty($openid)){
                            if($type==1){
                                 $datas['openid1']= session('openid');
                            }else{
                                 $datas['openid2']= session('openid');
                            }
                            $mapss['user_id']=$r['user_id'];
                            D("Users")->where($mapss)->save($datas);
                            $this->success("绑定公众号成功",U('Home/Member/index'));

                    }
                   
                }
            }
                 public function sign_Up(){
                    $mobile = $_REQUEST['mobile'];
                    $password = $_REQUEST['password'];
                    $password1 = $_REQUEST['password_a'];
                    $maps['mobile'] = $mobile;
                    $info = D('users')->where($maps)->find();
                    if(empty($info)){
                        if($password ==$password1){

                        $type=session('type');
                        $openid=session('openid');

                            if(!empty($openid)){
                                if($type==1){
                                     $where['openid1']= session('openid');
                                }else{
                                     $where['openid2']= session('openid');
                                }
                            }
                            $where['mobile'] = $mobile;
                            $where['password'] = md5($password);
                            //$where['pay_points'] = 30;
                            $r = D('users')->add($where);
                            accountLog($r,0,30,'注册成功+30积分',2); 
                            session('uid',$r);

                            $content="您的电子会员卡已激活，并送30积分，消费时请报手机号码，一边吃海参，一边赚积分！详询4006990605（晓芹海参）";
                            getType($r,0,0);
                            $res= sendsmss($mobile,$content);
                                $this->success("注册成功",U('Home/Member/index'));
                            }else{
                                
                                $this->error("密码不相同");
                            }
                    }else{
                        session('uid',$info['user_id']);
                        $type=session('type');
                        $openid=session('openid');
                        if(!empty($openid)){
                            if($type==1){
                                 $datas['openid1']= session('openid');
                            }else{
                                 $datas['openid2']= session('openid');
                            }
                            $mapss['user_id']=$info['user_id'];
                            D("Users")->where($mapss)->save($datas);
                            $this->success("绑定公众号成功",U('Home/Member/index'));
                    }
                 }
              
  }
    
 // 二维码
    public function qr_code(){                
        $uid=session('uid');
        if(empty($uid)){
            $uid=1;
        }
        $url = C('http_urls');        
        require_once 'ThinkPHP/Library/Vendor/phpqrcode/phpqrcode.php';
        //import('Vendor.phpqrcode.phpqrcode');
        error_reporting(E_ERROR);            
        $data = urldecode("".$url."/index.php/Apis/LoginApi/checkType/type/csg/id/".$uid."");
        // 纠错级别：L、M、Q、H
        $level = 'L';
        // 点的大小：1到10,用于手机端4就可以了
        $size = 10;
        // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
        //$path=$url ;
        $path.= "./Public/qrcode/";
        // 生成的文件名
        $fileName = $path.$uid.'.png';
        // echo $fileName;
        $pic=\QRcode::png($data, $fileName, $level, $size);
        //dump($pic);
        $paths=$url ;
        $paths.='/Public/qrcode/';
        $paths.=$uid;
        $paths.=".png";

        $this->assign('pic',$paths);
        $this->assign('url',$url);
        $this->display('code');      
    }

    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';
        
        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);        
    }
    
    // 促销活动页面
    public function promoteList()
    {                          
        $Model = new \Think\Model();
        $goodsList = $Model->query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id   where ".time()." > start_time  and ".time()." < end_time");                        
        $brandList = M('brand')->getField("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        $this->display();
    }
    
    function truncate_tables (){
        $model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $tables = $model->query("show tables");
        $table = array('tp_admin','tp_config','tp_region','tp_system_module','tp_admin_role','tp_system_menu');
        foreach($tables as $key => $val)
        {                                    
            if(!in_array($val['tables_in_TPshop'], $table))                             
                echo "truncate table ".$val['tables_in_TPshop'].' ; ';
                echo "<br/>";         
        }                
    }
    public function do_information(){
         $data['name'] = $_REQUEST['xingming'];
         $data['nickname'] = $_REQUEST['name'];
         $data['birthdays'] = $_REQUEST['birthday'];
         $data['sex'] = $_REQUEST['sex'];
         $datas['address'] = $_REQUEST['address'];
         $maps['user_id'] = session('uid');
         $add = D('users')->where($maps)->save($data);
         $maps['is_default'] = 1;
         $adds = D('user_address')->where($maps)->save($datas);
          $this->success("修改成功",U('Home/Member/index'));
    }

    public function loginout(){
        session('uid',0);
        $this->display("Member/login");
    }
}