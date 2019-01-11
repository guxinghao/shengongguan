<?php
/**
 *  Author: eric yang     
 * Date: 2017-05-15
 */
namespace Admin\Controller;
use Think\AjaxPage;
use Think\Page;
use Admin\Logic\UsersLogic;

class UserController extends BaseController {
    public function index(){
        $levels=M('user_level')->where('status=1')->select();

        //权限管理  客服只看选定门店
        if (session('role_id')==12) {
            $store_maps['store_id'] = array('in',session('store_id'));
        }

        $info = D('store')->where($store_maps)->select();
        $maos['role_id'] = array('in',array('4','5'));

        $role_id = session('role_id');
        if ($role_id==4) {
            $maos['store_id'] = session('store_id');
        }

        //权限管理  客服只看选定门店
        if ($role_id==12) {
            $maos['store_id'] = array('in',session('store_id'));
        }
        //商圈
        $maps['status']=1;
        $list=D("trading_area")->where($maps)->select();
        $this->assign('list',$list);

        $now_time = date('Y-m-d',time());
        $this->assign('now_time',$now_time);
        $guwen = M('admin')->where($maos)->field('user_name,admin_id')->select();
        $this->assign('role_id',$role_id);//角色ID
        $this->assign('guwen',$guwen);
        $this->assign('info',$info);
        $this->assign('levels',$levels);
        $this->display();
    }
    /**
     * 会员列表
     */
    public function ajaxindex(){
        // 搜索条件
        $condition = array();
        $userIdArr = array();
        $flag = true;
        $condition['is_del'] = 0;
        I('mobile') ? $condition['mobile|nickname'] = array('like','%'.I('mobile').'%') : false;
        I('email') ? $condition['email'] = I('email') : false;  
        I('level') ? $condition['level'] = I('level') : false;
        I('store_id') ? $condition['store_id'] = I('store_id') : false;
        // I('store_id') ? $search_add['store_id'] = I('store_id') : false;//新增会员条件
        I('guwen_id') ? $condition['first_leader'] = I('guwen_id') : false;
        // I('guwen_id') ? $search_add['first_leader'] = I('guwen_id') : false;//新增会员条件
        //备注
        I('remark') ? $condition['remark'] = array('like','%'.I('remark').'%') : false;


        //权限管理  店长只看门店
        if (session('role_id')==4) {
            $condition['store_id'] = session('store_id');
            // $search_add['store_id'] = session('store_id');//新增会员条件
        }

        //权限管理  服务顾问只看服务顾问
        if (session('role_id')==5) {
            $condition['first_leader'] = session('admin_id');
            // $search_add['first_leader'] = session('admin_id');//新增会员条件
        }


        //权限管理  客服只看选定门店
        if (!I('store_id') && (session('role_id')==12)) {
            $condition['store_id'] = array('in',session('store_id'));
        }
        

        //风格
        if (I('fengge') == 999) {
            
        }elseif (I('fengge') == 888) {
            $condition['fengge_id'] = array('in',array('0','1'));
            $fengge_count_where['fengge_id'] = array('in',array('0','1')); //风格人数条件
        }else{
            $condition['fengge_id'] = I('fengge');
            $fengge_count_where['fengge_id'] = I('fengge'); //风格人数条件
        }

        //积分
        $min_integral = I('min_integral');//小积分
        $max_integral = I('max_integral');//大积分
        if($max_integral>0&&$min_integral>0){  
            $condition['pay_points']=array(array('egt',$min_integral),array('elt',$max_integral));     
        }elseif($min_integral>0){
            $condition['pay_points']=array('gt',$min_integral);
        }elseif($max_integral>0){
            $condition['pay_points']=array('lt',$max_integral);
        }else{
        }

        //本年购买金额

        if (!empty(I('year_amount'))) {
            $year_amount = sprintf("%.2f", I('year_amount'));//本年购买金额

            //查询订单列表满足条件的数据
            $this_year = date('Y',time());
            $this_year = $this_year.'-01-01';
            $this_year_stp = strtotime($this_year);

            $order_map['pay_status']=1;
            $order_map['order_status']=array('not in','3,5');
            
            $order_map['add_time'] = array('gt',$this_year_stp);
            $order_map['user_id'] = array('neq',0);
            $model = M();
            $order_sql = M('order')->field('sum(total_amount) as a,user_id')->where($order_map)->group('user_id')->buildSql();
            $newTable['ss.a'] = array('eq',$year_amount);
            $user_id_order = $model->table($order_sql.' ss')->field('user_id')->where($newTable)->select();
            //二维数组转为一维数组
            $user_idArr = array();
            foreach ($user_id_order as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }

            // //添加条件
            if (!$user_id_order) {
                $condition['user_id'] = 0;
                $flag = false;
            }

        }else if (I('year_amount')==='0') {
            $year_amount = sprintf("%.2f", I('year_amount'));//本年购买金额
            $condition['total_amount'] = $year_amount;
        }
        //来源搜索
        if (!empty(I('source'))) {
            $condition['source'] = array('like','%'.I('source').'%');
        }
        
        //add_uid 开发人搜索
        if (!empty(I('add_uid'))) {
            $condition['add_uid'] = I('add_uid');
            // $search_add['add_uid'] = I('add_uid');//新增会员条件
        }

        // 注册区间
        $start_addtime = I('start_addtime');//高级搜索条件  开始时间
        $end_addtime = I('end_addtime');//高级搜索条件  结束时间
        //如果只有开始时间 则结束时间为当前
        if (!empty($start_addtime) && empty($end_addtime)) {
            $start_addtime = strtotime($start_addtime);
            $condition['reg_time'] = array('EGT',$start_addtime); 
        }else if (empty($start_addtime) && !empty($end_addtime)) {
            $tlend_day = $end_addtime.' 23:59:59';
            $condition['reg_time'] = array('ELT',strtotime($tlend_day));
        }else if(!empty($start_addtime) && !empty($end_addtime)){
            $start_addtime = strtotime($start_addtime);
            $tlend_day = $end_addtime.' 23:59:59';
            $condition['reg_time']=array(array('EGT',$start_addtime),array('ELT',strtotime($tlend_day))); 
        }

        //购买用途
        if (!empty(I('purpose'))) {
            $condition['purpose'] = array('like','%'.I('purpose').'%');
        }

        //食用人群
        if (!empty(I('shiyongrenqun'))) {
            $condition['shiyongrenqun'] = array('like','%'.I('shiyongrenqun').'%');
        }

        //偏好品类
        if (!empty(I('preferred_products'))) {
            $condition['preferred_products'] = array('like','%'.I('preferred_products').'%');
        }

        //累计购买金额
        $start_point_a = I('start_point_a');//高级搜索条件  开始时间
        $end_point_b = I('end_point_b');//高级搜索条件  结束时间
        //如果只有开始时间 则结束时间为当前
        if (!empty($start_point_a) && empty($end_point_b)) {
            $condition['total_amount'] = array('EGT',$start_point_a); 
        }else if (empty($start_point_a) && !empty($end_point_b)) {
            $condition['total_amount'] = array('ELT',$end_point_b);
        }else if(!empty($start_point_a) && !empty($end_point_b)){
            $condition['total_amount'] = array(array('EGT',$start_point_a),array('ELT',$end_point_b)); 
        }


        //单次购买金额 区间
        $start_point = I('start_point');//高级搜索条件  单次购买金额
        $end_point = I('end_point');//高级搜索条件  单次购买金额
        //如果只有开始 则结束时间为当前
        if (!empty($start_point) && empty($end_point)) {
            //查询订单列表满足条件的数据
            $order_map1['pay_status']=1;
            $order_map1['order_status']=array('not in','3,5');
            $order_map1['user_id'] = array('neq',0);
            $order_map1['total_amount'] = array('EGT',$start_point);
            $model = M();
            $user_id_order1 = M('order')->field('user_id')->where($order_map1)->select();
            //二维数组转为一维数组
            foreach ($user_id_order1 as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }
            $userIdArr = array_unique($userIdArr);
            // //添加条件
            if (!$user_id_order1) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }else if (empty($start_point) && !empty($end_point)) {
            $order_map1['pay_status']=1;
            $order_map1['order_status']=array('not in','3,5');
            $order_map1['user_id'] = array('neq',0);
            $order_map1['total_amount'] = array('ELT',$end_point);
            $model = M();
            $user_id_order1 = M('order')->field('user_id')->where($order_map1)->select();
            //二维数组转为一维数组
            foreach ($user_id_order1 as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }
            $userIdArr = array_unique($userIdArr);
            // //添加条件
            if (!$user_id_order1) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }else if(!empty($start_point) && !empty($end_point)){
            $order_map1['pay_status']=1;
            $order_map1['order_status']=array('not in','3,5');
            $order_map1['user_id'] = array('neq',0);
            $order_map1['total_amount'] = array(array('EGT',$start_point),array('ELT',$end_point));
            $model = M();
            $user_id_order1 = M('order')->field('user_id')->where($order_map1)->select();
            //二维数组转为一维数组
            foreach ($user_id_order1 as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }
            $userIdArr = array_unique($userIdArr);
            // //添加条件
            if (!$user_id_order1) {
                $condition['user_id'] = 0;
                $flag = false;
            }
            // $condition['total_amount'] = array(array('EGT',$start_point),array('ELT',$end_point)); 
        }


        //首单购买金额 区间
        $first_start_point = I('first_start_point');//高级搜索条件  首单购买金额
        $first_end_point = I('first_end_point');//高级搜索条件  首单购买金额
        //如果只有开始 则结束时间为当前
        if (!empty($first_start_point) && empty($first_end_point)) {
            //查询订单列表满足条件的数据
            $first_order['pay_status']=1;
            $first_order['is_first']=1;
            $first_order['order_status']=array('not in','3,5');
            $first_order['user_id'] = array('neq',0);
            $first_order['total_amount'] = array('EGT',$first_start_point);
            $model = M();
            $user_id_order1 = M('order')->field('user_id')->where($first_order)->select();
            //二维数组转为一维数组
            foreach ($user_id_order1 as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }
            $userIdArr = array_unique($userIdArr);
            // //添加条件
            if (!$user_id_order1) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }else if (empty($first_start_point) && !empty($first_end_point)) {
            $first_order['pay_status']=1;
            $first_order['order_status']=array('not in','3,5');
            $first_order['user_id'] = array('neq',0);
            $first_order['is_first']=1;
            $first_order['total_amount'] = array('ELT',$first_end_point);
            $model = M();
            $user_id_order1 = M('order')->field('user_id')->where($first_order)->select();
            //二维数组转为一维数组
            foreach ($user_id_order1 as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }
            $userIdArr = array_unique($userIdArr);
            // //添加条件
            if (!$user_id_order1) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }else if(!empty($first_start_point) && !empty($first_end_point)){
            $first_order['pay_status']=1;
            $first_order['order_status']=array('not in','3,5');
            $first_order['user_id'] = array('neq',0);
            $first_order['total_amount'] = array(array('EGT',$first_start_point),array('ELT',$first_end_point));
            $model = M();
            $user_id_order1 = M('order')->field('user_id')->where($first_order)->select();
            //二维数组转为一维数组
            foreach ($user_id_order1 as $val) {
                $user_idArr[] = $val['user_id'];
                $userIdArr[] = $val['user_id'];
            }
            $userIdArr = array_unique($userIdArr);
            // //添加条件
            if (!$user_id_order1) {
                $condition['user_id'] = 0;
                $flag = false;
            }
            
        }

        //偏好品类
        if (!empty(I('eating_habits'))) {
            $condition['eating_habits'] = array('like','%'.I('eating_habits').'%');
        }

        //发制方式
        if (!empty(I('fazhifangshi'))) {
            $condition['fazhifangshi'] = array('like','%'.I('fazhifangshi').'%');
        }

        //累计购买次数 区间
        $start_buy_count = I('start_buy_count');//高级搜索条件  单次购买金额
        $end_buy_count = I('end_buy_count');//高级搜索条件  单次购买金额
        //如果只有开始 则结束时间为最大
        if (!empty($start_buy_count) && empty($end_buy_count)) {
            $model = M();
            $map121['user_id'] = array('gt',0);
            $map121['pay_status'] = 1;
            $map121['order_status']=array('not in','3,5');
            $_sql = M('order')->field('count(*) as cc, user_id')->where($map121)->group('user_id')->buildSql();

            $newTable1['newTable.cc'] = array('EGT',$start_buy_count); 
            
            $count_num = $model->table($_sql.' newTable')->field('user_id')->where($newTable1)->select();
            //二维数组转为一维数组
            $buyuserid = array();
            foreach ($count_num as $val1) {
                $buyuserid[] = $val1['user_id'];
                $userIdArr[] = $val1['user_id'];
            }

            //添加条件
            if (!$count_num) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        //开始为空 结束不为空 
        }else if (empty($start_buy_count) && !empty($end_buy_count)) {
            $model = M();
            $map121['user_id'] = array('gt',0);
            $map121['pay_status'] = 1;
            $map121['order_status']=array('not in','3,5');
            $_sql = M('order')->field('count(*) as cc, user_id')->where($map121)->group('user_id')->buildSql();

            $newTable1['newTable.cc'] = array('ELT',$end_buy_count); 
            
            $count_num = $model->table($_sql.' newTable')->field('user_id')->where($newTable1)->select();
            // echo M()->getLastSql();
            //如果查询数量为0 则获取没有联系的人员ID
            if ($start_buy_count==='0') {
                $newTable13['newTable.cc'] = array('GT',0); 
                $count_num1 = $model->table($_sql.' newTable')->field('user_id')->where($newTable13)->select();
                // echo M()->getLastSql();
                // var_dump($count_num1);die;
                $newUserArr = array();
                foreach ($count_num1 as $val12) {
                    $newUserArr[] = $val12['user_id'];
                }
                //获取没有联系次数的人员ID
                $getUserID['user_id'] = array('not in', $newUserArr);
                $searchArrr = M('users')->field('user_id')->where($getUserID)->select();
            }
            //二维数组转为一维数组
            $buyuserid = array();
            foreach ($count_num as $val1) {
                $buyuserid[] = $val1['user_id'];
                $userIdArr[] = $val1['user_id'];
            }
            if ($searchArrr) {
                foreach ($searchArrr as $val12) {
                    $userIdArr[] = $val12['user_id'];
                }
            }

            //添加条件
            if (!$count_num) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }else if(!empty($start_buy_count) && !empty($end_buy_count)){
            $model = M();
            $map121['user_id'] = array('gt',0);
            $map121['pay_status'] = 1;
            $map121['order_status']=array('not in','3,5');
            $_sql = M('order')->field('count(*) as cc, user_id')->where($map121)->group('user_id')->buildSql();

            $newTable1['newTable.cc'] = array(array('EGT',$start_buy_count),array('ELT',$end_buy_count)); 
            
            $count_num = $model->table($_sql.' newTable')->field('user_id')->where($newTable1)->select();
            
            //二维数组转为一维数组
            $buyuserid = array();
            foreach ($count_num as $val1) {
                $buyuserid[] = $val1['user_id'];
                $userIdArr[] = $val1['user_id'];
            }

            //添加条件
            if (!$count_num) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }


        //生日搜索
        $birthdays_start=I('birthdaystart');//生日搜索条件
        $birthdays_end=I('birthdayend');//生日搜索条件
        $birthdays_start_stp = strtotime($birthdays_start);//时间戳
        $birthdays_end_stp = strtotime($birthdays_end);//时间戳
        $new_birthdays_start = date('m-d',$birthdays_start_stp);
        $new_birthdays_end = date('m-d',$birthdays_end_stp);
        if(!empty($birthdays_start) && !empty($birthdays_end)){
            $condition['_string'] = "unix_timestamp(concat('1970-',SUBSTRING_INDEX(birthdays,'-',-2))) between unix_timestamp('1970-".$new_birthdays_start."') and unix_timestamp('1970-".$new_birthdays_end."')";
        }

        //累计联系次数
        $communication_count = I('communication');
        if ($communication_count==='0' || !empty($communication_count)) {
            $model = M();
            $map1211['uid'] = array('gt',0);
            $_sql1 = M('communication')->field('count(*) as cc, uid')->where($map1211)->group('uid')->buildSql();
            if ($communication_count==='0') {
                $newTable11['newTable.cc'] = array('gt',$communication_count);
            }else{
                $newTable11['newTable.cc'] = array('eq',$communication_count);
            }
            $count_num = $model->table($_sql1.' newTable')->field('uid')->where($newTable11)->select();
            //如果查询数量为0 则获取没有联系的人员ID
            if ($communication_count==='0') {
                $userIdAll = array();
                foreach ($count_num as $val12) {
                    $userIdAll[] = $val12['uid'];
                }
                //获取没有联系次数的人员ID
                $getUserID['user_id'] = array('not in', $userIdAll);
                $count_num = M('users')->field('user_id uid')->where($getUserID)->select();
            }
            //二维数组转为一维数组
            $comuserid = array();
            foreach ($count_num as $val1) {
                $comuserid[] = $val1['uid'];
                $userIdArr[] = $val1['uid'];
            }
            // //添加条件
            if (!$comuserid) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }


        //推荐参友人数
        $recommend_count = I('recommend_count');
        if ($recommend_count==='0' || !empty($recommend_count)) {
            $model = M();
            $mp['referrals_id'] = array('gt',0);
            $newsql = M('users')->field('count(*) as cc, referrals_id')->where($mp)->group('referrals_id')->buildSql();
            if ($recommend_count==='0') {
                $createTable['newTable.cc'] = array('gt',$recommend_count);
            }else{
                $createTable['newTable.cc'] = array('eq',$recommend_count);
            }
            $count_num = $model->table($newsql.' newTable')->field('referrals_id')->where($createTable)->select();
            //如果查询数量为0 则获取没有联系的人员ID
            if ($recommend_count==='0') {
                $userIdAll = array();
                foreach ($count_num as $val12) {
                    $userIdAll[] = $val12['referrals_id'];
                }
                //获取没有联系次数的人员ID
                $getUserID['user_id'] = array('not in', $userIdAll);
                $count_num = M('users')->field('user_id referrals_id')->where($getUserID)->select();
            }
            //二维数组转为一维数组
            $recommenduserid = array();
            foreach ($count_num as $val1) {
                $recommenduserid[] = $val1['referrals_id'];
                $userIdArr[] = $val1['referrals_id'];
            }
            // 添加条件
            if (!$recommenduserid) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }

        //最后一次购买时间搜索
        if (I('lastbuytime')) {
            $lastbuytimestp_st = strtotime(I('lastbuytime'));
            $gettime = I('lastbuytime').' 23:59:59';
            $lastbuytimestp_ed = strtotime($gettime);
            $condition['end_time']=array(array('EGT',$lastbuytimestp_st),array('ELT',$lastbuytimestp_ed)); 
        }

        //常购产品搜索
        if (I('changgouchanpin_name')) {
            //通过user_id分组 获取最后一次购买的订单ID
            $mapsop['user_id'] = array('gt', 0);
            $new_sql = M('order')->order('order_id desc')->buildSql();
            $model = M();
            $info = $model->table($new_sql.' a')->field('order_id')->where($mapsop)->group('user_id')->order('order_id desc')->select(); 
            //二维数组转为一维数组
            $order_id=formatArray($info,'order_id');
            //通过商品名称获取order_id
            $getGood['goods_name'] = array('like', '%'.I('changgouchanpin_name').'%');
            $getGood['goods_price'] = array('gt',0);
            $getGood['order_id'] = array('in',$order_id);
            $info1 = D('order_goods')->where($getGood)->field('order_id')->order('rec_id desc')->select();
            $order_id1 = formatArray($info1,'order_id');
            $getGood1['order_id'] = array('in',$order_id1);
            //获取满足条件的数据 user_id
            $result = M('order')->where($getGood1)->field('user_id')->select();
            //二维数组转为一维数组
            $orderIdArray = array();
            foreach ($result as $thisval) {
                $orderIdArray[] = $thisval['user_id'];
                $userIdArr[] = $thisval['user_id'];
            }
            // 添加条件
            if (!$orderIdArray) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }

        //是否关注公众号
        if (I('focus_gzh')) {
            //查询关注公众号的人
            if (I('focus_gzh')==1) {
                $condition['head_pic'] = array('exp','is not null');
            }else{
                $condition['head_pic'] = array('exp','is null');
            }
        }

        //是否添加微信
        if (I('add_wechat')) {
            //查询关注公众号的人
            if (I('add_wechat')==1) {
                $condition['openid1'] = array('neq','');
            }else{
                $condition['openid1'] = '';
            }
        }

        //商圈搜索
        if (I('trading_area')) {
            $poe['trading_area'] = I('trading_area');
            $re = M('user_address')->field('user_id')->where($poe)->select();
            //二维数组转为一维数组
            $trading_area = array();
            foreach ($re as $thisval1) {
                $trading_area[] = $thisval1['user_id'];
                $userIdArr[] = $thisval1['user_id'];
            }
            // 添加条件
            if (!$trading_area) {
                $condition['user_id'] = 0;
                $flag = false;
            }
        }


        $sort_order = I('order_by','user_id').' '.I('sort','desc');               
        if ($userIdArr && $flag) {

            $unique_arr = array_unique ($userIdArr); 
            $repeat_arr = array_diff_assoc ($userIdArr, $unique_arr); 

            if ($repeat_arr) {
                $condition['user_id'] = array('in',$repeat_arr);
            }else{
                $condition['user_id'] = array('in',$unique_arr);
            }

        }else if (!$flag) {
            $condition['user_id'] = 0;
        }
        $model = M('users');
        $count = $model->where($condition)->count();
        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $Page  = new AjaxPage($count,$pageCount);
            
        $userList = $model->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select(); 
        $user_id_arr = get_arr_column($userList, 'user_id');
        if(!empty($user_id_arr))
        {
            $first_leader = M('users')->query("select first_leader,count(1) as count  from __PREFIX__users where first_leader in(".  implode(',', $user_id_arr).")  group by first_leader");
            $first_leader = convert_arr_key($first_leader,'first_leader');           
            $second_leader = M('users')->query("select second_leader,count(1) as count  from __PREFIX__users where second_leader in(".  implode(',', $user_id_arr).")  group by second_leader");
            $second_leader = convert_arr_key($second_leader,'second_leader');            
            
            $third_leader = M('users')->query("select third_leader,count(1) as count  from __PREFIX__users where third_leader in(".  implode(',', $user_id_arr).")  group by third_leader");
            $third_leader = convert_arr_key($third_leader,'third_leader');            
        }
        foreach ($userList as $key => $value) {

            $r['store_id'] = $value['store_id'];
            $info = D('store')->where($r)->find();
            $userList[$key]['store_name'] = $info['store_name'];
            if($value['first_leader']>0){
                $guwen=getAdminInfo($value['first_leader']);
                $userList[$key]['consultant']=$guwen['name'];//顾问
            }else{
                $userList[$key]['consultant']='';//顾问
            }
            if($value['add_uid']>0){
                $kaifa=getAdminInfo($value['add_uid']);
                $userList[$key]['kaifa']=$kaifa['name'];//开发
            }else{
                $userList[$key]['kaifa']='';//开发
            }  

            // 查找出现次数最多的产品
            $mmp_list['user_id']=$value['user_id'];
            $mmp_list['pay_status']=1;
            $mmp_list['order_status']=array('not in','3,5');
            $mmp_list['total_amount']=array('gt',0);
            //用户id查询订单
            $model = D('order')->field('order_id')->where($mmp_list)->order('order_id desc')->limit(1)->select();
            $orderID = $model[0]['order_id'];
            if ($orderID) {
                $or['order_id'] = $orderID;
                $or['goods_price'] = array('gt',0);
                $goodsName = M('order_goods')->where($or)->field('rec_id,order_id,goods_name')->select();
                $goodsNameArr = array();
                foreach ($goodsName as $key1 => $value) {
                    $goodsNameArr[] = $value['goods_name'];
                }
                $goodsNameStr = implode(',', $goodsNameArr);
                $userList[$key]['changgouchanpin_name']=$goodsNameStr;//常购产品
            }else{
                $userList[$key]['changgouchanpin_name']='';//常购产品 
            }

            //查找最后一次沟通时间
            $mmp_com['uid']=$value['user_id'];
            $last_time = M('communication')->where($mmp_com)->order('create_time desc')->getField('create_time');
            $userList[$key]['last_time']=$last_time;//最后一次沟通时间 
        
        }

        //统计
        //会员总数
        $total_count = M('users')->where('is_del=0')->count();
        $this->assign('total_count',$total_count);

        //新增会员
        $total_count_where['is_del'] = 0;
        //时间
        if (!$condition['reg_time']) {
            $st = strtotime(date('Y-m-d',time()));
            $ed = $st+86400;
            $total_count_where['reg_time'] = array(array('EGT',$st),array('ELT',$ed));
        }
        $add_count = M('users')->where($condition)->where($total_count_where)->count();
        $this->assign('add_count',$add_count);

        //风格人数
        $fengge_count_where['is_del'] = 0;
        if ($fengge_count_where['fengge_id']) {
            $fengge_count = M('users')->where($fengge_count_where)->count();
        }else{
            $fengge_count = 0;
        }
        $this->assign('fengge_count',$fengge_count);

        //门店会员
        $store_count_where['is_del'] = 0;
        if ($condition['store_id']) {
            $store_count_where['store_id'] = $condition['store_id'];
        }
        $store_count = M('users')->where($store_count_where)->count();
        $this->assign('store_count',$store_count);


        //服务顾问会员
        $guwen_count_where['is_del'] = 0;
        if ($condition['first_leader']) {
            $guwen_count_where['first_leader'] = $condition['first_leader'];
            $guwen_count = M('users')->where($guwen_count_where)->count();
        }else{
            $guwen_count = 0;
        }
        $this->assign('guwen_count',$guwen_count);

        //砖石会员
        $jewel_count_where['is_del'] = 0;
        $jewel_count_where['level'] = 5;
        $jewel_count = M('users')->where($condition)->where($jewel_count_where)->count();
        $this->assign('jewel_count',$jewel_count);

        //白金会员
        $pt_count_where['is_del'] = 0;
        $pt_count_where['level'] = 4;
        $pt_count = M('users')->where($condition)->where($pt_count_where)->count();
        $this->assign('pt_count',$pt_count);

        //金卡会员
        $gold_count_where['is_del'] = 0;
        $gold_count_where['level'] = 3;
        $gold_count = M('users')->where($condition)->where($gold_count_where)->count();
        $this->assign('gold_count',$gold_count);

        //银卡会员
        $silver_count_where['is_del'] = 0;
        $silver_count_where['level'] = 2;
        $silver_count = M('users')->where($condition)->where($silver_count_where)->count();
        $this->assign('silver_count',$silver_count);

        //普通会员
        $normal_count_where['is_del'] = 0;
        $normal_count_where['level'] = 1;
        $normal_count = M('users')->where($condition)->where($normal_count_where)->count();
        $this->assign('normal_count',$normal_count);

        $this->assign('first_leader',$first_leader);
        $this->assign('second_leader',$second_leader);
        $this->assign('third_leader',$third_leader);                                
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('level',M('user_level')->getField('level_id,level_name'));
        $this->assign('levels',$levels);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pageCount',$pageCount);// 赋值分页输出
        $this->display();
    }

    //根据门店获取顾问列表
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






    /**
     * 会员记录
     */
    public function detail_a(){
        $maps['tp_order.user_id'] = I('jilu');
        $maps['tp_order.pay_status'] = 1;
        $maps['tp_order.order_status']=array('not in','3,5');
        $count = M('order')->join('tp_order_goods ON tp_order_goods.order_id = tp_order.order_id','left')->field('tp_order.order_id,tp_order.order_sn,tp_order.goods_price,tp_order.order_amount,tp_order.pay_time,tp_order_goods.goods_id,tp_order_goods.goods_name,tp_order_goods.goods_num,tp_order_goods.goods_price every_price')->where($maps)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show();

        // $info =$model->where($maps)->order("pay_time desc")->limit($Page->firstRow.','.$Page->listRows)->select(); 
        $info = M('order')->join('tp_order_goods ON tp_order_goods.order_id = tp_order.order_id','left')->field('tp_order.order_id,tp_order.order_sn,tp_order.goods_price,tp_order.order_amount,tp_order.pay_time,tp_order_goods.goods_id,tp_order_goods.goods_name,tp_order_goods.goods_num,tp_order_goods.goods_price every_price')->where($maps)->limit("$Page->firstRow,$Page->listRows")->order("pay_time desc")->select();
        // foreach ($info as $key => $value) {
        //     $maps['order_id'] = $value['order_id'];
        //     $r = D('order_goods')->where($maps)->find();
        //     $info[$key]['goods_name'] = $r['goods_name'];
        //     $info[$key]['goods_num'] = $r['goods_num'];
        // }   
    // var_dump($info);die;
        $this->assign('maps',$maps);
        $this->assign('info',$info);

        $this->assign('page',$show);
        $this->display();
    }

    public function detail_b(){
        $mapss['uid'] =  I('style');
        $model = M('fengge_log');
        $count = $model->where($mapss)->count();
        $Page  = new \Think\Page($count,20);         
        $user = D('users')->where($mapss)->find();

        $style = $model->where($mapss)->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($style as $key => $value) {
            $style[$key]['old_fengge']=$this->getfengge($value['old_fengge']);
            $style[$key]['new_fengge']=$this->getfengge($value['new_fengge']);
        }
        $this->assign('mapss',$mapss);
        $this->assign('style',$style);
        $this->assign('user',$user);
        $show = $Page->show();
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function detail_c(){
        $maps['user_id'] = I('points');
        $model = M('account_log');
        $count = $model->where($maps)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show();
        // //  搜索条件下 分页赋值
        // foreach($maps as $key=>$val) {
        //     $Page->parameter[$key]   =   urlencode($val);
        // }
        $infos = $model->where($maps)->order("change_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $user = D('users')->where($maps)->find();
        $this->assign('maps',$maps);
        $this->assign('infos',$infos);
        $this->assign('user',$user);
        $this->assign('page',$show);// 赋值分页输出
        $this->display("detail_c");
    }


    /**
     * 沟通记录
     */
    public function detail_d(){
        $maps['uid'] = I('communication');
        $model = M('communication');
        $count = $model->where($maps)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show();

        $info =$model->where($maps)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();   

        $this->assign('maps',$maps);
        $this->assign('info',$info);

        $this->assign('page',$show);
        $this->display();
    }

    /**
     * 参会记录
     */
    public function detail_e(){
        $posmit['tp_sign_up.uid'] = I('sign_up');
        $posmit['tp_sign_up.status'] = 1;

        $count = M('sign_up')->join('tp_activity on tp_sign_up.activity_id = tp_activity.activity_id')->field('tp_activity.activity_id, tp_activity.title, tp_activity.start_day, tp_activity.start_time, tp_activity.end_time, tp_activity.store_name, tp_sign_up.update_time')->where($posmit)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show(); 

        $sign_up = M('sign_up')->join('tp_activity on tp_sign_up.activity_id = tp_activity.activity_id')->field('tp_activity.activity_id, tp_activity.title, tp_activity.start_day, tp_activity.start_time, tp_activity.end_time, tp_activity.store_name, tp_sign_up.update_time')->where($posmit)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('maps',$posmit);
        $this->assign('info',$sign_up);

        $this->assign('page',$show);
        $this->display();
    }

    /**
     * 上门记录
     */
    public function detail_f(){
        $maps['uid'] = I('visit_list');

        $count =  M('visit_list')->where($maps)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show(); 

        $visit_list = M('visit_list')->where($maps)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('maps',$maps);
        $this->assign('info',$visit_list);

        $this->assign('page',$show);
        $this->display();
    }

    /**
     * 寄存记录
     */
    public function detail_g(){
        $maps['uid'] = I('deposit_list');
        $maps['is_del'] = 0;
        $count =  M('deposit_list')->where($maps)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show(); 

        $deposit_list = M('deposit_list')->where($maps)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('maps',$maps);
        $this->assign('info',$deposit_list);

        $this->assign('page',$show);
        $this->display();
    }


    /**
     * 代发记录
     */
    public function detail_h(){
        $maps['uid'] = I('send_list');
        $count =  M('send_list')->where($maps)->count();

        $Page  = new \Think\Page($count,20);         
        $show = $Page->show(); 

        $send_list = M('send_list')->where($maps)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('maps',$maps);
        $this->assign('info',$send_list);

        $this->assign('page',$show);
        $this->display();
    }

    /**
     * 会员详细信息查看
     */
    public function detail(){
        $uid = I('get.id');
        $user = D('users')->where(array('user_id'=>$uid))->find();
        if(!$user)
            exit($this->error('会员不存在'));
        if(IS_POST){
            //  会员信息编辑
            $password = I('post.password');
            $password2 = I('post.password2');
            if($password != '' && $password != $password2){
                exit($this->error('两次输入密码不同'));
            }
            if($password == '' && $password2 == ''){
                unset($_POST['password']);
            }else{
                $_POST['password'] = encrypts($_POST['password']);
            }

            $row = M('users')->where(array('user_id'=>$uid))->save($_POST);
            if($row)
                exit($this->success('修改成功'));
            exit($this->error('未作内容修改或修改失败'));
        }
        if($user['birthday']==0){
            $user['age']="未填写";
        }else{
            $birthday=date('Y-m-d',$user['birthday']);
            
            $age= getAge($birthday);

            $user['age']=$this->getAgelist($age);

        }

        $birthdays=$user['birthdays'];

        if(!empty($birthdays)){
           // $birthdays=substr($birthdays, -4);
            $user['birthdays']=$birthdays;

        }

        $mapssss['level_id']=$user['level'];
        $user_level=D("user_level")->where($mapssss)->find();

        //dump($user_level);

        if($user_level){
            $user['levels']=$user_level['level_name'];
        }
        if($user['add_uid']>0){
            $kaifa=getAdminInfo($user['add_uid']);
            $user['kaifa']=$kaifa['name'];
        }
        
        if($user['first_leader']>0){
            $kaifa=getAdminInfo($user['first_leader']);
            $user['consultant']=$kaifa['name'];
        }        
        $maps['store_id']=$user['store_id'];
        $store=D("store")->where($maps)->find();
        if($store){
                $user["store"]=$store['store_name'];
           }else{
                $user["store"]="";
           }
        $user['first_lower'] = M('users')->where("first_leader = {$user['user_id']}")->count();
        $user['second_lower'] = M('users')->where("second_leader = {$user['user_id']}")->count();
        $user['third_lower'] = M('users')->where("third_leader = {$user['user_id']}")->count();

        // 查找出现次数最多的产品
        $mmp_changjian['user_id']=$uid;
        //用户id查询订单
        $model = D('order')->field('order_id')->where($mmp_changjian)->order('order_id desc')->limit(1)->select();
        $orderID = $model[0]['order_id'];
        if ($orderID) {
            $order_changgou['order_id'] = $orderID;
            $order_changgou['goods_price'] = array('gt',0);
            $goodsName = M('order_goods')->where($order_changgou)->getField('goods_name');
            $user['changgouchanpin_name']=$goodsName;//常购产品 
        }else{
            $user['changgouchanpin_name']='无';//常购产品 
        }

        $mapss['user_id'] = $uid;
        $mapss['pay_status'] = 1;
        $mapss['order_status']=array('not in','3,5');
        // $info = D('order')->where($mapss)->order("pay_time desc")->limit("10")->select(); 
        $info = M('order')->join('tp_order_goods ON tp_order_goods.order_id = tp_order.order_id','left')->field('tp_order.order_id,tp_order.order_sn,tp_order.goods_price,tp_order.order_amount,tp_order.pay_time,tp_order_goods.goods_id,tp_order_goods.goods_name,tp_order_goods.goods_num,tp_order_goods.goods_price every_price')->where($mapss)->limit('10')->order("pay_time desc")->select();
        $infos = D('account_log')->where($mapss)->order("change_time desc")->limit("10")->select();
        $style = M('fengge_log')->where(array('uid'=>$uid))->limit("10")->select();
        foreach ($style as $key => $value) {
            $style[$key]['old_fengge']=$this->getfengge($value['old_fengge']);
            $style[$key]['new_fengge']=$this->getfengge($value['new_fengge']);
        }

        // 发票抬头
        $fapiao_list = M('bill')->where('user_id='.$uid)->select();
        
        //沟通记录
        $communication = M('communication')->where('uid='.$uid)->order("id desc")->limit("10")->select();

        //参会记录
        $posmit['tp_sign_up.uid'] = $uid;
        $posmit['tp_sign_up.status'] = 1;
        $sign_up = M('sign_up')->join('tp_activity on tp_sign_up.activity_id = tp_activity.activity_id')->field('tp_activity.activity_id, tp_activity.title, tp_activity.start_day, tp_activity.start_time, tp_activity.end_time, tp_activity.store_name, tp_sign_up.update_time')->where($posmit)->order("id desc")->limit("10")->select();

        //上门记录
        $visit_list = M('visit_list')->where('uid='.$uid)->order("id desc")->limit("10")->select();
        //寄存记录
        $deposit_list['uid'] = $uid;
        $deposit_list['is_del'] = 0;
        $deposit_list = M('deposit_list')->where('uid='.$uid)->order("id desc")->limit("10")->select();
        //代发记录
        $send_list = M('send_list')->where('uid='.$uid)->order("id desc")->limit("10")->select();
        $this->assign('send_list',$send_list);//代发
        $this->assign('deposit_list',$deposit_list);//寄存
        $this->assign('visit_list',$visit_list);//上门
        $this->assign('fapiao_list',$fapiao_list);//发票
        $this->assign('communication',$communication);//沟通
        $this->assign('sign_up',$sign_up);//参会
        $this->assign('style',$style);
        $this->assign('infos',$infos);
        $this->assign('info',$info);
        $this->assign('user',$user);
        $this->display();
    }
    public function getfengge($id){
            switch ($id)
    {
     case 1:
     return "初始";
     break;
     case 2:
     return "潜在";
     break;
     case 3:
     return "新手";
     case 4:
     return "活跃";
     break;
     case 5:
     return "忠诚";
     break;
     case 6:
     return "至尊";
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
     public function getAgelist($age){
        if($age<5){
            $name="0-5岁";
        }elseif ($age<10&&$age>=5) {
           $name="5-10岁";
        }elseif ($age<15&&$age>=10) {
           $name="10-15岁";
        }elseif ($age<20&&$age>=15) {
           $name="15-20岁";
        }elseif ($age<25&&$age>=20) {
           $name="20-25岁";
        }elseif ($age<30&&$age>=25) {
           $name="25-30岁";
        }elseif ($age<35&&$age>=30) {
           $name="30-35岁";
        }elseif ($age<40&&$age>=35) {
           $name="35-40岁";
        }elseif ($age<45&&$age>=40) {
           $name="40-45岁";
        }elseif ($age<50&&$age>=45) {
           $name="45-50岁";
        }elseif ($age<55&&$age>=50) {
           $name="50-55岁";
        }elseif ($age<60&&$age>=55) {
           $name="55-60岁";
        }elseif ($age<65&&$age>=60) {
           $name="60-65岁";
        }elseif ($age<70&&$age>=65) {
           $name="65-70岁";
        }elseif ($age<75&&$age>=70) {
           $name="70-75岁";
        }elseif ($age<80&&$age>=75) {
           $name="75-80岁";
        }elseif ($age<85&&$age>=80) {
           $name="80-85岁";
        }elseif ($age<90&&$age>=85) {
           $name="85-90岁";
        }elseif ($age<95&&$age>=90) {
           $name="90-95岁";
        }elseif ($age<100&&$age>=95) {
           $name="95-100岁";
        }elseif ($age<105&&$age>=100) {
           $name="100-105岁";
        }elseif ($age<110&&$age>=105) {
           $name="105-110岁";
        }elseif ($age<115&&$age>=110) {
           $name="110-115岁";
        }elseif ($age<120&&$age>=115) {
           $name="115-120岁";
        }
        return $name;
    }
    
    public function add_user(){
    	if(IS_POST){
    		$data = I('post.');
			$user_obj = new UsersLogic();
			$res = $user_obj->addUser($data);
			if($res['status'] == 1){
				$this->success('添加成功',U('User/index'));exit;
			}else{
				$this->error('添加失败,'.$res['msg'],U('User/index'));
			}
    	}
    	$this->display();
    }

    /**
     * 用户收货地址查看
     */
    public function address(){
        $uid = I('get.id');
        $lists = D('user_address')->where(array('user_id'=>$uid))->select();
        $regionList = M('Region')->getField('id,name');
        $this->assign('regionList',$regionList);
        $this->assign('lists',$lists);
        $this->display();
    }

    /**
     * 删除会员
     */
    public function delete(){
        $uid = I('get.id');
        $maps['is_del'] = 1;
        $row = M('users')->where(array('user_id'=>$uid))->save($maps);
        if($row){
            $this->success('成功删除会员');
        }else{
            $this->error('操作失败');
        }
    }
    //会员消费管理
    public function consumption(){

        $condition = array();
        $model = M('consumption');
        $count = $model->where($condition)->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page  = new \Think\Page($count,$pageCount);
        $show = $Page->show();

        $consumption = $model->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)
              ->select();

        foreach($consumption as $key=>$val){
            if($val['is_cate']==2){
                $mapss['id']=$val['coupon_id'];
                $coupon=D("coupon")->where($mapss)->find();
                $consumption[$key]['co_name']=$coupon['name'];
            }

            $usmap['level_id']=array('in',$val['user_level']);
            $user_level=D("user_level")->field('level_name')->where($usmap)->select();
           // echo M()->getlastsql();
           // dump($user_level);
            $memberlevel=formatArray($user_level,'level_name');
            $consumption[$key]['memberlevel']=$memberlevel;
        }

        $this->assign('list',$consumption);
        $this->assign('page',$show);// 赋值分页输出

        $this->display();
    }

    //
    public function addconsumption(){
        if(IS_POST){
            $data = I('post.');

            $data['user_level']=implode(",",$data['user_level']);
            $data['status']=1;
            if($data['act'] == 'edit')
            {
                $data['update_time']=time();
                $d = D('consumption')->save($data);

            }elseif($data['act'] == 'add'){
                $data['create_time']=time();
                $d = D('consumption')->add($data);
            }
            if($d){
                exit($this->success('操作成功',U("User/consumption")));
            }else{
                exit($this->success('操作失败！'));
            }
            exit;
        }

        $act = I('GET.act','add');
        $this->assign('act',$act);

        $user_level=D("user_level")->select();

        $this->assign('user_level',$user_level);

        $lists = M('coupon')->order('add_time desc')->select();

        $this->assign('lists',$lists);

        $mapsss['id']=I('id');
        $info=D("consumption")->where( $mapsss)->find();
        if($act=='add'){
            $info['is_cate']=1;
        }
        $this->assign('info',$info);

        $this->display();
    }
    //获取等级用户数
    public function getUserCount(){
        $level_id=I('id');

        $maps['level']=$level_id;
        $maps['user_type']=0;
        $count=D("User")->where($maps)->count();
        return $count;
    }
    /**
     * 账户资金记录
     */
    public function account_log(){
        
        // 当前登录用户ID
        $now_uid = session('admin_id');
        //获取当前用户的权限ID
        $role_id = M('admin')->where('admin_id='.$now_uid)->getField('role_id');


        $uid = I('get.id');
        $user = D('users')->where(array('user_id'=>$uid))->find();

        //常购产品
        $mmp_list['user_id']=$uid;
        $mmp_list['pay_status']=1;
        $mmp_list['order_status']=array('not in','3,5');
        $mmp_list['total_amount']=array('gt',0);
        //用户id查询订单
        $model = D('order')->field('order_id')->where($mmp_list)->order('order_id desc')->limit(1)->select();
        $orderID = $model[0]['order_id'];
        if ($orderID) {
            $or['order_id'] = $orderID;
            $or['goods_price'] = array('gt',0);
            $goodsName = M('order_goods')->where($or)->field('rec_id,order_id,goods_name')->select();
            $goodsNameArr = array();
            foreach ($goodsName as $key1 => $value) {
                $goodsNameArr[] = $value['goods_name'];
            }
            $goodsNameStr = implode(',', $goodsNameArr);
            $changgouchanpin_name=$goodsNameStr;//常购产品
        }else{
            $changgouchanpin_name='';//常购产品 
        }

        if(!$user)
            exit($this->error('会员不存在'));
        if(IS_POST){
            if ($role_id==1) {
                $shiyongrenqun=implode(',', $_POST['shiyongrenqun']);
                $preferred_products=implode(',', $_POST['preferred_products']);
                $purpose=implode(',', $_POST['purpose']);
                $data['fazhifangshi']=$_POST['fazhifangshi'];
                $data['source']=$_POST['source'];
                $data['invoice']=$_POST['invoice'];
                $data['style']=$_POST['style'];
                $data['remark']=$_POST['remark'];
                $data['shiyongrenqun']=$shiyongrenqun;
                $data['preferred_products']=$preferred_products;
                $data['purpose']=$purpose;
                $data['level'] = $_POST['levels'];
                $data['pay_points'] = $_POST['pay_points'];
                //服务顾问修改
                $fuwu_guwen = $_POST['fuwu_guwen'];
                $data['first_leader'] = $fuwu_guwen;

                //姓名修改
                $data['nickname'] = $_POST['nickname'];
                //风格修改
                $data['fengge_id'] = $_POST['fengge_id'];
                //手机号修改
                $data['mobile'] = $_POST['mobile'];
                //生日修改
                $data['birthdays'] = $_POST['birthdays'];
                //性别修改
                $data['sex'] = $_POST['sex'];

                //年龄段修改
                $data['age_group'] = $_POST['age_group'];

                //开发人修改
                $data['add_uid'] = $_POST['add_uid'];

                //消费总金额修改
                $data['total_amount'] = $_POST['total_amount'];
                //所属门店修改
                $data['store_id'] = $_POST['changeStore'];

                //修改推荐人手机号
                $data['referrals_phone'] = $_POST['referrals_phone'];

                //习惯吃法
                $xiguanchifa=implode(',', $_POST['xiguanchifa']);
                $data['eating_habits']=$xiguanchifa;

                //滋补习惯
                $data['tonic_behavior'] = $_POST['tonic_behavior'];

                //食用人数
                $data['shiyongrenshu'] = $_POST['shiyongrenshu'];

                //食用量
                $data['shiyongliang'] = $_POST['shiyongliang'];

                //获取推荐人ID
                if ($data['referrals_phone']) {
                    $mobile1['mobile'] = $data['referrals_phone'];
                    $mem = M('users')->where($mobile1)->find();
                    if(!$mem){
                        exit($this->error('推荐人不存在'));
                        die;
                    }else{
                        $data['referrals_id'] = $mem['user_id'];
                    }
                }else{
                    $data['referrals_id'] = 0;
                    $data['referrals_phone'] = '';
                }
                
                //修改发票
                $fapiaoArr = $_POST['fp'];
                $_idArr = $fapiaoArr['id'];
                $length = count($_idArr);
                $new_arr = array();
                $arr = array();

                for ($j=0; $j < $length; $j++) { 
                    $arr['faPiao_name'] = $fapiaoArr['fapiao_name'][$_idArr[$j]]?$fapiaoArr['fapiao_name'][$_idArr[$j]]:'';
                    $arr['faPiao_shuihao'] = $fapiaoArr['fapiao_shuihao'][$_idArr[$j]]?$fapiaoArr['fapiao_shuihao'][$_idArr[$j]]:'';
                    $arr['faPiao_adress'] = $fapiaoArr['fapiao_adress'][$_idArr[$j]]?$fapiaoArr['fapiao_adress'][$_idArr[$j]]:'';
                    $arr['link_phone'] = $fapiaoArr['link_phone'][$_idArr[$j]]?$fapiaoArr['link_phone'][$_idArr[$j]]:0;
                    $arr['bank_deposit'] = $fapiaoArr['bank_deposit'][$_idArr[$j]]?$fapiaoArr['bank_deposit'][$_idArr[$j]]:'';
                    $arr['bank_num'] = $fapiaoArr['bank_num'][$_idArr[$j]]?$fapiaoArr['bank_num'][$_idArr[$j]]:0;
                    array_push($new_arr,$arr);
                    $arr = array();
                }

                $le = count($new_arr);
                $str = '';
                for ($i=0; $i < $le; $i++) { 
                    $res1 = M('bill')->where('faPiao_id='.$_idArr[$i])->save($new_arr[$i]);
                    if ($res1) {
                        $str .= '修改成功!';
                    }
                }

                $row = M('users')->where(array('user_id'=>$uid))->save($data);
                if($row || $str)
                    exit($this->success('修改成功'));
                    exit($this->error('未作内容修改或修改失败'));
            }else{
                $data['pay_points'] = $_POST['pay_points'];
                $data['level'] = $_POST['levels'];
                $data['source']=$_POST['source'];
                $purpose=implode(',', $_POST['purpose']);
                $data['purpose']=$purpose;
                $data['fazhifangshi']=$_POST['fazhifangshi'];
                $shiyongrenqun=implode(',', $_POST['shiyongrenqun']);
                $data['shiyongrenqun']=$shiyongrenqun;
                $preferred_products=implode(',', $_POST['preferred_products']);
                $data['preferred_products']=$preferred_products;
                //所属门店修改
                $data['store_id'] = $_POST['changeStore'];
                //服务顾问修改
                $fuwu_guwen = $_POST['fuwu_guwen'];
                $data['first_leader'] = $fuwu_guwen;
                $data['remark']=$_POST['remark'];
                $data['style']=$_POST['style'];
                //修改推荐人手机号
                $data['referrals_phone'] = $_POST['referrals_phone'];
                //获取推荐人ID
                $mobile1['mobile'] = $data['referrals_phone'];
                $mem = M('users')->where($mobile1)->find();
                if(!$mem){
                    exit($this->error('推荐人不存在'));
                    die;
                }else{
                    $data['referrals_id'] = $mem['user_id'];
                }
                $row = M('users')->where(array('user_id'=>$uid))->save($data);
                if($row){
                    exit($this->success('修改成功'));
                }else{
                    exit($this->error('未作内容修改或修改失败'));
                }
            }
            
        }
        if($user['birthday']==0){
            $user['age']="未填写";
        }else{
            $birthday=date('Y-m-d',$user['birthday']);
            
            $age= getAge($birthday);

            $user['age']=$this->getAgelist($age);

        }

        $birthdays=$user['birthdays'];

        if(!empty($birthdays)){
           // $birthdays=substr($birthdays, -4);
            $user['birthdays']=$birthdays;

        }

        $mapssss['level_id']=$user['level'];
        $user_level=D("user_level")->where($mapssss)->find();

        //dump($user_level);

        if($user_level){
            $user['levels']=$user_level['level_name'];
        }

        $kaifa=getAdminInfo($user['add_uid']);
        $user['kaifa']=$kaifa['name'];
        if($user['first_leader']>0){
        $kaifa=getAdminInfo($user['first_leader']);
        $user['consultant']=$kaifa['name'];
        }
       
        $maps['store_id']=$user['store_id'];
        $store=D("store")->where($maps)->find();
        if($store){
               $user["store"]=$store['store_name'];
           }else{
            $user["store"]="";
           }
     
        //服务顾问列表
        $whereMap['_string'] = 'role_id=4 OR role_id=5';
        $guwenList = M('admin')->field('name, admin_id')->where($whereMap)->select();
        $user['first_lower'] = M('users')->where("first_leader = {$user['user_id']}")->count();
        $user['second_lower'] = M('users')->where("second_leader = {$user['user_id']}")->count();
        $user['third_lower'] = M('users')->where("third_leader = {$user['user_id']}")->count();
        $info = D('user_level')->select();

        // 发票抬头
        $fapiao_list = M('bill')->where('user_id='.$uid)->select();

        $storeList = D('store')->field('store_name,store_id')->where('is_forbid=0')->select();
        // var_dump($fapiao_list);die;
        $this->assign('fapiao_list',$fapiao_list);
        $this->assign('changgouchanpin_name',$changgouchanpin_name);
        $this->assign('info',$info);
        $this->assign('user',$user);
        $this->assign('guwenList',$guwenList);
        $this->assign('role_id',$role_id);
        $this->assign('storeList',$storeList);

        $this->display();
    }

    /**
     * 账户资金调节
     */
    public function account_edit(){
        $user_id = I('get.id');
        if(!$user_id > 0)
            $this->error("参数有误");
        if(IS_POST){
            //获取操作类型
            $m_op_type = I('post.money_act_type');
            $user_money = I('post.user_money');
            $user_money =  $m_op_type ? $user_money : 0-$user_money;

            $p_op_type = I('post.point_act_type');
            $pay_points = I('post.pay_points');
            $pay_points =  $p_op_type ? $pay_points : 0-$pay_points;

            $f_op_type = I('post.frozen_act_type');
            $frozen_money = I('post.frozen_money');
            $frozen_money =  $f_op_type ? $frozen_money : 0-$frozen_money;

            $desc = I('post.desc');
            if(!$desc)
                $this->error("请填写操作说明");
            if(accountLog($user_id,$user_money,$pay_points,$desc)){
                $this->success("操作成功",U("Admin/User/account_log",array('id'=>$user_id)));
            }else{
                $this->error("操作失败");
            }
            exit;
        }
        $this->assign('user_id',$user_id);
        $this->display();
    }
    
    public function recharge(){
    	$timegap = I('timegap');
    	$nickname = I('nickname');
    	$map = array();
    	if($timegap){
    		$gap = explode(' - ', $timegap);
    		$begin = $gap[0];
    		$end = $gap[1];
    		$map['ctime'] = array('between',array(strtotime($begin),strtotime($end)));
    	}
    	if($nickname){
    		$map['nickname'] = array('like',"%$nickname%");
    	}  	
    	$count = M('recharge')->where($map)->count();
    	$page = new Page($count);
    	$lists  = M('recharge')->where($map)->order('ctime desc')->limit($page->firstRow.','.$page->listRows)->select();
    	$this->assign('page',$page->show());
    	$this->assign('lists',$lists);
    	$this->display();
    }
    
    public function level(){
    	$act = I('GET.act','add');
    	$this->assign('act',$act);
    	$level_id = I('GET.level_id');
    	$level_info = array();
    	if($level_id){
    		$level_info = D('user_level')->where('level_id='.$level_id)->find();
    		$this->assign('info',$level_info);
    	}
    	$this->display();
    }
    
    public function levelList(){
    	$Ad =  M('user_level');
        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $res = $Ad->where('1=1')->order('level_id')->page($_GET['p'],$pageCount)->select();
        // if($res){
        //  foreach ($res as $key=>$val){
        //      if($val['status']==1){
     //                $res[$key]['status']="启用";
     //            }else{
     //                $res[$key]['status']="禁用";
     //            }
        //  }
        // }
        
        $this->assign('list',$res);
        $count = $Ad->where('1=1')->count();

    	$Page = new \Think\Page($count,$pageCount);
    	$show = $Page->show();
    	$this->assign('page',$show);
    	$this->display();
    }
    
    public function levelHandle(){
    	$data = I('post.');
    	if($data['act'] == 'add'){
    		$r = D('user_level')->add($data);
    	}
    	if($data['act'] == 'edit'){
    		$r = D('user_level')->where('level_id='.$data['level_id'])->save($data);
    	}
    	 
    	if($data['act'] == 'del'){
    		$r = D('user_level')->where('level_id='.$data['level_id'])->delete();
    		if($r) exit(json_encode(1));
    	}
    	 
    	if($r){
    		$this->success("操作成功",U('Admin/User/levelList'));
    	}else{
    		$this->error("操作失败",U('Admin/User/levelList'));
    	}
    }

    /**
     * 搜索用户名
     */
    public function search_user()
    {
        $search_key = trim(I('search_key'));        
        if(strstr($search_key,'@'))    
        {
            $list = M('users')->where(" email like '%$search_key%' ")->select();        
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['user_id']}'>{$val['email']}</option>";
            }                        
        }
        else
        {
            $list = M('users')->where(" mobile like '%$search_key%' ")->select();        
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['user_id']}'>{$val['mobile']}</option>";
            }            
        } 
        exit;
    }
    
    /**
     * 分销树状关系
     */
    public function ajax_distribut_tree()
    {
          $list = M('users')->where("first_leader = 1")->select();
          $this->display();
    }

    /**
     *
     * @time 2016/08/31
     * @author dyr
     * 发送站内信
     */
    public function sendMessage()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $users = M('users')->field('user_id,nickname')->where(array('user_id' => array('IN', $user_id_array)))->select();
        }
        $this->assign('users',$users);
        $this->display();
    }

    /**
     * 发送系统消息
     * @author dyr
     * @time  2016/09/01
     */
    public function doSendMessage()
    {
        $call_back = I('call_back');//回调方法
        $message = I('post.text');//内容
        $type = I('post.type', 0);//个体or全体
        $admin_id = session('admin_id');
        $users = I('post.user');//个体id
        $message = array(
            'admin_id' => $admin_id,
            'message' => $message,
            'category' => 0,
            'send_time' => time()
        );
        if ($type == 1) {
            //全体用户系统消息
            $message['type'] = 1;
            M('Message')->data($message)->add();
        } else {
            //个体消息
            $message['type'] = 0;
            if (!empty($users)) {
                $create_message_id = M('Message')->data($message)->add();
                foreach ($users as $key) {
                    M('user_message')->data(array('user_id' => $key, 'message_id' => $create_message_id, 'status' => 0, 'category' => 0))->add();
                }
            }
        }
        echo "<script>parent.{$call_back}(1);</script>";
        exit();
    }

    /**
     *
     * @time 2016/09/03
     * @author dyr
     * 发送邮件
     */
    public function sendMail()
    {
        $user_id_array = I('get.user_id_array');
        $users = array();
        if (!empty($user_id_array)) {
            $user_where = array(
                'user_id' => array('IN', $user_id_array),
                'email' => array('neq', '')
            );
            $users = M('users')->field('user_id,nickname,email')->where($user_where)->select();
        }
        $this->assign('smtp', tpCache('smtp'));
        $this->assign('users', $users);
        $this->display();
    }

    /**
     * 发送邮箱
     * @author dyr
     * @time  2016/09/03
     */
    public function doSendMail()
    {
        $call_back = I('call_back');//回调方法
        $message = I('post.text');//内容
        $title = I('post.title');//标题
        $users = I('post.user');
        if (!empty($users)) {
            $user_id_array = implode(',', $users);
            $users = M('users')->field('email')->where(array('user_id' => array('IN', $user_id_array)))->select();
            $to = array();
            foreach ($users as $user) {
                if (check_email($user['email'])) {
                    $to[] = $user['email'];
                }
            }
            $res = send_email($to, $title, $message);
            echo "<script>parent.{$call_back}({$res});</script>";
            exit();
        }
    }

    /**
     * 提现申请记录
     */
    public function withdrawals()
    {
        $model = M("withdrawals");
        $_GET = array_merge($_GET,$_POST);
        unset($_GET['create_time']);

        $status = I('status');
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');
        $create_time = I('create_time');
        $create_time = $create_time  ? $create_time  : date('Y/m/d',strtotime('-1 year')).'-'.date('Y/m/d',strtotime('+1 day'));
        $create_time2 = explode('-',$create_time);
        $where = " create_time >= '".strtotime($create_time2[0])."' and create_time <= '".strtotime($create_time2[1])."' ";

        if($status === '0' || $status > 0)
            $where .= " and status = $status ";
        $user_id && $where .= " and user_id = $user_id ";
        $account_bank && $where .= " and account_bank like '%$account_bank%' ";
        $account_name && $where .= " and account_name like '%$account_name%' ";

        $count = $model->where($where)->count();
        $Page  = new Page($count,16);
        $list = $model->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('create_time',$create_time);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }
    /**
     * 删除申请记录
     */
    public function delWithdrawals()
    {
        $model = M("withdrawals");
        $model->where('id ='.$_GET['id'])->delete();
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn(json_encode($return_arr));
    }

    /**
     * 修改编辑 申请提现
     */
    public  function editWithdrawals(){
        $id = I('id');
        $model = M("withdrawals");
        $withdrawals = $model->find($id);
        $user = M('users')->where("user_id = {$withdrawals[user_id]}")->find();

        if(IS_POST)
        {
            $model->create();

            // 如果是已经给用户转账 则生成转账流水记录
            if($model->status == 1 && $withdrawals['status'] != 1)
            {
                if($user['user_money'] < $withdrawals['money'])
                {
                    $this->error("用户余额不足{$withdrawals['money']}，不够提现");
                    exit;
                }


                accountLog($withdrawals['user_id'], ($withdrawals['money'] * -1), 0,"平台提现");
                $remittance = array(
                    'user_id' => $withdrawals['user_id'],
                    'bank_name' => $withdrawals['bank_name'],
                    'account_bank' => $withdrawals['account_bank'],
                    'account_name' => $withdrawals['account_name'],
                    'money' => $withdrawals['money'],
                    'status' => 1,
                    'create_time' => time(),
                    'admin_id' => session('admin_id'),
                    'withdrawals_id' => $withdrawals['id'],
                    'remark'=>$model->remark,
                );
                M('remittance')->add($remittance);
            }
            $model->save();
            $this->success("操作成功!",U('Admin/User/remittance'),3);
            exit;
        }



        if($user['nickname'])
            $withdrawals['user_name'] = $user['nickname'];
        elseif($user['email'])
            $withdrawals['user_name'] = $user['email'];
        elseif($user['mobile'])
            $withdrawals['user_name'] = $user['mobile'];

        $this->assign('user',$user);
        $this->assign('data',$withdrawals);
        $this->display();
    }
    /**
     *  转账汇款记录
     */
    public function remittance(){
        $model = M("remittance");
        $_GET = array_merge($_GET,$_POST);
        unset($_GET['create_time']);

        $status = I('status');
        $user_id = I('user_id');
        $account_bank = I('account_bank');
        $account_name = I('account_name');

        $create_time = I('create_time');
        $create_time = $create_time  ? $create_time  : date('Y/m/d',strtotime('-1 year')).'-'.date('Y/m/d',strtotime('+1 day'));
        $create_time2 = explode('-',$create_time);
        $where = " create_time >= '".strtotime($create_time2[0])."' and create_time <= '".strtotime($create_time2[1])."' ";
        $user_id && $where .= " and user_id = $user_id ";
        $account_bank && $where .= " and account_bank like '%$account_bank%' ";
        $account_name && $where .= " and account_name like '%$account_name%' ";

        $count = $model->where($where)->count();
        $Page  = new Page($count,16);
        $list = $model->where($where)->order("`id` desc")->limit($Page->firstRow.','.$Page->listRows)->select();

        $this->assign('create_time',$create_time);
        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        C('TOKEN_ON',false);
        $this->display();
    }

    public function test(){
       header("Content-type: text/html; charset=utf-8"); 
           
         vendor("PHPExcel.PHPExcel");
            $file_name="Public/20170701.xls";
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            $objPHPExcel = $objReader->load($file_name,$encode='utf-8');
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数

            //echo $highestRow;
            //exit;
            $j=0;
            for($i=2;$i<=$highestRow;$i++)
            {

                $store_id= $objPHPExcel->getActiveSheet()->getCell("A".$i)->getValue();

                
                $maps['shop_no']=$store_id;

                $store=D("store")->where($maps)->find();

                if($store){
                    $store_ids=$store['store_id'];
                }else{

                    $store_ids=3;
                }


                $nickname= $objPHPExcel->getActiveSheet()->getCell("B".$i)->getValue();
              
               

               $mobile= $objPHPExcel->getActiveSheet()->getCell("C".$i)->getValue();
               
                
                $birthdays= $objPHPExcel->getActiveSheet()->getCell("D".$i)->getValue();
               


                $maitime= $objPHPExcel->getActiveSheet()->getCell("E".$i)->getValue();
                $pay_points= $objPHPExcel->getActiveSheet()->getCell("F".$i)->getValue();
                $reg_time= $objPHPExcel->getActiveSheet()->getCell("G".$i)->getValue();
                $level= $objPHPExcel->getActiveSheet()->getCell("H".$i)->getValue();
                $sex= $objPHPExcel->getActiveSheet()->getCell("I".$i)->getValue();
                $remark= $objPHPExcel->getActiveSheet()->getCell("J".$i)->getValue();
                // if(M('Contacts')->where("name='".$data['name']."' and phone=$data['phone']")->find()){
                if(M('Users')->where("mobile='".$mobile."'")->find()){

                    //echo $mobile;
                   // echo ",";
                    //如果存在相同联系人。判断条件：电话 两项一致，上面注释的代码是用姓名/电话判断
                }else{

                    $data['mobile']=$mobile;


                    if(!empty($nickname)){
                          $data['nickname']=$nickname;
                    }else{
                         $data['nickname']='';
                    }

                    if(!empty($birthdays)){
                        $data['birthdays']=$birthdays;
                    }else{
                        $data['birthdays']="0000-00-00";
                    }
                     if(!empty($maitime)){
                         $n = intval(($maitime - 25569) * 3600 * 24)-8*3600;
                         $data['end_time']=$n;
                     }else{
                         $data['end_time']=0;
                     }

                     if(!empty($pay_points)){
                         $data['pay_points']=$pay_points;
                     }else{
                        $data['pay_points']=0;
                     }
                   
                    if(!empty($reg_time)){
                        $b = intval(($reg_time - 25569) * 3600 * 24)-8*3600;
                         $data['reg_time']=$b;
                         $data['first_time']=$b;
                     }else{
                        $data['reg_time']=0;
                     }


                    
                     if(!empty($level)){
                         $data['level']=$level+1;
                     }else{
                          $data['level']=1;
                     }

                     if(!empty($sex)){
                         $data['sex']=$sex;
                     }else{
                         $data['sex']=0;
                     }

                     if(!empty($store_ids)){
                         $data['store_id']=$store_ids;
                     }
                    
                    if(!empty($remark)){
                         $data['remark']=$remark;
                     }else{
                         $data['remark']="";
                     }

                    M('Users')->add($data);
                    $j++;
                }
            }
            //unlink($file_name);
    }

    public function upbirth(){
        $user=D("Users")->field('user_id,reg_time')->limit('15000,8000')->select();
        foreach ($user as $key => $value) {
            $data['user_id']=$value['user_id'];
            $data['first_time']=$value['reg_time'];

            D("Users")->save($data);
            echo $value['user_id'];
            echo '<br>';
        }

    }

    public function recommender(){
        $time = date('Y/m/d',time());
        $this->assign('time',$time);
        $this->display();
    }

    /*
     *Ajax首页
     */
    public function ajaxrecommender(){

        $maps['referrals_id'] = array('gt',0);
        $maps['is_del'] = 0;
        $sort = I('sort');
        $sort_order = I('order_by').' '.$sort;
        $result = M('users')->field('referrals_id')->where($maps)->select();
        if ($result) {
            $user_idArr = array();
            foreach ($result as $key => $value) {
                $user_idArr[] = $value['referrals_id'];
            }
        }

        $model = M();
        if ($user_idArr) {

            //推荐数量
            $start_count_a = I('start_count_a');//高级搜索条件  起始数量
            $end_countt_b = I('end_countt_b');//高级搜索条件  结束数量
            if ($start_count_a || $end_countt_b) {
                if (!empty($start_count_a) && empty($end_countt_b)) {
                    $search_user_id1['count_referrals.cou'] = array('EGT',$start_count_a);
                }else if (empty($start_count_a) && !empty($end_countt_b)) {
                    $search_user_id1['count_referrals.cou'] = array('ELT',$end_countt_b);
                }else if(!empty($start_count_a) && !empty($end_countt_b)){
                    $search_user_id1['count_referrals.cou'] = array(array('EGT',$start_count_a),array('ELT',$end_countt_b)); 
                }
                $search_user_id['is_del'] = 0;
                $search_user_id['referrals_id'] = array('gt',0);
                $infos = M('users')->field('count("referrals_id") cou,referrals_id,nickname')->group('referrals_id')->where($search_user_id)->buildSql();
                $count_search_list = $model->table($infos.' count_referrals')->field('referrals_id')->where($search_user_id1)->select();
                $user_idArr1 = array();
                foreach ($count_search_list as $key => $value) {
                    $user_idArr1[] = $value['referrals_id'];
                }
                $where['user_id'] = array('in',$user_idArr1);
            }else{
                $where['user_id'] = array('in',$user_idArr);
            }

            $sql = M('users')->field('user_id as re_user_id,nickname as re_nickname,mobile as re_mobile,is_del as re_is_del')->where($where)->order('user_id desc')->buildSql();
            $condition = array();

            //搜索条件  推荐人
            if (I('search_user')) {
               $condition['recommender.re_nickname|recommender.re_mobile']=array('like',"%".I('search_user')."%");
            }

            //搜索条件  被推荐人
            if (I('referrals_user')) {
               $condition['tp_users.nickname|tp_users.mobile']=array('like',"%".I('referrals_user')."%");
            }

            //注册时间搜索
            $receive_time = urldecode(I('receive_time'));
            if($receive_time){
                $gap = explode('-', $receive_time);
                $begin = strtotime($gap[0]);
                $end = strtotime($gap[1])+24*3600-1;
                if($begin && $end){
                    $condition['tp_users.reg_time'] = array('between',"$begin,$end");
                }
                $this->assign('_receive_time', urldecode(I('receive_time')));
            }

            //累计购买金额
            $start_point_a = I('start_point_a');//高级搜索条件  开始时间
            $end_point_b = I('end_point_b');//高级搜索条件  结束时间
            //如果只有开始时间 则结束时间为当前
            if (!empty($start_point_a) && empty($end_point_b)) {
                echo "1";
                $condition['total_amount'] = array('EGT',$start_point_a); 
            }else if (empty($start_point_a) && !empty($end_point_b)) {
                $condition['total_amount'] = array('ELT',$end_point_b);
            }else if(!empty($start_point_a) && !empty($end_point_b)){
                $condition['total_amount'] = array(array('EGT',$start_point_a),array('ELT',$end_point_b)); 
            }

            $condition['tp_users.is_del'] = 0;

            $condition['recommender.re_is_del'] = 0;

            $count = $model->table($sql.' recommender')->join('tp_users ON recommender.re_user_id = tp_users.referrals_id','left')->field('recommender.*,tp_users.user_id,tp_users.mobile,tp_users.nickname,tp_users.reg_time,tp_users.total_amount')->where($condition)->count();
            $pageCount = $_GET['pageCount'];
            if ($pageCount==='undefined' || !$pageCount) {
                $pageCount = 25;
            }
            $Page  = new AjaxPage($count,$pageCount);
                
            $result1 = $model->table($sql.' recommender')->join('tp_users ON recommender.re_user_id = tp_users.referrals_id','left')->field('recommender.*,tp_users.user_id,tp_users.mobile,tp_users.nickname,tp_users.reg_time,tp_users.total_amount')->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->where($condition)->select();
            //统计
            
            //推荐人总数
            
            //搜索条件  推荐人
            if (I('search_user')) {
               $gettuijianid['nickname|mobile']=array('like',"%".I('search_user')."%");
               $gettuijianid_arr = M('users')->field('user_id')->where($gettuijianid)->select();
               $result_gettuijianid = array();
               foreach ($gettuijianid_arr as $key2 => $v) {
                   $result_gettuijianid[] = $v['user_id'];
               }
            }

            //搜索条件  被推荐人
            if (I('referrals_user')) {
               $getbeituijianid['nickname|mobile']=array('like',"%".I('referrals_user')."%");
               $getbeituijianid_arr = M('users')->field('user_id')->where($getbeituijianid)->select();
               $result_getbeituijianid = array();
               foreach ($getbeituijianid_arr as $key1 => $v1) {
                   $result_getbeituijianid[] = $v1['user_id'];
               }
            }

            

            $result12 = $model->table($sql.' recommender')->join('tp_users ON recommender.re_user_id = tp_users.referrals_id','left')->field('recommender.re_user_id')->order($sort_order)->where($condition)->select();
            $tuijianIdArr = array();
            foreach ($result12 as $key => $val2) {
                $tuijianIdArr[] = $val2['re_user_id'];
            }
            $tuijianIdArr = array_unique($tuijianIdArr);
            $tuijian_count = count($tuijianIdArr);

            //被推荐人数
            // if ($result_gettuijianid) {
            //     $beituijian_search['referrals_id'] = array('in',$result_gettuijianid);
            // }else{
            //     $beituijian_search['referrals_id'] = array('gt',0);
            // }

            //如果推荐数量存在
            if ($user_idArr1 && $result_gettuijianid) {
                $repeat_arr = array_intersect($user_idArr1, $result_gettuijianid);
                if ($repeat_arr) {
                    $beituijian_search['referrals_id'] = array('in',$repeat_arr);
                }
            }elseif ($user_idArr1 && !$result_gettuijianid) {
                $beituijian_search['referrals_id'] = array('in',$user_idArr1);
            }elseif(!$user_idArr1 && $result_gettuijianid){
                $beituijian_search['referrals_id'] = array('in',$result_gettuijianid);
            }else{
                $beituijian_search['referrals_id'] = array('gt',0);
            }


            //搜索条件注册时间存在
            if ($condition['tp_users.reg_time']) {
                $beituijian_search['reg_time'] = array('between',"$begin,$end");
            }

            //被推荐人id存在
            if ($result_getbeituijianid) {
                $beituijian_search['user_id'] = array('in',$result_getbeituijianid);
            }

            //累计购买金额存在
            if ($condition['total_amount']) {
                if (!empty($start_point_a) && empty($end_point_b)) {
                    $beituijian_search['total_amount'] = array('EGT',$start_point_a); 
                }else if (empty($start_point_a) && !empty($end_point_b)) {
                    $beituijian_search['total_amount'] = array('ELT',$end_point_b);
                }else if(!empty($start_point_a) && !empty($end_point_b)){
                    $beituijian_search['total_amount'] = array(array('EGT',$start_point_a),array('ELT',$end_point_b)); 
                }
            }

            // $beituijian_search['is_del'] = 0;
            // $beituijian_count = M('users')->where($beituijian_search)->count();
            $beituijian_count = $count;

            // //推荐人消费
            // if ($result_gettuijianid) {
            //     $beituijian_sale_search['referrals_id'] = array('in',$result_gettuijianid);
            // }else{
            //     $beituijian_sale_search['referrals_id'] = array('gt',0);
            // }
            //如果推荐数量存在
            if ($user_idArr1 && $result_gettuijianid) {
                $repeat_arr = array_intersect($user_idArr1, $result_gettuijianid);
                if ($repeat_arr) {
                    $beituijian_sale_search['referrals_id'] = array('in',$repeat_arr);
                }
            }elseif ($user_idArr1 && !$result_gettuijianid) {
                $beituijian_sale_search['referrals_id'] = array('in',$user_idArr1);
            }elseif(!$user_idArr1 && $result_gettuijianid){
                $beituijian_sale_search['referrals_id'] = array('in',$result_gettuijianid);
            }else{
                $beituijian_sale_search['referrals_id'] = array('gt',0);
            }

            //被推荐人id存在
            if ($result_getbeituijianid) {
                $beituijian_sale_search['user_id'] = array('in',$result_getbeituijianid);
            }

            //搜索条件注册时间存在
            if ($condition['tp_users.reg_time']) {
                $beituijian_sale_search['reg_time'] = array('between',"$begin,$end");
            }

            //累计购买金额存在
            if ($condition['total_amount']) {
                if (!empty($start_point_a) && empty($end_point_b)) {
                    $beituijian_sale_search['total_amount'] = array('EGT',$start_point_a); 
                }else if (empty($start_point_a) && !empty($end_point_b)) {
                    $beituijian_sale_search['total_amount'] = array('ELT',$end_point_b);
                }else if(!empty($start_point_a) && !empty($end_point_b)){
                    $beituijian_sale_search['total_amount'] = array(array('EGT',$start_point_a),array('ELT',$end_point_b)); 
                }
            }


            // $beituijian_sale_search['is_del'] = 0;
            $true_user_id = array();
            foreach ($result1 as $ke => $va) {
                $true_user_id[] = $va['user_id'];
            }
            $beituijian_sale_search['user_id'] = array('in',$true_user_id);
            if ($beituijian_sale_search['user_id']) {
                $beituijian_sale = M('users')->where($beituijian_sale_search)->sum('total_amount');
            }else{
                $beituijian_sale = 0;
            }

            $this->assign('tuijian_count',$tuijian_count);
            $this->assign('beituijian_count',$beituijian_count);
            $this->assign('beituijian_sale',$beituijian_sale);
            $this->assign('info',$result1);
            $show = $Page->show();
            $this->assign('page',$show);// 赋值分页输出
        }

        $this->display();
    }

}