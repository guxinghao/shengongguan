<?php
/**
 * 总仓库接口
 */
namespace Apis\Controller;
use Think\Controller;

class RepertoryController extends BaseController {
	/**
	 * 获取仓库列表
	 * with_del 0:不包含已删除仓库 1:包含已删除仓库
	 */
	public function get_storages()
	{	
		$with_del = $_REQUEST['with_del'];
		if (!$with_del) {
			$map['is_del'] = 0;
		}
		$map['status'] = 0;
		$result = M('repertory')->where($map)->select();
		$re = array();
		foreach ($result as $key => $val) {
			$re[$key]['storage_id'] = $val['id'];
			$re[$key]['repertory_name'] = $val['repertory_name'];
			if ($val['status']==0) {
				$re[$key]['status'] = '启用';
			}else{
				$re[$key]['status'] = '禁用';
			}
			$re[$key]['address'] = $val['address'];
		}
		$count = M('repertory')->where($map)->count();
		$obj = new \StdClass();
		$obj->item_count = $count;
		$obj->list = $re;
		if($result){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

	/**
	 * 新增仓库/修改
	 */
	public function add_storages()
	{	
		$user_id = $_REQUEST['user_id'];//用户ID
		$repertory_name = $_REQUEST['repertory_name'];//仓库名称
		$repertory_name_short = $_REQUEST['repertory_name_short'];//仓库简短名称
		$address = $_REQUEST['address'];//仓库地址
		$repertory_id = $_REQUEST['repertory_id'];//仓库ID
		$is_del = $_REQUEST['is_del'];//是否删除
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'请使用正确信息登录！','data'=>null);
			echo json_encode($news,true);exit;
		}
		// 判断有无权限新建仓库
		// $role_name = D("admin")->join('tp_admin_role ON tp_admin.role_id = tp_admin_role.id','left')->getField('role_name');
		$roleInfo = M('admin')->where('admin_id='.$user_id)->getField('admin_id, role_id, user_name');
		$role_id = $roleInfo[$user_id]['role_id'];
		$user_name = $roleInfo[$user_id]['user_name'];
		if ($role_id !=10 ) {
			$news = array('code' =>0 ,'msg'=>'无权限此操作！','data'=>null);
			echo json_encode($news,true);exit;
		}
		if (!empty($repertory_name)) {
			$data['repertory_name'] = $repertory_name;
		}
		if (!empty($repertory_name_short)) {
			$data['repertory_name_short'] = $repertory_name_short;
		}
		if (!empty($address)) {
			$data['address'] = $address;
		}
		if (!empty($is_del)) {
			$data['is_del'] = $is_del;//传参取参数
		}else{
			$data['is_del'] = 0;//无参取0
		}
		$data['creator'] = $user_name;
		$data['creator_id'] = $user_id;
		$data['create_time'] = time();
		if ($repertory_id) {			//有仓库ID做修改
			$where['id'] = $repertory_id;
			$res = M('repertory')->where($where)->save($data);
		}else{							//无仓库ID做新增
			$res = M('repertory')->add($data);
		}
		if($res){
			$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
		
	}

	/**
	 * 仓库端库存查询接口
	 */
	public function search_storages()
	{	
		$repertory_id = $_REQUEST['id'];//仓库ID
		$goods_sn = $_REQUEST['goods_sn'];//商品编号
		$page = $_REQUEST['page'];//页码
		$user_id = $_REQUEST['user_id'];//登录人员标识
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$orderBy = $_REQUEST['orderBy'];//排序  0 正序 1 倒序
		// 判断有无权限此操作
		if (!empty($user_id)) {
			$roleInfo = M('admin')->where('admin_id='.$user_id)->getField('admin_id, role_id, user_name');
			$role_id = $roleInfo[$user_id]['role_id'];
			$user_name = $roleInfo[$user_id]['user_name'];
			if ($role_id !=10 ) {
				$news = array('code' =>0 ,'msg'=>'无权限此操作！','data'=>null);
				echo json_encode($news,true);exit;
			}
		}else{
			$news = array('code' =>0 ,'msg'=>'无权限此操作！','data'=>null);
			echo json_encode($news,true);exit;
		}
		//不同仓库查询
		if (!empty($repertory_id)) {
			$map['resource_id'] = $repertory_id;
		}
		//商品查询
		if(!empty($goods_sn)){
			$bbmap['sku']=$goods_sn;
            $goods_id=D("spec_goods_price")->where($bbmap)->getField('goods_id');
            $map['good_id'] = $goods_id;
        }
        // 页面
        if (!$page) {
			$page = 1;
		}else{
			$page = $_REQUEST['page'];
		}
		// 每页展示条数
		if (!$page_count) {
			$page_count = 10;
		}
		$start=($page-1)*$page_count;
		//仓库端标识
		$map['stock_type'] = 1;

		$maps['stock_type'] = 1;//查询商品总条数
		$count = M('stock')->where($map)->sum('number');

		if ($orderBy) {
			$result = M('stock')->where($map)->field('tp_stock.*,sum(number) as num1,sum(lock_number) as num2')->group('good_id')->order('good_id desc')->limit($start.','.$page_count)->select();
		}else{
			$result = M('stock')->where($map)->field('tp_stock.*,sum(number) as num1,sum(lock_number) as num2')->group('good_id')->limit($start.','.$page_count)->select();
		}	

		
		// $result = M('stock')->where($map)->limit($start.','.$page_count)->select();
		$re = array();
		foreach ($result as $key => $val) {
			$re[$key]['id'] = $val['id'];//记录ID
			$re[$key]['good_id'] = $val['good_id'];//商品ID
			//获取商品名称
			$goods_name = M("goods")->where('goods_id='.$re[$key]['good_id'])->getField('goods_name');
			$spu = M("goods")->where('goods_id='.$re[$key]['good_id'])->getField('spu');

			//获取商品编号
			$sku = M("spec_goods_price")->where('goods_id='.$re[$key]['good_id'])->getField('sku');
			//获取仓库名称
			$repertory_name = M("repertory")->where('id='.$val['resource_id'])->getField('repertory_name');
			$re[$key]['goods_name'] = $goods_name;
			$re[$key]['spu'] = $spu;//单位
			$re[$key]['sku'] = $sku;//单位
			if ($map['resource_id']) {
				$re[$key]['repertory_name'] = $repertory_name;//仓库名称
			}else{
				$re[$key]['repertory_name'] = '';//仓库名称
			}
			$re[$key]['total_count'] = $val['num1'];//总数量
			$re[$key]['lock_count'] = $val['num2'];//锁定件数
			$re[$key]['valid_count'] = $re[$key]['total_count'];//可用件数
		}
		$obj = new \StdClass();
		$obj->total_num = $count;
		$obj->list = $re;
		if($result){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}


	/**
	 * 仓库端入库操作
	 */
	public function in_storage_stocks()
	{
		$in_storage_type = $_REQUEST['type'];//入库类型
		$repertory_id = $_REQUEST['repertory_id'];//仓库id
		$user_id = $_REQUEST['user_id'];//操作人ID
		$goods_list = $_REQUEST['goods_list'];//货物列表
		$user_name = $this->AutoCheckRole($user_id,'库管');
		// 新增入库记录
		$data['in_storage_type'] = $in_storage_type;
		//入库单编号
		$sn1=date('YmdHis',time());
        $num=str_pad($user_id,6,"0",STR_PAD_LEFT); 
        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

		$data['in_storage_sn'] = $sn;//入库单编号
		$data['repertory_id'] = $repertory_id;
		$data['create_time'] = time();
		$data['creator'] = $user_name;
		$result = M('in_storage_stocks')->add($data);
		if (!$result) {
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
		if ($result) {
			// 新增入库记录详情
			$arr = json_decode($goods_list);//确认发货信息  数组
			$length = count($arr);
			$info['storage_stocks_id'] = $result;	//入库记录ID
			$str = '';
			for ($i=0; $i < $length; $i++) { 
				$info['count'] = $arr[$i]->count;				//商品数量
				$info['goods_id'] = $arr[$i]->goods_id;				//商品ID
				$info['goods_name'] = $arr[$i]->goods_name;			//商品名称
				$info['repertory_id'] = $repertory_id;			 	//仓库ID
				$info['create_time'] = time();						//入库时间
				$res = M('warehousing_detail')->add($info);
				if (!$res) {
					$str .= $info['goods_name'].'入库失败!';
					continue;
				}
				//新增总部库存表数据
				$stockArr['stock_type'] = 1;
				$stockArr['good_id'] = $info['goods_id'];
				$stockArr['resource_id'] = $repertory_id;	//仓库ID
				$stockArr['number'] = $info['count'];	//数量
				$stockArr['create_time'] = time();	//数量
				$stockArr['creator'] = $user_name;	//操作人
				//判断是否存在  不存在新增 存在修改
				$where['stock_type'] = 1;
				$where['good_id'] = $stockArr['good_id'];
				$where['resource_id'] = $stockArr['resource_id'];
				$wwp = M('stock')->where($where)->find();
				if ($wwp) {
					$old_number = $wwp['number'];//原始数据
					$newData['number'] = $old_number+$stockArr['number'];//更新数据
					$newData['update_time'] = time();//更新时间
					$re = M('stock')->where($where)->save($newData);
					if (!$re) {
						$str .= '操作失败!';
					}else{
						// 新增仓库端流水
						addWaterRepertoryRecord($stockArr['good_id'],$stockArr['number'],$where['resource_id'],1);
					}
				}else{
					$re = M('stock')->add($stockArr);
					if (!$re) {
						$str .= '操作失败!';
					}else{
						// 新增仓库端流水
						addWaterRepertoryRecord($stockArr['good_id'],$stockArr['number'],$where['resource_id'],1);
					}
				}
			}
			if(!$str){
				$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>$obj);
				echo json_encode($news,true);exit;
	        }else{
				$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
				echo json_encode($news,true);exit;
	        }
		}
	}

	/**
	 * 获取仓库端入库申请列表
	 */
	public function getInnerRepertory()
	{	
		$user_id = $_REQUEST['user_id'];//当前用户ID

		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$start_date = $_REQUEST['start_date'];//开始时间
		$end_date = $_REQUEST['end_date'];//结束时间
		$type = $_REQUEST['status'];//入库方式 1 新增入库 2 退货入库
		$repertory_id = $_REQUEST['repertory_id'];//仓库ID
		$in_storage_sn = $_REQUEST['in_storage_sn'];//入库订单编号
		//判断权限
		$user_name = $this->AutoCheckRole($user_id,'库管');
		// 拼接时间
		if ($start_date && $end_date) {
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$start_time=strtotime($start_date);
			$end_time=strtotime($end_date);
			// 时间条件
			$maps['tp_in_storage_stocks.create_time'] = array(array('gt',$start_time),array('lt',$end_time));
		}
		if (!$page) {
			$page = 1;
		}else{
			$page = $_REQUEST['page'];
		}
		if (!$page_count) {
			$page_count = 10;
		}
		$start=($page-1)*$page_count;
		
		// 入库方式筛选条件
		if ($type) {
			if ($type==1) {
				$maps['tp_in_storage_stocks.in_storage_type'] = 0;
			}else{
				$maps['tp_in_storage_stocks.in_storage_type'] = 1;
			}
		}
		// 仓库筛选条件
		if ($repertory_id) {
			$maps['tp_in_storage_stocks.repertory_id'] = $repertory_id;
		}
		//搜索编号查询
		if ($in_storage_sn) {
			$maps['tp_in_storage_stocks.in_storage_sn'] = array('like',"%".$in_storage_sn."%");
		}
		$result=D("in_storage_stocks")->join('tp_warehousing_detail ON tp_in_storage_stocks.id = tp_warehousing_detail.storage_stocks_id','right')->join('tp_goods on tp_warehousing_detail.goods_id=tp_goods.goods_id','left')->field('tp_in_storage_stocks.*,tp_warehousing_detail.*,tp_goods.spu')->where($maps)->order('tp_warehousing_detail.storage_stocks_id desc')->limit($start.','.$page_count)->select();
		$list = array();
		$orderInfo = array();
		$details = array();
		$_last_order_id = 0;//定义标识
		$create_time = 0;//定义标识

		$repertory_id = 0;//定义标识 仓库ID
		foreach ($result as $key => $val) {
			if ($val['in_storage_sn'] != $_last_order_id && $key!=0) {//不是同一个订单的商品
					$orderInfo['details'] = $details;
					$orderInfo['in_storage_sn'] = $_last_order_id;
					$orderInfo['create_time'] = $create_time;
					$repertory_name = M('repertory')->where('id='.$repertory_id)->getField('repertory_name');
					$orderInfo['repertory_name'] = $repertory_name;
					array_push($list, $orderInfo);
					$details = array();					
			}
			$_last_order_id = $val['in_storage_sn'];//标识更新 订单号 in_storage_sn
			$create_time = $val['create_time'];//标识更新 create_time
			$repertory_id = $val['repertory_id'];//标识更新
			array_push($details, $val);
			if ($key==count($result)-1) {
				$orderInfo['details'] = $details;
				$orderInfo['in_storage_sn'] = $_last_order_id;
				$orderInfo['create_time'] = $create_time;
				$repertory_name = M('repertory')->where('id='.$repertory_id)->getField('repertory_name');
				$orderInfo['repertory_name'] = $repertory_name;
				array_push($list, $orderInfo);
			}
		}
		//获取库存总件数
		// $total_count = M('stock')->where('stock_type=1')->sum('number');
		$total_count = D("in_storage_stocks")->join('tp_warehousing_detail ON tp_in_storage_stocks.id = tp_warehousing_detail.storage_stocks_id','right')->where($maps)->sum('count');
		$obj = new \StdClass();
		$obj->total_count = $total_count;//库存总件数
		$obj->list = $list;
		if($list){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}



	/*
	 *新建转库
	 *uid     用户ID
	 *repertory_id_outer  转出仓库ID
	 *repertory_id_inner  转入仓库ID
	 *goods_list
	 */
	
	public function stock_transfer()
	{	
		$user_id = $_REQUEST['user_id'];
		$repertory_id_outer = $_REQUEST['repertory_id_outer'];//转出ID
		$repertory_id_inner = $_REQUEST['repertory_id_inner'];//转入ID

		$in_storage_type = 2;//入库类型
		$goods_list = $_REQUEST['goods_list'];//货物列表
		//判断权限
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}

		$user_name = $this->AutoCheckRole($user_id,'库管');
		$arr = json_decode($goods_list);//确认发货信息  数组
		$length = count($arr);
		// 查看库存是否充足
		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = $repertory_id_outer;			 	//仓库ID
			$goods_num = $arr[$i]->count;						//商品数量
			$stock_type = 1;									//门店库存标识
			$this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
		}

		// 新增入库记录
		$data['in_storage_type'] = $in_storage_type;
		//入库单编号
		$sn1=date('YmdHis',time());
        $num=str_pad($user_id,6,"0",STR_PAD_LEFT); 
        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

		$data['in_storage_sn'] = $sn;//入库单编号
		$data['repertory_id'] = $repertory_id_inner;//入库仓库ID
		$data['repertory_id_outer'] = $repertory_id_outer;//出库仓库ID
		$data['create_time'] = time();
		$data['creator'] = $user_name;
		$result = M('in_storage_stocks')->add($data);
		if (!$result) {
			$news = array('code' =>0 ,'msg'=>'转入失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
		// 出库表数据
		$da['in_storage_sn'] = $sn;//入库单编号
		$da['in_storage_type'] = 1;//转库类型 0 正常出库 1转库出库
		$da['repertory_id'] = $repertory_id_outer;
		$da['create_time'] = time();
		$result1 = M('outstock_repertory')->add($da);

		if (!$result1) {
			$news = array('code' =>0 ,'msg'=>'转出失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
		if ($result && $result1) {
			// 新增入库记录详情
			$info['storage_stocks_id'] = $result;	//入库记录ID
			$info1['outstock_id'] = $result1;	//出库记录ID
			$str = '';
			for ($i=0; $i < $length; $i++) { 
				$info['count'] = $arr[$i]->count;					//商品数量
				$info['goods_id'] = $arr[$i]->goods_id;				//商品ID
				$info['goods_name'] = $arr[$i]->goods_name;			//商品名称
				$info['repertory_id'] = $repertory_id_inner;			 	//仓库ID
				$info['create_time'] = time();						//入库时间
				$res = M('warehousing_detail')->add($info);
				if (!$res) {
					$str .= $info['goods_name'].'转库失败!';
					continue;
				}
				//出库表详情（总库）
				$info1['count'] = $arr[$i]->count;						//商品数量
				$info1['goods_id'] = $arr[$i]->goods_id;				//商品ID
				$info1['goods_name'] = $arr[$i]->goods_name;			//商品名称
				$info1['repertory_id'] = $repertory_id_outer;			//仓库ID
				$info1['create_time'] = time();
				$res1 = M('outstock_repertory_detail')->add($info1);

				if (!$res1) {
					$str .= $info1['goods_name'].'转库失败!';
					continue;
				}

				//修改库存数据(减少) 
				$resst = jskc_new($info1['goods_id'],$info1['count'],$repertory_id_outer,1,1);

				if (!$resst) {
					$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
					echo json_encode($news,true);exit;
				}
				//修改库存数据(新增) 
				$resst1 = jskc_new($info1['goods_id'],$info1['count'],$repertory_id_inner,1,2);
				if (!$resst1) {
					$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
					echo json_encode($news,true);exit;
				}

				//新增仓库流水
				addWaterRepertoryRecord($info1['goods_id'],$info1['count'],$repertory_id_outer,2);
				addWaterRepertoryRecord($info1['goods_id'],$info1['count'],$repertory_id_inner,1);
			}
		}
		if(!$str){
			$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>$obj);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
		
	}

	/*
	 *转库列表
	 *user_id     用户ID
	 *repertory_id_outer  转出仓库ID
	 *repertory_id_inner  转入仓库ID
	 */
	public function stock_transfer_list()
	{
		$user_id = $_REQUEST['user_id'];//当前用户ID

		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$repertory_id_outer = $_REQUEST['repertory_id_outer'];//转出ID

		$repertory_id_inner = $_REQUEST['repertory_id_inner'];//转入ID

		//判断权限
		$user_name = $this->AutoCheckRole($user_id,'库管');
		
		if (!$page) {
			$page = 1;
		}else{
			$page = $_REQUEST['page'];
		}
		if (!$page_count) {
			$page_count = 10;
		}
		$start=($page-1)*$page_count;
		//高级搜索条件
		if ($repertory_id_outer) {
			$maps['tp_in_storage_stocks.repertory_id_outer'] = $repertory_id_outer;
		}
		if ($repertory_id_inner) {
			$maps['tp_in_storage_stocks.repertory_id'] = $repertory_id_inner;
		}

		$maps['in_storage_type'] = 2;

		$result=D("in_storage_stocks")->join('tp_warehousing_detail ON tp_in_storage_stocks.id = tp_warehousing_detail.storage_stocks_id','right')->join('tp_goods on tp_warehousing_detail.goods_id=tp_goods.goods_id')->field('tp_in_storage_stocks.*,tp_warehousing_detail.*,tp_goods.spu')->where($maps)->order('tp_warehousing_detail.storage_stocks_id desc')->limit($start.','.$page_count)->select();

		$list = array();
		$orderInfo = array();
		$details = array();
		$_last_order_id = 0;//定义标识
		$create_time = 0;//定义标识

		$repertory_id = 0;//定义标识 仓库ID
		foreach ($result as $key => $val) {
			if ($val['in_storage_sn'] != $_last_order_id && $key!=0) {//不是同一个订单的商品
				$orderInfo['details'] = $details;
				$orderInfo['in_storage_sn'] = $_last_order_id;
				$orderInfo['create_time'] = $create_time;
				$repertory_name = M('repertory')->where('id='.$repertory_id)->getField('repertory_name');
				$orderInfo['repertory_name'] = $repertory_name;
				array_push($list, $orderInfo);
				$details = array();					
			}
			$_last_order_id = $val['in_storage_sn'];//标识更新 订单号 in_storage_sn
			$create_time = $val['create_time'];//标识更新 create_time
			$repertory_id = $val['repertory_id'];//标识更新
			array_push($details, $val);
			if ($key==count($result)-1) {
				$orderInfo['details'] = $details;
				$orderInfo['in_storage_sn'] = $_last_order_id;
				$orderInfo['create_time'] = $create_time;
				$repertory_name = M('repertory')->where('id='.$repertory_id)->getField('repertory_name');
				$orderInfo['repertory_name'] = $repertory_name;
				array_push($list, $orderInfo);
			}
		}
		//获取库存总件数
		// $total_count = M('stock')->where('stock_type=1')->sum('number');
		// $obj = new \StdClass();
		// $obj->total_count = $total_count;//库存总件数
		// $obj->list = $list;
		if($list){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

}