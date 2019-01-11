<?php
/**
 * TPshop检验登陆
 * @param
 * @return bool
 */
function is_login(){
    if(isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0){
        return $_SESSION['admin_id'];
    }else{
        return false;
    }
}
/**
 * 获取用户信息
 * @param $user_id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function get_user_info($user_id_or_name,$type = 0,$oauth=''){
    $map = array();
    if($type == 0)
        $map['user_id'] = $user_id_or_name;
    if($type == 1)
        $map['email'] = $user_id_or_name;
    if($type == 2)
        $map['mobile'] = $user_id_or_name;
    if($type == 3){
        $map['openid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
    $user = M('users')->where($map)->find();
    return $user;
}
/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
function update_user_level($user_id){
     if($user_id>0){
        $level_info_search['status'] = array('neq',0);
        $level_info = M('user_level')->where($level_info_search)->order('level_id')->select();
        $total_amount = M('order')->where("user_id=$user_id AND pay_status=1 and order_status not in (3,5)")->sum('order_amount');
    //echo $total_amount;
    if($level_info){
        foreach($level_info as $k=>$v){
            if($total_amount >= $v['amount']){
                $level = $level_info[$k]['level_id'];
                $discount = $level_info[$k]['discount']/100;
            }
        }
        $vmapss['user_id']=$user_id;
        $user = D("Users")->where($vmapss)->find();
        $updata['total_amount'] = $total_amount;//更新累计修复额度

            if(isset($level) && $level>$user['level']){
                $updata['level'] = $level;
                $updata['discount'] = $discount;                  
                $vdata['level']=$level;
                $vdata['uid']=$user_id;
                $vdata['create_time']=time();
                D("level_log")->add($vdata);
                if($user['xingming']){
                            $name = $user['xingming'];
                         }else{
                            $name = $user['nickname'];
                         }
                $day = time();
                $r['level_id'] = $level;
                $infoss = D('user_level')->where($r)->find();
                $mobile = $user['mobile'];
                $content = "尊敬的会员".$name."，您的晓芹会员卡在".date('Y-m-d H:i:s',$day)."，升级为".$infoss['level_name']."。详询4006990605(晓芹海参)";
                 $res= sendsmss($mobile,$content);
            }     
        //累计额度达到新等级，更新会员折扣        
        M('users')->where("user_id=$user_id")->save($updata);

        //echo M()->getlastsql();
    }
     }

    
}

/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
function update_user_levels($user_id){
     if($user_id>0){
        $level_info_search['status'] = array('neq',0);
        $level_info = M('user_level')->where($level_info_search)->order('level_id')->select();
    
    $total_amount = M('order')->where("user_id=$user_id AND pay_status=1 and order_status not in (3,5)")->sum('order_amount');
    //echo $total_amount;
    if($level_info){
        foreach($level_info as $k=>$v){
            if($total_amount >= $v['famount']){
                $level = $level_info[$k]['level_id'];
                $discount = $level_info[$k]['discount']/100;
            }
        }
        
        $vmapss['user_id']=$user_id;
        $user = D("Users")->where($vmapss)->find();
        $updata['total_amount'] = $total_amount;//更新累计修复额度
             if(isset($level) && $level>$user['level']){
                $updata['level'] = $level;
                $updata['discount'] = $discount;                   
                $vdata['level']=$level;
                $vdata['uid']=$user_id;
                $vdata['create_time']=time();
                D("level_log")->add($vdata);
                if($user['xingming']){
                    $name = $user['xingming'];
                }else{
                    $name = $user['nickname'];
                }
                $day = time();
                $r['level_id'] = $level;
                $infoss = D('user_level')->where($r)->find();
                $mobile = $user['mobile'];
                $content = "尊敬的会员".$name."，您的晓芹会员卡在".date('Y-m-d H:i:s',$day)."，升级为".$infoss['level_name']."。详询4006990605(晓芹海参)";
                 $res= sendsmss($mobile,$content);
            }

        //累计额度达到新等级，更新会员折扣
        
        M('users')->where("user_id=$user_id")->save($updata);

        //echo M()->getlastsql();
    }
     }

    
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thum_images($goods_id,$width,$height){

     if(empty($goods_id))
		 return '';
    //判断缩略图是否存在
    $path = "Public/upload/goods/thumb/$goods_id/";
    $goods_thumb_name ="goods_thumb_{$goods_id}_{$width}_{$height}";
  
    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg'; 
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg'; 
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif'; 
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png'; 
        
    $original_img = M('Goods')->where("goods_id = $goods_id")->getField('original_img');
    if(empty($original_img)) return '';
    
    $original_img = '.'.$original_img; // 相对路径
    if(!file_exists($original_img)) return '';

    $image = new \Think\Image();
    $image->open($original_img);
        
    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    //生成缩略图
    if(!is_dir($path)) 
        mkdir($path,0777,true);
    
    //参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    
    //图片水印处理
    $water = tpCache('water');
    if($water['is_mark']==1){
    	$imgresource = './'.$path.$goods_thumb_name;
    	if($width>$water['mark_width'] && $height>$water['mark_height']){
    		if($water['mark_type'] == 'img'){
    			$image->open($imgresource)->water(".".$water['mark_img'],$water['sel'],$water['mark_degree'])->save($imgresource);
    		}else{
    		    //检查字体文件是否存在
    			if(file_exists('./zhjt.ttf')){
    				$image->open($imgresource)->text($water['mark_txt'],'./zhjt.ttf',20,'#000000',$water['sel'])->save($imgresource);
    			}
    		}
    	}
    }
    return '/'.$path.$goods_thumb_name;
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img,$goods_id,$width,$height){
	//判断缩略图是否存在
	$path = "Public/upload/goods/thumb/$goods_id/";
	$goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}";
	//这个缩略图 已经生成过这个比例的图片就直接返回了
	if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
	if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
	if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
	if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';
	
	$original_img = '.'.$sub_img['image_url']; //相对路径
	if(!file_exists($original_img)) return '';
	
	$image = new \Think\Image();
	$image->open($original_img);
	
	$goods_thumb_name = $goods_thumb_name. '.'.$image->type();
	// 生成缩略图
	if(!is_dir($path))
		mkdir($path,777,true);
	$image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
	return '/'.$path.$goods_thumb_name;
}

/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id  商品id
 */
function refresh_stock($goods_id){
    $count = M("SpecGoodsPrice")->where("goods_id = $goods_id")->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count = M("SpecGoodsPrice")->where("goods_id = $goods_id")->sum('store_count');
    M("Goods")->where("goods_id = $goods_id")->save(array('store_count'=>$store_count)); // 更新商品的总库存
}

/**
 * 根据 order_goods 表扣除商品库存
 * @param type $order_id  订单id
 */
function minus_stock($order_id){
    $orderGoodsArr = M('OrderGoods')->where("order_id = $order_id")->select();
    foreach($orderGoodsArr as $key => $val)
    {
        // 有选择规格的商品
        if(!empty($val['spec_key']))
        {   // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
            M('SpecGoodsPrice')->where("goods_id = {$val['goods_id']} and `key` = '{$val['spec_key']}'")->setDec('store_count',$val['goods_num']);
            refresh_stock($val['goods_id']);
        }else{
            M('Goods')->where("goods_id = {$val['goods_id']}")->setDec('store_count',$val['goods_num']); // 直接扣除商品总数量
        }
        M('Goods')->where("goods_id = {$val['goods_id']}")->setInc('sales_sum',$val['goods_num']); // 增加商品销售量
        //更新活动商品购买量
        if($val['prom_type']==1 || $val['prom_type']==2){
        	$prom = get_goods_promotion($val['goods_id']);
        	if($prom['is_end']==0){
        		$tb = $val['prom_type']==1 ? 'flash_sale' : 'group_buy';
        		M($tb)->where("id=".$val['prom_id'])->setInc('buy_num',$val['goods_num']);
        		M($tb)->where("id=".$val['prom_id'])->setInc('order_num');
        	}
        }
    }
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to,$subject='',$content=''){
    require_once THINK_PATH.'Library/Vendor/phpmailer/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    $config = tpCache('smtp');
	$mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //调试输出格式
	//$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];
	
	if($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //用户名
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if(is_array($to)){
    	foreach ($to as $v){
    		$mail->addAddress($v);
    	}
    }else{
    	$mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //添加附件
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    return $mail->send();
}

/**
 * 发送短信
 * @param $mobile  手机号码
 * @param $content  内容
 * @return bool

function sendSMS($mobile,$content)
{
    $config = F('sms','',TEMP_PATH);
    $http = $config['sms_url'];			//短信接口
    $uid = $config['sms_user'];			//用户账号
    $pwd = $config['sms_pwd'];			//密码
    $mobileids = $mobile;         		//号码发送状态接收唯一编号
    $data = array
    (
        'uid'=>$uid,					//用户账号
        'pwd'=>md5($pwd.$uid),			//MD5位32密码,密码和用户名拼接字符
        'mobile'=>$mobile,				//号码，以英文逗号隔开
        'content'=>$content,			//内容
        'mobileids'=>$mobileids,
    );
    //即时发送
    $res = httpRequest($http,'POST',$data);//POST方式提交
    $stat = strpos($res,'stat=100');
    if($stat){
        return true;
    }else{
        return false;
    }
}
 */
//    /**
//     * 发送短信
//     * @param $mobile  手机号码
//     * @param $code    验证码
//     * @return bool    短信发送成功返回true失败返回false
//     */
function sendSMS($mobile, $code)
{
    //时区设置：亚洲/上海
    date_default_timezone_set('Asia/Shanghai');
    //这个是你下面实例化的类
    vendor('Alidayu.TopClient');
    //这个是topClient 里面需要实例化一个类所以我们也要加载 不然会报错
    vendor('Alidayu.ResultSet');
    //这个是成功后返回的信息文件
    vendor('Alidayu.RequestCheckUtil');
    //这个是错误信息返回的一个php文件
    vendor('Alidayu.TopLogger');
    //这个也是你下面示例的类
    vendor('Alidayu.AlibabaAliqinFcSmsNumSendRequest');

    $c = new \TopClient;
    $config =  tpCache('sms');
    //短信内容：公司名/名牌名/产品名
    $product = $config['sms_product'];
    //App Key的值 这个在开发者控制台的应用管理点击你添加过的应用就有了
    $c->appkey = $config['sms_appkey'];
    //App Secret的值也是在哪里一起的 你点击查看就有了
    $c->secretKey = $config['sms_secretKey'];
    //这个是用户名记录那个用户操作
    $req = new \AlibabaAliqinFcSmsNumSendRequest;
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
    $req->setSmsTemplateCode($config['sms_templateCode']); // templateCode
    
    $c->format='json'; 
    //发送短信
    $resp = $c->execute($req);
    //短信发送成功返回True，失败返回false
    //if (!$resp)
    if ($resp && $resp->result)   // if($resp->result->success == true)
    {
        // 从数据库中查询是否有验证码
        $data = M('sms_log')->where("code = '$code' and add_time > ".(time() - 60*60))->find();
        // 没有就插入验证码,供验证用
        empty($data) && M('sms_log')->add(array('mobile' => $mobile, 'code' => $code, 'add_time' => time(), 'session_id' => SESSION_ID));
        return true;        
    }
    else 
    {
        return false;
    }
}

function sendsmss($mobile,$content){
        
        $data['mobile']=$mobile;
        $data['content']=$content;
        $data['create_time']=time();
        $res=D("sendsms_log")->add($data);
        if($res){ 
            return true;
        }else{
            return false;
        }
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
/**
 * 查询快递
 * @param $postcom  快递公司编码
 * @param $getNu  快递单号
 * @return array  物流跟踪信息数组
 */
function queryExpress($postcom , $getNu) {
    $url = "http://wap.kuaidi100.com/wap_result.jsp?rand=".time()."&id={$postcom}&fromWeb=null&postid={$getNu}";
    //$resp = httpRequest($url,'GET');
    $resp = file_get_contents($url);
    if (empty($resp)) {
        return array('status'=>0, 'message'=>'物流公司网络异常，请稍后查询');
    }
    preg_match_all('/\\<p\\>&middot;(.*)\\<\\/p\\>/U', $resp, $arr);
    if (!isset($arr[1])) {
        return array( 'status'=>0, 'message'=>'查询失败，参数有误' );
    }else{
        foreach ($arr[1] as $key => $value) {
            $a = array();
            $a = explode('<br /> ', $value);
            $data[$key]['time'] = $a[0];
            $data[$key]['context'] = $a[1];
        }     
        return array( 'status'=>1, 'message'=>'ok','data'=> array_reverse($data));
    }
}

/**
 * 获取某个商品分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
function getCatGrandson ($cat_id)
{
    $GLOBALS['catGrandson'] = array();
    $GLOBALS['category_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['catGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['category_id_arr'] = M('GoodsCategory')->cache(true,TPshop_CACHE_TIME)->getField('id,parent_id');
    // 先把所有儿子找出来
    $son_id_arr = M('GoodsCategory')->where("parent_id = $cat_id")->cache(true,TPshop_CACHE_TIME)->getField('id',true);
    foreach($son_id_arr as $k => $v)
    {
        getCatGrandson2($v);
    }
    return $GLOBALS['catGrandson'];
}

/**
 * 获取某个文章分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
function getArticleCatGrandson ($cat_id)
{
    $GLOBALS['ArticleCatGrandson'] = array();
    $GLOBALS['cat_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['cat_id_arr'] = M('ArticleCat')->getField('cat_id,parent_id');
    // 先把所有儿子找出来
    $son_id_arr = M('ArticleCat')->where("parent_id = $cat_id")->getField('cat_id',true);
    foreach($son_id_arr as $k => $v)
    {
        getArticleCatGrandson2($v);
    }
    return $GLOBALS['ArticleCatGrandson'];
}

/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach($GLOBALS['category_id_arr'] as $k => $v)
    {
        // 找到孙子
        if($v == $cat_id)
        {
            getCatGrandson2($k); // 继续找孙子
        }
    }
}


/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getArticleCatGrandson2($cat_id)
{
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    foreach($GLOBALS['cat_id_arr'] as $k => $v)
    {
        // 找到孙子
        if($v == $cat_id)
        {
            getArticleCatGrandson2($k); // 继续找孙子
        }
    }
}

/**
 * 查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function cart_goods_num($user_id = 0,$session_id = '')
{
    $where = " session_id = '$session_id' ";
    $user_id && $where .= " or user_id = $user_id ";
    // 查找购物车数量
    $cart_count =  M('Cart')->where($where)->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}

/**
 * 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
function getGoodNum($goods_id,$key)
{
    if(!empty($key))
        return  M("SpecGoodsPrice")->where("goods_id = $goods_id and `key` = '$key'")->getField('store_count');
    else
        return  M("Goods")->where("goods_id = $goods_id")->getField('store_count');
}
 
/**
 * 获取缓存或者更新缓存
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return array or string or bool
 */
function tpCache($config_key,$data = array()){
    $param = explode('.', $config_key);
    if(empty($data)){
        //如$config_key=shop_info则获取网站信息数组
        //如$config_key=shop_info.logo则获取网站logo字符串
        $config = F($param[0],'',TEMP_PATH);//直接获取缓存文件
        if(empty($config)){
            //缓存文件不存在就读取数据库
            $res = D('config')->where("inc_type='$param[0]'")->select();
            if($res){
                foreach($res as $k=>$val){
                    $config[$val['name']] = $val['value'];
                }
                F($param[0],$config,TEMP_PATH);
            }
        }
        if(count($param)>1){
            return $config[$param[1]];
        }else{
            return $config;
        }
    }else{
        //更新缓存
        $result =  D('config')->where("inc_type='$param[0]'")->select();
        if($result){
            foreach($result as $val){
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k=>$v){
                $newArr = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
                if(!isset($temp[$k])){
                    M('config')->add($newArr);//新key数据插入数据库
                }else{
                    if($v!=$temp[$k])
                        M('config')->where("name='$k'")->save($newArr);//缓存key存在且值有变更新此项
                }
            }
            //更新后的数据库记录
            $newRes = D('config')->where("inc_type='$param[0]'")->select();
            foreach ($newRes as $rs){
                $newData[$rs['name']] = $rs['value'];
            }
        }else{
            foreach($data as $k=>$v){
                $newArr[] = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
            }
            M('config')->addAll($newArr);
            $newData = $data;
        }
        return F($param[0],$newData,TEMP_PATH);
    }
}

/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $desc    变动说明
 * @param   float   distribut_money 分佣金额
 * @return  bool
 */
function accountLog($user_id, $user_money = 0,$pay_points = 0, $desc = '',$distribut_money = 0,$type,$scoretype,$order_id){
    /* 插入帐户变动记录 */
    if($user_id>0){
            $account_log = array(
            'user_id'       => $user_id,
            'user_money'    => $user_money,
            'pay_points'    => $pay_points,
            'order_id'    => $order_id,
            'type'    => $type,
            'change_time'   => time(),
            'desc'   => $desc,
        );
        /* 更新用户信息 */

        if($scoretype==1){
              $sql = "UPDATE __PREFIX__users SET user_money = user_money + $user_money," .
            " pay_points = pay_points -$pay_points, distribut_money = distribut_money + $distribut_money WHERE user_id = $user_id";
        }else{
             $sql = "UPDATE __PREFIX__users SET user_money = user_money + $user_money," .
            " pay_points = pay_points + $pay_points, frozen_points = frozen_points + $pay_points, distribut_money = distribut_money + $distribut_money WHERE user_id = $user_id"; 
        }

        if( D('users')->execute($sql)){
            M('account_log')->add($account_log);
            return true;
        }else{
            return false;
        }
    }

    
}

/**
 * 记录帐户变动
 * @param   int     $user_id      用户id
 * @param   int     $store_id     门店ID
 * @param   int     $goods_id     商品ID
 * @param   int     $goods_num    商品数量
 * @param   string  $desc         变动说明
 * @param   int     $order_id     订单ID
 * @return  bool
 */
function return_goods($user_id, $user_money = 0,$pay_points = 0, $desc = '',$distribut_money = 0,$type,$scoretype,$order_id,$store_id){
    /* 插入帐户变动记录 */
        $return_goods = array(
            'user_id'       => $user_id,
            'user_money'    => $user_money,
            'pay_points'    => $pay_points,
            'order_id'    => $order_id,
            'type'    => $type,
            'change_time'   => time(),
            'desc'   => $desc,
        );
        $search['order_id']=$order_id;
        $order_info = M('order_goods')->where($search)->field('goods_id, goods_num')->select();
        $order_time = M('order')->where($search)->getField('add_time');
        $str = '';
        foreach ($order_info as $key => $val) {
            //修改库存
            $result1 = jskc_new($val['goods_id'],$val['goods_num'],$store_id,4,2);
            if (!$result1) {
                $str .= '修改失败!';
            }else{
                //删除门店销售记录
                $result2 = delWaterRecord($val['goods_id'],$val['goods_num'],$store_id,2,$order_time);
            }
            
            
            if (!$result2) {
                $str .= '新增失败!';
            }
        }

        if(!$str){
            M('account_log')->add($account_log);
            return 1;
        }else{
            return 0;
        }
}



//获取可用积分
 function getKeScore($user_id){
    $map['user_id']=$user_id;
    $user=D("Users")->where($map)->find();
    if($user['first_time']==0){
        $score3=$user['pay_points']-30;
    }else{
        $score3=$user['pay_points']-$user['frozen_points'];
    }
   return number_format($score3,2,".","");
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id  订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id  用户id 默认为管理员
 * @return boolean
 */
function logOrder($order_id,$action_note,$status_desc,$user_id = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
    // if(!in_array($status_desc, $status_desc_arr))
    // return false;

    $order = M('order')->where("order_id = $order_id")->find();
    $action_info = array(
        'order_id'        =>$order_id,
        'action_user'     =>$user_id,
        'order_status'    =>$order['order_status'],
        'shipping_status' =>$order['shipping_status'],
        'pay_status'      =>$order['pay_status'],
        'action_note'     => $action_note,
        'status_desc'     =>$status_desc, //''
        'log_time'        =>time(),
    );
    return M('order_action')->add($action_info);
}

/*
 * 获取地区列表
 */
function get_region_list(){
    //获取地址列表 缓存读取
    if(!S('region_list')){
        $region_list = M('region')->select();
        $region_list = convert_arr_key($region_list,'id');        
        S('region_list',$region_list);
    }

    return $region_list ? $region_list : S('region_list');
}
//获取具体地址
function get_region_name($id){
    if(!empty($id)){
        $maps['id']=$id;
        $lists = M('region')->where($maps)->find();
        return $lists['name'];
    }   
    
}
/*
 * 获取用户地址列表
 */
function get_user_address_list($user_id){
    $lists = M('user_address')->where(array('user_id'=>$user_id))->select();
    return $lists;
}

/*
 * 获取指定地址信息
 */
function get_user_address_info($user_id,$address_id){
    $data = M('user_address')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->find();
    return $data;
}
/*
 * 获取用户默认收货地址
 */
function get_user_default_address($user_id){
    $data = M('user_address')->where(array('user_id'=>$user_id,'is_default'=>1))->find();
    return $data;
}
/**
 * 获取订单状态的 中文描述名称
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return string
 */
function orderStatusDesc($order_id = 0, $order = array())
{
    if(empty($order))
        $order = M('Order')->where("order_id = $order_id")->find();

    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0)
            return 'WAITSEND'; //'待发货',
    }
    else // 非货到付款
    {
        if($order['pay_status'] == 0 && $order['order_status'] == 0)
            return 'WAITPAY'; //'待支付',
        if($order['pay_status'] == 1 &&  in_array($order['order_status'],array(0,1)) && $order['shipping_status'] != 1)
            return 'WAITSEND'; //'待发货',
    }
    if(($order['shipping_status'] == 1) && ($order['order_status'] == 1))
        return 'WAITRECEIVE'; //'待收货',
    if($order['order_status'] == 2)
        return 'WAITCCOMMENT'; //'待评价',
    if($order['order_status'] == 3)
        return 'CANCEL'; //'已取消',
    if($order['order_status'] == 4)
        return 'FINISH'; //'已完成',
    if($order['order_status'] == 5)
    	return 'CANCELLED'; //'已作废',
    return 'OTHER';
}

/**
 * 获取订单状态的 显示按钮
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return array()
 */
function orderBtn($order_id = 0, $order = array())
{
    if(empty($order))
        $order = M('Order')->where("order_id = $order_id")->find();
    /**
     *  订单用户端显示按钮
    去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
    取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
    确认收货  AND shipping_status=1 AND order_status=0
    评价      AND order_status=1
    查看物流  if(!empty(物流单号))
     */
    $btn_arr = array(
        'pay_btn' => 0, // 去支付按钮
        'cancel_btn' => 0, // 取消按钮
        'receive_btn' => 0, // 确认收货
        'comment_btn' => 0, // 评价按钮
        'shipping_btn' => 0, // 查看物流
        'return_btn' => 0, // 退货按钮 (联系客服)
    );


    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(($order['order_status']==0 || $order['order_status']==1) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['cancel_btn'] = 1; // 取消按钮 (联系客服)
        }
        if($order['shipping_status'] == 1 && $order['order_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }       
    }
    // 非货到付款
    else
    {
        if($order['pay_status'] == 0 && $order['order_status'] == 0) // 待支付
        {
            $btn_arr['pay_btn'] = 1; // 去支付按钮
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        if($order['pay_status'] == 1 && in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
        if($order['pay_status'] == 1 && $order['order_status'] == 1  && $order['shipping_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }
    if($order['order_status'] == 2)
    {
        $btn_arr['comment_btn'] = 1;  // 评价按钮
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($order['shipping_status'] != 0)
    {
        $btn_arr['shipping_btn'] = 1; // 查看物流
    }
    if($order['shipping_status'] == 2 && $order['order_status'] == 1) // 部分发货
    {            
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    
    return $btn_arr;
}

/**
 * 给订单数组添加属性  包括按钮显示属性 和 订单状态显示属性
 * @param type $order
 */
function set_btn_order_status($order)
{
    $order_status_arr = C('ORDER_STATUS_DESC');
    $order['order_status_code'] = $order_status_code = orderStatusDesc(0, $order); // 订单状态显示给用户看的
    $order['order_status_desc'] = $order_status_arr[$order_status_code];
    $orderBtnArr = orderBtn(0, $order);
    return array_merge($order,$orderBtnArr); // 订单该显示的按钮
}


/**
 * 支付完成修改订单
 * $order_sn 订单号
 * $pay_status 默认1 为已支付
 */
function update_pay_status($order_sn,$pay_status = 1)
{
	if(stripos($order_sn,'recharge') !== false){
		//用户在线充值
		$count = M('recharge')->where("order_sn = '$order_sn' and pay_status = 0")->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
		if($count == 0) return false;
		$order = M('recharge')->where("order_sn = '$order_sn'")->find();
		M('recharge')->where("order_sn = '$order_sn'")->save(array('pay_status'=>1,'pay_time'=>time()));
		accountLog($order['user_id'],$order['account'],0,'会员在线充值');
	}else{
		// 如果这笔订单已经处理过了
		$count = M('order')->where("order_sn = '$order_sn' and pay_status = 0")->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
		if($count == 0) return false;
		// 找出对应的订单
		$order = M('order')->where("order_sn = '$order_sn'")->find();
		// 修改支付状态  已支付
		M('order')->where("order_sn = '$order_sn'")->save(array('pay_status'=>1,'pay_time'=>time()));
		// 减少对应商品的库存
		minus_stock($order['order_id']);
		// 给他升级, 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
		update_user_level($order['user_id']);
		// 记录订单操作日志
		logOrder($order['order_id'],'订单付款成功','付款成功',$order['user_id']);
		//分销设置
		M('rebate_log')->where("order_id = {$order['order_id']}")->save(array('status'=>1));
		// 成为分销商条件
		$distribut_condition = tpCache('distribut.condition');
		if($distribut_condition == 1)  // 购买商品付款才可以成为分销商
			M('users')->where("user_id = {$order['user_id']}")->save(array('is_distribut'=>1));
	}

}

    /**
     * 订单确认收货
     * @param $id   订单id
     */
    function confirm_order($id,$user_id = 0){
        
        $where = "order_id = $id";
        $user_id && $where .= " and user_id = $user_id ";
        
        $order = M('order')->where($where)->find();
        if($order['order_status'] != 1)
            return array('status'=>-1,'msg'=>'该订单不能收货确认');
        
        $data['order_status'] = 2; // 已收货        
        $data['pay_status'] = 1; // 已付款        
        $data['confirm_time'] = time(); // 收货确认时间
        if($order['pay_code'] == 'cod'){
        	$data['pay_time'] = time();
        }
        $row = M('order')->where(array('order_id'=>$id))->save($data);
        if(!$row)        
            return array('status'=>-3,'msg'=>'操作失败');
        
        order_give($order);// 调用送礼物方法, 给下单这个人赠送相应的礼物
        
        //分销设置
        M('rebate_log')->where("order_id = $id")->save(array('status'=>2,'confirm'=>time()));
               
        return array('status'=>1,'msg'=>'操作成功');
    }

/**
 * 给订单送券送积分 送东西
 */
function order_give($order)
{
	$order_goods = M('order_goods')->where("order_id=".$order['order_id'])->cache(true)->select();
	//查找购买商品送优惠券活动
	foreach ($order_goods as $val)
    {
		if($val['prom_type'] == 3)
        {
			$prom = M('prom_goods')->where('type=3 and id='.$val['prom_id'])->find();
			if($prom){
				$coupon = M('coupon')->where("id=".$prom['expression'])->find();//查找优惠券模板
				if($coupon && $coupon['createnum']>0){					                                        
                    $remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
                    if($remain > 0)                                            
                    {
                        $data = array('cid'=>$coupon['id'],'type'=>$coupon['type'],'uid'=>$order['user_id'],'send_time'=>time());
                        M('coupon_list')->add($data);       
                        M('Coupon')->where("id = {$coupon['id']}")->setInc('send_num'); // 优惠券领取数量加一
                    }
				}
		 	}
		 }
	}
	
	//查找订单满额送优惠券活动
	$pay_time = $order['pay_time'];
	$prom = M('prom_order')->where("type>1 and end_time>$pay_time and start_time<$pay_time and money<=".$order['order_amount'])->order('money desc')->find();
	if($prom){
		if($prom['type']==3){
			$coupon = M('coupon')->where("id=".$prom['expression'])->find();//查找优惠券模板
			if($coupon){
				if($coupon['createnum']>0){
					$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
                    if($remain > 0)
                    {
                       $data = array('cid'=>$coupon['id'],'type'=>$coupon['type'],'uid'=>$order['user_id'],'send_time'=>time());
                       M('coupon_list')->add($data);           
                       M('Coupon')->where("id = {$coupon['id']}")->setInc('send_num'); // 优惠券领取数量加一
                    }				
				}
			}
		}else if($prom['type']==2){
			accountLog($order['user_id'], 0 , $prom['expression'] ,"订单活动赠送积分");
		}
	}
    $points = M('order_goods')->where("order_id = {$order[order_id]}")->sum("give_integral * goods_num");
    $points && accountLog($order['user_id'], 0,$points,"下单赠送积分");
}


/**
 * 查看商品是否有活动
 * @param goods_id 商品ID
 */

function get_goods_promotion($goods_id,$user_id=0){
	$now = time();
	$goods = M('goods')->where("goods_id=$goods_id")->find();
	$where = "end_time>$now and start_time<$now and id=".$goods['prom_id'];
	
	$prom['price'] = $goods['shop_price'];
	$prom['prom_type'] = $goods['prom_type'];
	$prom['prom_id'] = $goods['prom_id'];
	$prom['is_end'] = 0;
	
	if($goods['prom_type'] == 1){//抢购
		$prominfo = M('flash_sale')->where($where)->find();
		if(!empty($prominfo)){
			if($prominfo['goods_num'] == $prominfo['buy_num']){
				$prom['is_end'] = 2;//已售馨
			}else{
				//核查用户购买数量
				$where = "user_id = $user_id and order_status!=3 and  add_time>".$prominfo['start_time']." and add_time<".$prominfo['end_time'];
				$order_id_arr = M('order')->where($where)->getField('order_id',true);
				if($order_id_arr){
					$goods_num = M('order_goods')->where("prom_id={$goods['prom_id']} and prom_type={$goods['prom_type']} and order_id in (".implode(',', $order_id_arr).")")->sum('goods_num');
					if($goods_num < $prominfo['buy_limit']){
						$prom['price'] = $prominfo['price'];
					}
				}else{
					$prom['price'] = $prominfo['price'];
				}
			} 				
		}
	}
	
	if($goods['prom_type']==2){//团购
		$prominfo = M('group_buy')->where($where)->find();
		if(!empty($prominfo)){			
			if($prominfo['goods_num'] == $prominfo['buy_num']){
				$prom['is_end'] = 2;//已售馨
			}else{
				$prom['price'] = $prominfo['price'];
			}				
		}
	}
	if($goods['prom_type'] == 3){//优惠促销
		$parse_type = array('0'=>'直接打折','1'=>'减价优惠','2'=>'固定金额出售','3'=>'买就赠优惠券','4'=>'买M件送N件');
		$prominfo = M('prom_goods')->where($where)->find();
		if(!empty($prominfo)){
			if($prominfo['type'] == 0){
				$prom['price'] = $goods['shop_price']*$prominfo['expression']/100;//打折优惠
			}elseif($prominfo['type'] == 1){
				$prom['price'] = $goods['shop_price']-$prominfo['expression'];//减价优惠
			}elseif($prominfo['type']==2){
				$prom['price'] = $prominfo['expression'];//固定金额优惠
			}
		}
	}
	
	if(!empty($prominfo)){
		$prom['start_time'] = $prominfo['start_time'];
		$prom['end_time'] = $prominfo['end_time'];
	}else{
		$prom['prom_type'] = $prom['prom_id'] = 0 ;//活动已过期
		$prom['is_end'] = 1;//已结束
	}
	
	if($prom['prom_id'] == 0){
		M('goods')->where("goods_id=$goods_id")->save($prom);
	}
	return $prom;
}

/**
 * 查看订单是否满足条件参加活动
 * @param order_amount 订单应付金额
 */
function get_order_promotion($order_amount){
	$parse_type = array('0'=>'满额打折','1'=>'满额优惠金额','2'=>'满额送倍数积分','3'=>'满额送优惠券','4'=>'满额免运费');
	$now = time();
	$prom = M('prom_order')->where("type<2 and end_time>$now and start_time<$now and money<=$order_amount")->order('money desc')->find();
	$res = array('order_amount'=>$order_amount,'order_prom_id'=>0,'order_prom_amount'=>0);
	if($prom){
		if($prom['type'] == 0){
			$res['order_amount']  = round($order_amount*$prom['expression']/100,2);//满额打折
			$res['order_prom_amount'] = $order_amount - $res['order_amount'] ;
			$res['order_prom_id'] = $prom['id'];
		}elseif($prom['type'] == 1){
			$res['order_amount'] = $order_amount- $prom['expression'];//满额优惠金额
			$res['order_prom_amount'] = $prom['expression'];
			$res['order_prom_id'] = $prom['id'];
		}
	}
	return $res;		
}

/**
 * 计算订单金额
 * @param type $user_id  用户id
 * @param type $order_goods  购买的商品
 * @param type $shipping  物流code
 * @param type $shipping_price 物流费用, 如果传递了物流费用 就不在计算物流费
 * @param type $province  省份
 * @param type $city 城市
 * @param type $district 县
 * @param type $pay_points 积分
 * @param type $user_money 余额
 * @param type $coupon_id  优惠券
 * @param type $couponCode  优惠码
 */
 
function calculate_price($user_id=0,$order_goods,$shipping_code='',$shipping_price=0,$province=0,$city=0,$district=0,$pay_points=0,$user_money=0,$coupon_id=0,$couponCode='')
{    
    $cartLogic = new \Home\Logic\CartLogic();               
    $user = M('users')->where("user_id = $user_id")->find();// 找出这个用户
    
    if(empty($order_goods)) 
        return array('status'=>-9,'msg'=>'商品列表不能为空','result'=>'');  
    
    $goods_id_arr = get_arr_column($order_goods,'goods_id');
    $goods_arr = M('goods')->where("goods_id in(".  implode(',',$goods_id_arr).")")->getField('goods_id,weight,market_price,is_free_shipping'); // 商品id 和重量对应的键值对
    
        foreach($order_goods as $key => $val)
        {       
            // 如果传递过来的商品列表没有定义会员价
            if(!array_key_exists('member_goods_price',$val))  
            {
                $user['discount'] = $user['discount'] ? $user['discount'] : 1; // 会员折扣 不能为 0
                $order_goods[$key]['member_goods_price'] = $val['member_goods_price'] = $val['goods_price'] * $user['discount'];
            }
			//如果商品不是包邮的
            if($goods_arr[$val['goods_id']]['is_free_shipping'] == 0)
	            $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //累积商品重量 每种商品的重量 * 数量
				
            $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];    // 小计            
            $order_goods[$key]['store_count']  = getGoodNum($val['goods_id'],$val['spec_key']); // 最多可购买的库存数量
            if($order_goods[$key]['store_count'] <= 0) 
                return array('status'=>-10,'msg'=>$order_goods[$key]['goods_name']."库存不足,请重新下单",'result'=>'');  
            
            $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
            $cut_fee     += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price']; // 共节约
            $anum        += $val['goods_num']; // 购买数量
        }        
        
        // 优惠券处理操作
        $coupon_price = 0;
        if($coupon_id && $user_id)
        {
            $coupon_price = $cartLogic->getCouponMoney($user_id, $coupon_id,1); // 下拉框方式选择优惠券                    
        }        
        if($couponCode && $user_id)
        {                 
             $coupon_result = $cartLogic->getCouponMoneyByCode($couponCode,$goods_price); // 根据 优惠券 号码获取的优惠券             
             if($coupon_result['status'] < 0) 
               return $coupon_result;
             $coupon_price = $coupon_result['result'];            
        }
        // 处理物流
        if($shipping_price == 0)
        {
            $shipping_price = $cartLogic->cart_freight2($shipping_code,$province,$city,$district,$goods_weight);        
            $freight_free = tpCache('shopping.freight_free'); // 全场满多少免运费
            if($freight_free > 0 && $goods_price >= $freight_free)
               $shipping_price = 0;               
        }
        
        if($pay_points && ($pay_points > $user['pay_points']))
            return array('status'=>-5,'msg'=>"你的账户可用积分为:".$user['pay_points'],'result'=>''); // 返回结果状态                
        if($user_money  && ($user_money > $user['user_money']))
            return array('status'=>-6,'msg'=>"你的账户可用余额为:".$user['user_money'],'result'=>''); // 返回结果状态

       $order_amount = $goods_price + $shipping_price - $coupon_price; // 应付金额 = 商品价格 + 物流费 - 优惠券
       
       $pay_points = ($pay_points / tpCache('shopping.point_rate')); // 积分支付 100 积分等于 1块钱                              
       $pay_points = ($pay_points > $order_amount) ? $order_amount : $pay_points; // 假设应付 1块钱 而用户输入了 200 积分 2块钱, 那么就让 $pay_points = 1块钱 等同于强制让用户输入1块钱               
       $order_amount = $order_amount - $pay_points; //  积分抵消应付金额       
      
       $user_money = ($user_money > $order_amount) ? $order_amount : $user_money;  // 余额支付原理等同于积分
       $order_amount = $order_amount - $user_money; //  余额支付抵应付金额
      
       $total_amount = $goods_price + $shipping_price;
           //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 积分  余额  优惠券
        $result = array(
            'total_amount'      => $total_amount, // 商品总价
            'order_amount'      => $order_amount, // 应付金额
            'shipping_price'    => $shipping_price, // 物流费
            'goods_price'       => $goods_price, // 商品总价
            'cut_fee'           => $cut_fee, // 共节约多少钱
            'anum'              => $anum, // 商品总共数量
            'integral_money'    => $pay_points,  // 积分抵消金额
            'user_money'        => $user_money, // 使用余额
            'coupon_price'      => $coupon_price,// 优惠券抵消金额
            'order_goods'       => $order_goods, // 商品列表 多加几个字段原样返回
        );        
    return array('status'=>1,'msg'=>"计算价钱成功",'result'=>$result); // 返回结果状态
}

/**
 * 获取商品一二三级分类
 * @return type
 */
function get_goods_category_tree(){
	$result = array();
	$cat_list = M('goods_category')->where("is_show = 1")->order('sort_order')->cache(true)->select();//所有分类
	
	foreach ($cat_list as $val){
		if($val['level'] == 2){
			$arr[$val['parent_id']][] = $val;
		}
		if($val['level'] == 3){
			$crr[$val['parent_id']][] = $val;
		}
		if($val['level'] == 1){
			$tree[] = $val;
		}
	}

	foreach ($arr as $k=>$v){
		foreach ($v as $kk=>$vv){
			$arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
		}
	}
	
	foreach ($tree as $val){
		$val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
		$result[$val['id']] = $val;
	}
	return $result;
}

function getAge($birthday) {
$age = 0;
$year = $month = $day = 0;
if (is_array($birthday)) {
extract($birthday);
} else {
if (strpos($birthday, '-') !== false) {
list($year, $month, $day) = explode('-', $birthday);
$day = substr($day, 0, 2); //get the first two chars in case of '2000-11-03 12:12:00'
}
}
$age = date('Y') - $year;
if (date('m') < $month || (date('m') == $month && date('d') < $day)) $age--;
return $age;
} 
//将二维数组格式化为一维数组
function formatArray($array, $field = 'id') {
    $res = array();
    foreach($array as $v) {
        $res[] = $v[$field];
    }

    $res=implode(",",$res);
    return $res;
}

function getUserFen($uid){

    $maps['user_id']=$uid;

    $user=D("Users")->where($maps)->find();

    $fenggeid=$user['fengge_id'];

    switch ($fenggeid)
    {
     case 1:
     return "初始";
     break;
     case 2:
     return "潜在";
     break;
     case 3:
     return "新手";
     break;
     case 4:
     return "活跃";
     break;
     case 5:
     return "忠诚";
     break;
     case 7:
     return "游离";
     break;
     case 8:
     return "沉寂";
     break;
     case 10:
     return "回归";
     break;
     case 11:
     return "老友";
     break;
     case 12:
     return "复购";
     break;
        case 0:
       return "初始";
     break;
     
    }


}

function getTypes($uid,$total_amount,$type)
{
  
    $maps['user_id']=$uid;
    $user=D("users")->where($maps)->find();

    $yeartime1= date("Y-m-d H:i:s", strtotime("-1 year"));
    $yeartime2= date("Y-m-d H:i:s", strtotime("-2 year"));

    $yeartime1=strtotime($yeartime1);
    $yeartime2=strtotime($yeartime2);
   
        //注册的时候
        if($type==1){
            $datas1['fengge_id']=2;
            $where['user_id']=$uid;
            D("users")->where($where)->save($datas1);
            //echo M()->getlastsql();
        }elseif($type==2){
                
            //首次购买

            // if($user['fengge_id']==1){
            //     $datas['fengge_id']=11;
            // }elseif($user['fengge_id']==0){
            //     $datas['fengge_id']=12;
            // }else{
            //     $datas['fengge_id']=3;
            // }
            $datas['fengge_id']=12;
            // if($user['fengge_id']==0){
            //     $datas['fengge_id']=11;
            // }else{
            //     $datas['fengge_id']=3;
            // }

            $where['user_id']=$uid;
            $res=D("users")->where($where)->save($datas);

            if($res){
                $vdata['old_fengge']=$user['fengge_id'];
                $vdata['new_fengge']=$datas['fengge_id'];
                $vdata['create_time']=time();
                $vdata['uid']=$uid;
                D("fengge_log")->add($vdata);
            }
        
        }else{
           
            if($user['fengge_id']==3){

                //判断时间
                $totaltime=time()-180*3600*24;
                $first_time=$user['first_time'];

                if($totaltime>$first_time){
               
                    $datas['fengge_id']=12;
                    $where['user_id']=$uid;
                    $res=D("users")->where($where)->save($datas);
                    if($res){
                        $vdata['old_fengge']=$user['fengge_id'];
                        $vdata['new_fengge']=12;
                        $vdata['create_time']=time();
                        $vdata['uid']=$uid;
                        D("fengge_log")->add($vdata);
                    }

                }else{
                    $yeartime1=time()-180*3600*24;
                    $count1 = M('order')->where("user_id=$uid AND pay_status=1 and  add_time>$yeartime1  and order_status not in (3,5)")->count();
                    if ($count1>=3) {
                        $datas['fengge_id']=4;
                        $where['user_id']=$uid;
                        $res=D("users")->where($where)->save($datas);
                        if($res){
                            $vdata['old_fengge']=$user['fengge_id'];
                            $vdata['new_fengge']=4;
                            $vdata['create_time']=time();
                            $vdata['uid']=$uid;
                            D("fengge_log")->add($vdata);
                        }
                    }
                }


            }elseif($user['fengge_id']==4){

                $count = M('order')->where("user_id=$uid AND pay_status=1 and  add_time>$yeartime1  and order_status not in (3,5)")->count();

              //echo D("users")->getlastsql();

                if($count>=5){
                    // echo 'b4444';
                    $datas['fengge_id']=5;
                    $where['user_id']=$uid;
                    $res=D("users")->where($where)->save($datas);
                    if($res){
                        $vdata['old_fengge']=$user['fengge_id'];
                        $vdata['new_fengge']=5;
                        $vdata['create_time']=time();
                        $vdata['uid']=$uid;
                        D("fengge_log")->add($vdata);
                    }
                }
            }elseif($user['fengge_id']==11){
            
                $count = M('order')->where("user_id=$uid AND pay_status=1 and  add_time>$yeartime1  and order_status not in (3,5)")->count();

                $yeartime1=time()-180*3600*24;

                $count1 = M('order')->where("user_id=$uid AND pay_status=1 and  add_time>$yeartime1  and order_status not in (3,5)")->count();

                if($count>=5){
                    $datas['fengge_id']=5;
                    $where['user_id']=$uid;
                    $res=D("users")->where($where)->save($datas);
                    if($res){
                        $vdata['old_fengge']=$user['fengge_id'];
                        $vdata['new_fengge']=5;
                        $vdata['create_time']=time();
                        $vdata['uid']=$uid;
                        D("fengge_log")->add($vdata);
                    }
                }elseif($count1>=3){
                    $datas['fengge_id']=4;
                    $where['user_id']=$uid;
                    $res=D("users")->where($where)->save($datas);
                    if($res){
                        $vdata['old_fengge']=$user['fengge_id'];
                        $vdata['new_fengge']=4;
                        $vdata['create_time']=time();
                        $vdata['uid']=$uid;
                        D("fengge_log")->add($vdata);
                    }
                }

            }elseif($user['fengge_id']==12){

                $count = M('order')->where("user_id=$uid AND pay_status=1 and  add_time>$yeartime1  and order_status not in (3,5)")->count();

                if($count>=5){

                    $datas['fengge_id']=5;
                    $where['user_id']=$uid;
                    $res=D("users")->where($where)->save($datas);
                    if($res){
                        $vdata['old_fengge']=$user['fengge_id'];
                        $vdata['new_fengge']=5;
                        $vdata['create_time']=time();
                        $vdata['uid']=$uid;
                        D("fengge_log")->add($vdata);
                    }
                }

            }

        }

       
}

//计算友好时间差
function friendlyTimes($sTime,$type = 'normal',$alt = 'false') {

//sTime=源时间，cTime=当前时间，dTime=时间差

$cTime  = time();

$dTime  = $cTime - $sTime;

$dDay  = intval(date("Ymd",$cTime)) - intval(date("Ymd",$sTime));

$dYear  = intval(date("Y",$cTime)) - intval(date("Y",$sTime));

//normal：n秒前，n分钟前，n小时前，日期

if($type=='normal'){

  if( $dTime < 60 ){

   echo $dTime."秒前";

  }elseif( $dTime < 3600 ){

   echo intval($dTime/60)."分钟前";

  }elseif( $dTime >= 3600 && $dDay == 0  ){

   echo intval($dTime/3600)."小时前";

  }elseif($dYear==0){

   echo date("m-d",$sTime);

  }else{

   echo date("m-d",$sTime);

  }

//full: Y-m-d , H:i:s

}elseif($type=='full'){

  echo date("Y-m-d, H:i",$sTime);

}

}
//减少库存
function jskc($id){
    $maps['goods_id'] = $id;
    $info = D('goods')->where($maps)->find();
    $data['store_count'] = $info['store_count'] - 1;
    $add = D('goods')->where($maps)->save($data);

}

/*
 *修改库存
 *id  商品ID 
 *goods_num    商品数量
 *resource_id  根据不同情况填写不同的ID(门店,仓库,在途,退货在途)
 *stock_type   库存类型（1 总部库存、2 发货在途、3退货在途、4门店）
 *updata_type  1 减少库存 2 新增库存
 *@return      1 修改成功  0 修改失败
 */
function jskc_new($id,$goods_num,$resource_id,$stock_type=1,$updata_type=1){
    $maps['good_id'] = $id;
    $maps['resource_id'] = $resource_id;
    $maps['stock_type'] = $stock_type;

    $info = D('stock')->where($maps)->find();
    //如果没有该产品 则新增数据
    if (!$info) {
        $maps['number'] = 0;
        $maps['create_time'] = time();
        $get_id = D('stock')->add($maps);
        $info = D('stock')->where('id='.$get_id)->find();
    }
    if ($updata_type==1) {
        $data['number'] = (int)$info['number'] - (int)$goods_num;
        if ($data['number']<0) {
            return 0;
        }
    }else{
        $data['number'] = (int)$info['number'] + (int)$goods_num;

    }
    $save = D('stock')->where($maps)->save($data);
    // 添加至库存记录表中
    unset($info['id']);
    $results = M('stock_record')->add($info);

    if ($save && $results) {
        return 1;
    }else{
        return 0;
    }
}


/*
 *新增门店流水记录表
 *id  商品ID 
 *goods_num    商品数量
 *store_id     门店ID
 *shipment_style   出货类型 1 进货 2 销售 3 返货
 *@return      1 新增成功  0 新增失败
 */
function addWaterRecord($id,$goods_number,$store_id,$shipment_style){
    $maps['goods_id'] = $id;//商品ID
    $maps['number'] = $goods_number;//商品数量
    $maps['store_id'] = $store_id;//门店ID
    $maps['shipment_style'] = $shipment_style;//类型
    $maps['create_time'] = time();//创建时间

    $result = M('sales_water')->add($maps);
    if ($result) {
        return 1;
    }else{
        return 0;
    }
}


/*
 *删除门店流水记录表
 *id  商品ID 
 *goods_num    商品数量
 *store_id     门店ID
 *shipment_style   出货类型 1 进货 2 销售 3 返货
 *$time        订单生成时间
 *@return      1 新增成功  0 新增失败
 */
function delWaterRecord($id,$goods_number,$store_id,$shipment_style,$time){
    $maps['goods_id'] = $id;//商品ID
    $maps['number'] = $goods_number;//商品数量
    $maps['store_id'] = $store_id;//门店ID
    $maps['shipment_style'] = $shipment_style;//类型
    //删除当天的销售流水
    $this_date = date('Y-m-d',$time);
    $this_date1 = $this_date.' 23:59:59';
    $began = strtotime($this_date);
    $end = strtotime($this_date1);
    $maps['create_time'] = array('between',array($began,$end));//类型
    $result = M('sales_water')->where($maps)->order('id desc')->limit('1')->delete();

    if ($result) {
        return 1;
    }else{
        return 0;
    }
}



/*
 *新增仓库流水记录表
 *id  商品ID 
 *goods_num    商品数量
 *repertory_id     仓库ID
 *shipment_style   出货类型 1 进货 2 销售 3 返货
 *@return      1 新增成功  0 新增失败
 */
function addWaterRepertoryRecord($id,$goods_number,$repertory_id,$shipment_style){
    $maps['goods_id'] = $id;//商品ID
    $maps['number'] = $goods_number;//商品数量
    $maps['repertory_id'] = $repertory_id;//门店ID
    $maps['shipment_style'] = $shipment_style;//类型
    $maps['create_time'] = time();//创建时间

    $result = M('sales_water_repertory')->add($maps);
    if ($result) {
        return 1;
    }else{
        return 0;
    }
}


/*
 *新增仓库流水记录表       (开启事务之后的方法事务)
 *id  商品ID 
 *goods_num    商品数量
 *repertory_id     仓库ID
 *shipment_style   出货类型 1 进货 2 销售 3 返货
 *@return      1 新增成功  0 新增失败
 */
function addWaterRepertoryRecord_trans($tranDb,$id,$goods_number,$repertory_id,$shipment_style){
    $maps['goods_id'] = $id;//商品ID
    $maps['number'] = $goods_number;//商品数量
    $maps['repertory_id'] = $repertory_id;//门店ID
    $maps['shipment_style'] = $shipment_style;//类型
    $maps['create_time'] = time();//创建时间

    $result = $tranDb->table('tp_sales_water_repertory')->add($maps);

    if ($result) {
        return 1;
    }else{
        return 0;
    }
}



/*
 *检测库存是否充足
 *id  商品ID 
 *resource_id  根据不同情况填写不同的ID(门店,仓库,在途,退货在途)
 *stock_type   库存类型（1 总部库存、2 发货在途、3退货在途、4门店）
 *1 库存充足
 *0 库存不足
 */

function checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type=1){   
    $map['good_id'] = $goods_id;
    $map['resource_id'] = $resource_id;
    $map['stock_type'] = $stock_type;
    // 获取库存数量
    $num = M('stock')->where($map)->getField('number');
    $lock_number = M('stock')->where($map)->getField('lock_number');
    $realnum = $num - $lock_number;
    if ($realnum>=$goods_num) {
        return 1;
    }else{
        return 0;
    }
}


/*
 *检测库存是否充足(总库存)   新增补货订单时候使用
 *goods_id  商品ID 
 *stock_type   库存类型（1 总部库存、2 发货在途、3退货在途、4门店）
 *1 库存充足
 *0 库存不足
 */

function checkAllGoodsNum($goods_id,$goods_num,$stock_type=1){   
    $map['good_id'] = $goods_id;
    $map['stock_type'] = $stock_type;
    // 获取库存数量
    $num = M('stock')->where($map)->sum('number');
    if ($num>=$goods_num) {
        return 1;
    }else{
        return 0;
    }
}


/*
 *门店出库表新增记录
 *store_id 门店ID
 *code  出库订单编号
 *@return  成功 返回该条记录ID 失败 返回0
 */
function store_stock_out($store_id,$code){
    //新建出库表（门店）记录 
    $data['store_id'] = $store_id;
    $data['code'] = $code;
    $data['create_time'] = time();
    $result = M('outstock_store')->add($data);
    if ($result) {
        return $result;
    }else{
        return 0;
    }
}

/*
 *门店出库表及出库记录表数据操作
 *outstock_id 出库记录表ID
 *goods_id  商品ID
 *goods_name 商品名称
 *count     数量
 *@return  成功 返回该条记录ID 失败 返回0
 */

function store_stock_out_detail($outstock_id,$goods_id,$goods_name,$count){
    $data['outstock_id'] = $outstock_id;
    $data['goods_id'] = $goods_id;
    $data['goods_name'] = $goods_name;
    $data['count'] = $count;
    $data['create_time'] = time();
    $result = M('outstock_store_detail')->add($data);
    if ($result) {
        return $result;
    }else{
        return 0;
    }
}

/*
 *仓库出库表新增记录
 *in_storage_type 出库类型 0 正常出库 1转库出库 2 门店发货 3 返回总部
 *sn  出库订单编号
 *repertory_id  出库仓库
 *creator  操作人名
 *creator_id  操作人ID
 *@return  成功 返回该条记录ID 失败 返回0
 */
function repertory_stock_out($in_storage_type,$sn,$repertory_id,$creator,$creator_id,$reason=''){
    //新建出库表（门店）记录 
    $data['in_storage_type'] = $in_storage_type;
    $data['in_storage_sn'] = $sn;//入库单编号
    $data['repertory_id'] = $repertory_id;
    $data['reason'] = $reason;
    $data['create_time'] = time();
    $data['creator'] = $creator;
    $data['creator_id'] = $creator_id;
    $result = M('outstock_repertory')->add($data);
    if ($result) {
        return $result;
    }else{
        return 0;
    }
}

/*
 *仓库出库表及出库记录表数据操作
 *outstock_id 出库记录表ID
 *repertory_id  出库仓库
 *goods_id  商品ID
 *goods_name 商品名称
 *count     数量
 *@return  成功 返回该条记录ID 失败 返回0
 */

function repertory_stock_out_detail($outstock_id,$repertory_id,$goods_id,$goods_name,$count){
    $data['outstock_id'] = $outstock_id;
    $data['repertory_id'] = $repertory_id;
    $data['goods_id'] = $goods_id;
    $data['goods_name'] = $goods_name;
    $data['count'] = $count;
    $data['create_time'] = time();
    $result = M('outstock_repertory_detail')->add($data);
    if ($result) {
        return $result;
    }else{
        return 0;
    }
}

/*
 *将库存转化成锁定数量
 *id  商品ID 
 *goods_num    商品数量
 *resource_id  根据不同情况填写不同的ID(门店,仓库,在途,退货在途)
 *stock_type   库存类型（1 总部库存、2 发货在途、3退货在途、4门店）
 *updata_type  1 清空lock_numbet 2 新增lock_numbet 3 将lock_number转回即时库存
 *@return      1 修改成功  0 修改失败
 */

function updata_lock_number($id,$goods_num,$resource_id,$stock_type=4,$updata_type=2){
    $maps['good_id'] = $id;
    $maps['resource_id'] = $resource_id;
    $maps['stock_type'] = $stock_type;

    $info = D('stock')->where($maps)->find();
    //如果没有该产品 则返回错误
    
    if (!$info) {
        return 0;
    }

    //减少lock_number
    if ($updata_type==1) {
        $data['lock_number'] = (int)$info['lock_number'] - (int)$goods_num;
        if ($data['lock_number']) {
            return 0;
        }
    //新增lock_number
    }else if($updata_type==2){
        $free_number = (int)$info['number'] - (int)$goods_num;
        if ($free_number<0) {
            return 0;
        }
        $data['lock_number'] = (int)$goods_num;
    }

    
    $save = D('stock')->where($maps)->save($data);

    if ($save) {
        return 1;
    }else{
        return 0;
    }
}




// 获取admin姓名
function getUserName($user_id){
    $user_name = M('admin')->where('admin_id='.$user_id)->getField('user_name');
    return $user_name;
} 
// 获取商品SKU
function getSku($goods_id){
    $sku = M('spec_goods_price')->where('goods_id='.$goods_id)->getField('sku');
    return $sku;
} 

// 获取商品spu   单位
function getSpu($goods_id){
    $spu = M('goods')->where('goods_id='.$goods_id)->getField('spu');
    return $spu;
} 

// 获取门店名称
function getStoreName($store_id){
    $store_name = M('store')->where('store_id='.$store_id)->getField('store_name');
    return $store_name;
} 


// 获取仓库名称
function getRepertoryName($repertory_id){
    $repertory_name = M('repertory')->where('id='.$repertory_id)->getField('repertory_name');
    return $repertory_name;
} 


// 获取入库单详情总数量
function getRuKuCount($storage_stocks_id){
    $count = M('warehousing_detail')->where('storage_stocks_id='.$storage_stocks_id)->sum('count');
    return $count;
}


// 获取user姓名
function getName_users($user_id){
    $user_name = M('users')->where('user_id='.$user_id)->getField('nickname');
    return $user_name;
}

// 获取user手机号
function getUserMobile($user_id){
    $mobile = M('users')->where('user_id='.$user_id)->getField('mobile');
    return $mobile;
}

//获取沟通列表类型
function getCommunicationType($intType){
    $type = array('','购买回访','活动通知','投诉处理','生日祝福','其他交流');
    $typeName = $type[$intType];
    return $typeName;
}
/**
 * 新增提领记录表数据
 * $id   记录ID
 * box   盒数
 * count 根数
 * over_box 剩余盒数
 * over_count 剩余根数
 * admin_id   创建人ID
 */

function add_tl_record($id,$box,$count,$over_box,$over_count,$admin_id){
    $data['deposit_id'] = $id;
    $data['tl_box'] = $box;
    $data['tl_count'] = $count;
    $data['over_box'] = $over_box;
    $data['over_count'] = $over_count;
    $data['creator'] = getUserName($admin_id);
    $data['create_time'] = time();
    $result = M('tlrecord')->add($data);
    if ($result) {
        return "1";
    }else{
        return "0";
    }
}


/**
 * 获取退货状态
 */
function getReturnGoodsStatus($status){
    switch ($status) {
        case '1':
            return "待库管确认";
            break;
        case '2':
            return "库管已拒绝";
            break;
        case '3':
            return "库管已确认";
            break;
        case '4':
            return "在途";
            break;
        case '5':
            return "退货完成";
            break;
        
    }
}
