<?php
/**
 * 库管app出库接口
 */
namespace Apis\Controller;
use Think\Controller;

class OutGoodsController extends BaseController {
	/**
	 * 新建出库(返厂)
	 * user_id     操作人ID
	 * repertory_id    仓库ID
	 * goods_list  货物list
	 */
	public function out_storage_stocks()
	{
		$in_storage_type = $_REQUEST['type'];//出库类型 0 正常出库 1转库出库 2 门店发货 3 返回总部
		$repertory_id = $_REQUEST['repertory_id'];//仓库id
		$user_id = $_REQUEST['user_id'];//操作人ID
		$goods_list = $_REQUEST['goods_list'];//货物列表
		$reason = $_REQUEST['reason'];//退货理由

		$arr = json_decode($goods_list);//商品信息  数组
		$length = count($arr);

		//判断权限
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'库管');

		// 查看库存是否充足
		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = $repertory_id;					//门店ID
			$goods_num = $arr[$i]->count;				//商品数量
			$stock_type = 1;							//门店库存标识
			$this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
		}
		//入库单编号
		$sn1=date('YmdHis',time());
        $num=str_pad($user_id,6,"0",STR_PAD_LEFT); 
        $sn="C";
        $sn.=$num;
        $sn.=$sn1;

		// 新增出库记录
        $result = repertory_stock_out($in_storage_type,$sn,$repertory_id,$user_name,$user_id,$reason);
		if (!$result) {
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
		if ($result) {
			// 新增出库记录详情
			$str = '';
			for ($i=0; $i < $length; $i++) { 
				$count = $arr[$i]->count;				//商品数量
				$goods_id = $arr[$i]->goods_id;				//商品ID
				$goods_name = $arr[$i]->goods_name;			//商品名称
				$res = repertory_stock_out_detail($result,$repertory_id,$goods_id,$goods_name,$count);
				if (!$res) {
					$str .= $goods_name.'入库失败!';
					continue;
				}
				//修改库存
				$rtem = jskc_new($goods_id,$count,$repertory_id,1,1);

				// 新增仓库端流水
				addWaterRepertoryRecord($goods_id,$count,$repertory_id,3);
				
				if (!$rtem) {
					$str .= $goods_name.'库存修改失败!';
					continue;
				}
			}
			if(!$str){
				$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
				echo json_encode($news,true);exit;
	        }else{
				$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
				echo json_encode($news,true);exit;
	        }
		}else{
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

	/**
	 * 发货列表
	 * user_id     操作人ID
	 * store_id    门店ID
	 * goods_list  货物ID
	 * return_reason 退货理由
	 */
	public function getOutGoodsList()
	{
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$start_date = $_REQUEST['start_date'];//开始时间
		$end_date = $_REQUEST['end_date'];//结束时间
		$in_storage_type = $_REQUEST['in_storage_type'];//出库类型 0 正常出库 1转库出库 2 门店发货 3 返回总部
		$repertory_id = $_REQUEST['repertory_id'];//仓库ID
		$in_storage_sn = $_REQUEST['in_storage_sn'];//出库订单编号

		// 判断是否有权限操作
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'库管');
		
		// 拼接时间
		if ($start_date && $end_date) {
			$start_date = $start_date.' 00:00:00';
			$end_date = $end_date.' 23:59:59';
			$start_time=strtotime($start_date);
			$end_time=strtotime($end_date);
			// 时间条件
			$maps['tp_outstock_repertory.create_time'] = array(array('gt',$start_time),array('lt',$end_time));
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
		if ($repertory_id) {
			$maps['tp_outstock_repertory.repertory_id'] = $repertory_id;
		}
		// 状态条件
		if ($in_storage_type) {
			$maps['tp_outstock_repertory.in_storage_type'] = $in_storage_type;
		}else{
			$maps['tp_outstock_repertory.in_storage_type'] = array('in',array('2','3'));
		}

		//搜索编号查询
		if ($in_storage_sn) {
			$maps['tp_outstock_repertory.in_storage_sn'] = array('like',"%".$in_storage_sn."%");
		}

		//获取主表数据
		$result=D("outstock_repertory")->where($maps)->order('id desc')->limit($start.','.$page_count)->select();
		//获取附表数据
		foreach ($result as $key => $val) {
			$wh['tp_outstock_repertory_detail.outstock_id'] = $val['id'];
			$result_detail = M('outstock_repertory_detail')->join('tp_goods ON tp_outstock_repertory_detail.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_outstock_repertory_detail.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_outstock_repertory_detail.*,tp_goods.spu,tp_goods.goods_name,tp_spec_goods_price.sku')->select();
			foreach ($result_detail as $key1 => $value) {
				$result_detail[$key1]['goods_num'] = $value['count'];
			}
			$result[$key]['details'] = $result_detail;

			//申请人名称
			$user_name = M('admin')->where('admin_id='.$val['creator_id'])->getField('name');

			$result[$key]['user_name'] = $user_name;
			//仓库名称
			if ($val['repertory_id']) {
				$repertory_name = M('repertory')->where('id='.$val['repertory_id'])->getField('repertory_name');
			}
			$result[$key]['repertory_name'] = $repertory_name?$repertory_name:'';
		}

		// 总符合条件的订单数
		$total_order = M('outstock_repertory')->where($maps)->count();
		// 符合条件的总数量
		$total_num = D("outstock_repertory")->join('tp_outstock_repertory_detail ON tp_outstock_repertory.id = tp_outstock_repertory_detail.outstock_id','right')->where($maps)->sum('count');

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
	 * 新建出库(库管直接发货至门店)
	 * user_id     操作人ID
	 * repertory_id    仓库ID
	 * goods_list  货物list
	 */
	public function out_storage_store()
	{
		$in_storage_type = $_REQUEST['type'];//出库类型 0 正常出库 1转库出库 2 门店发货 3 返回总部
		// $repertory_id = $_REQUEST['repertory_id'];//仓库id
		$user_id = $_REQUEST['user_id'];//操作人ID
		$goods_list = $_REQUEST['goods_list'];//货物列表
		$store_id = $_REQUEST['store_id'];//门店ID

		$arr = json_decode($goods_list);//商品信息  数组
		$length = count($arr);

		//判断权限
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'库管');

		// 查看库存是否充足
		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = $arr[$i]->repertory_id;				//仓库ID
			$goods_num = $arr[$i]->count;				//商品数量
			$stock_type = 1;							//门店库存标识
			$this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
		}
		//入库单编号
		$sn1=date('YmdHis',time());
        $num=str_pad($user_id,6,"0",STR_PAD_LEFT); 
        $sn="C";
        $sn.=$num;
        $sn.=$sn1;


        //开启事务
        $tranDb = M();
		$tranDb->startTrans();
        

        //新建订货申请
		$data['store_id'] = $store_id; 
		$data['admin_id'] = $user_id;  //默认为库管名称
		$data['status'] = 4;//状态 默认为1 新增补货时 未确认  
		$data['create_time'] = time();//创建时间
		$data['type'] = 1;//0 正常补货 1 库管直接出库到门店
		// 编号
        $data['replenishment_order'] = $sn;//订单编号
		$replenishment_id = M("replenishment")->add($data);

		if (!$replenishment_id) {
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
		}

		// 新增出库记录
        $result = repertory_stock_out($in_storage_type,$sn,0,$user_name,$user_id,'库管直接发货至门店');

		if (!$result) {
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
		if ($result) {
			
			$str = '';
			for ($i=0; $i < $length; $i++) { 

				// 新增出库记录详情
				$count = $arr[$i]->count;				//商品数量
				$goods_id = $arr[$i]->goods_id;				//商品ID
				$goods_name = $arr[$i]->goods_name;			//商品名称
				$resource_id = $arr[$i]->repertory_id;				//仓库ID
				$res = repertory_stock_out_detail($result,$resource_id,$goods_id,$goods_name,$count);

				if (!$res) {
					$str .= $goods_name.'入库失败!';
					continue;
				}

				//新增补货申请详情表库管修改之后终表
				$item1['goods_id'] = $goods_id;//商品ID
				// 判断是否符合条件
				$item1['goods_num'] = $count;//商品数量
				$item1['goods_price'] = $arr[$i]->price;//商品价格
				$item1['goods_name'] = $goods_name;//商品价格
				$item1['goods_sn'] = $arr[$i]->goods_sn;//商品价格
				$item1['create_time'] = time();//新增时间
				$item1['replenishment_order_id'] = $replenishment_id;//申请表ID

				$flag1 = M('replenishment_detail_final')->add($item1);

				//如果添加失败
				if (!$flag1) {
					$str .= $goods_name.'订货申请订单详情表新增失败!';
					continue;
				}

				//修改库存
				$rtem = jskc_new($goods_id,$count,$resource_id,1,1);
				//如果修改库存失败
				if (!$rtem) {
					$str .= $goods_name.'修改库存失败!';
					continue;
				}
				// 新增仓库端流水
				$water = addWaterRepertoryRecord($goods_id,$count,$resource_id,2);
				
				//如果仓库端流水
				if (!$water) {
					$str .= $goods_name.'添加仓库流水失败!';
					continue;
				}

				//新增在途数据
				$uzr['stock_type'] = 2;
				$uzr['good_id'] = $goods_id;
				$uzr['resource_id'] = $replenishment_id;//订货的ID
				$uzr['number'] = $count;
				$uzr['create_time'] = time();
				$uzr['creator'] = $user_name;
				$res11 = $tranDb->table('tp_stock')->add($uzr);


				if (!$rtem) {
					$str .= $goods_name.'库存修改失败!';
					continue;
				}
			}

			if(!$str){
				$tranDb->commit();
				$arr1 = array();
				//门店名称
				$store_name = getStoreName($store_id);
				$arr1['store_name'] = $store_name;

				$tp_maps['tp_replenishment.id'] = $replenishment_id;
				$result=D("replenishment")->join('tp_replenishment_detail_final ON tp_replenishment.id = tp_replenishment_detail_final.replenishment_order_id','right')->join('tp_goods ON tp_replenishment_detail_final.goods_id = tp_goods.goods_id','left')->where($tp_maps)->field('tp_replenishment_detail_final.*,tp_replenishment.*,tp_goods.spu')->order('tp_replenishment_detail_final.replenishment_order_id desc')->limit($start.','.$page_count)->select();
				foreach ($result as $key => $val) {
					$result[$key]['sku'] = getSku($val['goods_id']);
				}
				$arr1['detail'] = $result;
				$arr1['order_sn'] = $sn;
				//创建时间
				$arr1['create_time'] = $item1['create_time'];

				$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>$arr1);
				echo json_encode($news,true);exit;
	        }else{
	        	$tranDb->rollback();
				$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
				echo json_encode($news,true);exit;
	        }
		}else{
			$tranDb->rollback();
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

	/**
	 * 获取仓库端直接发货至仓库列表
	 */
	public function getStoreReplenList()
	{	
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$store_id = $_REQUEST['store_id'];// 门店ID
		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$start_date = $_REQUEST['start_date'];//开始时间
		$end_date = $_REQUEST['end_date'];//结束时间
		$status = $_REQUEST['status'];//状态
		$order_sn = $_REQUEST['order_sn'];//订单编号

		// 拼接时间
		if ($start_date || $end_date) {
			if ($start_date && $end_date) {
				$start_date = $start_date.' 00:00:00';
				$end_date = $end_date.' 23:59:59';
				$start_time=strtotime($start_date);
				$end_time=strtotime($end_date);
				// 时间条件
				$maps['tp_replenishment.create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			}else if($start_date && !$end_date){
				$start_date = $start_date.' 00:00:00';
				$start_time=strtotime($start_date);
				// 时间条件
				$maps['tp_replenishment.create_time'] = array(array('gt',$start_time));
			}else if (!$start_date && $end_date) {
				$end_date = $end_date.' 23:59:59';
				$end_time=strtotime($end_date);
				// 时间条件
				$maps['tp_replenishment.create_time'] = array(array('lt',$end_time));
			}
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

		//门店搜索
		if ($store_id) {
			$maps['tp_replenishment.store_id'] = $store_id;
		}

		// 状态条件
		if ($status) {
			$maps['tp_replenishment.status'] = $status;
		}else{
			$maps['tp_replenishment.status'] = array('in','4,5,6');
		}

		//订单编号搜索
		if ($status) {
			$maps['tp_replenishment.replenishment_order']=array('like',"%".$order_sn."%");
		}

		//获取仓库端直接发货列表条件 0 正常补货 1 库管直接发货
		$maps['tp_replenishment.type'] = 1;

		//获取主表数据
		$result=D("replenishment")->where($maps)->order('id desc')->limit($start.','.$page_count)->select();

		//获取附表数据
		foreach ($result as $key => $val) {
			$wh['tp_replenishment_detail_final.replenishment_order_id'] = $val['id'];
			$result_detail = M('replenishment_detail_final')->join('tp_goods ON tp_replenishment_detail_final.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_replenishment_detail_final.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_replenishment_detail_final.*,tp_goods.spu,tp_goods.goods_name,tp_spec_goods_price.sku')->select();
			$result[$key]['details'] = $result_detail;
			$result[$key]['replenishment_order_id'] = $val['id'];
			//申请人名称
			$user_name = M('admin')->where('admin_id='.$val['admin_id'])->getField('name');

			$result[$key]['user_name'] = $user_name;
			//门店名称
			if ($val['store_id']) {
				$result[$key]['store_name'] = getStoreName($val['store_id'])?getStoreName($val['store_id']):'';
			}
		}
		// 总符合条件的订单数
		$total_order = M('replenishment')->where($maps)->count();
		// 符合条件的总数量
		$total_num = D("replenishment")->join('tp_replenishment_detail_final ON tp_replenishment.id = tp_replenishment_detail_final.replenishment_order_id','right')->where($maps)->sum('goods_num');

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



}