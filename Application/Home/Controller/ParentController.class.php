<?php  
namespace Home\Controller;
use Think\Controller;
/**
 * 父类控制器，需要继承
 * @file ParentController.class.php
 * @author Gary <lizhiyong2204@sina.com>
 * @date 2015年8月4日
 * @todu
 */
class ParentController extends Controller { 
	protected $options = array (
	'token' => '', // 填写你设定的key
	'encodingaeskey' => '', // 填写加密用的EncodingAESKey
	'appid' => '', // 填写高级调用功能的app id
	'appsecret' => '', // 填写高级调用功能的密钥
	'debug' => false,
	'logcallback' => ''
	); 
	public $errCode = 40001; 
	public $errMsg = "no access"; 
 
	/**
	* 获取access_token
	* @return mixed|boolean|unknown
	*/
	public function getToken(){
		$cache_token = S('exp_wechat_pay_token');
		if(!empty($cache_token)){
			return $cache_token;
		}
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
		$url = sprintf($url,$this->options['appid'],$this->options['appsecret']); 
		$result = $this->http_get($url);
		$result = json_decode($result,true); 
		if(empty($result)){
			return false;
		} 
		S('exp_wechat_pay_token',$result['access_token'],array('type'=>'file','expire'=>3600));
		return $result['access_token'];
	}
	 
	/**
	* 发送客服消息
	* @param array $data 消息结构{"touser":"OPENID","msgtype":"news","news":{...}}
	*/
	public function sendCustomMessage($data){
		$token = $this->getToken();
		if (empty($token)) return false; 
		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=%s';
		$url = sprintf($url,$token);
		$result = $this->http_post($url,self::json_encode($data));
		if ($result)
		{
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	* 发送模板消息
	* @param unknown $data
	* @return boolean|unknown
	*/
	public function sendTemplateMessage($data){
		$token = $this->getToken();
		if (empty($token)) return false;
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=%s";
		$url = sprintf($url,$token);
		$result = $this->http_post($url,self::json_encode($data));
		if ($result){
			$json = json_decode($result,true);
			if (!$json || !empty($json['errcode'])) {
				$this->errCode = $json['errcode'];
				$this->errMsg = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}


	public function getFileCache($name){
		return S($name);
	}

	/**
	* 微信api不支持中文转义的json结构
	* @param array $arr
	*/
	static function json_encode($arr) {
		$parts = array ();
		$is_list = false;
		//Find out if the given array is a numerical array
		$keys = array_keys ( $arr );
		$max_length = count ( $arr ) - 1;
		if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
			$is_list = true;
			for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
				if ($i != $keys [$i]) { //A key fails at position check.
					$is_list = false; //It is an associative array.
					break;
				}
			}
		}
		foreach ( $arr as $key => $value ) {
			if (is_array ( $value )) { //Custom handling for arrays
				if ($is_list)
					$parts [] = self::json_encode ( $value ); /* :RECURSION: */
				else
					$parts [] = '"' . $key . '":' . self::json_encode ( $value ); /* :RECURSION: */
			} else {
				$str = '';
				if (! $is_list)
					$str = '"' . $key . '":';
			//Custom handling for multiple data types
			if (!is_string ( $value ) && is_numeric ( $value ) && $value<2000000000)
				$str .= $value; //Numbers
			elseif ($value === false)
				$str .= 'false'; //The booleans
			elseif ($value === true)
				$str .= 'true';
			else
				$str .= '"' . addslashes ( $value ) . '"'; //All other things
				// :TODO: Is there any more datatype we should be in the lookout for? (Object?)
				$parts [] = $str;
			}
		}
		$json = implode ( ',', $parts );
		if ($is_list)
			return '[' . $json . ']'; //Return numerical JSON
			return '{' . $json . '}'; //Return associative JSON
		}

	/**
	+----------------------------------------------------------
	* 生成随机字符串
	+----------------------------------------------------------
	* @param int $length 要生成的随机字符串长度
	* @param string $type 随机码类型：0，数字+大小写字母；1，数字；2，小写字母；3，大写字母；4，特殊字符；-1，数字+大小写字母+特殊字符
	+----------------------------------------------------------
	* @return string
	+----------------------------------------------------------
	*/
	static public function randCode($length = 5, $type = 2){
		$arr = array(1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz", 3 => "ABCDEFGHIJKLMNOPQRSTUVWXYZ", 4 => "~@#$%^&*(){}[]|");
		if ($type == 0) {
			array_pop($arr);
			$string = implode("", $arr);
		} elseif ($type == "-1") {
			$string = implode("", $arr);
		} else {
			$string = $arr[$type];
		}
		$count = strlen($string) - 1;
		$code = '';
		for ($i = 0; $i < $length; $i++) {
			$code .= $string[rand(0, $count)];
		}
		return $code;
	} 


	/**
	* GET 请求
	* @param string $url
	*/
	private function http_get($url){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}

	/**
	* POST 请求
	* @param string $url
	* @param array $param
	* @param boolean $post_file 是否文件上传
	* @return string content
	*/
	private function http_post($url,$param,$post_file=false){
		$oCurl = curl_init();
		if(stripos($url,"https://")!==FALSE){
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
		}
		if (is_string($param) || $post_file) {
			$strPOST = $param;
		} else {
			$aPOST = array();
			foreach($param as $key=>$val){
				$aPOST[] = $key."=".urlencode($val);
			}
			$strPOST = join("&", $aPOST);
		}
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($oCurl, CURLOPT_POST,true);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}
}
