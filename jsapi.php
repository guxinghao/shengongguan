<?php
session_start();
ini_set('date.timezone','Asia/Shanghai');
require("./ThinkPHP/Library/Vendor/WxPay/JsApiPay.php");

$fee = $_REQUEST['fee'];//金额
$trade_no = $_REQUEST['trade_no'];//订单号
$title = $_REQUEST['title'];//商品详情

$fee = $fee*100;
$trade_no = $trade_no;
$tools = new JsApiPay();

//①、获取用户openid
// $openId = $tools->GetOpenid();
$openId = $_SESSION['openid'];

$input = new WxPayUnifiedOrder();
$input->SetBody($title);
$input->SetAttach("参公馆");

$input->SetOut_trade_no($trade_no);
$input->SetTotal_fee($fee);
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("参公馆");
$notify_url = "http://www.shengongshiye.com/index.php/Home/User/pay_success.html";
$input->SetNotify_url($notify_url);
$input->SetTrade_type("JSAPI");
$input->SetOpenid($openId);
$order = WxPayApi::unifiedOrder($input);

$jsApiParameters = $tools->GetJsApiParameters($order);
$editAddress = $tools->GetEditAddressParameters();
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/> 
<title>微信支付</title>
</head>
<body>
</body>
<script type="text/javascript">
 //调用微信JS api 支付
 function jsApiCall()
 {
	 WeixinJSBridge.invoke(
	 	'getBrandWCPayRequest',
	 	<?php echo $jsApiParameters; ?>,
	 	function(res){
	 		WeixinJSBridge.log(res.err_msg);
	 		if (res.err_msg == "get_brand_wcpay_request:ok") {
				window.location.href = "http://www.shengongshiye.com/index.php/Home/User/pay_true.html";
			} else {
				alert("支付中止或失败，请稍后再试");
				history.go(-1);
			}
 			//alert(res.err_code+res.err_desc+res.err_msg);
 		}
 	);
 }
 
 function callpay()
 {
	 if (typeof WeixinJSBridge == "undefined"){
		 if( document.addEventListener ){
		 	 document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		 }else if (document.attachEvent){
			 document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
			 document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		 }
	 }else{
	 	jsApiCall();
	 }
 }
	//获取共享地址
function editAddress(){
	WeixinJSBridge.invoke(
		'editAddress',
		<?php echo $editAddress; ?>,
		function(res){
		var value1 = res.proviceFirstStageName;
		var value2 = res.addressCitySecondStageName;
		var value3 = res.addressCountiesThirdStageName;
		var value4 = res.addressDetailInfo;
		var tel = res.telNumber; 
		// alert(value1 + value2 + value3 + value4 + ":" + tel);
		}
	);
}
 
window.onload = function(){
	if (typeof WeixinJSBridge == "undefined"){
	    if( document.addEventListener ){
	        document.addEventListener('WeixinJSBridgeReady', editAddress, false);
	    }else if (document.attachEvent){
	        document.attachEvent('WeixinJSBridgeReady', editAddress); 
	        document.attachEvent('onWeixinJSBridgeReady', editAddress);
	    }
	}else{
		editAddress();
	}
};

callpay();
</script>
</html>