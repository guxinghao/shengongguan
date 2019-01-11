<?php
/** 
 * 会员服务->发票列表
 * Date: 2017-09-30
 */
namespace Admin\Controller;

use Think\Page;
use Think\Verify;

class InvoiceController extends BaseController {
	// 发票列表
	public function index()
	{	

		// 发票名称查询
		if (I('fapiao_name')) {
			$where['fapiao_name'] = array('like',"%".urldecode(I('fapiao_name'))."%");
			$this->assign('fapiao_name',urldecode(I('fapiao_name')));
		}
		// 门店查询条件
        if(!empty(I('store_id'))){
            $where['store_id'] = I('store_id');
            $this->assign('store_id',I('store_id'));
        }

        $role_id = session('role_id');
        if ($role_id==4) {
            $where['store_id'] = session('store_id');
            $this->assign('role_id',$role_id);
        }

        //是否删除
        $where['is_del'] = 0;

		$count = M('invoice')->where($where)->count();
		$pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        //展示条数        
        $this->assign('pageCount',$pageCount);

		$Page  = new \Think\Page($count,$pageCount);         
		$result = M('invoice')->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach ($result as $key => $val) {
			$map['order_id'] = $val['order_id'];
			// 订单编号
			$order_sn = M('order')->where($map)->getField('order_sn');
			$result[$key]['order_sn'] = $order_sn;
			// 用户名
			$mapp['user_id'] = $val['user_id'];
			$user_name = M('users')->where($mapp)->getField('nickname');
			$result[$key]['user_name'] = $user_name;

			// 操作人
			$mapps['admin_id'] = $val['c_id'];
			$admin_name = M('admin')->where($mapps)->getField('user_name');
			$result[$key]['admin_name'] = $admin_name;

			// 门店名称
			$mappss['store_id'] = $val['store_id'];
			$store_name = M('store')->where($mappss)->getField('store_name');
			$result[$key]['store_name'] = $store_name;
		}

		if (I('fapiao_name')) {
        	$Page->parameter['fapiao_name'] = urlencode(urldecode(I('fapiao_name')));
		}
		if (I('store_id')) {
        	$Page->parameter['store_id'] = urlencode(I('store_id'));
		}
		
        $show = $Page->show();

		//所有门店列表
        $store = D('store')->field('store_name,store_id')->select();

        $this->assign('store',$store);
        $this->assign('page',$show);
       	$this->assign('search',I('search'));
		$this->assign('result',$result);
		$this->display();
	}


	// 删除记录
    public function delData()
    {   
        $id = I('post.id');
        $data['is_del'] = 1;
        $arr = array();
        $result = M('invoice')->where('id='.$id)->save($data);
        if ($result) {
            $arr['success'] = 1;
        }else{
            $arr['info'] = "删除失败!";
            $arr['success'] = 0;
        }
        echo json_encode($arr); 
        die; 
    }

}