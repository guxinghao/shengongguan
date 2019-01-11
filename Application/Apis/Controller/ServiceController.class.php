<?php
/**
 * 服务入口接口
 */
namespace Apis\Controller;
use Think\Controller;

class ServiceController extends BaseController {
    /**
     * 新建投诉接口
     * store_id    门店ID
     */
    public function  addComplain()
    {   
        $type = $_REQUEST['type'];  //投诉类型
        $mobile = $_REQUEST['mobile'];  //投诉人手机号
        $target_id = $_REQUEST['target_id'];  //被投诉人ID
        $target_name = $_REQUEST['target_name'];  //被投诉人姓名
        $store_id = $_REQUEST['store_id'];  //被投诉人门店
        $content = $_REQUEST['content'];  //投诉内容
        $create_time = time();  //投诉时间

        if (!$target_id) {
            $target_name = '';
        }

        //获取投诉人ID
        $where['mobile'] = $mobile;
        $user = M('users')->where($where)->field('user_id, nickname')->find();
        if (!$user) {
            $news = array('code' =>1 ,'msg'=>'手机号不正确！','data'=>null);
            echo json_encode($news,true);exit;
        }
        $data['type'] = $type;
        $data['uid'] = $user['user_id'];
        $data['username'] = $user['nickname'];
        $data['mobile'] = $mobile;
        $data['store_id'] = $store_id;
        $data['create_time'] = $create_time;
        $data['target_id'] = $target_id;
        
        $data['target_name'] = $target_name;
        $data['content'] = $content;

        $result = M('complaint')->add($data);

        if ($result) {
            $news = array('code' =>1 ,'msg'=>'新增成功！','data'=>null);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'新增成功！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    /**
     * 根据门店id 筛选门店人员
     * store_id    门店ID
     */
    public function  getUserOfStore()
    {   
        $store_id = $_REQUEST['store_id'];  //被投诉人门店

        $where['store_id'] = $store_id;
        $user = M('admin')->where($where)->field('admin_id, user_name')->select();

        if ($user) {
            $news = array('code' =>1 ,'msg'=>'新增成功！','data'=>$user);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'新增成功！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }



	/**
	 * 投诉列表接口
	 * store_id    门店ID
	 */
	public function  complainList()
	{	
		$store_id = $_REQUEST['store_id'];

        $member_id = $_REQUEST['memberId'];//会员中心相关记录 某个人的记录  人员ID

		// 如果门店ID存在  则为单个门店的投诉列表
		if ($store_id) {
			$where['store_id'] = $store_id;
		}
		if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        $pages="10";//每页展示10条
        $start=($p-1)*$pages;


        //搜索某个会员的投诉记录
        if ($member_id) {
            $where['uid'] = $member_id;
        }


		$prom_list = M('complaint')->where($where)->limit($start.','.$pages)->order('id desc')->select();
		$list = array();
		foreach ($prom_list as $key => $val) {
			$list[$key]['username'] = $val['username'];//投诉人姓名
			$list[$key]['mobile'] = $val['mobile'];//投诉人手机号
            // 门店名称
            $store_name = M('store')->where('store_id='.$val['store_id'])->getField('store_name');
			$list[$key]['target_name'] = $val['target_name']?$val['target_name']:$store_name;//投诉对象姓名
			$list[$key]['content'] = $val['content'];//投诉内容
			$list[$key]['create_time'] = $val['create_time'];//投诉时间
			$list[$key]['type'] = $val['type'];//投诉类型
			$list[$key]['status'] = $val['status'];//状态  0  未处理  1 已处理
		}
		if ($list) {
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
			echo json_encode($news,true);exit;
		}else{
			$news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
	}


	// 获取后台活动列表数据
	public function showActivity(){
		if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }
        $pages="10";//每页展示10条
        $start=($p-1)*$pages;
		$list = D('activity')->field('title, img, start_day, start_time, end_time, store_name, content, counts, activity_id')->limit($start.','.$pages)->order('activity_id desc')->select();
		foreach ($list as $key => $val) {
			$where['activity_id'] = $val['activity_id'];//活动ID
			$enlist_num = M('sign_up')->where($where)->count();
			$map['status'] = 1;//签到成功条件
			$map['activity_id'] = $val['activity_id'];//活动ID
			$coming_num = M('sign_up')->where($map)->count();
			$list[$key]['enlist_num'] = $enlist_num;//报名人数

            // 拼接图片路径
            $url=C("http_urls");
            $imgurl = $url.$val['img'];
            $list[$key]['img'] = $imgurl;//图片路径
			$list[$key]['coming_num'] = $coming_num;//实到人数
		}
		if($list){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}


	// 活动签到接口 (扫码签到)
	public function signAttend(){
		$user_id = $_REQUEST['user_id'];//参会人ID
        $mobile = M('users')->where('user_id='.$user_id)->getField('mobile');
		$activity_id = $_REQUEST['activity_id'];//活动ID
		$maps['uid'] = $user_id;
        $maps['activity_id'] = $activity_id;
		$maps['pay_status'] = 2;//支付完成
		$info = D('sign_up')->where($maps)->find();
		if (!$info) {
			$news = array('code' =>0 ,'msg'=>'未找到报名信息！','data'=>null);
			echo json_encode($news,true);exit;
		}else{
            if ($info['status']==1) {
                $news = array('code' =>0 ,'msg'=>'该客户已签到！','data'=>$mobile);
                echo json_encode($news,true);exit;
            }
			$where['id'] = $info['id'];
			$data['status'] = 1;
			$data['update_time'] = time();
			$result = D('sign_up')->where($where)->save($data);
			if ($result) {
				$news = array('code' =>1 ,'msg'=>'签到成功！','data'=>$mobile);
				echo json_encode($news,true);exit;
			}else{
				$news = array('code' =>0 ,'msg'=>'签到失败！','data'=>null);
				echo json_encode($news,true);exit;
			}
		}
		
	}

    // 活动签到接口 (扫码签到)
    public function signAttend_mobile(){
        $mobile = $_REQUEST['mobile'];//参会人手机号
        $user_id = M('users')->where('mobile='.$mobile)->getField('user_id');

        if (!$user_id) {
            $news = array('code' =>0 ,'msg'=>'手机号不存在！','data'=>null);
            echo json_encode($news,true);exit;
        }
        $activity_id = $_REQUEST['activity_id'];//活动ID
        $maps['uid'] = $user_id;
        $maps['activity_id'] = $activity_id;
        $maps['pay_status'] = 2;//支付完成
        $info = D('sign_up')->where($maps)->find();
        if (!$info) {
            $news = array('code' =>0 ,'msg'=>'未找到报名信息！','data'=>null);
            echo json_encode($news,true);exit;
        }else{
            if ($info['status']==1) {
                $news = array('code' =>0 ,'msg'=>'该客户已签到！','data'=>$mobile);
                echo json_encode($news,true);exit;
            }
            $where['id'] = $info['id'];
            $data['status'] = 1;
            $data['update_time'] = time();
            $result = D('sign_up')->where($where)->save($data);
            if ($result) {
                $news = array('code' =>1 ,'msg'=>'签到成功！','data'=>$mobile);
                echo json_encode($news,true);exit;
            }else{
                $news = array('code' =>0 ,'msg'=>'签到失败！','data'=>null);
                echo json_encode($news,true);exit;
            }
        }
        
    }

	/*//参会======================(供应商)
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
    }*/

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
        // if($role_id>2){
            // $maps['cid']=$cid;
        // }else{
            $maps['store_id']=array('in',$store_id[0]);
        // }
        
        $maps['type']=2;//类型为送货
        $maps['status']=1;//未发货
        $maps['pay_status']=1;//已支付

        $list=D("send_points")->where($maps)->field('id, goods_num, order_sn, goods_name, address')->order('id desc')->limit($start.','.$pages)->select();
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



     //送货操作
    public function send_goods(){
        $id=$_REQUEST['id'];

        $data['id']=$id;
        $data['status']=2;
        $data['pay_status']=1;

        $res=D("send_points")->save($data);

        //获取消费积分
        $mapsss['id']=$id;
        $send_points=D("send_points")->where($mapsss)->find();
        $points = $send_points['pay_points'];//消耗积分
        $store_id=$send_points['store_id'];
        $code = $send_points['order_sn'];
        $user_id = $send_points['user_id'];
        //抵扣分数减掉
        if($res){
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
        }
        
        if($res){
            $news = array('code' =>1 ,'msg'=>'送货成功！','data'=>null);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>0 ,'msg'=>'送货失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }



    //参会记录(某个人的记录)
    public function atvlog(){
        $user_id=$_REQUEST['uid'];
        $maps['tp_sign_up.uid']=$user_id;

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $pages="10";
        $start=($p-1)*$pages;
        // 状态为已参会的记录
        $maps['tp_sign_up.status']=1;//0未到场 1到场

        $order_goods=D("sign_up")->join('tp_users on tp_sign_up.uid = tp_users.user_id')->field('tp_sign_up.*,tp_users.mobile,tp_users.nickname')->where($maps)->limit($start.','.$pages)->order('id desc')->select();

        if($order_goods){

            foreach($order_goods as $key=>$val){
                $mapss['activity_id']=$val['activity_id'];
                $activity=D("activity")->where($mapss)->find();
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


    //通知未完成内容(服务)
    public function notice_service(){
        // 权限  当为库管时   当为店长时 不同内容
        $role_id = $_REQUEST['role_id'];//权限ID

        // 为库管时
        if ($role_id==10) {
            $total_count = 0;
        // 为店长时
        }else{
            $store_id = $_REQUEST['store_id'];//门店ID

            // 未处理的投诉总数
            $map['status'] = 0;
            $map['store_id'] = $store_id[0];
            $count = M('complaint')->where($map)->count();

            // 未处理的发货总数
            $maps['type'] = 2;//类型为送货
            $maps['status'] = 1;//未发货
            $maps['pay_status'] = 1;//已支付
            $maps['store_id'] = array('in',$store_id[0]);
            $count1=D("send_points")->where($maps)->count();

            //未处理的沟通总数
            $mao['store_id'] = $store_id[0];
            $mao['com_status'] = 2;
            $count2=D("communication")->where($mao)->count();

            $total_count = $count+$count1+$count2;
        }

        $data = array();
        $data['total_count'] = $total_count;
        $data['complaint_count'] = $count?$count:0;
        $data['send_count'] = $count1?$count1:0;
        $data['communication_count'] = $count2?$count2:0;

        $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
        echo json_encode($news,true);exit;
    }

    //通知未完成内容(库存)
    public function notice_stock(){
        // 权限  当为库管时   当为店长时 不同内容
        $role_id = $_REQUEST['role_id'];//权限ID

        // 为库管时
        if ($role_id==10) {
            //未确认的补货  及  拒签收的单数
            $wh['status'] = array('in','1,6');
            $count1 = D("replenishment")->where($wh)->count();

            //退回处理
            $wh1['status'] = array('in','1,4');
            $count2 = D("return_goods_store")->where($wh1)->count();
            
            $total_count = $count1+$count2;
        // 为店长时
        }else{
            $store_id = $_REQUEST['store_id'];//门店ID

            //未处理的门店入库确认
            $wh['status'] = 4;
            $wh['store_id'] = array('in',$store_id[0]);
            $count1=D("replenishment")->where($wh)->count();

            //未处理的门店退货->发货
            $wh1['status'] = 3;
            $wh1['store_id'] = array('in',$store_id[0]);
            $count2 = D("return_goods_store")->where($wh1)->count();

            $total_count = $count1+$count2;
        }
        
        $data = array();
        $data['total_count'] = $total_count;
        $data['replenishment_count'] = $count1;
        $data['return_count'] = $count2;

        $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$data);
        echo json_encode($news,true);exit;
    }

}