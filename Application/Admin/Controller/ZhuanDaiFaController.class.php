<?php
namespace Admin\Controller;
use Think\AjaxPage;
use Think\Controller;
set_time_limit(300);
class ZhuanDaiFaController extends BaseController {
	// 商品上下限列表
	public function index()
	{	
		$condition = array();
		$condition['deposit_id'] = I("id");
        $count = D("zhuandaifa")->where($condition)->count();

        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page  = new \Think\Page($count,$pageCount);    

        $info = D("zhuandaifa")->where($condition)->order('create_time asc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($info as $key => $val) {
            $info[$key]['sn'] = M('deposit_list')->where('id='.$condition['deposit_id'])->getField('sn');
        }
        $show = $Page->show();

		$this->assign('info',$info);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('p',I('p'));
        $this->display();
	}

}