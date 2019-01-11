<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-25
 */
namespace Apis\Controller;
use Think\Controller;

class LoginApiController extends Controller {

    //登陆
    public function login(){
        $username=$_REQUEST['username'];
        $password=$_REQUEST['pwd'];
        $User=D("Admin");
        $map['user_name|mobile'] = $username;
        // 把查询条件传入查询方法
        $login=$User->where($map)->find();



    /**
      
        if($login['role_id']<3){
               $maps1['store_id']=$login['store_id'];

            $store=D("store")->where($maps1)->select();

        }elseif($login['role_id']==4){

             $maps1['store_id']=$login['store_id'];

             //$maps1['store_id']=$login['store_id'];

            $store=D("store")->where($maps1)->select();

        }elseif($login['role_id']==5){

           $maps1['store_id']=$login['store_id'];

            $store=D("store")->where($maps1)->select();
        }
      
      */        
           
        if($login){

             if($login['role_id']==1){
                 $store=D("store")->where('is_forbid=0')->select();
                 $zmap['role_id']=2;
                 $zongjianlist=D("Admin")->where($zmap)->select();

                 if($zongjianlist==false){
                    $zongjianlist=null;
                 }

            }elseif($login['role_id']==2){

                 $zmap['role_id']=4;
                 $zongjianlist=D("Admin")->where($zmap)->select();

                 if($zongjianlist==false){
                    $zongjianlist=null;
                 }

                 if(!empty($login['store_id'])){
                    $maps1['store_id']=array('in',$login['store_id']);
                    $store=D("store")->where($maps1)->where('is_forbid=0')->select();  
                 }

              

                       
            }elseif($login['role_id']==4){


               if(!empty($login['store_id'])){
                    $maps1['store_id']=array('in',$login['store_id']);
                    $store=D("store")->where($maps1)->where('is_forbid=0')->select();  
                 }

                $guwenlist =D("Admin")->where($maps1)->select(); 

            }elseif($login['role_id']==5){

                if(!empty($login['store_id'])){
                    $maps1['store_id']=array('in',$login['store_id']);
                    $store=D("store")->where($maps1)->where('is_forbid=0')->select();  
                 }
            }
            $pwds= encrypts($password);

            $login['store']=$store;
            $login['store_id']=0;
            $login['birthday']=date('Y-m-d',$login['birthday']);

            if($login['password']==$pwds){
                $uniqueKey=$login['admin_id'];
                $uniqueKey.="_";
                $uniqueKey.=time();
                $uniqueKey.=rand(10000,99999);

                $login['uniquekey']=$uniqueKey;

                $datas['uniquekey']=$uniqueKey;
                $datas['uid']=$login['admin_id'];
                $datas['create_time']=time();

                D("login_log")->add($datas);


                if($zongjianlist==false){
                    $login['zongjianlist']=$zongjianlist;
                }

                if($guwenlist==false){
                    $login['guwenlist']=$guwenlist;
                }


                $news = array('code' =>1 ,'msg'=>'登录成功','data'=>$login);
                echo json_encode($news,true);exit;
            }else{
                 $news = array('code' =>0 ,'msg'=>'密码错误！','data'=>null);
                echo json_encode($news,true);exit;

            }
            
        }else{
                $news = array('code' =>0 ,'msg'=>'用户不存在！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    
     //判断是非存在
    public function checkType(){

        $userid=$_REQUEST['id'];
        $maps['user_id']=$userid;
        $user=D("users")->where($maps)->find();
        if($user){
            
             $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'此号码不是会员','data'=>null);
                echo json_encode($news,true);exit;
        }
    }

    public function getBanben(){

        $inc_type =  I('get.inc_type','basic');
       
        $config = tpCache($inc_type);


        $app_banben=$config["app_banben"];
        $app_url=$config["app_url"];

        $list['code']=$app_banben;
        $list['url']=$app_url;

          $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;

    }

    public function getPay(){
        $uid="18831";
        $list=getKeScore($uid);
        echo $list;
    }
    
    public function sendBrith(){
        $uid="9";
        $mapss['user_id']=$uid;
        $user=D("Users")->where($mapss)->find();
        $list=D("brith_reminder")->find();
        $maps['id']=$list['coupon_id'];
        $coupon=D("coupon")->where($maps)->find();

        dump($coupon);

        $add['cid'] =$list['coupon_id'];
        $add['uid'] =$uid;
        $add['type'] = 5;
        $add['send_time'] = time();

        do{
            $code = get_rand_str(6,0,1);//获取随机8位字符串
            $check_exist = M('coupon_list')->where(array('code'=>$code))->find();
        }while($check_exist);

        $add['code'] = $code;
        M('coupon_list')->add($add);

       //M('coupon')->where("id=$cid")->setInc('send_num',$num);
        $money=$coupon['money'];
        $use_end_time=date('Y-m-d',$coupon['use_end_time']);
        $content="会员您好，提前祝您生日快乐！您已获得价值".$money."元的电子券1张，兑换码".$code."，过期时间".$use_end_time."，请勿错过特惠良机！详询4006990605";

        $mobile=$user['mobile'];
         echo $mobile;
         $res=sendsmss($mobile,$content);
         dump($res);


    }

    //修改密码
    public function updatePw(){
        $mobile=$_REQUEST['mobile'];
        $pwd=$_REQUEST['pwd'];
        $pwd1=$_REQUEST['pwd1'];

        $vmaps['mobile']=$mobile;

        $admin=D("Admin")->where($vmaps)->find();
        if($admin==false){
            $news = array('code' =>0 ,'msg'=>'手机不存在！','data'=>null);
                echo json_encode($news,true);exit;
      
        }

        if($pwd1!=$pwd){
             $news = array('code' =>0,'msg'=>'两次输入的密码不一样','data'=>null);
                echo json_encode($news,true);exit;
        }
        $where['mobile']=$mobile;
        $data['password']= encrypts($pwd);
        $admin=D("Admin")->where($where)->save($data);

        if($admin){
             $news = array('code' =>1,'msg'=>'找回密码成功！','data'=>null);
                        echo json_encode($news,true);exit;
        }else{

            $news = array('code' =>0,'msg'=>'找回密码失败！','data'=>null);
                        echo json_encode($news,true);exit;
            

        }
    }


        //发送验证码接口
    public  function sendsmslog(){
       
        $rand=rand('1000','9999');
        $mobile=$_REQUEST['mobile'];

        $vmaps['mobile']=$mobile;

        $admin=D("Admin")->where($vmaps)->find();
        if($admin==false){
            $news = array('code' =>0 ,'msg'=>'手机不存在！','data'=>null);
                echo json_encode($news,true);exit;
      
        }

        $date = date("Y-m-d");
        $maps['mobile'] = $mobile;
        $maps['create_time'] = $date;
        $maps['type'] = 3;
        $count = D('send_log')->where($maps)->count();
        if($count>5){
              $news = array('code' =>0 ,'msg'=>'一天最多发5条消息！','data'=>null);
                echo json_encode($news,true);exit;
      
        }else{
               $res= sendsmss($mobile,$rand);
                if($res==true){
                    $where['type'] = 3;
                    $where['rand'] = $rand;
                    $where['mobile'] = $mobile;
                    $where['create_time'] = date("Y-m-d");
                    $r = D('send_log')->add($where); 
                     $news = array('code' =>1,'msg'=>'发送成功！','data'=>null);
                        echo json_encode($news,true);exit;
                }else{
                     $news = array('code' =>0 ,'msg'=>'发送失败！','data'=>null);
                        echo json_encode($news,true);exit;
                }
        }

    }
    //验证码验证操作
    public function check_code(){
        $mobile=$_REQUEST['mobile'];
        $code=$_REQUEST['code'];
        $date = date("Y-m-d");
        $maps['mobile']=$mobile;
        //$maps['code']=$code;
        $maps['create_time']=$date;
        $list=D("send_log")->where()->order('id desc')->find();

        if($list){
            if($list['rand']==$code){
                $news = array('code' =>1,'msg'=>'验证码正确！','data'=>null);
                        echo json_encode($news,true);exit;
            }else{
                $news = array('code' =>0,'msg'=>'验证码不正确！','data'=>null);
                        echo json_encode($news,true);exit;
            }

        }else{
            $news = array('code' =>0 ,'msg'=>'验证码不存在！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
}