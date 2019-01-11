<?php
/**    
 * Date: 2017-11-06
 */
namespace Admin\Controller;
use Think\AjaxPage;
use Think\Controller;
class PosManageController extends BaseController {

	//pos机打印时提示语句
	public function index(){
		$result = M('marked_words')->select();
		$this->assign('info',$result);
		$this->display();
	}


	public function update(){
		$id = I('id');
		$map['id'] = $id;
		$result = M('marked_words')->where($map)->find();
		if ($_POST) {
			$data['title'] = I('title');//标题
			$data['content'] = I('content');//提示内容
			$userInfo = getAdminInfo(session('admin_id'));
        	$userName = $userInfo['user_name'];
			$data['creator'] = $userName;//最后一次修改人
			$data['update_time'] = time();//最后一次更新时间
			$re = M('marked_words')->where($map)->save($data);
			if ($re) {
				$this->success('修改成功!');
				exit;
			}else{
				$this->error('修改失败!');
				exit;
			}
		}
		$this->assign('info',$result);
		$this->display();
	}

}