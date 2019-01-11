<?php
/**    
 * Date: 2017-09-05
 */
namespace Admin\Controller;
use Think\AjaxPage;

class RepertoryController extends BaseController {
	// 仓库列表
	public function index()
	{	
		$condition = array();
        if(!empty($_POST['repertory_name'])){
            $condition['repertory_name']=array('like','%'.$_POST['repertory_name'].'%');
        }
        $condition['is_del']=0;
        $count = M('repertory')->where($condition)->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page  = new \Think\Page($count,$pageCount);         
        $show = $Page->show();
        $result = M('repertory')->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('info',$result);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('p',I('p'));
        $this->display();
	}

    // 新增仓库
    public function add()
    {   
        if(IS_POST){
            $data['repertory_name'] = I('post.repertory_name');
            $data['repertory_name_short'] = I('post.repertory_name_short');
            $data['address'] = I('post.address');
            $data['remark'] = I('post.remark');
            $data['status'] = 0;
            $data['create_time'] = time();
            $data['creator_id'] = session('admin_id');
            $admin_id = session('admin_id');//当前用户ID
            if ($admin_id) {
                $data['creator'] = M('admin')->where('admin_id='.$admin_id)->getField('user_name');
            }
            $row =  M('repertory')->add($data);
            if($row){
                $this->success('新增成功',U('index'));
            }else{
                $this->error('新增失败');
            }
            exit;
        }
        $this->display();
    }


	// 修改仓库
	public function update()
	{	
		$id = I('get.id');
		$result = M('repertory')->where('id='.$id)->find();
		if(!$result){
            exit($this->error('不存在该仓库'));
        }
        $p = I('get.p');
        if(IS_POST){
        	$data['repertory_name'] = I('post.repertory_name');
        	$data['repertory_name_short'] = I('post.repertory_name_short');
        	$data['address'] = I('post.address');
        	$data['remark'] = I('post.remark');
        	$data['update_time'] = time();
        	$data['id'] = I('post.id');

        	$admin_id = session('admin_id');//当前用户ID
        	if ($admin_id) {
        		$data['last_creator'] = M('admin')->where('admin_id='.$admin_id)->getField('user_name');
        	}
        	$row =  M('repertory')->save($data);
            if($row){
                $this->success('修改成功',U('index',array('p'=>$p)));
            }else{
                $this->error('修改失败');
            }
            exit;
        }
		$this->assign('info',$result);
    	$this->display();
		
	}

    // 删除仓库
    public function delData()
    {   
        $id = I('post.id');
        $data['is_del'] = 1;
        $data['update_time'] = time();
        $data['last_creator'] = time();
        $admin_id = session('admin_id');//当前用户ID
        $arr = array();
        if ($admin_id) {
            $data['last_creator'] = M('admin')->where('admin_id='.$admin_id)->getField('user_name');
        }
        $result = M('repertory')->where('id='.$id)->save($data);
        if ($result) {
            $arr['success'] = 1;
        }else{
            $arr['info'] = "删除失败!";
            $arr['success'] = 0;
        }
        echo json_encode($arr); 
        die;
        
    }

    //禁用
    public function forbid_repertory(){
        if(IS_POST){
            $arr = array();
            $id = I('get.id');
            if(!$id){
                $arr['info'] = "缺少参数值";
                $arr['success'] = 0;
            }else{
                $model = D('repertory');
                $model->status = 1;
                $result = $model->where('id='.$id)->save();
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
    //启用
    public function start_repertory(){
        if(IS_POST){
            $id = I('get.id');
            if(!$id){
                $arr['info'] = "缺少参数值";
                $arr['success'] = 0;
            }else{
                $model = D('repertory');
                $model->status = 0;
                $result = $model->where('id='.$id)->save();
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