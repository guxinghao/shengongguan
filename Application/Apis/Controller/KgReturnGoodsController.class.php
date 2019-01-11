<?php
/**
 * 库管app退货接口
 */
namespace Apis\Controller;
use Think\Controller;

class KgReturnGoodsController extends BaseController {
	/**
	 * 退货申请列表
	 * user_id     操作人ID
	 * store_id    门店ID
	 * goods_list  货物ID
	 * return_reason 退货理由
	 */
	public function getReturnGoodsList()
	{
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		$start_date = $_REQUEST['start_date'];//开始时间
		$end_date = $_REQUEST['end_date'];//结束时间
		$status = $_REQUEST['status'];//状态

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
			$maps['tp_return_goods_store.create_time'] = array(array('gt',$start_time),array('lt',$end_time));
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
			$maps['tp_return_goods_store.store_id'] = $store_id;
		}
		// 状态条件
		if ($status) {
			$maps['tp_return_goods_store.status'] = $status;
		}

		//获取主表数据
		$result=D("return_goods_store")->where($maps)->order('id desc')->limit($start.','.$page_count)->select();

		foreach ($result as $key => $val) {
			$wh['tp_return_goods_detail.replenishment_order_id'] = $val['id'];
			$result_detail = M('return_goods_detail')->join('tp_goods ON tp_return_goods_detail.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_return_goods_detail.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_return_goods_detail.*,tp_goods.spu,tp_spec_goods_price.sku')->select();
			foreach ($result_detail as $key1 => $value) {
				$result_detail[$key1]['goods_num'] = $value['count'];
			}
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
		$total_order = M('return_goods_store')->where($maps)->count();
		// 符合条件的总数量
		$total_num = D("return_goods_store")->join('tp_return_goods_detail ON tp_return_goods_store.id = tp_return_goods_detail.replenishment_order_id','right')->where($maps)->sum('count');

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
	 * 库管不确认操作 | 库管确认操作
	 * user_id     操作人ID
	 * replenishment_order_id    门店ID
	 * operating  操作标识  1 不确认 2 确认
	 */
	public function disConfirmation()
	{	
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$replenishment_order_id = $_REQUEST['replenishment_order_id'];//订单编号
		$operating = $_REQUEST['operating'];//操作类别 1 不确认 2 确认
		$goods_list = $_REQUEST['goods_list'];//获取货物信息
		$arr = json_decode($goods_list);//货物信息  数组
		$len = count($arr);
		// 判断是否有权限操作
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'库管');

		//判断订货订单状态
		$thisStatus = M('return_goods_store')->where('id='.$replenishment_order_id)->getField('status');
		if ($thisStatus==2) {
			$news = array('code' =>0 ,'msg'=>'该订单已处理！','data'=>null);
			echo json_encode($news,true);exit;
		}else if ($thisStatus==3) {
			$news = array('code' =>0 ,'msg'=>'该订单已处理！','data'=>null);
			echo json_encode($news,true);exit;
		}

		//修改订单状态
		$map['id'] = $replenishment_order_id;
		if ($operating==1) {
			$data['status'] = 2;
		}else{
			$data['status'] = 3;
		}
		$data['update_time'] = time();
		$data['creator'] = $user_name;
		$res = M('return_goods_store')->where($map)->save($data);
		//如果库管拒绝  将锁定数量返回即时库存
		if ($operating==1 && $res) {
			//获取 resource_id
			$store_id = M('return_goods_store')->where($map)->getField('store_id');
			// 查看库存是否充足
			for ($i=0; $i < $len; $i++) { 
				$goods_id = $arr[$i]->goods_id;				//商品ID
				$goods_num = $arr[$i]->count;				//商品数量
				$stock_type = 4;							//门店库存标识
				$reop = updata_lock_number($goods_id,$goods_num,$store_id,$stock_type,1);
			}
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
	 * 退货接收入库
	 */
	public function returnInnerStock()
	{	
		$order_id = $_REQUEST['replenishment_order_id'];//订单ID
		$user_id = $_REQUEST['user_id'];//当前用户ID
		// $repertory_id = $_REQUEST['repertory_id'];//仓库ID
		$getData = $_REQUEST['goods_list'];//商品列表
		
		$arr = json_decode($getData);//确认发货信息  数组
		// 判断有无权限此操作
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'库管');
		
		//判断订货订单状态
		$thisStatus = M('return_goods_store')->where('id='.$order_id)->getField('status');
		if ($thisStatus==5) {
			$news = array('code' =>0 ,'msg'=>'该订单已处理！','data'=>null);
			echo json_encode($news,true);exit;
		}

		// 修改总库存数量
		$length = count($arr);

		//开启事务
		$str = '';
		$tranDb = M();
		$tranDb->startTrans();

		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;	//商品ID
			$num = $arr[$i]->goods_num;		//商品数量
			$resource_id = $arr[$i]->repertory_id;	//仓库ID
			// 增加库存
			$rtem = jskc_new($goods_id,$num,$resource_id,1,2);
			// 新增仓库端流水
			$result55 = addWaterRepertoryRecord($goods_id,$num,$resource_id,4);

		    if (!$result55) {
		    	$str .= '修改失败!';
		    }

			if (!$rtem) {
				$str .= '修改失败!';
			}else{
				//删除退货在途数据 且添加至库存记录表中
				$ims['stock_type'] = 3;
				$ims['resource_id'] = $order_id;
				$ims['good_id'] = $goods_id;
				$ltem = M('stock');
				$ite = $ltem->where($ims)->find();
				$ite_id = $ite['id'];
				unset($ite['id']);				
				// 添加至库存记录表中
				$results = M('stock_record')->add($ite);
				if ($results) {
					$uup = $ltem->where('id='.$ite_id)->delete();
				}else{
					$str .= '修改失败!';
				}
				
			}
		}

		// 修改退货订单状态为已完成
		if (!$str) {
			$info['id'] = $order_id;
			$info['status'] = 5;
			$info['update_time'] = time();
			$res = M('return_goods_store')->save($info);//修改订单状态
			if ($res) {
				$tranDb->commit();
				$news = array('code' =>1 ,'msg'=>'接收成功！','data'=>null);
	            echo json_encode($news,true);exit;
			}else{
				$tranDb->rollback();
				$news = array('code' =>0 ,'msg'=>'接收失败！','data'=>null);
	            echo json_encode($news,true);exit;
			}
		}else{
			$tranDb->rollback();
			$news = array('code' =>0 ,'msg'=>'接收失败！','data'=>null);
            echo json_encode($news,true);exit;
		}
		
	}

}