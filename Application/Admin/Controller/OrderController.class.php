<?php
/**
 *  Author: eric yang     
 * Date: 2017-05-15
 */
namespace Admin\Controller;
use Admin\Logic\OrderLogic;
use Think\AjaxPage;

class OrderController extends BaseController {
    public  $order_status;
    public  $pay_status;
    public  $shipping_status;
    /*
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
        C('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_status = C('ORDER_STATUS');
        $this->pay_status = C('PAY_STATUS');
        $this->shipping_status = C('SHIPPING_STATUS');
        // 订单 支付 发货状态
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
    }

    /*
     *订单首页
     */
    public function index(){
    	$begin = date('Y/m/d',(time()-90*60*60*24));//30天前
    	$end = date('Y/m/d',strtotime('+1 days'));
        $info = D('store')->select();
        $role_id = session('role_id');

        //权限管理  店长只看门店
        if (session('role_id')==4) {
            $maps['store_id'] = session('store_id');
            $where['store_id'] = session('store_id');
        }

        //权限管理  服务顾问只看服务顾问
        if (session('role_id')==5) {
            $maps['cid'] = session('admin_id');
            $where['cid'] = session('admin_id');
        }

        


        $this->assign('role_id',$role_id);//角色ID
        $this->assign('info',$info);
        $this->assign('timegap',$begin.'-'.$end);
        $this->display();
    }
   public function point(){
        //如果为店长 则显示门店
        $role_id = session('role_id');
        if ($role_id==4) {
            $condition['store_id'] = session('store_id');
            $this->assign('role_id',$role_id);
        }
        $time = date('Y/m/d',time());
        $this->assign('time',$time);
        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();
        $this->assign('store',$store);
        $this->display();
    }

    /*
     *Ajax首页
     */
    public function ajaxindex(){
        $orderLogic = new OrderLogic();       
        $timegap = I('timegap');
        if($timegap){
        	$gap = explode('-', $timegap);
        	$begin = strtotime($gap[0]);
        	$end = strtotime($gap[1])+24*3600-1;
        }

        // 搜索条件
        $condition = array();

        //只显示有效的订单
        $condition['tp_order.pay_status'] = 1;
       
        //权限管理  店长只看门店
        if (session('role_id')>=4) {
            $condition['tp_order.store_id'] = array('in',session('store_id'));
            $maps['store_id'] = array('in',session('store_id'));
            $is_del_count['store_id'] = array('in',session('store_id'));;//退货等数据统计
        }

        //权限管理  服务顾问只看服务顾问
        if (session('role_id')==5) {
            $condition['tp_order.cid'] = session('admin_id');
            $is_del_count['cid'] = session('store_id');//退货等数据统计
        }

        if(I('consignee')!=""){
            $kk=I('consignee');
            $maps1['mobile'] = array('like',"%".$kk."%");
            $info = D('users')->where($maps1)->select();

            $uids=formatArray($info,'user_id');

            if($info){
                $condition['tp_order.user_id'] = array('in',$uids);
            }else{
                $condition['tp_order.order_sn'] =array('like',"%".$kk."%");
            }
        }


        if(I('sku')!=""){
            $sku=I('sku');
            $condition['tp_order_goods.sku'] = array('like',"%".$sku."%");

        }
        //筛选是否使用积分
        if(!empty(I('use_point'))){
            $sku=I('use_point');
            //等于1 使用 2 未使用
            if ($sku==1) {
                $condition['tp_order.integral'] = array('GT',0);
                $is_del_count['integral'] = array('GT',0);//退货等数据统计
            }else{
                $condition['tp_order.integral'] = array('ELT',0);
                $is_del_count['integral'] = array('ELT',0);//退货等数据统计
            }
        }

        //筛选是否使用优惠券
        if(!empty(I('coupon'))){
            $sku=I('coupon');
            //等于1 使用 2 未使用
            if ($sku==1) {
                $condition['tp_order.coupon_price'] = array('GT',0);
                $is_del_count['coupon_price'] = array('GT',0);//退货等数据统计
            }else{
                $condition['tp_order.coupon_price'] = array('ELT',0);
                $is_del_count['coupon_price'] = array('GT',0);//退货等数据统计
            }
        }

        //筛选是否删除
        if(!empty(I('is_del'))){
            $is_del=I('is_del');
            //等于1 删除 2 未删除
            if ($is_del==1) {
                $condition['tp_order.order_status'] = 5;
            }else{
                $condition['tp_order.order_status'] = array('neq',5);
            }
        }
        

        if($begin && $end){
        	$condition['tp_order.add_time'] = array('between',"$begin,$end");
            $maps['add_time'] = array('between',"$begin,$end");
            $is_del_count['add_time'] = array('between',"$begin,$end");//退货等数据统计
        }
        I('pay_name') != '' ? $condition['tp_order.pay_name'] = I('pay_name') : false;
        I('shipping_status') != '' ? $condition['tp_order.shipping_status'] = I('shipping_status') : false;
        I('user_id') ? $condition['tp_order.user_id'] = trim(I('user_id')) : false;
        I('store_id') != '' ? $condition['tp_order.store_id'] = I('store_id') : false;
        I('store_id') != '' ? $maps['store_id'] = I('store_id') : false;
        I('store_id') != '' ? $is_del_count['store_id'] = I('store_id') : false;//退货等数据统计
        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = M('order')->join('tp_order_goods ON tp_order_goods.order_id = tp_order.order_id','left')->where($condition)->order($sort_order)->count();
        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $Page  = new AjaxPage($count,$pageCount);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }

        $show = $Page->show();
        //获取订单列表
        // $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $orderList = M('order')->join('tp_order_goods ON tp_order_goods.order_id = tp_order.order_id','left')->field('tp_order.order_id,tp_order.order_status,tp_order.order_sn,tp_order.user_id,tp_order.goods_price,tp_order.order_amount,tp_order.integral,tp_order.coupon_price,tp_order.pay_name,tp_order.store_id,tp_order.cid,tp_order.add_time,tp_order.order_note,tp_order.del_reason,tp_order_goods.goods_id,tp_order_goods.goods_name,tp_order_goods.goods_num,tp_order_goods.goods_price every_price')->where($condition)->limit("$Page->firstRow,$Page->listRows")->order($sort_order)->select();
        foreach ($orderList as $key => $value) {
            $r['store_id'] = $value['store_id'];
            $info = D('store')->where($r)->find();
            $orderList[$key]['store_name'] = $info['store_name'];
            $rs['admin_id'] = $value['cid'];
            $infos = D("admin")->where($rs)->find();
            $orderList[$key]['name'] = $infos['name'];
            $mapss['user_id']=$value['user_id'];
            $user=D("Users")->where($mapss)->find();
            $orderList[$key]['mobile']=$user['mobile'];
        }

        //总统计
        $maps['pay_status']=1;
        $maps['order_status']=4;
        //总销售金额
        $total_amount=D("order")->where($maps)->sum('goods_price');
        //使用积分总数
        $total_points=D("order")->where($maps)->sum('integral');
        //实际收款总数
        $true_amount=D("order")->where($maps)->sum('order_amount');
        //银行卡收款
        $where['pay_name']="银行卡";

        $bank_card = D("order")->where($where)->where($maps)->sum('order_amount');
        //现金收款
        $where['pay_name']="现金";
        $cash = D("order")->where($where)->where($maps)->sum('order_amount');

        //微信收款
        $where['pay_name']="微信";
        $wechat = D("order")->where($where)->where($maps)->sum('order_amount');

        //支付宝收款
        $where['pay_name']="支付宝";
        $zhifubao = D("order")->where($where)->where($maps)->sum('order_amount');
        //百联OK卡
        $where['pay_name']="百联OK卡";
        $okcard = D("order")->where($where)->where($maps)->sum('order_amount');
        //银行转账
        $where['pay_name']="银行转账";
        $bankchange = D("order")->where($where)->where($maps)->sum('order_amount');

        //礼券
        $where['pay_name']="礼券";
        $quan = D("order")->where($where)->where($maps)->sum('order_amount');

        //组合支付
        $where['pay_name']="组合支付";
        $zuhe = D("order")->where($where)->where($maps)->sum('order_amount');

        //优惠券
        $coupon_price=D("order")->where($maps)->sum('coupon_price');

        //退货笔数
        $is_del_count['order_status'] = 5;
        $is_del_count['pay_status'] = 1;
        $del_count = D("order")->where($is_del_count)->count();
        //退货金额
        $del_count_money = D("order")->where($is_del_count)->sum('total_amount');

        $this->assign('del_count_money',$del_count_money);
        $this->assign('del_count',$del_count);
        $this->assign('total_amount',$total_amount);
        $this->assign('total_points',$total_points);
        $this->assign('true_amount',$true_amount);
        $this->assign('bank_card',$bank_card);
        $this->assign('cash',$cash);
        $this->assign('wechat',$wechat);
        $this->assign('zhifubao',$zhifubao);
        $this->assign('okcard',$okcard);
        $this->assign('bankchange',$bankchange);
        $this->assign('quan',$quan);
        $this->assign('zuhe',$zuhe);
        $this->assign('coupon_price',$coupon_price);

        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    public function ajaxpoint(){ 
        // 搜索条件
        $condition = array();

        //权限管理  店长只看门店
        if (session('role_id')>=4) {
            $condition['store_id'] = array('in',session('store_id'));
        }

        //权限管理  服务顾问只看服务顾问
        if (session('role_id')==5) {
            $condition['cid'] = session('admin_id');
        }

        I('consignee')!="" ? $condition['consignee|mobile'] = array('like','%'.I('consignee').'%') : false;

        //日期搜索
        $searchTime = I('create_time');
        if($searchTime){
            $gap = explode('-', $searchTime);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            if($begin && $end){
                $condition['create_time'] = array('between',"$begin,$end");
            }
        }
        //门店搜索
        $searchStore = I('store_id');
        if ($searchStore) {
            $condition['store_id'] = $searchStore;
        }

        //配送搜索
        $searchType = I('type');
        if ($searchType) {
            $condition['type'] = $searchType;
        }

        $searchStatus = I('status');
        if ($searchStatus) {
            //如果type未选择
            if (!$searchType) {
                if ($searchStatus==1) {
                    $condition['type'] = 2;
                    $condition['status'] = 1;
                }else if($searchStatus==2){
                    $condition['type'] = 2;
                    $condition['status'] = 2;
                }else if($searchStatus==3){
                    $condition['type'] = 2;
                    $condition['status'] = 3;
                }else if($searchStatus==4){
                    $condition['type'] = 1;
                    $condition['is_tiling'] = 0;
                }else if ($searchStatus==5) {
                    $condition['type'] = 1;
                    $condition['is_tiling'] = 1;
                }
            //如果type选择
            }else{
                if ($searchStatus==1) {
                    $condition['status'] = 1;
                }else if($searchStatus==2){
                    $condition['status'] = 2;
                }else if($searchStatus==3){
                    $condition['status'] = 3;
                }else if($searchStatus==4){
                    $condition['is_tiling'] = 0;
                }else if ($searchStatus==5) {
                    $condition['is_tiling'] = 1;
                }
            }
            
        }
        
        //产品编号搜索
        $searchSku = I('sku');
        if ($searchSku) {
            //查询goods_id
            $map2['sku'] = array('like','%'.$searchSku.'%');
            $goods_id_all = M('spec_goods_price')->where($map2)->field('goods_id')->select();
            //二维数组转为一维数组
            $goodsIdAll = array();
            foreach ($goods_id_all as $key => $value) {
                $goodsIdAll[] = $value['goods_id'];
            }
            //查找主表内有该产品的数据
            $where1['goods_id'] = array('in',$goodsIdAll);
            $send_points_id = M('send_points')->where($where1)->field('id')->select();
            //查询子表内有该产品的数据
            $send_points_detail_id = M('send_points_detail')->where($where1)->field('send_points_id')->select();
            $inArrayId = array();
            if ($send_points_id) {
                foreach ($send_points_id as $key1 => $value1) {
                    $inArrayId[] = $value1['id'];
                }
            }
            if ($send_points_detail_id) {
                foreach ($send_points_detail_id as $key2 => $value2) {
                    $inArrayId[] = $value2['send_points_id'];
                }
            }
            //如果没有满足条件的数据则选择空  如果有值
            if (!$inArrayId) {
                $condition['id'] = 0;
            }else{
                $condition['id'] = array('in',$inArrayId);
            }
        }

        $count = M('send_points')->where($condition)->count();
        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $this->assign('pageCount',$pageCount);
        $Page  = new AjaxPage($count,$pageCount);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }
        $show = $Page->show();
        //获取订单列表
        $order = M('send_points')->where($condition)->limit($Page->firstRow,$Page->listRows)->order("id desc")->select();
        foreach ($order as $key => $value) {
            $maps['user_id'] = $value['user_id'];
            $info = D('users')->where($maps)->find();
            $order[$key]['nickname'] = $info['nickname'];
            $map['store_id'] = $value['store_id'];
            $infos = D('store')->where($map)->find();
            if($infos){
                $order[$key]['store_name'] = $infos['store_name'];
            }else{
                $order[$key]['store_name'] = "无";
            }
        }

        //统计
        //兑换总数
        $total_count = $count;

        //已提领总数
        if ($condition['type']==2) {
            $ytl_count = 0;
        }else{
            if ($condition['is_tiling']===0) {
                $ytl_count = 0;
            }else{
                $ytl_count_search['type'] = 1;
                $ytl_count_search['is_tiling'] = 1;
                $ytl_count = M('send_points')->where($ytl_count_search)->where($condition)->count();
            }
        }

        //未提领总数
        if ($condition['type']==2) {
            $wtl_count = 0;
        }else{
            if ($condition['is_tiling']===1) {
                $wtl_count = 0;
            }else{
                $wtl_count_search['type'] = 1;
                $wtl_count_search['is_tiling'] = 0;
                $wtl_count = M('send_points')->where($wtl_count_search)->where($condition)->count();
            }
        }


        //已发货总数
        if ($condition['type']==1) {
            $ysend_count = 0;
        }else{
            if ($condition['status'] && $condition['status']!=2) {
                $ysend_count = 0;
            }else{
                $ysend_count_search['type'] = 2;
                $ysend_count_search['status'] = 2;
                $ysend_count = M('send_points')->where($ysend_count_search)->where($condition)->count();
            }
        }
        
        //已收货总数
        if ($condition['type']==1) {
            $yget_count = 0;
        }else{
            if ($condition['status'] && $condition['status']!=3) {
                $yget_count = 0;
            }else{
                $yget_count_search['type'] = 2;
                $yget_count_search['status'] = 3;
                $yget_count = M('send_points')->where($yget_count_search)->where($condition)->count();
            }
        }

        //未发货总数
        if ($condition['type']==1) {
            $wsend_count = 0;
        }else{
            if ($condition['status'] && $condition['status']!=1) {
                $wsend_count = 0;
            }else{
                $wsend_count_search['type'] = 2;
                $wsend_count_search['status'] = 1;
                $wsend_count = M('send_points')->where($wsend_count_search)->where($condition)->count();
            }
        }


        //App兑换数量
        $app_count_search['source'] = 0;
        $app_count = M('send_points')->where($app_count_search)->where($condition)->count();

        //微信兑换数量
        $wx_count_search['source'] = 1;
        $wx_count = M('send_points')->where($wx_count_search)->where($condition)->count();


        $this->assign('total_count',$total_count);
        $this->assign('ytl_count',$ytl_count);
        $this->assign('wtl_count',$wtl_count);
        $this->assign('ysend_count',$ysend_count);
        $this->assign('wsend_count',$wsend_count);
        $this->assign('yget_count',$yget_count);
        $this->assign('app_count',$app_count);
        $this->assign('wx_count',$wx_count);
        $this->assign('page',$show);
        $this->assign('order',$order);
        $this->display();
    }

    //积分兑换商品统计表
    public function pointCount(){
        //如果为店长 则显示门店
        $role_id = session('role_id');
        if ($role_id>=4) {
            $condition['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }
        $time = date('Y/m/d',time());
        $this->assign('time',$time);
        $stratTime = '2017/01/01 - ';
        $endTime = date('Y/m/d',time());
        $point_time = $stratTime.$endTime;
        $this->assign('point_time',$point_time);
        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();
        $this->assign('store',$store);
        $this->display();
    }

    //积分兑换商品统计表
    public function ajaxpointCount(){ 
        // 搜索条件
        $condition = array();

        //权限管理  店长只看门店
        if (session('role_id')>=4) {
            $condition['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }
        //权限管理  服务顾问只看服务顾问
        if (session('role_id')==5) {
            $condition['cid'] = session('admin_id');
        }
        //时间搜索
        if(I('create_time')){
            $gap = explode('-', I('create_time'));
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            if($begin && $end){
                $condition['tp_send_points.create_time'] = array('between',"$begin,$end");
            }
            //统计表头使用
            $this->assign('thisCreateTime',I('create_time'));
        }

        //门店搜索
        if (I('store_id')) {
            $condition['tp_send_points.store_id'] = I('store_id');
            //统计表头使用
            $this->assign('thisStore',getStoreName(I('store_id')));
        }

        //商品编号搜索
        $searchSku = I('sku');
        if ($searchSku) {
            //查询goods_id
            $map2['sku'] = array('like','%'.$searchSku.'%');
            $goods_id_all = M('spec_goods_price')->where($map2)->field('goods_id')->select();
            //二维数组转为一维数组
            $goodsIdAll = array();
            if ($goods_id_all) {
                foreach ($goods_id_all as $key => $value) {
                    $goodsIdAll[] = $value['goods_id'];
                }
            }
            $condition['tp_send_points_detail.goods_id'] = array('in',$goodsIdAll);
            //统计表头使用
            $this->assign('thisSku',I('sku'));
        }


        //商品名称搜索
        $searchGoodsName = I('goods_name');
        if ($searchGoodsName) {
            $condition['tp_send_points_detail.goods_name'] = array('like','%'.$searchGoodsName.'%');
            //统计表头使用
            $this->assign('thisGoodsName',$searchGoodsName);
        }

        $countSql = D("send_points")->join('tp_send_points_detail ON tp_send_points.id = tp_send_points_detail.send_points_id','left')->where($condition)->field('tp_send_points.store_id,sum(tp_send_points_detail.goods_num) total_num,sum(tp_send_points_detail.points) total_points,tp_send_points_detail.*')->group('tp_send_points_detail.goods_id')->order('tp_send_points_detail.goods_id asc')->buildSql();
        $model = M();
        $count = $model->table($countSql.' aa')->count();

        //搜索条件下的兑换数量  使用积分
        $total_num = $model->table($countSql.' aa')->sum('total_num');
        $total_points = $model->table($countSql.' aa')->sum('total_points');
        $this->assign('total_num', $total_num ? $total_num : 0);
        $this->assign('total_points', $total_points ? $total_points : 0);
        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $this->assign('pageCount',$pageCount);
        
        $Page  = new AjaxPage($count,$pageCount);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key] = urlencode($val);
        }
        $show = $Page->show();
        $send_points = D("send_points")->join('tp_send_points_detail ON tp_send_points.id = tp_send_points_detail.send_points_id','left')->where($condition)->field('tp_send_points.store_id,sum(tp_send_points_detail.goods_num) total_num,sum(tp_send_points_detail.points) total_points,tp_send_points_detail.*')->group('tp_send_points_detail.goods_id,tp_send_points.store_id')->order('tp_send_points_detail.goods_id asc')->limit($Page->firstRow,$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('send_points',$send_points);


        //兑换时间 默认值
        if (!I('create_time')) {
            $stratTime = '2017/01/01 - ';
            $endTime = date('Y/m/d',time());
            $point_time = $stratTime.$endTime;
            $this->assign('point_time',$point_time);
        }else{
            $this->assign('point_time',I('create_time'));
        }
        $this->assign('send_points',$send_points);
        $this->display();
    }


    public function sendPoint_detail(){
        //主表ID
        $maps['id'] = $_REQUEST['order_id'];
        $res = D('send_points')->where($maps)->find();

        $arrlist = array();
        $smArr = array();
        $newArr = array();
        //如果为app端兑换 则查询详情表 如果为微信兑换则用当前数据
        //0 app端积分兑换 1 微信端积分兑换
        if (!$res['source']) {
            $send_points_id['send_points_id'] = $res['id'];
            $sendList = M('send_points_detail')->where($send_points_id)->select();
            foreach ($sendList as $key1 => $value) {
                $goods_sn = D('spec_goods_price')->where('goods_id='.$value['goods_id'])->getField('sku');
                $smArr['pay_points'] = $value['points'];
                $smArr['goods_name'] = $value['goods_name'];
                $smArr['sku'] = $goods_sn;//商品编号
                $smArr['count'] = $value['goods_num'];//数量
                array_push($newArr, $smArr);
                $smArr = array();
            }
        }else{
            $goods_sn = D('spec_goods_price')->where('goods_id='.$res['goods_id'])->getField('sku');
            $smArr['pay_points'] = $res['pay_points'];
            $smArr['goods_name'] = $res['goods_name'];
            $smArr['sku'] = $goods_sn;//商品编号
            $smArr['count'] = $res['goods_num'];//数量
            array_push($newArr, $smArr);
            $smArr = array();
        }
        $maps['user_id'] = $res['user_id'];
        $info1 = D('users')->where($maps)->find();
        $nickname = $info1['nickname'];
        $this->assign('order_sn',$res['order_sn']);//订单编号
        $this->assign('source',$res['source']);//兑换方式
        $this->assign('nickname',$nickname);//用户id
        $this->assign('address',$res['address']);//兑换方式
        $this->assign('newArr',$newArr);//订单编号
        $this->assign('res',$res);//订单编号
        $this->display();
    }



    //删除积分兑换
    public function del_sendPoint(){
        $maps['id'] = $_REQUEST['id'];
        $info = D("send_points")->where($maps)->delete();
        if($info) {
             $news = array('code' =>1 ,'msg'=>'删除成功','data'=>null);
            echo json_encode($news,true);exit;
        }else{
           $news = array('code' =>0 ,'msg'=>'删除失败','data'=>null);
            echo json_encode($news,true);exit;
        }      
    }



    public function songhuo(){
        $maps['id'] = $_REQUEST['id'];

        $mapsss['id'] = $maps['id'];
        $send_points = D("send_points")->where($mapsss)->find();
        //判断是否为已处理订单
        if ($send_points['status']==2) {
            $news = array('code' =>0 ,'msg'=>'已发货订单不可重复发货!','data'=>null);
            echo json_encode($news,true);exit;
        }
        //判断积分是否充足
        $pay_points = M('users')->where('user_id='.$user_id)->getField('pay_points');
        //获取消费积分
        $points = $send_points['pay_points'];//消耗积分
        $store_id = $send_points['store_id'];
        $code = $send_points['order_sn'];
        $user_id = $send_points['user_id'];
        if ($pay_points<$points) {
            $news = array('code' =>0 ,'msg'=>'积分不足!','data'=>null);
            echo json_encode($news,true);exit;
        }
        //判断库存
        $source = $send_points['source'];//1微信兑换  0 app兑换
        if ($source==1) {
            //修改库存
            $goods_id=$send_points['goods_id'];
            $goods_num=$send_points['goods_num'];
            $resource_id=$store_id;
            $re11 = checkGoodsNum($goods_id,$goods_num,$resource_id,4);
            if (!$re11) {
                $news = array('code' =>0 ,'msg'=>'库存不足','data'=>null);
                echo json_encode($news,true);exit;
            }
        }

        //抵扣分数减掉
        if($res){
            accountLog($user_id,0,$points,'积分抵扣',0,3,1); 
        }
        $data['status'] = 2;
        $info = D("send_points")->where($maps)->save($data);


        //积分兑换类型
        $source = $send_points['source'];//1微信兑换  0 app兑换
        if ($source==1) {
            //修改库存
            $goods_id=$send_points['goods_id'];
            $goods_num=$send_points['goods_num'];
            $goods_name = $send_points['goods_name'];
            $resource_id = $store_id;
            $jjk = jskc_new($goods_id,$goods_num,$resource_id,4,1);
            //新增门店出库记录
            if ($jjk) {
                //新增门店流水记录
                $infofo = addWaterRecord($goods_id, $goods_num, $resource_id, 2);//出货类型 1 进货 2 销售 3 返货
                
                //新增门店出库记录
                $result_outRecord = store_stock_out($resource_id,$code);
                if ($result_outRecord) {
                    //新增门店出库记录详情表
                   store_stock_out_detail($result_outRecord,$goods_id,$goods_name,$goods_num);
                }
            }
        }else{
            $news = array('code' =>0 ,'msg'=>'app端积分兑换无需发货','data'=>null);
            echo json_encode($news,true);exit;
        }
        

        if($info) {
            $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
            echo json_encode($news,true);exit;
        }else{
           $news = array('code' =>0 ,'msg'=>'没有更多的数据','data'=>null);
            echo json_encode($news,true);exit;
        }      
  }
      public function wangcheng(){
        $maps['id'] = $_REQUEST['id'];
        $data['status'] = 3;
        $info = D("send_points")->where($maps)->save($data);
        if($info) {
             $news = array('code' =>1 ,'msg'=>'没有更多的数据','data'=>null);
            echo json_encode($news,true);exit;
        }else{
           $news = array('code' =>0 ,'msg'=>'没有更多的数据','data'=>null);
            echo json_encode($news,true);exit;
        }      
  }
    public function ex_points(){
        // $maps['store_count'] =  array('gt' , 0);
        $maps['is_on_sale'] = 1;
        $maps['type'] = 0;
        $info = D('goods')->where($maps)->select();
        // var_dump($info);die;
        $mapps['is_forbid'] = 0;
        $r = D('store')->where($mapps)->select();
        $this->assign('r',$r);
        $this->assign('info',$info);
        $this->display();
    }
    public function ex_goods(){
        if(empty($_POST['mobile'])||empty($_POST['goods_id'])){
            $this->error("不能为空");
        }
        $maps['mobile'] = $_POST['mobile'];
        $info = D('users')->where($maps)->find();
        if($info==fales){
            $this->error("该用户不存在");
        }
        $total_points = getKeScore($info['user_id']);
        $map['goods_id'] = $_POST['goods_id'];
        $infos = D('goods')->where($map)->find();
        $pay_points = $infos['goods_points'];
        if($total_points<$pay_points){
            $this->error("积分不够");
         }else{
            $data['user_id'] = $info['user_id'];
            $data['goods_id'] = $infos['goods_id'];
            $data['pay_points'] = $infos['goods_points'];
            $data['goods_num'] = 1;
            $data['goods_name'] = $infos['goods_name'];
            $data['mobile'] = $info['mobile'];
            $data['create_time'] = time();
            $data['type'] = 1;
            $data['store_id'] = $_POST['store'];
            $data['consignee'] = $info['nickname'];
            $data['is_tiling'] = 0;
            $data['source'] = 1;
            $order_sn=date('YmdHis').rand(1000,9999);
            $data['order_sn'] = $order_sn;
            $data['pay_status'] = 0;
            // accountLog($data['user_id'],0,$pay_points,'积分兑换减少',0,3,1); 
            // jskc($goods['goods_id']);
            $add = D("send_points")->add($data);
            if($add){
             $this->success('兑换成功', U('admin/Order/point'));
            }else{
                $this->error("兑换失败");
            }
        }
         }

    
    /*
     * ajax 发货订单列表
    */
    public function ajaxdelivery(){
    	$orderLogic = new OrderLogic();
    	$condition = array();
    	I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
    	I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
    	$shipping_status = I('shipping_status');
    	$condition['shipping_status'] = empty($shipping_status) ? array('neq',1) : $shipping_status;
        $condition['order_status'] = array('in','1,2,4');
    	$count = M('order')->where($condition)->count();

        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page  = new AjaxPage($count,$pageCount);
    	//搜索条件下 分页赋值
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key]   =   urlencode($val);
    	}
    	$show = $Page->show();
    	$orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
    	$this->assign('orderList',$orderList);
    	$this->assign('page',$show);// 赋值分页输出
    	$this->display();
    }
    
    /**
     * 订单详情
     * @param int $id 订单id
     */
    public function detail($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $button = $orderLogic->getOrderButton($order);
        // 获取操作记录
        $action_log = M('order_action')->where(array('order_id'=>$order_id))->order('log_time desc')->select();
        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        $split = count($orderGoods) >1 ? 1 : 0;
        foreach ($orderGoods as $val){
        	if($val['goods_num']>1){
        		$split = 1;
        	}
        }
        $this->assign('split',$split);
        $this->assign('button',$button);
        $this->display();
    }

    /**
     * 订单编辑
     * @param int $id 订单id
     */
    public function edit_order(){
    	$order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        } 
    
        $orderGoods = $orderLogic->getOrderGoods($order_id);
                
       	if(IS_POST)
        {
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机           
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注
            $order['admin_note'] = I('admin_note'); //                  
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');            
            $order['pay_code'] = I('payment');// 支付方式            
            $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');                            
            $goods_id_arr = I("goods_id");
            $new_goods = $old_goods_arr = array();
            //################################订单添加商品
            if($goods_id_arr){
            	$new_goods = $orderLogic->get_spec_goods($goods_id_arr);
            	foreach($new_goods as $key => $val)
            	{
            		$val['order_id'] = $order_id;
            		$rec_id = M('order_goods')->add($val);//订单添加商品
            		if(!$rec_id)
            			$this->error('添加失败');
            	}
            }
            
            //################################订单修改删除商品
            $old_goods = I('old_goods');
            foreach ($orderGoods as $val){
            	if(empty($old_goods[$val['rec_id']])){
            		M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
            	}else{
            		//修改商品数量
            		if($old_goods[$val['rec_id']] != $val['goods_num']){
            			$val['goods_num'] = $old_goods[$val['rec_id']];
            			M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
            		}
            		$old_goods_arr[] = $val;
            	}
            }
            
            $goodsArr = array_merge($old_goods_arr,$new_goods);
            $result = calculate_price($order['user_id'],$goodsArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
            if($result['status'] < 0)
            {
            	$this->error($result['msg']);
            }
       
            //################################修改订单费用
            $order['goods_price']    = $result['result']['goods_price']; // 商品总价
            $order['shipping_price'] = $result['result']['shipping_price'];//物流费
            $order['order_amount']   = $result['result']['order_amount']; // 应付金额
            $order['total_amount']   = $result['result']['total_amount']; // 订单总价           
            $o = M('order')->where('order_id='.$order_id)->save($order);
            
            $l = $orderLogic->orderActionLog($order_id,'edit','修改订单');//操作日志
            if($o && $l){
            	$this->success('修改成功',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
            	$this->success('修改失败',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        // 获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //获取订单城市
        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //获取订单地区
        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //获取支付方式
        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        //获取配送方式
        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        
        $this->assign('order',$order);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);
        $this->display();
    }
    
    /*
     * 拆分订单
     */
    public function split_order(){
    	$order_id = I('order_id');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	if($order['shipping_status'] != 0){
    		$this->error('已发货订单不允许编辑');
    		exit;
    	}
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
    	if(IS_POST){
    		$data = I('post.');
    		//################################先处理原单剩余商品和原订单信息
    		$old_goods = I('old_goods');
    		foreach ($orderGoods as $val){
    			if(empty($old_goods[$val['rec_id']])){
    				M('order_goods')->where("rec_id=".$val['rec_id'])->delete();//删除商品
    			}else{
    				//修改商品数量
    				if($old_goods[$val['rec_id']] != $val['goods_num']){
    					$val['goods_num'] = $old_goods[$val['rec_id']];
    					M('order_goods')->where("rec_id=".$val['rec_id'])->save(array('goods_num'=>$val['goods_num']));
    				}
    				$oldArr[] = $val;//剩余商品
    			}
    			$all_goods[$val['rec_id']] = $val;//所有商品信息
    		}
    		$result = calculate_price($order['user_id'],$oldArr,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
    		if($result['status'] < 0)
    		{
    			$this->error($result['msg']);
    		}
    		//修改订单费用
    		$res['goods_price']    = $result['result']['goods_price']; // 商品总价
    		$res['order_amount']   = $result['result']['order_amount']; // 应付金额
    		$res['total_amount']   = $result['result']['total_amount']; // 订单总价
    		M('order')->where("order_id=".$order_id)->save($res);
			//################################原单处理结束
			
    		//################################新单处理
    		for($i=1;$i<20;$i++){
    			if(!empty($_POST[$i.'_old_goods'])){
    				$split_goods[] = $_POST[$i.'_old_goods'];
    			}
    		}

    		foreach ($split_goods as $key=>$vrr){
    			foreach ($vrr as $k=>$v){
    				$all_goods[$k]['goods_num'] = $v;
    				$brr[$key][] = $all_goods[$k];
    			}
    		}

    		foreach($brr as $goods){
    			$result = calculate_price($order['user_id'],$goods,$order['shipping_code'],0,$order['province'],$order['city'],$order['district'],0,0,0,0);
    			if($result['status'] < 0)
    			{
    				$this->error($result['msg']);
    			}
    			$new_order = $order;
    			$new_order['order_sn'] = date('YmdHis').mt_rand(1000,9999);
    			$new_order['parent_sn'] = $order['order_sn'];
    			//修改订单费用
    			$new_order['goods_price']    = $result['result']['goods_price']; // 商品总价
    			$new_order['order_amount']   = $result['result']['order_amount']; // 应付金额
    			$new_order['total_amount']   = $result['result']['total_amount']; // 订单总价
    			$new_order['add_time'] = time();
    			unset($new_order['order_id']);
    			$new_order_id = M('order')->add($new_order);//插入订单表
    			foreach ($goods as $vv){
    				$vv['order_id'] = $new_order_id;
    				unset($vv['rec_id']);
    				$nid = M('order_goods')->add($vv);//插入订单商品表
    			}
    		}
    		//################################新单处理结束
    		$this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            exit;
    	}
    	
    	foreach ($orderGoods as $val){
    		$brr[$val['rec_id']] = array('goods_num'=>$val['goods_num'],'goods_name'=>getSubstr($val['goods_name'], 0, 35).$val['spec_key_name']);
    	}
    	$this->assign('order',$order);
    	$this->assign('goods_num_arr',json_encode($brr));
    	$this->assign('orderGoods',$orderGoods);
    	$this->display();
    }
    
    /*
     * 价钱修改
     */
    public function editprice($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $this->editable($order);
        if(IS_POST){
        	$admin_id = session('admin_id');
            if(empty($admin_id)){
                $this->error('非法操作');
                exit;
            }
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
			$update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $row = M('order')->where(array('order_id'=>$order_id))->save($update);
            if(!$row){
                $this->success('没有更新数据',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        $this->assign('order',$order);
        $this->display();
    }

    /**
     * 订单删除
     * @param int $id 订单id
     */
    public function delete_order($order_id){
    	$orderLogic = new OrderLogic();
    	$del = $orderLogic->delOrder($order_id);
        if($del){
            $this->success('删除订单成功');
        }else{
        	$this->error('订单删除失败');
        }
    }
    
    /**
     * 订单取消付款
     */
    public function pay_cancel($order_id){
    	if(I('remark')){
    		$data = I('post.');
    		$note = array('退款到用户余额','已通过其他方式退款','不处理，误操作项');
    		if($data['refundType'] == 0 && $data['amount']>0){
    			accountLog($data['user_id'], $data['amount'], 0,  '退款到用户余额');
    		}
    		$orderLogic = new OrderLogic();
                $orderLogic->orderProcessHandle($data['order_id'],'pay_cancel');
    		$d = $orderLogic->orderActionLog($data['order_id'],'pay_cancel',$data['remark'].':'.$note[$data['refundType']]);
    		if($d){
    			exit("<script>window.parent.pay_callback(1);</script>");
    		}else{
    			exit("<script>window.parent.pay_callback(0);</script>");
    		}
    	}else{
    		$order = M('order')->where("order_id=$order_id")->find();
    		$this->assign('order',$order);
    		$this->display();
    	}
    }

    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print(){
    	$order_id = I('order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $shop = tpCache('shop_info');
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('orderGoods',$orderGoods);
        $template = I('template','print');
        $this->display($template);
    }

    /**
     * 快递单打印
     */
    public function shipping_print(){
        $order_id = I('get.order_id');
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        //查询是否存在订单及物流
        $shipping = M('plugin')->where(array('code'=>$order['shipping_code'],'type'=>'shipping'))->find();        
        if(!$shipping){
        	$this->error('物流插件不存在');
        }
		if(empty($shipping['config_value'])){
			$this->error('请设置'.$shipping['name'].'打印模板');
		}
        $shop = tpCache('shop_info');//获取网站信息
        $shop['province'] = empty($shop['province']) ? '' : getRegionName($shop['province']);
        $shop['city'] = empty($shop['city']) ? '' : getRegionName($shop['city']);
        $shop['district'] = empty($shop['district']) ? '' : getRegionName($shop['district']);

        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        if(empty($shipping['config'])){
        	$config = array('width'=>840,'height'=>480,'offset_x'=>0,'offset_y'=>0);
        	$this->assign('config',$config);
        }else{
        	$this->assign('config',unserialize($shipping['config']));
        }
        $template_var = array("发货点-名称", "发货点-联系人", "发货点-电话", "发货点-省份", "发货点-城市",
        		 "发货点-区县", "发货点-手机", "发货点-详细地址", "收件人-姓名", "收件人-手机", "收件人-电话", 
        		"收件人-省份", "收件人-城市", "收件人-区县", "收件人-邮编", "收件人-详细地址", "时间-年", "时间-月", 
        		"时间-日","时间-当前日期","订单-订单号", "订单-备注","订单-配送费用");
        $content_var = array($shop['store_name'],$shop['contact'],$shop['phone'],$shop['province'],$shop['city'],
        	$shop['district'],$shop['phone'],$shop['address'],$order['consignee'],$order['mobile'],$order['phone'],
        	$order['province'],$order['city'],$order['district'],$order['zipcode'],$order['address'],date('Y'),date('M'),
        	date('d'),date('Y-m-d'),$order['order_sn'],$order['admin_note'],$order['shipping_price'],
        );
        $shipping['config_value'] = str_replace($template_var,$content_var, $shipping['config_value']);
        $this->assign('shipping',$shipping);
        $this->display("Plugin/print_express");
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $orderLogic = new OrderLogic();
		$data = I('post.');
		$res = $orderLogic->deliveryHandle($data);
		if($res){
			$this->success('操作成功',U('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
		}else{
			$this->success('操作失败',U('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
		}
    }

    
    public function delivery_info(){
    	$order_id = I('order_id');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
		$delivery_record = M('delivery_doc')->join('LEFT JOIN __ADMIN__ ON __ADMIN__.admin_id = __DELIVERY_DOC__.admin_id')->where('order_id='.$order_id)->select();
		if($delivery_record){
			$order['invoice_no'] = $delivery_record[count($delivery_record)-1]['invoice_no'];
		}
		$this->assign('order',$order);
		$this->assign('orderGoods',$orderGoods);
		$this->assign('delivery_record',$delivery_record);//发货记录
    	$this->display();
    }
    
    /**
     * 发货单列表
     */
    public function delivery_list(){
        $this->display();
    }
	
    /*
     * ajax 退货订单列表
     */
    public function ajax_return_list(){
        // 搜索条件        
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status');
        
        $where = " 1 = 1 ";
        $order_sn && $where.= " and order_sn like '%$order_sn%' ";
        empty($order_sn) && $where.= " and status = '$status' ";
        $count = M('return_goods')->where($where)->count();

        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page  = new AjaxPage($count,$pageCount);
        $show = $Page->show();
        $list = M('return_goods')->where($where)->order("$order_by $sort_order")->limit("{$Page->firstRow},{$Page->listRows}")->select();        
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr))
            $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
        $this->assign('goods_list',$goods_list);
        $this->assign('list',$list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    /**
     * 删除某个退换货申请
     */
    public function return_del(){
        $id = I('get.id');
        M('return_goods')->where("id = $id")->delete(); 
        $this->success('成功删除!');
    }
    /**
     * 退换货操作
     */
    public function return_info()
    {
        $id = I('id');
        $return_goods = M('return_goods')->where("id= $id")->find();
        if($return_goods['imgs'])            
             $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $user = M('users')->where("user_id = {$return_goods[user_id]}")->find();
        $goods = M('goods')->where("goods_id = {$return_goods[goods_id]}")->find();
        $type_msg = array('退换','换货');
        $status_msg = array('未处理','处理中','已完成');
        if(IS_POST)
        {
            $data['type'] = I('type');
            $data['status'] = I('status');
            $data['remark'] = I('remark');                                    
            $note ="退换货:{$type_msg[$data['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
            $result = M('return_goods')->where("id= $id")->save($data);    
            if($result)
            {        
            	$type = empty($data['type']) ? 2 : 3;
            	$where = " order_id = ".$return_goods['order_id']." and goods_id=".$return_goods['goods_id'];
            	M('order_goods')->where($where)->save(array('is_send'=>$type));//更改商品状态        
                $orderLogic = new OrderLogic();
                $log = $orderLogic->orderActionLog($return_goods[order_id],'refund',$note);
                $this->success('修改成功!');            
                exit;
            }  
        }        
        
        $this->assign('id',$id); // 用户
        $this->assign('user',$user); // 用户
        $this->assign('goods',$goods);// 商品
        $this->assign('return_goods',$return_goods);// 退换货               
        $this->display();
    }
    
    /**
     * 管理员生成申请退货单
     */
    public function add_return_goods()
   {                
            $order_id = I('order_id'); 
            $goods_id = I('goods_id');
                
            $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id")->find();            
            if(!empty($return_goods))
            {
                $this->error('已经提交过退货申请!',U('Admin/Order/return_list'));
                exit;
            }
            $order = M('order')->where("order_id = $order_id")->find();
            
            $data['order_id'] = $order_id; 
            $data['order_sn'] = $order['order_sn']; 
            $data['goods_id'] = $goods_id; 
            $data['addtime'] = time(); 
            $data['user_id'] = $order[user_id];            
            $data['remark'] = '管理员申请退换货'; // 问题描述            
            M('return_goods')->add($data);            
            $this->success('申请成功,现在去处理退货',U('Admin/Order/return_list'));
            exit;
    }

    /**
     * 订单操作
     * @param $id
     */
    public function order_action(){ 	
        $orderLogic = new OrderLogic();
        $action = I('get.type');
        $order_id = I('get.order_id');
        $del_reason = I('get.note');//删除原因
        if($action && $order_id){
        	 $a = $orderLogic->orderProcessHandle($order_id,$action,$del_reason);       	
        	 $res = $orderLogic->orderActionLog($order_id,$action,I('note'));
        	 if($res && $a){
                //向订单操作 表中插入删除原因
        	 	exit(json_encode(array('status' => 1,'msg' => '操作成功')));
        	 }else{
        	 	exit(json_encode(array('status' => 0,'msg' => '操作失败')));
        	 }
        }else{
        	$this->error('参数错误',U('Admin/Order/detail',array('order_id'=>$order_id)));
        }
    }
    
    public function order_log(){
    	$timegap = I('timegap');
    	if($timegap){
    		$gap = explode('-', urldecode($timegap));
    		$begin = strtotime($gap[0]);
    		$end = strtotime($gap[1]);
    	}
    	$condition = array();
    	$log =  M('order_action');
    	if($begin && $end){
    		$condition['log_time'] = array('between',"$begin,$end");
            $this->assign('timegap',urlencode(I('timegap')));
    	}

        if (I('order_sn')) {
            $condition['order_id'] = I('order_sn');
            $this->assign('order_sn',urlencode(I('order_sn')));
        }

    	$admin_id = I('admin_id');

        if ($admin_id) {
            if($admin_id >1 ){
                $condition['action_user'] = $admin_id;
            }else{
                $condition['action_user'] = I('admin_id');
                $this->assign('admin_id',I('admin_id'));
            }
        }
		
    	$count = $log->where($condition)->count();

        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page = new \Think\Page($count,$pageCount);
            
        //展示条数        
        $this->assign('pageCount',$pageCount);

    	// foreach($condition as $key=>$val) {
    	// 	$Page->parameter[$key] = urlencode($val);
    	// }
        
        if (I('order_sn')) {
            $Page->parameter['order_sn'] = urlencode(I('order_sn'));
        }
        if (I('timegap')) {
            $Page->parameter['timegap'] = urlencode(urldecode(I('timegap')));
        }
        if (I('admin_id')) {
            $Page->parameter['admin_id'] = urlencode(I('admin_id'));
        }

    	$show = $Page->show();
    	$list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('search',I('search'));
    	$this->assign('list',$list);
    	$this->assign('page',$show);   	
    	$admin = M('admin')->getField('admin_id,user_name');
    	$this->assign('admin',$admin);    	
    	$this->display();
    }

    /**
     * 检测订单是否可以编辑
     * @param $order
     */
    private function editable($order){
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }

    public function export_order()
    {
    	//搜索条件
		$where = 'where 1=1 ';
        
		$consignee = I('consignee');
        if(I('consignee')!=""){
            $kk=I('consignee');
            $maps['mobile'] = array('like',"%".$kk."%");
            $info = D('users')->where($maps)->select();

            $uids=formatArray($info,'user_id');

            if($info){
                $where .= " AND user_id IN (".$uids.") ";
            }else{
                $where .= " AND order_sn like '%$consignee%' ";
            }
        }

        $where .= " AND pay_status=1";
        //门店
        if(I('store_id')){
            $where .= " AND store_id = ".I('store_id');
        }

        //权限管理  店长只看门店
        if (session('role_id')==4) {
            $where .= " AND store_id = ".I('store_id');
        }

        //权限管理  服务顾问只看服务顾问
        if (session('role_id')==5) {
            $where .= " AND cid = ".session('admin_id');
        }

        //支付方式
        if(I('pay_name')){
            $where .= " AND pay_name = '".I('pay_name')."'";
        }

        if(I('sku')!=""){
            $sku=I('sku');
            $maps['sku'] = array('like',"%".$sku."%");
            $info = D('order_goods')->where($maps)->select();
            $order_id=formatArray($info,'order_id');
            if($info){
                $where .= " AND order_id IN (".$order_id.")";
            }
        }

		$timegap = I('timegap');
		if($timegap){
			$gap = explode('-', $timegap);
            $begin = strtotime($gap[0].' 00:00:00');
            $end = strtotime($gap[1].' 23:59:59');
			$where .= " AND add_time>$begin and add_time<$end ";
		}

        //筛选是否使用积分
        if(!empty(I('use_point'))){
            $sku=I('use_point');
            //等于1 使用 2 未使用
            if ($sku==1) {
                // $condition['tp_order.integral'] = array('GT',0);
                $where .= " AND integral>0";
            }else{
                $where .= " AND integral<=0";
                // $condition['tp_order.integral'] = array('ELT',0);
            }
        }

        //筛选是否使用优惠券
        if(!empty(I('coupon'))){
            $sku=I('coupon');
            //等于1 使用 2 未使用
            if ($sku==1) {
                $where .= " AND coupon_price>0";
            }else{
                $where .= " AND coupon_price<=0";
            }
        }

        //筛选是否删除
        if(!empty(I('is_del'))){
            $is_del=I('is_del');
            //等于1 删除 2 未删除
            if ($is_del==1) {
                $where .= " AND order_status =5";
            }else{
                $where .= " AND order_status <> 5";
            }
        }

        $sql = "select *,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time from __PREFIX__order $where order by order_id desc";
        $orderList = D()->query($sql);
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
    	// $strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
    	// $strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">使用积分</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">门店</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">操作人</td>';
    	$strTable .= '</tr>';
	    if(is_array($orderList)){
	    	$region	= M('region')->getField('id,name');
    
	    	foreach($orderList as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';	    		
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['consignee'].'</td>';
                // $strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['district']]},{$region[$val['twon']]}{$val['address']}".' </td>'; 
                //收货人手机号隐藏                       
	    		// $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
                // 使用积分
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['integral'].' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';
	    		$orderGoods = D('order_goods')->where('order_id='.$val['order_id'])->select();
             
	    		$strGoods="";
	    		foreach($orderGoods as $goods){
	    			$strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
	    			if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
	    			$strGoods .= "<br />";
	    		}
	    		unset($orderGoods);
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
                // 门店
                if ($val['store_id']) {
                    $store_name = M('store')->where('store_id='.$val['store_id'])->getField('store_name');
                }
                if ($store_name) {
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$store_name.' </td>';
                }else{
                    $strTable .= '<td style="text-align:left;font-size:12px;"> </td>';
                }

                $flagStatus = array('待确认','已确认','已收货','已取消','已完成','已作废');
                // 订单状态
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$flagStatus[$val['order_status']].' </td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_note'].' </td>';
                // 操作人
                if ($val['cid']) {
                    $infos = D("admin")->where('admin_id='.$val['cid'])->find();

                    $infosname = $infos['name'];
                }else{
                    $infosname = '';
                }

                $strTable .= '<td style="text-align:left;font-size:12px;">'.$infosname.' </td>';
	    		$strTable .= '</tr>';
	    	}
	    }
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'order');
    	exit();
    }
    
    /**
     * 退货单列表
     */
    public function return_list(){
        $this->display();
    }
    
    /**
     * 添加一笔订单
     */
    public function add_order()
    {
        $order = array();
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取订单城市
        $city =  M('region')->where(array('parent_id'=>$order['province'],'level'=>2))->select();
        //  获取订单地区
        $area =  M('region')->where(array('parent_id'=>$order['city'],'level'=>3))->select();
        //  获取配送方式
        $shipping_list = M('plugin')->where(array('status'=>1,'type'=>'shipping'))->select();
        //  获取支付方式
        $payment_list = M('plugin')->where(array('status'=>1,'type'=>'payment'))->select();
        if(IS_POST)
        {
            $order['user_id'] = I('user_id');// 用户id 可以为空
            $order['consignee'] = I('consignee');// 收货人
            $order['province'] = I('province'); // 省份
            $order['city'] = I('city'); // 城市
            $order['district'] = I('district'); // 县
            $order['address'] = I('address'); // 收货地址
            $order['mobile'] = I('mobile'); // 手机           
            $order['invoice_title'] = I('invoice_title');// 发票
            $order['admin_note'] = I('admin_note'); // 管理员备注            
            $order['order_sn'] = date('YmdHis').mt_rand(1000,9999); // 订单编号;
            $order['admin_note'] = I('admin_note'); // 
            $order['add_time'] = time(); //                    
            $order['shipping_code'] = I('shipping');// 物流方式
            $order['shipping_name'] = M('plugin')->where(array('status'=>1,'type'=>'shipping','code'=>I('shipping')))->getField('name');            
            $order['pay_code'] = I('payment');// 支付方式            
            $order['pay_name'] = M('plugin')->where(array('status'=>1,'type'=>'payment','code'=>I('payment')))->getField('name');            
                            
            $goods_id_arr = I("goods_id");
            $orderLogic = new OrderLogic();
            $order_goods = $orderLogic->get_spec_goods($goods_id_arr);          
            $result = calculate_price($order['user_id'],$order_goods,$order['shipping_code'],0,$order[province],$order[city],$order[district],0,0,0,0);      
            if($result['status'] < 0)	
            {
                 $this->error($result['msg']);      
            } 
           
           $order['goods_price']    = $result['result']['goods_price']; // 商品总价
           $order['shipping_price'] = $result['result']['shipping_price']; //物流费
           $order['order_amount']   = $result['result']['order_amount']; // 应付金额
           $order['total_amount']   = $result['result']['total_amount']; // 订单总价
           
            // 添加订单
            $order_id = M('order')->add($order);
            if($order_id)
            {
                foreach($order_goods as $key => $val)
                {
                    $val['order_id'] = $order_id;
                    $rec_id = M('order_goods')->add($val);
                    if(!$rec_id)                 
                        $this->error('添加失败');                                  
                }
                $this->success('添加商品成功',U("Admin/Order/detail",array('order_id'=>$order_id)));
                exit();
            }
            else{
                $this->error('添加失败');
            }                
        }     
        $this->assign('shipping_list',$shipping_list);
        $this->assign('payment_list',$payment_list);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('area',$area);        
        $this->display();
    }
    
    /**
     * 选择搜索商品
     */
    public function search_goods()
    {
    	$brandList =  M("brand")->select();
    	$categoryList =  M("goods_category")->select();
    	$this->assign('categoryList',$categoryList);
    	$this->assign('brandList',$brandList);   	
    	$where = ' is_on_sale = 1 ';//搜索条件
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));    		
            $grandson_ids = getCatGrandson(I('cat_id')); 
            $where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
                
    	}
        if(I('brand_id')){
            $this->assign('brand_id',I('brand_id'));
            $where = "$where and brand_id = ".I('brand_id');
        }
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}  	
    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit(10)->select();
                
        foreach($goodsList as $key => $val)
        {
            $spec_goods = M('spec_goods_price')->where("goods_id = {$val['goods_id']}")->select();
            $goodsList[$key]['spec_goods'] = $spec_goods;            
        }
    	$this->assign('goodsList',$goodsList);
    	$this->display();        
    }
    
    public function ajaxOrderNotice(){
        $order_amount = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();
        echo $order_amount;
    }

}
