<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-25
 */
namespace Admin\Controller;
use Think\Controller;

class PlanController extends Controller {

    //设置定时发放生日短信脚本
    public function sendBirthMessage(){
        //查找设定的生日券
        $where['status'] = 1;
        $brith_reminder = M('brith_reminder')->where($where)->order('add_time desc')->find();
        //判断有没有设定生日提醒 如果没有 则不做操作
        if ($brith_reminder) {
            
            //查找生日券内容
            $coupon_id['id'] = $brith_reminder['coupon_id'];
            $result = M('coupon')->where($coupon_id)->find();

            //获取商品单位
            $spu = getSpu($result['goods_id']);
            if (!$spu) {
                $spu = '份';
            }
            //查找符合条件的会员
            $condition['is_del'] = 0;

            $now_date = date("m-d",strtotime("+7 day"));
            $condition['_string'] = "unix_timestamp(concat('1970-',SUBSTRING_INDEX(birthdays,'-',-2))) = unix_timestamp('1970-".$now_date."')";
            $users = M('users')->where($condition)->field('user_id,nickname,mobile,birthdays')->select();
            //获取有效期结束时间
            $last_date = date("Y-m-d",strtotime("+15 day"));

            $i = 0;

            foreach ($users as $key => $val) {
                //短信模板
                $content="尊敬的会员".$val['nickname']."，您的生日快到了，晓芹海参为您准备了礼物（".$result['goods_name']." ".$result['createnum'].$spu."），凭短信到门店免费领取，".$last_date."前有效";
                $res = sendsmss($val['mobile'],$content);
                //如果成功  则新增 会员优惠劵记录
                if ($res) {
                    $code = date('YmdHis').rand(1000,9999);
                    $info['cid'] = $coupon_id['id'];
                    $info['type'] = 4;
                    $info['uid'] = $val['user_id'];
                    $info['code'] = $code;
                    $info['send_time'] = time();
                    $op = M('coupon_list')->add($info);
                    $i++;
                }
            }

            //修改优惠券表发放数量
            $old_number = $result['send_num'];//原始数量
            $new_number = $i;//新增数量
            $new['send_num'] = $old_number+$new_number;//最终数量
            $result = M('coupon')->where($coupon_id)->save($new);
            exit;
        }
    }

    //设置沟通列表修改状态
    public function update_communction(){
        //查找没有填写的沟通记录
        $maps['com_status'] = 1;
        $nowdate = date('Y-m-d',time());
        $time = strtotime($nowdate)-24*60*60*3;
        $maps['create_time'] = array('lt',$time);
        $result = M('communication')->field('id')->where($maps)->select();
        //修改沟通状态
        if ($result) {
            foreach ($result as $key => $value) {
                $data['com_status'] = 2;
                $data['update_time'] = time();
                $where['id'] = $value['id'];
                M('communication')->where($where)->save($data);
            }
        }
        exit;
    }
}