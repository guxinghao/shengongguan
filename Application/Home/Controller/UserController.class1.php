<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-22
 */namespace Home\Controller;
use Think\Page;
use Think\Verify;
class UserController extends BaseController {
    
 
    //食参客
    //海参下午茶
    public function index(){
        $this->display();
    }
        //活动列表
        public function activity_1(){
            $info = M('activity')->order('update_time desc')->limit("10")->select();
            foreach ($info as $key => $value) {
                $mapss['activity_id']=$value['activity_id'];
                $mapss['status']=1;
                $counts=D("sign_up")->where($mapss)->count();            
                $info[$key]['enter_count']= $counts;               
            }
            $this->assign('info',$info);
            $this->display();
        }
        public function ajax_activity_1(){
            $page = $_REQUEST['p'];
            $start = $page*10;     
            $info = M('activity')->order('update_time desc')->limit("$start,10")->select();
            $this->assign('info',$info);
            $this->display();
    }
        //马上预约
        public function activity(){
            $activity_id = $_REQUEST['activity_id'];
            $maps['activity_id'] = $activity_id;
            $info = M('activity')->where($maps)->find();
            $this->assign('info',$info);
            $this->display();
        }
            //支付
            public function pay(){
                session('uid',1);
                $uid=session('uid');
                $activity_id = $_REQUEST['activity_id'];
                $maps['activity_id'] = $activity_id;
                $info = M('activity')->where($maps)->find();
                $maps['uid']=$uid;
                $maps['status'] = 1;
                $res=D("sign_up")->where($maps)->find();
                if($res==false){
                    $r['sn'] = date('YmdHis').rand(1000,9999);
                    $r['uid'] = $uid;
                    $r['activity_id'] = $activity_id;
                    $r['price'] = $info['price1'];
                    $r['status'] = 0;
                    $r['create_time'] = time();
                    D("sign_up")->add($r);
                    $this->assign("info",$info);
                    $this->display();

                    
                }else{
                $this->assign("info",$info);
                 $this->success("已支付",U('Home/User/activity'));
                }
            }
                //预约成功
                public function imm_order(){
                    $this->display();
                }
        //下次预约
        public function apply(){
            $this->display();
        }
        public function apply_a(){
            $name = $_REQUEST['name'];
            $mobile = $_REQUEST['mobile'];
            session('uid',4);
            $uid=session('uid');
            $maps['uid']=$uid;
            $res=D("appaly")->where($maps)->find();
            if($res==false){
                $r['sn'] = date('YmdHis').rand(1000,9999);
                $r['uid'] = $uid;
                $r['activity_id'] = 0;
                $r['price'] = 0;
                $r['status'] = 0;
                $r['create_time'] = time();
                $r['mobile'] = $mobile;
                $r['name'] = $name;
                D("appaly")->add($r);
                $this->success("报名成功",U('Home/User/index'));
            }else{
                $this->error('报名失败');
            }   
        }


    //食参客分享
    public function Diners_share(){
        session('uid',1);
        $url = C('url');
        $type = $_REQUEST['type'];
        if($type==1){
            $maps['is_top'] = 1;
            $info = D('share')->where($maps)->order("id desc")->limit("10")->select();
        }else if($type==2){
            $maps['user_id'] = session("uid");
            $info = D('share')->where($maps)->order("id desc")->limit("10")->select();
        }else{
            $info = D('share')->order("id desc")->limit("10")->select();
        }
        
        foreach ($info as $key => $value) {
            if($value['cate_id']==1){
            $info[$key]['t'] = "泡发窍门";
        }else if($value['cate_id']==2){
            $info[$key]['t'] = "厨艺分享";
        }else if($value['cate_id']==3){
            $info[$key]['t'] = "食参感受";
        }else if($value['cate_id']==4){
            $info[$key]['t'] = "其他分享";           
        }
        $info[$key]['zana'] = $value['zan'] + 1;
        $where['pid'] =$value['id'];        
        $info[$key]['data'] = M('share_pic')->where($where)->order("id desc")->limit("3")->select();
    }
        $this->assign('url',$url);
        $this->assign('info',$info);
        $this->display();
    }
    public function ajax_Diners(){
        $page = $_REQUEST['p'];
        $start = $page*10;     

        session('uid',1);
        $url = C('url');
        $type = $_REQUEST['type'];
        if($type==1){
            $maps['is_top'] = 1;
            $info = D('share')->where($maps)->order("id desc")->limit("$start,10")->select();
        }else if($type==2){
            $maps['user_id'] = session("uid");
            $info = D('share')->where($maps)->order("id desc")->limit("$start,10")->select();
        }else{
            $info = D('share')->order("id desc")->limit("$start,10")->select();
        }        
        foreach ($info as $key => $value) {
            if($value['cate_id']==1){
            $info[$key]['t'] = "泡发窍门";
        }else if($value['cate_id']==2){
            $info[$key]['t'] = "厨艺分享";
        }else if($value['cate_id']==3){
            $info[$key]['t'] = "食参感受";
        }else if($value['cate_id']==4){
            $info[$key]['t'] = "其他分享";           
        }
        $info[$key]['zana'] = $value['zan'] + 1;
        $where['pid'] =$value['id'];        
        $info[$key]['data'] = M('share_pic')->where($where)->order("id desc")->limit("3")->select();
    }
   
        $this->assign('url',$url);
        $this->assign('info',$info);
        $this->display();
    }
        //我要分享
        public function share(){
            $this->display();
        }
        public function share_a(){
            session('uid',1);
            $r['title']= $_REQUEST['title'];
            $r['cate_id'] = $_REQUEST['cate_id'];
            $r['content'] = $_REQUEST['content'];
            $r['create_time'] = time();
            $r['update_time'] = time();
            $r['user_id'] = session('uid');
            $pics = $_REQUEST['images'];
            $add = M('share')->add($r);
            $info = M('share')->where($r)->find();
            foreach ($pics as $key => $value) {
                $maps['pid'] = $info['id'];
                $maps['image'] = $pics[$key];
                $maps['create_time'] = time();
                $pic = M('share_pic')->add($maps);

            }          
            $this->success("分享成功",U('Home/User/Diners_share'));
        }

    //评价你的顾问（没找到）
    // public function Diners_share(){
    //     $this->display();
    // }
        //服务顾问详情
        public function consultant(){
        $this->display();
        }
        //投诉
        public function complaint (){
        $this->display();
        }

    //参观原产地
    public function visit(){
        $this->display();
    }
         //马上预约
        public function order(){
            $this->display();
        }
                // //预约成功（重复）
                // public function imm_order(){
                //     $this->display();
                // }

     //vip会员商城
    public function vip_index(){
        $info = M('goods')->where('is_recommend=1')->order('last_update desc')->limit('10')->select();
        $this->assign('info',$info);
        $this->display();
    }
    public function ajax_vip(){
        $page = $_REQUEST['p'];
        $start = $page*10;    
        $info = M('goods')->where('is_recommend=1')->order('last_update desc')->limit("$start,10")->select();
        $this->assign('info',$info);
        $this->display();
    }

            //商品详情
            public function product_Details(){
                $goods_id = $_REQUEST['id'];
                $maps['goods_id'] = $goods_id;
                $info = M('goods')->where($maps)->find();
                $this->assign('info',$info);

                $this->display();
             }
        //购物车
        public function shopCar(){
            $this->display();
        }  
            //确定订单
            public function confirmation(){
                $this->display();
            }
                //选择优惠劵
                public function discount_coupon_1(){
                    $this->display();
                }
                //提交成功
                public function sub_order(){
                    $this->display();
                }
                //地址列表
                public function site_list(){
                    $this->display();
                }
                    //新增地址
                    public function new_addi(){
                        $this->display();
                    }
    public function doUpload(){      
        /****
        *获取活动id
        ****/
        set_time_limit(0);
      
        //这里判断文件是否符合规则
        $uploadFile = $_FILES['file'];
        if(empty($uploadFile)){
            $this->ajaxReturn(array('status' => -1, 'info' => '上传失败'));
        }
        if($uploadFile['size'] == 0){
            $this->ajaxReturn(array('status' => -1, 'info' => '上传失败'));
        }
        if($uploadFile['size'] < $minSize*1024*1024){
            $this->ajaxReturn(array('status' => -1, 'info' => '文件不能小于'.$minSize.'MB'));
            //return false;
            //echo json_encode(array('success' => 0, 'error' => '文件不能小于'.$minSize.'MB'));exit;
        }
        $img_info = getimagesize($uploadFile['tmp_name']);
        if($img_info['0'] < $minWidth){
            $this->ajaxReturn(array('status' => -2, 'info' => '文件不能小于'.$minSize.'MB'));
            //echo json_encode(array('success' => 0, 'error' => '宽不能低于'.$minWidth.'px'));exit;
        }else if($img_info['1'] < $minHeight){
            $this->ajaxReturn(array('status' => -3, 'info' => '文件不能小于'.$minSize.'MB'));
            //echo json_encode(array('success' => 0, 'error' => '高不能低于'.$minHeight.'px'));exit;
        }
        $uploadList = $this->upload($_FILES);
       
        $this->ajaxReturn(array('status' => 1, 'info' => $uploadList['file']));
        /*$data['mid'] = $matchlist['id'];
        $data['uid'] = $_SESSION['uid'];
        $data['author'] = $_SESSION['name'];
        $data['status'] = 0;
        $res = M("pics")->where($data)->order('id desc')->find();

        $dat['savepath'] = $uploadList[0]['savename'];
        $dat['size']     = $uploadList[0]['size'];
        $dat['pid']      = $res['id'];
        $dat['uid']      = $_SESSION['uid'];
        $ress = D("Attach")->add($dat);
        
        //判断如果有添加数据成功更新一条
        if($ress){
            $datas['pic'] = $dat['savepath'];
            M("pics")->where(' id = '.$res['id'])->save($datas);
        }
        echo $res;*/
    }

    public function upload(){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Public/upload/news/'; // 设置附件上传根目录
        $upload->savePath  =     ''; // 设置附件上传（子）目录
        // 上传文件 
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
        }else{// 上传成功
            return $info;    
        }
    }
    public function do_a(){
        $id = $_REQUEST['id'];
        $maps['id'] = $id;
        $add = M('share')->where($maps)->find();
        $add['zan'] =  $add['zan'] + 1;
        M('share')->where($maps)->save($add);
        $this->display('Diners_share');
    }
    public function do_b(){
        $id = $_REQUEST['id'];
        $maps['id'] = $id;
        $add = M('share')->where($maps)->find();
        $add['zan'] =  $add['zan'] - 1;
        M('share')->where($maps)->save($add);
        $this->display('Diners_share');
    }
}