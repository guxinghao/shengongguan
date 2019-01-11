<?php
/**
 * 门店app临期产品接口
 */
namespace Apis\Controller;
use Think\Controller;

class AdventGoodsController extends BaseController {
	/**
	 * 临期产品列表
	 * user_id     操作人ID
	 * store_id    门店ID
	 * sku  	   产品编号
	 */
	public function adventGoodsList()
	{
		$user_id = $_REQUEST['user_id'];//操作人ID
		$store_id = $_REQUEST['store_id'];//门店ID
		$sku = $_REQUEST['sku'];// 产品编号


		$page = $_REQUEST['page'];//页码
		$page_count = $_REQUEST['page_count'];//每页展示条数
		if (!$page) {
			$page = 1;
		}else{
			$page = $_REQUEST['page'];
		}
		if (!$page_count) {
			$page_count = 10;
		}

		if ($sku) {
			$maps['tp_spec_goods_price.sku'] = array('like',"%".$sku."%");
		}

		$start=($page-1)*$page_count;
		$maps['tp_stock.stock_type'] = 4;
		$maps['tp_stock.resource_id'] = $store_id;
		$result = D('stock')->join('tp_spec_goods_price ON tp_stock.good_id = tp_spec_goods_price.goods_id','left
			')->join('tp_goods ON tp_stock.good_id = tp_goods.goods_id','left
			')->field('tp_stock.advent_number,tp_stock.good_id,tp_stock.id,tp_spec_goods_price.sku,tp_goods.goods_name,tp_goods.spu')->where($maps)->order('tp_stock.advent_number desc')->limit($start.','.$page_count)->select();
		$list = array();
		foreach ($result as $key => $val) {
			$list[$key]['id'] = $val['id'];
			$list[$key]['goods_id'] = $val['good_id'];
			$list[$key]['goods_name'] = $val['goods_name'];
			$list[$key]['advent_number'] = $val['advent_number'];
			$list[$key]['original_number'] = $val['advent_number'];
			$list[$key]['spu'] = $val['spu'];
			// $list[$key]['sku'] = $val['sku'];
		}
		if($list){
			$news = array('code' =>1 ,'msg'=>'获取成功！','data'=>$list);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'获取失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}


	/**
	 * 修改临期产品
	 * user_id     操作人ID
	 * store_id    门店ID
	 * goods_list  产品ID
	 */
	public function updateAdventGoods()
	{
		$user_id = $_REQUEST['user_id'];//操作人ID
		$store_id = $_REQUEST['store_id'];//门店ID
		$goods_list = $_REQUEST['goods_list'];//货物列表
		//判断权限
		if (!$user_id) {
			$news = array('code' =>0 ,'msg'=>'没有权限！','data'=>null);
			echo json_encode($news,true);exit;
		}
		$user_name = $this->AutoCheckRole($user_id,'店长');

		// 查看库存是否充足
		$arr = json_decode($goods_list);//商品信息  数组
		$length = count($arr);

		for ($i=0; $i < $length; $i++) { 
			$goods_id = $arr[$i]->goods_id;				//商品ID
			$resource_id = $store_id;					//门店ID
			$goods_num = $arr[$i]->count;				//商品数量
			$stock_type = 4;							//门店库存标识
			$this->checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type);
		}

		//修改临期产品数量
		$str = '';
		for ($i=0; $i < $length; $i++) { 
			$wh['id'] = $arr[$i]->id;					//记录ID
			$data['advent_number'] = $arr[$i]->count;	//商品数量
			$data['update_time'] = time();
			$info = M('stock')->where($wh)->save($data);

			if (!$info) {
				$str = '修改临期产品失败!';
				$news = array('code' =>0 ,'msg'=>'操作失败！','data'=>null);
				echo json_encode($news,true);exit;
			}
		}

		if(!$str){
			$news = array('code' =>1 ,'msg'=>'修改成功！','data'=>null);
			echo json_encode($news,true);exit;
        }else{
			$news = array('code' =>0 ,'msg'=>'修改失败！','data'=>null);
			echo json_encode($news,true);exit;
        }
	}

}