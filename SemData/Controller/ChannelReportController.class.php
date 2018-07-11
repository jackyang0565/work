<?php
namespace SemData\Controller;
use Common\Controller\CommonController;

class ChannelReportController extends CommonController
{
    /**
     * 获取查询条件
     * @return mixed
     */
    public function commOption(){
        $option['channel'] = S('report_channel') ? S('report_channel') : '';
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
        return $option;
    }

    public function report()
    {
        $semaccount = M('SemnAccount');
        $semcampaign = M('SemnCampaign');
        $semadgroup = M('SemnAdgroup');
        $semproperty = M('SemnProperty');

        if(!empty($_GET['channel'])){
            S('report_channel',$_GET['channel']);
        }
        if(!empty($_POST)){
            if($_POST['is_download']==0){
                //查询数据
                $option = $this->commOption();

                $model = D('NewCommonReport');
                $pageData = $model->sum($option);

                $pageData['dataList'] = $this->dataProcessing($pageData['dataList']);
                $sum_data = $this->sumDataProcessing($pageData['sum_data'][0]);

                $this->assign('pageData', $pageData);
                $this->assign('sum_data', $sum_data);
            }else{
                //下载数据
                $option = $this->commOption();

                $model = D('NewCommonReport');
                $pageData = $model->sum($option);

                $dataList = $this->dataProcessing($pageData['dataList']);
                $sum_data = $this->sumDataProcessing($pageData['sum_data'][0]);

                $file_name = S('report_channel').'渠道计划报表'.date('Ymd').'_'.rand(1000,9999).'.csv';
                $this->data2csv($dataList,$sum_data,$file_name);
            }
        }
        //账号
        $channel = S('report_channel');
        if(!empty($channel)){
            $account = $semaccount->where(array('channel'=>$channel))->getField('id,username');
        }
        $this->assign('account',$account);
        //获取计划
        $campaigns = $semcampaign->field('distinct(campaign_name)')->select();
        $this->assign('campaigns',$campaigns);
        //获取词性
        $propertys = $semproperty->field('distinct(property)')->select();
        $this->assign('propertys',$propertys);
        //获取单元
        $adgroups = $semadgroup->field('distinct(adgroup_name)')->select();
        $this->assign('adgroups',$adgroups);

        $this->assign('channel',$_REQUEST['channel']);
        $this->display('report');
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
            if($sum_data['clicks']!=0) {
                $sum_data['effect_order_nums_rate'] = (round($sum_data['effect_order_nums'] / $sum_data['real_order_nums'], 4) * 100) . '%';
            }else{
                $sum_data['effect_order_nums_rate'] = '-';
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

                $v['click_rate'] = (round($v['clicks'] / $v['views'], 4) * 100) . '%';
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
                //发标单价
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
                if($v['clicks']!=0) {
                    $v['effect_order_nums_rate'] = (round($v['effect_order_nums'] / $v['real_order_nums'], 4) * 100) . '%';
                }else{
                    $v['effect_order_nums_rate'] = '-';
                }
                //平台
                if($v['device']!='聚拢'){
                    if($v['device']==0){
                        $v['device'] = 'others';
                    }elseif ($v['device']==1){
                        $v['device'] = 'PC';
                    }elseif ($v['device']==2){
                        $v['device'] = 'H5';
                    }
                }

            }
        }

        return $data;
    }

    public function data2csv($data,$sum_data,$download_file_name){
        header("Content-type:text/csv");  //保存文件的类型
        header("Content-Disposition:attachment;filename=".$download_file_name);//保存文件的名字
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        ob_start();//开启ob缓存
        echo "\xEF\xBB\xBF";
        $df = fopen("php://output",'w');

        $new_head  = array('日期','账户名','平台','推广计划','词性','推广单元','展现','点击','消费','点击率','点击单价','登记','登记单价','登记率','发标','发标单价','发标率','有效','有效单价','有效率','未分单','不可分','待定','无效','未处理','重单');

        fputcsv($df,$new_head);
        fputcsv($df,array('合计','-','-','-','-','-',$sum_data['views'],$sum_data['clicks'],$sum_data['cost'],$sum_data['click_rate'],$sum_data['click_unit_cost'],$sum_data['order_nums'],$sum_data['order_nums_unit_cost'],$sum_data['order_nums_rate'],$sum_data['real_order_nums'],$sum_data['real_order_nums_unit_cost'],$sum_data['real_order_nums_rate'],$sum_data['effect_order_nums'],$sum_data['effect_order_nums_unit_cost'],$sum_data['effect_order_nums_rate'],$sum_data['status4_order_nums'],$sum_data['status9_order_nums'],$sum_data['status3_order_nums'],$sum_data['status2_order_nums'],$sum_data['status0_order_nums'],$sum_data['status1_order_nums']));
        foreach($data as $row){
            if($row['device']==1){
                $row['device'] = 'PC';
            }elseif ($row['device']==2){
                $row['device'] = 'H5';
            }
            fputcsv($df,array($row['report_date'],$row['account_name'],$row['device'],$row['campaign_name'],$row['property'],$row['adgroup_name'],$row['views'],$row['clicks'],$row['cost'],$row['click_rate'],$row['click_unit_cost'],$row['order_nums'],$row['order_nums_unit_cost'],$row['order_nums_rate'],$row['real_order_nums'],$row['real_order_nums_unit_cost'],$row['real_order_nums_rate'],$row['effect_order_nums'],$row['effect_order_nums_unit_cost'],$row['effect_order_nums_rate'],$row['status4_order_nums'],$row['status9_order_nums'],$row['status3_order_nums'],$row['status2_order_nums'],$row['status0_order_nums'],$row['status1_order_nums']));
        }
        fclose($df);

        echo ob_get_clean();
        die();
    }

    public function task_status(){
        $this->display();
    }

    /**
     * 获取总任务
     */
    public function gettask(){
        $page = !empty($_GET['page'])?$_GET['page']:'1';
        $limit = !empty($_GET['limit'])?$_GET['limit']:'30';
        $semtask = M('SemnTask');

        $count = $semtask->count();
        $result = $semtask->limit(($page-1)*$limit,$limit)->order('task_level asc,id asc')->select();

        if(!empty($count)&&!empty($result)){
            $data['code'] = 0;
            $data['msg'] = "";
            $data['count'] = $count;
            $data['data'] = $result;
        }else{
            $data['code'] = 1000;
            $data['msg'] = "暂无数据";
        }

        $this->ajaxReturn($data,'JSON');
    }

    /**
     * 设置任务完成情况
     */
    public function setTaskFinish(){
        $id = !empty($_REQUEST['id'])?$_REQUEST['id']:'';
        $finish = isset($_REQUEST['finish'])?$_REQUEST['finish']:'1';
        $semtask = M('SemnTask');

        if(!empty($id)){
            $semtask->where(array('id'=>$id))->data(array('finish'=>$finish))->save();
        }
    }

    public function schedule_status(){
        $this->display();
    }

    public function getschedule(){
        $page = !empty($_GET['page'])?$_GET['page']:'1';
        $limit = !empty($_GET['limit'])?$_GET['limit']:'30';
        $semacount = M('SemnAccount');
        $semschedule = M('SemnSchedule');

        $count = $semschedule->count();
        $result = $semschedule->limit(($page-1)*$limit,$limit)->order('work_date desc,channel_aid asc')->select();
        foreach ($result as &$value){
            $account_name = $semacount->where(array('id'=>$value['channel_aid']))->getField('username');
            $value['channel_aid'] = $value['channel_aid'].'|'.$account_name;
        }

        if(!empty($count)&&!empty($result)){
            $data['code'] = 0;
            $data['msg'] = "";
            $data['count'] = $count;
            $data['data'] = $result;
        }else{
            $data['code'] = 1000;
            $data['msg'] = "暂无数据";
        }

        $this->ajaxReturn($data,'JSON');
    }

    public function setReportFinish(){
        $id = !empty($_REQUEST['id'])?$_REQUEST['id']:'';
        $report_finish = isset($_REQUEST['report_finish'])?$_REQUEST['report_finish']:'1';
        $semschedule = M('SemnSchedule');

        if(!empty($id)){
            $semschedule->where(array('id'=>$id))->data(array('report_finish'=>$report_finish))->save();
        }
    }

    public function setOrderFinish(){
        $id = !empty($_REQUEST['id'])?$_REQUEST['id']:'';
        $order_finish = isset($_REQUEST['order_finish'])?$_REQUEST['order_finish']:'1';
        $semschedule = M('SemnSchedule');

        if(!empty($id)){
            $semschedule->where(array('id'=>$id))->data(array('order_finish'=>$order_finish))->save();
        }
    }

    public function setCityFinish(){
        $id = !empty($_REQUEST['id'])?$_REQUEST['id']:'';
        $city_finish = isset($_REQUEST['city_finish'])?$_REQUEST['city_finish']:'1';
        $semschedule = M('SemnSchedule');

        if(!empty($id)){
            $semschedule->where(array('id'=>$id))->data(array('city_finish'=>$city_finish))->save();
        }
    }

    public function delOneDateReport(){
        $semaccount = M('SemnAccount');
        $semreport = M('SemnReport');

        $channel_aid = !empty($_REQUEST['channel_aid'])?$_REQUEST['channel_aid']:'';
        $channel_aid_arr = explode('|',$channel_aid);
        $work_date = !empty($_REQUEST['work_date'])?$_REQUEST['work_date']:'';

        $account_name = $semaccount->where(array('id'=>$channel_aid_arr[0]))->getField('username');

        if(!empty($channel_aid)&&!empty($account_name)){
            $rs = $semreport->where(array('account_name'=>$account_name,'report_date'=>$work_date))->delete();

            if($rs){
                $data['code'] = 0;
                $data['msg'] = "删除成功";
            }else{
                $data['code'] = 1000;
                $data['msg'] = $semreport->getDbError();
            }
        }else{
            $data['code'] = 1000;
            $data['msg'] = "暂无数据";
        }

        $this->ajaxReturn($data,'JSON');
    }

    public function delOneDateOrderReport(){
        $semaccount = M('SemnAccount');
        $semchannelreport = M('SemnChannelReport');

        $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';
        $work_date = !empty($_REQUEST['work_date'])?$_REQUEST['work_date']:'';

        if(!empty($channel)&&!empty($work_date)){
            $rs = $semchannelreport->where(array('channel'=>$channel,'report_date'=>$work_date))->delete();

            if($rs){
                $data['code'] = 0;
                $data['msg'] = "删除成功";
            }else{
                $data['code'] = 1000;
                $data['msg'] = $semchannelreport->getDbError();
            }
        }else{
            $data['code'] = 1000;
            $data['msg'] = "暂无数据";
        }

        $this->ajaxReturn($data,'JSON');
    }

    public function delOneDateCityReport(){
        $semaccount = M('SemnAccount');
        $semcityreport = M('SemnCityReport');

        $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';
        $work_date = !empty($_REQUEST['work_date'])?$_REQUEST['work_date']:'';

        if(!empty($channel)&&!empty($work_date)){
            $rs = $semcityreport->where(array('channel'=>$channel,'report_date'=>$work_date))->delete();

            if($rs){
                $data['code'] = 0;
                $data['msg'] = "删除成功";
            }else{
                $data['code'] = 1000;
                $data['msg'] = $semcityreport->getDbError();
            }
        }else{
            $data['code'] = 1000;
            $data['msg'] = "暂无数据";
        }

        $this->ajaxReturn($data,'JSON');
    }

    public function del_today_report_csv(){
        $channel_aid = !empty($_REQUEST['channel_aid'])?$_REQUEST['channel_aid']:'';
        $work_date = !empty($_REQUEST['work_date'])?$_REQUEST['work_date']:'';

        if(!empty($channel_aid)&&!empty($work_date)){
            $channel_aid_arr = explode('|',$channel_aid);

            $pc_file_name = "./dynamicRes/data/sem/".$channel_aid_arr[0]."/report_".$work_date."_pc.csv";
            $wap_file_name = "./dynamicRes/data/sem/".$channel_aid_arr[0]."/report_".$work_date."_wap.csv";


            unlink($pc_file_name);
            unlink($wap_file_name);

            $data['code'] = 0;
            $data['msg'] = "删除成功";
        }else{
            $data['code'] = 1000;
            $data['msg'] = "暂无数据";
        }

        $this->ajaxReturn($data,'JSON');
    }

    public function operatecache(){
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';

        if($type=='clear'){
            $res = \Think\Cache::getInstance()->clear();
            if($res){
                echo "清理成功";
            }else{
                echo "清理失败";
            }
        }elseif ($type=='get'){
            $key = !empty($_REQUEST['key'])?$_REQUEST['key']:'';
            $value = S($key);
            var_dump($value);
        }elseif ($type=='del'){
            $key = !empty($_REQUEST['key'])?$_REQUEST['key']:'';
            S($key,null);
        }

    }

    public function initorderreport(){

        $semcityreport = M('SemnCityReport');
        $semschedule = M('SemnSchedule');
        $semsetting = M('SemSetting');

        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        $report_date = !empty($_REQUEST['report_date'])?$_REQUEST['report_date']:'';
        if($type==1){
            $res = $semschedule->where(array('order_finish'=>1))->data(array('order_finish'=>0))->save();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }elseif ($type==2){
            $res = $semschedule->where(array('city_finish'=>1))->data(array('city_finish'=>0))->save();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }elseif ($type==4){
            if(!empty($_GET['channel'])){
                $res1 = $semcityreport->where(array('channel'=>$_GET['channel']))->delete();
                if($res1){
                    echo "执行成功:".$res1."条";
                }else{
                    echo "执行失败";
                }
                $res2 = $semschedule->where(array('channel'=>$_GET['channel']))->data(array('city_finish'=>0))->save();
                if($res2){
                    echo "执行成功:".$res2."条";
                }else{
                    echo "执行失败";
                }
            }else{
                echo '无渠道';
            }
        }elseif ($type==5){
            $res = $semsetting->where(array('key'=>'init_task'))->data(array('value'=>0))->save();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }elseif ($type==6){
            $name = !empty($_REQUEST['name'])?$_REQUEST['name']:'';
            $where=array();
            if($name=='all'){
                $where['report_error'] = array('in',array(1,2));
            }elseif ($name=='1'){
                $where['report_error'] = array('in',array(1));
            }elseif ($name=='2'){
                $where['report_error'] = array('in',array(2));
            }
            $res = $semschedule->where($where)->data(array('report_error'=>0))->save();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }elseif ($type==7){
            $start_date = !empty($_REQUEST['start_date'])?$_REQUEST['start_date']:'';
            $end_date = !empty($_REQUEST['end_date'])?$_REQUEST['end_date']:'';
            $status = !empty($_REQUEST['status'])?$_REQUEST['status']:'';

            $where=array();
            $where['work_date'] = array('between',array($start_date,$end_date));
            $res = $semschedule->where($where)->data(array('report_finish'=>$status,'order_finish'=>$status,'city_finish'=>$status))->save();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }elseif ($type==8){
            $start_date = !empty($_REQUEST['start_date'])?$_REQUEST['start_date']:'';
            $end_date = !empty($_REQUEST['end_date'])?$_REQUEST['end_date']:'';
            $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';
            $status = isset($_REQUEST['status'])?$_REQUEST['status']:'';

            $where=array();
            $where['channel'] = $channel;
            $where['work_date'] = array('between',array($start_date,$end_date));
            $res = $semschedule->where($where)->data(array('city_finish'=>$status))->save();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }elseif ($type==9){
            $start_date = !empty($_REQUEST['start_date'])?$_REQUEST['start_date']:'';
            $end_date = !empty($_REQUEST['end_date'])?$_REQUEST['end_date']:'';
            $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';

            $where=array();
            $where['channel'] = $channel;
            $where['report_date'] = array('between',array($start_date,$end_date));
            $res = $semcityreport->where($where)->delete();
            if($res){
                echo "执行成功:".$res."条";
            }else{
                echo "执行失败";
            }
        }else{
            echo '请求错误';
        }

    }

    public function file(){
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        $key = !empty($_REQUEST['key'])?$_REQUEST['key']:'';
        $name = !empty($_REQUEST['name'])?$_REQUEST['name']:'';

        if($type=='show'){
            if(!empty($key)){
                $dir="./dynamicRes/data/sem/".$key.'/';
                $file=scandir($dir);
                unset($file[0]);unset($file[1]);
                $result = array();
                foreach ($file as $value){
                    $data['name'] = $value;
                    $data['size'] = filesize($dir.$value);
                    array_push($result,$data);
                }

                if(!empty($result)){
                    var_dump($result);
                }
            }
        }elseif ($type=='delete'){
            if(!empty($key)){
                if(!empty($name)){
                    $file="./dynamicRes/data/sem/".$key.'/'.$name;
                    unlink($file);
                }
            }
        }

    }

    public function dropdatabase(){
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        if($type==1){
            $sql = "DROP TABLE `t_base_data`";
        }
        M()->execute($sql);
    }

    public function cleartabledata(){
        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        if($type==1){
            $sql = 'TRUNCATE TABLE `t_semn_task`';
            M()->query($sql);
        }elseif ($type==2){
            $sql = 'TRUNCATE TABLE `t_semn_schedule`';
            M()->query($sql);
        }elseif ($type==3){
            $semsetting = M('SemSetting');
            $semsetting->where(array('key'=>'init_task2'))->data(array('value'=>0))->save();
        }elseif ($type==4){
            $semsetting = M('SemSetting');
            $semsetting->where(array('key'=>'init_task'))->data(array('value'=>0))->save();
        }elseif ($type==9){
            $semcampaign = M('SemnCampaign');
            $channel_aid = $_REQUEST['channel_aid'];
            if(empty($channel_aid)){
                echo '账号id没填写';die();
            }
            $result = $semcampaign->where(array('channel_aid'=>$channel_aid))->delete();
            var_dump($result);
        }elseif ($type==10){
            $semadgroup = M('SemnAdgroup');
            $channel_aid = $_REQUEST['channel_aid'];
            if(empty($channel_aid)){
                echo '账号id没填写';die();
            }
            $result = $semadgroup->where(array('channel_aid'=>$channel_aid))->delete();
            var_dump($result);
        }elseif ($type==11){
            $semkeyword = M('SemnKeyword');
            $channel_aid = $_REQUEST['channel_aid'];
            if(empty($channel_aid)){
                echo '账号id没填写';die();
            }
            $result = $semkeyword->where(array('channel_aid'=>$channel_aid))->delete();
            var_dump($result);
        }

    }

    public function rundata(){
        $semkeyword = M('SemnKeyword');

        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        $start_date = !empty($_REQUEST['start_date'])?$_REQUEST['start_date']:'';
        $end_date = !empty($_REQUEST['end_date'])?$_REQUEST['end_date']:'';
        $redisHandle=\Com\Tbs\RedisLib::getInstance();
        if($type=='init'){
            for($date=$start_date;$date<=$end_date;$date=date('Y-m-d',strtotime($date." +1 day"))){
                $redisHandle->rPush('task_date',$date);
            }
        }elseif ($type=='list'){
            $result = $redisHandle->lrange ('task_date',0,-1);
            var_dump($result);
        }elseif ($type=='del'){
            $redisHandle->ltrim ('task_date',1,0);
        }elseif ($type=='run'){
            $run_date = $redisHandle->lPop('task_date');
            $where = array();
            $where['urlhistory'] = array('like','%channel=sem&%subchannel=baidu&%');
            $where['addtime'] = array('between',array($run_date." 00:00:00",$run_date." 23:59:59"));
            $orders = TBS_D('Order')->where($where)->field('urlhistory')->select();
            $keywordIds = array();
            foreach ($orders as $order){
                preg_match('/tbs_keyword=(\d+)/',$order['urlhistory'],$match);
                if(!empty($match[1])){
                    array_push($keywordIds,$match['1']);
                }
            }

            $where = array();
            $where['keyword_id'] = array('in',$keywordIds);
            $result = $semkeyword->where($where)->field('keyword_id')->select();
            $exist_arr = array();
            foreach ($result as $value){
                array_push($exist_arr,$value['keyword_id']);
            }

            $run_arr = array_diff($keywordIds,$exist_arr);

            $model = new \SemNew\Model\BaiduChannelModel(1);
            call_user_func_array(array($model,'addkeyword'),array($run_arr));

            $model = new \SemNew\Model\BaiduChannelModel(2);
            call_user_func_array(array($model,'addkeyword'),array($run_arr));

            $model = new \SemNew\Model\BaiduChannelModel(3);
            call_user_func_array(array($model,'addkeyword'),array($run_arr));

            $model = new \SemNew\Model\BaiduChannelModel(7);
            call_user_func_array(array($model,'addkeyword'),array($run_arr));
        }elseif ($type=='retask'){
            $semntask = M('SemnTask');
            $where = array();
            $where['task_module'] = array('in',array('BaiduChannel'));
            $where['task_name'] = array('in',array('getCampaign','getAdgroup'));
            $where['finish'] = 1;
            $semntask->where($where)->data(array('finish'=>0))->save();
        }elseif ($type=='reinit2'){
            $semsetting = M('SemSetting');
            $semsetting->where(array('key'=>'init_task2'))->data(array('value'=>0))->save();
        }
    }

    public function getkeywordbyreport(){
        $semkeyword = M('SemnKeyword');
        $semreport = M('SemnReport');

        $type = !empty($_REQUEST['type'])?$_REQUEST['type']:'';
        $start_date = !empty($_REQUEST['start_date'])?$_REQUEST['start_date']:'';
        $end_date = !empty($_REQUEST['end_date'])?$_REQUEST['end_date']:'';
        $redisHandle=\Com\Tbs\RedisLib::getInstance();
        if($type=='init'){
            for($date=$start_date;$date<=$end_date;$date=date('Y-m-d',strtotime($date." +1 day"))){
                $redisHandle->rPush('task_date',$date);
            }
        }elseif ($type=='list'){
            $result = $redisHandle->lrange ('task_date',0,-1);
            var_dump($result);
        }elseif ($type=='del'){
            $redisHandle->ltrim ('task_date',1,0);
        }elseif ($type=='run'){
            $run_date = $redisHandle->lPop('task_date');
            $where = array();
            $where['urlhistory'] = array('like','%channel=sem&%subchannel=baidu&%');
            $where['addtime'] = array('between',array($run_date." 00:00:00",$run_date." 23:59:59"));
            $orders = TBS_D('Order')->where($where)->field('urlhistory')->select();
            $keywordIds = array();
            foreach ($orders as $order){
                $match_result = preg_match('/tbs_keyword=(\d+)/',$order['urlhistory'],$match);

                if($match_result){
                    $keyword_id = $match['1'];

                    $where = array();
                    $where['channel'] = 'baidu';
                    $where['keyword_id'] = $keyword_id;

                    $count = $semkeyword->where($where)->count();
                    if($count==0){
                        $reportInfo = $semreport->where(array('keyword_id'=>$keyword_id))->find();

                        $data = array();
                        $data['channel'] = 'baidu';
                        if($reportInfo['account_name']=='ps-土拨鼠宁波'){
                            $data['channel_aid'] = 1;
                        }elseif ($reportInfo['account_name']=='nb-土拨鼠深圳'){
                            $data['channel_aid'] = 2;
                        }elseif ($reportInfo['account_name']=='ps-土拨鼠温州'){
                            $data['channel_aid'] = 3;
                        }elseif ($reportInfo['account_name']=='nb-土拨鼠广州'){
                            $data['channel_aid'] = 7;
                        }
                        $data['campaign_id'] = $reportInfo['campaign_id'];
                        $data['adgroup_id'] = $reportInfo['adgroup_id'];
                        $data['keyword_id'] = $reportInfo['keyword_id'];
                        $data['keyword_name'] = $reportInfo['keyword_name'];
                        $data['keyword_status'] = 1;

                        $semkeyword->data($data)->add();
                    }

                }

            }

        }
    }

    public function autorunkeywordreport(){
        $semkeyword = M('SemnKeyword');
        $semreport = M('SemnReport');

        if(!empty($_REQUEST['run_date'])){
            $run_date = $_REQUEST['run_date'];
        }else{
            $run_date = date("Y-m-d",strtotime("-1 day"));
        }

        //1.填补baidu的keyword
        $where = array();
        $where['urlhistory'] = array('like','%channel=sem&%subchannel=baidu&%');
        $where['addtime'] = array('between',array($run_date." 00:00:00",$run_date." 23:59:59"));
        $orders = TBS_D('Order')->where($where)->field('urlhistory')->select();
        $keywordIds = array();
        foreach ($orders as $order){
            $match_result = preg_match('/tbs_keyword=(\d+)/',$order['urlhistory'],$match);

            if($match_result){
                $keyword_id = $match['1'];

                $where = array();
                $where['channel'] = 'baidu';
                $where['keyword_id'] = $keyword_id;

                $count = $semkeyword->where($where)->count();
                echo "channel:baidu--keyword_id:".$keyword_id."<br/>";
                if($count==0){
                    echo "写入"."<br/>";
                    $reportInfo = $semreport->where(array('keyword_id'=>$keyword_id))->find();

                    if(!empty($reportInfo)){
                        $data = array();
                        $data['channel'] = 'baidu';
                        if($reportInfo['account_name']=='ps-土拨鼠宁波'){
                            $data['channel_aid'] = 1;
                        }elseif ($reportInfo['account_name']=='nb-土拨鼠深圳'){
                            $data['channel_aid'] = 2;
                        }elseif ($reportInfo['account_name']=='ps-土拨鼠温州'){
                            $data['channel_aid'] = 3;
                        }elseif ($reportInfo['account_name']=='nb-土拨鼠广州'){
                            $data['channel_aid'] = 7;
                        }
                        $data['campaign_id'] = $reportInfo['campaign_id'];
                        $data['adgroup_id'] = $reportInfo['adgroup_id'];
                        $data['keyword_id'] = $reportInfo['keyword_id'];
                        $data['keyword_name'] = $reportInfo['keyword_name'];
                        $data['keyword_status'] = 1;

                        $semkeyword->data($data)->add();
                    }

                }

            }

        }

        //2.填补sougou的keyword
        $where = array();
        $where['urlhistory'] = array('like','%channel=sem&%subchannel=sougou&%');
        $where['addtime'] = array('between',array($run_date." 00:00:00",$run_date." 23:59:59"));
        $orders = TBS_D('Order')->where($where)->field('urlhistory')->select();
        $keywordIds = array();
        foreach ($orders as $order){
            $match_result = preg_match('/tbs_keyword=(\d+)/',$order['urlhistory'],$match);

            if($match_result){
                $keyword_id = $match['1'];

                $where = array();
                $where['channel'] = 'sougou';
                $where['keyword_id'] = $keyword_id;

                $count = $semkeyword->where($where)->count();
                echo "channel:sougou--keyword_id:".$keyword_id."<br/>";
                if($count==0){
                    echo "写入"."<br/>";
                    $reportInfo = $semreport->where(array('keyword_id'=>$keyword_id))->find();

                    if(!empty($reportInfo)){
                        $data = array();
                        $data['channel'] = 'sougou';
                        if($reportInfo['account_name']=='tobosu@cloudunion.net.cn'){
                            $data['channel_aid'] = 6;
                        }elseif ($reportInfo['account_name']=='tobosu@sogouinc.com'){
                            $data['channel_aid'] = 8;
                        }
                        $data['campaign_id'] = $reportInfo['campaign_id'];
                        $data['adgroup_id'] = $reportInfo['adgroup_id'];
                        $data['keyword_id'] = $reportInfo['keyword_id'];
                        $data['keyword_name'] = $reportInfo['keyword_name'];
                        $data['keyword_status'] = 1;

                        $semkeyword->data($data)->add();
                    }

                }

            }

        }

    }

}
