<?php
/**
 * Author: ericyang      
 * Date: 2017-05-21
 */
namespace Admin\Controller;
use Think\AjaxPage;

class StoreController extends BaseController {
    /**----------------------------------------------*/
     /*                优惠券控制器                  */
    /**----------------------------------------------*/
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表
        
    	$count =  M('store')->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

    	$Page = new \Think\Page($count,$pageCount);        
        $show = $Page->show();
        $lists = M('store')->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        // var_dump($lists);die;
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
        $this->display();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function store_info(){

          $admin=D("Admin")->where('role_id =4')->select();
        

          $this->assign('admin',$admin);

        if(IS_POST){
        	$data = I('post.');

            if(empty($data['shop_no'])){
                 $this->error('门店编号不能为空！');
            }
             if(empty($data['store_name'])){
                 $this->error('门店名称不能为空！');
            }
           
            $mpas['admin_id']=$data['userid'];
            $admins=D("Admin")->where($mpas)->find();

            if(empty($data['store_id'])){


                $mapsss['shop_no']=$data['shop_no'];

                $store=D("store")->where($mapsss)->find();

                if($store){
                     $this->error('该门店编号已经存在！');
                }

              
                

                $shop_sn=D("store")->max('shop_no');

                if(!empty($admins['name'])){
                    $data['shopkeeper']=$admins['name'];
                }

                $data['create_time'] = time();
                $row = M('store')->add($data);

                if(!empty($admins['store_id'])){
                    $row1=$admins['store_id'];
                    $row1.=",";
                    $row1.=$row;
                }else{
                    $row1=$row ;
                }
                
                $datas['store_id']=$row1;
                $datas['admin_id']=$data['userid'];
                //dump($datas);
                D("Admin")->save($datas);
                  
            }else{
                $data['update_time'] = time();
                $data['shopkeeper']=$admins['name'];
            	$row =  M('store')->where(array('store_id'=>$data['store_id']))->save($data);

                ///echo M()->getlastsql();

                //门店有没有店长
                $mapsss['store_id']=$data['store_id'];
                $mapsss['role_id']=4;

                $listss=D("Admin")->where($mapsss)->find();

                if($listss){
                        $vadata['store_id']=0;
                        $vadata['admin_id']=$listss['admin_id'];        
                        D("Admin")->save($vadata);
                }

                //下架其他门店的店长
               if(!empty($admins['store_id'])){

                    if($admins['store_id']!=$data['store_id']){
                        $vwhere['store_id']=$admins['store_id'];
                        $datass['shopkeeper']="";

                        D("store")->where($vwhere)->save($datass);
                    }
                  

                    $awhere['admin_id']=$admins['admin_id'];
                    $adataava['store_id']="";

                    D("Admin")->where( $awhere)->save($adataava);


               }


                $datas['store_id']=$data['store_id'];
                $datas['admin_id']=$data['userid'];
                //dump($datas);
                D("Admin")->save($datas);
            }
            if(!$row)
                $this->error('操作门店失败');
            $this->success('操作门店成功',U('Admin/Store/index'));
            exit;
        }
        $cid = I('get.id');
        if($cid){

            $admin=D("Admin")->where('admin_id=4')->select();
        	$coupon = M('store')->where(array('store_id'=>$cid))->find();

            //dump($admin);

        	$this->assign('coupon',$coupon);
            
        }
        $this->display();
    }

    public function getAdminStore(){
        $uid=$_REQUEST['uid'];

        $maps['admin_id']=$uid;
        $admin=D("Admin")->where($maps)->find();
       // echo M()->getlastsql();

        if(!empty($admin['store_id'])){
            $mapss['store_id']=$admin['store_id'];
            $store=D("Store")->where($mapss)->find();

            $data="该用户在门店";
            $data.=$store['store_name'];
            $data.="当店长，是否要更换！";

                $return_arr = array(
                        'status' => 0,
                        'msg'   => '操作失败!',
                        'data'  => $data,
                    );
                    $this->ajaxReturn(json_encode($return_arr));

        }else{

                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '',
                        'data'  => ""
                    );
                    $this->ajaxReturn(json_encode($return_arr));

        }

         //  编辑
                   
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
            $pageCount =25;
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
    				$user = M('users')->where("is_lock=0")->select();
    			}else{
                    
                
    				$user = M('users')->where("is_lock=0 and level=$level_id")->select();

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
    				$insert .= "($cid,1,$v,$time)$gap";
    			}
    		}
			$sql = "insert into __PREFIX__coupon_list (`cid`,`type`,`uid`,`send_time`) VALUES $insert";
			M()->execute($sql);
			M('coupon')->where("id=$cid")->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$coupon['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->select();
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
        
        $count = M()->query($sql);
        $count = $count[0]['c'];

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

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
    public function del_store(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        $maps['store_id']=$cid;

        $admin=D("Admin")->where($maps)->select();
        if($admin){
              $this->error('该门店有销售人员不能删！');
        }

        $row = M('store')->where(array('store_id'=>$cid))->delete();
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
            $data = I('post.');

            exit;
        }

        $coupon=D("coupon")->where()->select();
        $reminder=D("brith_reminder")->find();

        $this->assign('coupon',$reminder);
        $this->display();
    }

    //禁用门店
    public function forbid_store(){
        if(IS_POST){
            $arr = array();
            $id = I('get.id');
            if(!$id){
                $arr['info'] = "缺少参数值";
                $arr['success'] = 0;
            }else{
                $model = D('store');
                $model->is_forbid = 1;
                $result = $model->where('store_id='.$id)->save();
                if ($result) {
                    $arr['success'] = 1;
                }else{
                    $arr['info'] = "修改失败";
                    $arr['success'] = 0;
                }
            }
            echo json_encode($arr); 
            die;
        }
    }
    //启用门店
    public function start_store(){
        if(IS_POST){
            $id = I('get.id');
            if(!$id){
                $arr['info'] = "缺少参数值";
                $arr['success'] = 0;
            }else{
                $model = D('store');
                $model->is_forbid = 0;
                $result = $model->where('store_id='.$id)->save();
                if ($result) {
                    $arr['success'] = 1;
                }else{
                    $arr['info'] = "修改失败";
                    $arr['success'] = 0;
                }
            }
            echo json_encode($arr); 
            die;
        }
    }

}