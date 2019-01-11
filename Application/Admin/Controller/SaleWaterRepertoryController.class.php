<?php
/**    
 * Date: 2017-10-09
 */
namespace Admin\Controller;
use Think\AjaxPage;
use Think\Controller;
class SaleWaterRepertoryController extends BaseController {

	public function index(){


		// 搜索条件   仓库
		if(!empty(I('id'))){
            $maps['tp_stock.resource_id'] = I('id');
            $this->assign('id',I('id'));
        }
		// 搜索条件   商品编号
		if(!empty(I('sku'))){
            $goods_id = M('spec_goods_price')->where('sku='.I('sku'))->getField('goods_id');
            $maps['tp_stock.good_id'] = $goods_id;
            $this->assign('sku',I('sku'));
        }
		$maps['tp_stock.stock_type'] = 1;

		$count = M('stock')->join('tp_goods on tp_stock.good_id = tp_goods.goods_id', 'left')->join('tp_spec_goods_price on tp_stock.good_id = tp_spec_goods_price.goods_id', 'left')->join('tp_store on tp_stock.resource_id = tp_store.store_id', 'left')->where($maps)->field('tp_stock.*, tp_goods.goods_name, tp_goods.spu, tp_spec_goods_price.sku, tp_store.store_name')->order('resource_id desc, good_id asc')->count();

		$pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }
        //展示条数        
        $this->assign('pageCount',$pageCount);

        $Page  = new \Think\Page($count,$pageCount);         


		$result = M('stock')->join('tp_goods on tp_stock.good_id = tp_goods.goods_id', 'left')->join('tp_spec_goods_price on tp_stock.good_id = tp_spec_goods_price.goods_id', 'left')->join('tp_repertory on tp_stock.resource_id = tp_repertory.id', 'left')->where($maps)->field('tp_stock.*, tp_goods.goods_name, tp_goods.spu, tp_goods.market_price, tp_spec_goods_price.sku, tp_repertory.repertory_name')->order('good_id asc, resource_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
		$list = array();

		// 搜索条件   日期
		$timegap = I('timegap');

		if($timegap){
            $gap = explode('-', urldecode($timegap));
            $startTime_A = strtotime($gap[0]);
            $time_B = $gap[1].'23:59:59';
            $endTime_B = strtotime($time_B);
            $this->assign('timegap',urldecode($timegap));
        }else{
        	$yesDay = date("Y/m/d",time());
        	$nowDay = date('Y/m/d', time());
        	$this->assign('timegap',$yesDay.' - '.$nowDay);
        }
		// 如果没有选择时间段  则默认当天到现在
		if (!$timegap) {
			// $start = date('Y-m-d',time());
			$startTime = $yesDay.'00:00:00';
			$start_time = strtotime($startTime);
		}else{
			$start_time = $startTime_A;
		}

		if (!$timegap) {
			$end_time = time();
		}else{
			$end_time = $endTime_B;
		}

		foreach ($result as $key => $val) {
			$list[$key]['sku'] = $val['sku'];
			$list[$key]['goods_name'] = $val['goods_name'];
			$list[$key]['spu'] = $val['spu'];
			$list[$key]['repertory_name'] = $val['repertory_name'];

			// 查询进货数量
			$where['repertory_id'] = $val['resource_id'];
			$where['goods_id'] = $val['good_id'];
			$where['shipment_style'] = 1;
			$where['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$inner_number = M('sales_water_repertory')->where($where)->sum('number');
			$list[$key]['inner_number'] = $inner_number;

			// 查询销售数量
			$where1['repertory_id'] = $val['resource_id'];
			$where1['goods_id'] = $val['good_id'];
			$where1['shipment_style'] = 2;
			$where1['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$outer_number = M('sales_water_repertory')->where($where1)->sum('number');
			$list[$key]['outer_number'] = $outer_number;
			// 查询返货数量
			$where2['repertory_id'] = $val['resource_id'];
			$where2['goods_id'] = $val['good_id'];
			$where2['shipment_style'] = 3;
			$where2['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$return_number = M('sales_water_repertory')->where($where2)->sum('number');
			$list[$key]['return_number'] = $return_number;

			// 查询门店退回数量
			$where3['repertory_id'] = $val['resource_id'];
			$where3['goods_id'] = $val['good_id'];
			$where3['shipment_style'] = 4;
			$where3['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$return_number_store = M('sales_water_repertory')->where($where3)->sum('number');
			$list[$key]['return_number_store'] = $return_number_store;

			// 查询期初数量
			$whe['repertory_id'] = $val['resource_id'];
			$whe['goods_id'] = $val['good_id'];
			$whe['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe['shipment_style'] = 1;

			$whe1['repertory_id'] = $val['resource_id'];
			$whe1['goods_id'] = $val['good_id'];
			$whe1['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe1['shipment_style'] = 2;

			$whe2['repertory_id'] = $val['resource_id'];
			$whe2['goods_id'] = $val['good_id'];
			$whe2['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe2['shipment_style'] = 3;

			$whe3['repertory_id'] = $val['resource_id'];
			$whe3['goods_id'] = $val['good_id'];
			$whe3['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe3['shipment_style'] = 4;

			$inner_number1 = M('sales_water_repertory')->where($whe)->sum('number');
			$outer_number1 = M('sales_water_repertory')->where($whe1)->sum('number');
			$return_number1 = M('sales_water_repertory')->where($whe2)->sum('number');
			$return_number_store = M('sales_water_repertory')->where($whe3)->sum('number');
			$beginning = $val['number'] - $inner_number1 - $return_number_store + $outer_number1 + $return_number1;
			$list[$key]['beginning'] = $beginning;

			// 查询期末数量
			$ending = $beginning+$inner_number + $return_number_store -$outer_number-$return_number;
			$list[$key]['ending'] = $ending;
		}

		if (I('pageCount')) {
        	$Page->parameter['pageCount'] = urlencode(I('pageCount'));
		}
		if (I('id')) {
        	$Page->parameter['id'] = urlencode(I('id'));
		}
		if (I('timegap')) {
        	$Page->parameter['timegap'] = urlencode(urldecode(I('timegap')));
		}
		if (I('sku')) {
        	$Page->parameter['sku'] = urlencode(I('sku'));
		}

		//所有仓库列表
        $repertory = D('repertory')->where('is_del=0')->field('repertory_name,id')->select();

        $show = $Page->show();

        $this->assign('repertory',$repertory);

        $this->assign('search',I('search'));
        
		$this->assign('info', $list);
		$this->assign('page', $show);// 赋值分页输出
		$this->display();
	}

	// 每日进销存报表导出
	public function export_order()
    {	
    	// 引入phpexcel类
    	vendor("PHPExcel.PHPExcel");
    	$objPHPExcel = new \PHPExcel();
    	$where = array();
		// 搜索条件   仓库
		if(!empty($_POST['id'])){
            $maps['tp_stock.resource_id'] = $_POST['id'];
        }
        // 搜索条件   商品编号
		if(!empty($_POST['sku'])){
            $goods_id = M('spec_goods_price')->where('sku='.$_POST['sku'])->getField('goods_id');
            $maps['tp_stock.good_id'] = $goods_id;
        }


        // 搜索条件   日期
		$timegap = I('timegap');
		$maps['tp_stock.stock_type'] = 1;

    	$title = array('编号', '商品编号', '商品名称', '单位', '仓库名称', '期初', '进货', '出库', '返货', '期末');
    	$name = "saleWaterRepertory".date("Y/m/d");
    	$content = self::getAllDate($maps, $timegap);
    	$objPHPExcel->ExcelExport($name, $title, $content);
    }

    // 获取导出内容
    public static function getAllDate($maps,$timegap)
    {	
    	$result = M('stock')->join('tp_goods on tp_stock.good_id = tp_goods.goods_id', 'left')->join('tp_spec_goods_price on tp_stock.good_id = tp_spec_goods_price.goods_id', 'left')->join('tp_repertory on tp_stock.resource_id = tp_repertory.id', 'left')->where($maps)->field('tp_stock.*, tp_goods.goods_name, tp_goods.spu, tp_goods.market_price, tp_spec_goods_price.sku, tp_repertory.repertory_name')->order('good_id asc, resource_id desc')->select();

    	if($timegap){
            $gap = explode('-', $timegap);
            $startTime_A = strtotime($gap[0]);
            $time_B = $gap[1].'23:59:59';
            $endTime_B = strtotime($time_B);
        }else{
        	$yesDay = date("Y/m/d",time());
        	$nowDay = date('Y/m/d', time());
        }
		// 如果没有选择时间段  则默认当天到现在
		if (!$timegap) {
			// $start = date('Y-m-d',time());
			$startTime = $yesDay.'00:00:00';
			$start_time = strtotime($startTime);
		}else{
			$start_time = $startTime_A;
		}

		if (!$timegap) {
			$end_time = time();
		}else{
			$end_time = $endTime_B;
		}

    	$list = array();
		
		foreach ($result as $key => $val) {
			$list[$key]['sku'] = $val['sku'];
			$list[$key]['goods_name'] = $val['goods_name'];
			$list[$key]['spu'] = $val['spu'];
			$list[$key]['repertory_name'] = $val['repertory_name'];

			// 查询进货数量
			$where['repertory_id'] = $val['resource_id'];
			$where['goods_id'] = $val['good_id'];
			$where['shipment_style'] = 1;
			$where['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$inner_number = M('sales_water_repertory')->where($where)->sum('number');
			$list[$key]['inner_number'] = $inner_number;

			// 查询销售数量
			$where1['repertory_id'] = $val['resource_id'];
			$where1['goods_id'] = $val['good_id'];
			$where1['shipment_style'] = 2;
			$where1['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$outer_number = M('sales_water_repertory')->where($where1)->sum('number');
			$list[$key]['outer_number'] = $outer_number;
			// 查询返货数量
			$where2['repertory_id'] = $val['resource_id'];
			$where2['goods_id'] = $val['good_id'];
			$where2['shipment_style'] = 3;
			$where2['create_time'] = array(array('gt',$start_time),array('lt',$end_time));
			$return_number = M('sales_water_repertory')->where($where2)->sum('number');
			$list[$key]['return_number'] = $return_number;

			// 查询期初数量
			$whe['repertory_id'] = $val['resource_id'];
			$whe['goods_id'] = $val['good_id'];
			$whe['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe['shipment_style'] = 1;

			$whe1['repertory_id'] = $val['resource_id'];
			$whe1['goods_id'] = $val['good_id'];
			$whe1['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe1['shipment_style'] = 2;

			$whe2['repertory_id'] = $val['resource_id'];
			$whe2['goods_id'] = $val['good_id'];
			$whe2['create_time'] = array(array('gt',$start_time),array('lt',time()));
			$whe2['shipment_style'] = 3;

			$inner_number1 = M('sales_water_repertory')->where($whe)->sum('number');
			$outer_number1 = M('sales_water_repertory')->where($whe1)->sum('number');
			$return_number1 = M('sales_water_repertory')->where($whe2)->sum('number');
			$beginning = $val['number'] - $inner_number1 + $outer_number1 + $return_number1;
			$list[$key]['beginning'] = $beginning;

			// 查询期末数量
			$ending = $beginning+$inner_number-$outer_number-$return_number;
			$list[$key]['ending'] = $ending;
		}

    	$content = array();
    	$i = 1;
    	foreach ($list as $key => $val) {
    		$temp = array(
    			$i,
    			$val['sku'] ? $val['sku'] : '',
    			$val['goods_name'] ? $val['goods_name'] : '',
    			$val['spu'] ? $val['spu'] : '',
    			$val['repertory_name'] ? $val['repertory_name'] : '',
    			$val['beginning'] ? $val['beginning'] : 0,
    			$val['inner_number'] ? $val['inner_number'] : 0,
    			$val['outer_number'] ? $val['outer_number'] : 0,
    			$val['return_number'] ? $val['return_number'] : 0,
    			$val['ending'] ? $val['ending'] : 0,
            );
            array_push($content, $temp);
            $i++;
    	}
    	return $content;
    }
}