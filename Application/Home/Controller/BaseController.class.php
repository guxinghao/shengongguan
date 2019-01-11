<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-22
 */
namespace Home\Controller;
use Think\Controller;
class BaseController extends Controller {
    public $session_id;
    public $cateTrre = array();
    /*
     * 初始化操作
     */
    public function _initialize() { 
      $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机                
        if(isMobile())
            cookie('is_mobile','1',3600); 
        else 
            cookie('is_mobile','0',3600);

        $type=$_REQUEST['type'];

        $mobile=$_REQUEST['mobile'];//获取登录手机号

        $inc_type =  I('get.inc_type','basic');
        $config = tpCache($inc_type);
        $appid1=$config["appid1"];
        $secret1=$config["secret1"];
        $appid2=$config["appid2"];
        $secret2=$config["secret2"];
        //1为参公馆2为大连
        if($type==1){
             //获取微信头像和昵称
            $appid = $appid1;
            $secret = $secret1;
        }else{
            //获取微信头像和昵称
            $appid = $appid2;
            $secret =$secret2;
        }

        $code=$_REQUEST['code'];
        $state=$_REQUEST['state'];
        //获取头像及openid
        if (!empty($code)&&!empty($state)) {
            //获取opendid
            session('weixin',$type);
           //
            $openurl="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$secret."&code=".$code."&grant_type=authorization_code";
            $res=file_get_contents($openurl);

            $json = json_decode($res, true); 
            $wecha_id=$json['openid'];

            session('openid',$wecha_id);
            session('type',$type);
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";

            $res=file_get_contents($url);
            $obj=json_decode($res); 
        
            $access_token=$obj->access_token;

            $openid=$wecha_id;

            $urls="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";


            $users=file_get_contents($urls);
            $users=json_decode($users); 

            $nickname=$users->nickname;
            $headimgurl=$users->headimgurl;

            //session存储头像路径
            session('head_pic',$headimgurl);

            session('nickname',$nickname);
            session('headimgurl',$headimgurl);
        }


        if(!empty($code)&&!empty($state)&&session('uid')){
           
            if($wecha_id){
                if($type==1){
                   $openmaps['openid1']=$wecha_id;
                }else{
                    $openmaps['openid2']=$wecha_id;
                }
                $user=M("Users")->where($openmaps)->find();
                if($user){
                    session('uid',$user['user_id']);

                    //更新头像  
                    if(empty($user['nickname'])){
                      $opendata['nickname']=$nickname;
                    }
                    //if(empty($user['headimgurl'])){
                      $opendata['head_pic']=$headimgurl;
                    //}
                       
                    //$opendata['id']=$user['id'];
                    $opendata['update_time']=time();
                    //$ress=D("Users")->save($opendata);
                    $oos = M("Users")->where($openmaps)->save($opendata); 

                }else{
                    $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";

                    $res=file_get_contents($url);
                    $obj=json_decode($res); 
                
                    $access_token=$obj->access_token;

                    $openid=$wecha_id;

                    $urls="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";


                    $users=file_get_contents($urls);
                    $users=json_decode($users); 

                    $nickname=$users->nickname;
                    $headimgurl=$users->headimgurl;
                    session('nickname',$nickname);
                    session('headimgurl',$headimgurl);

                   /* $wh['mobile'] = $mobile;
                    $user=M("Users")->where($wh)->find();
                    $u_id = $user['user_id'];
                    //更新头像  
                    if(empty($user['nickname'])){
                        $opendata['nickname']=$nickname;
                    }
                        $opendata['head_pic']=$headimgurl;
                       
                        //$opendata['id']=$u_id;
                        $opendata['update_time']=time();
                        //$ress=D("Users")->save($opendata);
                        M("Users")->where($wh)->save($opendata);
*/
                }
            }       
        }

        //$this->public_assign(); 
}
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
        
       $TPshop_config = array();
       $tp_config = M('config')->cache(true,TPshop_CACHE_TIME)->select();       
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $TPshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }       	  
          $TPshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }                        
       
       $goods_category_tree = get_goods_category_tree();    
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);                     
       $brand_list = M('brand')->cache(true,TPshop_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();              
       $this->assign('brand_list', $brand_list);
       $this->assign('TPshop_config', $TPshop_config);          
    }



    /*
     * 检查库存数量是否充足
     * goods_id    商品ID
     * goods_num   商品数量
     * resource_id 仓库 或者门店ID
     * stock_type  类型 1 总部库存 4门店 默认仓库
     */
    
    public function checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type=1)
    {   
        $map['good_id'] = $goods_id;
        if ($resource_id) {
            $map['resource_id'] = $resource_id;
        }
        $map['stock_type'] = $stock_type;
        // 获取库存数量
        $num = M('stock')->where($map)->getField('number');
        if ($num>=$goods_num) {
            return true;
        }else{
            $this->error("库存不足!");
        }
    }


}