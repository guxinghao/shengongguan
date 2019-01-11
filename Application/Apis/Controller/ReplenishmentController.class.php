<?php
/**
 * 补货管理
 */
namespace Apis\Controller;
use Think\Controller;

class ReplenishmentController extends BaseController {
	/**
	 * 新增订货订单
	 */
	public function add_replenishment()
	{		
		$store_id = $_REQUEST['store_id'];//门店ID
		$admin_id = $_REQUEST['admin_id'];//申请人ID
		$getData = $_REQUEST['data'];//新增货物信息
		$force_add = $_REQUEST['force_add'];//是否强制提交

		$arr = json_decode($getData);//新增货物信息  数组

		// 判断库存数量
		$len = count($arr);
		for ($i=0; $i < $len; $i++) { 
			$goods_id = $arr[$i]->goods_id;//商品ID
			$goods_num = $arr[$i]->count;//商品数量
			$stock_type = 1;//门店库存标识
			$re11 = checkAllGoodsNum($goods_id,$goods_num,1);
			if (!$re11) {
				$news = array('code' =>2 ,'msg'=>'总库存不足','data'=>null);
				echo json_encode($news,true);exit;
			}
		}
		//判断商品上下限
		if (!$force_add) {
			$len = count($arr);
			$message = array();
			for ($i=0; $i < $len; $i++) { 
				// $force_add 0 不强制提交 1 强制提交
				// 判断货物上限下限
				$mmp['store_id'] = $store_id;
				$mmp['goods_id'] = $arr[$i]->goods_id;//商品ID

				$goods_num = $arr[$i]->count;//商品数量

				$goods_limit = M('goods_limit')->field('min_count,max_count')->where($mmp)->find();
				//查询当前库存
				$where['stock_type'] = 4;
				$where['resource_id'] = $store_id;
				$where['good_id'] = $arr[$i]->goods_id;//商品ID
				$goods_number = M('stock')->where($where)->getField('number');

				//当前数量加订购数量之和
				$this_number = $goods_num + $goods_number;

				$min_count = $goods_limit['min_count'];//该商品的下限
				$max_count = $goods_limit['max_count'];//该商品的上限
				if ($goods_limit) {
					$sku = M('spec_goods_price')->where('goods_id='.$arr[$i]->goods_id)->getField('sku');
					//如果之和小于下限 
					if ($this_number < $min_count) {
						$message[] = '商品'.$sku.'低于安全库存！';
						// $news = array('code' =>0 ,'msg'=>'有商品低于下限！','data'=>null);
						// echo json_encode($news,true);exit;
					}else if ($this_number > $max_count) {
						$message[] = '商品'.$sku.'高于安全库存！';
						// $news = array('code' =>0 ,'msg'=>'有商品高于上限！','data'=>null);
						// echo json_encode($news,true);exit;
					}
				}
			}
			if (count($message)) {
				$messageStr = implode('_', $message);
				$news = array('code' =>0 ,'msg'=>$messageStr,'data'=>null);
				echo json_encode($news,true);exit;
			}
		}

		$data['store_id'] = $store_id; 
		$data['admin_id'] = $admin_id; 
		$data['status'] = 1;//状态 默认为1 新增补货时 未确认  
		$data['create_time'] = time();//创建时间
		// 编号
		$sn1 = date('YmdHis',time());
        $num = str_pad($uid,6,"0",STR_PAD_LEFT); 
        $sn = "C";
        $sn .= $num;
        $sn .= $sn1;
        $data['replenishment_order'] = $sn;//订单编号
		$res = M("replenishment")->add($data);
		if ($res) {
			// 操作补货详情表
			$item['replenishment_order_id'] = $res;//申请表ID
			$len = count($arr);
			$str = '';
			$info = array();
			for ($i=0; $i < $len; $i++) { 
				$item['goods_id'] = $arr[$i]->goods_id;//商品ID
				// 判断是否符合条件
				$item['goods_num'] = $arr[$i]->count;//商品数量
				$item['goods_price'] = $arr[$i]->price;//商品价格
				$item['goods_name'] = $arr[$i]->goods_name;//商品价格
				$item['goods_sn'] = $arr[$i]->goods_sn;//商品价格
				$item['create_time'] = time();//新增时间
				$flag = M('replenishment_detail')->add($item);
				$flag1 = M('replenishment_detail_final')->add($item);
				if (!$flag || !$flag1) {
					$str .= '保存失败!';
				}
			}
			
			// str 为空  保存成功 不为空 保存失败
			if ($str) {
				$news = array('code' =>0 ,'msg'=>'保存失败！','data'=>null);
				echo json_encode($news,true);exit;
			}else{
				$news = array('code' =>1 ,'msg'=>'新增成功！','data'=>null);
				echo json_encode($news,true);exit;
			}
		}
	}

	/**
	 * 获取门店入库确认列表
	 */
	public function getFinalReplenList()
	{	
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$store_id = $_REQUEST['store_id'];//门店ID
		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$start_date = $_REQUEST['start_date'];//开始时间
		$end_date = $_REQUEST['end_date'];//结束时间
		$status = $_REQUEST['status'];//状态
		// 拼接时间
		if ($start_date && $end_date) {
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$start_time=strtotime($start_date);
			$end_time=strtotime($end_date);
			// 时间条件
			$maps['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$maps1['tp_replenishment.create_time'] = array(array('gt',$start_time),array('lt',$end_time));
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
		if ($store_id) {
			$maps['store_id'] = $store_id;
			$maps1['tp_replenishment.store_id'] = $store_id;
		}
		// 状态条件
		if ($status) {
			$maps['status'] = $status;
			$maps1['tp_replenishment.status'] = $status;
		}else{
			$maps['status'] = array('in','4,5,6');
			$maps1['tp_replenishment.status'] = array('in','4,5,6');

		}
		//获取主表订单
		$result = D("replenishment")->where($maps)->order('id desc')->limit($start.','.$page_count)->select();
		//获取子表数据
		foreach ($result as $key => $val) {
			$wh['tp_replenishment_detail_final.replenishment_order_id'] = $val['id'];
			$result_detail = M('replenishment_detail_final')->join('tp_goods ON tp_replenishment_detail_final.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_replenishment_detail_final.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_replenishment_detail_final.*,tp_goods.spu,tp_spec_goods_price.sku')->select();
			$result[$key]['details'] = $result_detail;

			//申请人名称
			$user_name = M('admin')->where('admin_id='.$val['admin_id'])->getField('name');
			$result[$key]['user_name'] = $user_name;
			//replenishment_order_id   ID
			$result[$key]['replenishment_order_id'] = $val['id'];
			//门店名称
			$result[$key]['store_name'] = getStoreName($val['store_id']);
		}

		
		// 总符合条件的订单数
		$total_order = M('replenishment')->where($maps)->count();
		// 符合条件的总数量
		$total_num = D("replenishment")->join('tp_replenishment_detail_final ON tp_replenishment.id = tp_replenishment_detail_final.replenishment_order_id','right')->where($maps1)->sum('goods_num');

		$obj = new \StdClass();
		$obj->total_order = $total_order;
		$obj->total_num = $total_num;
		$obj->list = $result;
		if($result){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}


	/**
	 * 获取订货申请列表(门店)(库管)
	 */
	public function getReplenList()
	{	
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$store_id = $_REQUEST['store_id'];//门店ID
		// $store_id = 21;//门店ID
		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$start_date = $_REQUEST['start_date'];//开始时间
		$end_date = $_REQUEST['end_date'];//结束时间
		$status = $_REQUEST['status'];//状态
		// 拼接时间
		if ($start_date && $end_date) {
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$start_time=strtotime($start_date);
			$end_time=strtotime($end_date);
			// 时间条件
			$maps['tp_replenishment.create_time'] = array(array('gt',$start_time),array('lt',$end_time));
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
		if ($store_id) {
			$maps['tp_replenishment.store_id'] = $store_id;
		}
		// 状态条件
		if ($status) {
			$maps['tp_replenishment.status'] = $status;
		}

		//如果为门店查看零时表  即有门店ID 则不显示库管直接发货至门店
		if ($store_id) {
			$maps['tp_replenishment.type'] = 0;
		}

		//获取主表订单
		$result = D("replenishment")->where($maps)->order('id desc')->limit($start.','.$page_count)->select();
		// var_dump($result);die();
		// 如果门店ID存在  则为门店端入库列表 查询零时表    否则为库管端入库列表 查询最终表
		if ($store_id) {
			//获取 子表数据
			foreach ($result as $key => $val) {
				$wh['tp_replenishment_detail.replenishment_order_id'] = $val['id'];
				$result_detail = M('replenishment_detail')->join('tp_goods ON tp_replenishment_detail.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_replenishment_detail.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_replenishment_detail.*,tp_goods.spu,tp_spec_goods_price.sku')->select();
				$result[$key]['details'] = $result_detail;

				//申请人名称
				$user_name = M('admin')->where('admin_id='.$val['admin_id'])->getField('name');
				$result[$key]['user_name'] = $user_name;
				//replenishment_order_id   ID
				$result[$key]['replenishment_order_id'] = $val['id'];
				//门店名称
				$result[$key]['store_name'] = getStoreName($val['store_id']);
			}
			$total_num = D("replenishment")->join('tp_replenishment_detail ON tp_replenishment.id = tp_replenishment_detail.replenishment_order_id','right')->where($maps)->sum('goods_num');
		}else{
			//获取子表数据
			foreach ($result as $key => $val) {
				$wh['tp_replenishment_detail_final.replenishment_order_id'] = $val['id'];
				$result_detail = M('replenishment_detail_final')->join('tp_goods ON tp_replenishment_detail_final.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_replenishment_detail_final.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_replenishment_detail_final.*,tp_goods.spu,tp_spec_goods_price.sku')->select();
				$result[$key]['details'] = $result_detail;

				//申请人名称
				$user_name = M('admin')->where('admin_id='.$val['admin_id'])->getField('name');
				$result[$key]['user_name'] = $user_name;
				//replenishment_order_id   ID
				$result[$key]['replenishment_order_id'] = $val['id'];
				//门店名称
				$result[$key]['store_name'] = getStoreName($val['store_id']);
			}
			$total_num = D("replenishment")->join('tp_replenishment_detail_final ON tp_replenishment.id = tp_replenishment_detail_final.replenishment_order_id','right')->where($maps)->sum('goods_num');
		}

		// 总符合条件的订单数
		$total_order = M('replenishment')->where($maps)->count();

		$obj = new \StdClass();
		$obj->total_order = $total_order;
		$obj->total_num = $total_num;
		$obj->list = $result;
		if($result){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

	/**
	 * 确认订货订单(库管)
	 */
	public function trueReplenList()
	{
		$goods_list = $_REQUEST['goods_list'];//订单详情列表
		
		$user_id = $_REQUEST['user_id'];//当前用户ID

		$del_id = $_REQUEST['deleted_goods'];//删除记录的ID
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
		$replenishment_order_id = $_REQUEST['replenishment_order_id'];//当前订单ID

		//判断订货订单状态
		$thisStatus = M('replenishment')->where('id='.$replenishment_order_id)->getField('status');
		if ($thisStatus==3) {
			$news = array('code' =>0 ,'msg'=>'改订单已确认！','data'=>null);
			echo json_encode($news,true);exit;
		}

		$arr = json_decode($goods_list);//新增货物信息  数组
		$len = count($arr);

		// 查看库存是否充足
		for ($i=0; $i < $len; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = 0;							//0 所有仓库
			$goods_num = $arr[$i]->goods_num;				//商品数量
			$stock_type = 1;							//门店库存标识
			$this->checkAllGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
		}

		//如果存在删掉的ID 则删除记录
		if ($del_id) {
			$res = M('replenishment_detail_final')->delete($del_id);
			if (!$res) {
				$news = array('code' =>0 ,'msg'=>'删除失败！','data'=>null);
	        	echo json_encode($news,true);exit;
			}
			
		}

		$item = array();
		$str = '';
		for ($i=0; $i < $len; $i++) { 
			$item['detail_id'] = $arr[$i]->detail_id;//记录ID

			$item['goods_num'] = $arr[$i]->goods_num;//商品数量
			$item['goods_id'] = $arr[$i]->goods_id;//商品ID
			$item['goods_name'] = $arr[$i]->goods_name;//商品名称
			$item['update_time'] = time();//修改时间
			// 如果记录ID不存在 则新增数据
			if (!$item['detail_id']) {
				$item['replenishment_order_id'] = $replenishment_order_id;//订单编号
				$getInfo = M('goods')->where('goods_id='.$item['goods_id'])->field('goods_sn, market_price')->find();
				$item['goods_sn'] = $getInfo['goods_sn'];//商品编号
				$item['goods_price'] = $getInfo['market_price'];//商品价钱
				$item['create_time'] = time();//创建时间

				$res = M('replenishment_detail_final')->add($item);
			}else{
				$res = M('replenishment_detail_final')->save($item);
			}

			if (!$res) {
				$str .= '修改失败!';
			}
		}
		if (!$str) {
			$info['id'] = $replenishment_order_id;
			$info['status'] = 3;
			$info['update_time'] = time();
			$info['jingbanren'] = $user_name;//经办人
			$re = M('replenishment')->save($info);
			if ($re) {
				$news = array('code' =>1 ,'msg'=>'确认成功！','data'=>null);
	            echo json_encode($news,true);exit;
			}else{
				$news = array('code' =>0 ,'msg'=>'确认失败！','data'=>null);
	            echo json_encode($news,true);exit;
			}
		}else{
			$news = array('code' =>0 ,'msg'=>'确认失败！','data'=>null);
            echo json_encode($news,true);exit;
		}
		
	}




	/**
	 * 拒绝订货申请(库管)
	 */
	public function refuseReplen()
	{
		$order_id = $_REQUEST['replenishment_order_id'];//订单ID
		$user_id = $_REQUEST['user_id'];//当前用户ID

		//判断订货订单状态
		$thisStatus = M('replenishment')->where('id='.$order_id)->getField('status');
		if ($thisStatus==2) {
			$news = array('code' =>0 ,'msg'=>'改订单已拒绝！','data'=>null);
			echo json_encode($news,true);exit;
		}
		
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
		$info['id'] = $order_id;
		$info['status'] = 2;
		$info['update_time'] = time();
		$info['jingbanren'] = $user_name;//经办人
		$res = M('replenishment')->save($info);
		if ($res) {
			$news = array('code' =>1 ,'msg'=>'确认成功！','data'=>null);
            echo json_encode($news,true);exit;
		}else{
			$news = array('code' =>0 ,'msg'=>'确认失败！','data'=>null);
            echo json_encode($news,true);exit;
		}
	}


	/**
	 * 订货申请发货
	 */
	public function sendReplen()
	{	
		$order_id = $_REQUEST['replenishment_order_id'];//订单ID
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$getData = $_REQUEST['goods_list'];//商品列表		
		$arr = json_decode($getData);//确认发货信息  数组
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

		//判断订货订单状态
		$thisStatus = M('replenishment')->where('id='.$order_id)->getField('status');
		if ($thisStatus==4) {
			$news = array('code' =>0 ,'msg'=>'该订单已操作！','data'=>null);
			echo json_encode($news,true);exit;
		}

		$length = count($arr);
		$where['stock_type'] = 1;

		// 判断仓库库存数量是否可操作
		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;//商品ID
			$goods_num = $arr[$i]->count;//商品数量
			$repertory_id = $arr[$i]->repertory_id;//仓库ID
			$stock_type = 1;//门店库存标识
			$this->checkGoodsNum($goods_id,$goods_num,$repertory_id,$stock_type);
		}

		// 修改总库存数量
		$str = '';
		$tranDb = M();
		$tranDb->startTrans();

		for ($i=0; $i < $length; $i++) { 
			$where['good_id'] = $arr[$i]->goods_id;	//商品ID
			$num = $arr[$i]->goods_num;				//商品数量
			$where['resource_id'] = $arr[$i]->repertory_id;//仓库ID
			$ppt = $tranDb->table('tp_stock')->field('id, number')->where($where)->select();//原始数量
			$oldNum = $ppt[0]['number'];
			$data['id'] = $ppt[0]['id'];
			$data['number'] = $oldNum-$num;
			$data['update_time'] = time();
			if ($data['number']<0) {
				$news = array('code' =>0 ,'msg'=>'库存不足！','data'=>null);
	            echo json_encode($news,true);exit;
			}
			$rtem = $tranDb->table('tp_stock')->save($data);

			if (!$rtem) {
				$str .= '修改失败!';
			}else{
				//新增仓库流水
				addWaterRepertoryRecord($where['good_id'],$num,$where['resource_id'],2);

				//新增在途数据
				$uzr['stock_type'] = 2;
				$uzr['good_id'] = $where['good_id'];
				$uzr['resource_id'] = $order_id;//订货的ID
				$uzr['number'] = $num;
				$uzr['create_time'] = time();
				$uzr['creator'] = $user_name;
				$res = $tranDb->table('tp_stock')->add($uzr);
			}
		}

		if (!$str) {
			$info['id'] = $order_id;
			$info['status'] = 4;
			$info['update_time'] = time();
			$info['operator'] = $user_name;//出库人
			$res = $tranDb->table('tp_replenishment')->save($info);//修改订单状态
			if ($res) {
				$tranDb->commit();
				$news = array('code' =>1 ,'msg'=>'发货成功！','data'=>null);
	            echo json_encode($news,true);exit;
			}else{
				$tranDb->rollback();
				$news = array('code' =>0 ,'msg'=>'发货失败！','data'=>null);
	            echo json_encode($news,true);exit;
			}
		}else{
			$tranDb->rollback();
			$news = array('code' =>0 ,'msg'=>'发货失败！','data'=>null);
            echo json_encode($news,true);exit;
		}
		
	}


	/**
	 * 拒收接收入库
	 */
	public function rejectInnerStock()
	{	
		$order_id = $_REQUEST['replenishment_order_id'];//订单ID
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$getData = $_REQUEST['goods_list'];//商品列表
		
		$arr = json_decode($getData);//确认发货信息  数组
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
		
		//判断订货订单状态
		$thisStatus = M('replenishment')->where('id='.$order_id)->getField('status');
		if ($thisStatus==7) {
			$news = array('code' =>0 ,'msg'=>'该订单已操作！','data'=>null);
			echo json_encode($news,true);exit;
		}


		// 修改总库存数量
		$length = count($arr);
		$where['stock_type'] = 1;
		$str = '';
		for ($i=0; $i < $length; $i++) { 
			$where['good_id'] = $arr[$i]->goods_id;	//商品ID
			$num = $arr[$i]->goods_num;				//商品数量
			$where['resource_id'] = $arr[$i]->repertory_id;//仓库ID
			$ppt = M('stock')->field('id, number')->where($where)->select();//原始数量
			//原始仓库有数据则添加  否则 新增
			if ($ppt) {
				$oldNum = $ppt[0]['number'];
				$data['id'] = $ppt[0]['id'];
				$data['number'] = $oldNum+$num;
				$rtem = M('stock')->save($data);
			}else{
				$where['number'] = $arr[$i]->goods_num;
				$where['create_time'] = time();
				$where['creator'] = $user_name;
				$rtem = M('stock')->add($where);
			}
			if (!$rtem) {
				$str .= '修改失败!';
			}else{

				//新增仓库流水
				addWaterRepertoryRecord($where['good_id'],$num,$where['resource_id'],1);

				//删除退货在途数据 且添加至库存记录表中
				$ims['stock_type'] = 3;
				$ims['resource_id'] = $order_id;
				$ims['good_id'] = $where['good_id'];
				$ltem = M('stock');
				$ite = $ltem->where($ims)->find();
				$ite_id = $ite['id'];
				unset($ite['id']);
				// 添加至库存记录表中
				$results = M('stock_record')->add($ite);
				if ($results) {
					$ltem->where('id='.$ite_id)->delete();
				}
				
			}
		}
		if (!$str) {
			$info['id'] = $order_id;
			$info['status'] = 7;
			$info['update_time'] = time();
			$res = M('replenishment')->save($info);//修改订单状态
			if ($res) {
				$news = array('code' =>1 ,'msg'=>'接收成功！','data'=>null);
	            echo json_encode($news,true);exit;
			}else{
				$news = array('code' =>0 ,'msg'=>'接收失败！','data'=>null);
	            echo json_encode($news,true);exit;
			}
		}else{
			$news = array('code' =>0 ,'msg'=>'接收失败！','data'=>null);
            echo json_encode($news,true);exit;
		}
		
	}


	/**
	 * 签收(门店) | 拒绝签收(门店)
	 */
	public function trueReplenList_store()
	{	
		$replenishment_order_id = $_REQUEST['replenishment_order_id'];//当前订单ID
		$user_id = $_REQUEST['user_id'];//用户ID
		$is_sure = $_REQUEST['is_sure'];//1 签收  2 拒签收
		$store_id = $_REQUEST['store_id'];//门店ID
		$goods_list = $_REQUEST['goods_list'];//门店ID
		
		$arr = json_decode($goods_list);//货物信息  数组
		//判断是否为店长
		if (!empty($user_id)) {
			$roleInfo = M('admin')->where('admin_id='.$user_id)->getField('admin_id, role_id, user_name');
			$role_id = $roleInfo[$user_id]['role_id'];
			$user_name = $roleInfo[$user_id]['user_name'];
			if ($role_id !=4 ) {
				$news = array('code' =>0 ,'msg'=>'无权限此操作！','data'=>null);
				echo json_encode($news,true);exit;
			}
		}else{
			$news = array('code' =>0 ,'msg'=>'无权限此操作！','data'=>null);
			echo json_encode($news,true);exit;
		}

		//判断订货订单状态
		$thisStatus = M('replenishment')->where('id='.$replenishment_order_id)->getField('status');
		if ($thisStatus==5) {
			$news = array('code' =>0 ,'msg'=>'该订单已操作！','data'=>null);
			echo json_encode($news,true);exit;
		}else if ($thisStatus==6) {
			$news = array('code' =>0 ,'msg'=>'该订单已操作！','data'=>null);
			echo json_encode($news,true);exit;
		}


		if ($is_sure==1) {
			$data['status'] = 5;//签收
		}else{
			$data['status'] = 6;//拒签收
		}
		$data['id'] = $replenishment_order_id;
		$data['update_time'] = time();
		$res = M('replenishment')->save($data);
		
		if (!$res) {
			$news = array('code' =>0 ,'msg'=>'操作失败!','data'=>null);
            echo json_encode($news,true);exit;
		}
		
		//删除在途数据   保存库存记录表数据
		$whe['resource_id'] = $replenishment_order_id;
		$whe['stock_type'] = 2;
		$addRes = M('stock')->where($whe)->select();
		foreach ($addRes as $key => $val) {
			unset($addRes[$key]['id']);
		}
		$re = M('stock_record')->addAll($addRes);
		$resu1 = M('stock')->where($whe)->delete();

		if (!$resu1 || !$re) {
			$news = array('code' =>0 ,'msg'=>'操作失败!','data'=>null);
            echo json_encode($news,true);exit;
		}


		if ($res && $is_sure==1) {
			// 确认签收 新增库存
			$ttm['in_storage_type'] = 0;
			$ttm['store_id'] = $store_id;
			$ttm['replenishment_id'] = $replenishment_order_id;
			$ttm['create_time'] = time();
			$ttm['creator'] = $user_name;
			$bbs = M('stocks_store')->add($ttm);
			if ($bbs) {
				$length = count($arr);
				$item['storage_stocks_id'] = $bbs;//入库表id
				$str = '';
				for ($i=0; $i < $length; $i++) { 
					// 新增门店入库详情表数据
					$item['goods_id'] = $arr[$i]->goods_id;//商品ID
					$item['count'] = $arr[$i]->goods_num;//商品数量
					$item['goods_name'] = $arr[$i]->goods_name;//商品名称
					$item['create_time'] = time();//商品价格
					$tty = M('warehousing_detail_store')->add($item);
					if (!$tty) {
						$str .= '操作失败';
					}else{

						//修改库存
						$rtem = jskc_new($arr[$i]->goods_id,$arr[$i]->goods_num,$store_id,4,2);

						/*// 新增门店库存表数据
						$where1['stock_type'] = 4;
						$where1['good_id'] = $item['goods_id'];//商品ID
						$where1['resource_id'] = $store_id;//门店ID
						// 判断是否存在  存在则修改数量  不存在则新增
						$result1 = M('stock')->where($where1)->find();
						if ($result1) {
							$data1['id'] = $result1['id'];
							$old_num = $result1['number'];//原始数量
							$data1['number'] = $item['count']+$old_num;
							$data1['update_time'] = time();
							$data1['creator'] = $user_name;//操作人
							$res = M('stock')->save($data1);
						}else{
							$where1['number'] = $item['count'];//数量
							$where1['create_time'] = time();//时间
							$where1['creator'] = $user_name;//操作人
							$res = M('stock')->add($where1);
						}*/

						// 新增门店流水记录表数据
						addWaterRecord($item['goods_id'],$item['count'],$store_id,1);
					}
				}
			}
		}

		// 新增退货在途
		if ($res && $is_sure==2) {
			$length = count($arr);
			$str = '';
			for ($i=0; $i < $length; $i++) { 
				$uzr['stock_type'] = 3;
				$uzr['good_id'] = $arr[$i]->goods_id;
				$uzr['resource_id'] = $replenishment_order_id;//订货的ID
				$uzr['number'] = $arr[$i]->goods_num;//商品数量
				$uzr['create_time'] = time();
				$uzr['creator'] = $user_name;
				$res = M('stock')->add($uzr);
			}
		}
		
		if ($res && !$str) {
			$news = array('code' =>1 ,'msg'=>'确认成功！','data'=>null);
            echo json_encode($news,true);exit;
		}else{
			$news = array('code' =>0 ,'msg'=>'确认失败！','data'=>null);
            echo json_encode($news,true);exit;
		}
		
	}

	//门店实时库存
    public  function real_time_stock(){
        $cid=$_REQUEST['cid'];

        $orderBy = $_REQUEST['orderBy'];//排序  0 正序 1 倒序

        if(empty($_REQUEST['p'])){
            $p=1;
        }else{
            $p=$_REQUEST['p'];
        }

        $maps1['admin_id']=$cid;
        $admin=D("Admin")->where($maps1)->find();
        $pages="15";
        $start=($p-1)*$pages;
        $maps['stock_type'] = 4;
        //如果权限为门店或服务顾问则查询所属门店 为超管等隶属于多个门店 则查询多个门店数据
        if ($admin['role_id']==4 || $admin['role_id']==5) {
			$maps['resource_id'] = $admin['store_id'];
        }else{
        	$maps['resource_id'] = array('in',$admin['store_id']);
        }
		//等于0 的数据不显示
		$maps['tp_stock.number'] = array('GT',0);
		// $selectnum['tp_stock.lock_number'] = array('GT',0);
        // $selectnum['_logic'] = 'or';
        // $maps['_complex'] = $selectnum;
		//获取总条数
		$total_count = D("stock")->where($maps)->count();  
		//获取商品总数
		$total_num1 = D("stock")->where($maps)->sum('number'); //即时库存
		// $total_num2 = D("stock")->where($maps)->sum('lock_number'); //锁定库存
		$total_num = $total_num1;
        if(!empty($_REQUEST['goods_sn'])){
            $maps['tp_spec_goods_price.sku']=array('like',"%".$_REQUEST['goods_sn']."%");
        }
        if ($orderBy) {
        	$list=D("stock")->join('tp_spec_goods_price on tp_stock.good_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_stock.good_id = tp_goods.goods_id','left')->field('tp_stock.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_goods.spu')->where($maps)->order('tp_spec_goods_price.sku desc')->limit($start.','.$pages)->select();  
        }else{
        	$list=D("stock")->join('tp_spec_goods_price on tp_stock.good_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_stock.good_id = tp_goods.goods_id','left')->field('tp_stock.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_goods.spu')->where($maps)->order('tp_spec_goods_price.sku asc')->limit($start.','.$pages)->select(); 
        }
        if($list==false){
            if($p>1){
                $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
                echo json_encode($news,true);exit;
            }
        }

        //判断当前商品数量与商品上下限的关
        foreach ($list as $key => $val) {
        	//添加门店名称
        	$list[$key]['store_name'] = getStoreName($val['resource_id']);

        	$limit['store_id'] = $val['resource_id'];
        	$limit['goods_id'] = $val['good_id'];
        	// $number = $val['number'];//当前商品数量
        	$number = $val['number'];

        	$goods_limit = M('goods_limit')->where($limit)->field('goods_id, min_count, max_count')->find();
        	//如果存在限制则做判断 否则不做判断 is_limit 0 不做变化 1 红色 2 绿色
        	if ($goods_limit) {
        		// 如果小于最低限制 
        		if ($number < $goods_limit['min_count']) {
        			$list[$key]['is_limit'] = 1;
        		// 如果大于最低限制	
        		}elseif($number > $goods_limit['max_count']){
        			$list[$key]['is_limit'] = 2;
        		}else{
        			$list[$key]['is_limit'] = 0;
        		}
        	}else{
        		$list[$key]['is_limit'] = 0;
        	}

        }

       //存储总记录数
        $obj = new \StdClass();
		$obj->total_count = $total_count;//总条数
		$obj->total_num = (int)$total_num;//商品总数量
		$obj->list = $list;
        if($list){
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$obj);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    //门店补货推荐列表
    public  function recommend_store(){
        $store_id=$_REQUEST['store_id'];//门店ID
        $user_id=$_REQUEST['user_id'];//用户ID
        $p=$_REQUEST['page'];//page

        //判断权限
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'店长');

        if(!$p){
            $p=1;
        }

        $pages="10";
        $start=($p-1)*$pages;

       // 获取库存低于下限的商品
       
        $maps['tp_stock.resource_id'] = $store_id;
        $maps['tp_stock.stock_type'] = 4;
        $maps['_string'] = 'tp_stock.number < tp_goods_limit.min_count';
        $list=D("stock")->join('tp_goods_limit on tp_stock.good_id = tp_goods_limit.goods_id AND tp_stock.resource_id = tp_goods_limit.store_id','left')->join('tp_goods on tp_stock.good_id = tp_goods.goods_id','left')->field('tp_goods.goods_id,tp_goods.spu,tp_goods.goods_sn,tp_goods.goods_name,tp_goods.market_price,tp_stock.number,tp_goods_limit.min_count')->where($maps)->order('tp_stock.number asc')->limit($start.','.$pages)->select();
        foreach ($list as $key => $val) {
        	$list[$key]['price'] = $val['market_price'];
        	$recommend_count = $val['min_count']-$val['number'];
        	$list[$key]['count'] = $recommend_count;//推荐数量
        	//查询总部库存商品库存
            if ($val['goods_id']) {
                $whe['good_id'] = $val['goods_id'];
                $whe['stock_type'] = 1;
                $over_number = M('stock')->where($whe)->sum('number');

                $list[$key]['over_number']=$over_number;
            }
        }
       //存储总记录数
        if($list){
            $news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
            echo json_encode($news,true);exit;
        }else{
            $news = array('code' =>1 ,'msg'=>'获取失败！','data'=>null);
            echo json_encode($news,true);exit;
        }
    }
}