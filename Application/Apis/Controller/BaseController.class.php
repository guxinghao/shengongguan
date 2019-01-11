<?php

/**
 *  Author: eric yang     
 * Date: 2017-05-15
 */

namespace Apis\Controller;
use Think\Controller;

class BaseController extends Controller {  
    /*
     * 初始化操作
     */
    public function _initialize() 
    {   
        $uniqueKey=$_REQUEST['uniquekey'];

        if(empty($uniqueKey)){
             $news = array('code' =>8 ,'msg'=>'用户信息有误！','data'=>null);
             echo json_encode($news,true);exit;
        }

        $uniqueKey1=explode("_", $uniqueKey);

        $uidss=$uniqueKey1['0'];
        $maps11['uid']=$uidss;

        $loginss=D("login_log")->where($maps11)->order('id desc')->find();
       
        if($loginss['uniquekey']!=$uniqueKey){
              $news = array('code' =>9 ,'msg'=>'账号在其他地方登陆','data'=>null);
               echo json_encode($news,true);exit;
        }
       
    }

    /*
     * 判断有无权限此操作
     * user_id  用户ID
     * role_name  查询权限名称
     */
    
    public function AutoCheckRole($user_id,$role_name)
    {   
        $roleInfo = M('admin')->where('admin_id='.$user_id)->getField('admin_id, role_id, user_name');
        $role_id = $roleInfo[$user_id]['role_id'];

        // 查询role_id
        $roleId = M('admin_role')->where('role_name="'.$role_name.'"')->getField('role_id');
        $user_name = $roleInfo[$user_id]['user_name'];
        if ($role_id != $roleId ) {
            $news = array('code' =>0 ,'msg'=>'无权限此操作！','data'=>null);
            echo json_encode($news,true);exit;
        }
        return $user_name;
    }


    /*
     * 检查库存数量是否充足
     * goods_id    商品ID
     * goods_num   商品数量
     * resource_id 仓库 或者门店ID
     * stock_type  类型 1 总部库存 4门店 默认仓库
     */
    
    public function checkGoodsNum($goods_id,$goods_num,$resource_id,$stock_type=1)
    {   
        $map['good_id'] = $goods_id;
        if ($resource_id) {
            $map['resource_id'] = $resource_id;
        }
        $map['stock_type'] = $stock_type;
        // 获取库存数量
        $num = M('stock')->where($map)->getField('number');
        $lock_number = M('stock')->where($map)->getField('lock_number');
        $realnum = $num - $lock_number;
        if ($realnum>=$goods_num) {
            return true;
        }else{
            $news = array('code' =>0 ,'msg'=>'库存不足','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    /*
     * 检查库存数量是否充足(门店退货发货时使用)
     * goods_id    商品ID
     * goods_num   商品数量
     * resource_id 仓库 或者门店ID
     * stock_type  类型 1 总部库存 4门店 默认仓库
     */
    
    public function checkGoodsNum_return($goods_id,$goods_num,$resource_id,$stock_type=1)
    {   
        $map['good_id'] = $goods_id;
        if ($resource_id) {
            $map['resource_id'] = $resource_id;
        }
        $map['stock_type'] = $stock_type;
        // 获取库存数量
        $num = M('stock')->where($map)->getField('number');
        // $lock_number = M('stock')->where($map)->getField('lock_number');
        // $realnum = $num - $lock_number;
        if ($num>=$goods_num) {
            return true;
        }else{
            $news = array('code' =>0 ,'msg'=>'库存不足','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    /*
     * 检查单个商品单个仓库库存数量是否满足
     * goods_id    商品ID
     * goods_num   商品数量
     * resource_id 仓库 或者门店ID
     * stock_type  类型 1 总部库存 4门店 默认仓库
     */
    
    public function checkAllGoodsNum($goods_id,$goods_num,$resource_id,$stock_type=1)
    {   
        $map['good_id'] = $goods_id;
        
        $map['number'] = array('egt',$goods_num);
        $map['stock_type'] = $stock_type;
        // 获取库存数量
        $result = M('stock')->where($map)->select();
        if ($result) {
            return true;
        }else{
            $news = array('code' =>0 ,'msg'=>'库存不足','data'=>null);
            echo json_encode($news,true);exit;
        }
    }

    /**
     * 仓库端入库操作
     * in_storage_type  入库类型 1
     */
    public function in_storage_stocks()
    {
        $in_storage_type = $_REQUEST['type'];//入库类型
        $repertory_id = $_REQUEST['repertory_id'];//仓库id
        $user_id = $_REQUEST['user_id'];//操作人ID
        $goods_list = $_REQUEST['goods_list'];//货物列表
        $user_name = $this->AutoCheckRole($user_id,'库管');
        // 新增入库记录
        $data['in_storage_type'] = $in_storage_type;
        $data['repertory_id'] = $repertory_id;
        $data['create_time'] = time();
        $data['creator'] = $user_name;
        $result = M('in_storage_stocks')->add($data);
        if ($result) {
            // 新增入库记录详情
            $arr = json_decode($goods_list);//确认发货信息  数组
            $length = count($arr);
            $where['storage_stocks_id'] = $result;  //入库记录ID
            for ($i=0; $i < $length; $i++) { 
                $num = $arr[$i]->goods_num;             //商品数量
            }
        }
    }


}