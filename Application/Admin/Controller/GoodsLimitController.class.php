<?php
/**    
 * Date: 2017-09-05
 */
namespace Admin\Controller;
use Think\AjaxPage;
use Think\Controller;
set_time_limit(300);
class GoodsLimitController extends BaseController {
	// 商品上下限列表
	public function index()
	{	
		$condition = array();
        //权限管理  店长只看门店
        if (session('role_id')==4) {
            $condition['tp_goods_limit.store_id'] = session('store_id');
            $this->assign('role_id', session('role_id'));
        }

		// 门店查询条件
        if(!empty(I('store_id'))){
            $condition['tp_goods_limit.store_id'] = I('store_id');
            $this->assign('store_id',I('store_id'));
        }
        //商品编号查询条件
        if(!empty(I('sku'))){
            $condition['tp_spec_goods_price.sku'] = I('sku');
            $this->assign('sku',I('sku'));
        }
        $count = D("goods_limit")->join('tp_spec_goods_price on tp_goods_limit.goods_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_goods_limit.goods_id = tp_goods.goods_id','left')->join('tp_store on tp_goods_limit.store_id = tp_store.store_id','left')->field('tp_goods_limit.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_store.store_name')->where($condition)->count();

        $pageCount = I('pageCount');

        if ($pageCount==='undefined' || !$pageCount) {
            $pageCount = 25;
        }

        $Page  = new \Think\Page($count,$pageCount);    

        if ($pageCount) {
            $Page->parameter['pageCount'] = urlencode($pageCount);
            $this->assign('_pageCount',$pageCount);
        }

        if (I('store_id')) {
            $Page->parameter['store_id'] = urlencode(I('store_id'));
        }
        if (I('sku')) {
            $Page->parameter['sku'] = urlencode(I('sku'));
        }

        $show = $Page->show();
        // $result = M('goods_limit')->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $info = D("goods_limit")->join('tp_spec_goods_price on tp_goods_limit.goods_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_goods_limit.goods_id = tp_goods.goods_id','left')->join('tp_store on tp_goods_limit.store_id = tp_store.store_id','left')->field('tp_goods_limit.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_store.store_name')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select(); 
        //所有门店列表
        $store = D('store')->field('store_name,store_id')->select();
        $this->assign('store',$store);

		$this->assign('info',$info);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('p',I('p'));
        $this->display();
	}


	// 修改上下限
	public function update()
	{
		$id = I('post.id');
		$data['min_count'] = I('post.min_count');
		$data['max_count'] = I('post.max_count');
		$data['update_time'] = time();
		$result = M('goods_limit')->where('id='.$id)->save($data);
		
        if ($result) {
            $arr['success'] = 1;
        }else{
            $arr['info'] = "删除失败!";
            $arr['success'] = 0;
        }
        echo json_encode($arr); 
        die;
	}


	// 商品上下限导出功能
	public function export_order()
    {	
    	// 引入phpexcel类
    	vendor("PHPExcel.PHPExcel");
    	$objPHPExcel = new \PHPExcel();
    	$where = array();
		// 门店查询条件
        if(!empty($_POST['store_id'])){
            $where['tp_goods_limit.store_id'] = $_POST['store_id'];
        }
        //商品编号查询条件
        if(!empty($_POST['sku'])){
            $where['tp_spec_goods_price.sku'] = $_POST['sku'];
        }
    	$title = array('编号','门店名称', '商品名称','商品编号', '库存下限', '库存上限', '设置人', '设置时间');
    	$name = "goodsLimit".date("Y/m/d");
    	$content = self::getAllDate($where);
    	$objPHPExcel->ExcelExport($name, $title, $content);
    }

    // 获取导出内容
    public static function getAllDate($where)
    {	
    	$result = D("goods_limit")->join('tp_spec_goods_price on tp_goods_limit.goods_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_goods_limit.goods_id = tp_goods.goods_id','left')->join('tp_store on tp_goods_limit.store_id = tp_store.store_id','left')->field('tp_goods_limit.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_store.store_name')->where($where)->order('id desc')->select();
    	$content = array();
    	$i = 1;
    	foreach ($result as $key => $val) {
    		$temp = array(
    			$i,
    			$val['store_name'],
    			$val['goods_name'],
    			$val['sku'],
    			$val['min_count'],
    			$val['max_count'],
    			$val['creator'],
    			$val['create_time']?date('Y-m-d',$val['create_time']):'',
            );
            array_push($content, $temp);
            $i++;
    	}
    	return $content;
    }


    //数据导入
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
                $store_name = $sheet->getCell("B".$i)->getValue();
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
                $sku = $sheet->getCell("D".$i)->getValue();
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
                // 商品名称
                $goods_name = $sheet->getCell("C".$i)->getValue();
                // 下限
                $min_count = $sheet->getCell("E".$i)->getValue();

                if ((int)$min_count!= $min_count) {
                	$str .= "第".$xuhao."条数据下限格式不正确！<br/>";
                	continue;
                }
                // 上限
                $max_count = $sheet->getCell("F".$i)->getValue(); 
                if ((int)$max_count!= $max_count) {
                	$str .= "第".$xuhao."条数据上限格式不正确！<br/>";
                	continue;
                }

                if ($min_count>=$max_count) {
                	$str .= "第".$xuhao."条数据上下限不正确！<br/>";
                	continue;
                }
                //判断商品是否设置上下限
                $map['goods_id'] = $goods_id;
                $map['store_id'] = $store_id;
                $result_id = M('goods_limit')->where($map)->getField('id');
                //如果存在  则修改  不存在则新增
                if ($result_id) {
                	$data['min_count'] = $min_count;
                	$data['max_count'] = $max_count;
                	$data['update_time'] = time();
                	$result1 = M('goods_limit')->where('id='.$result_id)->save($data);
                	if (!$result1) {
                		$str .= "第".$xuhao."条数据导入失败！<br/>";
                	}
                }else{
                	$data1['goods_id'] = $goods_id;
                	$data1['store_id'] = $store_id;
                	$data1['min_count'] = $min_count;
                	$data1['max_count'] = $max_count;
                	$data1['create_time'] = time();
                	$userInfo = getAdminInfo(session('admin_id'));
                	$userName = $userInfo['user_name'];
                	$data1['creator'] = $userName;
                	$result2 = M('goods_limit')->add($data1);
                	if (!$result2) {
                		$str .= "第".$xuhao."条数据导入失败！<br/>";
                	}
                }
            }
            if (!$str) {
            	$this->success("导入成功",U('goodsLimit/index'));
            	exit();
            }else{
            	// $this->assign('str',$str);
            	$this->index_a($str);
            	exit();
            }
        }else{
        	$this->error('请上传文件!');
        }
    }

    //导入数据跳转页面 
    public function index_a($str)
	{	
		$condition = array();
        
        //权限管理  店长只看门店
        if (session('role_id')==4) {
            $condition['tp_goods_limit.store_id'] = session('store_id');
            $this->assign('role_id', session('role_id'));
        }
		// 门店查询条件
        if(!empty($_POST['store_id'])){
            $condition['tp_goods_limit.store_id'] = $_POST['store_id'];
            $this->assign('store_id',$_POST['store_id']);
        }
        //商品编号查询条件
        if(!empty($_POST['sku'])){
            $condition['tp_spec_goods_price.sku'] = $_POST['sku'];
            $this->assign('sku',$_POST['sku']);
        }
        $count = D("goods_limit")->join('tp_spec_goods_price on tp_goods_limit.goods_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_goods_limit.goods_id = tp_goods.goods_id','left')->join('tp_store on tp_goods_limit.store_id = tp_store.store_id','left')->field('tp_goods_limit.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_store.store_name')->where($condition)->count();
        $Page  = new \Think\Page($count,25);         
        $show = $Page->show();
        // $result = M('goods_limit')->where($condition)->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $info = D("goods_limit")->join('tp_spec_goods_price on tp_goods_limit.goods_id = tp_spec_goods_price.goods_id','left')->join('tp_goods on tp_goods_limit.goods_id = tp_goods.goods_id','left')->join('tp_store on tp_goods_limit.store_id = tp_store.store_id','left')->field('tp_goods_limit.*,tp_spec_goods_price.sku,tp_goods.goods_name,tp_store.store_name')->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select(); 
        //所有门店列表
        $store = D('store')->field('store_name,store_id')->select();
        $this->assign('store',$store);

		$this->assign('info',$info);
		$this->assign('str',$str);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('p',I('p'));
        $this->display('goodsLimit/index');
	}

}