<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-22
 */namespace Home\Controller;
use Think\Page;
use Think\Verify;
class IndexController extends BaseController {
    

    /**
     *  公告详情页
     */
    public function notice(){
        $this->display();
    }

     /**
     *  公告详情页
     */
    public function test(){
        $this->display();
    }
    
    
    // 二维码
    public function qr_code(){        
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.tp-shop.cn/Home/Index/erweima/data/www.99soubao.com
         require_once 'ThinkPHP/Library/Vendor/phpqrcode/phpqrcode.php';
          //import('Vendor.phpqrcode.phpqrcode');
            error_reporting(E_ERROR);            
            $url = urldecode($_GET["data"]);
            \QRcode::png($url);          
    }
    
    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';
        
        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);        
    }
    
    // 促销活动页面
    public function promoteList()
    {                          
        $Model = new \Think\Model();
        $goodsList = $Model->query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id   where ".time()." > start_time  and ".time()." < end_time");                        
        $brandList = M('brand')->getField("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        $this->display();
    }    
    function truncate_tables (){
        $model = new \Think\Model(); // 实例化一个model对象 没有对应任何数据表
        $tables = $model->query("show tables");
        $table = array('tp_admin','tp_config','tp_region','tp_system_module','tp_admin_role','tp_system_menu');
        foreach($tables as $key => $val)
        {                                    
            if(!in_array($val['tables_in_TPshop'], $table))                             
                echo "truncate table ".$val['tables_in_TPshop'].' ; ';
                echo "<br/>";         
        }                
    }
//海参讲堂
    //海参鉴别    
    public function index(){
        $maps['cat_id'] = 2;
        $maps['is_top'] = 1;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 2;
        $top['is_recommed'] = 1;
        $top['is_open'] = 1;
        $r = D('article')->where($top)->order('orderby asc')->limit('10')->select();
        $where['cate_id'] = 2;
        $data = M('share')->where($where)->order("id desc")->limit("10")->select();
        $this->assign('data',$data);
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display();
    }
        public function ajax_index(){
        $page = $_REQUEST['p'];   
        $start = $page*10;
        $where['cate_id'] = 2;
        $data = M('share')->where($where)->order("id desc")->limit("$start,10")->select();
        $this->assign('data',$data);
        $this->display();
    }
    public function show(){
        $id = $_REQUEST['id'];
        $maps['article_id'] = $id;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 2;
        $top['is_recommed'] = 1;
        $r = D('article')->where($top)->order('orderby asc')->select();
        $where['cate_id'] = 2;
        $data = M('share')->where($where)->order("id desc")->limit("10")->select();
        $this->assign('data',$data);
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display('index');       
    }
       public function show_a(){
        $id = $_REQUEST['id'];
        $maps['article_id'] = $id;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 3;
        $top['is_recommed'] = 1;
        $r = D('article')->where($top)->order('orderby asc')->select();
        $where['cate_id'] = 2;
        $data = M('share')->where($where)->order("id desc")->limit("10")->select();
        $this->assign('data',$data);
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display('trepang_cuisine');
        
    }
    //专业发海参
    public function trepang_soak(){
        $maps['cat_id'] = 1;
        $maps['is_top'] = 1;
        $info = D('article')->where($maps)->find();
        $where['cate_id'] = 1;
        $data = M('share')->where($where)->order("id desc")->limit("10")->select();
        $this->assign('data',$data);
        $this->assign('info',$info);
        $this->display();
    }
     public function ajax_soak(){
        $page = $_REQUEST['p'];   
        $start = $page*10;
        $where['cate_id'] = 1;
        $data = M('share')->where($where)->order("id desc")->limit("$start,10")->select();

        $this->assign('data',$data);
        $this->display();
    }
    //送至门店泡发
    public function soak(){
        if(session('uid')){
            $info = M('store')->where('is_forbid=0')->select();
            $this->assign('info',$info);
            $this->display(); 
        }else{
            $this->display("Member/login");
        }
              
    }
    public function dosoak(){
    if(session('uid')){
        $uid=session('uid');
        $sn1=date('YmdHis',time());
        $num=str_pad($uid,6,"0",STR_PAD_LEFT); 
        $sn="C";
        $sn.=$num;
        $sn.=$sn1;
        $data['product_name'] = $_REQUEST['name'];
        $data['sn'] = $sn;
        $data['uid'] = $uid;
        $data['create_time'] = time();
        $data['send_time'] =  time();
        $data['status'] = 0;
        $data['update_time'] = time();
        $data['requirement'] = $_REQUEST['kougan'];
        $data['count'] = $_REQUEST['geng'];
        $data['data'] = $_REQUEST['box'];
        $data['receive_time'] = $_REQUEST['time'];
        $data['store_id'] = $_REQUEST['store_name'];
        $data['get_goods'] = $_REQUEST['get_goods'];
        $data['special'] = $_REQUEST['special'];
        $add = M('send_list')->add($data);
        if($add){
            $this->success("发泡成功",U('Index/trepang_soak'));
        }else{
            $this->error("发泡失败");
        }
    }else{
         $this->display("Member/login");
        }
    }
    //预约上门泡发
    public function soak_1(){
        if(session('uid')){
        $uid=session('uid');
        $maps['is_default'] = 1;
        $maps['user_id'] = $uid;
        $info = M("user_address")->where($maps)->find();
        $this->assign('info',$info);
        $this->display();
    }else{
         $this->display("Member/login");
        }
    }
    public function dosoak_1(){
        if(session('uid')){
        $uid=session('uid');
        $maps['user_id'] = $uid;
        $maps['city'] = $_REQUEST['shi'];
        $maps['district'] = $_REQUEST['qu'];
        $maps['address'] = $_REQUEST['address'];
        $maps['time'] = $_REQUEST['time'];
        $add = M('address')->add($maps);
        $this->display("trepang_soak");
        }else{
         $this->display("Member/login");
        }
    }
    //参公馆厨房
    public function trepang_cuisine(){
        $page = $_REQUEST['p'];   
        $maps['cat_id'] = 3;
        $maps['is_top'] = 1;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 3;
        $top['is_recommed'] = 1;
        $r = D('article')->where($top)->order('orderby asc')->limit('10')->select();
        $where['cate_id'] = 2;
        $data = M('share')->where($where)->order("id desc")->limit("10")->select();
        $this->assign('data',$data);
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display();
    }
    public function ajax_cuisine(){
        $page = $_REQUEST['p'];   
        $start = $page*10;
        $top['cat_id'] = 3;
        $top['is_recommed'] = 1;
        $r = D('article')->where($top)->order('orderby asc')->limit('$start,10')->select();
        $this->assign('r',$r);
        $this->display();
    }
    //食用小贴士
    public function trepang_taboo(){
        $page = $_REQUEST['p'];     
        $maps['cat_id'] = 4;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 4;
        $r = D('article')->where($top)->order('orderby asc')->limit('10')->select();
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display();
    }
    public function ajax_food(){
        $page = $_REQUEST['p'];
        $start = $page*10;     
        $top['cat_id'] = 4;
        $r = D('article')->where($top)->order('orderby asc')->limit("$start,10")->select();       
        $this->assign('r',$r);
        $this->display();
    }
    public function show_b(){
        $id = $_REQUEST['id'];
        $maps['article_id'] = $id;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 4;
        $r = D('article')->where($top)->order('orderby asc')->select();
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display('trepang_taboo');        
    }
    //养生大课堂
    public function attend_class(){
        $maps['cat_id'] = 5;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 5;
        $r = D('article')->where($top)->order('orderby asc')->limit('10')->select();
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display();
    }
    public function ajax_class(){
        $page = $_REQUEST['p'];
        $start = $page*10;     
        $top['cat_id'] = 5;
        $r = D('article')->where($top)->order('orderby asc')->limit("$start,10")->select();       
        $this->assign('r',$r);
        $this->display();
    }
        public function show_c(){
        $id = $_REQUEST['id'];
        $maps['article_id'] = $id;
        $info = D('article')->where($maps)->find();
        $top['cat_id'] = 5;
        $r = D('article')->where($top)->order('orderby asc')->select();
        $this->assign('info',$info);
        $this->assign('r',$r);
        $this->display('attend_class');        
    }

    public function notices(){
        $id=$_REQUEST['article_id'];
        $maps['article_id']=$id;
        $app=D("app_article")->where($maps)->find();

        $this->assign('app',$app);
        $this->display();    

    }
        public function classify(){
        $maps['is_on_sale'] = 1;
        $maps['type'] = 1;
        $maps['weixin'] = session('weixin');
        $info = D("goods")->where($maps)->order("sort desc")->select();
        $this->assign('info',$info);
        $this->display();
    }
 
}