<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-27
 */
namespace Admin\Controller;

use Think\AjaxPage;
use Think\Model;
use Think\Page;

class ServicesController extends BaseController {


    public function index(){
        $this->display();
    }

    //代发管理
    public function send_list(){
        $condition = array();
        $model = M('send_list');

        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        
        //如果为店长 则显示门店  如果为服务顾问 展示服务顾问的数据
        $role_id = session('role_id');
        if($role_id==5){
            $condition['cid'] = session('admin_id');
            $this->assign('role_id',$role_id);
        }elseif($role_id==1){
            $this->assign('role_id',$role_id);
        }else{
            $condition['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }

        //订单编号
        if(!empty(I('sn'))){
            $condition['sn']=array('like','%'.urldecode(I('sn')).'%');
            $this->assign('sn',urldecode(I('sn')));
        }

        //门店
        if(!empty(I('store_id'))){
            $condition['store_id'] = I('store_id');
            $where1['store_id'] = I('store_id');
            $where2['store_id'] = I('store_id');
            $where3['store_id'] = I('store_id');
            $where4['store_id'] = I('store_id');
            $where5['store_id'] = I('store_id');
            $where6['store_id'] = I('store_id');
            $where7['store_id'] = I('store_id');
            $where8['store_id'] = I('store_id');
            $this->assign('store_id',urldecode(I('store_id')));
        }

        // 泡发日期
        $start_time=I('start_time');//高级搜索条件  开始时间
        $end_time=I('end_time');//高级搜索条件  结束时间
        if(!empty($start_time) || !empty($end_time)){
            $a_day = date('Y-m-d',time());
            $a_date = $a_day.'00:00:00';
            $aa_time = strtotime($a_date);
            $a_time = $aa_time-86400*$end_time;//今天的前几天(小)
            $b_time = $aa_time-86400*$start_time;//今天的前几天(大)
            $start_time = date('Y-m-d',$a_time).' 24:00';
            $end_time = date('Y-m-d',$b_time).' 24:00';
            $condition['send_time']=array(array('EGT',$start_time),array('ELT',$end_time)); 
            $this->assign('start_time',urldecode(I('start_time')));  
            $this->assign('end_time',urldecode(I('end_time')));  
        }


        //会员姓名
        if(!empty(I('username'))){
            $mapss['nickname']=array('like','%'.urldecode(I('username')).'%');
            $useridarr = M('users')->where($mapss)->field('user_id')->select();
            $userids = formatArray($useridarr,'user_id');
            $condition['uid']=array('in',$userids);
            $this->assign('username',urldecode(I('username')));
        }

        //手机号
        if(!empty(I('mobile'))){
            $mapss1['mobile']=array('like','%'.urldecode(I('mobile')).'%');
            $useridarr = M('users')->where($mapss1)->field('user_id')->select();
            $userids = formatArray($useridarr,'user_id');
            $condition['uid']=array('in',$userids);
            $this->assign('mobile',urldecode(I('mobile')));
        }

        //发制数量
        if(!empty(I('count'))){
            $condition['count'] = I('count');
            $this->assign('count',urldecode(I('count')));
        }

        //商品编码
        if(!empty(I('sku'))){
            $condition['sku']=I('sku');
            $this->assign('sku',urldecode(I('sku')));
        }

        //口感要求
        if(!empty(I('requirement'))){
            $condition['requirement']=urldecode(I('requirement'));
            $this->assign('_requirement',urldecode(I('requirement')));
        }

        //其他要求
        if(!empty(I('special'))){
            $condition['special']=array('like','%'.urldecode(I('special')).'%');
            $this->assign('special',urldecode(I('special')));
        }

        // 领取区间
        $tlstart_time = I('tlstart_time');//高级搜索条件  开始时间
        $tlend_time = I('tlend_time');//高级搜索条件  结束时间
        //如果只有开始时间 则结束时间为当前
        if (!empty($tlstart_time) && empty($tlend_time)) {
            $tlstart_day = $tlstart_time.' 00:00:00';
            $condition['receive_time'] = array('EGT',$tlstart_day); 
            $this->assign('tlstart_time',urldecode(I('tlstart_time')));  
        }else if (empty($tlstart_time) && !empty($tlend_time)) {
            $tlend_day = $tlend_time.' 23:59:59';
            $condition['receive_time'] = array('ELT',$tlend_day);
            $this->assign('tlend_time',urldecode(I('tlend_time')));  
        }else if(!empty($tlstart_time) && !empty($tlend_time)){
            $tlstart_day = $tlstart_time.' 00:00:00';
            $tlend_day = $tlend_time.' 23:59:59';
            $condition['receive_time']=array(array('EGT',$tlstart_day),array('ELT',$tlend_day)); 
            $this->assign('tlstart_time',urldecode(I('tlstart_time')));  
            $this->assign('tlend_time',urldecode(I('tlend_time')));  
        }


        //经手人
        if(!empty(I('creator'))){
            $mapsss['user_name']=array('like','%'.urldecode(I('creator')).'%');
            $adminidarr = M('admin')->where($mapsss)->field('admin_id')->select();
            $adminids = formatArray($adminidarr,'admin_id');
            $condition['cid']=array('in',$adminids);
            $this->assign('creator',urldecode(I('creator')));
        }

        //搜索条件  提领状态
        if(!empty(I('status'))){
            $condition['status'] = urldecode(I('status'));
            $this->assign('status',urldecode(I('status')));
        }

        //搜索条件  代发阶段
        if(!empty(I('stage'))){
            $condition['stage'] = urldecode(I('stage'));
            $this->assign('stage',urldecode(I('stage')));
        }


        if(!empty(I('product_name'))){
            $condition['product_name']=array('like','%'.urldecode(I('product_name')).'%');
            $this->assign('_product_name',urldecode(I('product_name')));
        }
        if(!empty(I('receive_time'))){
            $condition['receive_time'] = array('like','%'.I('receive_time').'%');
            $this->assign('_time',I('receive_time'));
        }

        $count = $model->where($condition)->count();
        $Page  = new \Think\Page($count,$pageCount);  

        //订单编号
        if (I('sn')) {
            $Page->parameter['sn'] = urlencode(urldecode(I('sn')));
        }

        //门店
        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(urldecode(I('store_id')));
        }

        //泡发时间
        if (I('start_time')) {
            $Page->parameter['start_time'] = urlencode(urldecode(I('start_time')));
        }
        if (I('end_time')) {
            $Page->parameter['end_time'] = urlencode(urldecode(I('end_time')));
        }

        //会员姓名
        if (I('username')) {
            $Page->parameter['username'] = urlencode(urldecode(I('username')));
        }

        //会员手机号
        if (I('mobile')) {
            $Page->parameter['mobile'] = urlencode(urldecode(I('mobile')));
        }

        //商品编码
        if (I('sku')) {
            $Page->parameter['sku'] = urlencode(urldecode(I('sku')));
        }

        //发制数量
        if (I('count')) {
            $Page->parameter['count'] = urlencode(urldecode(I('count')));
        }
        

        //口感要求
        if (I('requirement')) {
            $Page->parameter['requirement'] = urlencode(urldecode(I('requirement')));
        }

        //其他要求
        if (I('special')) {
            $Page->parameter['special'] = urlencode(urldecode(I('special')));
        }

        //领取时间
        if (I('tlstart_time')) {
            $Page->parameter['tlstart_time'] = urlencode(urldecode(I('tlstart_time')));
        }
        if (I('tlend_time')) {
            $Page->parameter['tlend_time'] = urlencode(urldecode(I('tlend_time')));
        }

        //会员姓名
        if (I('creator')) {
            $Page->parameter['creator'] = urlencode(urldecode(I('creator')));
        }

        //提领状态
        if (I('status')) {
            $Page->parameter['status'] = urlencode(urldecode(I('status')));
        }

        //代发阶段
        if (I('stage')) {
            $Page->parameter['stage'] = urlencode(urldecode(I('stage')));
        }

       
        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode($pageCount);
            $this->assign('_pageCount',$pageCount);
        }
        $show = $Page->show();

        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();
        $this->assign('store',$store);

        $prom_list = $model->where($condition)->order("create_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        //统计
        //如果为店长 则显示门店
        $role_id = session('role_id');
        if ($role_id>=4) {
            $where1['store_id'] = array('in',session('store_id'));
            $where2['store_id'] = array('in',session('store_id'));
            $where3['store_id'] = array('in',session('store_id'));
            $where4['store_id'] = array('in',session('store_id'));
            $where5['store_id'] = array('in',session('store_id'));
            $where6['store_id'] = array('in',session('store_id'));
            $where7['store_id'] = array('in',session('store_id'));
            $where8['store_id'] = array('in',session('store_id'));
            
            $this->assign('role_id',$role_id);
        }
        
        //未领取份数
        $where1['status'] = '未提领';
        $where1['is_del'] = 0;
        $nottl_count = M('send_list')->where($condition)->where($where1)->count();
        $this->assign('nottl_count',$nottl_count);
        //已领取份数
        $where2['status'] = '已提领';
        $where2['is_del'] = 0;
        $tl_count = M('send_list')->where($condition)->where($where2)->count();
        $this->assign('tl_count',$tl_count);
        //本月新增份数
        $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
        $endDate=date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));     //加一个月减去一天
        $month=strtotime($BeginDate);
        $month1=strtotime($endDate)+24*3600-1;
        $where3['create_time']=array(array('gt',$month),array('lt',$month1));
        $where3['is_del'] = 0;
        $add_count = M('send_list')->where($condition)->where($where3)->count();
        $this->assign('add_count',$add_count);

        //历史代发份数
        // $BeginDate=date('Y-m-01', strtotime(date("Y-m-d"))); //获取当前月份第一天
        // $month=strtotime($BeginDate);
        $month=time();
        $where4['create_time'] = array('lt',$month);
        $where4['is_del'] = 0;
        $history_count = M('send_list')->where($condition)->where($where4)->count();
        $this->assign('history_count',$history_count);
        
        //泡发状态份数
        $where5['stage'] = '泡软';
        $where5['status'] = "未提领";
        $where5['is_del'] = 0;
        $paoruan_count = M('send_list')->where($condition)->where($where5)->count();
        $this->assign('paoruan_count',$paoruan_count);

        //剪洗状态份数
        $where6['stage'] = '剪洗';
        $where6['status'] = "未提领";
        $where6['is_del'] = 0;
        $jianxi_count = M('send_list')->where($condition)->where($where6)->count();
        $this->assign('jianxi_count',$jianxi_count);

        //煮发状态份数
        $where7['stage'] = '煮发';
        $where7['status'] = "未提领";
        $where7['is_del'] = 0;
        $zhufa_count = M('send_list')->where($condition)->where($where7)->count();
        $this->assign('zhufa_count',$zhufa_count);

        //封装状态份数
        $where8['stage'] = '封装';
        $where8['status'] = "未提领";
        $where8['is_del'] = 0;
        $fengzhuang_count = M('send_list')->where($condition)->where($where8)->count();
        $this->assign('fengzhuang_count',$fengzhuang_count);

        //传递当前时间
        $time = date('Y-m-d', time());
        $this->assign('time',$time);
        $this->assign('search',I('search'));
        $this->assign('prom_list',$prom_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    //寄存管理
    public function deposit_list(){
        $condition = array();
        $model = M('deposit_list');

        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        //如果为店长 则显示门店
        $role_id = session('role_id');
        if($role_id==5){
            $condition['cid'] = session('admin_id');
            $this->assign('role_id',$role_id);
        }elseif($role_id==1){
            $this->assign('role_id',$role_id);
        }else{
            $condition['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }
        //订单编号
        if(!empty(I('sn'))){
            $condition['sn']=array('like','%'.urldecode(I('sn')).'%');
            $this->assign('sn',urldecode(I('sn')));
        }

        //门店
        if(!empty(I('store_id'))){
            $condition['store_id'] = I('store_id');
            $where4['store_id'] = I('store_id');
            $this->assign('store_id',urldecode(I('store_id')));
        }
        //商品名称
        if(!empty(I('product_name'))){
            $condition['product_name']=array('like','%'.urldecode(I('product_name')).'%');
            $this->assign('_product_name', urldecode(I('product_name')));
        }

        //寄存日期
        $receive_time = urldecode(I('receive_time'));
        if($receive_time){
            $gap = explode('-', $receive_time);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            if($begin && $end){
                $condition['receive_time'] = array('between',"$begin,$end");
            }
            $this->assign('_receive_time', urldecode(I('receive_time')));
            $this->assign('time',$gap[0]);
        }else{
            //当前日期
            $now = date('Y/m/d',time());
            $this->assign('time',$now);
        }

        //会员姓名
        if(!empty(I('username'))){
            $mapss['nickname']=array('like','%'.urldecode(I('username')).'%');
            $useridarr = M('users')->where($mapss)->field('user_id')->select();
            $userids = formatArray($useridarr,'user_id');
            $condition['uid']=array('in',$userids);
            $this->assign('username',urldecode(I('username')));
        }

        //手机号
        if(!empty(I('mobile'))){
            $mapss1['mobile']=array('like','%'.urldecode(I('mobile')).'%');
            $useridarr = M('users')->where($mapss1)->field('user_id')->select();
            $userids = formatArray($useridarr,'user_id');
            $condition['uid']=array('in',$userids);
            $this->assign('mobile',urldecode(I('mobile')));
        }

        //初始盒数
        $a_box = I('a_box');
        $b_box = I('b_box');
        //起始盒数都存在
        if(!empty($a_box) && !empty($b_box)){
            if ($a_box>$b_box) {
                $this->error('请填写正确的原始盒数');
            }else{
                $condition['box']=array('between',array($a_box,$b_box));
            }
            $this->assign('a_box',urldecode(I('a_box')));
            $this->assign('b_box',urldecode(I('b_box')));
        //如果盒数全为0
        }else if($a_box==='0' && $b_box==='0'){
            $condition['box']=0;
            $this->assign('a_box',0);
            $this->assign('b_box',0);
        //开始存在 结束为0
        }else if(!empty($a_box) && empty($b_box)){
            $this->error('请填写正确的原始盒数');
            $this->assign('a_box',$a_box);
            $this->assign('b_box',$b_box);
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_box) && !empty($b_box)){
            $condition['box']=array('between',array($a_box,$b_box));
            $this->assign('a_box',$a_box);
            $this->assign('b_box',$b_box);
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_box) && empty($b_box)){
            
        }


        //初始根数
        $a_count = I('a_count');
        $b_count = I('b_count');
        //起始根数都存在
        if(!empty($a_count) && !empty($b_count)){
            $condition['count']=array('between',array($a_count,$b_count));
            $this->assign('a_count',urldecode(I('a_count')));
            $this->assign('b_count',urldecode(I('b_count')));
        //如果盒数全为0
        }else if($a_count==='0' && $b_count==='0'){
            $condition['count']=0;
            $this->assign('a_count',0);
            $this->assign('b_count',0);
        //开始存在 结束为0
        }else if(!empty($a_count) && empty($b_count)){
            $condition['count']=array('between',array($b_count,$a_count));
            $this->assign('a_count',$a_count);
            $this->assign('b_count',$b_count);
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_count) && !empty($b_count)){
            $condition['count']=array('between',array($a_count,$b_count));
            $this->assign('a_count',$a_count);
            $this->assign('b_count',$b_count);
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_count) && empty($b_count)){
            
        }

        //剩余盒数
        $a_over_box = I('a_over_box');
        $b_over_box = I('b_over_box');
        //起始根数都存在
        if(!empty($a_over_box) && !empty($b_over_box)){
            if ($a_over_box>$b_over_box) {
                $this->error('请填写正确的剩余盒数');
            }else{
                $condition['over_box']=array('between',array($a_over_box,$b_over_box));
            }
            $this->assign('a_over_box',urldecode(I('a_over_box')));
            $this->assign('b_over_box',urldecode(I('b_over_box')));
        //如果盒数全为0
        }else if($a_over_box==='0' && $b_over_box==='0'){
            $condition['over_box']=0;
            $this->assign('a_over_box',0);
            $this->assign('b_over_box',0);
        //开始存在 结束为0
        }else if(!empty($a_over_box) && empty($b_over_box)){
            $this->error('请填写正确的原始盒数');
            $this->assign('a_over_box',$a_over_box);
            $this->assign('b_over_box',$b_over_box);
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_over_box) && !empty($b_over_box)){
            $condition['over_box']=array('between',array($a_over_box,$b_over_box));
            $this->assign('a_over_box',$a_over_box);
            $this->assign('b_over_box',$b_over_box);
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_over_box) && empty($b_over_box)){
            
        }

        //剩余根数
        $a_over_count = I('a_over_count');
        $b_over_count = I('b_over_count');
        //剩余根数都存在
        if(!empty($a_over_count) && !empty($b_over_count)){
            $condition['over_count']=array('between',array($a_over_count,$b_over_count));
            $this->assign('a_over_count',urldecode(I('a_over_count')));
            $this->assign('b_over_count',urldecode(I('b_over_count')));
        //如果盒数全为0
        }else if($a_over_count==='0' && $b_over_count==='0'){
            $condition['over_count']=0;
            $this->assign('a_over_count',0);
            $this->assign('b_over_count',0);
        //开始存在 结束为0
        }else if(!empty($a_over_count) && empty($b_over_count)){
            $condition['over_count']=array('between',array($b_over_count,$a_over_count));
            $this->assign('a_over_count',$a_over_count);
            $this->assign('b_over_count',$b_over_count);
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_over_count) && !empty($b_over_count)){
            $condition['over_count']=array('between',array($a_over_count,$b_over_count));
            $this->assign('a_over_count',$a_over_count);
            $this->assign('b_over_count',$b_over_count);
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_over_count) && empty($b_over_count)){
            
        }

        //寄存日期
        $tl_time = urldecode(I('tl_time'));
        if($tl_time){
            $gap1 = explode('-', $tl_time);
            $begin1 = strtotime($gap1[0]);
            $end1 = strtotime($gap1[1])+24*3600-1;
            if($begin1 && $end1){
                $condition['tl_time'] = array('between',"$begin1,$end1");
            }
            $this->assign('tl_time', urldecode(I('tl_time')));
            $this->assign('tll_time',$gap1[0]);
        }else{
            //当前日期
            $now = date('Y/m/d',time());
            $this->assign('tll_time',$now);
        }

        //搜索条件  提领状态
        if(!empty(I('status'))){
            if (urldecode(I('status'))=='未提领') {
                $condition['status'] = 0;
            }else if (urldecode(I('status'))=='已提领') {
                $condition['status'] = 1;
            }else{
                // //搜索转代发表
                // $daifa_list = M('zhuandaifa')->field('deposit_id')->select();
                // $_deposit_id_arr = array();
                // foreach ($daifa_list as $ke => $va) {
                //     $_deposit_id_arr[] = $va['deposit_id'];
                // }

                // $condition['id'] = array('in', $_deposit_id_arr);
                $condition['is_zhuandaifa'] = 1;
            }
            $this->assign('status',urldecode(I('status')));
        }

        //经手人
        if(!empty(I('creator'))){
            $mapsss['user_name']=array('like','%'.urldecode(I('creator')).'%');
            $adminidarr = M('admin')->where($mapsss)->field('admin_id')->select();
            $adminids = formatArray($adminidarr,'admin_id');
            $condition['cid']=array('in',$adminids);
            $this->assign('creator',urldecode(I('creator')));
        }

        $count = $model->where($condition)->count();
        $Page  = new \Think\Page($count,$pageCount);  

        //商品名称
        if (I('product_name')) {
            $Page->parameter['product_name'] = urlencode(urldecode(I('product_name')));
        }
        
        //寄存日期
        if (I('receive_time')) {
            $Page->parameter['receive_time'] = urlencode(urldecode(I('receive_time')));
        }

        //订单编号
        if (I('sn')) {
            $Page->parameter['sn'] = urlencode(urldecode(I('sn')));
        }

        //门店
        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(urldecode(I('store_id')));
        }

        //会员姓名
        if (I('username')) {
            $Page->parameter['username'] = urlencode(urldecode(I('username')));
        }

        //会员手机号
        if (I('mobile')) {
            $Page->parameter['mobile'] = urlencode(urldecode(I('mobile')));
        }
        //初始盒数
        if(!empty(I('box'))){
            $Page->parameter['box'] = urlencode(urldecode(I('box')));
        }
        //初始根数
        if(!empty(I('count'))){
            $Page->parameter['count'] = urlencode(urldecode(I('count')));
        }
        //剩余盒数
        if(!empty(I('over_box'))){
            $Page->parameter['over_box'] = urlencode(urldecode(I('over_box')));
        }
        //剩余根数
        if(!empty(I('over_count'))){
            $Page->parameter['over_count'] = urlencode(urldecode(I('over_count')));
        }

        //提领日期
        if (I('tl_time')) {
            $Page->parameter['tl_time'] = urlencode(urldecode(I('tl_time')));
        }

        //提领状态
        if (I('status')) {
            $Page->parameter['status'] = urlencode(urldecode(I('status')));
        }

        //经办人姓名
        if (I('creator')) {
            $Page->parameter['creator'] = urlencode(urldecode(I('creator')));
        }

        //起始盒数都存在
        if(!empty($a_box) && !empty($b_box)){
            $Page->parameter['a_box'] = urlencode(urldecode(I('a_box')));
            $Page->parameter['b_box'] = urlencode(urldecode(I('b_box')));
        //如果盒数全为0
        }else if($a_box==='0' && $b_box==='0'){
            $Page->parameter['a_box'] = urlencode(urldecode(I('a_box')));
            $Page->parameter['b_box'] = urlencode(urldecode(I('b_box')));
        //开始存在 结束为0
        }else if(!empty($a_box) && empty($b_box)){
            
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_box) && !empty($b_box)){
            $Page->parameter['a_box'] = urlencode(urldecode(I('a_box')));
            $Page->parameter['b_box'] = urlencode(urldecode(I('b_box')));
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_box) && empty($b_box)){
            
        }

        //起始根数都存在
        if(!empty($a_count) && !empty($b_count)){
            $Page->parameter['a_count'] = urlencode(urldecode(I('a_count')));
            $Page->parameter['b_count'] = urlencode(urldecode(I('b_count')));
        //如果盒数全为0
        }else if($a_count==='0' && $b_count==='0'){
            $Page->parameter['a_count'] = urlencode(urldecode(I('a_count')));
            $Page->parameter['b_count'] = urlencode(urldecode(I('b_count')));
        //开始存在 结束为0
        }else if(!empty($a_count) && empty($b_count)){
            
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_count) && !empty($b_count)){
            $Page->parameter['a_count'] = urlencode(urldecode(I('a_count')));
            $Page->parameter['b_count'] = urlencode(urldecode(I('b_count')));
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_count) && empty($b_count)){
            
        }

        //起始根数都存在
        if(!empty($a_over_box) && !empty($b_over_box)){
            $Page->parameter['a_over_box'] = urlencode(urldecode(I('a_over_box')));
            $Page->parameter['b_over_box'] = urlencode(urldecode(I('b_over_box')));
        //如果盒数全为0
        }else if($a_over_box==='0' && $b_over_box==='0'){
            $Page->parameter['a_over_box'] = urlencode(urldecode(I('a_over_box')));
            $Page->parameter['b_over_box'] = urlencode(urldecode(I('b_over_box')));
        //开始存在 结束为0
        }else if(!empty($a_over_box) && empty($b_over_box)){
            
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_over_box) && !empty($b_over_box)){
            $Page->parameter['a_over_box'] = urlencode(urldecode(I('a_over_box')));
            $Page->parameter['b_over_box'] = urlencode(urldecode(I('b_over_box')));
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_over_box) && empty($b_over_box)){
            
        }

        //剩余根数都存在
        if(!empty($a_over_count) && !empty($b_over_count)){
            $Page->parameter['a_over_count'] = urlencode(urldecode(I('a_over_count')));
            $Page->parameter['b_over_count'] = urlencode(urldecode(I('b_over_count')));
        //如果盒数全为0
        }else if($a_over_count==='0' && $b_over_count==='0'){
            $Page->parameter['a_over_count'] = urlencode(urldecode(I('a_over_count')));
            $Page->parameter['b_over_count'] = urlencode(urldecode(I('b_over_count')));
        //开始存在 结束为0
        }else if(!empty($a_over_count) && empty($b_over_count)){
            $Page->parameter['a_over_count'] = urlencode(urldecode(I('a_over_count')));
            $Page->parameter['b_over_count'] = urlencode(urldecode(I('b_over_count')));
        //如果开始的盒数为0 结束盒数存在
        }else if(empty($a_over_count) && !empty($b_over_count)){
            $Page->parameter['a_over_count'] = urlencode(urldecode(I('a_over_count')));
            $Page->parameter['b_over_count'] = urlencode(urldecode(I('b_over_count')));
        //如果开始的盒数不存在 结束盒数也不存在  则不搜索盒数
        }else if(empty($a_over_count) && empty($b_over_count)){
            
        }

        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode($pageCount);
            $this->assign('_pageCount',$pageCount);
        }

        $show = $Page->show();

        $prom_list = $model->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        //统计
        //如果为店长 则显示门店
        $role_id = session('role_id');
        if ($role_id==4) {
            $where1['store_id'] = session('store_id');
            $where2['store_id'] = session('store_id');
            $where4['store_id'] = session('store_id');
            $this->assign('role_id',$role_id);
        }
        
        //未领取份数
        $where1['status'] = 0;
        $where1['is_del'] = 0;
        $nottl_count = $model->where($condition)->where($where1)->count();
        $this->assign('nottl_count',$nottl_count);
        //已领取份数
        $where2['status'] = 1;
        $where2['is_del'] = 0;
        $tl_count = $model->where($condition)->where($where2)->count();
        $this->assign('tl_count',$tl_count);

        //转代发分数 
        $where5['is_del'] = 0;
        $prom_list_idArr = $model->where($condition)->where($where5)->order("id desc")->field('id')->select();
        $deposit_id = array();
        foreach ($prom_list_idArr as $key2 => $value2) {
            $deposit_id[] = $value2['id'];
        }
        if ($deposit_id) {
            $where3['deposit_id'] = array('in', $deposit_id);
            $daifa_count = M('zhuandaifa')->where($where3)->count();
        }else{
            $daifa_count = 0;
        }
        $this->assign('daifa_count',$daifa_count);

        //历史代发份数
        $now_time = time();
        $where4['create_time'] = array('lt',$now_time);
        $where4['is_del'] = 0;
        $history_count = $model->where($where4)->count();
        $this->assign('history_count',$history_count);
        



        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();
        $this->assign('store',$store);

        $this->assign('search',I('search'));
        $this->assign('prom_list',$prom_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    //上门管理
    public function visit_list(){
        $condition = array();
        $model = M('visit_list');

        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        //如果为店长 则显示门店
        $role_id = session('role_id');
        if($role_id==5){
            $condition['cid'] = session('admin_id');
            $count_search2['cid']=session('admin_id');
            $this->assign('role_id',$role_id);
        }elseif($role_id==1){
            $this->assign('role_id',$role_id);
        }else{
            $condition['store_id'] = array('in',session('store_id'));
            $count_search1['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }
        
        if(!empty(I('mobile'))){
            $condition['mobile']=array('like','%'.I('mobile').'%');
            $count_search['mobile']=array('like','%'.I('mobile').'%');
            $count_search1['mobile']=array('like','%'.I('mobile').'%');
            $count_search2['mobile']=array('like','%'.I('mobile').'%');
            $this->assign('_mobile',I('mobile'));
        }

        if(!empty(I('store_id'))){
            $condition['store_id']=I('store_id');
            $maos['store_id']=I('store_id');
            $count_search1['store_id']=I('store_id');
            $this->assign('_store_id',I('store_id'));
        }

        if(!empty(I('cid'))){
            $condition['cid']=I('cid');
            $count_search2['cid']=I('cid');
            $this->assign('_cid',I('cid'));

        }

        if(!empty(urldecode(I('remark')))){
            $condition['remark']=array('like','%'.urldecode(I('remark')).'%');
            $count_search['remark']=array('like','%'.urldecode(I('remark')).'%');
            $count_search1['remark']=array('like','%'.urldecode(I('remark')).'%');
            $count_search2['remark']=array('like','%'.urldecode(I('remark')).'%');
            $this->assign('remark',urldecode(urldecode(I('remark'))));

        }
        //日期
        $receive_time = urldecode(I('receive_time'));
        if($receive_time){
            $gap = explode('-', $receive_time);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            if($begin && $end){
                $condition['create_time'] = array('between',"$begin,$end");
                $condition1['create_time'] = array('between',"$begin,$end");
                $count_search['create_time']=array('between',"$begin,$end");
                $count_search1['create_time']=array('between',"$begin,$end");
                $count_search2['create_time']=array('between',"$begin,$end");
            }
            $this->assign('_receive_time', urldecode(I('receive_time')));
        }
        $count = $model->where($condition)->count();

        $Page  = new \Think\Page($count,$pageCount);  

        if (I('mobile')) {
            $Page->parameter['mobile'] = urlencode((I('mobile')));
        }
        
        if (I('cid')) {
            $Page->parameter['cid'] = urlencode(I('cid'));
        }

        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(I('store_id'));
        }
        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode($pageCount);
            $this->assign('_pageCount',$pageCount);
        }

        //寄存日期
        if (I('receive_time')) {
            $Page->parameter['receive_time'] = urlencode(urldecode(I('receive_time')));
        }

        //客户反馈
        if (I('remark')) {
            $Page->parameter['remark'] = urlencode(urldecode(I('remark')));
        }

        $show = $Page->show();

        $visit_list = $model->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();

        //所有服务顾问列表
        if ($role_id==4) {
            $maos['store_id'] = session('store_id');
        }
        $maos['role_id'] = array('in',array('4','5'));
        $guwen = M('admin')->where($maos)->field('user_name,admin_id')->select();
        
        //上门总数
       
        $total_count = $model->where($condition1)->count();
        //门店上门
        if ($count_search1['store_id']) {
            $store_count = $model->where($count_search1)->count();
        }else{
            $store_count = 0;
        }
        if ($count_search2['cid']) {
            $guwen_count = $model->where($count_search2)->count();
        }else{
            $guwen_count = 0;
        }
        //服务顾问总数

        $this->assign('total_count',$total_count);
        $this->assign('store_count',$store_count);
        $this->assign('guwen_count',$guwen_count);
        $this->assign('store',$store);
        $this->assign('guwen',$guwen);
        $this->assign('visit_list',$visit_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }


    //根据门店获取门店顾问
    public function getGuWen(){
        $store_id = I('store_id');
        if ($store_id) {
            $where['store_id'] = $store_id;
        }
        $where['role_id'] = array('in',array('4','5'));
        $guwen = M('admin')->where($where)->field('user_name,admin_id')->select();
        $json = json_encode($guwen);
        echo $json;exit();
    }


    //沟通管理
    public function communication_list(){
        $condition = array();
        $model = M('communication');

        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        //如果为店长 则显示门店
        $role_id = session('role_id');
        // if ($role_id==4) {
        //     $condition['store_id'] = session('store_id');
        //     $posts['store_id'] = session('store_id');
        //     $this->assign('role_id',$role_id);
        // }
        if($role_id==5){
            $condition['cid'] = session('admin_id');
            $this->assign('role_id',$role_id);
            $posts['cid'] = array('in',session('store_id'));
            $maps1['cid'] = array('in',session('store_id'));
        }elseif ($role_id==1) {
            $this->assign('role_id',$role_id);
        }else{
            $condition['store_id'] = array('in',session('store_id'));
            $posts['store_id'] = array('in',session('store_id'));
            $maps1['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }

        if(!empty(I('tel'))){
            $condition['tel']=array('like','%'.I('tel').'%');
            $this->assign('_tel',I('tel'));
        }

        if(!empty(I('store_id'))){
            $condition['store_id']=I('store_id');
            $maos['store_id']=I('store_id');
            $posts['store_id'] = I('store_id');
            $maps1['store_id'] = I('store_id');
            $this->assign('_store_id',I('store_id'));
        }

        if(!empty(I('cid'))){
            $condition['fuid']=I('cid');
            $maps1['fuid'] = I('cid');
            $this->assign('cid',I('cid'));
        }

        if(!empty(I('type'))){
            $condition['type']=I('type');
            $maps1['type'] = I('type');
            $this->assign('type',I('type'));
        }

        if(!empty(I('status'))){
            $condition['status']=urldecode(I('status'));
            $this->assign('status',urldecode(I('status')));
        }
        //会员姓名
        if(!empty(I('username'))){
            $user['nickname']=array('like','%'.urldecode(I('username')).'%');
            $user_result = M('users')->where($user)->field('user_id')->select();
            $user_id=formatArray($user_result,'user_id');
            $condition['uid']=array('in', $user_id);
            $this->assign('username',urldecode(I('username')));
        }

        //内容
        if(!empty(I('content'))){
            $condition['content']=array('like','%'.urldecode(I('content')).'%');
            $this->assign('content',urldecode(I('content')));
        }

        //是否完成沟通搜索条件
        if (!empty(I('com_status'))) {
            if (I('com_status')==1) {
                $condition['com_status']=0;
            }else{
                $condition['com_status']=array('neq',0);
            }
            $this->assign('com_status',urldecode(I('com_status')));
        }
        // //创建时间
        // if(!empty(I('start_time'))){
        //     $time_a = I('start_time').' 00:00:00';
        //     $time_b = I('start_time').' 23:59:59';
        //     $start = strtotime($time_a);//起始时间
        //     $end = strtotime($time_b);//结束时间
        //     $condition['create_time']=array(array('gt',$start),array('lt',$end));
        //     $this->assign('start_time',I('start_time'));
        // }
        //创建时间
        $start_time = urldecode(I('start_time'));
        if($start_time){
            $gap = explode('-', $start_time);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            if($begin && $end){
                $condition['create_time'] = array('between',"$begin,$end");
                $maps1['create_time'] = array('between',"$begin,$end");
            }
            $this->assign('start_time', urldecode(I('start_time')));
        }

        $count = $model->where($condition)->count();
        $Page  = new \Think\Page($count,$pageCount); 
        
        if (I('tel')) {
            $Page->parameter['tel'] = urlencode((I('tel')));
        }
        
        if (I('cid')) {
            $Page->parameter['cid'] = urlencode(I('cid'));
        }

        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(I('store_id'));
        }

        if (I('type')) {
            $Page->parameter['type'] = urlencode(I('type'));
        }

        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode($pageCount);
            $this->assign('_pageCount',$pageCount);
        }

        //寄存日期
        if (I('start_time')) {
            $Page->parameter['start_time'] = urlencode(urldecode(I('start_time')));
        }

        if (I('status')) {
            $Page->parameter['status'] = urlencode(urldecode(I('status')));
        }
        if (I('username')) {
            $Page->parameter['username'] = urlencode(urldecode(I('username')));
        }

        if (I('content')) {
            $Page->parameter['content'] = urlencode(urldecode(I('content')));
        }
        if (I('com_status')) {
            $Page->parameter['com_status'] = urlencode(urldecode(I('com_status')));
        }

        $show = $Page->show();

        
        $visit_list = $model->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();

        //所有服务顾问列表
        $maos['role_id'] = array('in',array('4','5'));
        $guwen = M('admin')->where($maos)->field('user_name,admin_id')->select();

        //计算今日完成沟通
        $today = date('Y-m-d',time());
        $today_a = $today.' 00:00:00';
        $today_b = $today.' 23:59:59';
        $start_time = strtotime($today_a);
        $end_time = strtotime($today_b);
        $maps['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
        $maps['com_status'] = 0;
        $today_count = $model->where($maps)->where($condition)->count();

        //计算待完成沟通
        $maps1['com_status'] = array('neq',0);
        $today_count1 = $model->where($maps1)->count();

        //历史记录
        $old_count = $model->where($posts)->count();

        //当前日期
        $now = date('Y/m/d',time());
        $this->assign('now',$now);

        $this->assign('need_count',$today_count1?$today_count1:0);//待完成统计
        $this->assign('today_count',$today_count);//今日完成沟通
        $this->assign('count',$old_count);//历史记录
        $this->assign('store',$store);
        $this->assign('guwen',$guwen);
        $this->assign('visit_list',$visit_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }


    //评论管理
    public function comment_list(){
        $condition = array();
        $model = M('comment');
        $count = $model->where($condition)->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page  = new \Think\Page($count,$pageCount);         
        $show = $Page->show();

        if(!empty($_POST['content'])){
            $condition['content']=array('like','%'.$_POST['content'].'%');
        }

     

        $prom_list = $model->where($condition)->order("comment_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $this->assign('prom_list',$prom_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    //评论详情
    public function comment_info(){

        $id = I('get.id');
        $res = M('comment')->where(array('comment_id'=>$id))->find();
        //dump($res);
        if(!$res){
            exit($this->error('不存在该评论'));
        }
        if(IS_POST){
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['add_time'] = time();
            $add['username'] = 'admin';

            $add['is_show'] = 1;

            $row =  M('comment')->add($add);
            if($row){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;

        }
        $reply = M('comment')->where(array('parent_id'=>$id))->select(); // 评论回复列表
         
        $this->assign('comment',$res);
        $this->assign('reply',$reply);
        $this->display();

    }

    //投诉管理
    public function complaint_list(){
        $condition = array();

        //如果为店长  则展示该门店的数据
        $admin_info = M('admin')->field('role_id, store_id')->where('admin_id='.session('admin_id'))->find();

        if($admin_info['role_id']==5){
            $condition['target_id'] = session('admin_id');
            $this->assign('role_id',$role_id);
        }elseif ($admin_info['role_id']==1) {
            $this->assign('role_id',$admin_info['role_id']);
        }else{
            $condition['store_id'] = array('in',session('store_id'));
            $this->assign('role_id',$role_id);
        }

        if(!empty(I('type'))){
            $condition['type']=urldecode(I('type'));
            $this->assign('_type',urldecode(I('type')));
        }

        if(!empty(I('store_id'))){
            $condition['store_id']=urldecode(I('store_id'));
            $this->assign('store_id',urldecode(I('store_id')));
        }

        //编号搜索
        if(!empty(I('id'))){
            $condition['id']=I('id');
            $this->assign('id',urldecode(I('id')));
        }

        //内容
        if(!empty(I('content'))){
            $condition['content']=array('like','%'.urldecode(I('content')).'%');
            $this->assign('content',urldecode(I('content')));
        }

        //处理状态
        if(!empty(I('status'))){
            if (I('status')==1) {
                $condition['status'] = 1;
            }else if (I('status')==2) {
                $condition['status'] = 0;
            }
            $this->assign('status',urldecode(I('status')));
        }

        //投诉日期
        $receive_time = urldecode(I('receive_time'));
        if($receive_time){
            $gap = explode('-', $receive_time);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1])+24*3600-1;
            if($begin && $end){
                $condition['create_time'] = array('between',"$begin,$end");
            }
            $this->assign('_receive_time', urldecode(I('receive_time')));
            $this->assign('now',$gap[0]);
        }else{
            //当前日期
            $now = date('Y/m/d',time());
            $this->assign('now',$now);
        }

        //手机号搜索
        if(!empty(I('mobile'))){
            $condition['mobile']=array('like','%'.I('mobile').'%');
            $this->assign('mobile',urldecode(I('mobile')));
        }

        //被投诉人搜索
        if(!empty(I('cid'))){
            $condition['target_id']=I('cid');
            $this->assign('cid',urldecode(I('cid')));
        }

        $model = M('complaint');
        $count = $model->where($condition)->count();

        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        //展示条数        
        $this->assign('pageCount',$pageCount);

        $Page  = new \Think\Page($count,$pageCount); 

        if (I('type')) {
            $Page->parameter['type'] = urlencode(urldecode(I('type')));
        }
        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode(I('pageCount'));
        }
        if (I('status')) {
            $Page->parameter['status'] = urlencode(urldecode(I('status')));
        }
        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(urldecode(I('store_id')));
        }
        if (I('id')) {
            $Page->parameter['id'] = urlencode(urldecode(I('id')));
        }

        if (I('mobile')) {
            $Page->parameter['mobile'] = urlencode(urldecode(I('mobile')));
        }

        if (I('cid')) {
            $Page->parameter['cid'] = urlencode(urldecode(I('cid')));
        }
         //内容
        if (I('content')) {
            $Page->parameter['content'] = urlencode(urldecode(I('content')));
        }
        //寄存日期
        if (I('receive_time')) {
            $Page->parameter['receive_time'] = urlencode(urldecode(I('receive_time')));
        }
        $prom_list = $model->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $show = $Page->show();
        //所有门店列表
        $store = D('store')->where('is_forbid=0')->field('store_name,store_id')->select();
        //所有服务顾问列表
        if ($role_id==4) {
            $maos['store_id'] = session('store_id');
        }
        $maos['role_id'] = array('in',array('4','5'));
        $guwen = M('admin')->where($maos)->field('user_name,admin_id')->select();
        
        //统计
        $total_count = $model->where($condition)->count();
        //已处理
        $condition['status'] = 1;
        $had_count = $model->where($condition)->count();

        //未处理
        $condition['status'] = 0;
        $not_count = $model->where($condition)->count();

        $this->assign('total_count',$total_count);
        $this->assign('had_count',$had_count);
        $this->assign('not_count',$not_count);
        $this->assign('guwen',$guwen);
        $this->assign('store',$store);
        $this->assign('search',I('search'));
        $this->assign('prom_list',$prom_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    public function  complaint_infos(){
        $id=I('id');
        $maps['id']=$id;
        $book = M('complaint')->where($maps)->find();
        $admin_id=session('admin_id');
        $maps['admin_id']=$admin_id;
        $admin=D("Admin")->where($maps)->find();
        $this->assign('book',$book);// 赋值分页输出
        $this->assign('admin',$admin);// 赋值分页输出

        $this->assign('times',time());
        $this->display();
    }
    public function  complaint_info(){
        $id=I('id');
        $maps['id']=$id;
        $book = M('complaint')->where($maps)->find();

        $this->assign('book',$book);// 赋值分页输出
        $this->display();
    }
    public function add_complaint(){
        $id=I('id');
        $status=I('status');
        $recontent=I('recontent');

        $data['id']=$id;
        $data['admin_id']=session('id');
        $data['recontent']=$recontent;
        $data['update_time']=time();
        $data['status']=$status;
        $book = M('complaint')->save($data);
        if($book){
            $this->success("操作成功",U('Admin/Services/complaint_list'));
        }else{
            $this->error("操作失败");
        }

    }
    //活动列表
    public function activity_list(){
        $condition = array();
        $model = M('activity');

        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $this->assign('pageCount',urldecode(I('pageCount')));


        if(!empty(I('title'))){
            $condition['title']=array('like','%'.urldecode(I('title')).'%');
            $this->assign('title',urldecode(I('title')));
        }
        if(!empty(I('cate_id'))){
            $condition['cate_id']=I('cate_id');
            $this->assign('cate_id',urldecode(I('cate_id')));
        }
        $count = $model->where($condition)->count();
        $Page  = new \Think\Page($count,$pageCount);         
        
        if (I('title')) {
            $Page->parameter['title'] = urlencode(urldecode(I('title')));
        }
        if (I('cate_id')) {
            $Page->parameter['cate_id'] = urlencode(urldecode(I('cate_id')));
        }
        $prom_list = $model->where($condition)->order("activity_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
       
        $show = $Page->show();
        $this->assign('prom_list',$prom_list);
        $this->assign('search',I('search'));
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    public function addEditAtv(){

        $act = I('GET.act','add');
        $info = array();
        $info['publish_time'] = time()+3600*24;
        if(I('GET.activity_id')){
           $activity_id = I('GET.activity_id');
           $info = D('activity')->where('activity_id='.$activity_id)->find();

           $info['start_day']=date('Y-m-d',$info['start_day']);
           $info['deadline']=date('Y-m-d',$info['deadline']);

           $act="edit";
        }
       
        $this->assign('act',$act);
        $this->assign('info',$info);
        $this->initEditor();
        $this->display();
    }

    //删掉活动
    public function delAtv(){
        $data = I('get.');
        $r = D('activity')->where('activity_id='.$data['activity_id'])->delete();
        if($r) exit(json_encode(1));        
    }
    
    public function adAvtHandle(){
        $data = I('post.');
        $data['start_day'] = strtotime($data['start_day']);
        $data['deadline'] = strtotime($data['deadline']);
        //$data['content'] = htmlspecialchars(stripslashes($_POST['content']));
        if($data['act'] == 'add'){
               
            $data['create_time'] = time(); 
            $r = D('activity')->add($data);
        }
        
        if($data['act'] == 'edit'){
            $data['update_time'] = time(); 
            $r = D('activity')->where('activity_id='.$data['activity_id'])->save($data);
        }
        
        if($data['act'] == 'del'){
            $r = D('activity')->where('activity_id='.$data['activity_id'])->delete();
            if($r) exit(json_encode(1));        
        }
        $referurl = U('Admin/Services/activity_list');
        if($r){
            $this->success("操作成功",$referurl);
        }else{
            $this->error("操作失败",$referurl);
        }
    }
    
    /**
     * 初始化编辑器链接     
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    private function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理        
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_Home", "");
    }    
    

    public function detail(){
        $id = I('get.id');
        $res = M('comment')->where(array('comment_id'=>$id))->find();
        if(!$res){
            exit($this->error('不存在该评论'));
        }
        if(IS_POST){
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['add_time'] = time();
            $add['username'] = 'admin';

            $add['is_show'] = 1;

            $row =  M('comment')->add($add);
            if($row){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;

        }
        $reply = M('comment')->where(array('parent_id'=>$id))->select(); // 评论回复列表
         
        $this->assign('comment',$res);
        $this->assign('reply',$reply);
        $this->display();
    }


    public function comment_del(){
        $id = $_REQUEST['del_id'];
       

        $row = M('comment')->where(array('comment_id'=>$id))->delete();
       // echo  M()->getLastSql();
        if($row){
            echo '1';
        }else{
            $this->error('删除失败');
        }
    }

    public function op(){
        $type = I('post.type');
        $selected_id = I('post.selected');
        if(!in_array($type,array('del','show','hide')) || !$selected_id)
            $this->error('非法操作');
        $where = "comment_id IN ({$selected_id})";
        if($type == 'del'){
            //删除回复
            $where .= " OR parent_id IN ({$selected_id})";
            $row = M('comment')->where($where)->delete();
//            exit(M()->getLastSql());
        }
        if($type == 'show'){
            $row = M('comment')->where($where)->save(array('is_show'=>1));
        }
        if($type == 'hide'){
            $row = M('comment')->where($where)->save(array('is_show'=>0));
        }
        if(!$row)
            $this->error('操作失败');
        $this->success('操作成功');

    }

    public function ajaxindex(){
        $model = M('comment');
        $username = I('nickname','','trim');
        $content = I('content','','trim');
        $where=' parent_id = 0';
        if($username){
            $where .= " AND username='$username'";
        }
        if($content){
            $where .= " AND content like '%{$content}%'";
        }        
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,16);
        $show = $Page->show();
                
        $comment_list = $model->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        if(!empty($comment_list))
        {
            $goods_id_arr = get_arr_column($comment_list, 'goods_id');
            $goods_list = M('Goods')->where("goods_id in (".  implode(',', $goods_id_arr).")")->getField("goods_id,goods_name");
        }
        $this->assign('goods_list',$goods_list);
        $this->assign('comment_list',$comment_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    
    public function ask_list(){
    	$this->display();
    }
    
    public function ajax_ask_list(){
    	$model = M('goods_consult');
    	$username = I('nickname','','trim');
    	$content = I('content','','trim');
    	$where=' parent_id = 0';
    	if($username){
    		$where .= " AND username='$username'";
    	}
    	if($content){
    		$where .= " AND content like '%{$content}%'";
    	}
        $count = $model->where($where)->count();        
        $Page  = new AjaxPage($count,16);
        $show = $Page->show();            	
    	
        $comment_list = $model->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select(); 
    	if(!empty($comment_list))
    	{
    		$goods_id_arr = get_arr_column($comment_list, 'goods_id');
    		$goods_list = M('Goods')->where("goods_id in (".  implode(',', $goods_id_arr).")")->getField("goods_id,goods_name");
    	}
    	$consult_type = array(0=>'默认咨询',1=>'商品咨询',2=>'支付咨询',3=>'配送',4=>'售后');
    	$this->assign('consult_type',$consult_type);
    	$this->assign('goods_list',$goods_list);
    	$this->assign('comment_list',$comment_list);
    	$this->assign('page',$show);// 赋值分页输出
    	$this->display();
    }
    
    public function consult_info(){
    	$id = I('get.id');
    	$res = M('goods_consult')->where(array('id'=>$id))->find();
    	if(!$res){
    		exit($this->error('不存在该咨询'));
    	}
    	if(IS_POST){
    		$add['parent_id'] = $id;
    		$add['content'] = I('post.content');
    		$add['goods_id'] = $res['goods_id'];
                $add['consult_type'] = $res['consult_type'];
    		$add['add_time'] = time();    		
    		$add['is_show'] = 1;   	
    		$row =  M('goods_consult')->add($add);
    		if($row){
    			$this->success('添加成功');
    		}else{
    			$this->error('添加失败');
    		}
    		exit;    	
    	}
    	$reply = M('goods_consult')->where(array('parent_id'=>$id))->select(); // 咨询回复列表   	 
    	$this->assign('comment',$res);
    	$this->assign('reply',$reply);
    	$this->display();
    }
    public function ask_handle(){
    	$type = I('post.type');
    	$selected_id = I('post.selected');        
    	if(!in_array($type,array('del','show','hide')) || !$selected_id)
    		$this->error('操作完成');
    
        $selected_id = implode(',',$selected_id);
    	if($type == 'del'){
    		//删除咨询
    		$where .= "( id IN ({$selected_id}) OR parent_id IN ({$selected_id})) ";
    		$row = M('goods_consult')->where($where)->delete();
    	}
    	if($type == 'show'){
    		$row = M('goods_consult')->where("id IN ({$selected_id})")->save(array('is_show'=>1));
    	}
    	if($type == 'hide'){
    		$row = M('goods_consult')->where("id IN ({$selected_id})")->save(array('is_show'=>0));
    	}    		
    	$this->success('操作完成');
    }
    public function viewEditAtv(){
        $act = I('GET.act','add');
        $info = array();
        $info['publish_time'] = time()+3600*24;
        if(I('GET.activity_id')){
           $activity_id = I('GET.activity_id');
           $info = D('activity')->where('activity_id='.$activity_id)->find();
           $info['start_day']=$info['start_day']?date('Y-m-d',$info['start_day']):'';
           $info['deadline']=$info['deadline']?date('Y-m-d',$info['deadline']):'';
           $act="edit";
        }
        $maps['activity_id'] = I('GET.activity_id');
        $infos = D('sign_up')->where($maps)->select();
        foreach ($infos as $key => $value) {
            $map['user_id'] = $value['uid'];
            $r = D('users')->where($map)->find();
            $infos[$key]['nickname'] = $r['nickname'];
            $infos[$key]['mobile'] = $r['mobile'];
        }
        $this->assign('infos',$infos);   
        $this->assign('act',$act);
        $this->assign('info',$info);
        $this->initEditor();
        $this->display();
    }


    /**
     * 删除操作
     * @param $id
     */
    public function order_action(){     
        $id = I('get.id');//删除数据的ID
        $table_name = I('get.table_name');//删除数据的表名
        $del_reason = I('get.note');//删除原因

        //判断删除的数据是否存在
        $maps['id'] = $id;
        $info = M($table_name)->where($maps)->find();
        if ($info) {
            $data['is_del'] = 1;
            $data['del_reason'] = $del_reason;
            $result = M($table_name)->where($maps)->save($data);
            if ($result) {
                //代发列表
                if ($table_name=='send_list') {
                   adminLog('删除订单编号为'.$info['sn'].'的代发数据');//操作日志 
                }else{
                   adminLog('删除订单编号为'.$info['sn'].'的寄存数据');//操作日志  
                }
                echo 1;exit;
            }else{
                echo 0;exit;
            }

        }else{
            exit(json_encode(array('status' => 0,'msg' => '操作失败')));
        }
    }

    /**
     * 删除操作
     * @param $id
     */
    public function order_action1(){     
        $id = I('get.id');//删除数据的ID
        $table_name = I('get.table_name');//删除数据的表名
        $del_reason = I('get.note');//删除原因

        //判断删除的数据是否存在
        $maps['id'] = $id;
        $info = M($table_name)->where($maps)->find();
        if ($info) {
            $data['is_del'] = 1;
            $data['del_reason'] = $del_reason;
            $result = M($table_name)->where($maps)->save($data);
            if ($result) {
                //代发列表
                if ($table_name=='send_list') {
                   adminLog('删除订单编号为'.$info['sn'].'的代发数据');//操作日志 
                }else{
                   adminLog('删除订单编号为'.$info['sn'].'的寄存数据');//操作日志  
                }
                echo 1;exit;
            }else{
                echo 0;exit;
            }

        }else{
            exit(json_encode(array('status' => 0,'msg' => '操作失败')));
        }
    }

    //寄存修改
    public function update_deposit_list(){
        $id = I('id');
        $info = M('deposit_list')->where('id='.$id)->find();
        if (IS_POST) {
            $data['id'] = I('id');
            $data['box'] = I('box');
            $data['count'] = I('count');
            $data['over_box'] = I('over_box');
            $data['over_count'] = I('over_count');
             //搜索条件  提领状态
            if(!empty(I('status'))){
                if (urldecode(I('status'))=='未提领') {
                    $data['status'] = 0;
                }else if (urldecode(I('status'))=='已提领') {
                    $data['status'] = 1;
                }else{
                    $data['status'] = 2;
                }
            }
            $re = M('deposit_list')->save($data);
            if ($re) {
                adminLog('修改订单编号为'.$info['sn'].'的寄存数据');//操作日志 
                $this->success('修改成功');
                exit;
            }else{
                $this->error('修改失败');
                exit;
            }
        }
        $this->assign('info',$info);
        $this->display();
    }

    //代发修改
    public function update_send_list(){
        $id = I('id');
        $info = M('send_list')->where('id='.$id)->find();
        if (IS_POST) {
            $data['id'] = I('id');
            $data['count'] = I('count');
            $data['requirement'] = I('requirement');
            $data['special'] = I('special');
            $data['stage'] = I('stage');
            $data['status'] = I('status');
            
            $re = M('send_list')->save($data);
            if ($re) {
                adminLog('修改订单编号为'.$info['sn'].'的代发数据');//操作日志 
                $this->success('修改成功');
                exit;
            }else{
                $this->error('修改失败');
                exit;
            }
        }
        $this->assign('info',$info);
        $this->display();
    }

}