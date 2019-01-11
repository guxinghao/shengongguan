<?php
/**
 * app优惠券
 * Date: 2017-12-13
 */
namespace Apis\Controller;
use Think\Controller;

class CouponController extends BaseController {

	//获取优惠券
	public function getCoupon(){
		$total_amount = $_REQUEST['total_amount'];//订单总金额
		$user_id = $_REQUEST['uid'];//客户ID

		//获取客户拥有优惠券
		$maps['uid'] = $user_id;
		$maps['use_time'] = 0;//未使用

		$result = M('coupon_list')->where($maps)->select();
		$arr = array();
		foreach ($result as $key => $val) {
			//获取优惠券内容
			$where['id'] = $val['cid'];
			$where['use_start_time'] = array('lt',time());
			$where['use_end_time'] = array('gt',time());
			$info = M('coupon')->field('id,name,cate_id,money,condition,use_start_time,use_end_time')->where($where)->find();
			$info['coupon_list_id'] = $val['id'];
			//如果为满减礼券  判断是否符合使用条件
			if ($info['cate_id'] == 1 && $info['condition'] <= $total_amount) {
				$arr[] = $info;
			}else if($info['cate_id'] == 2 && $info['money'] < $total_amount){
				$arr[] = $info;
			}
		}
		if(empty($arr)){
            $news = array('code' =>0 ,'msg'=>'没有可用优惠券！','data'=>null);
            echo json_encode($news,true);exit;
        }else{
        	$news = array('code' =>1 ,'msg'=>'有可用优惠券！','data'=>$arr);
            echo json_encode($news,true);exit;
        }
	}
}