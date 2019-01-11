<?php
/**
 * 日志接口
 */
namespace Apis\Controller;
use Think\Controller;

class LogController extends BaseController {

	/**
     * 销售打印日志 判断销售失败问题原因
     * store_id    门店ID
     */
	public function save_log(){
		$msg = $_REQUEST['msg'];//app返回错误信息
		$order_id = $_REQUEST['order_id'];//app订单ID

		$date = date('Y-m-d',time());//记录日期
	    
	    $str = $date.' 订单号为'.$order_id.'的订单操作日志: '.$msg;
	    
	    $result = file_put_contents(UPLOAD_PATH."/log.txt",$str.PHP_EOL,FILE_APPEND);
	    $news = array('code' =>1 ,'msg'=>'记录成功','data'=>$result);
        echo json_encode($news,true);exit;
	}	



}