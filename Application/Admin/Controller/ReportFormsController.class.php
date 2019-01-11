<?php
/**  
 * 库存报表  
 * Date: 2017-09-07
 */
namespace Admin\Controller;
use Think\AjaxPage;
use Think\Controller;
set_time_limit(300);
class ReportFormsController extends BaseController {
	// 库存报表列表页
	public function index()
	{	
		$condition = array();
		 //商品编号查询条件
        if(!empty(I('search'))){
            $condition['tp_goods.goods_name|tp_spec_goods_price.sku']=array('like',"%".$_REQUEST['search']."%");
            $this->assign('search',I('search'));
        }
		// 仓库列表
		$repertory_name = M('repertory')->where('status=0 and is_del=0')->field('id,repertory_name')->select();
		// 门店列表
		$store_name = M('store')->where('is_forbid=0')->field('store_id,store_name')->select();

		$count = M('spec_goods_price')->join('tp_goods ON tp_spec_goods_price.goods_id = tp_goods.goods_id','left')->field('tp_spec_goods_price.goods_id,tp_spec_goods_price.sku,tp_goods.goods_name')->where($condition)->order('tp_spec_goods_price.goods_id desc')->count();

        $pageCount = $_GET['pageCount'];

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

		$Page  = new \Think\Page($count,$pageCount);         
        $show = $Page->show();
		// 商品编号
		// $result = M('spec_goods_price')->join('tp_stock ON tp_spec_goods_price.goods_id = tp_stock.good_id','right')->field('tp_spec_goods_price.goods_id, tp_spec_goods_price.sku,tp_stock.*')->select();
		$result = M('spec_goods_price')->join('tp_goods ON tp_spec_goods_price.goods_id = tp_goods.goods_id','left')->field('tp_spec_goods_price.goods_id,tp_spec_goods_price.sku,tp_goods.goods_name')->where($condition)->order('tp_spec_goods_price.goods_id asc')->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach ($result as $key => $val) {
			$goods_id = $val['goods_id'];

			// 根据goods_id 查询总库存
			$mapp['good_id'] = $goods_id;
			// $mapp['stock_type'] = 1;
			$total_number = M('stock')->where($mapp)->sum('number');
            if (!$total_number) {
                $total_number=0;
            }
			$result[$key]['total_number'] = $total_number;

			// 根据goods_id 查询仓库数量
			$repertoryArrLen = count($repertory_name);
			for ($i=0; $i < $repertoryArrLen; $i++) { 
				$map['good_id'] = $goods_id;
				$map['stock_type'] = 1;
				$map['resource_id'] = $repertory_name[$i]['id'];
				$total_number_repertory = M('stock')->where($map)->getField('number');
				if (!$total_number_repertory) {
					$total_number_repertory = 0;
				}
				$result[$key][$repertory_name[$i]['repertory_name']] = $total_number_repertory;
			}


			// 根据goods_id 查询在途数量
			$maps['good_id'] = $goods_id;
			$maps['_string'] = '(stock_type=2) OR (stock_type=3)';
			$total_number_zaitu = M('stock')->where($maps)->sum('number');
			if (!$total_number_zaitu) {
				$total_number_zaitu = 0;
			}
			$result[$key]['在途'] = $total_number_zaitu;


			// 根据goods_id 查询门店数量
			$storeArrLen = count($store_name);
			for ($j=0; $j < $storeArrLen; $j++) { 
				$map1['resource_id'] = $store_name[$j]['store_id'];
				$map1['good_id'] = $goods_id;
				$map1['stock_type'] = 4;
                $total_number_store1 = M('stock')->where($map1)->getField('number');//门店即时库存
                $total_number_store = $total_number_store1;
				if (!$total_number_store) {
					$total_number_store = 0;
				}
				$result[$key][$store_name[$j]['store_name']] = $total_number_store;
			}
		}
		// 将关联数组 result 转换成索引数组
	    $keyOfArr = array_keys($result[0]);
        $this_index = count($keyOfArr);
        $this->assign('repertory_name', $repertory_name);
        $repertory_count = count($repertory_name);
        $num = $repertory_count+5;
		$this->assign('num', $num);//仓库数量

        $this->assign('store_name', $store_name);

		$this->assign('result', $result);
		$this->assign('keyOfArr', $keyOfArr);//单个子数组的键数组
		$this->assign('this_index', $this_index);//单个子数组的长度
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('p',I('p'));// 赋值分页输出
		$this->display();
	}


	//新开导入页面
	public function turnoverInital() {
		$this->display();
	}


	//门店库存数据导入
    public function titleImport() {

        if ($_FILES['import']['tmp_name']){
        	vendor("PHPExcel.PHPExcel");
    		$objExcel = new \PHPExcel();
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');

            $filename=$_FILES['import']['tmp_name'];
            $objPHPExcel = $objReader->load($filename);
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            for($i=2;$i<=$highestRow;$i++)
            {   
                $flag = false; 
                //序号
                $xuhao = $sheet->getCell("A".$i)->getValue();
                if (!$xuhao) {
                   continue;
                }
                // 验证门店是否存在
                // $objPHPExcel->getActiveSheet()->getCell("M".$i)->getValue()->__toString()
                $store_name = $sheet->getCell("B".$i)->getValue();
                if (is_object($store_name)) {
                    $repertory_name = $sheet->getCell("B".$i)->getValue()->__toString();
                }
                if (!$store_name) {
                    $str .= "请输入第".$xuhao."条数据的门店！<br/>";
                    continue;
                }
                $where['store_name'] = $store_name;
                $store_id = M('store')->where($where)->getField('store_id');
                if (!$store_id) {
                    $str .= "第".$xuhao."条数据的门店信息不存在！<br/>";
                    continue;
                }
                
                // 获取商品ID   goods_id
                $sku = $sheet->getCell("C".$i)->getValue();
                if (!$sku) {
                	$str .= "请填写".$xuhao."条数据的商品编号！<br/>";
                    continue;
                }
                if ($sku) {
                   $goods_id = M('spec_goods_price')->where('sku='.$sku)->getField('goods_id');
                   if (!$goods_id) {
                   		$str .= "第".$xuhao."条数据的商品信息不正确！<br/>";
                    	continue;
                   }
                }

                //获取库存数量
                $number = $sheet->getCell("D".$i)->getValue();

                //判断该商品是否存在该门店
                $where1['stock_type'] = 4;
                $where1['good_id'] = $goods_id;
                $where1['resource_id'] = $store_id;
                $re = M('stock')->where($where1)->find();
                //如果存在  则修改  不存在则新增
                if ($re) {
                	$oldNumber = $re['number'];//原始存在的商品数量
                	$data['number'] = $oldNumber + $number;
                	$data['update_time'] = time();
                	$result1 = M('stock')->where('id='.$re['id'])->save($data);
                	if (!$result1) {
                		$str .= "第".$xuhao."条数据导入失败！<br/>";
                	}else{
                        addWaterRecord($goods_id,$number,$store_id,1);
                    }
                }else{
                	$data1['stock_type'] = 4;
                	$data1['good_id'] = $goods_id;
                	$data1['resource_id'] = $store_id;
                	$data1['number'] = $number;
                	$data1['create_time'] = time();
                	$userInfo = getAdminInfo(session('admin_id'));
                	$userName = $userInfo['user_name'];
                	$data1['creator'] = $userName;
                	$result2 = M('stock')->add($data1);
                	if (!$result2) {
                		$str .= "第".$xuhao."条数据导入失败！<br/>";
                	}else{
                        addWaterRecord($goods_id,$number,$store_id,1);
                    }
                }
            }
            if (!$str) {
            	$this->success("导入成功");
            	exit();
            }else{
            	$this->assign('str',$str);
            	$this->display('turnoverInital');
            	exit();
            }
        }else{
        	$this->error('请上传文件!');
        }
    }


	//新开导入页面
	public function turnoverInital_cangku() {
		$this->display();
	}

    //仓库库存数据导入
    public function titleImport_cangku() {

        if ($_FILES['import']['tmp_name']){
        	vendor("PHPExcel.PHPExcel");
    		$objExcel = new \PHPExcel();
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');

            $filename=$_FILES['import']['tmp_name'];
            $objPHPExcel = $objReader->load($filename);
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            for($i=2;$i<=$highestRow;$i++)
            {   
                $flag = false; 
                //序号
                $xuhao = $sheet->getCell("A".$i)->getValue();
                if (!$xuhao) {
                   continue;
                }
                // 验证门店是否存在
                // $objPHPExcel->getActiveSheet()->getCell("M".$i)->getValue()->__toString()
                $repertory_name = $sheet->getCell("B".$i)->getValue();
                if (is_object($repertory_name)) {
                    $repertory_name = $sheet->getCell("B".$i)->getValue()->__toString();
                }
                if (!$repertory_name) {
                    $str .= "请输入第".$xuhao."条数据的仓库名称！<br/>";
                    continue;
                }
                $where['repertory_name'] = $repertory_name;
                $where['is_del'] = 0;
                $repertory_id = M('repertory')->where($where)->getField('id');
                if (!$repertory_id) {
                    $str .= "第".$xuhao."条数据的仓库信息不存在！<br/>";
                    continue;
                }
                
                // 获取商品ID   goods_id
                $sku = $sheet->getCell("C".$i)->getValue();
                if (!$sku) {
                	$str .= "请填写".$xuhao."条数据的商品编号！<br/>";
                    continue;
                }
                if ($sku) {
                   $goods_id = M('spec_goods_price')->where('sku='.$sku)->getField('goods_id');
                   if (!$goods_id) {
                   		$str .= "第".$xuhao."条数据的商品信息不正确！<br/>";
                    	continue;
                   }
                }

                //获取库存数量
                $number = $sheet->getCell("D".$i)->getValue();

                //判断该商品是否存在改门店
                $where1['stock_type'] = 1;
                $where1['good_id'] = $goods_id;
                $where1['resource_id'] = $repertory_id;
                $re = M('stock')->where($where1)->find();
                //如果存在  则修改  不存在则新增
                if ($re) {
                	$oldNumber = $re['number'];//原始存在的商品数量
                	$data['number'] = $oldNumber + $number;
                	$data['update_time'] = time();
                	$result1 = M('stock')->where('id='.$re['id'])->save($data);
                	if (!$result1) {
                		$str .= "第".$xuhao."条数据导入失败！<br/>";
                	}else{
                        addWaterRepertoryRecord($goods_id,$number,$repertory_id,1);
                    }
                }else{
                	$data1['stock_type'] = 1;
                	$data1['good_id'] = $goods_id;
                	$data1['resource_id'] = $repertory_id;
                	$data1['number'] = $number;
                	$data1['create_time'] = time();
                	$userInfo = getAdminInfo(session('admin_id'));
                	$userName = $userInfo['user_name'];
                	$data1['creator'] = $userName;
                	$result2 = M('stock')->add($data1);
                	if (!$result2) {
                		$str .= "第".$xuhao."条数据导入失败！<br/>";
                	}else{
                        addWaterRepertoryRecord($goods_id,$number,$repertory_id,1);
                    }
                }
            }
            if (!$str) {
            	$this->success("导入成功");
            	exit();
            }else{
            	$this->assign('str',$str);
            	$this->display('turnoverInital_cangku');
            	exit();
            }
        }else{
        	$this->error('请上传文件!');
        }
    }
}