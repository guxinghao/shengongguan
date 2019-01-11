<?php
/**
 * Date: 2017-11-21
 */
namespace Admin\Controller;
use Think\AjaxPage;

class ReturnGoodsController extends BaseController {
    /*
     *订单首页
     */
    public function index(){
    	$begin = date('Y/m/d',(time()-90*60*60*24));//30天前
    	$end = date('Y/m/d',strtotime('+1 days'));
        $info = D('store')->select();
        $role_id = session('role_id');

        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();

        //所有服务顾问列表
        $maos['role_id'] = array('in',array('4','5'));
        $guwen = M('admin')->where($maos)->field('user_name,admin_id')->select();

        $this->assign('store',$store);//门店
        $this->assign('guwen',$guwen);//顾问
        $this->assign('role_id',$role_id);//角色ID
        $this->assign('info',$info);
        $this->assign('timegap',$begin.'-'.$end);
        $this->display();
    }

    /*
     *Ajax首页
     */
    public function ajaxindex(){

        $timegap = I('timegap');
        //日期搜索
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            $maps['tp_return_goods_store.create_time']=array(array('gt',$begin),array('lt',$end));
        }

        //权限控制
        $role_id = session('role_id');
        if($role_id==5){
            $maps['tp_return_goods_store.store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }elseif($role_id==1){
            $this->assign('role_id',$role_id);
        }else{
            $maps['tp_return_goods_store.store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }


        //门店搜索
        $store_id = I('store_id');
        if ($store_id) {
            $maps['tp_return_goods_store.store_id'] = $store_id;
        }

        //商品编号搜索
        $sku = I('sku');
        if ($sku) {
            $sku_search['sku'] = array('like','%'.$sku.'%');
            $sarch_goods_id = M('spec_goods_price')->where($sku_search)->field('goods_id')->select();
            $goods_id_arr = array();
            foreach ($sarch_goods_id as $key => $v) {
                $goods_id_arr[] = $v['goods_id'];
            }
            if ($goods_id_arr) {
                $maps['tp_return_goods_detail.goods_id'] = array('in',$goods_id_arr);
            }
        }  
        
        //商品名称搜索
        $goods_name = I('goods_name');
        if ($goods_name) {
            $maps['tp_return_goods_detail.goods_name'] = array('like','%'.$goods_name.'%');
        }

        // 状态条件
        $status = I('status');
        if ($status) {
            $maps['tp_return_goods_store.status'] = $status;
        }

        // 退货原因
        $reason = I('reason');
        if ($reason) {
            if ($reason == '其他原因') {
                $maps['tp_return_goods_store.reason'] = array('like','%'.$reason.'%');
            }else{
                $maps['tp_return_goods_store.reason'] = $reason;
            }
        }

        $count = D("return_goods_store")->join('tp_return_goods_detail ON tp_return_goods_detail.replenishment_order_id=tp_return_goods_store.id','left')->where($maps)->count();
        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $Page  = new AjaxPage($count,$pageCount);

        //获取主表数据
        $result = D("return_goods_store")->join('tp_return_goods_detail ON tp_return_goods_detail.replenishment_order_id=tp_return_goods_store.id','left')->where($maps)->field('tp_return_goods_store.*,tp_return_goods_detail.goods_id,tp_return_goods_detail.count,tp_return_goods_detail.goods_name,tp_return_goods_detail.goods_name')->order('tp_return_goods_store.id desc')->limit("$Page->firstRow,$Page->listRows")->select();
        $show = $Page->show();
        $this->assign('result',$result);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

}