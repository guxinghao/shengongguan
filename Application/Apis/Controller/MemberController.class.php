<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-25
 */
namespace Apis\Controller;
use Think\Controller;

class MemberController extends BaseController {

    public function test(){
       
    }
    //获取服务顾问用户列表
    public function userlist(){
        $uid=$_REQUEST['uid'];

        $store_id=$_REQUEST['store_id'];

        ///$store_id=implode(',', $store_id);
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        $r['admin_id'] = $uid;


        if(!empty($_REQUEST['nickname'])){
            $mpas['nickname|mobile']=array('like',"%".$_REQUEST['nickname']."%");
        }
        
        $pages="10";
        $start=($p-1)*$pages;

        if($uid==0){
            $smaps['store_id']=array('in',$store_id[0]);

            $admin=D("Admin")->where($smaps)->select();

            $adminid=formatArray($admin,'admin_id');

            $mpas['first_leader']=array('in',$adminid);
            $mpas['store_id']=array('in',$store_id[0]);

        }else{
             $mpas['first_leader']=$uid;
        }

        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));

        $start_time=strtotime($BeginDate);
        //echo $start_time;
       
        $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
        $end_time=strtotime($endDate)+24*3600*24-1;



        //$mpas['add_time']=array(array('egt',$start_time),array('elt',$end_time));   
        $mpas['end_time']=array(array('gt',$start_time),array('lt',$end_time));
       


          $count=D("users")->where($mpas)->count();

       
        $user=D("users")->where($mpas)->order('user_id desc')->limit($start.','.$pages)->select();

        //echo M()->getlastsql();

        if($user==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       

        foreach ($user as $key => $value) {
            $user[$key]['dengji']=$this->getCount($value['level']);
            $user[$key]['flag']=getUserFen($value['user_id']);

            $maps1['level_id']=$value['level'];

            $level=D("user_level")->where($maps1)->find();
            if($level){
                $url=C("http_urls");
               
                $url.=$level['vlogo'];
                $user[$key]['vlogo']=$url;

            }else{
                 $user[$key]['vlogo']=null;
            }
            
            $rs['store_id'] = $value['store_id'];
            $infos = M('store')->where($rs)->where('is_forbid=0')->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];
        }
         

        if($user){
                $data['count']=$count;
                $data['list']=$user;
               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    } 
    //查看等级
    public function getCount($score){
        
        $maps['level_id']=$score;
        $user_level=D("user_level")->where($maps)->find();

        return $user_level['level_name'];
    }
    //查看用户信息
    public function getUser(){
        $userid=$_REQUEST['user_id'];
        // $userid = 18724;
        $maps['user_id']=$userid;
        $user=D("users")->where($maps)->find();
        // 如果 注册时间为空时  返回空
        if (!$user['reg_time']) {
            $user['reg_time']='';   
        }else{
        // 如果 注册时间不为空时  返回
            $user['reg_time']=date('Y-m-d H:i:s',$user['reg_time']);
        }

        //最近沟通时间
        $vmmp['uid'] = $userid;
        $communication=D("communication")->where($vmmp)->order('create_time desc')->select();
        if($communication){
            $user['contact_time']=$communication[0]['ctime'];
        }else{  
            $user['contact_time']='无';
        }


        if($user['birthday']==0){
            $user['age']="未填写";
        }else{
            $birthday=date('Y-m-d',$user['birthday']);
            
            $age= getAge($birthday);

            $user['age']=$this->getAgelist($age);

        }

        $birthdays=$user['birthdays'];

        if(!empty($birthdays)){
            //$birthdays=substr($birthdays, -4);
            $user['birthdays']=$birthdays;
        }

        $gongling=date('Y-m-d',$user['add_time']);
        $user['gongling']=getAge($$gongling);

       
        $vmaps['store_id']=$user['store_id'];

        $store=M("store")->where($vmaps)->where('is_forbid=0')->find();
        $user['store']=$store['store_name'];
        $user['dianzhang']=$store['shopkeeper'];
        $user['deji']=$this->getCount($user['level']);

        $user['guwen_name']=getAdminName($user['first_leader']);
        $user['kaifa_name']=getAdminName($user['add_uid']);

        // 查找出现次数最多的产品
        $mmp['user_id']=$userid;
        $mmp['pay_status']=1;
        $mmp['order_status']=array('not in','3,5');
        $mmp['total_amount']=array('gt',0);
        //用户id查询订单
        $model = D('order')->field('order_id')->where($mmp)->order('order_id desc')->limit(1)->select();
        $orderID = $model[0]['order_id'];
        if ($orderID) {
            $or['order_id'] = $orderID;
            $or['goods_price'] = array('gt',0);
            $goodsName = M('order_goods')->where($or)->field('rec_id,order_id,goods_name')->select();
            $goodsNameArr = array();
            foreach ($goodsName as $key1 => $value) {
                $goodsNameArr[] = $value['goods_name'];
            }
            $goodsNameStr = implode(',', $goodsNameArr);
            $user['changgouchanpin_name']=$goodsNameStr;//常购产品 
        }else{
            $user['changgouchanpin_name']='无';//常购产品 
        }

        $user['flag']=getUserFen($user['user_id']);

        if ($user['openid1'] || $user['openid2']) {
            $user['focus_gzh_flag']=1;//是否关注公众号  0 没有关注 1 已关注
        }else{
            $user['focus_gzh_flag']=0;//是否关注公众号  0 没有关注 1 已关注
        }

        if($user['add_uid']==0){
            $user['isdev']=1;
        }else{
            $user['isdev']=1; 
        }
        $user['xitong']=null;  //所属系统
        if($user){
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;

        }

    } 

    public function getAgelist($age){

        if($age<5){
            $name="0-5岁";
        }elseif ($age<10&&$age>=5) {
           $name="5-10岁";
        }elseif ($age<15&&$age>=10) {
           $name="10-15岁";
        }elseif ($age<20&&$age>=15) {
           $name="15-20岁";
        }elseif ($age<25&&$age>=20) {
           $name="20-25岁";
        }elseif ($age<30&&$age>=25) {
           $name="25-30岁";
        }elseif ($age<35&&$age>=30) {
           $name="30-35岁";
        }elseif ($age<40&&$age>=35) {
           $name="35-40岁";
        }elseif ($age<45&&$age>=40) {
           $name="40-45岁";
        }elseif ($age<50&&$age>=45) {
           $name="45-50岁";
        }elseif ($age<55&&$age>=50) {
           $name="50-55岁";
        }elseif ($age<60&&$age>=55) {
           $name="55-60岁";
        }elseif ($age<65&&$age>=60) {
           $name="60-65岁";
        }elseif ($age<70&&$age>=65) {
           $name="65-70岁";
        }elseif ($age<75&&$age>=70) {
           $name="70-75岁";
        }elseif ($age<80&&$age>=75) {
           $name="75-80岁";
        }elseif ($age<85&&$age>=80) {
           $name="80-85岁";
        }elseif ($age<90&&$age>=85) {
           $name="85-90岁";
        }elseif ($age<95&&$age>=90) {
           $name="90-95岁";
        }elseif ($age<100&&$age>=95) {
           $name="95-100岁";
        }elseif ($age<105&&$age>=100) {
           $name="100-105岁";
        }elseif ($age<110&&$age>=105) {
           $name="105-110岁";
        }elseif ($age<115&&$age>=110) {
           $name="110-115岁";
        }elseif ($age<120&&$age>=115) {
           $name="115-120岁";
        }

        return $name;

    }
    //获取联系地址
    public function getAdr(){
        $userid=$_REQUEST['user_id'];
        $maps['user_id']=$userid;
        $maps['is_default']=1;
        $user=D("user_address")->where($maps)->find();

        if($user){
            $mapss['id']=$user['trading_area'];

            $trading_area=D("trading_area")->where($mapss)->find();
            $user['province_id']=$user['province'];
            $user['trading_area_name']=$trading_area['name'];
            $user['city_id']=$user['city'];
            $user['district_id']=$user['district'];
            $user['province']=get_region_name($user['province']);
            $user['city']=get_region_name($user['city']);
            $user['district']=get_region_name($user['district']);

        }else{
            $user['province_id']=null;
            $user['city_id']=null;
            $user['district_id']=null;
            $user['mobile']=null;
            $user['tel']=null;
            $user['email']=null;
            $user['address']=null;
            $user['trading_area']=null;
            $user['trading_area_name']=null;
            $user['province']=get_region_name($user['province']);
            $user['city']=get_region_name($user['city']);
            $user['district']=get_region_name($user['district']);
        }

    
        //$user['twon']=get_region_name($user['twon']);

        if($user){
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;

        }
    }

    public function updateAdr(){



        $dat['user_id']=$_REQUEST['uid'];
        $dat['is_default']=1;
        $user=D("user_address")->where($dat)->find();

        if($user){

            $dat['province']=$_REQUEST['province'];
            $dat['city']=$_REQUEST['city'];
            $dat['district']=$_REQUEST['district'];
            $dat['address']=$_REQUEST['address'];
            if(!empty($_REQUEST['mobile'])){
                $dat['mobile']=$_REQUEST['mobile'];
            }
            if(!empty($_REQUEST['tel'])){
                $dat['tel']=$_REQUEST['tel'];
            }
            if(!empty($_REQUEST['email'])){
                $dat['email']=$_REQUEST['email'];
            }
            $dat['time']=time();
            $dat['trading_area']=$_REQUEST['trading_area'];
            $where['address_id']=$user['address_id'];
            $user=D("user_address")->where($where)->save($dat);

        }else{

            $dat['province']=$_REQUEST['province'];
            $dat['city']=$_REQUEST['city'];
            $dat['district']=$_REQUEST['district'];
            $dat['address']=$_REQUEST['address'];
            $dat['is_default']=1;
            if(!empty($_REQUEST['mobile'])){
                $dat['mobile']=$_REQUEST['mobile'];
            }
            if(!empty($_REQUEST['tel'])){
                $dat['tel']=$_REQUEST['tel'];
            }
            if(!empty($_REQUEST['email'])){
                $dat['email']=$_REQUEST['email'];
            }
          
            $dat['trading_area']=$_REQUEST['trading_area'];
            //$where['id']=$_REQUEST['id'];
            $user=D("user_address")->add($dat);

           
        }


       

        if($user){
                $news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>1 ,'msg'=>'操作失败','data'=>null);
                echo json_encode($news,true);exit;
        }
    }

    public function getCity(){

        //默认传0
        $pid=$_REQUEST['pid'];

        $maps['parent_id']=$pid;
        $list=M('region')->where($maps)->select();

     

        if($list){
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;

        }

    }

    //查看顾问列表
    public function index(){

        $uid=$_REQUEST['uid'];//登录用户ID

        $store_id=$_REQUEST['store_id'];

        // $store_id=implode(',', $store_id);
        


        if (!$uid )
        {
            echo json_encode(array('code' =>0 ,'msg'=>'参数异常','data'=>[]), true);
            exit;
        }
        $member=D('admin')->where('admin_id='.$uid)->find();

        $role_id = $member['role_id'];//当前用户权限ID

        $vmaps['store_id']=array('in',$store_id[0]);

        $store=D("store")->where($vmaps)->where('is_forbid=0')->select();



        foreach ($store as $key => $value) {
            $store[$key]['dianzhang']=$value['shopkeeper'];
        }

        $vmapss['role_id']=$member['role_id'];
        $admin_role=D("admin_role")->where($vmapss)->find();


        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));

        $start_time=strtotime($BeginDate);
        //echo $start_time;
       
        $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
        //$end_time=strtotime($endDate);
        $end_time=strtotime($endDate)+24*3600*24-1;



        //$mpas['add_time']=array(array('egt',$start_time),array('elt',$end_time));  
        //查询开发客户人数
        // 权限为店员
        if ($role_id==5 || $role_id==10 || $role_id==10) {
            $kaifamap['add_uid']=$uid;
            $fwamap['first_leader']=$uid;
        // 权限为店长
        }else if($role_id==4){
            $sppk['store_id'] = $store_id[0];
            // 查找当前门店的所有店员ID
            $adminIdList = D('admin')->field('admin_id')->where($sppk)->select();
            $adminArr = array();
            foreach ($adminIdList as $val) {
                array_push($adminArr, $val['admin_id']);
            }
            $kaifamap['add_uid']=array('in', $adminArr);
            $fwamap['first_leader']=array('in', $adminArr);
        //权限为总监的时候
        }else if($role_id==2){
            //查询总监负责门店
            $sppk['admin_id'] = $uid;
            $get_store_id = D('admin')->where($sppk)->getField('store_id');
            //查询门店内所有店员 包涵店长
            $mappy['store_id'] = array('in', $get_store_id);
            $adminIdList = D('admin')->field('admin_id')->where($mappy)->select();
            $adminArr = array();
            foreach ($adminIdList as $val) {
                array_push($adminArr, $val['admin_id']);
            }
            $kaifamap['add_uid']=array('in', $adminArr);
            $fwamap['first_leader']=array('in', $adminArr);
        //权限为经理(超级管理)的时候
        }else if($role_id==1){
            
        }
        $kaifamap['reg_time']=array(array('gt',$start_time),array('lt',$end_time));
        $fwamap['end_time']=array(array('gt',$start_time),array('lt',$end_time));
        $kaifacount=D("users")->where($kaifamap)->count();
        $fuwucount=D("users")->where($fwamap)->count();

        $mapsss['store_id']=array('in',$store_id[0]);
        $huiyuancount=D("users")->where($mapsss)->count();

      
        $member['store']=$store;
        $member['kaifacount']=$kaifacount;
        $member['fuwucount']=$fuwucount;
        $member['huiyuancount']=$huiyuancount;
        $member['xitong']=$member['remark'];
        $member['juese']=$admin_role['role_name'];
        $member['birthday']=date('Y-m-d',$member['birthday']);
        $img=$member['img'];
        if(!empty($img)){
             $url=C("http_urls");
            //$url="http://bsl.265nt.com";
            $url.=$img;
            $member['img']=$url;
        }
        

         $gongling=date('Y-m-d',$member['add_time']);
        $member['gongling']=getAge($gongling);
        if($member['add_time']==0){
            $member['age']=1;
        }else{
            $birthday=date('Y-m-d',$member['add_time']);
            $member['age']=getAge($birthday)+1;
        }
        
        $mapss['pay_status']=1;
        $mapss['cid']=$uid;
        $mapss['type']=0;

        $total_amount=D("Order")->where($mapss)->sum('total_amount');

        $member['total_amount']=$total_amount;
        $member['sale_count']=$member['sale_count'];

        $bili=$total_amount/$member['sale_count'];
        $bili=ceil($bili*100);
        if($bili>100){
            $bili=100;
        }
        $member['sale_jindu']=$bili;



        $member['store_id']=0;

         if($member){
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$member);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;

        }
        
    }
    //商品编码和商品名称
    public function searchGood(){
        $goods_sn=$_REQUEST['goods_sn'];
        $goods_name=$_REQUEST['goods_name'];

        if(!empty($goods_sn)){
            $maps['goods_sn']=$goods_sn;
        }

        if(!empty($goods_name)){
            $maps['goods_name']=array('like','%'.$goods_name.'%');
        }

        $goods=M("goods")->where($maps)->select();
        if($goods){
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$goods);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;

        }
    }

    //获取服务顾问用户列表
    public function dafaList(){
        $uid=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];
        $role_id=$_REQUEST['role_id'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        

        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['sn'])){
            $mpas['sn']=array('like',"%".$_REQUEST['sn']."%");
        }
        
        if($role_id>2){
             $mpas['fuid']=$uid;
        }else{
             $mpas['store_id']=array('in',$store_id[0]);
        }
       
       
        $mpas['is_del']=0;
        $list=D("send_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }

     //我的代发
    public function mydafa(){
        $uid=$_REQUEST['memberid'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        

        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['sn'])){
             $mpas['sn']=array('like',"%".$_REQUEST['sn']."%");
        }
        

        $mpas['uid']=$uid;
        $mpas['is_del']=0;
        $list=D("send_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }  
    //代发操作
    public function dafa(){
        $uid=$_REQUEST['uid'];
        $member_id=$_REQUEST['member_id'];
        $store_id=$_REQUEST['store_id'];


        $product_name=$_REQUEST['product_name'];
        $send_time=$_REQUEST['send_time'];
        $requirement=$_REQUEST['requirement'];
        $sn1=date('YmdHis',time());

        $num=str_pad($uid,6,"0",STR_PAD_LEFT); 

        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

        $data['cid']=$uid;
        $data['uid']=$member_id;
        $data['sn']=$sn;
        $data['product_name']=$product_name;
        $data['requirement']=$requirement;
        $data['send_time']=$send_time;
        $data['store_id']=$store_id[0];
        $data['count']=$_REQUEST['count'];
        $data['box']=$_REQUEST['box'];
        $data['create_time']=time();

        $res=M("send_list")->add($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>1 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }
     //代发操作
    public function updateDafa(){
        $id=$_REQUEST['id'];
        
        $requirement=$_REQUEST['requirement'];
        $receive_time=$_REQUEST['receive_time'];
      
    
       
        $data['id']=$id;
        $data['status']=$_REQUEST['status'];
        $data['update_time']=time();

        $res=M("send_list")->save($data);
        if($res){


            $uploadList = $this->uploads($_FILES);
                foreach($uploadList as $kv){    

                   $data1['images']= $kv['urlpath'];
                   $data1['pid']= $id;
                   $data1['model']= 'send_list';
                   $data1['create_time']=time();

                   D("attach")->add($data1);
                
                }
            
           $news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
           echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>1 ,'msg'=>'操作失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }

    public function uploads(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     31457280000 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     UPLOAD_PATH.'dafa/'; // 设置附件上传根目录
        $upload->savePath  =     ''; // 设置附件上传（子）目录
        $upload->saveName  =     'uniqid'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功

            return $info;
        }
    }

    //获取服务顾问用户列表
    public function depositList(){
        $uid=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];
        $role_id=$_REQUEST['role_id'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }


        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['sn'])){
             $mpas['sn']=array('like',"%".$_REQUEST['sn']."%");
        }
        

        if($role_id>2){
              $mpas['fuid']=$uid;
        }else{
              $mpas['store_id']=array('in',$store_id[0]);
        }

      
      
        $mpas['is_del']=0;
        $list=D("deposit_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //取消代发
    public function del_daifa(){

        if(!empty($_REQUEST['sn'])){
            $maps['sn'] = $_REQUEST['sn'];
            $data['is_del'] = 1;
            $admin=D("send_list")->where($maps)->save($data);
        }
        if($admin){
            $news = array('code' =>1 ,'msg'=>'撤销成功！','data'=>null);
                echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'撤销失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //取消寄存
    public function del_deposit(){
       
        if(!empty($_REQUEST['sn'])){
            $maps['sn'] = $_REQUEST['sn'];
            $data['is_del'] = 1;
            $admin=D("deposit_list")->where($maps)->save($data);
        }
        if($admin){
            $news = array('code' =>1 ,'msg'=>'撤销成功！','data'=>null);
                echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'撤销失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //我的寄存列表
    public function mydeposit(){
        $uid=$_REQUEST['memberid'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }


        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['sn'])){
             $mpas['sn']=array('like',"%".$_REQUEST['sn']."%");
        }
        

        $mpas['uid']=$uid;
        $mpas['is_del']=0;
        $list=D("deposit_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    } 
     //代发操作
    public function deposit(){
        $uid=$_REQUEST['uid'];
        $member_id=$_REQUEST['member_id'];
        $product_name=$_REQUEST['product_name'];
        $store_id=$_REQUEST['store_id'];
        $receive_time=$_REQUEST['receive_time'];
        $requirement=$_REQUEST['requirement'];
       
        $sn1=date('YmdHis',time());

        $num=str_pad($uid,6,"0",STR_PAD_LEFT); 

        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

        $data['cid']=$uid;
        $data['uid']=$member_id;
        $data['sn']=$sn;
        $data['product_name']=$product_name;
        $data['store_id']=$store_id[0];
        $data['requirement']=$requirement;
        $data['receive_time']=$receive_time;
        $data['count']=$_REQUEST['count'];
        $data['box']=$_REQUEST['box'];
        $data['create_time']=time();

        $res=M("deposit_list")->add($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>1 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
    //添加沟通操作
    public  function doCommunica(){
        $uid=$_REQUEST['uid'];
        $member_id=$_REQUEST['member_id'];
        $store_id=$_REQUEST['store_id'];
        $cTime=$_REQUEST['cTime'];
        $c_person=$_REQUEST['c_person'];
        $tel=$_REQUEST['tel'];
        $status=$_REQUEST['status'];
        $content=$_REQUEST['content'];
        $type=$_REQUEST['type'];//沟通类别 1 购买沟通 2 回访沟通 3 提醒沟通 4 其他
       


        $data['fuid']=$uid;
        $data['uid']=$member_id;
       
        $data['cTime']=$cTime;
        $data['c_person']=$c_person;
        $data['store_id']=$store_id[0];
        $data['tel']=$tel;
        $data['status']=$status;
        $data['type']=$type;
        $data['content']=$content;
        $data['create_time']=strtotime($cTime);

        $res=M("communication")->add($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>1 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    //修改沟通操作
    public  function update_communica(){
        $id=$_REQUEST['com_id'];
        $fuid = $_REQUEST['uid'];//服务顾问ID
        $c_person=$_REQUEST['c_person'];
        $status=$_REQUEST['status'];
        $content=$_REQUEST['content'];

        $data['id']=$id;
        $data['fuid']=$fuid;
        $data['c_person']=$c_person;
        $data['com_status']=0;
        $data['status']=$status;
        $data['content']=$content;
        $data['update_time']=time();
       

        $res=M("communication")->save($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'修改成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'修改失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    //获取服务顾问用户列表
    public function communicationList(){
        $uid=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];
        $role_id=$_REQUEST['role_id'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['cTime'])){
            $mpas['cTime']=array('like',"%".$_REQUEST['cTime']."%");
        }
        if(!empty($_REQUEST['c_person'])){
            $mpas['c_person']=array('like',"%".$_REQUEST['c_person']."%");
        }

        if($role_id==5){
            $mpas['fuid']=$uid;
        }else{
            $mpas['store_id']=array('in',$store_id[0]);
        }

        
        
        $list=D("communication")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
        
        foreach ($list as $key => $val) {
            $list[$key]['kehuname'] = D('users')->where('user_id='.$val['uid'])->getField('nickname');
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    } 

      public function mycommunication(){
        $uid=$_REQUEST['memberid'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;

        $maps1['user_id']=$uid;
        $users=D("users")->where($maps1)->find();

        if($users==false){

        }

        if(!empty($_REQUEST['cTime'])){
            $mpas['cTime']=$_REQUEST['cTime'];
        }
        if(!empty($_REQUEST['c_person'])){
            $mpas['c_person']=$_REQUEST['c_person'];
        }

        $mpas['tel']=$users['mobile'];
        $list=D("communication")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
        foreach ($list as $key => $val) {
            $list[$key]['kehuname'] = D('users')->where('user_id='.$val['uid'])->getField('nickname');
        }
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    } 
     //添加上门操作
    public  function doVisit(){
        $uid=$_REQUEST['uid'];//拜访人
        $member_id=$_REQUEST['member_id'];//被拜访人
        $store_id=$_REQUEST['store_id'];//门店ID
        $cTime=$_REQUEST['cTime'];//创建时间
        $province=$_REQUEST['province'];
        $city=$_REQUEST['city'];
        $address=$_REQUEST['address'];
        $reason=$_REQUEST['reason'];
        $content=$_REQUEST['content'];
        $remark=$_REQUEST['remark'];
        $mobile=$_REQUEST['mobile'];//被拜访人手机号
      
       


        $data['cid']=$uid;
        $data['uid']=$member_id;
        $data['store_id']=$store_id[0];
       
        $data['cTime']=$cTime;
        $data['province']=$province;
        $data['city']=$city;
        $data['address']=$address;
        $data['remark']=$remark;
        $data['reason']=$reason;
        $data['content']=$content;
        $data['create_time']=time();
        $data['mobile']=$mobile;

        $res=M("visit_list")->add($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
     //上门列表
    public function visitList(){
        $uid=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];
        $role_id=$_REQUEST['role_id'];

        $member_id = $_REQUEST['memberId'];//会员中心相关记录 某个人的记录  人员ID

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['cTime'])){
            $mpas['cTime']=$_REQUEST['cTime'];
        }
        if(!empty($_REQUEST['c_person'])){
            $mpas['c_person']=$_REQUEST['c_person'];
        }

        // 手机号搜索条件
        if(!empty($_REQUEST['mobile'])){
            $mpas['mobile']=$_REQUEST['mobile'];
        }


        //搜索某个会员的被上门记录
        if ($member_id) {
            $mpas['uid'] = $member_id;
        }

        if($role_id>2){
               $mpas['cid']=$uid;
        }else{
             $mpas['store_id']=array('in',$store_id[0]);
        }

       
        
        $list=D("visit_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
        foreach ($list as $key => $val) {
            //获取拜访人姓名
            $bfname = M('admin')->where('admin_id='.$val['cid'])->getField('user_name');
            $list[$key]['bfname'] = $bfname;
            //获取被拜访人姓名
            $bbfname = M('users')->where('user_id='.$val['uid'])->getField('nickname');
            $list[$key]['bbfname'] = $bbfname;
        }
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    } 
    //判断是否存在
    public function checkUser(){
        $mobile=$_REQUEST['mobile'];
        $maps['mobile']=$mobile;
        $user=D("Users")->where($maps)->find();
        if($user){
            $uid=$user['user_id']; 
             $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$uid);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'此号码不是会员','data'=>null);
                echo json_encode($news,true);exit;
        }
    }


    //判断是否存在
    public function getMemberName(){
        $mobile=$_REQUEST['mobile'];
        $maps['mobile']=$mobile;
        $user=D("Users")->where($maps)->find();
        if($user){
            $nickname=$user['nickname']?$user['nickname']:'无姓名信息'; 
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$nickname);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'此号码不是会员','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

   
    //根据商品编号
    public function  getSpecGoods(){
        $store_id = $_REQUEST['store_id'];
        $sku=$_REQUEST['sku'];
        $sale_type=$_REQUEST['sale_type'];//购买类型 1 积分 2 购买

        //$maps11['sku']=$sku;
        $maps11['sku']=array('like',"%".$_REQUEST['sku']."%");
        //dump($maps11);
        $spec_goods =D("spec_goods_price")->where($maps11)->limit('1')->select();
        //echo M()->getlastsql();
        //exit;

        if($spec_goods==false){
             $news = array('code' =>0 ,'msg'=>'商品不存在！','data'=>null);
                echo json_encode($news,true);exit;

        }
        
        foreach($spec_goods as $key=>$value){
            $gmaps['goods_id']=$value['goods_id'];
            $gmaps['is_on_sale']=1;
            if ($sale_type==1) {
                $gmaps['type'] = array('in','0,1');//积分购买
            }elseif($sale_type==2){
                $gmaps['type'] = array('in','0,2');//金钱购买
            }
            $gmaps['is_on_sale']=1;
            $goods=D("goods")->field('goods_id,market_price,goods_name,goods_sn,spu,app_points')->where($gmaps)->find();
            if($goods==false){
                if ($sale_type==1) {
                    $news = array('code' =>0 ,'msg'=>'商品无法通过积分购买！','data'=>null);
                    echo json_encode($news,true);exit;
                }elseif($sale_type==2){
                    $news = array('code' =>0 ,'msg'=>'商品无法通过金钱购买！','data'=>null);
                    echo json_encode($news,true);exit;
                }else{
                    $news = array('code' =>0 ,'msg'=>'商品不存在！','data'=>null);
                    echo json_encode($news,true);exit;
                }
            }

            $spec_goods[$key]['goods_sn']=$goods['goods_sn'];
            $spec_goods[$key]['goods_name']=$goods['goods_name'];
            $spec_goods[$key]['spu']=$goods['spu'];
            $spec_goods[$key]['spec_key_name']=$value['key_name'];
          
            $spec_goods[$key]['goods_id']=$goods['goods_id'];
            $spec_goods[$key]['price']=$goods['market_price'];
            $spec_goods[$key]['app_points']=$goods['app_points'];
            $spec_goods[$key]['store_count']=$value['store_count'];
            $spec_goods[$key]['spec_key']=$value['key'];
            $spec_goods[$key]['sku']=$value['sku'];

            //查询总部库存商品库存
            if ($goods['goods_id']) {
                $whe['good_id'] = $goods['goods_id'];
                $whe['stock_type'] = 1;
                $over_number = M('stock')->where($whe)->sum('number');

                $spec_goods[$key]['over_number']=$over_number;
            }
            //如果门店ID存在  则查询门店剩余库存
            if ($store_id && $goods['goods_id']) {
                $whe1['good_id'] = $goods['goods_id'];
                $whe1['stock_type'] = 4;
                $whe1['resource_id'] = $store_id;
                $over_number1 = M('stock')->where($whe1)->sum('number');

                $spec_goods[$key]['store_count']=$over_number1;
            }
            
        }

        if($goods){
            $news = array('code' =>1 ,'msg'=>'获取成功','data'=>$spec_goods);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'获取失败','data'=>null);
            echo json_encode($news,true);exit;
        }

    }
    //添加购物车
    public function addCart(){
        $uid=$_REQUEST['uid'];
        if($uid==0){
            $user_level['discount']=100;
        }else{
            $vmaps['user_id']=$uid;
            $user=D("Users")->where($vmaps)->find();
            $lmaps['level_id']=$user['level'];
            $user_level=D("user_level")->where($lmaps)->find();
        }
      

        $discount=$user_level['discount']/100;
        $discount=1-$discount;

        $inc_type =  I('get.inc_type','basic');
       
        $config = tpCache($inc_type);


        $money_integral=$config["money_integral"];
        $score_integral=$config["score_integral"];
        $duihuan_integral=$config["duihuan_integral"]/100;


        $score_money=$money_integral/$score_integral;

        $cid=$_REQUEST['cid'];
        $goods_price=$_REQUEST['goods_price'];
        $data=$_REQUEST['data'];              
        $order_sn=date('YmdHis').rand(1000,9999);
        $userprice=$goods_price*$discount;

        $store_id=$_REQUEST['store_id'];       
        if($store_id[0]==0||empty($store_id)){
              $news = array('code' =>0 ,'msg'=>'门店信息有误','data'=>null);
                echo json_encode($news,true);exit;
        }

        $arr = json_decode($data, true);

        // 查看库存是否充足
        foreach ($arr as $key => $value) {
            $goods_id=$value['goods_id'];
            $goods_num=$value['count'];
            $resource_id = $store_id[0]; 
            $stock_type = 4; 
            $this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
        }

        $adata['user_id']=$uid;
        $adata['cid']=$cid;
        $adata['order_sn']=$order_sn;
        $adata['goods_price']=$goods_price;
        $adata['user_money']=$userprice;
        $adata['total_amount']=$goods_price;
        $adata['add_time']=time();
        $adata['store_id']=$store_id[0];
        $order=D("Order")->add($adata);

        if($order){

            $arr = json_decode($data, true);
            foreach ($arr as $key => $value) {
                
                $datas['goods_name']=$value['goods_name'];
                $datas['market_price']=$value['market_price'];
                $datas['goods_price']=$value['price'];
                $datas['member_goods_price']=$value['price'];
                $datas['goods_id']=$value['goods_id'];
                $datas['sku']=$value['sku'];
                $datas['goods_sn']=$value['goods_sn'];
                $datas['spec_key']=$value['spec_key'];
                $datas['spec_key_name']=$value['spec_key_name'];
                $datas['goods_num']=$value['count'];
                $datas['selected']=1;
                $datas['user_id']=$uid;
                $datas['cid']=$cid;
                $datas['order_id']=$order; 

                // 添加单个商品积分和总数量积分
                $app_point = D('goods')->where('goods_id='.$value['goods_id'])->getField('app_points');
                $total_points = $app_point*$value['count'];
                $arr[$key]['app_point'] = $app_point;               
                $arr[$key]['total_points'] = $total_points;               
                $goods=D("order_goods")->add($datas);
            }
                $pricess=$goods_price-$userprice;
                $keyong_points=$pricess*$duihuan_integral;


               $getKeScore=getKeScore($uid); 

              $list['order_sn']=$order_sn;
              $list['goods_price']=$goods_price;
              $list['user_money']=$userprice;
              $list['discount']=$user_level['discount'];
              $list['score_money']=$score_money;
              $list['keyong_points']=$keyong_points/$score_money;
              if ($getKeScore<0) {
                $list['pay_points']=0;
              }else{
                 $list['pay_points']=$getKeScore;
              }
              $list['data']=$arr;
              $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>$list);
                echo json_encode($news,true);exit;

        }else{

            $news = array('code' =>0 ,'msg'=>'添加失败','data'=>null);
            echo json_encode($news,true);exit;

        }
    
    }


    //积分兑换
    public function addCarts(){
        $uid=$_REQUEST['uid'];
        if($uid==0){
            $user_level['discount']=100;
        }else{
            $vmaps['user_id']=$uid;
            $user=D("Users")->where($vmaps)->find();
            $lmaps['level_id']=$user['level'];
            $user_level=D("user_level")->where($lmaps)->find();
        }
        
        $keScore=getKeScore($uid);

        if($keScore<$_REQUEST['goods_price']){
              $news = array('code' =>0 ,'msg'=>'可用积分不够！','data'=>null);
                echo json_encode($news,true);exit;
        }

        $cid=$_REQUEST['cid'];
        $goods_price=$_REQUEST['goods_price'];
        $data=$_REQUEST['data']; 
        $store_id=$_REQUEST['store_id'];       

        $order_sn=date('YmdHis').rand(1000,9999);
           
        if($store_id[0]==0||empty($store_id)){
              $news = array('code' =>0 ,'msg'=>'门店信息有误','data'=>null);
                echo json_encode($news,true);exit;
        }

        // $adata['user_id']=$uid;
        // $adata['cid']=$cid;
        // $adata['order_sn']=$order_sn;
        // $adata['goods_price']=0;
        // $adata['integral']=$goods_price;
        // $adata['total_amount']=0;
        // $adata['add_time']=time();
        // $adata['store_id']=$store_id[0];
        // $order=D("Order")->add($adata);

        // if($order){

            $arr = json_decode($data, true);
            $datas['user_id']=$uid;
            $str = '';

            // 查看库存是否充足
            foreach ($arr as $key => $value) {
                $goods_id=$value['goods_id'];
                $goods_num=$value['count'];
                $resource_id = $store_id[0]; 
                $stock_type = 4; 
                $this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
            }


            //开启事务
            $tranDb = M();
            $tranDb->startTrans();

            //新增积分兑换表记录
            $datas['cid']=$cid;
            $datas['order_sn']=$order_sn;
            // 查找联系电话
            $mobile = M('users')->where('user_id='.$uid)->getField('mobile');
            $datas['mobile']=$mobile;
            // 查找人员姓名
            $user_name = M('users')->where('user_id='.$uid)->getField('nickname');
            if (!$user_name) {
                $user_name = $mobile;
            }
            $datas['consignee'] = $user_name;
            $datas['pay_status'] = 0;
            $datas['create_time']=time();
            $datas['type']=1;
            $datas['status']=1;
            $datas['source']=0;
            $datas['store_id']=$store_id[0];
            $datas['pay_points']=$goods_price;
            $add = D("send_points")->add($datas);

            //标识
            $str = '';
            //新增app积分兑换详情表
            foreach ($arr as $key => $value) {
                $datas1['send_points_id'] = $add;
                $datas1['goods_id'] = $value['goods_id'];
                $datas1['goods_num'] = $value['count'];
                // 添加单个商品积分和总数量积分
                // $app_point = D('goods')->where('goods_id='.$value['goods_id'])->getField('app_points');
                $app_points = $value['app_points'];
                $total_points = $app_points*$value['count'];
                $datas1['points']=$total_points;
                $datas1['goods_name']=$value['goods_name'];
                $datas1['order_sn']=$order_sn;
                $datas1['create_time']=time();
                
                $add1 = D("send_points_detail")->add($datas1);

                if (!$add1) {
                    $str .= '新增失败';
                }

                $arr[$key]['total_points'] = $total_points;               
                $arr[$key]['app_point'] = $app_point;               
                
            }

            if (!$str) {
                $tranDb->commit();
            }else{
                $tranDb->rollback();
            }

            $pricess=$goods_price-$userprice;
            $keyong_points=$pricess*$duihuan_integral;

            $getKeScore=getKeScore($uid); 

            $list['order_sn']=$order_sn;
            $list['goods_price']=$_REQUEST['goods_price'];
            $list['user_money']=$userprice;
            $list['discount']=$user_level['discount'];
            $list['score_money']=0;
            $list['keyong_points']=$getKeScore;
            $list['pay_points']=$keScore;
            $list['data']=$arr;
            if (!$str) {
                $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>$list);
                echo json_encode($news,true);exit;
            }else{
                $news = array('code' =>0 ,'msg'=>'添加失败','data'=>null);
                echo json_encode($news,true);exit;
            }
    
    }

    public function getBookgoods(){

        $sku=$_REQUEST['sku'];
        $goods_name=$_REQUEST['goods_name'];
        
        if(!empty($sku)){
             $maps11['sku']=$sku;
            //dump($maps11);
             $spec_goods =D("spec_goods_price")->where($maps11)->find();
             $goodsid1=$spec_goods['goods_id'];
        }

        if(empty($goods_name)&&empty($sku)){

            $news = array('code' =>0 ,'msg'=>'请输入请求条件！','data'=>null);
                echo json_encode($news,true);exit;

        }
        
        $mapss['goods_name']=array('like','%'.$goods_name.'%');
        $goods=D("goods")->where($mapss)->select();

        $goodsid=formatArray($goods,'goods_id');

        if(!empty($goodsid1)){
            $goodsid.=',';
            $goodsid.=$goodsid1;
        }

        $wmaps['goods_id']=array('in',$goodsid);

        $spec_goods_price=D("spec_goods_price")->where($wmaps)->select();
        foreach ($spec_goods_price as $key => $value) {
            $vms['goods_id']=$value['goods_id'];
            $goods=D("goods")->where($vms)->find();

            $spec_goods_price[$key]['goods_name']=$goods['goods_name'];
            $spec_goods_price[$key]['goods_sn']=$goods['goods_sn'];
        }


        if($spec_goods_price){
            
             $news = array('code' =>1 ,'msg'=>'获取成功','data'=>$spec_goods_price);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败','data'=>null);
                echo json_encode($news,true);exit;
        }


    }

    //添加订货
    public function addBook(){
        
        $cid=$_REQUEST['cid'];
        $goods_price=$_REQUEST['goods_price'];
        $data=$_REQUEST['data'];
        
        $order_sn=date('YmdHis').rand(1000,9999);


        //$adata['user_id']=$uid;
        $adata['cid']=$cid;
        $adata['order_sn']=$order_sn;
        //$adata['goods_price']=$goods_price;
        //$adata['total_amount']=$goods_price;
        $adata['add_time']=time();

        $order=D("Book")->add($adata);

        if($order){

            $arr = json_decode($data, true);

            foreach ($arr as $key => $value) {
                
                $datas['goods_name']=$value['goods_name'];
                $datas['market_price']=$value['price']*$value['count'];
                $datas['goods_price']=$value['price'];
                $datas['member_goods_price']=$value['price'];
                $datas['goods_sn']=$value['goods_sn'];
                $datas['goods_id']=$value['goods_id'];
                $datas['sku']=$value['sku'];
                $datas['spec_key']=$value['key'];
                $datas['spec_key_name']=$value['key_name'];
                $datas['goods_num']=$value['count'];
                $datas['selected']=1;
                //$datas['user_id']=$uid;
                $datas['cid']=$cid;
                $datas['order_id']=$order;
                
                $goods=D("book_goods")->add($datas);
                
               // echo M()->getlastsql();    
            }

        

              $list['order_sn']=$order_sn;
              //$list['goods_price']=$goods_price;
              //$list['data']=$arr;

              $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
                echo json_encode($news,true);exit;

        }else{

                $news = array('code' =>0 ,'msg'=>'添加失败','data'=>null);
                echo json_encode($news,true);exit;

        }
    
    }
    //app通知
    public function notice(){

         $uid=$_REQUEST['uid'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($_REQUEST['cat_id'])){
            $mpas['cat_id']=$_REQUEST['cat_id'];
        }else{
             $mpas['cat_id']=1;
        }
        
        $mpas['fuid']=$uid;
        $list=D("app_article")->field('article_id,add_time,title,cat_id')->where($mpas)->order('article_id desc')->limit($start.','.$pages)->select();

        foreach ($list as $key => $value) {

             $url=C("http_urls");
            $url.="/index.php/Home/index/notices/article_id/".$value['article_id'];
            
            $list[$key]['url']=$url;
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //获取验证码
    public function getCode(){

        sendsmss('13585772389','13434');
    }
    //发货列表
    public function apply_comfirm_list(){

        $cid=$_REQUEST['cid'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

       // if(!empty($_REQUEST['goods_sn'])){
       //     $mpas['goods_sn']=array('like',"%".$_REQUEST['goods_sn']."%");
       // }

        $pages="10";
        $start=($p-1)*$pages;

        $maps['cid']=$cid;
        $maps['shipping_status']=1;

        $list=D("book")->where($maps)->field('order_id,order_sn,shipping_status,shipping_time,shipping_uid')->order('order_id desc')->limit($start.','.$pages)->select();
        foreach ($list as $key => $value) {
            $maps1['order_id']=$value['order_id'];
             $book_goods=D("book_goods")->where($maps1)->select();

             foreach ($book_goods as $key1 => $val) {
                  $img=goods_thum_images($val['goods_id'],200,200);
                  $url="http://cgg.265nt.com";
                  $url.=$img;
                  $book_goods[$key1]['img']=$url;
             }

             if($book_goods==false){
                $list[$key]['goods']=array();
             }else{
                $list[$key]['goods']=$book_goods;

             }
             
             $maps2['admin_id']=$value['shipping_uid'];
             $admin=D("admin")->where($maps2)->find();

             $list[$key]['danwei']=$admin['remark'];
             $list[$key]['hipping_name']=$admin['name'];
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //确认入库操作
    public function confirm_into_store(){
        $order_id=$_REQUEST['order_id'];
        $pay_status=$_REQUEST['pay_status'];

        $data['confirm_time']=time();
        $data['order_id']=$order_id;
        $data['pay_status']=$pay_status;
        $res=D("book")->save($data);
        if($res){

            if($pay_status==1){
                $maps['order_id']=$order_id;

                $list=D("book_goods")->where($maps)->select();

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
                    }
                }
            }
            



            $news = array('code' =>1 ,'msg'=>'操作成功','data'=>null);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }

     //入库记录
    public function into_store_list(){

        $cid=$_REQUEST['cid'];
        $order_sn=$_REQUEST['order_sn'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        if(!empty($_REQUEST['order_sn'])){
            $maps['order_sn']=$order_sn;
        }
        $pages="10";
        $start=($p-1)*$pages;

        $maps['cid']=$cid;
        $maps['pay_status']=1;

        $list=D("book")->where($maps)->field('order_id,cid,order_sn,shipping_status,shipping_time,confirm_time,shipping_uid')->order('order_id desc')->limit($start.','.$pages)->select();
        foreach ($list as $key => $value) {
            $maps1['order_id']=$value['order_id'];
             $book_goods=D("book_goods")->where($maps1)->select();
             foreach ($book_goods as $key1 => $val) {
                  $img=goods_thum_images($val['goods_id'],200,200);

                  //dump($img);
                  $url="http://cgg.265nt.com";
                  $url.=$img;
                  $book_goods[$key1]['img']=$url;
                  $book_goods[$key1]['danwei']=$admin['remark'];
                  $book_goods[$key1]['hipping_name']=$admin['name'];
             }

             if($book_goods==false){
                $list[$key]['goods']=array();
             }else{
                $list[$key]['goods']=$book_goods;

             }
             
             $maps2['admin_id']=$value['cid'];
             $admin=D("admin")->where($maps2)->find();

             $list[$key]['danwei']=$admin['remark'];
             $list[$key]['hipping_name']=$admin['name'];
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //实时库存
    public  function real_time_stock(){
        $cid=$_REQUEST['cid'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $maps1['admin_id']=$cid;

        $admin=D("Admin")->where($maps1)->find();



        $pages="10";
        $start=($p-1)*$pages;

        $maps['store_id']=$admin['store_id'];
        
        if(!empty($_REQUEST['goods_sn'])){
            $maps['sku']=array('like',"%".$_REQUEST['goods_sn']."%");
        }

        $list=D("store_data")->where($maps)->order('id desc')->limit($start.','.$pages)->select();  
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }

    //通知列表
    public  function notice_list(){
        $cat_id=$_REQUEST['cat_id'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

      

        $pages="10";
        $start=($p-1)*$pages;

        $maps['cat_id']=$cat_id;
        

        $list=D("app_article")->where($mpas)->order('article_id desc')->limit($start.','.$pages)->select();

       
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
   
    //送货列表
    public function shipping_list(){

        $cid=$_REQUEST['cid'];
        $store_id=$_REQUEST['store_id'];
        $role_id=$_REQUEST['role_id'];
       
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;
        if($role_id>2){
            $maps['cid']=$cid;
        }else{
            $maps['store_id']=array('in',$store_id[0]);
        }
        
        $maps['shipping_status']=1;

        $list=D("order")->where($maps)->order('order_id desc')->limit($start.','.$pages)->select();
        foreach ($list as $key => $value) {
            $maps1['order_id']=$value['order_id'];
             $book_goods=D("order_goods")->where($maps1)->select();
             foreach ($book_goods as $key1 => $val) {
                  $img=goods_thum_images($val['goods_id'],200,200);

                  //dump($img);
                  $url="http://cgg.265nt.com";
                  $url.=$img;
                  $book_goods[$key1]['img']=$url;
                  $book_goods[$key1]['danwei']=$admin['remark'];
                  $book_goods[$key1]['hipping_name']=$admin['name'];
             }

             if($book_goods==false){
                $list[$key]['goods']=array();
             }else{
                $list[$key]['goods']=$book_goods;

             }
             
             $maps2['admin_id']=$value['cid'];
             $admin=D("admin")->where($maps2)->find();

             $list[$key]['danwei']=$admin['remark'];
             $list[$key]['hipping_name']=$admin['name'];
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //参会
    public function meeting(){

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;
        
        $maps['start_day']=array('gt',time()-8*3600);

        $list=D("activity")->where($maps)->order('activity_id desc')->limit($start.','.$pages)->select();

        foreach ($list as $key1 => $val) {
                 
                  $url="http://cgg.265nt.com";
                  $url.=$val['img'];
                  $list[$key1]['img']=$url;
        }
       
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //送货操作
    public function send_goods(){
        $order_id=$_REQUEST['order_id'];

        $data['order_id']=$order_id;
        $data['shipping_status']=2;

        $res=D("order")->save($data);
        if($res){
              $news = array('code' =>1 ,'msg'=>'送货成功！','data'=>null);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'送货失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }
    //销售列表
    public function sale_list(){


         $cid=$_REQUEST['cid'];
         $store_id=$_REQUEST['store_id'];
         $day=$_REQUEST['daytype'];
        
         $start_time=$_REQUEST['start_time'];
         $end_time=$_REQUEST['end_time'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        
        /**
        if(!empty($store_id)){
            $smaps['store_id']=$store_id;
            $admin=D("admin")->where($smaps)->select();
            $cids=formatArray($admin,'admin_id');
            $maps['cid']=array('in',$cids);

        }else{
            $maps['cid']=$cid;
        }
        */

        $maps['store_id']=array('in',$store_id[0]);

        $start_time=strtotime($start_time);
        $end_time=strtotime($end_time)+24*3600-10; 

        if($start_time!='0'&&$end_time!='0'){

            if($start_time==$end_time){
                $end_time=$end_time+24*3600-10;
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }else{

                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }

            

        }elseif($start_time!=''){
            $maps['add_time']=array('gt',$start_time);
        }elseif($end_time!=''){
            $maps['add_time']=array('lt',$end_time);
        }else{

            if($day==1){

                $daytime=strtotime(date("Y-m-d"));
                $daytime1=$daytime+24*3600-10;

                $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));

            }

            if($day==2){

                  $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
    
                  $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
                  $month=strtotime($BeginDate);
                  $month1=strtotime($endDate)+24*3600-10;
                  $maps['add_time']=array(array('gt',$month),array('lt',$month1));
            }

            if($day==3){

                $radio=date('Y');
                $begintimeall=$radio."-01-01";
                $endtimeall=$radio."-12-31";

                $beginYear=strtotime($begintimeall);
                $endYear=strtotime($endtimeall);

                $maps['add_time']=array(array('gt',$beginYear),array('lt',$endYear));

            }
        }


        $pages="10";
        $start=($p-1)*$pages;

       // $maps['cid']=$cid;
        $maps['pay_status']=1;
        $maps['type']=0;    
        $maps['order_status']=array('not in','3,5');

        

        $counts=D("order")->where($maps)->count();
        $total_amount=D("order")->where($maps)->sum('order_amount');

        $list=D("order")->where($maps)->order('order_id desc')->limit($start.','.$pages)->select();

        //echo M()->getlastsql();
        //exit;
        foreach ($list as $key => $value) {
            $maps1['order_id']=$value['order_id'];
            $r['store_id'] = $value['store_id'];
            $where= D('store')->where($r)->where('is_forbid=0')->find();
            $list[$key]['shop_no'] =  $where['shop_no'];

            $book_goods=D("order_goods")->where($maps1)->select();
            foreach ($book_goods as $key1 => $val) {
                $img=goods_thum_images($val['goods_id'],200,200);

                $url="http://cgg.265nt.com";
                $url.=$img;
                $book_goods[$key1]['img']=$url;
                $book_goods[$key1]['danwei']=$admin['remark'];
                $book_goods[$key1]['hipping_name']=$admin['name'];
            }

            if($book_goods==false){
                $list[$key]['goods']=array();
            }else{
                $list[$key]['goods']=$book_goods;

            }
             
            $maps2['admin_id']=$value['cid'];
            $admin=D("admin")->where($maps2)->find();

            $maps3['user_id']=$value['user_id'];

            $users=D("Users")->where($maps3)->find();


            $list[$key]['danwei']=$admin['remark'];
            $list[$key]['mobile']=$users['mobile'];
            $list[$key]['hipping_name']=$admin['name'];
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
       
        if($list){

            $list1['total_amount']=$total_amount;
            $list1['counts']=$counts;
            $list1['list']=$list;

            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list1);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }


     //销售列表
    public function sale_lists(){

        $store_id=$_REQUEST['store_id'];

        $day=$_REQUEST['daytype'];
        
        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        
        /**
        if(!empty($store_id)){
            $smaps['store_id']=$store_id;
            $admin=D("admin")->where($smaps)->select();
            $cids=formatArray($admin,'admin_id');
            $maps['cid']=array('in',$cids);

        }else{
            $maps['cid']=$cid;
        }
        */


        $start_time=strtotime($start_time);
        $end_time=strtotime($end_time)+24*3600-10; 

        
        if($_REQUEST['start_time']!=''&&$_REQUEST['end_time']!=''){

            if($start_time==$end_time){
                $end_time=$end_time+24*3600-10;
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }else{

                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }

            

        }elseif($_REQUEST['start_time']!=''){
            $maps['add_time']=array('gt',$start_time);
        }elseif($_REQUEST['end_time']!=''){
            $maps['add_time']=array('lt',$end_time);
        }else{

            if($day==1){

                $daytime=strtotime(date("Y-m-d"));
                $daytime1=$daytime+24*3600-10;

                $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));

            }

            if($day==2){

                  $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
    
                  $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
                  $month=strtotime($BeginDate);
                  $month1=strtotime($endDate)+24*3600-10;
                  $maps['add_time']=array(array('gt',$month),array('lt',$month1));
            }

            if($day==3){

                $radio=date('Y');
                $begintimeall=$radio."-01-01";
                $endtimeall=$radio."-12-31";

                $beginYear=strtotime($begintimeall);
                $endYear=strtotime($endtimeall);

                $maps['add_time']=array(array('gt',$beginYear),array('lt',$endYear));

            }
          
          }
      

        $pages="10";
        $start=($p-1)*$pages;

        if(!empty($store_id[0])){
            $maps['store_id']=array('in',$store_id[0]);
        }

        

        $is_first=$_REQUEST['is_first'];

        // 查询首单   0  全部数据 1 查询首单 2 查询非首单 3 查询非会员
        if($is_first==1){
            $maps['is_first']=1;    
            $maps['user_id']=array('NEQ',0);    
        }else if($is_first==2){
            $maps['is_first'] = array('NEQ',1);
            $maps['user_id']=array('NEQ',0);  
        }else if($is_first==3){
            $maps['user_id']=array('EQ',0);
        }


        $pay_name=$_REQUEST['pay_name'];
        if(!empty($pay_name)){
            $maps['pay_name']=$pay_name;    
        }

        // 查询消费金额
        $min_use_amount=$_REQUEST['min_use_amount'];
        $max_use_amount=$_REQUEST['max_use_amount'];

        // 有最小值 有最大值
        if(!empty($min_use_amount) && !empty($max_use_amount)){
            $maps['total_amount'] = array(array('gt',$min_use_amount),array('lt',$max_use_amount));

        // 有最大值
        }elseif(!empty($max_use_amount) && empty($min_use_amount)){
            $maps['total_amount'] = array('lt',$max_use_amount);

        // 有最小值
        }elseif(empty($max_use_amount) && !empty($min_use_amount)){
            $maps['total_amount'] = array('GT',$min_use_amount);
        }

        $maps['store_id']=array('in',$store_id[0]);
        $maps['pay_status']=1;
        $maps['type']=0;    
        $maps['order_status']=array('not in','3,5');

        $counts=D("order")->where($maps)->count();
        $total_amount=D("order")->where($maps)->sum('order_amount');

        $list=D("order")->where($maps)->order('order_id desc')->limit($start.','.$pages)->select();

        foreach ($list as $key => $value) {
            $maps1['order_id']=$value['order_id'];
            $r['store_id'] = $value['store_id'];
            $where= D('store')->where($r)->where('is_forbid=0')->find();
            $list[$key]['shop_no'] =  $where['shop_no'];
            $list[$key]['store_name'] =  $where['store_name'];

            //会员等级
            $mapss['user_id'] = $value['user_id'];
            $usersss=D("Users")->where($mapss)->find();

            //获取会员等级
            $dengji = $this->getCount($usersss['level']);
            $list[$key]['dengji'] = $dengji;

            //获取增加积分
            $mapss11['level_id']=$usersss['level'];
            $userlevel=D("user_level")->where($mapss11)->find();
            $ps=$userlevel['ps'];
            $score=$order_amount*$ps;
            $score=$score/100;
            $score=sprintf('%.2f', $score);
            $list[$key]['addjifen'] = $score;

            //总积分
            $pay_points1=$usersss['pay_points'];
            $list[$key]['zongjifen'] = $pay_points1;

            //备注
            
            $book_goods=D("order_goods")->where($maps1)->select();
            foreach ($book_goods as $key1 => $val) {
                  $img=goods_thum_images($val['goods_id'],200,200);
                  $url="http://cgg.265nt.com";
                  $url.=$img;
                  $book_goods[$key1]['img']=$url;
                  $book_goods[$key1]['danwei']=getSpu($val['goods_id']);
                  $book_goods[$key1]['hipping_name']=$admin['name'];
            }

             if($book_goods==false){
                $list[$key]['goods']=array();
             }else{
                $list[$key]['goods']=$book_goods;

             }
             
             $maps2['admin_id']=$value['cid'];
             $admin=D("admin")->where($maps2)->find();

             $maps3['user_id']=$value['user_id'];

             $users=D("Users")->where($maps3)->find();


             $list[$key]['danwei']=$admin['remark'];
             $list[$key]['mobile']=$users['mobile'];
             $list[$key]['hipping_name']=$admin['name'];
        }

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        if($list){
            $list1['total_amount']=$total_amount;
            $list1['counts']=$counts;
            $list1['list']=$list;
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list1);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
    //销售统计
    public function sale_count(){
        
        $cid=$_REQUEST['cid'];
        $store_id=$_REQUEST['store_id'];

        $day=$_REQUEST['daytype'];


        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $count_peixu=$_REQUEST['count'];//以数量排序  1 升顺 2 降序
        $price_peixu=$_REQUEST['price'];//以金额排序  1 升顺 2 降序
        $goodsNum_peixu=$_REQUEST['goodsNum'];//以商品编号排序  1 升顺 2 降序
        // if(!empty($store_id)){
        //     $smaps['store_id']=$store_id;
        //     $admin=D("admin")->where($smaps)->select();
        //     $cids=formatArray($admin,'admin_id');
        //     $maps['cid']=array('in',$cids);

        // }else{
        //     $maps['cid']=$cid;
        // }

        $pages="15";
        $start=($p-1)*$pages;
        $maps['store_id']=array('in',$store_id[0]);

        $start_time=strtotime($start_time);
        $end_time=strtotime($end_time)+24*3600-1; 
       

        if($start_time!=''&&$end_time!=''){
            if($start_time==$end_time){
                $end_time=$end_time+24*3600-10;
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }else{
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }

        }elseif($_REQUEST['start_time']!=''){
            $maps['add_time']=array('gt',$start_time);
        }elseif($_REQUEST['end_time']!=''){
            $maps['add_time']=array('lt',$end_time);
        }else{
            if($day==1){

                $daytime=strtotime(date("Y-m-d"));
                $daytime1=$daytime+24*3600-10;

                $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));

            }

            if($day==2){

                $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
    
                $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
                $month=strtotime($BeginDate);
                $month1=strtotime($endDate)+24*3600-10;
                $maps['add_time']=array(array('gt',$month),array('lt',$month1));
            }

            if($day==3){

                $radio=date('Y');
                $begintimeall=$radio."-01-01";
                $endtimeall=$radio."-12-31";

                $beginYear=strtotime($begintimeall);
                $endYear=strtotime($endtimeall);

                $maps['add_time']=array(array('gt',$beginYear),array('lt',$endYear));

            }
        }

        //$maps['cid']=$cid;
        $maps['pay_status']=1;
        $maps['type']=0;
        $maps['order_status']=array('not in','3,5');
        $list=D("order")->where($maps)->order('order_id desc')->select();
        $orderid=formatArray($list,'order_id');
        $where['order_id']=array('in',$orderid);

        if ($goodsNum_peixu==1) {
            // 商品编号升序
            $order_goods_all=D("order_goods")->where($where)->group('sku')->order('goods_id asc')->select();
        }else if($goodsNum_peixu==2){
            // 商品编号降序
            $order_goods_all=D("order_goods")->where($where)->group('sku')->order('goods_id desc')->select();
        }else{
            // 商品编号升序
            $order_goods_all=D("order_goods")->where($where)->group('sku')->order('goods_id asc')->select();
        }
        $salecount_all = 0;  //销售总数
        $all_price_all = 0;  //销售总金额
        $zongcount = 0;  //销售总数量
        if($order_goods_all){
            foreach ($order_goods_all as $key => $value) {
                $where['sku']=$value['sku'];

                $cou=D("order_goods")->where($where)->select();

                $count_num = D("order_goods")->where($where)->count();

                $sums = 0;
                $counts = 0;
                $salecount = 0;
                foreach ($cou as $value) {
                    $sums += $value['goods_price']*$value['goods_num'];
                    $counts ++;
                    $salecount += $value['goods_num']; 
                }
                $all_price_all += $sums;
                $salecount_all += $counts;
                $zongcount += $salecount;
                //echo M()->getlastsql();
                $order_goods_all[$key]['salecount']=$salecount;
                // $salecount_all += $counts;
                $order_goods_all[$key]['all_price'] = $sums;
                $order_goods_all[$key]['count_num'] = $count_num;
                // $all_price_all += $value['goods_price']*$counts;
            }
        }
        // 数量排序
        foreach($order_goods_all as $arr2){
            $flag[]=$arr2["salecount"];
        }
        if ($count_peixu==1) {
            // 数量升序
            array_multisort($flag, SORT_ASC, $order_goods_all);
            
        }else if($count_peixu==2){
            // 数量降序
            array_multisort($flag, SORT_DESC, $order_goods_all);
            
        }

        // 金额排序
        foreach($order_goods_all as $arr3){
            $flag1[]=$arr3["all_price"];
        }
        if ($price_peixu==1) {
            // 数量升序
            array_multisort($flag1, SORT_ASC, $order_goods_all);
            
        }else if($price_peixu==2){
            // 数量降序
            array_multisort($flag1, SORT_DESC, $order_goods_all);
            
        }

        //$order_goods['all_price'] = $order_goods['goods_price']*$order_goods['goods_num'];
        
        $len = count($order_goods_all);
        $order_goods = array();
        for ($i=$start; $i < min($len, $start+15); $i++) { 
            array_push($order_goods,$order_goods_all[$i]);
        }
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        $obj = new \StdClass();
        $obj->list = $order_goods;
        $obj->zongqian = $all_price_all;//销售总钱数
        $obj->zongshu = $salecount_all;//销售总笔数
        $obj->zongcount = $zongcount;//销售总数量
        if($list){
               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }


    public function seeAllBySku(){

        $cid = $_REQUEST['cid'];
        $store_id = $_REQUEST['store_id'];

        $day = $_REQUEST['daytype'];

        $sku = $_REQUEST['sku'];

        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];

        if(empty($_REQUEST['p'])){
            $p = 1;
        }else{
            $p = $_REQUEST['p'];
        }

        $pages = "15";
        $start = ($p-1)*$pages;
        $maps['store_id'] = array('in',$store_id[0]);

        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time)+24*3600-10; 
       

        if($start_time != '' && $end_time != ''){
            if($start_time == $end_time){
                $end_time = $end_time+24*3600-10;
                $maps['add_time'] = array(array('gt',$start_time),array('lt',$end_time));
            }else{
                $maps['add_time'] = array(array('gt',$start_time),array('lt',$end_time));
            }

        }elseif($_REQUEST['start_time'] != ''){
            $maps['add_time'] = array('gt',$start_time);
        }elseif($_REQUEST['end_time'] != ''){
            $maps['add_time'] = array('lt',$end_time);
        }else{
            if($day == 1){
                $daytime = strtotime(date("Y-m-d"));
                $daytime1 = $daytime+24*3600-10;
                $maps['add_time'] = array(array('gt',$daytime),array('lt',$daytime1));
            }

            if($day == 2){
                $BeginDate = date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
                $endDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
                $month = strtotime($BeginDate);
                $month1 = strtotime($endDate)+24*3600-10;
                $maps['add_time'] = array(array('gt',$month),array('lt',$month1));
            }

            if($day == 3){
                $radio = date('Y');
                $begintimeall = $radio."-01-01";
                $endtimeall = $radio."-12-31";
                $beginYear = strtotime($begintimeall);
                $endYear = strtotime($endtimeall);
                $maps['add_time'] = array(array('gt',$beginYear),array('lt',$endYear));
            }
        }

        $maps['pay_status'] = 1;
        $maps['type'] = 0;
        $maps['order_status'] = array('not in','3,5');

        $list = D("order")->where($maps)->order('order_id desc')->select();
        $orderid = formatArray($list,'order_id');

        $where['tp_order_goods.order_id'] = array('in',$orderid);
        $where['tp_order_goods.sku'] = $sku;

        $order_goods_all = D("order_goods")->join('tp_order on tp_order_goods.order_id=tp_order.order_id','left')->field('tp_order_goods.*,tp_order.order_sn')->where($where)->order('goods_id asc')->limit($start.','.$pages)->select();


        if($order_goods_all){

            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$order_goods_all);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }


    //销售排名

    public function sale_rank(){
        $store_id=$_REQUEST['store_id'];
        $day=$_REQUEST['daytype'];
       
        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }


        $start_time=strtotime($start_time);
        //$end_time=strtotime($end_time); 
         $end_time=strtotime($end_time)+24*3600-10; 
        if($start_time!=''&&$end_time!=''){

            if($start_time==$end_time){
                $end_time=$end_time+24*3600-10;
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }else{
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }

        }elseif($start_time!=''){
            $maps['add_time']=array('gt',$start_time);
        }elseif($end_time!=''){
            $maps['add_time']=array('lt',$end_time);
        }else{

            if($day==1){

                $daytime=strtotime(date("Y-m-d"));
                $daytime1=$daytime+24*3600-10;

                $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));

            }

            if($day==2){

                  $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
    
                  $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
                  $month=strtotime($BeginDate);
                  $month1=strtotime($endDate)+24*3600-10;
                  $maps['add_time']=array(array('gt',$month),array('lt',$month1));
            }

            if($day==3){

                $radio=date('Y');
                $begintimeall=$radio."-01-01";
                $endtimeall=$radio."-12-31";

                $beginYear=strtotime($begintimeall);
                $endYear=strtotime($endtimeall);

                $maps['add_time']=array(array('gt',$beginYear),array('lt',$endYear));

            }
        }

      
        $maps['pay_status']=1;
        $maps['type']=0;

        /**
        if(!empty($store_id)){
            $smaps['store_id']=$store_id;
            $admin=D("admin")->where($smaps)->select();
            $cids=formatArray($admin,'admin_id');
            $maps['cid']=array('in',$cids);

        }
        */

        $maps['store_id']=array('in',$store_id[0]);
        $maps['order_status']=array('not in','3,5');

        //$total_amount=D("order")->where($maps)->sum('order_amount');

        $list=D("order")->where($maps)->order('order_id desc')->group('cid')->select();

        //$renshu=count($list);

        if($list){

            $cid=formatArray($list,'cid');

            $where['admin_id']=array('in',$cid);

            $admin=D("admin")->where($where)->select();

            foreach ($admin as $key => $value) {
                $maps['cid']=$value['admin_id'];
                
                $total_amount=D("order")->where($maps)->sum('order_amount');
                //echo M()->getlastsql();

                $mpas1['store_id']=array('in',$value['store_id']);

                $store=D("store")->where($mpas1)->where('is_forbid=0')->find();

                $admin[$key]['total_amount']=$total_amount;
                $admin[$key]['store_id']=0;
                $admin[$key]['storename']=$store['store_name'];
            }

            foreach($admin as $arr2){
                $flag[]=$arr2["total_amount"];
            }
            array_multisort($flag, SORT_DESC, $admin);
        }
        
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$admin);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }

       
    }


    public function sale_ranks(){
        $store_id=$_REQUEST['store_id'];
        $day=$_REQUEST['daytype'];
       
        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }


        $start_time=strtotime($start_time);
        //$end_time=strtotime($end_time); 
         $end_time=strtotime($end_time)+24*3600-10; 
        if($start_time!=''&&$end_time!=''){

            if($start_time==$end_time){
                $end_time=$end_time+24*3600-10;
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }else{
                $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
            }

        }elseif($start_time!=''){
            $maps['add_time']=array('gt',$start_time);
        }elseif($end_time!=''){
            $maps['add_time']=array('lt',$end_time);
        }else{

            if($day==1){

                $daytime=strtotime(date("Y-m-d"));
                $daytime1=$daytime+24*3600-10;

                $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));

            }

            if($day==2){

                  $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
    
                  $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
                  $month=strtotime($BeginDate);
                  $month1=strtotime($endDate)+24*3600-10;
                  $maps['add_time']=array(array('gt',$month),array('lt',$month1));
            }

            if($day==3){

                $radio=date('Y');
                $begintimeall=$radio."-01-01";
                $endtimeall=$radio."-12-31";

                $beginYear=strtotime($begintimeall);
                $endYear=strtotime($endtimeall);

                $maps['add_time']=array(array('gt',$beginYear),array('lt',$endYear));

            }
        }

      
        $maps['pay_status']=1;
        $maps['type']=0;

        /**
        if(!empty($store_id)){
            $smaps['store_id']=$store_id;
            $admin=D("admin")->where($smaps)->select();
            $cids=formatArray($admin,'admin_id');
            $maps['cid']=array('in',$cids);

        }
        */

        $maps['store_id']=array('in',$store_id[0]);
        $maps['order_status']=array('not in','3,5');

        //$total_amount=D("order")->where($maps)->sum('order_amount');

        $list=D("order")->where($maps)->order('order_id desc')->group('cid')->select();

        //$renshu=count($list);

        if($list){

            $cid=formatArray($list,'cid');

            $where['admin_id']=array('in',$cid);

            $admin=D("admin")->where($where)->select();

            foreach ($admin as $key => $value) {
                $maps['cid']=$value['admin_id'];
                
                $total_amount=D("order")->where($maps)->sum('order_amount');
                //echo M()->getlastsql();

                $mpas1['store_id']=array('in',$value['store_id']);

                $store=D("store")->where($mpas1)->where('is_forbid=0')->find();

                $admin[$key]['total_amount']=$total_amount;
                $admin[$key]['store_id']=0;
                $admin[$key]['storename']=$store['store_name'];
            }

            foreach($admin as $arr2){
                $flag[]=$arr2["total_amount"];
            }
            array_multisort($flag, SORT_DESC, $admin);
        }
        
         if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$admin);
               $total_amount = 0;
                foreach($admin as $k=>$val){
                     $total_amount += $val["total_amount"];
            }
               $data['renshu']=count($admin);
               $data['total_amount']=$total_amount;
               $data['list']=$admin;
               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }

       
    }

    //商品销售查询
    public function search_sale(){
        $store_id=$_REQUEST['store_id'];
        $sku=$_REQUEST['sku'];
       
        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $start_time=strtotime($start_time);//开始时间
        $end_time=strtotime($end_time)+24*3600-1; //结束时间

        if($start_time!='0'&&$end_time!='0'){
            $maps['add_time']=array(array('gt',$start_time),array('lt',$end_time));
        }elseif($start_time!=''){
            $maps['add_time']=array('gt',$start_time);
        }elseif($end_time!=''){
            $maps['add_time']=array('lt',$end_time);
        }else{

        }
        $maps['pay_status']=1;
        $maps['type']=0;
       
/**
        if(!empty($store_id)){
            $smaps['store_id']=$store_id;
            $admin=D("admin")->where($smaps)->select();
            $cids=formatArray($admin,'admin_id');
            $maps['cid']=array('in',$cids);

        }
 

        $maps['store_id']=array('in',$store_id[0]);
        $maps['order_status']=array('not in','3,5');
        $list=D("order")->where($maps)->order('order_id desc')->select();

        $orderid=formatArray($list,'order_id');
        //echo $orderid;
      */
        ///$where['order_id']=array('in',$orderid);
        // $where['sku']=$sku;
        $where['sku']=$sku;
        $order_goods=D("order_goods")->where($where)->select();
        if($order_goods==false){
            $news = array('code' =>0 ,'msg'=>'未找到指定商品','data'=>null);
                echo json_encode($news,true);exit;
        }

        $orderid=formatArray($order_goods,'order_id');


        $maps['order_id']=array('in',$orderid);

        $maps['store_id']=array('in',$store_id[0]);
        $maps['order_status']=array('not in','3,5');


        $list=D("order")->where($maps)->order('order_id desc')->group('cid')->select();

        if($list){
            $cid=formatArray($list,'cid');

            $where1['admin_id']=array('in',$cid);
            $admin=D("admin")->where($where1)->select();

            foreach ($admin as $key => $value) {
                $maps['cid']=$value['admin_id'];
                // $total_amount=D("order")->where($maps)->sum('total_amount');
                $total_order=D("order")->where($maps)->select();

                $orderid1=formatArray($total_order,'order_id');
                $omaps['order_id']=array('in',$orderid1);
                $omaps['sku']=$sku;
                $total_counts=D("order_goods")->where($omaps)->sum('goods_num');    
                $total_amount=D("order_goods")->where($omaps)->sum('goods_num*goods_price');
                $mpas1['store_id']=array('in',$value['store_id']);
                $store=D("store")->where($mpas1)->where('is_forbid=0')->find();
                $admin[$key]['total_amount']=$total_amount;
                $admin[$key]['total_counts']=$total_counts;
                $admin[$key]['storename']=$store['store_name'];
            }

            foreach($admin as $arr2){
                $flag[]=$arr2["total_amount"];
            }
            array_multisort($flag, SORT_DESC, $admin);

        }



        $lists['goods_name']=$order_goods['0']['goods_name'];
        $lists['spec_key_name']=$order_goods['0']['spec_key_name'];

        if(!empty($admin)){
            $lists['sale_rank']=$admin;
        }else{
            $lists['sale_rank']=null;
        }
        
        
        if($admin){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$lists);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }


    }


    //门店列表
    public function store_list(){
        $store=D("store")->where('is_forbid=0')->select();

        if($store){
             $news = array('code' =>1,'msg'=>'获取成功','data'=>$store);
                        echo json_encode($news,true);exit;
        }else{

            $news = array('code' =>0,'msg'=>'获取失败','data'=>null);
                        echo json_encode($news,true);exit;
            

        }
    }
    //返货操作
    public function add_return(){

        $cid=$_REQUEST['cid'];
        $return_type=$_REQUEST['return_type'];
        $store_id=$_REQUEST['store_id'];
        $user_note=$_REQUEST['user_note'];
        $shipping_time=strtotime($_REQUEST['shipping_time']);
        $data=$_REQUEST['data'];
        
        $order_sn=date('YmdHis').rand(1000,9999);
        //$adata['user_id']=$uid;
        $adata['cid']=$cid;
        $adata['order_sn']=$order_sn;
        $adata['store_id']=$store_id[0];
        if(!empty($user_note)){
            $adata['user_note']=$user_note;
        }
        
        $adata['return_type']=$return_type;
        $adata['shipping_time']=$shipping_time;
       
        $adata['add_time']=time();

        $order=D("returns")->add($adata);

        if($order){

            $arr = json_decode($data, true);

            foreach ($arr as $key => $value) {
                
                $datas['goods_name']=$value['goods_name'];
                $datas['market_price']=$value['price']*$value['count'];
                $datas['goods_price']=$value['price'];
                $datas['member_goods_price']=$value['price'];
                $datas['goods_sn']=$value['goods_sn'];
                $datas['goods_id']=$value['goods_id'];
                $datas['sku']=$value['sku'];
                $datas['spec_key']=$value['key'];
                $datas['spec_key_name']=$value['key_name'];
                $datas['goods_num']=$value['count'];
               
                $datas['order_id']=$order;
                
                $goods=D("returns_goods")->add($datas);
              
            }

              $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
                echo json_encode($news,true);exit;

        }else{
                $news = array('code' =>0 ,'msg'=>'添加失败','data'=>null);
                echo json_encode($news,true);exit;

        }
    }
    //修改资料
    public function update_info(){
        $admin_id=$_REQUEST['cid'];
        if(!empty($_REQUEST['nickname'])){
            $data['nickname']=$_REQUEST['nickname'];
        }
        if(!empty($_REQUEST['sign_name'])){
            $data['sign_name']=$_REQUEST['sign_name'];
        }
        if(!empty($_REQUEST['birthday'])){
            $data['birthday']=strtotime($_REQUEST['birthday']);
        }
        if(!empty($_REQUEST['sex'])){
            $data['sex']=$_REQUEST['sex'];
        }
        $maps['admin_id']=$admin_id;
        $admin=D("admin")->where($maps)->save($data);
        //echo M()->getlastsql();

        if($admin){
             $news = array('code' =>1,'msg'=>'操作成功','data'=>null);
                        echo json_encode($news,true);exit;
        }else{

            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
                        echo json_encode($news,true);exit;
        

        }
    }
    //上传头像
    public function upload_face(){

        $uploadList = $this->uploadface($_FILES);
        //dump($uploadList );
        
        $data['img']= $uploadList['img']['urlpath'];
        $img = $uploadList['img']['urlpath'];
        if(!empty($img)){
             $url=C("http_urls");
            //$url="http://bsl.265nt.com";
            $url.=$img;
            $info['img']=$url;
        }
        $data['admin_id']=$_REQUEST['cid'];

        $admin=D("Admin")->save($data);
        //echo M()->getlastsql();
        if($admin){
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$info);
                        echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
                        echo json_encode($news,true);exit;

        }
    }



     public function uploadface(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     31457280000 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     UPLOAD_PATH.'/'; // 设置附件上传根目录
        $upload->savePath  =     'face/'; // 设置附件上传（子）目录
        $upload->saveName  =     'uniqid'; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功

            return $info;
        }
    }
    //用户自动统计
    public function user_sate(){
       
        
        $uid=$_REQUEST['memberid'];
        // $uid=18752;
        $maps['user_id']=$uid;
        $user=D("users")->where($maps)->find();
        $vmmp['uid']=$uid;
        $communication=D("communication")->where($vmmp)->order('create_time desc')->select();

       // // dump($communication);

       //  if($communication){
       //      $contact_time=$communication[0]['ctime'];
       //  }else{  
       //      $contact_time='无';
       //  }

        $where['pay_status']=1;
        $where['order_status']=array('not in','3,5');
        $where['user_id']=$uid;

        $total_pricecount = D("order")->where($where)->sum('goods_price');
        $total_count = D("order")->where($where)->count();

        
        $firstOrder=D("order")->where($where)->order('order_id asc')->find();
        $endOrder=D("order")->where($where)->order('order_id desc')->find();

        $maxScore = D("order")->where($where)->max('goods_price');

        if(empty($maxScore)){
            $maxScore=0;
        }

        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天

        $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
        $month=strtotime($BeginDate);
        $month1=strtotime($endDate)+24*3600-10;
        $maps['add_time']=array(array('gt',$month),array('lt',$month1));
        $maps['pay_status']=1;

        $monthcount = D("order")->where($maps)->sum('goods_price');

        if(empty($monthcount)){
            $monthcount=0;
        }

        $season = ceil((date('n'))/3);//当月是第几季度

        $jdstart= date('Y-m-d H:i:s', mktime(0, 0, 0,$season*3-3+1,1,date('Y')));
        $jdend= date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y')));

        $jdstart=strtotime($jdstart);
        $jdend=strtotime($jdend);
        $maps['add_time']=array(array('gt',$jdstart),array('lt',$jdend));
        $maps['pay_status']=1;

        $jdcount = D("order")->where($maps)->sum('goods_price');

        if(empty($jdcount)){
            $jdcount=0;
        }

        $qnstart = date('Y-01-01');
        $qnend = date('Y-12-31');
        

        $qnstart=strtotime($qnstart);
        $qnend=strtotime($qnend)+24*3600;
        
        $maps['add_time']=array(array('gt',$qnstart),array('lt',$qnend));
        $maps['pay_status']=1;

        $qncount = D("order")->where($maps)->sum('goods_price');

        if(empty($qncount)){
            $qncount=0;
        }
        
        $member['level']=$this->getCount($user['level']);
        $member['contact_time']=$contact_time;
        $member['pricecount']=$total_pricecount?$total_pricecount:0;
        $member['total_count']=$total_count?$total_count:0;
        $member['shou_price']=$firstOrder['goods_price']?$firstOrder['goods_price']:0;
        $member['max_price']=$maxScore?$maxScore:0;
        if(!empty($user['first_time'])){
             $member['shou_time']=date('Y-m-d',$user['first_time']);
        }else{
             $member['shou_time']=null;
        }

        if(!empty($user['end_time'])){
            $member['end_time']=date('Y-m-d',$user['end_time']);
        }else{
            $member['end_time']=null;
        }
        $member['monthcount']=$monthcount;
        $member['jdcount']=$jdcount;
        $member['qncount']=$qncount;
        $member['total_price']=$total_pricecount;
        $member['lianxi_count']=count($communication);
        $member['hudong_count']=0;

        //推荐参友人数统计
        $tuiArr['referrals_id'] = $uid;
        $tuijian_count = M('users')->where($tuiArr)->count();

        $member['tuijian_count']=$tuijian_count;
        //被谁推荐
        $member['beituijian_count']=0;
        $member['beituijian']=getName_users($user['referrals_id'])?getName_users($user['referrals_id']):'无';
        if($member){
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$member);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    //现金支付跳转页面
    public function pay_return(){
        $order_sn=$_REQUEST['order_sn'];
        $store_id=$_REQUEST['store_id'];
        $pay_name=$_REQUEST['pay_name'];
        $order_amount=$_REQUEST['order_amount'];

        //接收优惠券ID(顾客持有)
        $coupon_list_id=$_REQUEST['coupon_list_id'];

        $remark=$_REQUEST['remark'];

        $points=$_REQUEST['points'];
        $sign=$_REQUEST['sign'];

        $maps['order_sn']=$order_sn;

        $order=D("Order")->field('order_id,user_money,cid,user_id,cid,order_sn,pay_name,integral,total_amount,order_status,pay_status,order_note,pay_time,store_id')->where($maps)->find();

        if($order['order_status']==5){
            $news = array('code' =>0,'msg'=>'作废订单不能支付','data'=>null);
                        echo json_encode($news,true);exit;
        }elseif($order['order_status']==3){
            $news = array('code' =>0,'msg'=>'取消订单不能支付','data'=>null);
                        echo json_encode($news,true);exit;
        }elseif($order['pay_status']==1){
            $news = array('code' =>0,'msg'=>'已支付的订单不能支付！','data'=>null);
                        echo json_encode($news,true);exit;
        }
        
        $mapsss['user_id']=$order['user_id'];

        $users=D("Users")->where($mapsss)->find();

        if($users['first_time']==0){
            $data['is_first']=1;
        }

        $data['pay_status']=1;
        $data['order_status']=4;
        $data['shipping_status']=1;
        $data['shipping_time']=time()+10*60;
        $data['confirm_time']=time()+10*60;
        $data['pay_name']=$pay_name;
        if(!empty($remark)){
            $data['order_note']=$remark;
        }
       
        //$data['store_id']=$store_id[0];
        $data['order_amount']=$order_amount;
        if(!empty($points)){
            $data['integral']=$points; 
            $inc_type =  I('get.inc_type','basic');
            $config = tpCache($inc_type);
            $money_integral=$config["money_integral"];
            $score_integral=$config["score_integral"];

            $score_money=$money_integral/$score_integral;
            $integral_money=$score_money*$points; 
            $data['integral_money']=$integral_money;

        }
        
        //判断是否使用优惠券
        if(!empty($coupon_list_id)){
            //查询优惠券抵扣金额
            $cou = M('coupon_list')->where('id='.$coupon_list_id)->getField('cid');
            if ($cou) {
                $coupon = M('coupon')->where('id='.$cou)->field('id,money,use_num,name')->find();
                //修改优惠券使用次数
                $da1['id'] = $coupon['id'];
                $da1['use_num'] = $coupon['use_num']+1;
                $nnr = M('coupon')->save($da1);
                if ($nnr) {
                    //将该用户优惠券销毁
                    $da2['id'] = $coupon_list_id;
                    $da2['use_time'] = time();
                    $da2['order_id'] = $order['order_id'];
                    $rrt = M('coupon_list')->save($da2);
                }
                if ($nnr && $rrt) {
                    //将购物订单添加优惠券使用数据
                    $data['coupon_price'] = $coupon['money'];
                }
            }else{
                $news = array('code' =>0,'msg'=>'不存在的优惠券','data'=>null);
                echo json_encode($news,true);exit;
            }
        }


        $data['pay_time']=time();
        $where['order_sn']=$order_sn;
        $res=D("Order")->where($where)->save($data);
       
        if($res){

            //如果用户为会员则修改数据
            if ($users) {
                //添加服务顾问
                if(!$users['first_leader']){
                    $datass['first_leader']=$order['cid'];
                }

                //修改最后购买时间
                $datass['end_time']=time();
                D("Users")->where($mapsss)->save($datass);

                if(!empty($points)){
                    //抵扣分数减掉
                    accountLog($order['user_id'],0,$points,'积分抵扣',0,3,1,$order['order_id']); 
                }
                
                $mapss['level_id']=$users['level'];

                $userlevel=D("user_level")->where($mapss)->find();

                $ps=$userlevel['ps'];
                $score=$order_amount*$ps;
                $score=$score/100;

                $score=sprintf('%.2f', $score);

                //如果为生日当天 且满足多倍积分条件
                //查询生日提醒是否开通
                $search_birth['status'] = 1;
                $search_birth['integral'] = 1;
                $birth = M('brith_reminder')->where($search_birth)->find();

                if ($birth) {
                    //设定积分的倍数
                    $integral_point = $birth['integral_point'];
                    //查找符合条件的会员
                    $condition['is_del'] = 0;

                    $now_date = date("m-d",time());
                    $condition['_string'] = "unix_timestamp(concat('1970-',SUBSTRING_INDEX(birthdays,'-',-2))) = unix_timestamp('1970-".$now_date."')";
                    $birthday_users = M('users')->where($condition)->field('user_id')->select();
                    $birthday_users_arr = array();
                    foreach ($birthday_users as $kk => $va) {
                        $birthday_users_arr[] = $va['user_id'];
                    }
                    //判断当前用户ID是否属于数组$birthday_users_arr
                    if (in_array($mapsss['user_id'], $birthday_users_arr)) {
                        $score = $integral_point*$score;
                        $score=sprintf('%.2f', $score);
                    }
                }


                if($pay_name!='积分支付'){
                    accountLog($order['user_id'],0,$score,'购买商品添加积分',0,2,0,$order['order_id']); 
                }
            }

            if($order['user_id']>0){
                $mapss['user_id']=$order['user_id'];

                $usersss=D("Users")->where($mapss)->find();

                //获取会员等级
                $dengji = $this->getCount($usersss['level']);

                if($usersss['xingming']){
                    $name = $usersss['xingming'];
                }else{
                    $name = $usersss['nickname'];
                }

                $mobile=$usersss['mobile'];
                $pay_points1=$usersss['pay_points'];

                if($points==0.0){
                $content="尊敬的会员".$name."，您的晓芹会员卡于".date('Y-m-d H:i:s')."消费现金".$order_amount."元，产生积分".$score."，总积分".$pay_points1."。查询兑换积分，学习海参吃法，请关注公众号！“日食一参”的人越来越多，快加入我们吧…4006990605";

                }else{
                    if($pay_name!='积分支付'){
                        $content="尊敬的会员".$name."，您的晓芹会员卡于".date('Y-m-d H:i:s')."消费现金".$order_amount."元，消费积分".$points."，产生积分".$score."，总积分".$pay_points1."。查询兑换积分，学习海参吃法，请关注公众号！“日食一参”的人越来越多，快加入我们吧…4006990605";
                    }else{
                        $content="尊敬的".$dengji.$name."，此次兑换共使用".$points."积分，消费赠积分，好礼兑不停！关注公众号，看看您有多少积分吧…";
                    }

                }

                $res= sendsmss($mobile,$content);
            }
            
            //更新首单信息
            if($users['first_time']==0){
                update_user_levels($order['user_id']);
                //更新风格
                getTypes($order['user_id'],$order_amount,2);

                $datass122['first_time']=time();
                D("Users")->where($mapsss)->save($datass122);
            }else{
                update_user_level($order['user_id']);

                //更新风格
                getTypes($order['user_id'],$order_amount,3);
            }

            //库存处理
            $mapsss['order_id']=$order['order_id'];
            $goods=D("order_goods")->where($mapsss)->select();

            foreach ($goods as $keys => $value) {

                //修改库存(stock)
                $goods_id = $value['goods_id'];
                $goods_num = $value['goods_num'];
                $goods_name = $value['goods_name'];
                $resource_id = $store_id[0];

                $jjk = jskc_new($goods_id,$goods_num,$resource_id,4,1);

                //新增门店出库记录
                if ($jjk) {
                    //新增门店流水记录
                    $infofo = addWaterRecord($goods_id, $goods_num, $resource_id, 2);//出货类型 1 进货 2 销售 3 返货

                    //新增门店出库记录
                    $result_outRecord = store_stock_out($resource_id,$order_sn);
                    if ($result_outRecord) {
                        //新增门店出库记录详情表
                       store_stock_out_detail($result_outRecord,$goods_id,$goods_name,$goods_num);
                    }
                }

                if(!empty($value['sku'])){
                    $vvmaps['sku']=$value['sku'];
                    $vvmaps['store_id']=$store_id[0];
                    $list=D("store_data")->where($vvmaps)->find();
                    if($list==false){
                        $into_stock="-".$value['goods_num']."";
                        $datas['store_id']=$store_id[0];
                        $datas['goods_name']=$value['goods_name'];
                        $datas['goods_id']=$value['goods_id'];
                        $datas['goods_sn']=$value['goods_sn'];
                        $datas['goods_price']=$value['goods_price'];
                        $datas['spec_key']=$value['spec_key'];
                        $datas['spec_key_name']=$value['spec_key_name'];
                        $datas['sku']=$value['sku'];
                        $datas['into_stock']=$into_stock;
                        D("store_data")->add($datas);
                    }else{
                        $datass['into_stock']=array('exp',"into_stock-".$value['goods_num']."");
                        $spec_goods_price=D("store_data")->where($vvmaps)->save($datass);
                    }
                }   
            }

            $mapsss['admin_id']=$order['cid'];
            $admin=D("admin")->where($mapsss)->find();
            $mpass1['store_id']=$admin['store_id'];
            $store=D("store")->where($mpass1)->where('is_forbid=0')->find();

            $order['jingbanren']=$admin['name'];
            $order['storename']=$store['store_name'];
            $where['order_id']=$order['order_id'];
            $order_goods=D("order_goods")->field('goods_name,sku,goods_id,goods_num,spec_key_name,goods_price')->where($where)->select();
            foreach ($order_goods as $keyss => $value) {
                $gmapsss['goods_id']=$value['goods_id'];
                $goods=D("goods")->where($gmapsss)->find();

                $order_goods[$keyss]['spu']=$goods['spu'];
                $order_goods[$keyss]['app_points']=$goods['app_points'];

            }

            $order['goods']=$order_goods;
            $order['order_amount']=$order_amount;
            $order['score_money']=$score_money;
            $order['user_money']=$order['user_money'];

            //增加积分
            if ($score) {
                $order['addjifen'] = $score;
            }else{
                $order['addjifen'] = 0;
            }
            //积分余额
            if ($pay_points1) {
                $order['zongjifen'] = $pay_points1;
            }else{
                $order['addjifen'] = 0;
            }
            //会员等级
            if ($order['user_id']>0) {
                $order['dengji'] = $dengji;
            }
            
            //会员手机号
            if ($mobile) {
                $order['mobile'] = $mobile;
            }else{
                $order['mobile'] = '';
            }
            //添加备注
            if ($remark) {
                $order['order_note'] = $remark;
            }else{
                $order['order_note'] = '';
            }

            if($points==0){
                $points=0;
            }

            //获取后台设置的温馨提示(广告语)
            $adMessage = M('marked_words')->where('id=1')->getField('content');

            $order['points']=$points;
            $order['pay_name']=$pay_name;
            $order['adMessage']=$adMessage?$adMessage:'';//获取后台设置的温馨提示(广告语)
            $order['remark']=$remark?$remark:'';//备注
            //打印订单广告信息
            $inc_type =  I('get.inc_type','basic');
            $config = tpCache($inc_type);   
            $guanggao=$config["ad_work"];

            $coupon_money = $coupon['money'];//优惠券抵扣金钱
            $order['guanggao']=$guanggao;

            $order['coupon_money']=$coupon_money?$coupon_money:0;//优惠券抵扣金钱

            //新增沟通记录 (如果为会员购买)
            if ($order['user_id']) {
                $uid = $order['user_id'];

                $data12['uid'] = $uid;
                $data12['fuid'] = $order['cid'];
                $data12['store_id'] = $store_id[0];
                $data12['tel'] = getUserMobile($uid)?getUserMobile($uid):'';
                $data12['com_status'] = 1;
                $data12['type'] = 1;
                $data12['create_time'] = time();
                $data12['cTime'] = date('Y-m-d H:i',time());

                $communication=M("communication")->add($data12);
            }
            

            $news = array('code' =>1,'msg'=>'操作成功','data'=>$order);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
            echo json_encode($news,true);exit;

        }
    }

    //现金支付跳转页面
    public function pay_Areturn(){
        
        $order_sn=$_REQUEST['order_sn'];
        $store_id=$_REQUEST['store_id'];
        $pay_name=$_REQUEST['pay_name'];
        $order_amount=$_REQUEST['order_amount'];
        $remark=$_REQUEST['remark'];

        $points=$_REQUEST['points'];
        $timestamp=$_REQUEST['timestamp'];
        $sign=$_REQUEST['sign'];

        $maps['order_sn']=$order_sn;

        $order=D("send_points")->field('id,order_sn,user_id,goods_id,cid')->where($maps)->find();
        $keyword=$order['order_sn'];
        $keyword.=$order['cid'];
        $keyword.=$timestamp;
        $sign1=md5($keyword);

        // if($sign1!=$sign){
        //     $news = array('code' =>0,'msg'=>'非法操作，签名不对','data'=>null);
        //                 echo json_encode($news,true);exit;
        // }
        
        $mapsss['user_id']=$order['user_id'];

        $users=D("Users")->where($mapsss)->find();

        if($order){

            //修改订单状态
            $data['remark']=$remark;
            $wh['id'] = $order['id'];
            $rest = M('send_points')->where($wh)->save($data);

            $mapss['level_id']=$users['level'];
            if($order['user_id']>0){
                $mapss['user_id']=$order['user_id'];

                $usersss=D("Users")->where($mapss)->find();

                if($usersss['xingming']){
                    $name = $usersss['xingming'];
                }else{
                    $name = $usersss['nickname'];
                }
                $mobile=$usersss['mobile'];
                $pay_points1=$usersss['pay_points']-$points;

                $content="尊敬的会员".$name."，您的晓芹会员卡于".date('Y-m-d H:i:s')."消费积分".$points."。查询兑换积分，学习海参吃法，请关注公众号！“日食一参”的人越来越多，快加入我们吧…4006990605";
                        
                $res= sendsmss($mobile,$content);
            }

            //库存处理
            $mapsss['order_sn']=$order_sn;
            $goods=D("send_points")->where($mapsss)->find();
            $send_points_id['send_points_id'] = $goods['id'];
            $sendList = M('send_points_detail')->where($send_points_id)->select();
           
            /*foreach ($sendList as $keys => $value) {
                $goods_id=$value['goods_id'];
                $goods_num=$value['goods_num'];
                $resource_id=$store_id[0];
                $goods_name = $value['goods_name'];
                $jjk = jskc_new($goods_id,$goods_num,$resource_id,4,1);
                //新增门店出库记录
                if ($jjk) {
                    //新增门店流水记录
                    $infofo = addWaterRecord($goods_id, $goods_num, $resource_id, 2);//出货类型 1 进货 2 销售 3 返货
                    
                    //新增门店出库记录
                    $result_outRecord = store_stock_out($resource_id,$order_sn);
                    if ($result_outRecord) {
                        //新增门店出库记录详情表
                       store_stock_out_detail($result_outRecord,$goods_id,$goods_name,$goods_num);
                    }
                }
            }*/

            $mapsss['admin_id']=$order['cid'];
            $admin=D("admin")->where($mapsss)->find();
            $mpass1['store_id']=$admin['store_id'];
            $store=D("store")->where($mpass1)->where('is_forbid=0')->find();

            $order['jingbanren']=$admin['name'];
            $order['storename']=$store['store_name'];

            foreach ($sendList as $keyss => $value) {
                $gmapsss['goods_id']=$value['goods_id'];
                $goods=D("goods")->where($gmapsss)->find();
                $spec_goods_price = D("spec_goods_price")->where($gmapsss)->find();
                $sendList[$keyss]['sku']=$spec_goods_price['sku'];
                $sendList[$keyss]['spec_key_name']=$spec_goods_price['key_name'];
                $sendList[$keyss]['spu']=$goods['spu'];
                $sendList[$keyss]['app_points']=$goods['app_points'];

            }

            $order['goods']=$sendList;
            $order['order_amount']=$order_amount;
            $order['score_money']=$score_money;
            $order['user_money']=0;

            if($points==0){
                $points=0;
            }
            $order['points']=$points;
            $order['pay_name']=$pay_name;
            $order['remark']=$remark?$remark:'';//备注

            //获取后台设置的温馨提示(广告语)
            $adMessage = M('marked_words')->where('id=1')->getField('content');
            $order['adMessage']=$adMessage?$adMessage:'';//获取后台设置的温馨提示(广告语)
            
            //打印订单广告信息
            $inc_type =  I('get.inc_type','basic');
            $config = tpCache($inc_type);   
            $guanggao=$config["ad_work"];

            $order['guanggao']=$guanggao;
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$order);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
            echo json_encode($news,true);exit;

        }
    }

    //添加非会员
    public function addfeihuiyuan(){
        $nickname=$_REQUEST['nickname'];
        $first_leader=$_REQUEST['cid'];

        $data['mobile']=$_REQUEST['mobile'];

        $list=D("Users")->where($data)->find();

        if($list){

            $vdata['nickname']=$nickname;
            $vdata['first_leader']=$first_leader;
            $wheres['user_id']=$list['user_id'];

            D("users")->where($wheres)->save($vdata);
            $news = array('code' =>0,'msg'=>'该手机已经存在！','data'=>$list['user_id']);
            echo json_encode($news,true);exit;
            exit;
        }

        $data['first_leader']=$_REQUEST['cid'];
        $data['nickname']=$_REQUEST['nickname'];
        



        $data['user_type']=1;
        $data['reg_time']=time();

        $users=D("Users")->add($data);

        if($users){
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$users);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
    //购买记录
    public function buylog(){
        $user_id=$_REQUEST['uid'];
        $maps['user_id']=$user_id;
        $maps['pay_status']=1;
        $order=D("order")->where($maps)->order('order_id desc')->select();
        $orderids=formatArray($order,'order_id');

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;


        foreach ($order as $key => $value) {
            $maps1['order_id']=$value['order_id'];

            $book_goods=D("order_goods")->where($maps1)->select();
            foreach ($book_goods as $key1 => $val) {
                  $book_goods[$key1]['danwei']=getSpu($val['goods_id']);
            }

            if($book_goods==false){
                $order[$key]['goods']=array();
            }else{
                $order[$key]['goods']=$book_goods;
            }

            $name = M('admin')->where('admin_id='.$value['cid'])->getField('user_name');//服务顾问名称
            $store_name = M('store')->where('store_id='.$value['store_id'])->getField('store_name');//门店名称

            $order[$key]['name'] = $name;//获取服务服务名称
            $order[$key]['store_name'] = $store_name;//获取门店名称
        }

        if($order){
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$order);
            echo json_encode($news,true);exit;
        }else{

            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
            echo json_encode($news,true);exit;


        }
    }

    //购买记录
    public function atvlog(){
        $user_id=$_REQUEST['uid'];
        $maps['uid']=$user_id;

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;

        $order_goods=D("sign_up")->where($maps)->limit($start.','.$pages)->order('id desc')->select();
        if($order_goods){
            foreach($order_goods as $key=>$val){
                $maps['activity_id']=$val['activity_id'];
                $activity=D("activity")->where($maps)->find();
                $order_goods[$key]['title']=$activity['title'];
                $order_goods[$key]['start_day']=date('Y-m-d',$activity['start_day']);
                $order_goods[$key]['start_time']=$activity['start_time'];
                $order_goods[$key]['end_time']=$activity['end_time'];
                $order_goods[$key]['store_name']=$activity['store_name'];
            }
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$order_goods);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0,'msg'=>'操作失败','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
    //开发人员
    public function kaifalist(){
        $uid=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];

        //$store_id=implode(',', $store_id);

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        if(!empty($_REQUEST['nickname'])){
            $mpas['nickname|mobile']=array('like',"%".$_REQUEST['nickname']."%");
        }
        
        $pages="10";
        $start=($p-1)*$pages;

        if($uid==0){
            $smaps['store_id']=array('in',$store_id[0]);

            $admin=D("Admin")->where($smaps)->select();

            $adminid=formatArray($admin,'admin_id');

            $mpas['add_uid']=array('in',$adminid);
            $mpas['store_id']=array('in',$store_id[0]);

        }else{
             $mpas['add_uid']=$uid;
        }

        
        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));

        $start_time=strtotime($BeginDate);
       
        $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
        $end_time=strtotime($endDate)+24*3600*24-1;


        $mpas['reg_time']=array(array('gt',$start_time),array('lt',$end_time));

        $count=D("users")->where($mpas)->count();
        $user=D("users")->where($mpas)->order('user_id desc')->limit($start.','.$pages)->select();

        if($user==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }

        foreach ($user as $key => $value) {
            $user[$key]['dengji']=$this->getCount($value['level']);

            //获取会员图标
            $maps1['level_id']=$value['level'];
            $level=D("user_level")->where($maps1)->find();
            if($level){
                $url=C("http_urls");
               
                $url.=$level['vlogo'];
                $user[$key]['vlogo']=$url;

            }else{
                 $user[$key]['vlogo']=null;
            }
            $user[$key]['flag']=getUserFen($value['user_id']);
             $rs['store_id'] = $value['store_id'];
            $infos = M('store')->where($rs)->where('is_forbid=0')->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];
        }
         
        if($user){
            $data['count']=$count;
            $data['list']=$user;
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }  
    }


     //会员列表
    public function huiyuanlist(){
        $uid=$_REQUEST['uid'];

        $store_id=$_REQUEST['store_id'];
        //$store_id=replace($store_id);
        //$store_id=implode(',', $store_id);

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

         if(!empty($_REQUEST['nickname'])){
            $maps['nickname|mobile']=array('like',"%".$_REQUEST['nickname']."%");
        }
        
        $pages="10";
        $start=($p-1)*$pages;

        $maps['store_id']=array('in',$store_id[0]);
        $count=D("users")->where($maps)->count();
       
        $user=D("users")->where($maps)->order('user_id desc')->limit($start.','.$pages)->select();

        if($user==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }

        foreach ($user as $key => $value) {
            $user[$key]['dengji']=$this->getCount($value['level']);
            $user[$key]['flag']=getUserFen($value['user_id']);

            $rs['store_id'] = $value['store_id'];
            $infos = M('store')->where($rs)->where('is_forbid=0')->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];

            if($value['add_uid']==0){
                 $user[$key]['isdev'] = 1;
            }else{
                $user[$key]['isdev'] = 1;
            }
        }

        if($user){
               $data['count']=$count;
               $data['list']=$user;
               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }  
    }

    public  function checkGoods(){
        $store_id=$_REQUEST['store_id'];
        $sku=$_REQUEST['sku'];

        $maps['store_id']=$store_id;
        $maps['sku']=$sku;
        $store_data=D("store_data")->where($maps)->find();

        if($store_data){
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>null);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }

    }
    //验证手机号码
    public function checkphone(){
        $mobile=$_REQUEST['mobile'];
        $maps['mobile']=$mobile;

        $admin=D("Users")->where($maps)->find();

        if($admin){
            $admin['dengji']=$this->getCount($admin['level']);
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$admin);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }  
    }

    public function getL(){
        $inc_type =  I('get.inc_type','basic');
        $config = tpCache($inc_type);
        $money_integral=$config["money_integral"];
        $score_integral=$config["score_integral"];
        $score_money=$money_integral/$score_integral;
        echo $score_money;
    }
    //修改资料
    public function editInfo(){
        $user_id=$_REQUEST['user_id'];
        $nickname=$_REQUEST['nickname'];//姓名
        $birthdays=$_REQUEST['birthdays'];//生日
        $source=$_REQUEST['source'];//来源
        $purpose=$_REQUEST['purpose'];//购买用途
        $preferred_products=$_REQUEST['preferred_products'];//偏好产品
        $sex=$_REQUEST['sex'];//性别
        $style=$_REQUEST['style'];//个人爱好
        $shiyongrenqun=$_REQUEST['shiyongrenqun'];//食用人群
        $shiyongrenshu=$_REQUEST['shiyongrenshu'];//食用人数
        $shiyongliang=$_REQUEST['shiyongliang'];//食用量
        $eating_habits=$_REQUEST['eating_habits'];//习惯吃法
        $tonic_behavior=$_REQUEST['tonic_behavior'];//滋补习惯
        $is_follow_store=$_REQUEST['is_follow_store'];//是否关注门店

        $fazhifangshi=$_REQUEST['fazhifangshi'];
        $fenggeleixing=$_REQUEST['fenggeleixing'];//风格类型
        $invoice=$_REQUEST['invoice'];
        $invoice1=$_REQUEST['invoice1'];
        $invoice2=$_REQUEST['invoice2'];
        $remark=$_REQUEST['remark'];
        $remark_man=$_REQUEST['remark_man'];
        $age_group=$_REQUEST['age_group'];

        if(!empty($nickname)){
            $data['nickname']=$nickname;
        }

        if(!empty($age_group)){
            $data['age_group']=$age_group;
        }
        if(!empty($birthdays)){
            $data['birthdays']=$birthdays;
            $data['birthday']=strtotime($birthdays);
        }
        if(!empty($invoice)){
            $data['invoice']=$invoice;
           
        }
        if(!empty($source)){
            $data['source']=$source;
        }
        if(!empty($purpose)){
            $data['purpose']=$purpose;
        }
        if(!empty($remark_man)){
            $data['remark_man']=$remark_man;
        }
        if(!empty($preferred_products)){
            $data['preferred_products']=$preferred_products;
        }
        if(!empty($sex)){
            $data['sex']=$sex;
        }
        if(!empty($style)){
            $data['style']=$style;
        }

        if(!empty($shiyongrenqun)){
            $data['shiyongrenqun']=$shiyongrenqun;
        }
        if(!empty($shiyongrenshu)){
            $data['shiyongrenshu']=$shiyongrenshu;
        }
        if(!empty($shiyongliang)){
            $data['shiyongliang']=$shiyongliang;
        }
        if(!empty($eating_habits)){
            $data['eating_habits']=$eating_habits;
        }
        if(!empty($eating_habits)){
            $data['eating_habits']=$eating_habits;
        }

        if($is_follow_store==1){
            $data['is_follow_store']=1;
        }elseif($is_follow_store==0){
             $data['is_follow_store']=0;
        }

        if(!empty($tonic_behavior)){
            $data['tonic_behavior']=$tonic_behavior;
        }

        if(!empty($fazhifangshi)){
            $data['fazhifangshi']=$fazhifangshi;
        }
        if(!empty($fenggeleixing)){
            $data['fenggeleixing']=$fenggeleixing;
        }
        if(!empty($invoice)){
            $data['invoice']=$invoice;
        }
        if(!empty($invoice1)){
            $data['invoice1']=$invoice1;
        }
        if(!empty($invoice2)){
            $data['invoice2']=$invoice2;
        }

        if(!empty($remark)){
            $data['remark']=$remark;
        }
        if(!empty($remark1)){
            $data['remark1']=$remark1;
        }
        if(!empty($remark2)){
            $data['remark2']=$remark2;
        }
        $where['user_id']=$user_id;
        $user=D("Users")->where($where)->save($data);

        if($user){
            $news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
            echo json_encode($news,true);exit;
        }  
    }


    //添加会员
    public function  addMember(){
        $first_leader=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];
        $mobile=$_REQUEST['mobile'];
        $nickname=$_REQUEST['nickname'];
        $birthdays=$_REQUEST['birthdays'];
        $referrals_phone=$_REQUEST['referrals_phone'];//推荐人手机号

        //判断推荐人手机号是否存在  如果不存在  则不处理
        if ($referrals_phone) {
            $phone['mobile'] = $referrals_phone;
            $is_user = M('users')->where($phone)->find();
            if (!$is_user) {
                $news = array('code' =>0 ,'msg'=>'推荐人不存在！','data'=>null);
                echo json_encode($news,true);exit; 
            }else{
                $referrals_id = $is_user['user_id'];
            }
        }else{
            $referrals_id = 0;
            $referrals_phone = '';
        }
        

        $maps['mobile']=$mobile;

        $user=D("users")->where($maps)->find();

        if($user){            
            $news = array('code' =>0 ,'msg'=>'用户已存在！','data'=>$user['user_id']);
            echo json_encode($news,true);exit;
        }
        $data['store_id']=$store_id[0];
        if(empty($birthdays)){
            $data['birthdays']="0000-00-00";
        }else{
            $data['birthdays']=$birthdays;
        }
       
        $data['add_uid']=$first_leader;
        $data['mobile']=$mobile;
        $data['nickname']=$nickname;
        $data['password']=md5('123456');
        $data['reg_time']=time();
        $data['referrals_phone']=$referrals_phone;//推荐人手机号
        $data['referrals_id']=$referrals_id;//推荐人ID

        $res=D("users")->data($data)->add();
        if($res){
            accountLog($res,0,30,'注册成功+30积分',0,1); 

            $content="您的电子会员卡已激活，并送30积分，消费时请报手机号码，一边吃海参，一边赚积分！详询4006990605（晓芹海参）";
            $res11= sendsmss($mobile,$content);

            getTypes($res,0,1);
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>$res);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }
    //门店销售
    public function store_ranks(){
        $day=$_REQUEST['daytype'];
        $p=$_REQUEST['p'];
        $sort=$_REQUEST['sort'];
        $store_id=$_REQUEST['store_id'];
        $getStartDay=$_REQUEST['startDay'];//开始日期
        $getEndDay=$_REQUEST['endDay'];//结束日期
        $trueStartDay=$getStartDay.' 00:00:00';//开始日期 拼接时分秒
        $trueEndDay=$getEndDay.' 23:59:59';//结束日期 拼接时分秒
        if($day==1){

            $daytime=strtotime(date("Y-m-d"));
            $daytime1=$daytime+24*3600-10;

            $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));

        }elseif($day==2){
            $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
            $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
            $startDay = strtotime($trueStartDay); //获取开始日期时间戳
            $endDay = strtotime($trueEndDay); //获取结束日期时间戳
            $month=strtotime($BeginDate);
            $month1=strtotime($endDate)+24*3600-10;
            $maps['add_time']=array(array('gt',$startDay),array('lt',$endDay));
        }elseif($day==3){
            $radio=date('Y');
            $begintimeall=$radio."-01-01";
            $endtimeall=$radio."-12-31";
            $startDay = strtotime($trueStartDay); //获取开始日期时间戳
            $endDay = strtotime($trueEndDay); //获取结束日期时间戳
            $beginYear=strtotime($begintimeall);
            $endYear=strtotime($endtimeall);
            $maps['add_time']=array(array('gt',$startDay),array('lt',$endDay));
        }
        
        $pages="10";
        $start=($p-1)*$pages;

        $maps['order_status']=array('not in','3,5');

        $maps['pay_status']=1;
        $maps['store_id']=array('in',$store_id[0]);
    
        $total=D("order")->where($maps)->sum('order_amount');
        $counts=D("order")->where($maps)->count();

        $vamp['store_id']=array('in',$store_id[0]);
        $store=D("store")->where($vamp)->where('is_forbid=0')->select();

        if($store){
            foreach ($store as $key => $value) {
                $maps['store_id']=$value['store_id'];
                $total_amount=D("order")->where($maps)->order('order_id desc')->sum('order_amount');

                $count=D("order")->where($maps)->count();

                $store[$key]['count']=$count;
                if($total_amount==false){
                     $store[$key]['order_amount']=0;
                }else{
                    $store[$key]['order_amount']=$total_amount;
                }
            }

            foreach($store as $arr2){
                $flag[]=$arr2["order_amount"];
            }

            if($sort=='1'){
                array_multisort($flag, SORT_ASC, $store);
            }else{
               array_multisort($flag, SORT_DESC, $store); 
            }

        }

        if($store){
            $list['total_amount']=$total;
            $list['count']=$counts;
            $list['list']=$store;

            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }


    }


     //门店销售
    public function guwen_ranks(){
        $day=$_REQUEST['daytype'];
        $p=$_REQUEST['p'];
        $sort=$_REQUEST['sort'];
        $store_id=$_REQUEST['store_id'];
        $getStartDay=$_REQUEST['startDay'];//开始日期
        $getEndDay=$_REQUEST['endDay'];//结束日期
        $trueStartDay=$getStartDay.' 00:00:00';//开始日期 拼接时分秒
        $trueEndDay=$getEndDay.' 23:59:59';//结束日期 拼接时分秒

        if($day==1){
            $daytime=strtotime(date("Y-m-d"));
            $daytime1=$daytime+24*3600-10;
            $maps['add_time']=array(array('gt',$daytime),array('lt',$daytime1));
        }elseif($day==2){
            $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
            $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
            $startDay = strtotime($trueStartDay); //获取开始日期时间戳
            $endDay = strtotime($trueEndDay); //获取结束日期时间戳
            $month=strtotime($BeginDate);
            $month1=strtotime($endDate)+24*3600-10;
            $maps['add_time']=array(array('gt',$startDay),array('lt',$endDay));
        }elseif($day==3){
            $radio=date('Y');
            $begintimeall=$radio."-01-01";
            $endtimeall=$radio."-12-31";
            $beginYear=strtotime($begintimeall);
            $endYear=strtotime($endtimeall);
            $startDay = strtotime($trueStartDay); //获取开始日期时间戳
            $endDay = strtotime($trueEndDay); //获取结束日期时间戳
            $maps['add_time']=array(array('gt',$startDay),array('lt',$endDay));
        }
        
        $maps['order_status']=array('not in','3,5');

        $maps['pay_status']=1;
        // $maps['store_id']=$store_id;
        $maps['store_id']=array('in',$store_id[0]);
    
        $total=D("order")->where($maps)->sum('order_amount');
        $counts=D("order")->where($maps)->count();
        $vmap['store_id']=array('in',$store_id[0]);
        $store=D("admin")->where($vmap)->select();
        if($store){

            foreach ($store as $key => $value) {
                $maps['cid']=$value['admin_id'];
                $total_amount=D("order")->where($maps)->order('order_id desc')->sum('order_amount');
                $count=D("order")->where($maps)->count();
                $store[$key]['count']=$count;
                if($total_amount==false){
                    $store[$key]['order_amount']=0;
                }else{
                    $store[$key]['order_amount']=$total_amount;
                }
                $store[$key]['store_id']=0;
            }

            foreach($store as $arr2){
                $flag[]=$arr2["order_amount"];
            }

            if($sort=='1'){
                array_multisort($flag, SORT_ASC, $store);
            }else{
               array_multisort($flag, SORT_DESC, $store); 
            }
        }

        if($store){
            $list['total_amount']=$total;
            $list['count']=$counts;
            $list['list']=$store;
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }



    //会员列表2
    public function huiyuanlists(){

        $uid=$_REQUEST['uid'];
        $store_id=$_REQUEST['store_id'];

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        $maps['nickname|mobile']=array('like',"%".$_REQUEST['nickname']."%");
        
        $pages="10";
        $start=($p-1)*$pages;


        //会员等级
        $level=$_REQUEST['level'];
        //风格
        $fengge_id=$_REQUEST['fengge_id'];

        $age_group=$_REQUEST['age_group'];
        $purpose=$_REQUEST['purpose'];
        $preferred_products=$_REQUEST['preferred_products'];
        $fazhifangshi=$_REQUEST['fazhifangshi'];
        $is_weixin=$_REQUEST['is_weixin'];
        $is_follow_store=$_REQUEST['is_follow_store'];
        $tonic_behavior=$_REQUEST['tonic_behavior'];
        $remark=$_REQUEST['remark'];
        $remark_man=$_REQUEST['remark_man'];
        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];
        $max_money=$_REQUEST['max_money'];
        $min_money=$_REQUEST['min_money'];

        if(!empty($level)){
            $name.=$level;
            $name.='会员';
            $maps1['level_name']=$name;
            $lists=D("user_level")->where($maps1)->find();
            if($lists){
                $maps['level']=$lists['level_id'];
            }
        }

        if(!empty($fengge_id)){
            $fengge_id=$this->getFengId($fengge_id);
            $maps['fengge_id']=$fengge_id;
        }

        if(!empty($age_group)){
            $maps['age_group']=$age_group;
        }
        if(!empty($purpose)){
            $maps['purpose']=array('like','%'.$purpose.'%');
        }

        if(!empty($remark)){
            $maps['remark']=array('like','%'.$remark.'%');
        }

        if(!empty($remark_man)){
            $maps['remark_man']=array('like','%'.$remark_man.'%');
        }

        if(!empty($tonic_behavior)){
            $maps['tonic_behavior']=array('like','%'.$tonic_behavior.'%');
        }

        if(!empty($preferred_products)){
            $maps['preferred_products']=array('like','%'.$preferred_products.'%');
        }

        if(!empty($fazhifangshi)){
            $maps['fazhifangshi']=$fazhifangshi;
        }

        if($is_weixin=='是'){
            $maps['openid1|openid2']=array('neq','');
        }

        if($is_follow_store=='是'){
            $maps['is_follow_store']=1;
        }elseif($is_follow_store=='否'){
            $maps['is_follow_store']=0;
        }

        if($start_time!=0&&$end_time!=0){
            $start_time=strtotime($start_time);
            $end_time=strtotime($end_time)+24*3600-10;             
            $maps['reg_time']=array(array('gt',$start_time),array('lt',$end_time));     
        }elseif($start_time!=0){
            $start_time=strtotime($start_time);
            $maps['reg_time']=array('gt',$start_time);
        }elseif($end_time!=0){
             $end_time=strtotime($end_time)+24*3600-10; 
            $maps['reg_time']=array('lt',$end_time);
        }else{
        }

        if($max_money>0&&$min_money>0){         
            $maps['user_money']=array(array('gt',$min_money),array('lt',$max_money));     
        }elseif($min_money>0){
            $maps['user_money']=array('gt',$min_money);
        }elseif($max_money>0){
            $maps['user_money']=array('lt',$max_money);
        }else{
        }

        $maps['store_id']=array('in',$store_id[0]);

        $count=D("users")->where($maps)->count();
       
        $user=D("users")->where($maps)->order('user_id desc')->limit($start.','.$pages)->select();

        if($user==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }

        foreach ($user as $key => $value) {
            $user[$key]['dengji']=$this->getCount($value['level']);

            $maps1['level_id']=$value['level'];

            $level=D("user_level")->where($maps1)->find();
            if($level){
                $url=C("http_urls");
               
                $url.=$level['vlogo'];
                $user[$key]['vlogo']=$url;

            }else{
                 $user[$key]['vlogo']=null;
            }

            $user[$key]['flag']=getUserFen($value['user_id']);

            $rs['store_id'] = $value['store_id'];
            $infos = M('store')->where($rs)->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];

            if($value['add_uid']==0){
                 $user[$key]['isdev'] = 1;
            }else{
                $user[$key]['isdev'] = 1;
            }
        }
        
        if($user){
            $data['count']=$count;
            $data['list']=$user;
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        } 

    }  
    public function getTradingArea(){
        $maps['status']=1;
        $list=D("trading_area")->where($maps)->select();
        if($list){
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        } 


    }
    public function getFengId($value){

        switch ($value){

            case "初始":
            return 0;
            break;
            case "潜在":
            return 2;
            break;
            case "新手":
            return 3;
            break;
            case "活跃":
            return 4;
            break;
            case "忠诚":
            return 5;
            break;
            case "游离":
            return 7;
            break;
            case  "沉寂":
            return 8;
            break;
            case "回归":
            return 10;
            break;
            case "老友":
            return 11;
            break;
            case "复购":
            return 12;
            break;
            case "初始":
            return 1;
            break;     
        }
    }
    public function search_tree(){
        $id = $_REQUEST['id'];
        $level = $_REQUEST['level'];
       
        if(empty($id)&&empty($level)){
           
            $maps['role_id'] = 2;
            $info= D('admin')->where($maps)->select();
            foreach ($info as $key => $value) {
                 $infos[$key]['id'] = $value['admin_id'];
                
                 $infos[$key]['title'] = $value['name'];
                 $infos[$key]['level'] = 1;
                 $infos[$key]['pid'] = 0;
            }
          
           
        }elseif ($id&&$level==1){
            $maps['admin_id'] = $id;
            $rs = D('admin')->where($maps)->find();
            if($rs){
                $map['store_id']= array('in', $rs['store_id']);
                $info = D('store')->where($map)->select();
                foreach ($info as $key => $value) {
                    $infos[$key]['id'] = $value['store_id'];
                    $infos[$key]['title'] = $value['store_name'];
                    $infos[$key]['level'] = 2;
                    $infos[$key]['pid'] = $id;
                }
            }


        }elseif($id&&$level==2){
            $maps['store_id'] = $id;
            $maps['role_id'] = 5;
            $r =D('admin')->where($maps)->select();

            foreach ($r as $key => $value) {
                $infos[$key]['id'] =$value['admin_id'];
                $infos[$key]['title'] = $value['name'];
                $infos[$key]['level'] = 3;
                $infos[$key]['pid'] = $id;
            }
    }

    if($infos){
        $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$infos);
        echo json_encode($news,true);exit;
    }else{
        $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
        echo json_encode($news,true);exit;
    } 

  
    }
    //3级联动会员搜索
    public  function search_userlist(){

        $search_id=$_REQUEST['search_id'];
        $level_num=$_REQUEST['level_num'];

        if(!empty($search_id)&&!empty($level_num)){
            if($level_num==1){
                //通过总监ID去查看门店ID
                $ww['admin_id'] = $search_id;
                $rs = D('admin')->where($ww)->find();
                $maps['store_id']=array('in',$rs['store_id']);
            }elseif($level_num==2){
                //门店级别的时候id就是门店ID
                $maps['store_id']=array('in',$search_id);
            }elseif($level_num==3){
                //服务顾问ID
                $maps['first_leader']=$search_id;
            }
            
        }
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
         if(!empty($_REQUEST['nickname'])){
            $maps['nickname|mobile']=array('like',"%".$_REQUEST['nickname']."%");
        }        
        $pages="10";
        $start=($p-1)*$pages;
        //会员等级
        $level=$_REQUEST['level'];
        //风格
        $fengge_id=$_REQUEST['fengge_id'];
        $age_group=$_REQUEST['age_group'];
        $purpose=$_REQUEST['purpose'];
        $preferred_products=$_REQUEST['preferred_products'];
        $fazhifangshi=$_REQUEST['fazhifangshi'];
        $is_weixin=$_REQUEST['is_weixin'];
        $is_follow_store=$_REQUEST['is_follow_store'];
        $tonic_behavior=$_REQUEST['tonic_behavior'];
        $remark=$_REQUEST['remark'];
        $remark_man=$_REQUEST['remark_man'];
        $start_time=$_REQUEST['start_time'];
        $end_time=$_REQUEST['end_time'];
        $max_integral=$_REQUEST['max_integral'];//积分搜索条件
        $min_integral=$_REQUEST['min_integral'];//积分搜索条件
        $birthdays_start=$_REQUEST['birthdays_start'];//生日搜索条件
        $birthdays_end=$_REQUEST['birthdays_end'];//生日搜索条件

        if(!empty($level)){
            $name.=$level;
            $name.='会员';
            $maps1['level_name']=$name;
            $lists=D("user_level")->where($maps1)->find();
            if($lists){
                $maps['level']=$lists['level_id'];
            }
        }

        if(!empty($fengge_id)){
            $fengge_id=$this->getFengId($fengge_id);
            $maps['fengge_id']=$fengge_id;
        }

        if(!empty($age_group)){
            $maps['age_group']=$age_group;
        }
        if(!empty($purpose)){
            $maps['purpose']=array('like','%'.$purpose.'%');
        }

        if(!empty($remark)){
            $maps['remark']=array('like','%'.$remark.'%');
        }

        if(!empty($remark_man)){
            $maps['remark_man']=array('like','%'.$remark_man.'%');
        }

        if(!empty($tonic_behavior)){
            $maps['tonic_behavior']=array('like','%'.$tonic_behavior.'%');
        }

        if(!empty($preferred_products)){
            $maps['preferred_products']=array('like','%'.$preferred_products.'%');
        }
        if(!empty($fazhifangshi)){
            $maps['fazhifangshi']=$fazhifangshi;
        }

        if($is_weixin=='是'){
            $maps['openid1|openid2']=array('neq','');
        }

        if($is_follow_store=='是'){
            $maps['is_follow_store']=1;
        }elseif($is_follow_store=='否'){
            $maps['is_follow_store']=0;
        }

        if($start_time!=0&&$end_time!=0){
            $start_time=strtotime($start_time);
            $end_time=strtotime($end_time)+24*3600-10;             
            $maps['reg_time']=array(array('gt',$start_time),array('lt',$end_time));     
        }elseif($start_time!=0){
            $start_time=strtotime($start_time);
            $maps['reg_time']=array('gt',$start_time);
        }elseif($end_time!=0){
             $end_time=strtotime($end_time)+24*3600-10; 
            $maps['reg_time']=array('lt',$end_time);
        }else{
        }

        if($max_integral>0&&$min_integral>0){  
            $maps['pay_points']=array(array('gt',$min_integral),array('lt',$max_integral));     
        }elseif($min_integral>0){
            $maps['pay_points']=array('gt',$min_integral);
        }elseif($max_integral>0){
            $maps['pay_points']=array('lt',$max_integral);
        }else{
        }


        // 生日搜索条件
        if(!empty($birthdays_start) &&!empty($birthdays_end) ){
            $maps['_string'] = "unix_timestamp(concat('1970-',SUBSTRING_INDEX(birthdays,'-',-2))) between unix_timestamp('1970-".$birthdays_start."') and unix_timestamp('1970-".$birthdays_end."')";
        }


        $maps['is_del']=0;//逻辑删除的数据不显示
        $count=D("users")->where($maps)->count();      
        $user=D("users")->where($maps)->order('reg_time desc')->limit($start.','.$pages)->select();
        if($user==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        foreach ($user as $key => $value) {
            $user[$key]['dengji']=$this->getCount($value['level']);

            $_maps1['level_id']=$value['level'];

            $levels=D("user_level")->where($_maps1)->find();
            //获取会员等级图标
            if($levels){
                $url=C("http_urls");         
                $url.=$levels['vlogo'];
                $user[$key]['vlogo']=$url;

            }else{
                $user[$key]['vlogo']=null;
            }
            $user[$key]['flag']=getUserFen($value['user_id']);
            $rs['store_id'] = $value['store_id'];
            $infos = M('store')->where($rs)->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];
            if($value['add_uid']==0){
                 $user[$key]['isdev'] = 1;
            }else{
                $user[$key]['isdev'] = 1;
            }
        }

        if($user){
            $data['count']=$count;
            $data['tree']=$info;
            $data['list']=$user;
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        } 

    }

    public function search_mobile(){
        if(I('mobile')!=""){
            $mobile=I('mobile');
            $uid = $_REQUEST['user_id'];//获取用户ID 
            if(empty($_REQUEST['p'])){
                $p=1;
            }else{
                $p=$_REQUEST['p'];
            }
            $pages="10";
            $start=($p-1)*$pages;
            // 搜索条件不为11位手机号
            if (!preg_match('/^1[34578]\d{9}$/',$mobile)) { 
                $res = M('users')->field('user_id')->where('first_leader='.$uid)->select();
                $user_idArr = array();
                foreach ($res as $val) {
                    $user_idArr[] = $val['user_id'];
                }
                if ($res) {
                    $maps['user_id'] = array('in',$user_idArr);
                }else{
                    $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);          
                    echo json_encode($news,true);exit; 
                }
            }
            $maps['mobile'] = array('like',"%".$mobile."%");
            $maps['is_del'] = 0;
            $info = D('users')->where($maps)->limit("$start,10")->select();
            foreach ($info as $key => $value) {
               $r['store_id'] = $value['store_id'];
               $where = D('store')->where($r)->find();
               $info[$key]['shop_no'] = $where['shop_no'];
                $maps1['level_id']=$value['level'];
            $level=D("user_level")->where($maps1)->find();
            if($level){
                $url=C("http_urls");               
                $url.=$level['vlogo'];
                $info[$key]['vlogo']=$url;
                }else{
                 $info[$key]['vlogo']=null;
                }
            }
        }
        if($info){
                $data['count']=$count;
                $data['list']=$info;        
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
                echo json_encode($news,true);exit;              
        }else{
                $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);          
                echo json_encode($news,true);exit; 
        }
    }
    public function getZj(){
        $maps['role_id'] = 2;
        $info = D('admin')->where($maps)->select();
         if($info){   
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$info);
            echo json_encode($news,true);exit;              
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);          
            echo json_encode($news,true);exit; 
        }
    }


    // 查看发票详情
    public function readFaPiao(){
        $user_id = $_REQUEST['user_id'];//用户ID
        //获取用户名
        $user_name = M('users')->where('user_id='.$user_id)->getField('nickname');
        $result = M('bill')->where('user_id='.$user_id)->select();
        if($result){
            $news = array('code' =>1 ,'msg'=>'获取成功','user_name'=>$user_name,'data'=>$result);          
            echo json_encode($news,true);exit; 
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','user_name'=>$user_name,'data'=>null);          
            echo json_encode($news,true);exit; 
        }
    }

    // 删除发票详情
    public function delFaPiao(){
        $faPiao_id = $_REQUEST['faPiao_id'];//发票ID
        $res = M('bill')->where('faPiao_id='.$faPiao_id)->delete();
        if($res){
            $news = array('code' =>1 ,'msg'=>'删除成功','data'=>$result);          
            echo json_encode($news,true);exit; 
        }else{
            $news = array('code' =>1 ,'msg'=>'删除失败！','data'=>null);          
            echo json_encode($news,true);exit; 
        }
    }

    // 添加发票| 修改发票
    public function addFaPiao(){
        $user_id = $_REQUEST['user_id'];//用户ID
        $faPiao_style = $_REQUEST['faPiao_style'];//发票类型 0 个人  1 单位
        $faPiao_name = $_REQUEST['faPiao_name'];//发票名称
        $faPiao_shuihao = $_REQUEST['faPiao_shuihao'];//发票税号
        $faPiao_adress = $_REQUEST['faPiao_adress'];//单位地址
        $link_phone = $_REQUEST['link_phone'];//联系人电话
        $bank_deposit = $_REQUEST['bank_deposit'];//开户银行
        $bank_num = $_REQUEST['bank_num'];//开户银行账户

        $faPiao_id = $_REQUEST['faPiao_id'];//发票ID
        if ($faPiao_id) {
            $data['faPiao_style'] = $faPiao_style;
            if(!empty($user_id)){
                $data['user_id']=$user_id;
            }
            if(!empty($faPiao_name)){
                $data['faPiao_name']=$faPiao_name;
            }
            if(!empty($faPiao_shuihao)){
                $data['faPiao_shuihao']=$faPiao_shuihao;
            }
            if(!empty($faPiao_adress)){
                $data['faPiao_adress']=$faPiao_adress;
            }
            if(!empty($link_phone)){
                $data['link_phone']=$link_phone;
            }
            if(!empty($bank_deposit)){
                $data['bank_deposit']=$bank_deposit;
            }
            if(!empty($bank_num)){
                $data['bank_num']=$bank_num;
            }

            $where['faPiao_id']=$faPiao_id;
            $user=D("bill")->where($where)->save($data);
            if($user){
                $news = array('code' =>1 ,'msg'=>'操作成功','data'=>null);          
                echo json_encode($news,true);exit; 
            }else{
                $news = array('code' =>1 ,'msg'=>'操作失败','data'=>null);          
                echo json_encode($news,true);exit; 
            }
        }
        $data['faPiao_style'] = $faPiao_style;
        if(!empty($user_id)){
            $data['user_id']=$user_id;
        }
        if(!empty($faPiao_name)){
            $data['faPiao_name']=$faPiao_name;
        }
        if(!empty($faPiao_shuihao)){
            $data['faPiao_shuihao']=$faPiao_shuihao;
        }
        if(!empty($faPiao_adress)){
            $data['faPiao_adress']=$faPiao_adress;
        }
        if(!empty($link_phone)){
            $data['link_phone']=$link_phone;
        }
        if(!empty($bank_deposit)){
            $data['bank_deposit']=$bank_deposit;
        }
        if(!empty($bank_num)){
            $data['bank_num']=$bank_num;
        }

        $res=D("bill")->data($data)->add();
        if($res){
            $news = array('code' =>1 ,'msg'=>'新增成功','data'=>$res);          
            echo json_encode($news,true);exit; 
        }else{
            $news = array('code' =>1 ,'msg'=>'新增失败！','data'=>null);          
            echo json_encode($news,true);exit; 
        }
    }

    // 修改
    public function editFaPiao(){

        $faPiao_id = $_REQUEST['faPiao_id'];//发票ID
        $faPiao_style = $_REQUEST['faPiao_style'];//发票类型 0 个人  1 单位
        $faPiao_name = $_REQUEST['faPiao_name'];//发票名称
        $faPiao_shuihao = $_REQUEST['faPiao_shuihao'];//发票税号
        $faPiao_adress = $_REQUEST['faPiao_adress'];//单位地址
        $link_phone = $_REQUEST['link_phone'];//联系人电话
        $bank_deposit = $_REQUEST['bank_deposit'];//开户银行
        $bank_num = $_REQUEST['bank_num'];//开户银行账户

        $data['faPiao_style'] = $faPiao_style;
        if(!empty($user_id)){
            $data['user_id']=$user_id;
        }
        if(!empty($faPiao_name)){
            $data['faPiao_name']=$faPiao_name;
        }
        if(!empty($faPiao_shuihao)){
            $data['faPiao_shuihao']=$faPiao_shuihao;
        }
        if(!empty($faPiao_adress)){
            $data['faPiao_adress']=$faPiao_adress;
        }
        if(!empty($link_phone)){
            $data['link_phone']=$link_phone;
        }
        if(!empty($bank_deposit)){
            $data['bank_deposit']=$bank_deposit;
        }
        if(!empty($bank_num)){
            $data['bank_num']=$bank_num;
        }

        $where['faPiao_id']=$faPiao_id;
        $user=D("bill")->where($where)->save($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'操作成功','data'=>null);          
            echo json_encode($news,true);exit; 
        }else{
            $news = array('code' =>1 ,'msg'=>'操作失败','data'=>null);          
            echo json_encode($news,true);exit; 
        }
    }

    // 销售流水开发票功能
    public function doInvoice(){
        $bill_id = $_REQUEST['fapiao_id'];//发票 ID
        $order_id = $_REQUEST['order_id'];//订单 ID
        $user_id = $_REQUEST['user_id'];//用户ID
        $c_id = $_REQUEST['c_id'];//开发票人ID
        $store_id = $_REQUEST['store_id'];//门店ID
        $where['faPiao_id'] = $bill_id;
        $result = M('bill')->where($where)->find();
        $data['fapiao_style'] = $result['fapiao_style'];
        $data['fapiao_name'] = $result['fapiao_name'];
        $data['fapiao_shuihao'] = $result['fapiao_shuihao'];
        $data['fapiao_adress'] = $result['fapiao_adress'];
        $data['link_phone'] = $result['link_phone'];
        $data['bank_deposit'] = $result['bank_deposit'];
        $data['bank_num'] = $result['bank_num'];
        $data['order_id'] = $order_id;
        $data['user_id'] = $user_id;
        $data['c_id'] = $c_id;
        $data['bill_id'] = $bill_id;
        $data['store_id'] = $store_id;
        $result = M('invoice')->add($data);
        if ($result) {
            $news = array('code' =>1 ,'msg'=>'开发票成功!','data'=>null);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'开发票失败!','data'=>null);
            echo json_encode($news,true);exit;
        }
    }



    // 积分兑换商品列表
    public function wxGoodsList(){
        $store_id = $_REQUEST['store_id'];//门店ID
        $admin_id = $_REQUEST['admin_id'];//经办人ID

        $uid = $_REQUEST['uid'];//筛选某个人的积分兑换记录

        // $role_id = $_REQUEST['role_id'];//权限ID
        $is_tiling = $_REQUEST['is_tiling'];//订单状态 0 不限 1 未提领 2 已提领
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        $pages="10";
        $start=($p-1)*$pages;
        if (!empty($store_id)) {
            $mpas['store_id']=array('in',$store_id[0]);
        }
        // 筛选条件  订单状态 0 不限 1 未提领 2 已提领
        if (!empty($is_tiling)) {
            if ($is_tiling==1) {
                // $mpas['is_tiling']=0;
                $mpas['_string'] = '(type=1 and is_tiling=0) OR (type=2 and status=1)';
            }else{
                // $mpas['is_tiling']=1;
                $mpas['_string'] = '(type=1 and is_tiling=1) OR (type=2 and status=2) OR (type=2 and status=3)';
            }
        }
        // $mpas['pay_status'] = 1;//1支付完成 0 未完成支付 

        if ($uid) {
           $mpas['user_id'] = $uid;//筛选属于某个人的积分兑换记录(查看会员信息---查看积分兑换记录使用)
        }

        $res = D('send_points')->where($mpas)->order('create_time desc')->limit($start.','.$pages)->select();
        $list = array();
        $arrlist = array();
        $smArr = array();
        $newArr = array();
        foreach ($res as $key => $val) {

            $store_name = D('store')->where('store_id='.$val['store_id'])->getField('store_name');

            //如果为app端兑换 则查询详情表 如果为微信兑换则用当前数据
            //0 app端积分兑换 1 微信端积分兑换
            if (!$val['source']) {
                $send_points_id['send_points_id'] = $val['id'];
                $sendList = M('send_points_detail')->where($send_points_id)->select();
                foreach ($sendList as $key1 => $value) {
                    if ($value['goods_id']) {
                        $goods_sn = D('goods')->where('goods_id='.$value['goods_id'])->getField('goods_sn');
                        //app端积分兑换
                        $app_points = D('goods')->where('goods_id='.$value['goods_id'])->getField('app_points');
                    }
                    // $smArr['pay_points'] = $value['points'];
                    $smArr['pay_points'] = $app_points;
                    $smArr['goods_name'] = $value['goods_name'];
                    $smArr['goods_sn'] = $goods_sn;//商品编号
                    $smArr['count'] = $value['goods_num'];//数量
                    if ($value['goods_id']) {
                        $smArr['sku'] = getSku($value['goods_id']);//sku
                    }
                    array_push($newArr, $smArr);
                    $smArr = array();
                }
            }else{
                if ($val['goods_id']) {
                    $goods_sn = D('goods')->where('goods_id='.$val['goods_id'])->getField('goods_sn');
                    //微信端积分兑换
                    $goods_points = D('goods')->where('goods_id='.$val['goods_id'])->getField('goods_points');
                }
                // $smArr['pay_points'] = $val['pay_points'];
                $smArr['pay_points'] = $goods_points;
                $smArr['goods_name'] = $val['goods_name'];
                $smArr['goods_sn'] = $goods_sn;//商品编号
                $smArr['count'] = $val['goods_num'];//数量
                if ($value['goods_id']) {
                    $smArr['sku'] = getSku($val['goods_id']);//sku
                }
                array_push($newArr, $smArr);
                $smArr = array();
            }
            $arrlist['mobile'] = $val['mobile'];
            $arrlist['consignee'] = $val['consignee'];
            $arrlist['create_time'] = $val['create_time'];//创建时间
            $arrlist['update_time'] = $val['update_time'];//提领时间
            $arrlist['is_tiling'] = $val['is_tiling'];
            $arrlist['status'] = $val['status'];
            $arrlist['type'] = $val['type'];
            $arrlist['store_name'] = $store_name;
            $arrlist['order_sn'] = $val['order_sn'];//订单编号
            $arrlist['address'] = $val['address'];//发货地址
            $arrlist['id'] = $val['id'];
            //获取后台设置的温馨提示(广告语)
            $adMessage = M('marked_words')->where('id=1')->getField('content');
            $arrlist['adMessage'] = $adMessage?$adMessage:'';//获取后台设置的温馨提示(广告语)
            if ($admin_id) {
                $arrlist['jingbanren'] = getUserName($admin_id);//经办人
            }
            $arrlist['total_point'] = $val['pay_points'];//总积分
            $arrlist['detail'] = $newArr;
            $list[$key] = $arrlist;
            $newArr = array();
            
        }
        if($res){
            $news = array('code' =>1 ,'msg'=>'操作成功','data'=>$list);
            echo json_encode($news,true);exit; 
        }else{
            $news = array('code' =>0 ,'msg'=>'操作失败','data'=>null);          
            echo json_encode($news,true);exit; 
        }
    }


    // 积分兑换商品提领
    public function tlWxGoods(){
        $id=$_REQUEST['id'];//订单ID 
        
        //获取消费积分
        $mapsss['id']=$id;
        $send_points=D("send_points")->where($mapsss)->find();
        if ($send_points['is_tiling']==1) {
            $news = array('code' =>0 ,'msg'=>'已提领订单不可重复提领!','data'=>null);
            echo json_encode($news,true);exit;
        }
        $points = $send_points['pay_points'];//消耗积分
        $store_id=$send_points['store_id'];
        $code = $send_points['order_sn'];
        $user_id = $send_points['user_id'];
        
        //判断积分是否足够
        $pay_points = M('users')->where('user_id='.$user_id)->getField('pay_points');
        if ($pay_points<$points) {
            $news = array('code' =>0 ,'msg'=>'积分不足!','data'=>null);
            echo json_encode($news,true);exit;
        }

        //判断库存
        $source = $send_points['source'];//1微信兑换  0 app兑换
        if ($source==1) {
            //修改库存
            $goods_id=$send_points['goods_id'];
            $goods_num=$send_points['goods_num'];
            $resource_id=$store_id;
            $re11 = checkGoodsNum($goods_id,$goods_num,$resource_id,4);
            if (!$re11) {
                $news = array('code' =>0 ,'msg'=>'库存不足','data'=>null);
                echo json_encode($news,true);exit;
            }
        }else{
            $send_points_id['send_points_id'] = $id;
            $sendList = M('send_points_detail')->where($send_points_id)->select();

            foreach ($sendList as $keys => $value) {
                $goods_id=$value['goods_id'];
                $goods_num=$value['goods_num'];
                $resource_id=$store_id;
                $re11 = checkGoodsNum($goods_id,$goods_num,$resource_id,4);

                if (!$re11) {
                    $news = array('code' =>0 ,'msg'=>'库存不足','data'=>null);
                    echo json_encode($news,true);exit;
                }
            }
        }

        //积分充足  修改记录状态
        $maps['id'] = $id;
        $data['is_tiling'] = 1;//已提领
        $data['update_time'] = time();//提领时间
        $data['pay_status']=1;
        $admin=D("send_points")->where($maps)->save($data);

        //抵扣分数减掉
        if($admin){
            accountLog($user_id,0,$points,'积分抵扣',0,3,1); 
        }

        //积分兑换类型
        $source = $send_points['source'];//1微信兑换  0 app兑换
        if ($source==1) {
            //修改库存
            $goods_id=$send_points['goods_id'];
            $goods_num=$send_points['goods_num'];
            $resource_id=$store_id;
            $goods_name = $send_points['goods_name'];
            $jjk = jskc_new($goods_id,$goods_num,$resource_id,4,1);
            //新增门店出库记录
            if ($jjk) {
                //新增门店流水记录
                $infofo = addWaterRecord($goods_id, $goods_num, $resource_id, 2);//出货类型 1 进货 2 销售 3 返货
                
                //新增门店出库记录
                $result_outRecord = store_stock_out($resource_id,$code);
                if ($result_outRecord) {
                    //新增门店出库记录详情表
                   store_stock_out_detail($result_outRecord,$goods_id,$goods_name,$goods_num);
                }
            }
        }else{
            $send_points_id['send_points_id'] = $id;
            $sendList = M('send_points_detail')->where($send_points_id)->select();
            foreach ($sendList as $keys => $value) {
                $goods_id=$value['goods_id'];
                $goods_num=$value['goods_num'];
                $resource_id=$store_id;
                $goods_name = $value['goods_name'];
                $jjk = jskc_new($goods_id,$goods_num,$resource_id,4,1);
                //新增门店出库记录
                if ($jjk) {
                    //新增门店流水记录
                    $infofo = addWaterRecord($goods_id, $goods_num, $resource_id, 2);//出货类型 1 进货 2 销售 3 返货
                    
                    //新增门店出库记录
                    $result_outRecord = store_stock_out($resource_id,$code);
                    if ($result_outRecord) {
                        //新增门店出库记录详情表
                       store_stock_out_detail($result_outRecord,$goods_id,$goods_name,$goods_num);
                    }
                }
            }
        }

        if($admin){
            $news = array('code' =>1 ,'msg'=>'提领成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
}