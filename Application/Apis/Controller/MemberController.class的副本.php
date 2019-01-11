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
            
             $rs['store_id'] = $value['store_id'];
            $infos = M('store')->where($rs)->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];
        }
         

        if($user){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
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
        $maps['user_id']=$userid;
        $user=D("users")->where($maps)->find();
        $user['reg_time']=date('Y.m.d',$user['reg_time']);

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

        $store=M("store")->where($vmaps)->find();
        $user['store']=$store['store_name'];
        $user['dianzhang']=$store['shopkeeper'];
        $user['deji']=$this->getCount($user['level']);

        $user['guwen_name']=getAdminName($user['first_leader']);
        $user['kaifa_name']=getAdminName($user['add_uid']);

         $user['flag']=getUserFen($user['user_id']);

        if($user['add_uid']==0){
            $user['isdev']=1;
        }else{
            $user['isdev']=1; 
        }

        $user['xitong']=null;
        
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
        $user['province']=get_region_name($user['province']);
        $user['city']=get_region_name($user['city']);
        $user['district']=get_region_name($user['district']);
        $user['twon']=get_region_name($user['twon']);

        if($user){
                $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
                echo json_encode($news,true);exit;
    
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;

        }
    }

    //查看顾问列表
    public function index(){

        $uid=$_REQUEST['uid'];

        $store_id=$_REQUEST['store_id'];

        // $store_id=implode(',', $store_id);
        


        if (!$uid )
        {
            echo json_encode(array('code' =>0 ,'msg'=>'参数异常','data'=>[]), true);
            exit;
        }
        $member=D('admin')->where('admin_id='.$uid)->find();


        $vmaps['store_id']=array('in',$store_id[0]);

        $store=D("store")->where($vmaps)->select();


        foreach ($store as $key => $value) {
            $store[$key]['dianzhang']=$value['shopkeeper'];
        }

        $vmapss['role_id']=$member['role_id'];
        $admin_role=D("admin_role")->where($vmapss)->find();



        $kaifacount=D("users")->where('add_uid='.$uid)->count();
        $fuwucount=D("users")->where('first_leader='.$uid)->count();

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
       


        $data['fuid']=$uid;
        //$data['uid']=$member_id;
       
        $data['cTime']=$cTime;
        $data['c_person']=$c_person;
        $data['store_id']=$store_id[0];
        $data['tel']=$tel;
        $data['status']=$status;
        $data['content']=$content;
        $data['create_time']=time();

        $res=M("communication")->add($data);
        if($res){
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>1 ,'msg'=>'添加失败！','data'=>null);
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

        if($role_id>2){
              $mpas['fuid']=$uid;
        }else{
              $mpas['store_id']=array('in',$store_id[0]);
        }

        
        
        $list=D("communication")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
       
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
        $uid=$_REQUEST['uid'];
        $member_id=$_REQUEST['member_id'];
        $store_id=$_REQUEST['store_id'];
        $cTime=$_REQUEST['cTime'];
        $province=$_REQUEST['province'];
        $city=$_REQUEST['city'];
        $address=$_REQUEST['address'];
        $reason=$_REQUEST['reason'];
        $content=$_REQUEST['content'];
        $remark=$_REQUEST['remark'];
      
       


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


         if($role_id>2){
               $mpas['cid']=$uid;
        }else{
             $mpas['store_id']=array('in',$store_id[0]);
        }

       
        
        $list=D("visit_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
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
   
    //根据商品编号
    public function  getSpecGoods(){
        $sku=$_REQUEST['sku'];
        
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
            $goods=D("goods")->field('goods_id,market_price,goods_name,goods_sn,spu')->where($gmaps)->find();

            if($goods==false){
                 $news = array('code' =>0 ,'msg'=>'商品不存在！','data'=>null);
                echo json_encode($news,true);exit;
            }

            $spec_goods[$key]['goods_sn']=$goods['goods_sn'];
            $spec_goods[$key]['goods_name']=$goods['goods_name'];
            $spec_goods[$key]['spu']=$goods['spu'];
            $spec_goods[$key]['spec_key_name']=$value['key_name'];
          
            $spec_goods[$key]['goods_id']=$goods['goods_id'];
            $spec_goods[$key]['price']=$goods['market_price'];
            $spec_goods[$key]['store_count']=$value['store_count'];
            $spec_goods[$key]['spec_key']=$value['key'];
            $spec_goods[$key]['sku']=$value['sku'];
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
              $list['pay_points']=$getKeScore;
              $list['data']=$arr;

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
        //echo M()->getlastsql();

       
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

        $counts=D("order")->where($maps)->count();
        $total_amount=D("order")->where($maps)->sum('order_amount');

        $list=D("order")->where($maps)->order('order_id desc')->limit($start.','.$pages)->select();

        //echo M()->getlastsql();
        //exit;
        foreach ($list as $key => $value) {
            $maps1['order_id']=$value['order_id'];
            $r['store_id'] = $value['store_id'];
            $where= D('store')->where($r)->find();
            $list[$key]['shop_no'] =  $where['shop_no'];

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
       // $end_time=strtotime($end_time); 
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

        //$maps['cid']=$cid;
        $maps['pay_status']=1;
        $maps['type']=0;

        $list=D("order")->where($maps)->order('order_id desc')->select();
        $orderid=formatArray($list,'order_id');
        //echo $orderid;
        $where['order_id']=array('in',$orderid);
        $order_goods=D("order_goods")->where($where)->group('sku')->select();
        //echo M()->getlastsql();

        if($order_goods){
            foreach ($order_goods as $key => $value) {
                $where['sku']=$value['sku'];

                $counts=D("order_goods")->where($where)->sum('goods_num');
                //echo M()->getlastsql();

                $order_goods[$key]['salecount']=$counts;
                 $order_goods[$key]['all_price'] = $order_goods[$key]['goods_price']*$order_goods[$key]['goods_num'];
            }
        }

         foreach($order_goods as $arr2){
                $flag[]=$arr2["salecount"];
            }
            array_multisort($flag, SORT_DESC, $order_goods);
        //$order_goods['all_price'] = $order_goods['goods_price']*$order_goods['goods_num'];
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$order_goods);
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

        $list=D("order")->where($maps)->order('order_id desc')->group('cid')->select();

        if($list){

            $cid=formatArray($list,'cid');

            $where['admin_id']=array('in',$cid);

            $admin=D("admin")->where($where)->select();

            foreach ($admin as $key => $value) {
                $maps['cid']=$value['admin_id'];
                
                $total_amount=D("order")->where($maps)->sum('order_amount');
                //echo M()->getlastsql();

                $mpas1['store_id']=array('in',$value['store_id']);

                $store=D("store")->where($mpas1)->find();

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

        $start_time=strtotime($start_time);
        //$end_time=strtotime($end_time); 
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
        $list=D("order")->where($maps)->order('order_id desc')->select();

        $orderid=formatArray($list,'order_id');
        //echo $orderid;
        $where['order_id']=array('in',$orderid);
        $where['sku']=$sku;
        $order_goods=D("order_goods")->where($where)->select();

        if($order_goods==false){
            $news = array('code' =>0 ,'msg'=>'未找到指定商品','data'=>null);
                echo json_encode($news,true);exit;
        }

        $orderid=formatArray($order_goods,'order_id');


        $where1['order_id']=array('in',$orderid);

        $list=D("order")->where($where1)->order('order_id desc')->group('cid')->select();

        if($list){

             $cid=formatArray($list,'cid');

            $where1['admin_id']=array('in',$cid);

            $admin=D("admin")->where($where1)->select();

            foreach ($admin as $key => $value) {
                $maps['cid']=$value['admin_id'];
                $total_amount=D("order")->where($maps)->sum('total_amount');
                $total_order=D("order")->where($maps)->select();
                $orderid1=formatArray($total_order,'order_id');
                $omaps['order_id']=array('in',$orderid1);
                $total_counts=D("order_goods")->where($omaps)->sum('goods_num');     

                $mpas1['store_id']=array('in',$value['store_id']);
                $store=D("store")->where($mpas1)->find();
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
        $lists['sale_rank']=$admin;
        
         if($admin){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$lists);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }


    }
    //发送验证码接口
    public  function sendsmslog(){
       
        $rand=rand('1000','9999');
        $mobile=$_REQUEST['mobile'];
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
    //修改密码
    public function updatePw(){
        $mobile=$_REQUEST['mobile'];
        $pwd=$_REQUEST['pwd'];
        $pwd1=$_REQUEST['pwd1'];

        if($pwd1!=$pw){
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
    //门店列表
    public function store_list(){
        $store=D("store")->select();

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

        $data['admin_id']=$_REQUEST['cid'];

        $admin=D("Admin")->save($data);
        //echo M()->getlastsql();
        if($admin){
            $news = array('code' =>1,'msg'=>'操作成功','data'=>null);
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
        $maps['user_id']=$uid;
        $user=D("users")->where($maps)->find();
        $vmmp['mobile']=$user['mobile'];
        $communication=D("communication")->where()->order('create_time desc')->find();

        if($communication){
            $contact_time=$communication['cTime'];
        }else{  
            $contact_time='无';
        }

        $where['pay_status']=1;
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
        $member['pricecount']=$total_pricecount;
        $member['total_count']=$total_count;
        $member['shou_price']=$firstOrder['goods_price'];
        $member['max_price']=$maxScore;
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
        $member['lianxi_count']=0;
        $member['hudong_count']=0;
        $member['tuijian_count']=0;
        $member['beituijian_count']=0;
        
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

        $points=$_REQUEST['points'];
        $timestamp=$_REQUEST['timestamp'];
        $sign=$_REQUEST['sign'];

        $maps['order_sn']=$order_sn;

        $order=D("Order")->field('order_id,user_money,cid,user_id,cid,order_sn,pay_name,total_amount')->where($maps)->find();

        $keyword=$order['order_sn'];
        $keyword.=$order['cid'];
        $keyword.=$timestamp;
        $sign1=md5($keyword);

    

        if($sign1!=$sign){
            $news = array('code' =>0,'msg'=>'非法操作，签名不对','data'=>null);
                        echo json_encode($news,true);exit;
        }

       

        $data['pay_status']=1;
        $data['order_status']=4;
        $data['shipping_status']=1;
        $data['shipping_time']=time()+10*60;
        $data['confirm_time']=time()+10*60;
        $data['pay_name']=$pay_name;
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
        
        $data['pay_time']=time();
        $where['order_sn']=$order_sn;
        $res=D("Order")->where($where)->save($data);
        //echo M()->getlastsql();

        if($res){


            

            $mapsss['user_id']=$order['user_id'];

            $users=D("Users")->where($mapsss)->find();

            if($users['first_time']==0){
                update_user_levels($order['user_id']);

                 $datass122['first_time']=time();
                 D("Users")->where($mapsss)->save($datass122);
            }else{
                update_user_level($order['user_id']);
            }

            $datass['first_leader']=$order['cid'];
            $datass['end_time']=time();
            D("Users")->where($mapsss)->save($datass);
            //dump($users);


            if(!empty($points)){
            //抵扣分数减掉

             //accountLog($order['user_id'],0,-$points,'积分抵扣'); 
             accountLog($order['user_id'],0,$points,'积分抵扣',0,3,1); 

            /**
            $points=$_REQUEST['points'];
            $userdatas1['pay_points']=array('exp',"pay_points-".$points."");
            $usermaps1['user_id']=$order['user_id'];
            D("users")->where($usermaps1)->save($userdatas1);
            */
            }

            $mapsss['user_id']=$order['user_id'];

            $users=D("Users")->where($mapsss)->find();

            $mapss['level_id']=$users['level'];

            $userlevel=D("user_level")->where($mapss)->find();
            //dump($userlevel);

            $ps=$userlevel['ps'];

            $score=$order_amount*$ps;
            //echo $score;
            $score=$score/100;

            // accountLog($order['user_id'],0,$score,'购买商品添加积分'); 
			  accountLog($order['user_id'],0,$score,'购买商品添加积分',0,2); 

              if($order['user_id']>0){
                    $mapss['user_id']=$order['user_id'];

                    $usersss=D("Users")->where($mapss)->find();

                    if($usersss['xingming']){
                        $name = $usersss['xingming'];
                     }else{
                        $name = $usersss['nickname'];
                     }
                     $mobile=$usersss['mobile'];
                     $pay_points1=$usersss['pay_points'];

                     $content="尊敬的会员".$name."，您的晓芹会员卡于".date('Y-m-d H:i:s')."消费现金".$order_amount."元，消费积分".$points."，产生积分".$score."，总积分".$pay_points1."。查询兑换积分，学习海参吃法，请关注公众号！“日食一参”的人越来越多，快加入我们吧…4006990605";
                     $res= sendsmss($mobile,$content);
            }
                        


           // $userdatas['pay_points']=array('exp',"pay_points+".$score."");
           // $usermaps['user_id']=$order['user_id'];
           // D("users")->where($usermaps)->save($userdatas);
            //echo M()->getlastsql();

            $mapsss['order_id']=$order['order_id'];

            $goods=D("order_goods")->where($mapsss)->select();
            foreach ($goods as $keys => $value) {



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
                        //echo M()->getlastsql();

                    }else{
                        

                        $datass['into_stock']=array('exp',"into_stock-".$value['goods_num']."");
                        $spec_goods_price=D("store_data")->where($vvmaps)->save($datass);
                    }

                   

                }   
            }

            $mapsss['admin_id']=$order['cid'];
            $admin=D("admin")->where($mapsss)->find();
            $mpass1['store_id']=$admin['store_id'];
            $store=D("store")->where($mpass1)->find();

            $order['jingbanren']=$admin['name'];
            $order['storename']=$store['store_name'];
            $where['order_id']=$order['order_id'];
            $order_goods=D("order_goods")->field('goods_name,sku,goods_id,goods_num,spec_key_name,goods_price')->where($where)->select();
            foreach ($order_goods as $keyss => $value) {
                $gmapsss['goods_id']=$value['goods_id'];
                $goods=D("goods")->where($gmapsss)->find();

                $order_goods[$keyss]['spu']=$goods['spu'];

            }

            $order['goods']=$order_goods;
            $order['order_amount']=$order_amount;
            $order['score_money']=$score_money;
            $order['user_money']=$order['user_money'];

            if($points==0){
                $points=0;
            }
            $order['points']=$points;
            $order['pay_name']=$pay_name;

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
        $order=D("order")->where($maps)->select();

        $orderids=formatArray($order,'order_id');

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;
        $mapss['order_id']=array('in',$orderids);

        $order_goods=D("order_goods")->where($mapss)->limit($start.','.$pages)->order('rec_id desc')->select();

        if($order_goods){
            $news = array('code' =>1,'msg'=>'操作成功','data'=>$order_goods);
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


       
        $user=D("users")->where($mpas)->order('user_id desc')->limit($start.','.$pages)->select();

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
            $infos = M('store')->where($rs)->find();
            $user[$key]['shop_no'] = $infos['shop_no'];
            $user[$key]['store_name'] = $infos['store_name'];
        }
         

        if($user){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
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
        ///echo  $store_id[0];

      //  $store_id=replace($store_id);

      //  dump($store_id);


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

        //dump($maps);
       
        $user=D("users")->where($maps)->order('user_id desc')->limit($start.','.$pages)->select();

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

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$user);
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
        $nickname=$_REQUEST['nickname'];
        $birthdays=$_REQUEST['birthdays'];
        $source=$_REQUEST['source'];
        $purpose=$_REQUEST['purpose'];
        $preferred_products=$_REQUEST['preferred_products'];
        $sex=$_REQUEST['sex'];
        $style=$_REQUEST['style'];
        $shiyongrenqun=$_REQUEST['shiyongrenqun'];
        $fazhifangshi=$_REQUEST['fazhifangshi'];
        $fenggeleixing=$_REQUEST['fenggeleixing'];
        $invoice=$_REQUEST['invoice'];
        $invoice=$_REQUEST['invoice'];
        $invoice1=$_REQUEST['invoice1'];
        $invoice2=$_REQUEST['invoice2'];
        $remark=$_REQUEST['remark'];
        $remark1=$_REQUEST['remark1'];
        $remark2=$_REQUEST['remark2'];

        if(!empty($nickname)){
            $data['nickname']=$nickname;
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

        $maps['mobile']=$mobile;

        $user=D("users")->where($maps)->find();

        if($user){            
            $news = array('code' =>0 ,'msg'=>'用户已存在！','data'=>$user['user_id']);
            echo json_encode($news,true);exit;
        }
        //if(!empty($store_id)){
             $data['store_id']=$store_id[0];
        //}
       
        $data['add_uid']=$first_leader;
        $data['first_leader']=$first_leader;
        $data['mobile']=$mobile;
        $data['nickname']=$nickname;
        $data['password']=md5('123456');
        $data['reg_time']=time();

        $res=D("users")->data($data)->add();

        if($res){
			accountLog($res,0,30,'注册成功+30积分',0,1); 

            $content="您的电子会员卡已激活，并送30积分，消费时请报手机号码，一边吃海参，一边赚积分！详询4006990605（晓芹海参）";
                            $res11= sendsmss($mobile,$content);
            
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>$res);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }

    
}