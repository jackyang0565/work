<?php

/**
 * 去掉图片url路径中的small
 */
function deleteSmall($url)
{
	// return $url;
	$newUrl=preg_replace('/\/small/','', $url);
	return $newUrl;
}

function object_array($array) {
	if(is_object($array)) {
		$array = (array)$array;
	} if(is_array($array)) {
		foreach($array as $key=>$value) {
			$array[$key] = object_array($value);
		}
	}
	return $array;
}

function excelTime($date) {
	$arr = explode('-', $date);
	return $arr[2].'-'.$arr[0].'-'.$arr[1];
}

/**
 * 读取execl文件转行成数组
 * @param string execl文件的路径
 * @param int 允许Sheet的数量
 * @return mixed
 */
function readExeclToArray($filePath,$sheetCount){
	if(empty($filePath)){
		return false;
	}else{
		vendor('PHPExcel.PHPExcel');
		$filePath=sprintf('%s%s',DATA_PATH,$filePath);
		$fileType = \PHPExcel_IOFactory::identify($filePath);
		$objReader = \PHPExcel_IOFactory::createReader($fileType);
		$objPHPExcel = $objReader->load($filePath);
		$count = $objPHPExcel->getSheetCount();
		if($counts>$sheetCount){
			return $count;
		}
		$currentSheet = $objPHPExcel->getSheet(0);
		$e_data = $currentSheet->toArray();
		return $e_data;
	}
}

/**
 * 读取excel文件转行成数组
 * @param array 上传文件的设置信息
 * @return array 返回上传后的文件信息
 */
function upFile($array_fileinfo){
	$default_fileinfo = array(
		'maxSize' => 1024*1024*100,
		'exts'    => array('xls','xlsx'),
		'rootPath'=> DATA_PATH,
		'savePath'=> ''
	);
	if(is_array($array_fileinfo) && !empty($array_fileinfo)){
		$default_fileinfo = $array_fileinfo;
	}
	$upload = new \Think\Upload();// 实例化上传类
	$upload->maxSize   = $default_fileinfo['maxSize']?$default_fileinfo['maxSize']:1024*1024*100;  // 设置附件上传大小
	$upload->exts      = $default_fileinfo['exts']?$default_fileinfo['exts']:array('xls','xlsx');  // 设置附件上传类型
	$upload->rootPath  = $default_fileinfo['rootPath']?$default_fileinfo['rootPath']:DATA_PATH;    // 设置附件上传根目录
	$upload->savePath  = $default_fileinfo['savePath']?$default_fileinfo['savePath']:'';           // 设置附件上传（子）目录
	// 上传文件
	$info =$upload->upload();
	return $info;
}

/*
 * 将excel的内容转化成数组
 */
function getExcel($filePath,$time,$field)
{
	$data_array = readExeclToArray($filePath,1);

	if(is_numeric($data_array)){
		if($data_array>1)
		{
			S('tast_error','只能有一个sheet');
			return 0;
		}
	}

	$e_data=farray($data_array,$field);

	foreach ($e_data as $k => &$v)
	{
		$v['time'] = $time;
	}

	return $e_data;
}

/*
 * 数组处理
 */
function farray($arr,$field)
{
	$length = count($field);
	//echo $length;die;
	$inx = in_array('date', $field);

	//检查是否是否正常
	$one_date = $arr[0][$inx-1];

	$data_year = date('Y',strtotime($one_date));

	if($data_year > 2014)
	{
		$inx = false;
	}

	//dump($inx);
	//
	foreach ($arr as &$val)
	{

		if($val[0])
		{
			//dump(date('Y',strtotime($val[$inx-1])));
			$val = array_slice($val, 0,$length);
			if($inx)
			{
				$val[$inx-1] = excelTime($val[$inx-1]);//date("Y-m-d",strtotime());
			   // dump(excelTime($val[$inx-1]));
			}
			$data[]= $val;
		}

		else  continue;
	}
	//die;
	return $data;
}

/*
 * 生成插入sql语句
 */

function getsqlstr($dataSet,$table,$field)
{
	$length = count($field);
	foreach ($field as $key => $val)
	{
		$fields[$key] ='`'.$val.'`';
	}

	foreach ($dataSet as $data){
		$value   =  array();
		foreach ($data as $key=>$val)
		{
			if(is_null($val)) continue;

			$value[]   =  '\''.$val.'\'';
		}

		$values[]    = '('.implode(',', $value).')';
	}

	$sql    =  'INSERT ignore INTO `t_'.$table.'` ('.implode(',', $fields).') VALUES '.implode(',',$values);
	return $sql;
}

/*
 * 添加报表数据
 */
function add_report($data,$channl='baidu',$type='keywords')
{

	$date_the = $data[0]['date'];

	$one = M($channl.'_'.$type)->find();

	$on_f['keyword_name'] = $one['keyword_name'];
	$on_f['camp_name'] = $one['camp_name'];
	$on_f['group_name'] = $one['group_name'];

	$on_f = array_filter($on_f);

	$on_f = array_keys($on_f);

	$on_str ='';
	foreach($on_f as $k1 =>$v1)
	{

		if($k1 == count($on_f)-1)
		{
			$on_str .='a.'.$v1.'=b.'.$v1.' ';

		}
		else
		{
			$on_str .='a.'.$v1.'=b.'.$v1.' and ';
		}
	}

	$table = 't_'.$channl.'_'.$type;

	M('')->execute('delete from t_report');

	$flag = M('report')->find();

	if($flag)  //如果还有数据，返回false
      return false;

	M('report')->addAll($data);

	$flag = M('report')->find();

	if(!$flag)  //添加不成功再试添加
		return false;


    if($channl!="baidu")
	{
		$sql = "UPDATE t_report a
INNER JOIN ".$table." b
ON ".$on_str."
SET a.keyword_id = b.id,a.citycode=b.citycode
where b.is_valid =1";

		$flag = M('')->execute($sql);

		if(!$flag) return false;
	}

			 


		
	$table1 = 't_'.$channl.'_'.$type.'_report_'.date('Ym',strtotime($date_the));
	$sql="insert into ".$table1." (camp_name,group_name,keyword_name,views,clicks,cost,date,keyword_id,citycode,is_pc) (select * from t_report) ON DUPLICATE KEY UPDATE keyword_name=values(keyword_name),group_name=values(group_name),
			camp_name=values(camp_name),clicks=values(clicks),views=values(views),cost=values(cost),citycode=values(citycode);";
               
	$flag =  M('')->execute($sql);

	if(!$flag) return false;

	if($flag) return true;

}



function processDataToExcel($col,$data,$heji,$heji_type,$time){
    vendor('PHPExcel.PHPExcel');
    $excel = new PHPExcel();
    $excel->getProperties()
        ->setCreator("")
        ->setLastModifiedBy("")
        ->setTitle("")
        ->setSubject("")
        ->setDescription("")
        ->setKeywords("")
        ->setCategory("");
    $excel->setActiveSheetIndex(0); //设置当前页
    //设置当前活动页的标题
    $excel->getActiveSheet()->setTitle($heji_type);
    $title = $heji_type.' '.$time;

    $excel->getActiveSheet()->setCellValue('A1',$title);
    //合并单元格
    $excel->getActiveSheet()->mergeCells('A1:P1');
    $sttleArray1 = array(
        'font' => array(
            'bold' =>true,
            'size' =>16,
            'color'=>array(
                'argb' => '00000000',
            ),
        ),
        'alignment' => array(
            'horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'  =>PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
    );
    $excel->getActiveSheet()->getStyle('A1')->applyFromArray($sttleArray1);

    $sttleArray2 = array(
        'font' => array(
            'bold' =>true,
            'size' =>10,
            'color'=>array(
                'argb' => '00FFFFFF',
            ),
        ),
        'alignment' => array(
            'horizontal'=>PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            'vertical'  =>PHPExcel_Style_Alignment::VERTICAL_CENTER,
        ),
    );


    $width = 12;
    $excel->getActiveSheet()->getColumnDimension('A')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('B')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('C')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('D')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('E')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('F')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('G')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('H')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('I')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('J')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('K')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('L')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('M')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('N')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('O')->setWidth($width);
    $excel->getActiveSheet()->getColumnDimension('P')->setWidth($width);


    $excel->getActiveSheet()->getRowDimension('1')->setRowHeight(30);
    $excel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);
    $megeCells_len = 0;
    $excel->getActiveSheet()->setCellValue('B2','展现量');
    $excel->getActiveSheet()->getStyle('B2:P2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
    $excel->getActiveSheet()->getStyle('B2:P2')->getFill()->getStartColor()->setARGB('001FA67A');
    $excel->getActiveSheet()->getStyle('B2:P2')->applyFromArray($sttleArray2);

    $excel->getActiveSheet()->setCellValue('C2','点击量');
    $excel->getActiveSheet()->setCellValue('D2','消费');
    $excel->getActiveSheet()->setCellValue('E2','发标量');
    $excel->getActiveSheet()->setCellValue('F2','有效量');
    $excel->getActiveSheet()->setCellValue('G2','分单公司数');
    $excel->getActiveSheet()->setCellValue('H2','扣款金额');
    $excel->getActiveSheet()->setCellValue('I2','实际消耗');
    $excel->getActiveSheet()->setCellValue('J2','点击率');
    $excel->getActiveSheet()->setCellValue('K2','cpc');
    $excel->getActiveSheet()->setCellValue('L2','发标率');
    $excel->getActiveSheet()->setCellValue('M2','转化率');
    $excel->getActiveSheet()->setCellValue('N2','盈亏');
    $excel->getActiveSheet()->setCellValue('O2','cpa');
    $excel->getActiveSheet()->setCellValue('P2','cpl');

    $excel->getActiveSheet()->setCellValue('B3',$heji['views']);
    $excel->getActiveSheet()->setCellValue('C3',$heji['clicks']);
    $excel->getActiveSheet()->setCellValue('D3',$heji['cost']);
    $excel->getActiveSheet()->setCellValue('E3',$heji['ordernum']);
    $excel->getActiveSheet()->setCellValue('F3',$heji['pronum']);
    $excel->getActiveSheet()->setCellValue('G3',$heji['coms']);
    $excel->getActiveSheet()->setCellValue('H3',$heji['trade']);
    $excel->getActiveSheet()->setCellValue('I3',$heji['t_cost']);
    $excel->getActiveSheet()->setCellValue('J3',$heji['clickslv']);
    $excel->getActiveSheet()->setCellValue('K3',$heji['cpc']);
    $excel->getActiveSheet()->setCellValue('L3',$heji['ordernumlv']);
    $excel->getActiveSheet()->setCellValue('M3',$heji['pronumlv']);
    $excel->getActiveSheet()->setCellValue('N3',$heji['ykui']);
    $excel->getActiveSheet()->setCellValue('O3',$heji['cpa']);
    $excel->getActiveSheet()->setCellValue('P3',$heji['cpl']);

    /*
    foreach($heji as $hj_k=>$hj_v){
        $row_key   = 2;//第二行;
        $row_value = 3;//第三行;
        $colum     = PHPExcel_Cell::stringFromColumnIndex($hj_k+$col);
        $excel->getActiveSheet()->setCellValue($colum.$row_key,$hj_v['heji_name']);
        $excel->getActiveSheet()->setCellValue($colum.$row_value,$hj_v['heji_value']);
        $megeCells_len++;
    }*/

//    $colum     = PHPExcel_Cell::stringFromColumnIndex($megeCells_len);
//    $excel->getActiveSheet()->mergeCells('A1'.':'.$colum.'1');
//    $excel->getActiveSheet()->setCellValue('B3',"负责人");
//    $excel->getActiveSheet()->setCellValue('C3',"合计");
//    $excel->getActiveSheet()->setCellValue('B4',"庆");
//
//    $excel->getActiveSheet()->mergeCells('B1:C2');
//    $excel->getActiveSheet()->mergeCells('D1:N1');
//    $excel->getActiveSheet()->mergeCells('B4:B22');

    foreach($data as $k=>$v){
        $row   = $k+4;
        $excel->getActiveSheet()->setCellValue('A'.$row,$v['name']);
        $excel->getActiveSheet()->setCellValue('B'.$row,$v['views']);
        $excel->getActiveSheet()->setCellValue('C'.$row,$v['clicks']);
        $excel->getActiveSheet()->setCellValue('D'.$row,$v['cost']);
        $excel->getActiveSheet()->setCellValue('E'.$row,$v['ordernum']);
        $excel->getActiveSheet()->setCellValue('F'.$row,$v['pronum']);
        $excel->getActiveSheet()->setCellValue('G'.$row,$v['coms']);
        $excel->getActiveSheet()->setCellValue('H'.$row,$v['trade']);
        $excel->getActiveSheet()->setCellValue('I'.$row,$v['t_cost']);
        $excel->getActiveSheet()->setCellValue('J'.$row,$v['clickslv']);
        $excel->getActiveSheet()->setCellValue('K'.$row,$v['cpc']);
        $excel->getActiveSheet()->setCellValue('L'.$row,$v['ordernumlv']);
        $excel->getActiveSheet()->setCellValue('M'.$row,$v['pronumlv']);
        $excel->getActiveSheet()->setCellValue('N'.$row,$v['ykui']);
        $excel->getActiveSheet()->setCellValue('O'.$row,$v['cpa']);
        $excel->getActiveSheet()->setCellValue('P'.$row,$v['cpl']);
    }
    $fileName = $title;
    $de = new DownloadExcel();
    $de->setHead($excel,$fileName);
}

function mFristAndLast($y="",$m=""){
    if($y=="") $y=date("Y");
    if($m=="") $m=date("m");
    $m=sprintf("%02d",intval($m));
    $y=str_pad(intval($y),4,"0",STR_PAD_RIGHT);
    $m>12||$m<1?$m=1:$m=$m;
    $firstday=strtotime($y.$m."01000000");
    $firstdaystr=date("Y-m-01",$firstday);
    $lastday = date('Y-m-d', strtotime("$firstdaystr +1 month -1 day"));
    return array("firstday"=>$firstdaystr,"lastday"=>$lastday);
}

/**
 * 返回json格式数据
 * @param  'status'|状态码
 * @param  'msg'|提示信息
 * $param  'data'|响应数据
 */
function parse_to_json($status=404,$msg='',$data=array()) {
    header('Content-Type:application/json; charset=utf-8');
    if(!$data) {
        exit(json_encode(array('status'=>$status,'msg'=>$msg,'data'=>'')));
    }else{
        exit(json_encode(array('status'=>$status,'msg'=>$msg,'data'=>$data)));
    }
}
