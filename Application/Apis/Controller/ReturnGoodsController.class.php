<?php
/**
 * 门店app退货接口
 */
namespace Apis\Controller;
use Think\Controller;

class ReturnGoodsController extends BaseController {
	/**
	 * 新增退货申请
	 * user_id     操作人ID
	 * store_id    门店ID
	 * goods_list  货物ID
	 * return_reason 退货理由
	 */
	public function addReturnGoods()
	{
		$user_id = $_REQUEST['user_id'];//操作人ID
		$store_id = $_REQUEST['store_id'];//门店ID
		$reason = $_REQUEST['reason'];//退货理由
		$goods_list = $_REQUEST['goods_list'];//货物列表
		// 判断是否有权限操作
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'店长');
		$arr = json_decode($goods_list);//确认发货信息  数组
		$length = count($arr);
		// 查看库存是否充足
		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = $store_id;					//门店ID
			$goods_num = $arr[$i]->count;				//商品数量
			$stock_type = 4;							//门店库存标识
			$this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
		}
		//退货单编号
		$sn1=date('YmdHis',time());
        $num=str_pad($user_id,6,"0",STR_PAD_LEFT); 
        $sn="C";
        $sn.=$num;
        $sn.=$sn1;
        
        
		// 新增退货记录表
        $data['replenishment_order'] = $sn;//入库单编号
		$data['status'] = 1;//状态
		$data['store_id'] = $store_id;//门店ID
		$data['admin_id'] = $user_id;//操作人ID
		$data['reason'] = $reason;//退货理由
		$data['create_time'] = time();
		$data['creator'] = $user_name;//操作人名称
		$result = M('return_goods_store')->add($data);
		if (!$result) {
			$news = array('code' =>0 ,'msg'=>'提交失败！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$str = '';
		for ($i=0; $i < $length; $i++) { 
			//  新增退货记录详情表
			$info['count'] = $arr[$i]->count;				//商品数量
			$info['goods_id'] = $arr[$i]->goods_id;				//商品ID
			$info['goods_name'] = $arr[$i]->goods_name;			//商品名称
			$info['replenishment_order_id'] = $result;		//仓库ID
			$info['return_goods_reason'] = $reason;		//退货商品原因
			if (!$info['return_goods_reason']) {
				$info['return_goods_reason'] = '';
			}
			$info['create_time'] = time();						//申请时间
			$res = M('return_goods_detail')->add($info);
			if (!$res) {
				$str .= $info['goods_name'].'退货失败!';
				continue;
			}

			//将库存数量冻结(锁定数量)
        	updata_lock_number($arr[$i]->goods_id,$arr[$i]->count,$store_id,4,2);

			
		}
		if(!$str){
			$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}


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
		$store_id = $_REQUEST['store_id'];//门店ID
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
		$user_name = $this->AutoCheckRole($user_id,'店长');
		
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
		//获取附表数据
		foreach ($result as $key => $val) {
			$wh['tp_return_goods_detail.replenishment_order_id'] = $val['id'];
			$result_detail = M('return_goods_detail')->join('tp_goods ON tp_return_goods_detail.goods_id = tp_goods.goods_id','left')->join('
tp_spec_goods_price ON tp_return_goods_detail.goods_id = 
tp_spec_goods_price.goods_id','left')->where($wh)->field('tp_return_goods_detail.*,tp_goods.spu,tp_goods.goods_name,tp_spec_goods_price.sku')->select();
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
	 * 门店端发货操作
	 * user_id     操作人ID
	 * replenishment_order_id    订单ID
	 * store_id    门店ID
	 * goods_list  商品详情
	 */
	public function sendReturnGoods()
	{	
		$user_id = $_REQUEST['user_id'];//当前用户ID
		$replenishment_order_id = $_REQUEST['replenishment_order_id'];//订单ID
		$store_id = $_REQUEST['store_id'];//门店ID
		$goods_list = $_REQUEST['goods_list'];//门店ID
		$arr = json_decode($goods_list);//货物信息  数组
		$len = count($arr);
		$str = '';
		// 判断是否有权限操作
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'店长');

		//判断订货订单状态
		$thisStatus = M('return_goods_store')->where('id='.$replenishment_order_id)->getField('status');
		if ($thisStatus==4) {
			$news = array('code' =>0 ,'msg'=>'该订单已处理！','data'=>null);
			echo json_encode($news,true);exit;
		}

		// 查看库存是否充足
		for ($i=0; $i < $len; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = $store_id;			 		//仓库ID
			$goods_num = $arr[$i]->count;				//商品数量
			$stock_type = 4;							//门店库存标识
			$this->checkGoodsNum_return($goods_id,$goods_num,$resource_id,$stock_type);
		}

		//修改订单状态
		$map['id'] = $replenishment_order_id;
		$data['status'] = 4;
		$data['update_time'] = time();
		$data['creator'] = $user_name;
		$res = M('return_goods_store')->where($map)->save($data);
		if ($res) {
			// 新增门店出库表记录
			$code = M('return_goods_store')->where('id='.$replenishment_order_id)->getField('replenishment_order');

			$order_sn_code=date('YmdHis').rand(1000,9999);
			$_id = store_stock_out($store_id,$order_sn_code);
			// 门店出库记录表新增成功 则新增出库记录详情表
			if ($_id) {
				for ($i=0; $i < $len; $i++) { 
					$outstock_id = $_id;
					$goods_id = $arr[$i]->goods_id;
					$goods_name = $arr[$i]->goods_name;
					$count = $arr[$i]->count;
					$getRe = store_stock_out_detail($outstock_id,$goods_id,$goods_name,$count);
					if (!$getRe) {
						$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
						echo json_encode($news,true);exit;
					}else{
						// 成功则修改门店库存
						$tt = jskc_new($goods_id,$count,$store_id,4,1);
						// 将锁定数量减少
						$tt = updata_lock_number($goods_id,$count,$store_id,4,1);
						//新增门店流水记录
                    	$infofo = addWaterRecord($goods_id, $count, $store_id, 3);//出货类型 1 进货 2 销售 3 返货

                    	if (!$infofo) {
                    		$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
							echo json_encode($news,true);exit;
                    	}

						if (!$tt) {
							//修改库存失败
							$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
							echo json_encode($news,true);exit;
						}else{
							//修改库存成功 则 新增在途数据
							$uzr['stock_type'] = 3;//退货在途
							$uzr['good_id'] = $goods_id;
							$uzr['resource_id'] = $replenishment_order_id;//退货订单ID
							$uzr['number'] = $count;
							$uzr['create_time'] = time();
							$uzr['creator'] = $user_name;
							$res2 = M('stock')->add($uzr);
							if (!$res2) {
								$str .= '操作失败！';
							}
						}
					}
				}
			}
		}
		if(!$str){
			$news = array('code' =>1 ,'msg'=>'操作成功！','data'=>null);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

}