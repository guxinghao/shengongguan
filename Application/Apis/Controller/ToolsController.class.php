<?php
/**
 * Author: yangxiao      
 * Date: 2017-05-25
 */
namespace Apis\Controller;
use Think\Controller;

class ToolsController extends Controller {

    	public function setFengge(){

    	

    		$mytime1= date("Y-m-d H:i:s", strtotime("-1 year"));
    		$mytime2= date("Y-m-d H:i:s", strtotime("-2 year"));

    		//2年未买东西
    		$maps['end_time']=array('lt',$mytime2);
    		$maps['fengge_id']=array('neq',8);

    		$user=D("users")->where($maps)->select();

    		foreach ($user as $key => $value) {
    			  $vdata1['user_id']=$value['user_id'];

    			  $vdata1['fengge_id']=8;

    			  $list=D("users")->save($vdata1);
    			  if($list){
					$vdata['old_fengge']=$value['fengge_id'];
					$vdata['new_fengge']=8;
					$vdata['create_time']=time();
					$vdata['uid']=$value['user_id'];

					D("fengge_log")->add($vdata);

    			  }

    		}


    		//2年未买东西
    		$maps1['end_time']=array('lt',$mytime2);
    		$maps1['fengge_id']=array('neq',7);

    		$user1=D("users")->where($maps1)->select();


    		foreach ($user1 as $keys => $val) {
    			  $vdata2['user_id']=$val['user_id'];

    			  $vdata2['fengge_id']=7;

    			  $list1=D("users")->save($vdata2);
    			  if($list1){
					$vdata['old_fengge']=$value['fengge_id'];
					$vdata['new_fengge']=8;
					$vdata['create_time']=time();
					$vdata['uid']=$val['user_id'];

					D("fengge_log")->add($vdata);

    			  }

    		}

    		$maps1['end_time']=array('lt',$mytime2);
    		$maps2['fengge_id']=array('in','7,8');
    		$user2=D("users")->where($maps2)->select();


    		foreach ($user2 as $keys => $vals) {
    			  $vdata2['user_id']=$vals['user_id'];

    			  $vdata2['fengge_id']=10;

    			  $list1=D("users")->save($vdata2);
    			  if($list1){
					$vdata['old_fengge']=$value['fengge_id'];
					$vdata['new_fengge']=10;
					$vdata['create_time']=time();
					$vdata['uid']=$vals['user_id'];

					D("fengge_log")->add($vdata);

    			  }

    		}

    	}

        public function getTt(){

            $count=getTypes("3328");
            
        }

        //获取验证码
    public function getCode(){

        sendsmss('13585772389','13434');
    }
    //请冻结积分
    public function clearFrozenPionts(){

      
        $res=strpos($_SERVER['REMOTE_ADDR'],"192.168.");

         $pos = strpos($mystring, $findme);
        
        if($res===false){
            $this->error("非法操作！");
        }else{
           $maps['frozen_points']=array('gt',0);
            $data['frozen_points']=0;

            D("Users")->where($maps)->save($data);
        }
    }
    //异步发短信
    public function doSendSms(){

        $maps['status']=0;
        $sendlist=D("sendsms_log")->where($maps)->limit('5')->select();
        
        if($sendlist){
            foreach ($sendlist as $key => $value) {
               $res=doSendSmss($value['mobile'],$value['content']);
               
               if($res){
                    $where['id']=$value['id'];
                    $vdata['status']=1;
                    $vdata['update_time']=time();
                    D('sendsms_log')->where($where)->save($vdata);
               }
            }

        }
        

    }

    public function doSms(){

         $interval=5; // 每隔5分钟运行 
        do{ 
            $this->doSendSms();   
            sleep($interval); // 等待5分钟 
        }while(true); 
    }

    public  function  logger($word)
    {
        $log_filename = "log.xml";
        $fp = fopen($log_filename,"a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,"执行日期：".strftime("%Y-%m-%d-%H：%M：%S",time())."\n".$word."\n\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }


        //测试
        public function test(){
           
           $interval=5; // 每隔5分钟运行 
            do{ 
                $vdata['mobile']="13585772311";
                $vdata['session_id']=1;
                $vdata['code']=1123;
                $vdata['add_time']=time();
                D('sendsms_log')->add($vdata);
                sleep($interval); // 等待5分钟 
            }while(true); 
        }

}