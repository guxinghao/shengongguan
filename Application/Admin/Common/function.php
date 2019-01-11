<?php
/**
 *  Author: eric yang     
 * Date: 2017-05-15
 */

/**
 * 管理员操作记录
 * @param $log_url 操作URL
 * @param $log_info 记录信息
 */
function adminLog($log_info){
    $add['log_time'] = time();
    $add['admin_id'] = session('admin_id');
    $add['log_info'] = $log_info;
    $add['log_ip'] = getIP();
    $add['log_url'] = __ACTION__;
    M('admin_log')->add($add);
}

function utf_substr($str,$len,$endString=""){


    for($i=0;$i<$len;$i++){
        $temp_str=substr($str,0,1);
        if(ord($temp_str) > 127){
            $i++;
            if($i<$len){
                $new_str[]=substr($str,0,3);
                $str=substr($str,3);
            }
        }else{
            $new_str[]=substr($str,0,1);
            $str=substr($str,1);
        }
    }
    if($str>mb_strlen($str,"utf_substr")){
    	return join($new_str).$endString;
    }else{
    	return join($new_str);
    }
    
}

function getAdminInfo($admin_id){
	return D('admin')->where("admin_id=$admin_id")->find();
}


/**
 * 面包屑导航  用于后台管理
 * 根据当前的控制器名称 和 action 方法
 */
function navigate_admin()
{        
    $navigate = include APP_PATH.'Common/Conf/navigate.php';    
    $location = strtolower('Admin/'.CONTROLLER_NAME);
    $arr = array(
        '后台首页'=>'javascript:void();',
        $navigate[$location]['name']=>'javascript:void();',
        $navigate[$location]['action'][ACTION_NAME]=>'javascript:void();',
    );
    return $arr;
}

/**
 * 导出excel
 * @param $strTable	表格内容
 * @param $filename 文件名
 */
function downloadExcel($strTable,$filename)
{
	header("Content-type: application/vnd.ms-excel");
	header("Content-Type: application/force-download");
	header("Content-Disposition: attachment; filename=".$filename."_".date('Y-m-d').".xls");
	header('Expires:0');
	header('Pragma:public');
	echo '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.$strTable.'</html>';
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
	$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
	for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
	return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 根据id获取地区名字
 * @param $regionId id
 */
function getRegionName($regionId){
    $data = M('region')->where(array('id'=>$regionId))->field('name')->find();
    return $data['name'];
}

function getMenuList($act_list){
	//根据角色权限过滤菜单
	$menu_list = getAllMenu();
	if($act_list != 'all'){
		if ($act_list) {
			$right = M('system_menu')->where("id in ($act_list)")->cache(true)->getField('right',true);
		}
		foreach ($right as $val){
			$role_right .= $val.',';
		}
		$role_right = explode(',', $role_right);		
		foreach($menu_list as $k=>$mrr){
			foreach ($mrr['sub_menu'] as $j=>$v){
				if(!in_array($v['control'].'Controller@'.$v['act'], $role_right)){
					unset($menu_list[$k]['sub_menu'][$j]);//过滤菜单
				}
			}
		}
	}
	return $menu_list;
}

function getAllMenu(){
	return	array(
			'system' => array('name'=>'系统设置','icon'=>'fa-cog','sub_menu'=>array(
					array('name'=>'网站设置','act'=>'index','control'=>'System'),
					//array('name'=>'友情链接','act'=>'linkList','control'=>'Article'),
					array('name'=>'商圈管理','act'=>'trading_area','control'=>'System'),
					array('name'=>'门店管理','act'=>'index','control'=>'Store'),
					array('name'=>'权限资源列表','act'=>'right_list','control'=>'System'),
					array('name'=>'pos机打印管理','act'=>'index','control'=>'PosManage'),
			)),
			'access' => array('name' => '管理员管理', 'icon'=>'fa-gears', 'sub_menu' => array(
					array('name' => '管理员列表', 'act'=>'index', 'control'=>'Admin'),
					array('name' => '角色管理', 'act'=>'role', 'control'=>'Admin'),
					array('name' => '管理员日志', 'act'=>'log', 'control'=>'Admin'),
					array('name' => '服务顾问列表', 'act'=>'supplier', 'control'=>'Admin'),
			)),
			'goods' => array('name' => '商品管理', 'icon'=>'fa-book', 'sub_menu' => array(					
					array('name' => '商品列表', 'act'=>'goodsList', 'control'=>'Goods'),
					array('name' => '商品分类', 'act'=>'categoryList', 'control'=>'Goods'),
					array('name' => '商品类型', 'act'=>'goodsTypeList', 'control'=>'Goods'),
					array('name' => '商品规格', 'act' =>'specList', 'control' => 'Goods'),
					array('name' => '商品统计表', 'act' =>'index', 'control' => 'GoodsCount'),
					//array('name' => '商品属性', 'act'=>'goodsAttributeList', 'control'=>'Goods'),
					//array('name' => '上架专区', 'act'=>'brandList', 'control'=>'Goods'),
					//array('name' => '商品评论','act'=>'index','control'=>'Comment'),
					//array('name' => '商品咨询','act'=>'ask_list','control'=>'Comment'),
			)),
			'order' => array('name' => '订单管理', 'icon'=>'fa-money', 'sub_menu' => array(
					array('name' => '订单列表', 'act'=>'index', 'control'=>'Order'),
					array('name' => '积分兑换列表', 'act'=>'point', 'control'=>'Order'),
					array('name' => '兑换商品统计表', 'act'=>'pointCount', 'control'=>'Order'),
					array('name' => '发货单', 'act'=>'delivery_list', 'control'=>'Order'),
					//array('name' => '快递单', 'act'=>'express_list', 'control'=>'Order'),
					//array('name' => '退货单', 'act'=>'return_list', 'control'=>'Order'),
					//array('name' => '添加订单', 'act'=>'add_order', 'control'=>'Order'),
					array('name' => '订单日志', 'act'=>'order_log', 'control'=>'Order'),
			)),

			'services' => array('name' => '会员服务', 'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '发票管理', 'act'=>'index', 'control'=>'Invoice'),
					array('name' => '代发管理', 'act'=>'send_list', 'control'=>'Services'),
					array('name' => '寄存管理', 'act'=>'deposit_list', 'control'=>'Services'),
					array('name' => '上门管理', 'act'=>'visit_list', 'control'=>'Services'),
					array('name' => '沟通管理', 'act'=>'communication_list', 'control'=>'Services'),
					array('name' => '投诉管理','act'=>'complaint_list', 'control'=>'Services'),
					array('name' => 'app文章列表', 'act'=>'app_articleList', 'control'=>'Article'),
					
			)),

			'member' => array('name'=>'会员管理','icon'=>'fa-user','sub_menu'=>array(
					array('name'=>'会员列表','act'=>'index','control'=>'User'),
					array('name'=>'会员等级','act'=>'levelList','control'=>'User'),
					array('name'=>'会员消费管理','act'=>'consumption','control'=>'User'),
					array('name'=>'推荐人管理','act'=>'recommender','control'=>'User'),
					//array('name'=>'充值记录','act'=>'recharge','control'=>'User'),
					//array('name' => '提现申请', 'act'=>'withdrawals', 'control'=>'User'),
					//array('name' => '汇款记录', 'act'=>'remittance', 'control'=>'User'),
					//array('name'=>'会员整合','act'=>'integrate','control'=>'User'),
			)),
			'Coupon' => array('name'=>'积分卡券管理','icon'=>'fa-user','sub_menu'=>array(
					array('name' => '优惠券管理','act'=>'index', 'control'=>'Coupon'),
					array('name'=>'满减管理','act'=>'manlist','control'=>'Coupon'),
					array('name'=>'礼品券管理','act'=>'consumption','control'=>'Coupon'),
					array('name'=>'生日营销设置','act'=>'reminder','control'=>'Coupon')
					
					
			)),	
			'promotion' => array('name' => '进销存管理', 'icon'=>'fa-bell', 'sub_menu' => array(
					// array('name' => '销售管理', 'act'=>'index', 'control'=>'Promotion'),
					array('name' => '订货管理', 'act'=>'order_buy_list', 'control'=>'Promotion'),
					array('name' => '仓库入库管理', 'act'=>'prom_goods_list', 'control'=>'Promotion'),
					array('name' => '门店退货管理', 'act'=>'index', 'control'=>'ReturnGoods'),
					array('name' => '仓库管理', 'act'=>'index', 'control'=>'Repertory'),
					array('name' => '库存报表', 'act'=>'index', 'control'=>'ReportForms'),
					array('name' => '商品上下限设置', 'act'=>'index', 'control'=>'GoodsLimit'),
					array('name' => '门店进销存报表', 'act'=>'index', 'control'=>'SaleWater'),
					array('name' => '仓库进销存报表', 'act'=>'index', 'control'=>'SaleWaterRepertory'),
			)),
			
			'content' => array('name' => '微信管理', 'icon'=>'fa-comments', 'sub_menu' => array(
					array('name' => '文章列表', 'act'=>'articleList', 'control'=>'Article'),
					array('name' => '分享管理', 'act'=>'shareList', 'control'=>'Article'),
					array('name' => '资讯管理', 'act'=>'help_list', 'control'=>'Article'),
				
					array('name' => '评论管理', 'act'=>'comment_list', 'control'=>'Services'),
					array('name' => '活动管理', 'act'=>'activity_list', 'control'=>'Services'),
					
			)),

			'Ad' => array('name' => '广告管理', 'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '广告列表', 'act'=>'adList', 'control'=>'Ad'),
					//array('name' => '广告位置', 'act'=>'positionList', 'control'=>'Ad'),
			)),
			
		 
	);

	/***
	return	array(
			'system' => array('name'=>'系统设置','icon'=>'fa-cog','sub_menu'=>array(
					array('name'=>'网站设置','act'=>'index','control'=>'System'),
					//array('name'=>'友情链接','act'=>'linkList','control'=>'Article'),
					//array('name'=>'自定义导航','act'=>'navigationList','control'=>'System'),
					//array('name'=>'区域管理','act'=>'region','control'=>'Tools'),
					array('name'=>'权限资源列表','act'=>'right_list','control'=>'System'),
			)),
			'access' => array('name' => '管理员管理', 'icon'=>'fa-gears', 'sub_menu' => array(
					array('name' => '管理员列表', 'act'=>'index', 'control'=>'Admin'),
					array('name' => '角色管理', 'act'=>'role', 'control'=>'Admin'),
					array('name' => '管理员日志', 'act'=>'log', 'control'=>'Admin'),
					array('name' => '服务顾问列表', 'act'=>'supplier', 'control'=>'Admin'),
			)),
			
			'member' => array('name'=>'会员管理','icon'=>'fa-user','sub_menu'=>array(
					array('name'=>'会员列表','act'=>'index','control'=>'User'),
					array('name'=>'会员等级','act'=>'levelList','control'=>'User'),
					array('name'=>'充值记录','act'=>'recharge','control'=>'User'),
					array('name' => '提现申请', 'act'=>'withdrawals', 'control'=>'User'),
					array('name' => '汇款记录', 'act'=>'remittance', 'control'=>'User'),
					//array('name'=>'会员整合','act'=>'integrate','control'=>'User'),
			)),
			'goods' => array('name' => '商品管理', 'icon'=>'fa-book', 'sub_menu' => array(
					array('name' => '商品分类', 'act'=>'categoryList', 'control'=>'Goods'),
					array('name' => '商品列表', 'act'=>'goodsList', 'control'=>'Goods'),
					array('name' => '商品类型', 'act'=>'goodsTypeList', 'control'=>'Goods'),
					array('name' => '商品规格', 'act' =>'specList', 'control' => 'Goods'),
					array('name' => '商品属性', 'act'=>'goodsAttributeList', 'control'=>'Goods'),
					array('name' => '品牌列表', 'act'=>'brandList', 'control'=>'Goods'),
					array('name' => '商品评论','act'=>'index','control'=>'Comment'),
					array('name' => '商品咨询','act'=>'ask_list','control'=>'Comment'),
			)),
			'order' => array('name' => '订单管理', 'icon'=>'fa-money', 'sub_menu' => array(
					array('name' => '订单列表', 'act'=>'index', 'control'=>'Order'),
					array('name' => '发货单', 'act'=>'delivery_list', 'control'=>'Order'),
					//array('name' => '快递单', 'act'=>'express_list', 'control'=>'Order'),
					array('name' => '退货单', 'act'=>'return_list', 'control'=>'Order'),
					array('name' => '添加订单', 'act'=>'add_order', 'control'=>'Order'),
					array('name' => '订单日志', 'act'=>'order_log', 'control'=>'Order'),
			)),
			'promotion' => array('name' => '促销管理', 'icon'=>'fa-bell', 'sub_menu' => array(
					array('name' => '抢购管理', 'act'=>'flash_sale', 'control'=>'Promotion'),
					array('name' => '团购管理', 'act'=>'group_buy_list', 'control'=>'Promotion'),
					array('name' => '商品促销', 'act'=>'prom_goods_list', 'control'=>'Promotion'),
					array('name' => '订单促销', 'act'=>'prom_order_list', 'control'=>'Promotion'),
					array('name' => '代金券管理','act'=>'index', 'control'=>'Coupon'),
			)),
			'Ad' => array('name' => '广告管理', 'icon'=>'fa-flag', 'sub_menu' => array(
					array('name' => '广告列表', 'act'=>'adList', 'control'=>'Ad'),
					array('name' => '广告位置', 'act'=>'positionList', 'control'=>'Ad'),
			)),
			'content' => array('name' => '内容管理', 'icon'=>'fa-comments', 'sub_menu' => array(
					array('name' => '文章列表', 'act'=>'articleList', 'control'=>'Article'),
					array('name' => '文章分类', 'act'=>'categoryList', 'control'=>'Article'),
					//array('name' => '帮助管理', 'act'=>'help_list', 'control'=>'Article'),
					//array('name' => '公告管理', 'act'=>'notice_list', 'control'=>'Article'),
					array('name' => '专题列表', 'act'=>'topicList', 'control'=>'Topic'),
			)),
		
			'theme' => array('name' => '模板管理', 'icon'=>'fa-adjust', 'sub_menu' => array(
					array('name' => 'PC端模板', 'act'=>'templateList?t=pc', 'control'=>'Template'),
					array('name' => '手机端模板', 'act'=>'templateList?t=mobile', 'control'=>'Template'),
			)),

			'tools' => array('name' => '数据工具', 'icon'=>'fa-plug', 'sub_menu' => array(
					//array('name' => '插件列表', 'act'=>'index', 'control'=>'Plugin'),
					array('name' => '数据备份', 'act'=>'index', 'control'=>'Tools'),
					array('name' => '数据还原', 'act'=>'restore', 'control'=>'Tools'),
			)),
			'count' => array('name' => '统计报表', 'icon'=>'fa-signal', 'sub_menu' => array(
					array('name' => '销售概况', 'act'=>'index', 'control'=>'Report'),
					array('name' => '销售排行', 'act'=>'saleTop', 'control'=>'Report'),
					array('name' => '会员排行', 'act'=>'userTop', 'control'=>'Report'),
					array('name' => '销售明细', 'act'=>'saleList', 'control'=>'Report'),
					array('name' => '会员统计', 'act'=>'user', 'control'=>'Report'),
					array('name' => '财务统计', 'act'=>'finance', 'control'=>'Report'),
			)),
			'pickup' => array('name' => '自提点管理', 'icon'=>'fa-anchor', 'sub_menu' => array(
					array('name' => '自提点列表', 'act'=>'index', 'control'=>'Pickup'),
					array('name' => '添加自提点', 'act'=>'add', 'control'=>'Pickup'),
			))
		 
	);
	*/
}


function respose($res){
	exit(json_encode($res));
}

function checkValue($mystring,$findme){
	if(empty($mystring)){
		return 0;
	}
	//echo $mystring;
	//echo $findme;
	$pos = strpos($mystring, $findme);
        if ($pos === false)
		{
               return 0;
          }
        else
          {
               return 1;
          }
}