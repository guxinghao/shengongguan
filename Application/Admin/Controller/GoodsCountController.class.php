<?php
/**
 * Date: 2017-11-21
 */
namespace Admin\Controller;
use Admin\Logic\OrderLogic;
use Think\AjaxPage;

class GoodsCountController extends BaseController {
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
            $maps['add_time']=array(array('gt',$begin),array('lt',$end));
        }

        //权限控制
        $role_id = session('role_id');
        if($role_id==5){
            $maps['cid'] = session('admin_id');
            $this->assign('role_id',$role_id);
        }elseif($role_id==1){
            $this->assign('role_id',$role_id);
        }else{
            $maps['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }


        //门店搜索
        $store_id = I('store_id');
        if ($store_id) {
            $maps['store_id'] = $store_id;
        }

        //服务顾问搜索
        $cid = I('cid');
        if ($cid) {
            $maps['cid'] = $cid;
        }

        $orderby = I('sort');//排序标识
        $fieldname = I('fieldname');//排序标识

        $maps['pay_status']=1;
        $maps['type']=0;
        $maps['order_status']=array('not in','3,5');
        $list=D("order")->where($maps)->order('order_id desc')->select();
        $orderid=formatArray($list,'order_id');
        $where['order_id']=array('in',$orderid);

        //商品编号搜索
        $sku = I('sku');
        if ($sku) {
            $where['sku'] = $sku;
        }


        //商品名称搜索
        $goods_name = I('goods_name');
        if ($goods_name) {
            $where['goods_name'] = array('like','%'.$goods_name.'%');
        }

        // 商品编号升序
        $order_goods_all = D("order_goods")->where($where)->group('sku')->order('sku asc')->select();
        $salecount_all = 0;  //销售总数
        $all_price_all = 0;  //销售总金额
        $zongcount = 0;  //销售总数量
        if($order_goods_all){
            foreach ($order_goods_all as $key => $value) {
                $where['sku']=$value['sku'];

                $cou=D("order_goods")->where($where)->select();

                $sums = 0;
                $counts = 0;
                $salecount = 0;
                foreach ($cou as $value) {
                    $sums += $value['goods_price']*$value['goods_num'];
                    $counts ++;
                    $salecount += $value['goods_num']; 
                }
                $all_price_all += $sums;
                $salecount_all += $counts;
                $zongcount += $salecount;
                $order_goods_all[$key]['salecount']=$salecount;
                $order_goods_all[$key]['all_price'] = $sums;
            }
        }

        // 金额排序
        foreach($order_goods_all as $arr1){
            $flag1[]=$arr1["all_price"];
        }

        array_multisort($flag1, SORT_DESC, $order_goods_all);

        foreach ($order_goods_all as $key => $data) {
            $order_goods_all[$key]['xuhao'] = $key+1;
        }


        //排名排序
        foreach($order_goods_all as $arr3){
            $flag3[]=$arr3["xuhao"];
        }


        //按排名升序
        if ($fieldname=='total_amount' && $orderby=='asc') {
            // 金额降序
            array_multisort($flag3, SORT_ASC, $order_goods_all);

        //按排名降序 
        }else if ($fieldname=='total_amount' && $orderby=='desc'){
            // 金额升序
            array_multisort($flag3, SORT_DESC, $order_goods_all);
            
        }
        $len = count($order_goods_all);

        //编号排序
        foreach($order_goods_all as $arr2){
            $flag2[]=$arr2["sku"];
        }

        if ($fieldname=='sku' && $orderby=='asc') {
            // 编号排序
            array_multisort($flag2, SORT_ASC, $order_goods_all);
        }elseif($fieldname=='sku' && $orderby=='desc'){
            array_multisort($flag2, SORT_DESC, $order_goods_all);
        }

        //按销售数排序
        foreach($order_goods_all as $arr5){
            $flag5[]=$arr5["salecount"];
        }

        //按排名升序
        if ($fieldname=='salecount' && $orderby=='asc') {
            // 金额降序
            array_multisort($flag5, SORT_ASC, $order_goods_all);

        //按排名降序 
        }else if ($fieldname=='salecount' && $orderby=='desc'){
            // 金额升序
            array_multisort($flag5, SORT_DESC, $order_goods_all);
            
        }


        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $p = I('p');
        $Page  = new AjaxPage($len,$pageCount);

        $start = $Page->firstRow;
        $order_goods = array();
        for ($i=$start; $i < min($len, $start+$pageCount); $i++) { 
            array_push($order_goods,$order_goods_all[$i]);
        }
        $show = $Page->show();
        $this->assign('order_goods',$order_goods);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function ajax_goods_name(){
        $sku = I('sku');
        if ($sku) {
            $goods_id = M('spec_goods_price')->where('sku='.$sku)->getField('goods_id');
            if ($goods_id) {
                $goods_name = M('goods')->where('goods_id='.$goods_id)->getField('goods_name');
                echo json_encode($goods_name);
            }else{
                echo 0;
            } 
        }else{
            echo 0;
        }
        
        exit();
    }

}