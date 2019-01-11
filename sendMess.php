<?php 
//时区设置：亚洲/上海
date_default_timezone_set('Asia/Shanghai');
define('ALIDAYU', __DIR__.'/ThinkPHP/Library/Vendor/Alidayu'); // 发送短信类库
define('HOST', 'localhost');
define('USER', 'root');
define('PWD', '');
define('DBNAME', 'shengongguan');
define('CHAR', 'utf8');
//这个是你下面实例化的类
// require_once ALIDAYU.'/TopClient.php';
// //这个是topClient 里面需要实例化一个类所以我们也要加载 不然会报错
// require_once ALIDAYU.'/ResultSet.php';
// //这个是成功后返回的信息文件
// require_once ALIDAYU.'/RequestCheckUtil.php';
// //这个是错误信息返回的一个php文件
// require_once ALIDAYU.'/TopLogger.php';
// //这个也是你下面示例的类
// require_once ALIDAYU.'/AlibabaAliqinFcSmsNumSendRequest.php';

// $c = new TopClient;
// var_dump($c);
function connect()
{
	//定义一个表示
	$info ['flag'] = true;
	$link = @mysqli_connect(HOST, USER, PWD);

	//判断是否连接成功
	if (!$link) {
		$info ['flag'] = false;
		$info['result'] = '错误信息('.mysqli_connect_errno().'):'.mysqli_connect_error();
		return $info;
	}

	//选择数据库
	if (!mysqli_select_db($link, DBNAME) ){
		$info ['flag'] = false;
		$info['result'] = '错误信息('.mysqli_errno($link).'):'.mysqli_error($link);
		return $info;
	}

	//设置字符集
	if (!mysqli_set_charset($link, CHAR) ){
		$info ['flag'] = false;
		$info['result'] = '错误信息('.mysqli_errno($link).'):'.mysqli_error($link);
		return $info;
	}
	$info['result'] = $link;
	return $info;
}
/**
 *@parem string $sql 执行的sql语句
 *@parem array $info 返回的结果
 */
function query($sql){
	//调用连接的操作
	$link = connect();
	if (!$link['flag']) {
		return false;
	}
	$result = mysqli_query($link['result'], $sql);
	if ($result) {
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
		return $rows;
	}	
}
/**
 *@parem string $sql 执行的sql语句
 *@parem array $info 返回的结果
 */
function excute($sql)
{
	//调用函数
	$link = connect();
	if (!$link['flag']) {
		return false;
	}
	//连接数据库
	$result = mysqli_query($link['result'], $sql);
	if ($result) {
		$info['flag'] = true;
		return $info;
	}
}

/**
* 发送短信
* @param $mobile  手机号码
* @param $code    验证码
* @return bool    短信发送成功返回true失败返回false
*/
function sendSMS($mobile, $code)
{
    //时区设置：亚洲/上海
    date_default_timezone_set('Asia/Shanghai');
    //这个是你下面实例化的类
    require_once ALIDAYU.'/TopClient.php';
    //这个是topClient 里面需要实例化一个类所以我们也要加载 不然会报错
    require_once ALIDAYU.'/ResultSet.php';
    //这个是成功后返回的信息文件
    require_once ALIDAYU.'/RequestCheckUtil.php';
    //这个是错误信息返回的一个php文件
    require_once ALIDAYU.'/TopLogger.php';
    //这个也是你下面示例的类
    require_once ALIDAYU.'/AlibabaAliqinFcSmsNumSendRequest.php';

    $c = new TopClient;
    //短信内容：公司名/名牌名/产品名
    $sql = "select value from tp_config where name='sms_product'";
    $result = query($sql);
    $product = $result[0]['value'];
    //App Key的值 这个在开发者控制台的应用管理点击你添加过的应用就有了
    $sql = "select value from tp_config where name='sms_appkey'";
    $result = query($sql);
    $c->appkey = $result[0]['value'];

    //App Secret的值也是在哪里一起的 你点击查看就有了
    $sql = "select value from tp_config where name='sms_secretKey'";
    $result = query($sql);
    $c->secretKey = $result[0]['value'];

    //这个是用户名记录那个用户操作
    $req = new AlibabaAliqinFcSmsNumSendRequest;
    //代理人编号 可选
    $req->setExtend("123456");
    //短信类型 此处默认 不用修改
    $req->setSmsType("normal");
    //短信签名 必须
    $req->setSmsFreeSignName("注册验证");
    //短信模板 必须
    $req->setSmsParam("{\"code\":\"$code\",\"product\":\"$product\"}");
    //短信接收号码 支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，
    $req->setRecNum("$mobile");
    //短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。
    $sql = "select value from tp_config where name='sms_templateCode'";
    $result = query($sql);
    $req->setSmsTemplateCode($result[0]['value']); // templateCode
    $c->format='json';
    //发送短信
    $resp = $c->execute($req);
    //短信发送成功返回True，失败返回false
    //if (!$resp)
    if ($resp && $resp->result)   // if($resp->result->success == true)
    {
        // 从数据库中查询是否有验证码
        // $data = M('sms_log')->where("code = '$code' and add_time > ".(time() - 60*60))->find();
        $sql = 'select * from tp_sms_log where code='.$code.' and add_time > '.$time;
		$data = query($sql);
        // 没有就插入验证码,供验证用
        empty($data) && M('sms_log')->add(array('mobile' => $mobile, 'code' => $code, 'add_time' => time(), 'session_id' => SESSION_ID));
        if (empty($data)) {
        	$time = time();
        	$session_id = SESSION_ID;
        	$sql = "insert into  tp_sms_log (mobile, code, add_time ,session_id) values ('{$mobile}', '{$code}', '{$time}', '{$session_id}')";
			$data = excute($sql);
        }
        return true;        
    }
    else 
    {
        return false;
    }
}

function sendsmss($mobile,$content){
        
    $create_time = time();
    $sql = "insert into  tp_sendsms_log (mobile, content, create_time) values ('{$mobile}', '{$content}', '{$create_time}')";
	$data = excute($sql);
}

function doSendSmss($mobile,$content){
    $Sd_UserName="201706010942";
    $Sd_UserPsd="123456";
    $Sd_Phones=$mobile;
    $Sd_MsgContent=$content;
    $url="http://210.14.64.74:81/SmsService/UnicomWdslRec.asmx/SendMessage";



    $data="Sd_UserName=".$Sd_UserName."&Sd_UserPsd=".$Sd_UserPsd."&Sd_Phones=".$Sd_Phones."&Sd_MsgContent=".$Sd_MsgContent."&Sd_SchTime=&Sd_ExNumber=&Sd_SeqNum=";

    $res=request_post($url,$data);
    $res1='<?xml version="1.0" encoding="utf-8"?>
<string xmlns="http://tempuri.org/">0,send success!</string>';


    $array = json_decode(json_encode(simplexml_load_string($res1)),TRUE);

    //$xml = simplexml_load_string($res1); 

  $res2=$array[0];
   
   if($res2=="0,send success!"){
      return true;
   }else{
      return false;
   }
   

}
function request_post($url = '', $param = '') {
    if (empty($url) || empty($param)) {
        return false;
    }
    
    $postUrl = $url;
    $curlPost = $param;
    $ch = curl_init();//初始化curl
    curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
    curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
    $data = curl_exec($ch);//运行curl
    curl_close($ch);        
    return $data;
}

//异步发短信
function doSendSms(){

    // $maps['status']=0;
    // $sendlist=D("sendsms_log")->where($maps)->limit('5')->select();

    $sql = 'select * from tp_sendsms_log where status=0 limit 5';
	$sendlist = query($sql);

    if($sendlist){
        foreach ($sendlist as $key => $value) {
           $res=doSendSmss($value['mobile'],$value['content']);
           
           if($res){
           		$id = $value['id'];
           		$_time = time();
           		$sql = "update tp_sendsms_log set status=1,update_time='{$_time}' where id = '{$id}'";
				$data = excute($sql);

                // $where['id']=$value['id'];
                // $vdata['status']=1;
                // $vdata['update_time']=time();
                // D('sendsms_log')->where($where)->save($vdata);
           }
        }
    }
}

ignore_user_abort(); //即使Client断开(如关掉浏览器)，PHP脚本也可以继续执行. 
set_time_limit(0); // 执行时间为无限制，php默认的执行时间是30秒，通过set_time_limit(0)可以让程序无限制的执行下去 
$interval=5; // 每隔5秒运行
do{ 
	doSendSms();
	sleep($interval);
}while(true); 