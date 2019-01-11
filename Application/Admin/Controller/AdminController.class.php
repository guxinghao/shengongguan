<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-15
 */

namespace Admin\Controller;

use Think\Page;
use Think\Verify;

class AdminController extends BaseController {

    public function index(){
    	$res = $list = array();
        $where = array();
    	$keywords = I('keywords');
    	if(empty($keywords)){
            $res = D('admin')->select();
    		// $count = D('admin')->count();
    	}else{
            // $res = D()->query("select * from __PREFIX__admin where user_name like '%$keywords%' order by admin_id");
            $where['user_name'] = array('like',"%".$keywords."%");
        }

    	$count = D('admin')->where($where)->count();
        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        $this->assign('pageCount',$pageCount);
        $Page  = new \Think\Page($count,$pageCount);         
        $show = $Page->show();
        $res = M('admin')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('admin_id')->select();
    	$role = D('admin_role')->getField('role_id,role_name');
    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
                if ($val['add_time']) {
                    $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                }else{
                    $val['add_time'] = '';
                }
                if ($val['last_login']) {
    			    $val['last_login'] = date('Y-m-d H:i:s',$val['last_login']);
                }else{
                    $val['last_login'] = '';
                }
    			$list[] = $val;
    		}
    	}
    	$this->assign('list',$list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
   
    public function admin_info(){
    	$admin_id = I('get.admin_id',0);   	
    	if($admin_id){
    		$info = D('admin')->where("admin_id=$admin_id")->find();
                $info['password'] =  "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
    	$role = D('admin_role')->where('1=1')->select();
    	$this->assign('role',$role);

        $admin = M('store')->field('store_id,store_name')->where('1=1')->select();

        
        if($info){

            foreach ( $admin as $key => $value) {
                $arr=explode(',', $info['store_id']);
                $isin = in_array($value['store_id'],$arr);
               // echo $info['store_id'];
                if ($isin === false){
                    $admin[$key]['check']=0;
                }else{
                    $admin[$key]['check']=1;
                }
            }


        }
      

        
        $this->assign('admin', $admin);
        
    	$this->display();
    }
    
    public function adminHandle(){
    	$data = I('post.');

    	if(empty($data['password'])){
    		unset($data['password']);
    	}else{
    		$data['password'] = encrypts($data['password']);
    	}
        // if($data['role_id']==4){

        //     $store_id=$data['store_id'];

        //     $maps['store_id']=$store_id;

        //     $mdlist=D("admin")->where($maps)->find();

        //    // echo M()->getlastsql();
        //    // exit;
        // if($data['act'] == 'add'){

        //     if($mdlist){

        //          $this->error("该门店已经有店长，请更换其他门店",U('Admin/Admin/admin_info'));
        //          exit;
        //     }
        // }else if($data['act'] == 'edit'){


        //     if($mdlist){

        //         if($mdlist['admin_id']!=$data['admin_id']){
        //              $this->error("该门店已经有店长，请更换其他门店",U('Admin/Admin/admin_info'));
        //             exit;
        //         }

                
        //     }

        // }

        // }

        // if($data['role_id']<3 || $data['role_id']==12){
             if(!empty($data['store_ids'])){
                $store_ids=implode(',', $data['store_ids']);

                $data['store_id']=$store_ids;
            }
        // }

     
    	if($data['act'] == 'add'){
    		unset($data['admin_id']);    		
    		$data['add_time'] = time();
    		if(D('admin')->where("user_name='".$data['user_name']."'")->count()){
    			$this->error("此用户名已被注册，请更换");
    		}elseif(D('admin')->where("mobile='".$data['mobile']."'")->count()){
                $this->error("此手机号码已被注册，请更换");
            }else{
    			$r = D('admin')->add($data);
    		}
    	}
    	
    	if($data['act'] == 'edit'){

            $maps['admin_id']=$data['admin_id'];

            $adm= D('admin')->where('admin_id='.$data['admin_id'])->find();
            $adminids=$adm['admin_id'];
            if($adm['user_name']!=$data['user_name']){

                if($username=D('admin')->where("user_name='".$data['user_name']."'")->find()){
                    $this->error("此用户名已存在，请更换",U("Admin/Admin/admin_info/admin_id/".$adminids.""));
                }

            }

            if($adm['mobile']!=$data['mobile']){

                if(D('admin')->where("mobile='".$data['mobile']."'")->find()){
                    
                    $this->error("此手机号码已存在，请更换",U("Admin/Admin/admin_info/admin_id/".$adminids.""));
                }

            }


    		$r = D('admin')->where('admin_id='.$data['admin_id'])->save($data);

    	}
    	// if($data['act'] == 'edit'||$data['act'] == 'add'){
     //        if($data['role_id']==4){

     //            $datas['store_id']=$data['store_id'];
     //            $datas['shopkeeper']=$data['name'];
     //            D("store")->save($datas);
     //        }
     //    }
        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = D('admin')->where('admin_id='.$data['admin_id'])->delete();
    		exit(json_encode(1));
    	}
    	
    	if($r){
    		$this->success("操作成功",U('Admin/Admin/index'));
    	}else{
    		$this->error("操作失败",U('Admin/Admin/index'));
    	}
    }
    
    //前台首页用户修改密码(即安全退出位置)
    public function admin_info1(){
        $admin_id = I('get.admin_id',0);    
        if($admin_id){
            $info = D('admin')->where("admin_id=$admin_id")->find();
                $info['password'] =  "";
            $this->assign('info',$info);
        }
        $act = empty($admin_id) ? 'add' : 'edit';
        $this->assign('act',$act);
        $role = D('admin_role')->where('1=1')->select();
        $this->assign('role',$role);

        $admin = M('store')->field('store_id,store_name')->where('1=1')->select();
        
        if($info){
            foreach ( $admin as $key => $value) {
                $arr=explode(',', $info['store_id']);
                $isin = in_array($value['store_id'],$arr);
                if ($isin === false){
                    $admin[$key]['check']=0;
                }else{
                    $admin[$key]['check']=1;
                }
            }
        }
        
        $this->assign('admin', $admin);
        
        $this->display();
    }


    public function updatePass (){
        $data = I('post.');
        if(empty($data['password'])){
            unset($data['password']);
        }else{
            $data['password'] = encrypts($data['password']);
        }
        $maps['admin_id']=$data['admin_id'];

        $adm= D('admin')->where('admin_id='.$data['admin_id'])->find();
        $adminids=$adm['admin_id'];
        if($adm['user_name']!=$data['user_name']){

            if($username=D('admin')->where("user_name='".$data['user_name']."'")->find()){
                $this->error("此用户名已存在，请更换",U("Admin/Admin/admin_info/admin_id/".$adminids.""));
            }

        }

        if($adm['mobile']!=$data['mobile']){

            if(D('admin')->where("mobile='".$data['mobile']."'")->find()){
                
                $this->error("此手机号码已存在，请更换",U("Admin/Admin/admin_info/admin_id/".$adminids.""));
            }

        }


        $r = D('admin')->where('admin_id='.$data['admin_id'])->save($data);

        if($r){
            $this->success("操作成功",U('Admin/index/welcome'));
        }else{
            $this->error("操作失败",U('Admin/index/welcome'));
        }
    }
    


    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?admin_id') && session('admin_id')>0){
             $this->error("您已登录",U('Admin/Index/index'));
        }
      
        if(IS_POST){
            $verify = new Verify();
            if (!$verify->check(I('post.vertify'), "admin_login")) {
            	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
            }
            $condition['user_name'] = I('post.username');


            $condition['password'] = I('post.password');
            if(!empty($condition['user_name']) && !empty($condition['password'])){
                $condition['password'] = encrypts($condition['password']);
                // $condition['password'] = '5f3507e4142f79d62d25d23fe0e7579b';

                $where['user_name']  = $condition['user_name'];
                $where['mobile']  = $condition['user_name'];
                $where['_logic'] = 'or';
                $maps['_complex'] = $where;

                $maps['password']=$condition['password'];
               	$admin_info = M('admin')->join('__ADMIN_ROLE__ ON __ADMIN__.role_id=__ADMIN_ROLE__.role_id')->where($maps)->find();
                if(is_array($admin_info)){
                    session('admin_id',$admin_info['admin_id']);
                    session('act_list',$admin_info['act_list']);
                    session('role_id',$admin_info['role_id']);
                    session('store_id',$admin_info['store_id']);
                    $last_login_time = M('admin_log')->where("admin_id = ".$admin_info['admin_id']." and log_info = '后台登录'")->order('log_id desc')->limit(1)->getField('log_time');
                    M('admin')->where("admin_id = ".$admin_info['admin_id'])->save(array('last_login'=>time(),'last_ip'=>  getIP()));
                    session('last_login_time',$last_login_time);                            
                    adminLog('后台登录',__ACTION__);
                    $url = session('from_url') ? session('from_url') : U('Admin/Index/index');
                    exit(json_encode(array('status'=>1,'url'=>$url)));
                }else{
                    exit(json_encode(array('status'=>0,'msg'=>'账号密码不正确')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }
        
        $this->display();
    }
    
    /**
     * 退出登陆
     */
    public function logout(){
        session_unset();
        session_destroy();
        $this->success("退出成功",U('Admin/Admin/login'));
    }
    
    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => true,
            'useNoise' => false,
        	'reset' => false
        );    
        $Verify = new Verify($config);
        //dump($Verify);
        //exit();
        $Verify->entry("admin_login");
    }
    
    public function role(){
    	$list = D('admin_role')->order('role_id desc')->select();
    	$this->assign('list',$list);
    	$this->display();
    }
    
    public function role_info(){
    	$role_id = I('get.role_id');
    	$tree = $detail = array();
    	if($role_id){
    		$detail = M('admin_role')->where("role_id=$role_id")->find();
    		$detail['act_list'] = explode(',', $detail['act_list']);
    		$this->assign('detail',$detail);
    	}
		$right = M('system_menu')->order('id')->select();

       
		foreach ($right as $val){
			if(!empty($detail)){
				$val['enable'] = in_array($val['id'], $detail['act_list']);
			}
			$modules[$val['group']][] = $val;
		}


       // dump($modules);

		//权限组
		$group = array('system'=>'系统设置','content'=>'内容管理','goods'=>'商品中心','member'=>'会员中心','service'=>'会员服务','order'=>'订单中心','marketing'=>'营销推广','tools'=>'插件工具','count'=>'统计报表','admin'=>'管理员管理','inventory'=>'进销存管理','wechat'=>'微信管理'
        );
		$this->assign('group',$group);
		$this->assign('modules',$modules);
    	$this->display();
    }
    
    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
    	if(empty($data['role_id'])){
    		$r = D('admin_role')->add($res);
    	}else{
    		$r = D('admin_role')->where('role_id='.$data['role_id'])->save($res);
    	}
		if($r){
			adminLog('管理角色',__ACTION__);
			$this->success("操作成功!",U('Admin/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->success("操作失败!",U('Admin/Admin/role'));
		}
    }
    
    public function roleDel(){
    	$role_id = I('post.role_id');
    	$admin = D('admin')->where('role_id='.$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("role_id=$role_id")->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }
    
    public function log(){
    	$Log = M('admin_log');
    	$p = I('p',1);
	    $keyword=I('keyword');
	    $maps['user_name']=array('like','%'.$keyword.'%');
        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        
        //展示条数        
        $this->assign('pageCount',$pageCount);

        $count = $Log->where($maps)->join('__ADMIN__ ON __ADMIN__.admin_id =__ADMIN_LOG__.admin_id')->count();
        $Page = new \Think\Page($count,$pageCount);

        $logs = $Log->where($maps)->join('__ADMIN__ ON __ADMIN__.admin_id =__ADMIN_LOG__.admin_id')->order('log_time DESC')
            ->page($p,$pageCount)->select();

        if (I('pageCount')) {
            $Page->parameter['pageCount'] = urlencode(I('pageCount'));
        }
        if (I('keyword')) {
            $Page->parameter['keyword'] = urlencode(urldecode(I('keyword')));
        }

        $this->assign('list',$logs);
        $this->assign('search',I('search'));
    	$show = $Page->show();
    	$this->assign('page',$show); 	
    	$this->display();
    }

	public function dellog(){
		$daytimes=I('daytimes');
		if($daytimes==1){
			$time=time()-7*24*3600;
			$map['log_time']=array('lt',$time);
		}elseif($daytimes==2){
			$time=time()-30*24*3600;
			$map['log_time']=array('lt',$time);
		}elseif($daytimes==3){
			$time=time()-90*24*3600;
			$map['log_time']=array('lt',$time);
		}elseif($daytimes==4){
			$time=time()-180*24*3600;
			$map['log_time']=array('lt',$time);
		}

		$res=M('admin_log')->where($map)->delete();
		if($res){
			$this->success("操作成功");
		}else{
			$this->error("操作失败");
		}
	}

	/**
	 * 服务顾问列表
	 */
	public function supplier()
    {
        $Admin = M('admin');
        $p = I('p',1);
        $maps['role_id']=5;
        $orderby=$_REQUEST['orderby'];
        $sort=$_REQUEST['sort'];

        if(empty($orderby)){
            $orderbys='store_id asc,admin_id desc';
        }else{
            //$orderbys="'";
            $orderbys.=$orderby;
            $orderbys.=" ";
            $orderbys.=$sort;
            //$orderbys.="'";

            if($orderby=='star'){
                $this->assign('starsort',$sort);
            }
            if($orderby=='zan'){
                $this->assign('zansort',$sort);
            }
        }
        $condition = array();
        if(!empty(I('user_name'))){
            $condition['user_name']=array('like','%'.urldecode(I('user_name')).'%');
            $this->assign('user_name',urldecode(I('user_name')));
        }

        if(!empty(I('store_id'))){
            $condition['store_id']=I('store_id');
            $this->assign('store_id',urldecode(I('store_id')));
        }

        $pageCount = I('pageCount');
        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $count = $Admin->where($maps)->where($condition)->count();

        $Page = new \Think\Page($count,$pageCount);

        //展示条数        
        $this->assign('pageCount',$pageCount);

        $admin = D('admin')->where($maps)->where($condition)->order($orderbys)->page($Page->firstRow.','.$Page->listRows)->select();

        foreach ($admin as $key => $value) {
            $maps['store_id']=$value['store_id'];
            $store=M("store")->where($maps)->find();
            $admin[$key]['store_name']=$store['store_name'];
        }

        if (I('pageCount')) {
            $Page->parameter['pageCount'] = urlencode(I('pageCount'));
        }
        if (I('orderby')) {
            $Page->parameter['orderby'] = urlencode(urldecode(I('orderby')));
        }
        if (I('user_name')) {
            $Page->parameter['user_name'] = urlencode(urldecode(I('user_name')));
        }
        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(I('store_id'));
        }
        //所有门店列表
        $store = D('store')->field('store_name,store_id')->select();
        
        $mmps['role_id']=5;
        $count = $Admin->where($mmps)->where($condition)->count();
        
        $show = $Page->show();
        $this->assign('p',I('p'));   
        $this->assign('page',$show);    
        $this->assign('search',I('search'));
        $this->assign('store',$store);
        $this->assign('list',$admin);
        $this->display();
    }

	/**
	 * 供应商资料
	 */
	public function supplier_info()
	{
		$admin_id = I('get.admin_id', 0);
		if ($admin_id) {

			$admin_model = M('admin');

            $maps['admin_id']=$admin_id;
			
            $info=$admin_model->where($maps)->find();
        

			$this->assign('info', $info);
		}
		$act = empty($admin_id) ? 'add' : 'edit';
		$this->assign('act', $act);
		$admin = M('store')->field('store_id,store_name')->where('1=1')->select();
		$this->assign('admin', $admin);
		$this->display();
	}

	/**
	 * 供应商增删改
	 */
	public function supplierHandle()
	{
		$data = I('post.');
        if(empty($data['password'])){
            unset($data['password']);
        }else{
            $data['password'] = encrypts($data['password']);
        }
		$suppliers_model = M('admin');
		//增
		if ($data['act'] == 'add') {
			unset($data['suppliers_id']);
			$count = $suppliers_model->where("user_name='" . $data['user_name'] . "'")->count();
			if ($count) {
				$this->error("此服务顾问已被注册，请更换", U('Admin/Admin/supplier_info'));
			} 

            $count = $suppliers_model->where("mobile='" . $data['mobile'] . "'")->count();
            if ($count) {
                $this->error("此手机号码已被注册，请更换", U('Admin/Admin/supplier_info'));
            } 
            $data['add_time']=time();
            $r = $suppliers_model->add($data);
		}
		//改
		if ($data['act'] == 'edit' && $data['admin_id'] > 0) {
			
            $maps['admin_id']=$data['admin_id'];

            $adm= D('admin')->where('admin_id='.$data['admin_id'])->find();
            $adminids=$adm['admin_id'];
            if($adm['user_name']!=$data['user_name']){

                if($username=D('admin')->where("user_name='".$data['user_name']."'")->find()){
                    $this->error("此用户名已存在，请更换",U("Admin/Admin/supplier_info/admin_id/".$adminids.""));
                }

            }

            if($adm['mobile']!=$data['mobile']){

                if(D('admin')->where("mobile='".$data['mobile']."'")->find()){
                    
                    $this->error("此手机号码已存在，请更换",U("Admin/Admin/supplier_info/admin_id/".$adminids.""));
                }

            }


            $r = $suppliers_model->where('admin_id=' . $data['admin_id'])->save($data);
			
		}
		//删
		if ($data['act'] == 'del' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('admin_id=' . $data['suppliers_id'])->delete();
		}

		if ($r !== false) {
			$this->success("操作成功", U('Admin/Admin/supplier'));
		} else {
			$this->error("操作失败", U('Admin/Admin/supplier'));
		}
	}
}