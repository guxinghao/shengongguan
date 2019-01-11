<?php 
namespace Home\Controller;
use Home\Controller\ParentController;
/**
 * 微信支付测试控制器
 * @file WxPayController.class.php
 * @author Gary <lizhiyong2204@sina.com>
 * @date 2015年8月4日
 * @todu
 */
class WxPayController extends ParentController {
   private $_order_body = 'xxx';
   private $_order_goods_tag = 'xxx';
   public function __construct(){
   parent::__construct();
   require_once ROOT_PATH."Api/lib/WxPay.Api.php";
   require_once ROOT_PATH."Api/lib/WxPay.JsApiPay.php";
 }
 
 public function index(){
   //①、获取用户openid
   $tools = new \JsApiPay();
   $openId = $tools->GetOpenid(); 
   //②、统一下单
   $input = new \WxPayUnifiedOrder(); 
   //商品描述
   $input->SetBody($this->_order_body);
   //附加数据，可以添加自己需要的数据，微信回异步回调时会附加这个数据
   $input->SetAttach('xxx');
   //商户订单号
   $out_trade_no = \WxPayConfig::MCHID.date("YmdHis");
   $input->SetOut_trade_no($out_trade_no);
   //总金额,订单总金额，只能为整数,单位为分 
   $input->SetTotal_fee(1);
   //交易起始时间
   $input->SetTime_start(date("YmdHis"));
   //交易结束时间
   $input->SetTime_expire(date("YmdHis", time() + 600));
   //商品标记
   $input->SetGoods_tag($this->_order_goods_tag);
   //通知地址,接收微信支付异步通知回调地址 SITE_URL=http://test.paywechat.com/Charge
   $notify_url = SITE_URL.'/index.php/Test/notify.html';
   $input->SetNotify_url($notify_url);
   //交易类型
   $input->SetTrade_type("JSAPI");
   $input->SetOpenid($openId);
   $order = \WxPayApi::unifiedOrder($input);
   $jsApiParameters = $tools->GetJsApiParameters($order);
   //获取共享收货地址js函数参数
   $editAddress = $tools->GetEditAddressParameters();
   
   $this->assign('openId',$openId);
   $this->assign('jsApiParameters',$jsApiParameters);
   $this->assign('editAddress',$editAddress);
   $this->display(); 
 }
 
 /**
 * 异步通知回调方法
 */
 public function notify(){
  require_once ROOT_PATH."Api/lib/notify.php";
  $notify = new \PayNotifyCallBack();
  $notify->Handle(false);
  //这里的IsSuccess是我自定义的一个方法，后面我会贴出这个文件的代码，供参考。
  $is_success = $notify->IsSuccess(); 
  $bdata = $is_success['data']; 
   //支付成功
  if($is_success['code'] == 1){ 
     $news = array(
      'touser' => $bdata['openid'],
      'msgtype' => 'news',
      'news' => array (
        'articles'=> array (
           array(
           'title' => '订单支付成功',
           'description' => "支付金额：{$bdata['total_fee']}\n".
           "微信订单号：{$bdata['transaction_id']}\n"
           'picurl' => '',
           'url' => ''
           )
        )
      )
    );
    //发送微信支付通知
    $this->sendCustomMessage($news); 
    }else{//支付失败
   
    }
 }
 
 /**
 * 支付成功页面
 * 不可靠的回调
 */
 public function ajax_PaySuccess(){
 //订单号
 $out_trade_no = I('post.out_trade_no');
 //支付金额
 $total_fee = I('post.total_fee');
 /*相关逻辑处理*/
 
 }