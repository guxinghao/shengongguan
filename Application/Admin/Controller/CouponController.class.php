<?php
/**
 * Author: ericyang      
 * Date: 2017-05-21
 */
namespace Admin\Controller;
use Think\AjaxPage;
class CouponController extends BaseController {
    /**----------------------------------------------*/
     /*                优惠券控制器                  */
    /**----------------------------------------------*/
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表
    	$count =  M('coupon')->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page = new \Think\Page($count,$pageCount);        
        $show = $Page->show();
        $lists = M('coupon')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
        $this->display();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function coupon_info(){

        if(IS_POST){
            $data1 = I('post.');
        	$data = array();
            if (I('cate_id')!=3) {
                $data['send_start_time'] = strtotime($data1['send_start_time']);
                $data['send_end_time'] = strtotime($data1['send_end_time']);
                $data['use_end_time'] = strtotime($data1['use_end_time']);
                $data['use_start_time'] = strtotime($data1['use_start_time']);
                $data['name'] = I('name');
                $data['money'] = I('money');
                $data['cate_id'] = I('cate_id');
                $data['condition'] = I('condition');
                $data['type'] = 1;
                if($data['send_start_time'] > $data['send_end_time']){
                    $this->error('发放日期填写有误');
                }
            }else{
                //判断sku和goods_id是否为同一商品
                if (I('goods_id')) {
                    $this_goods_id = getSku(I('goods_id'));
                    if ($this_goods_id != I('sku')) {
                        $this->error('商品名称与商品编号不一致!');
                        die;
                    }
                }
                $data['name'] = I('name');
                $data['cate_id'] = I('cate_id');
                $data['sku'] = I('sku');
                $data['goods_id'] = I('goods_id');
                $data['createnum'] = I('createnum');
                if ($data['goods_id']) {
                    $maps['goods_id'] = $data['goods_id'];
                    $data['goods_name'] = M('goods')->where($maps)->getField('goods_name');
                }
                
            }
            if(empty($data1['id'])){
            	$data['add_time'] = time();
            	$row = M('coupon')->add($data);
            }else{
            	$row =  M('coupon')->where(array('id'=>$data1['id']))->save($data);
            }
            if(!$row){
                $this->error('操作优惠券失败');
            }else{
                if (I('type1')) {
                    $this->success('操作代优惠券成功',U('Admin/Coupon/reminder'));
                    exit;
                }else{
                    $this->success('操作代优惠券成功',U('Admin/Coupon/index'));
                    exit;
                }
            }
        }
        $cid = I('get.id');

        if($cid){
        	$coupon = M('coupon')->where(array('id'=>$cid))->find();
        	$this->assign('coupon',$coupon);
        }else{
        	$def['send_start_time'] = strtotime("+1 day");
        	$def['send_end_time'] = strtotime("+1 month");
        	$def['use_start_time'] = strtotime("+1 day");
        	$def['use_end_time'] = strtotime("+2 month");
        	$this->assign('coupon',$def);
        } 
        $goodList = M('goods')->field('goods_id,goods_name')->select(); 
        $this->assign('goodsList',$goodList);    
        $this->display();
    }

    //根据sku获取商品列表
    public function getgoodslist(){
        $sku = I('sku');
        if ($sku) {
            $where['sku'] = array('like','%'.$sku.'%');
            $goods_idArr = M('spec_goods_price')->where($where)->field('goods_id')->select();
            $goodsIdArr = array();
            if ($goods_idArr) {
                foreach ($goods_idArr as $key => $value) {
                    $goodsIdArr[] = $value['goods_id'];
                }
            }
        }
        if ($goodsIdArr) {
            $condition['goods_id'] = array('in',$goodsIdArr);
        }else{
            $condition['goods_id'] = 0;
        }
        $goodslist = M('goods')->where($condition)->field('goods_id,goods_name')->select();
        foreach ($goodslist as $key => $val) {
            $goodslist[$key]['sku'] = getSku($val['goods_id']);
        }
        $json = json_encode($goodslist);
        echo $json;exit();
    }

    /*
    * 优惠券发放
    */
    public function make_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        $type = I('get.type');
        //查询是否存在优惠券
        $data = M('coupon')->where(array('id'=>$cid))->find();
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    	if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("优惠券类型不存在");
        if($type != 4) $this->error("该优惠券类型不支持发放");
        if(IS_POST){
            $num  = I('post.num');
            if($num>$remain) $this->error($data['name'].'发放量不够了');
            if(!$num > 0) $this->error("发放数量不能小于0");
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            for($i=0;$i<$num; $i++){
                do{
                    $code = get_rand_str(8,0,1);//获取随机8位字符串
                    $check_exist = M('coupon_list')->where(array('code'=>$code))->find();
                }while($check_exist);
                $add['code'] = $code;
                M('coupon_list')->add($add);
            }
            M('coupon')->where("id=$cid")->setInc('send_num',$num);
            adminLog("发放".$num.'张'.$data['name']);
            $this->success("发放成功",U('Admin/Coupon/index'));
            exit;
        }
        $this->assign('coupon',$data);
        $this->display();
    }
    
    public function ajax_get_user(){
    	//搜索条件
    	$condition = array();
    	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    	I('email') ? $condition['email'] = I('email') : false;
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
    	$model = M('users');
    	$count = $model->where($condition)->count();

        $pageCount = $_GET['pageCount'];
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page  = new AjaxPage($count,$pageCount);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $user_level = M('user_level')->getField('level_id,level_name',true);       
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$this->assign('page',$show);
    	$this->display();
    }
    
    public function send_coupon(){
    	$cid = I('cid');    	
    	if(IS_POST){
    		$level_id = I('level_id');
    		$user_id = I('user_id');
    		$insert = '';
    		$coupon = M('coupon')->where("id=$cid")->find();
    		
            if($coupon['createnum']>0){
    			$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
    			if($remain<=0) $this->error($coupon['name'].'已经发放完了');
    		}
    		
    		if(empty($user_id) && $level_id>=0){
    			if($level_id==0){
    				$user = M('users')->where("is_lock=0")->field('user_id')->select();
    			}else{
    				$user = M('users')->where("is_lock=0 and level=$level_id")->field('user_id')->select();
    			}
               
    			if($user){
    				$able = count($user);//本次发送量
    				if($coupon['createnum']>0 && $remain<$able){
    					$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    				}
    				foreach ($user as $k=>$val){
    					$user_id = $val['user_id'];
    					$time = time();
    					$gap = ($k+1) == $able ? '' : ',';
    					$insert .= "($cid,1,$user_id,$time)$gap";
    				}
    			}else{
                    $this->error("不存在要发放的用户");
                    exit;
                }
    		}else{
             
    			$able = count($user_id);//本次发送量
    			if($coupon['createnum']>0 && $remain<$able){
    				$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    			}
    			foreach ($user_id as $k=>$v){
    				$time = time();
    				$gap = ($k+1) == $able ? '' : ',';
                    $code = date('YmdHis').rand(1000,9999);
    				$insert .= "($cid,1,$v,$code,$time)$gap";
    			}
    		}
			$sql = "insert into __PREFIX__coupon_list (`cid`,`type`,`uid`,`code`,`send_time`) VALUES $insert";
			M()->execute($sql);
			M('coupon')->where("id=$cid")->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$coupon['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->where('status=1')->select();
    	$this->assign('level',$level);
    	$this->assign('cid',$cid);
    	$this->display();
    }
    
    public function send_cancel(){
    	
    }

    /*
     * 删除优惠券类型
     */
    public function del_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $row = M('coupon')->where(array('id'=>$cid))->delete();
        if($row){
            //删除此类型下的优惠券
            M('coupon_list')->where(array('cid'=>$cid))->delete();
            //删除此类型下的生日券
            M('brith_reminder')->where(array('coupon_id'=>$cid))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }


    /*
     * 优惠券详细查看
     */
    public function coupon_list(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $check_coupon = M('coupon')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_coupon['id'] > 0)
            $this->error('不存在该类型优惠券');
       
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid;    //联合用户表去查询用户名        
        
        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $count = M()->query($sql);
        $count = $count[0]['c'];
    	$Page = new \Think\Page($count,$pageCount);
    	$show = $Page->show();
        
        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid.    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = M()->query($sql);
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);            	
    	$this->assign('page',$show);        
        $this->display();
    }
    
    /*
     * 删除一张优惠券
     */
    public function coupon_list_del(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
         $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }
    //满减列表
    public function manlist(){
         //获取优惠券列表
        
        $count =  M('promotion')->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page = new \Think\Page($count,$pageCount);        
        $show = $Page->show();
        $lists = M('promotion')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($lists as $key=>$val){
            $mmaps['pid']=$val['id'];
            $pgoods=D("promotion_goods")->where( $mmaps)->select();
            $goods_names=formatArray($pgoods,'goods_name');

            $lists[$key]['goods_names']=$goods_names;
        }
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
       
        $this->display();
    }

    public function del_man(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
        $row = M('promotion')->where(array('id'=>$cid))->delete();
        if(!$row){
            $this->error('删除失败');
        }else{

            $row = M('promotion_goods')->where(array('pid'=>$cid))->delete();
            $this->success('删除成功');
        }


    }
    public function add_man(){
          if(IS_POST){
            $data = I('post.');


            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time'] = strtotime($data['send_end_time']);
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            if($data['send_start_time'] > $data['send_end_time']){
                $this->error('发放日期填写有误');
            }
            if(empty($data['id'])){
                $data['add_time'] = time();
                $data['status'] = 1;
                $row = M('promotion')->add($data);

                $sku=$data['sku'];
                $num=$data['num'];
                $goods_id=$data['goods_id'];
                $key_name=$data['key_name'];
                $goods_name=$data['goods_name'];
                for($i = 0; $i < count ( $sku ); $i ++) {
                    $datas ['sku'] = $sku[$i];
                    $datas ['num'] = $num[$i];
                    $datas ['goods_id'] = $goods_id[$i];
                    $datas ['key_name'] = $key_name[$i];
                    $datas ['goods_name'] = $goods_name[$i];

                    $datas ["create_time"] = time ();
                    $datas ["pid"] =$row;
                    D("promotion_goods")->add($datas);
                }

            }else{
                $row =  M('promotion')->where(array('id'=>$data['id']))->save($data);

                $sku=$data['sku'];
                $num=$data['num'];
                $goods_id=$data['goods_id'];
                $key_name=$data['key_name'];
                $goods_name=$data['goods_name'];
                for($i = 0; $i < count ( $sku ); $i ++) {
                    $datas ['sku'] = $sku[$i];
                    $datas ['num'] = $num[$i];
                    $datas ['goods_id'] = $goods_id[$i];
                    $datas ['key_name'] = $key_name[$i];
                    $datas ['goods_name'] = $goods_name[$i];
                    $datas ["create_time"] = time ();
                    $datas ["pid"] =$data['id'];
                    D("promotion_goods")->add($datas);
                }
            }
            if(!$row)
                $this->error('操作满减失败');
            $this->success('操作满减成功',U('Admin/Coupon/manlist'));
            exit;
        }
        $cid = I('get.id');
        if($cid){
            $coupon = M('promotion')->where(array('id'=>$cid))->find();
            $this->assign('coupon',$coupon);
        }else{
            $def['send_start_time'] = strtotime("+1 day");
            $def['send_end_time'] = strtotime("+1 month");
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('coupon',$def);
        }     
        $this->display();
    }

    //礼品券管理
    public function consumption(){

        $count =  M('promotion')->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page = new \Think\Page($count,$pageCount);        
        $show = $Page->show();
        $lists = M('package')->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($lists as $key=>$val){
            $mmaps['pid']=$val['id'];
            $pgoods=D("package_goods")->where( $mmaps)->select();
            //$goods_names=formatArray($pgoods,'goods_name');

            $lists[$key]['goods_names']=$pgoods;
        }
       // dump($lists);
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));

        $this->display();
    }

     public function del_consumption(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
        $row = M('package')->where(array('id'=>$cid))->delete();
        if(!$row){
            $this->error('删除失败');
        }else{

            $row = M('package_goods')->where(array('pid'=>$cid))->delete();
            $this->success('删除成功');
        }


    }

    public function add_package(){
          if(IS_POST){
            $data = I('post.');


            //$data['send_start_time'] = strtotime($data['send_start_time']);
           // $data['send_end_time'] = strtotime($data['send_end_time']);
           // $data['use_end_time'] = strtotime($data['use_end_time']);
           // $data['use_start_time'] = strtotime($data['use_start_time']);
           // if($data['send_start_time'] > $data['send_end_time']){
           //     $this->error('发放日期填写有误');
           // }
            if(empty($data['id'])){
                $data['create_time'] = time();
                $data['status'] = 1;
                $row = M('package')->add($data);

                $sku=$data['sku'];
                $num=$data['num'];
                $goods_id=$data['goods_id'];
                $key_name=$data['key_name'];
                $goods_name=$data['goods_name'];
                for($i = 0; $i < count ( $sku ); $i ++) {
                    $datas ['sku'] = $sku[$i];
                    $datas ['num'] = $num[$i];
                    $datas ['goods_id'] = $goods_id[$i];
                    $datas ['key_name'] = $key_name[$i];
                    $datas ['goods_name'] = $goods_name[$i];

                    $datas ["create_time"] = time ();
                    $datas ["pid"] =$row;
                    D("package_goods")->add($datas);
                }

            }else{
                $data['update_time'] = time();
                $row =  M('package')->save($data);

                M('package_goods')->where(array('pid'=>$data['id']))->delete();


                ///echo M()->getlastsql();

                $sku=$data['sku'];
                $num=$data['num'];
                $goods_id=$data['goods_id'];
                $key_name=$data['key_name'];
                $goods_name=$data['goods_name'];
                for($i = 0; $i < count ( $sku ); $i ++) {
                    $datas ['sku'] = $sku[$i];
                    $datas ['num'] = $num[$i];
                    $datas ['goods_id'] = $goods_id[$i];
                    $datas ['key_name'] = $key_name[$i];
                    $datas ['goods_name'] = $goods_name[$i];
                    $datas ["create_time"] = time ();
                    $datas ["pid"] =$data['id'];
                    D("package_goods")->add($datas);
                }
            }
            if(!$row)
                $this->error('操作礼品卷失败');
            $this->success('操作礼品卷成功',U('Admin/Coupon/consumption'));
            exit;
        }
        $cid = I('get.id');
        if($cid){
            $coupon = M('package')->where(array('id'=>$cid))->find();
            $this->assign('coupon',$coupon);
        }
        $this->display();
    }
    //动态获取商品
    public function ajaxSpecGoods(){
        $sku=$_REQUEST['goodss'];
        if(!empty($sku)){

            $maps['sku']=array('like',"%".$sku."%");
            $spec_goods_price=D("spec_goods_price")->where($maps)->select();
            foreach($spec_goods_price as $key=>$val){
                $mapss['goods_id']=$val['goods_id'];
                $goods=D("goods")->where($mapss)->find();

                $spec_goods_price[$key]['goods_name']=$goods['goods_name'];
                $spec_goods_price[$key]['spu']=$goods['spu'];
            }
            $this->assign('list',$spec_goods_price);
            $this->display();
        }
       
    }
    //生日提醒
    public function reminder(){
        if(IS_POST){

            if (I('id')) {
               $data['id'] = I('id'); 
            }
            if (!I('card_id')) {
                $this->error('请选择优惠券!');
                exit();
            }
            $data['coupon_id'] = I('card_id');
            $data['status'] = I('status');  //0 关闭  1 开启
            $data['daycount'] = 7;  //提前天数  默认7天
            $data['effective'] = 15;  //有效天数 默认15天
            $data['add_time'] = time();  //创建时间
            $data['integral_point'] = I('integral_point');  //倍数
            $data['integral'] = I('integral');  //是否开启

            if (I('id')) {
                $result = M('brith_reminder')->save($data);
            }else{
                $result = M('brith_reminder')->add($data);
            }

            if($result){
                $this->success('设置成功',U('Admin/Coupon/reminder'));
                exit();
            }else{
                $this->error('设置失败');
                exit();
            }
        }

        $mapss['cate_id'] = 3;
        $coupon = D("coupon")->where($mapss)->select();

        $brith_reminder = M('brith_reminder')->where($where)->order('add_time desc')->find();
        $this->assign('coupon',$coupon);
        
        $this->assign('brith_reminder',$brith_reminder);
        $this->display();
    }

}