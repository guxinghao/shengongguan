<?php
/**
 * app寄存及代发接口
 * Date: 2017-08-09
 */
namespace Apis\Controller;
use Think\Controller;

class UserController extends BaseController {

	//代发列表及搜索
    public function daifaList(){
        $uid=$_REQUEST['uid'];//当前用户ID
        $store_id=$_REQUEST['store_id'];//门店ID
        $role_id=$_REQUEST['role_id'];//权限ID
        $status=$_REQUEST['status'];//高级搜索条件  提领状态
        $stage=$_REQUEST['stage'];//高级搜索条件  代发阶段

        $start_time=$_REQUEST['start_time'];//高级搜索条件  开始时间
        $end_time=$_REQUEST['end_time'];//高级搜索条件  结束时间
        $sku=$_REQUEST['sku'];//高级搜索条件  编号

        $p = $_REQUEST['p'];//页码
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;

        //搜索条件  编号
        if(!empty($sku)){
            $mpas['tp_send_list.sku'] = $sku;
        }


        //搜索条件  手机号
        if(!empty($_REQUEST['mobile'])){
            $mobile = $_REQUEST['mobile'];
            $mpas['tp_users.mobile']=array('like',"%".$mobile."%");
            
        }
        //搜索条件  提领状态 不限 未提领 已提领
        if(!empty($status)){
            if ($_REQUEST['status']!='不限') {
                $mpas['tp_send_list.status']=$status;
            }
            
        }
        //搜索条件  代发阶段 不限 泡软 剪洗 煮发 封装
        if(!empty($stage)){
            if ($_REQUEST['stage']!='不限') {
                $mpas['tp_send_list.stage']=$stage;
            }
            
        }

        //寄存时间 起始时间存在 结束时间存在  1-3 
        if(!empty($start_time) || !empty($end_time)){
            $a_day = date('Y-m-d',time());
            $a_date = $a_day.'00:00:00';
            $aa_time = strtotime($a_date);
            $a_time = $aa_time-86400*$end_time;//今天的前几天(小)
            $b_time = $aa_time-86400*$start_time;//今天的前几天(大)
            $start_time = date('Y-m-d',$a_time).' 24:00';
            $end_time = date('Y-m-d',$b_time).' 24:00';
            // $mpas['tp_send_list.send_time'] = array('between',array($a_time,$b_time));
            $mpas['tp_send_list.send_time']=array(array('EGT',$start_time),array('ELT',$end_time));   
        }

       /* //搜索条件  开始时间  结束时间
        if($start_time!=0&&$end_time!=0){
            // $start_time=strtotime($start_time);
            $end_time=date('Y-m-d',strtotime($end_time)+24*3600);             
            $mpas['tp_send_list.send_time']=array(array('EGT',$start_time),array('ELT',$end_time));     
        }elseif($start_time!=0){
            // $start_time=strtotime($start_time);
            $mpas['tp_send_list.send_time']=array('EGT',$start_time);
        }elseif($end_time!=0){
            $end_time=date('Y-m-d',strtotime($end_time)+24*3600); 
            $mpas['tp_send_list.send_time']=array('ELT',$end_time);
        }else{
        }*/

        
        if($role_id>4){
             $mpas['tp_send_list.cid']=$uid;
        }else{
             $mpas['tp_send_list.store_id']=array('in',$store_id[0]);
             // $mpas['store_id']=$store_id;
        }
       
       
        $mpas['tp_send_list.is_del']=0;
        $list=D("send_list")->join('tp_users ON tp_send_list.uid = tp_users.user_id','left')->where($mpas)->order('tp_send_list.status desc,create_time desc')->limit($start.','.$pages)->select();

        //笔数
        $count = D("send_list")->join('tp_users ON tp_send_list.uid = tp_users.user_id','left')->where($mpas)->count();

        //根数
        $genshu = D("send_list")->join('tp_users ON tp_send_list.uid = tp_users.user_id','left')->where($mpas)->sum('count');

        //盒数
        $heshu = D("send_list")->join('tp_users ON tp_send_list.uid = tp_users.user_id','left')->where($mpas)->sum('box');

        $obj = new \StdClass();
        $obj->list = $list;
        $obj->totalRecord = (int)$count;//总件数
        $obj->box = $heshu?(int)$heshu:0;//总盒数
        $obj->count = $genshu?(int)$genshu:0;//总根数

        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        


        if($list){
               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
                echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
                echo json_encode($news,true);exit;
        }
    }

    //新建代发订单 (寄存代发)
    public function addDaiFa(){

        $uid=$_REQUEST['uid'];//登录用户ID  
        $member_id=$_REQUEST['member_id'];//客户ID
        $store_id=$_REQUEST['store_id'];//门店ID
        $deposit_list_id = $_REQUEST['deposit_id'];//寄存信息ID  寄存信息跳转代发相关ID
        $product_name=$_REQUEST['product_name'];
        $send_time=$_REQUEST['send_time'];
        $receive_time=$_REQUEST['receive_time'];
        $other_remark=$_REQUEST['other_remark'];//特殊要求
        $requirement=$_REQUEST['requirement'];//发制要求
        // $status=$_REQUEST['status'];//提领状态
        $status="未提领";//提领状态  默认为泡软
        $stage=$_REQUEST['stage'];//代发阶段
        $sku=$_REQUEST['sku'];//商品编号
        $sn1=date('YmdHis',time());

        $num=str_pad($uid,6,"0",STR_PAD_LEFT); 

        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

        $data['cid']=$uid;
        $data['uid']=$member_id;
        $data['sn']=$sn;
        $data['product_name']=$product_name;
        $data['sku']=$sku;

        //特殊要求
        if (!$other_remark) {
            $other_remark = '';
        }
        $data['special']=$other_remark;

        //发制要求
        if (!$requirement) {
            $requirement = '';
        }
        $data['requirement'] = $requirement;

        //法制时间
        if (!$send_time) {
            $data['send_time']='';
        }else{
            $data['send_time']=$send_time;
        }
        // 提领时间
        if (!$receive_time) {
            $data['receive_time']='';
        }else{
            $data['receive_time']=$receive_time;
        }
        $data['store_id']=$store_id;

        //如果为寄存跳转 判断盒数根数限制
        if ($deposit_list_id) {
            $mmp['id'] = $deposit_list_id;
            $model = D('deposit_list');
            $result = $model->where($mmp)->find();
            $box = $result['over_box'];//原来的剩余盒数
            $count = $result['over_count'];//原来的剩余根数
            $tl_box=$_REQUEST['box'];//提领的盒数
            $tl_count=$_REQUEST['count'];//提领的根数
            $left_box=$_REQUEST['left_box'];//剩余的根数 left_box left_count
            $left_count=$_REQUEST['left_count'];//剩余的根数
            if ($tl_box > $box) {
                $news = array('code' =>0 ,'msg'=>'提领盒数超出剩余数量！','data'=>null);
                echo json_encode($news,true);exit;
            }
            // if ($tl_count > $count) {
            //     $news = array('code' =>0 ,'msg'=>'提领根数超出剩余数量！','data'=>null);
            //     echo json_encode($news,true);exit;
            // }
        }

        $data['count']=$_REQUEST['count'];
        $data['box']=$_REQUEST['box'];
        $data['over_count']=$_REQUEST['count'];
        $data['over_box']=$_REQUEST['box'];
        $data['create_time']=time();
        $data['update_time']=time();
        $data['status']=$status;//提领状态
        $data['stage']=$stage;//代发阶段

        $res=M("send_list")->add($data);

        $info = M('send_list')->where('id='.$res)->find();
        $user_info = M('users')->where('user_id='.$info['uid'])->field('nickname, mobile')->find();

        $info['user_name'] = $user_info['nickname'];
        $info['mobile'] = $user_info['mobile'];

        //获取后台设置的温馨提示(广告语)(代发)
        $adMessage = M('marked_words')->where('id=3')->getField('content');
        $info['adMessage'] = $adMessage;

        $arr = array();
        // 如果为寄存列表跳转代发 新增成功则更改寄存数据内容
        if ($res && $deposit_list_id) {
            $info1['over_box'] = $left_box;//剩余盒数
            $info1['over_count'] = $left_count;//剩余根数
            $info1['tl_time'] = time();
            $info1['is_zhuandaifa'] = 1;

            //如果剩余盒数根数都为0 则修改状态为已提领
            if ($info1['over_box']==0 && $info1['over_count']==0) {
                $info1['status'] = 1;
            }

            $maps['id'] = $deposit_list_id;
            $admin=$model->where($maps)->save($info1);

            if ($admin) {

                //新增寄存转代发记录表
                $zhuandaifa['deposit_id'] = $deposit_list_id;
                $zhuandaifa['tl_box'] = $_REQUEST['box'];
                $zhuandaifa['tl_count'] = $_REQUEST['count'];
                $zhuandaifa['over_box'] = $left_box;
                $zhuandaifa['over_count'] = $left_count;
                if ($uid) {
                    $zhuandaifa['creator'] = getUserName($uid);
                }
                $zhuandaifa['create_time'] = time();

                M('zhuandaifa')->add($zhuandaifa);

                $deposit_info = M('deposit_list')->where('id='.$deposit_list_id)->find();
                $deposit_info['user_name'] = $user_info['nickname'];
                $deposit_info['mobile'] = $user_info['mobile'];

                //获取后台设置的温馨提示(广告语)(寄存)
                $adMessage = M('marked_words')->where('id=2')->getField('content');

                $deposit_info['adMessage'] = $adMessage;
                $arr['jicun'] = $deposit_info;
                $arr['daifa'] = $info;


                $news = array('code' =>1 ,'msg'=>'提领成功！','data'=>$arr);
                echo json_encode($news,true);exit;
            }else{
                $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        if($res){
            $arr['daifa'] = $info;

            //短信提醒
            $content="尊敬的会员".$info['user_name']."，您的海参已经准备泡发啦，领取前请提前与您的服务顾问联系哦，有问题请拨打服务监督热线：400-699-0605";

            $res= sendsmss($user_info['mobile'],$content);
            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>$arr);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }

    //提领代发订单(全部提领)
    public function tlDaiFa(){
        $id=$_REQUEST['id'];//代发订单ID 
        $admin_id=$_REQUEST['admin_id'];//代发操作人ID
        // 获取原始盒数根数
        if($id){
            $maps['id'] = $id;
            $result = M('send_list')->where($maps)->find();
            $box = $result['box'];//原始数据
            $count = $result['count'];//原始数据
            // $data['stage'] = "封装";//代发阶段
            $data['status'] = "已提领";//提领状态
            $data['receive_time'] = date('Y-m-d H:i',time());
            $admin=D("send_list")->where($maps)->save($data);
            if ($admin) {
                $return = add_tl_record($id,$box,$count,0,0,$admin_id);
            }
            if($return){
                $news = array('code' =>1 ,'msg'=>'提领成功！','data'=>null);
                echo json_encode($news,true);exit;

            }else{
                $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
                echo json_encode($news,true);exit;
            }
        }else{
            $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }

    //提领代发订单(部分提领)
    public function tlDaiFa_part(){
        $id=$_REQUEST['id'];//代发订单ID 
        $admin_id=$_REQUEST['admin_id'];//代发操作人ID
        // 获取原始盒数根数
        if($id){
            $maps['id'] = $id;
            $box = $_REQUEST['box'];//提领数量
            $count = $_REQUEST['count'];//提领数量
            $over_box = $_REQUEST['over_box'];//剩余数量
            $over_count = $_REQUEST['over_count'];//剩余数量

            //如果剩余盒数和根数都为0  则状态改为提领完成
            if (!$over_box && !$over_count) {
                $data['status'] = "已提领";//提领状态
            }


            $data['over_box'] = $over_box;
            $data['over_count'] = $over_count;
            $data['receive_time'] = date('Y-m-d H:i',time());
            $admin=D("send_list")->where($maps)->save($data);

            if ($admin) {
                $return = add_tl_record($id,$box,$count,$over_box,$over_count,$admin_id);
            }
            if($return){
                $news = array('code' =>1 ,'msg'=>'提领成功！','data'=>null);
                echo json_encode($news,true);exit;

            }else{
                $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
                echo json_encode($news,true);exit;
            }
        }else{
            $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }


    //修改代发操作
    public function updateDafa(){
        $id=$_REQUEST['id'];
        
        $requirement=$_REQUEST['requirement'];
        $receive_time=$_REQUEST['receive_time'];
      
        $data['id']=$id;
        $data['stage']=$_REQUEST['status'];
        $data['update_time']=time();

        $res=M("send_list")->save($data);
        if($res){
            // $uploadList = $this->uploads($_FILES);
            //     foreach($uploadList as $kv){    

            //        $data1['images']= $kv['urlpath'];
            //        $data1['pid']= $id;
            //        $data1['model']= 'send_list';
            //        $data1['create_time']=time();

            //        D("attach")->add($data1);
                
            //     }
           $news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
           echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
            echo json_encode($news,true);exit;
        }

    }

    //寄存列表页及搜索
    public function depositList(){
        $uid=$_REQUEST['uid'];//顾问ID
        $store_id=$_REQUEST['store_id'];//门店ID
        $role_id=$_REQUEST['role_id'];
        
        //搜索条件
        $mobile = $_REQUEST['mobile'];//手机号码
        $sn = $_REQUEST['sn'];//寄存编号
        $status = $_REQUEST['status'];//提领状态（不限 已提领）
        $start_day = $_REQUEST['start_day'];//寄存时间(样式 start_day~end_day)
        $end_day = $_REQUEST['end_day'];//寄存时间(样式 start_day~end_day)
        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }


        $pages="10";
        $start=($p-1)*$pages;
        //手机号码查询
        if(!empty($mobile)){
             $mpas['tp_users.mobile']=array('like',"%".$mobile."%");
        }
        //编号查询
        if(!empty($sn)){
             $mpas['tp_deposit_list.sku']=array('like',"%".$sn."%");
        }
        //提领状态
        if(!empty($status)){
            if ($status==1) {
                $mpas['tp_deposit_list.status']=1;
            }else{
                $mpas['tp_deposit_list.status']=0;
            }
        }

        //寄存时间 起始时间存在 结束时间存在  1-3 
        if(!empty($start_day) || !empty($end_day)){
            $a_day = date('Y-m-d',time());
            $a_date = $a_day.'00:00:00';
            $aa_time = strtotime($a_date);
            $a_time = $aa_time-86400*$end_day;//今天的前几天(小)
            $b_time = $aa_time-86400*$start_day;//今天的前几天(大)
            $mpas['tp_deposit_list.receive_time'] = array('between',array($a_time,$b_time));
        }

        // //寄存时间 起始时间不存在 结束时间存在
        // if(empty($start_day) && !empty($end_day)){
        //     $a_day = date('Y-m-d',time());
        //     $a_date = $a_day.'00:00:00';
        //     $aa_time = strtotime($a_date);
        //     $b_time = $aa_time-86400*$end_day;//今天的前几天(大)
        //     $mpas['tp_deposit_list.receive_time'] = array('ELT',$b_time);//< =
        // }

        // //寄存时间 起始时间存在 结束时间不存在
        // if(!empty($start_day) && empty($end_day)){
        //     $a_day = date('Y-m-d',time());
        //     $a_date = $a_day.'00:00:00';
        //     $aa_time = strtotime($a_date);
        //     $a_time = $aa_time-86400*$start_day;//今天的前几天(小)
        //     $mpas['tp_deposit_list.receive_time'] = array('EGT',$a_time);//> =
        // }

        if($role_id==5){
              $mpas['tp_deposit_list.cid']=$uid;
              $where['cid'] = $uid;//获取当前权限可看的数据总数
        }else{
              $mpas['tp_deposit_list.store_id']=array('in',$store_id[0]);
              $where['store_id'] = array('in',$store_id[0]);//获取当前权限可看的数据总数
        }

      
        $mpas['tp_deposit_list.is_del']=0;
        $where['is_del'] = 0;//获取当前权限可看的数据总数
        // $list=D("deposit_list")->where($mpas)->order('id desc')->limit($start.','.$pages)->select();
        $list=D("deposit_list")->join('tp_users ON tp_deposit_list.uid = tp_users.user_id','left')->where($mpas)->order('tp_deposit_list.status asc')->limit($start.','.$pages)->select();
        $total_number=D("deposit_list")->where($where)->count();// 总条数统计
        $total_over_box=D("deposit_list")->where($where)->sum('over_box');// 总盒数统计
        $total_over_count=D("deposit_list")->where($where)->sum('over_count');// 总根数统计
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        $obj = new \StdClass();
        $obj->list = $list;
        $obj->total_number = $total_number;//总件数
        $obj->total_over_box = $total_over_box;//总盒数
        $obj->total_over_count = $total_over_count;//总根数
        if($list){

               $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
               echo json_encode($news,true);exit;
        }else{
             $news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
               echo json_encode($news,true);exit;
        }
    }


    //新增寄存数据
    public function addDeposit(){
        $uid=$_REQUEST['uid'];//app用户ID
        $member_id=$_REQUEST['member_id'];//客户ID
        $product_name=$_REQUEST['product_name'];//产品名称
        $store_id=$_REQUEST['store_id'];//门店ID
        $receive_time=$_REQUEST['receive_time'];//提交时间
        // $requirement=$_REQUEST['requirement'];
        $count = $_REQUEST['count'];//根数
        $box = $_REQUEST['box'];//盒数
        $goods_id = $_REQUEST['goods_id'];//盒数
        $sn1=date('YmdHis',time());

        $num=str_pad($uid,6,"0",STR_PAD_LEFT); 

        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

        $data['cid']=$uid;
        $data['uid']=$member_id;
        $data['goods_id']=$goods_id;
        if ($goods_id) {
            $sku = M('spec_goods_price')->where('goods_id='.$goods_id)->getField('sku');
        }
        $data['sku']=$sku;
        $data['sn']=$sn;
        $data['product_name']=$product_name;
        $data['store_id']=$store_id[0];
        // $data['requirement']=$requirement;
        $data['receive_time']=strtotime($receive_time);
        $data['count']=$count;
        $data['box']=$box;
        $data['over_count']=$count;
        $data['over_box']=$box;
        $data['create_time']=time();
        
        $res=M("deposit_list")->add($data);
        if($res){
            $info = M('deposit_list')->where('id='.$res)->find();
            $user_info = M('users')->where('user_id='.$info['uid'])->field('nickname, mobile')->find();
            $info['user_name'] = $user_info['nickname'];
            $info['mobile'] = $user_info['mobile'];
            
            //获取后台设置的温馨提示(广告语)(寄存)
            $adMessage = M('marked_words')->where('id=2')->getField('content');

            $info['adMessage']=$adMessage;

            //短信提醒
            
            $content="尊敬的会员".$info['user_name']."，感谢您的信任，晓芹海参提供免费的寄存、代发已经五年啦！这么多年，因为您的信任与支持，我们才能不断前行…";

            $res= sendsmss($user_info['mobile'],$content);

            $news = array('code' =>1 ,'msg'=>'添加成功！','data'=>$info);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'添加失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    //提领寄存数据
    public function tlDeposit(){
        $id=$_REQUEST['id'];//寄存订单ID 
        $tl_box = $_REQUEST['tl_box'];//提领盒数
        $tl_count = $_REQUEST['tl_count'];//提领根数
        $left_box = $_REQUEST['left_box'];//最终剩余盒数 left_box 
        $left_count = $_REQUEST['left_count'];//最终剩余盒数 根数 left_count

        $maps['id'] = $id;
        $result = D('deposit_list')->where($maps)->find();
        $box = $result['over_box'];//原来的剩余盒数
        $count = $result['over_count'];//原来的剩余根数
        if ($tl_box > $box) {
            $news = array('code' =>0 ,'msg'=>'提领盒数超出剩余数量！','data'=>null);
            echo json_encode($news,true);exit;
        }
        $data['over_box'] = $left_box;//剩余盒数
        // if ($tl_count > $count) {
        //     $news = array('code' =>0 ,'msg'=>'提领根数超出剩余数量！','data'=>null);
        //     echo json_encode($news,true);exit;
        // }
        $data['over_count'] = $left_count;//剩余根数
        $shengyubox = $left_box;//剩余盒数
        $shengyucount = $left_count;//剩余根数
        if ($shengyubox==0 && $shengyucount==0) {
            $data['status'] = 1;
        }
        $data['tl_time'] = time();
        $admin=D("deposit_list")->where($maps)->save($data);
        if($admin){
            $news = array('code' =>1 ,'msg'=>'提领成功！','data'=>null);
            echo json_encode($news,true);exit;

        }else{
            $news = array('code' =>0 ,'msg'=>'提领失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    public function songhuo(){
        //获取消费积分
        $mapsss['id']=$_REQUEST['id'];
        $send_points=D("send_points")->where($mapsss)->find();
        $points = $send_points['pay_points'];//消耗积分
        $store_id=$send_points['store_id'];
        $code = $send_points['order_sn'];
        $user_id = $send_points['user_id'];
        if ($send_points['status']==2) {
            $news = array('code' =>0 ,'msg'=>'已发货订单不可重复发货!','data'=>null);
            echo json_encode($news,true);exit;
        }
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
        }
        
        $maps['id'] = $_REQUEST['id'];
        $data['status'] = 2;
        $data['pay_status']=1;
        $info = D("send_points")->where($maps)->save($data);

        //抵扣分数减掉
        if($info){
            accountLog($user_id,0,$points,'积分抵扣',0,3,1); 
        }

        //积分兑换类型
        $source = $send_points['source'];//1微信兑换  0 app兑换
        if ($source==1) {
            //修改库存
            $goods_id=$send_points['goods_id'];
            $goods_num=$send_points['goods_num'];
            $goods_name = $send_points['goods_name'];
            $resource_id = $store_id;
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
            $news = array('code' =>0 ,'msg'=>'app端积分兑换无需发货','data'=>null);
            echo json_encode($news,true);exit;
        }

        
        if($info) {
            $news = array('code' =>1 ,'msg'=>'发货成功!','data'=>null);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'发货失败!','data'=>null);
            echo json_encode($news,true);exit;
        }      
    }

}