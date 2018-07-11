<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/11
 * Time: 17:09
 */

namespace SemData\Controller;
use Common\Controller\CommonController;

class CityReportController extends CommonController
{
    public $channel_name = array('baidu'=>'百度','360'=>'360','sougou'=>'搜狗','sm'=>'神马');
    /**
     * 获取查询条件
     * @return mixed
     */
    public function commOption(){
        $option['channel'] = I('post.channel') ? I('post.channel') : '';
        $option['account'] = I('post.account_name') ? I('post.account_name') : '';
        $option['account'] = I('post.account_name') ? I('post.account_name') : '';
        $option['order_name'] = I('post.order_name') ? I('post.order_name') : 'date';
        $option['order_by'] = I('post.order_by') ? I('post.order_by') : 2; //默认1降序 desc，2升序asc

        $date_arr = explode(' - ',I('post.date_rand'));
        $option['start_date'] = trim($date_arr[0]);
        $option['end_date'] = trim($date_arr[1]);
        if(!$option['date_rand'] && !$option['end_date']){
            $option['start_date'] = date('Y-m-01', strtotime(date("Y-m-d")));
            $option['end_date'] =date('Y-m-d',time());
        }

        $option['group_type'] = I('post.group_type') ? I('post.group_type') : 'day';

        $option['page'] = I('post.page') ? I('post.page') : 1;
        $option['pageSize'] = I('post.pageSize') ? I('post.pageSize') : 31;
        $option['is_pc'] = I('post.is_pc');
        $option['is_download'] = I('post.is_download') ? I('post.is_download') : 0;

        $option['camp_name']    = trim(I('post.a_camp_name'));
        $option['group_name']   = trim(I('post.a_group_name'));
        $option['property'] = trim(I('post.property'));
        $option['city'] = trim(I('post.city'));
        $option['hot_flag'] = trim(I('post.hot_flag'));
        return $option;
    }

    public function report(){
        $semcityreport = M('SemnCityReport');

        if(!empty($_POST)){
            if($_POST['is_download']==0){
                //查询数据
                $option = $this->commOption();

                $model = D('NewCommonReport');
                $pageData = $model->sumcity($option);

                $pageData['dataList'] = $this->dataProcessing($pageData['dataList']);
                $sum_data = $this->sumDataProcessing($pageData['sum_data'][0]);

                $this->assign('pageData', $pageData);
                $this->assign('sum_data', $sum_data);
            }else{
                //下载数据
                $option = $this->commOption();

                $model = D('NewCommonReport');
                $pageData = $model->sumcity($option);

                $dataList = $this->dataProcessing($pageData['dataList']);
                $sum_data = $this->sumDataProcessing($pageData['sum_data'][0]);

                $file_name = '城市报表'.date('Ymd').'_'.rand(1000,9999).'.csv';
                $this->data2csv($dataList,$sum_data,$file_name);
            }
        }
        //渠道
        $channel = $this->channel_name;
        $this->assign('channel',$channel);
        //城市
        $citys=TBS_D('City')->getField('cityID,simpname,hot_flag');
        $this->assign('citys',$citys);
        //每天核对一下城市变化
        $redis = \Com\Tbs\RedisLib::getInstance();
        $check_date = $redis->get('semnew_cityreport_lastcheckdate');
        if($check_date!=date('Y-m-d')){
            //---------------核对数据----------------------//
            //重点城市
            $state_1_citys = TBS_D('City')->where(array('hot_flag'=>1))->getField('cityID,simpname');
            $where = array();
            $where['city'] = array('in',$state_1_citys);
            $semcityreport->where($where)->data(array('hot_flag'=>1))->save();
            //一般城市
            $state_0_citys = TBS_D('City')->where(array('hot_flag'=>0))->getField('cityID,simpname');
            $where['city'] = array('in',$state_0_citys);
            $semcityreport->where($where)->data(array('hot_flag'=>0))->save();
            //普通城市
            $state_2_citys = TBS_D('City')->where(array('hot_flag'=>2))->getField('cityID,simpname');
            $where['city'] = array('in',$state_2_citys);
            $semcityreport->where($where)->data(array('hot_flag'=>2))->save();
            $redis->set('semnew_cityreport_lastcheckdate',date('Y-m-d'),3600*24);
        }
        $redis->close();


        $this->display();
    }


    /**
     * 计算总和
     * @param $sum_data
     * @param $fk
     * @return array
     */
    public function sumDataProcessing($sum_data)
    {

        if (is_array($sum_data)) {

            $sum_data['account'] = '-';
            $sum_data['channel'] = '-';
            $sum_data['campaign_name'] = '-';
            $sum_data['property'] = '-';
            $sum_data['adgroup_name'] = '-';
            $sum_data['city'] = '-';
            $sum_data['device'] = '-';
            $sum_data['hot_flag'] = '-';

            $sum_data['click_rate'] = (round($sum_data['clicks'] / $sum_data['views'], 4) * 100) . '%';
            //点击单价
            if($sum_data['clicks']!=0){
                $sum_data['click_unit_cost'] = round($sum_data['cost'] / $sum_data['clicks'], 2);
            }else{
                $sum_data['click_unit_cost'] = '-';
            }
            //登记单价
            if($sum_data['order_nums']!=0){
                $sum_data['order_nums_unit_cost'] = round($sum_data['cost'] / $sum_data['order_nums'], 2);
            }else{
                $sum_data['order_nums_unit_cost'] = '-';
            }
            //登记率
            if($sum_data['clicks']!=0) {
                $sum_data['order_nums_rate'] = (round($sum_data['order_nums'] / $sum_data['clicks'], 4) * 100) . '%';
            }else{
                $sum_data['order_nums_rate'] = '-';
            }
            //发单单价
            if($sum_data['real_order_nums']!=0){
                $sum_data['real_order_nums_unit_cost'] = round($sum_data['cost'] / $sum_data['real_order_nums'], 2);
            }else{
                $sum_data['real_order_nums_unit_cost'] = '-';
            }
            //发标率
            if($sum_data['clicks']!=0) {
                $sum_data['real_order_nums_rate'] = (round($sum_data['real_order_nums'] / $sum_data['clicks'], 4) * 100) . '%';
            }else{
                $sum_data['real_order_nums_rate'] = '-';
            }
            //有效单价
            if($sum_data['effect_order_nums']!=0){
                $sum_data['effect_order_nums_unit_cost'] = round($sum_data['cost'] / $sum_data['effect_order_nums'], 2);
            }else{
                $sum_data['effect_order_nums_unit_cost'] = '-';
            }
            //有效率
            if($sum_data['real_order_nums']!=0) {
                $sum_data['effect_order_nums_rate'] = (round($sum_data['effect_order_nums'] / $sum_data['real_order_nums'], 4) * 100) . '%';
            }else{
                $sum_data['effect_order_nums_rate'] = '-';
            }
            //返点
            if($sum_data['cash']!=0){
                $sum_data['returnpoint'] = round($sum_data['cost'] / $sum_data['cash'], 2);
            }else{
                $sum_data['returnpoint'] = 0;
            }

        }
        return $sum_data;
    }


    /**
     * 处理数据
     * @param $data
     * @param $fk
     * @return mixed
     */
    public function dataProcessing($data)
    {
        if (is_array($data)) {

            foreach ($data as $k => &$v) {

                if($v['views']!=0) {
                    $v['click_rate'] = (round($v['clicks'] / $v['views'], 4) * 100) . '%';
                }else{
                    $v['click_rate'] = '-';
                }
                //点击单价
                if($v['clicks']!=0){
                    $v['click_unit_cost'] = round($v['cost'] / $v['clicks'], 2);
                }else{
                    $v['click_unit_cost'] = '-';
                }
                //登记单价
                if($v['order_nums']!=0){
                    $v['order_nums_unit_cost'] = round($v['cost'] / $v['order_nums'], 2);
                }else{
                    $v['order_nums_unit_cost'] = '-';
                }
                //登记率
                if($v['clicks']!=0) {
                    $v['order_nums_rate'] = (round($v['order_nums'] / $v['clicks'], 4) * 100) . '%';
                }else{
                    $v['order_nums_rate'] = '-';
                }
                //发单单价
                if($v['real_order_nums']!=0){
                    $v['real_order_nums_unit_cost'] = round($v['cost'] / $v['real_order_nums'], 2);
                }else{
                    $v['real_order_nums_unit_cost'] = '-';
                }
                //发标率
                if($v['clicks']!=0) {
                    $v['real_order_nums_rate'] = (round($v['real_order_nums'] / $v['clicks'], 4) * 100) . '%';
                }else{
                    $v['real_order_nums_rate'] = '-';
                }
                //有效单价
                if($v['effect_order_nums']!=0){
                    $v['effect_order_nums_unit_cost'] = round($v['cost'] / $v['effect_order_nums'], 2);
                }else{
                    $v['effect_order_nums_unit_cost'] = '-';
                }
                //有效率
                if($v['real_order_nums']!=0) {
                    $v['effect_order_nums_rate'] = (round($v['effect_order_nums'] / $v['real_order_nums'], 4) * 100) . '%';
                }else{
                    $v['effect_order_nums_rate'] = '-';
                }
                //城市类型
                if($v['hot_flag']!='聚拢'){
                    if($v['hot_flag']==0){
                        $v['hot_flag'] = '一般城市';
                    }elseif ($v['hot_flag']==1){
                        $v['hot_flag'] = '重点城市';
                    }elseif ($v['hot_flag']==2){
                        $v['hot_flag'] = '普通城市';
                    }
                }
                //返点
                if($v['cash']!=0){
                    $v['returnpoint'] = round($v['cost'] / $v['cash'], 2);
                }else{
                    $v['returnpoint'] = 0;
                }
            }
        }

        return $data;
    }

    public function data2csv($data,$sum_data,$download_file_name){
        $device_arr = array('1'=>'PC','2'=>'H5');
        header("Content-type:text/csv");  //保存文件的类型
        header("Content-Disposition:attachment;filename=".$download_file_name);//保存文件的名字
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        ob_start();//开启ob缓存
        echo "\xEF\xBB\xBF";
        $df = fopen("php://output",'w');

        $new_head  = array('日期','平台','渠道','城市类型','城市','展现','点击','消费','现金','返点','点击率','点击单价','登记','登记单价','登记率','发标','发标单价','发标率','有效','有效单价','有效率','未分单','不可分','待定','无效','未处理','重单');

        fputcsv($df,$new_head);
        fputcsv($df,array('合计','-','-','-','-',$sum_data['views'],$sum_data['clicks'],$sum_data['cost'],$sum_data['cash'],$sum_data['returnpoint'],$sum_data['click_rate'],$sum_data['click_unit_cost'],$sum_data['order_nums'],$sum_data['order_nums_unit_cost'],$sum_data['order_nums_rate'],$sum_data['real_order_nums'],$sum_data['real_order_nums_unit_cost'],$sum_data['real_order_nums_rate'],$sum_data['effect_order_nums'],$sum_data['effect_order_nums_unit_cost'],$sum_data['effect_order_nums_rate'],$sum_data['status4_order_nums'],$sum_data['status9_order_nums'],$sum_data['status3_order_nums'],$sum_data['status2_order_nums'],$sum_data['status0_order_nums'],$sum_data['status1_order_nums']));
        foreach($data as $row){
            if($row['device']==1){
                $row['device'] = 'PC';
            }elseif ($row['device']==2){
                $row['device'] = 'H5';
            }
            if(!empty($this->channel_name[$row['channel']])){
                $row['channel'] = $this->channel_name[$row['channel']];
            }
            fputcsv($df,array($row['report_date'],$row['device'],$row['channel'],$row['hot_flag'],$row['city'],$row['views'],$row['clicks'],$row['cost'],$row['cash'],$row['returnpoint'],$row['click_rate'],$row['click_unit_cost'],$row['order_nums'],$row['order_nums_unit_cost'],$row['order_nums_rate'],$row['real_order_nums'],$row['real_order_nums_unit_cost'],$row['real_order_nums_rate'],$row['effect_order_nums'],$row['effect_order_nums_unit_cost'],$row['effect_order_nums_rate'],$row['status4_order_nums'],$row['status9_order_nums'],$row['status3_order_nums'],$row['status2_order_nums'],$row['status0_order_nums'],$row['status1_order_nums']));
        }
        fclose($df);

        echo ob_get_clean();
        die();
    }

    public function setting(){
        $semdistrictreport = M('SemnDistrictReport');

        $pageSize = !empty($_REQUEST['limit'])?$_REQUEST['limit']:10;
        $page = !empty($_REQUEST['page'])?$_REQUEST['page']:1;
        $report_date = !empty($_REQUEST['report_date'])?$_REQUEST['report_date']:'';
        $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';

        //账号信息
        $where =array();
        $date_arr = explode(' - ',$report_date);
        if(count($date_arr)==1){
            $date_arr = explode('+-+',$report_date);
        }
        if(isset($date_arr[0])&&isset($date_arr[1])){
            if($date_arr[0]==$date_arr[1]){
                $where['report_date'] = $date_arr[0];
            }else{
                $where['report_date'] = array('between',array($date_arr[0],$date_arr[1]));
            }
        }
        if(!empty($channel)){
            $where['channel'] = $channel;
        }
        $list = $semdistrictreport->where($where)->limit(($page-1)*$pageSize,$pageSize)->order('id desc')->select();
        $count = $semdistrictreport->where($where)->count();
        $Page       = new \Org\Util\LayPage($count,$pageSize);
        $Page->setConfig('prev','上一页');
        $Page->setConfig('next','下一页');
        $Page->setConfig('last','末页');
        if(!empty($work_date)){
            $map['work_date'] = $work_date;
        }
        if(!empty($channel)){
            $map['channel'] = $channel;
        }
        foreach($map as $key=>$val) {
            $Page->parameter[$key]   =  $val;
        }
        $show       = $Page->show();
        $this->assign('page',$show);
        $this->assign('lists',$list);

        $this->display();
    }

    public function upload_csv(){
        $semdistrictreport = M('SemnDistrictReport');

        $citys=TBS_D('City')->getField('cityID,simpname');

        $upload = new \Think\Upload();
        $upload->maxSize   =     1024*1024*2;
        $upload->exts      =     array('csv');
        $upload->rootPath  =     './dynamicRes/data/sem/';
        $upload->savePath  =     './district/';
        //上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息
            $data['error'] = 1000;
            $data['msg'] = $upload->getError();
            $this->ajaxReturn($data,'json');
        }else{// 上传成功
            $file_name = $upload->rootPath.$info['file']['savepath'].$info['file']['savename'];
            $datas = array();
            $spl_object = new \SplFileObject($file_name, 'rb');
            $spl_object->seek(0);
            while (!$spl_object->eof()) {
                $content = $spl_object->fgetcsv();
                if(!empty($content[0])){
                    foreach ($content as &$value){
                        $value = mb_convert_encoding($value, "UTF-8", "GBK");
                    }
                    if($content[0]!='日期'){
                        $rs['report_date'] = $content['0'];
                        $rs['channel'] = trim($content['1']);
                        $rs['account_name'] = trim($content['2']);
                        $device = trim($content['3']);
                        if($device=='PC'){
                            $rs['device'] = 1;
                        }elseif ($device=='H5'){
                            $rs['device'] = 2;
                        }
                        if(in_array($content[4],$citys)){
                            $rs['city'] = $content['4'];
                        }else{
                            $rs['city'] = 'others';
                        }
                        $rs['views'] = $content['5'];
                        $rs['clicks'] = $content['6'];
                        $rs['cost'] = $content['7'];

                        $datas[] = $rs;
                    }
                }
                $spl_object->next();
            }

            $datas_arr = array_chunk($datas,1000);
            foreach ($datas_arr as &$datas){
                try{
                    $rs = $semdistrictreport->addAll($datas);
                    if($rs===false){
                        throw new \Exception($semdistrictreport->getDbError());
                    }
                }catch (\Exception $e){
                    $data['error'] = 1000;
                    $data['msg'] = $e->getMessage();
                    $this->ajaxReturn($data,'json');
                }

            }

            $data['error'] = 0;
            $data['msg'] = "上传成功";
            $this->ajaxReturn($data,'json');
        }

    }

    public function delete_district_report(){
        $semdistrictreport = M('SemnDistrictReport');

        $report_date = !empty($_REQUEST['report_date'])?$_REQUEST['report_date']:'';
        $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';

        $where =array();
        $date_arr = explode(' - ',$report_date);
        if(count($date_arr)==1){
            $date_arr = explode('+-+',$report_date);
        }
        if(isset($date_arr[0])&&isset($date_arr[1])){
            if($date_arr[0]==$date_arr[1]){
                $where['work_date'] = $date_arr[0];
            }else{
                $where['work_date'] = array('between',array($date_arr[0],$date_arr[1]));
            }
        }
        if(!empty($channel)){
            $where['channel'] = $channel;
        }

        $semdistrictreport->where($where)->delete();

        $data['error'] = 0;
        $data['msg'] = "删除成功";
        $this->ajaxReturn($data,'json');

    }

    public function setcityreport(){
        $semcityreport = M('SemnCityReport');
        $semschedule = M('SemnSchedule');

        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';
        $start_date = !empty($_REQUEST['start_date'])?$_REQUEST['start_date']:'';
        $end_date = !empty($_REQUEST['end_date'])?$_REQUEST['end_date']:'';

        if($type==1){
            $where = array();
            $where['channel'] = $channel;
            $where['report_date'] = array('between',array($start_date,$end_date));
            $semcityreport->where($where)->delete();
        }elseif ($type==2){
            $where = array();
            $where['channel'] = $channel;
            $where['work_date'] = array('between',array($start_date,$end_date));
            $semschedule->where($where)->data(array('city_finish'=>0))->save();
        }
    }

    public function charts(){
        if(!empty($_POST)){
            //查询数据
            $option = $this->commOption();
            $option['page'] = '1';
            $option['pageSize'] = '365';

            $model = D('NewCommonReport');
            $pageData = $model->sumcity($option);
            $pageData['dataList'] = $this->dataProcessing($pageData['dataList']);
        }
        //渠道
        $channel = $this->channel_name;
        $this->assign('channel',$channel);
        //城市
        $citys=TBS_D('City')->getField('cityID,simpname,hot_flag');
        $this->assign('citys',$citys);

        //城市类型
        $dataList = $pageData['dataList'];
        sort($dataList);
        if($_POST['chart_type']=='citytype_bar'){
            //type
            $key = array_search('open',$_POST);
            if($key=='hot_flag'){
                $types = array('普通城市','一般城市','重点城市');
            }else{
                exit('暂时只支持城市类型展开');
            }
            //date
            if($_POST['group_type']=='day'){
                $categories = array_values(array_unique(array_column($dataList,'report_date')));
                $result = $this->dealcitytype($dataList,$key,$types,'report_date',$categories);
            }elseif ($_POST['group_type']=='sum'){
                $categories = array_values(array_unique(array_column($dataList,'report_date')));
                $result = $this->dealcitytype($dataList,$key,$types,'report_date',$categories);
            }else{
                $categories = array_values(array_unique(array_column($dataList,$_POST['group_type'])));
                $result = $this->dealcitytype($dataList,$key,$types,$_POST['group_type'],$categories);
            }
            $series = array();
            foreach ($result as $lkey=>$ldata){
                foreach ($ldata as $mkey=>$mvalue){
                    if($lkey=='status1_order_nums'){
                        $data['name'] = '重单';
                    }elseif ($lkey=='status0_order_nums'){
                        $data['name'] = '未处理';
                    }elseif ($lkey=='status2_order_nums'){
                        $data['name'] = '无效';
                    }elseif ($lkey=='effect_order_nums'){
                        $data['name'] = '有效';
                    }elseif ($lkey=='status3_order_nums'){
                        $data['name'] = '待定';
                    }elseif ($lkey=='status4_order_nums'){
                        $data['name'] = '未分单';
                    }elseif ($lkey=='status9_order_nums'){
                        $data['name'] = '不可分';
                    }
                    $data['data'] = array_values($mvalue);
                    $data['stack'] = $mkey;
                    $series[] = $data;
                }
            }

            $this->assign('categories',json_encode($categories));
            $this->assign('series',json_encode($series));
        }elseif ($_POST['chart_type']=='ordertype_line'){
            //解析出图表渲染数据
            foreach ($dataList as $key=>$value){
                $chart_arr['date'][] = str_replace(array('~','-'),'',$value['report_date']);
                $chart_arr['views'][] = str_replace('-','0',$value['views']);
                $chart_arr['clicks'][] = str_replace('-','0',$value['clicks']);
                $chart_arr['cost'][] = str_replace('-','0',$value['cost']);
                $chart_arr['cash'][] = str_replace('-','0',$value['cash']);
                $chart_arr['order_nums'][] = str_replace('-','0',$value['order_nums']);$value[''];
                $chart_arr['status0_order_nums'][] = str_replace('-','0',$value['status0_order_nums']);
                $chart_arr['status1_order_nums'][] = str_replace('-','0',$value['status1_order_nums']);
                $chart_arr['status2_order_nums'][] = str_replace('-','0',$value['status2_order_nums']);
                $chart_arr['status3_order_nums'][] = str_replace('-','0',$value['status3_order_nums']);
                $chart_arr['status4_order_nums'][] = str_replace('-','0',$value['status4_order_nums']);
                $chart_arr['status9_order_nums'][] = str_replace('-','0',$value['status9_order_nums']);
                $chart_arr['effect_order_nums'][] = str_replace('-','0',$value['effect_order_nums']);
                $chart_arr['click_rate'][] = str_replace(array('%','-'),'0',$value['click_rate']);
                $chart_arr['click_unit_cost'][] = str_replace('-','0',$value['click_unit_cost']);
                $chart_arr['order_nums_unit_cost'][] = str_replace('-','0',$value['order_nums_unit_cost']);
                $chart_arr['order_nums_rate'][] = str_replace(array('%','-'),'',$value['order_nums_rate']);
                $chart_arr['effect_order_nums_unit_cost'][] = str_replace('-','0',$value['effect_order_nums_unit_cost']);
                $chart_arr['effect_order_nums_rate'][] = str_replace(array('%','-'),'',$value['effect_order_nums_rate']);
            }
            $this->assign("chart_arr",$chart_arr);
        }

        $this->display();
    }

    public function dealcitytype($list,$key,$types,$ckey,$categories)
    {
        $result = array();
        foreach ($list as $lvalue){
            foreach ($types as $mvalue){
                foreach ($categories as $nvalue) {
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['status1_order_nums'][$mvalue][$nvalue] = $result['status1_order_nums'][$mvalue][$nvalue] + $lvalue['status1_order_nums'];
                    }
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['status0_order_nums'][$mvalue][$nvalue] = $result['status0_order_nums'][$mvalue][$nvalue] + $lvalue['status0_order_nums'];
                    }
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['status2_order_nums'][$mvalue][$nvalue] = $result['status2_order_nums'][$mvalue][$nvalue] + $lvalue['status2_order_nums'];
                    }
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['effect_order_nums'][$mvalue][$nvalue] = $result['effect_order_nums'][$mvalue][$nvalue] + $lvalue['effect_order_nums'];
                    }
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['status3_order_nums'][$mvalue][$nvalue] = $result['status3_order_nums'][$mvalue][$nvalue] + $lvalue['status3_order_nums'];
                    }
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['status4_order_nums'][$mvalue][$nvalue] = $result['status4_order_nums'][$mvalue][$nvalue] + $lvalue['status4_order_nums'];
                    }
                    if ($mvalue == $lvalue[$key] && $nvalue == $lvalue[$ckey]) {
                        $result['status9_order_nums'][$mvalue][$nvalue] = $result['status9_order_nums'][$mvalue][$nvalue] + $lvalue['status9_order_nums'];
                    }
                }
            }
        }
		return $result;
    }
}
