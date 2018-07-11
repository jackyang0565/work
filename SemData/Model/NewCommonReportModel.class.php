<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/13 0013
 * Time: 10:22
 */

namespace SemData\Model;
use Common\Model\TbsModel;

class NewCommonReportModel extends TbsModel
{
    protected $tableName = 'semn_channel_report';
    protected $channel = 'sem';
    protected $module = 'sem';

    protected $relation_array = array(
        'baidu_pc'=>'NewBaidu',
        'baidu_wap'=>'NewBaiduWap',
        'baidu_gz'=>'NewBaiduGz',
        'baidu_wz'=>'NewBaiduWz',
        '360_pc'=>'NewQihu',
        'sm_wap'=>'NewSm',
        'sougou_pc'=>'NewSougou',
    );

    public function getChannelName(){
        return $this->channel;
    }

    /**
     * 初始化日程表
     * @param $taskid
     */
    public function initSchedule($taskid){
        $start_date = C('CRONTAB_START_DATE');
        $end_date = date("Y-m-d",strtotime("-1 day"));
        $semschedule = M('SemSchedule');
        $semtask = M('SemTask');

        for($date=$start_date;$date<=$end_date;$date=date('Y-m-d',strtotime($date." +1 day"))){
            $count = $semschedule->where(array('module'=>$this->module,'channel'=>$this->channel,'work_date'=>$date))->count();
            if($count==0){
                $data = array();
                $data['work_date'] = $date;
                $data['channel'] = $this->channel;
                $data['module'] = $this->module;
                $data['report_finish'] = 0;
                $data['order_finish'] = 0;
                $semschedule->add($data);
            }
        }
        $where = array();
        $where['task_name'] = array('in',array('getOrderReport','getCityReport'));
        $where['finish'] = 0;
        $semtask->where($where)->data(array('finish'=>1))->save();

        $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
    }

    public function sum($option = array())
    {
        $semchannelreport = M('SemnChannelReport');
        //按月分组合计字段
        $group_month_sum_filds = array('channel','device','city','account_name', 'channel', 'campaign_name', 'property', ' adgroup_name', "DATE_FORMAT(report_date, '%Y%m') months","DATE_FORMAT(report_date, '%Y%m') report_date", 'sum(views) as views', 'sum(clicks) as clicks', 'sum(cost) as cost','sum(cash) as cash', 'sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //按周分组合计字段
        $group_week_sum_fields = array('channel','device','city','account_name', 'channel', 'campaign_name', 'property', ' adgroup_name', "DATE_FORMAT(report_date, '%Y%u') weeks","CONCAT(date_sub(report_date,INTERVAL WEEKDAY(report_date) + 0 DAY),'~',date_sub(report_date,INTERVAL WEEKDAY(report_date) - 6 DAY)) report_date", 'sum(views) as views', 'sum(clicks) as clicks', 'sum(cost) as cost','sum(cash) as cash', 'sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //按天分组合计字段
        $group_day_sum_fields = array('report_date','channel','device','city','account_name' , 'campaign_name', 'property', ' adgroup_name', 'sum(views) as views', 'sum(clicks) as clicks', 'sum(cost) as cost','sum(cash) as cash', 'sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //按合计统计
        $group_all_sum_fields = array('CONCAT(MIN(report_date),\'~\',MAX(report_date)) as report_date','channel','device','city','account_name' , 'campaign_name', 'property', ' adgroup_name' ,'sum(views) as views','sum(clicks) as clicks','sum(cost) as cost','sum(cash) as cash','sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //总计合计字段
        $sum_fields = array('sum(views) as views','sum(clicks) as clicks','sum(cost) as cost','sum(cash) as cash','sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //查询条件
        if(!empty($option['channel'])&&!in_array($option['channel'],array('open','close'))){
            $where['channel'] = $option['channel'];
        }
        if(!empty($option['city'])&&!in_array($option['city'],array('open','close'))){
            $where['city'] = $option['city'];
        }
        if(!empty($option['account'])&&!in_array($option['account'],array('open','close'))){
            $where['account_name'] = $option['account'];
        }
        if(!empty($option['camp_name'])&&!in_array($option['camp_name'],array('open','close'))){
            $where['campaign_name'] = $option['camp_name'];
        }
        if(!empty($option['group_name'])&&!in_array($option['group_name'],array('open','close'))){
            $where['adgroup_name'] = $option['group_name'];
        }
        if(!empty($option['property'])&&!in_array($option['property'],array('open','close'))){
            $where['property'] = $option['property'];
        }
        if(!empty($option['is_pc'])&&!in_array($option['is_pc'],array('open','close'))){
            $where['device'] = $option['is_pc'];
        }
        //查询时间段
        if ($option['start_date'] && $option['end_date']){
            $where['report_date'] = array(array('egt', $option['start_date']), array('elt', $option['end_date']));
        }

        //查询总和
        $data['sum_data'] = $semchannelreport->field($sum_fields)->where($where)->select();
        $data['sum_data'][0]['start_date'] = $option['start_date'];
        $data['sum_data'][0]['end_date'] = $option['end_date'];
        //排序
        $order_by = "report_date desc,cost desc";

        //分页
        $page = $option['page'] ? $option['page'] : 1;
        $pageSize = $option['pageSize'] ? $option['pageSize'] : 15;

        //分组
        $group_by_arr = array();
        if($option['group_type']=='day'){
            array_push($group_by_arr,'report_date');
        }elseif ($option['group_type']=='weeks'){
            array_push($group_by_arr,'weeks');
        }elseif ($option['group_type']=='months'){
            array_push($group_by_arr,'months');
        }elseif ($option['group_type']=='months'){
            array_push($group_by_arr,'keyword_id');
        }
        if($option['channel']=='open'){
            array_push($group_by_arr,'channel');
        }
        if($option['city']=='open'){
            array_push($group_by_arr,'city');
        }
        if($option['account']=='open'){
            array_push($group_by_arr,'account_name');
        }
        if($option['camp_name']=='open'){
            array_push($group_by_arr,'campaign_name');
        }
        if($option['group_name']=='open'){
            array_push($group_by_arr,'adgroup_name');
        }
        if($option['property']=='open'){
            array_push($group_by_arr,'property');
        }
        if($option['is_pc']=='open'){
            array_push($group_by_arr,'device');
        }
        if(!empty($group_by_arr)){
            if(count($group_by_arr)==1){
                $group_by = $group_by_arr[0];
            }else{
                $group_by = implode(',',$group_by_arr);
            }
        }else{
            $group_by = '';
        }
        //合计
        if ($option['group_type'] == 'weeks') { //分周
            if($option['is_download'] == 1) { //下载
                $data['gourp_sum_data'] = $semchannelreport->field($group_week_sum_fields)->group($group_by)->where($where)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semchannelreport->field($group_week_sum_fields)->group($group_by)->page($option['page'], $option['pageSize'])->where($where)->order($order_by)->select();
                $count = $semchannelreport->field($group_week_sum_fields)->group($group_by)->where($where)->group($group_by)->select();
                $allCount = count($count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        } elseif ($option['group_type'] == 'sum') { // 合计
            if ($option['is_download'] == 1) { //下载
                $data['gourp_sum_data'] = $semchannelreport->field($group_all_sum_fields)->group($group_by)->where($where)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semchannelreport->field($group_all_sum_fields)->group($group_by)->page($option['page'], $option['pageSize'])->where($where)->order($order_by)->select();
                $data_count = $semchannelreport->field($group_all_sum_fields)->group($group_by)->where($where)->select();
                $allCount = count($data_count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        } elseif ($option['group_type'] == 'months') { //分月
            if ($option['is_download'] == 1) { //下载
                $data['gourp_sum_data'] = $semchannelreport->field($group_month_sum_filds)->group($group_by)->where($where)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semchannelreport->field($group_month_sum_filds)->group($group_by)->page($option['page'], $option['pageSize'])->where($where)->order($order_by)->select();
                $data_count = $semchannelreport->field($group_month_sum_filds)->group($group_by)->where($where)->select();
                $allCount = count($data_count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        } elseif ($option['group_type'] == 'day') { //分日
            if($option['is_download'] == 1){ //下载
                $data['gourp_sum_data'] = $semchannelreport->field($group_day_sum_fields)->where($where)->group($group_by)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semchannelreport->field($group_day_sum_fields)->page($option['page'], $option['pageSize'])->where($where)->group($group_by)->order($order_by)->select();
                $data_count = $semchannelreport->field($group_day_sum_fields)->where($where)->group($group_by)->select();
                $allCount = count($data_count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }

        }
        //处理聚拢字段
        $gourp_sum_data = $data['gourp_sum_data'];
        if($option['account']==''||$option['account']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['account_name'] = '聚拢';
            }
        }
        if($option['is_pc']==''||$option['is_pc']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['device'] = '聚拢';
            }
        }
        if($option['camp_name']==''||$option['camp_name']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['campaign_name'] = '聚拢';
            }
        }
        if($option['property']==''||$option['property']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['property'] = '聚拢';
            }
        }
        if($option['group_name']==''||$option['group_name']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['adgroup_name'] = '聚拢';
            }
        }
        if($option['city']==''||$option['city']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['city'] = '聚拢';
            }
        }
        if($option['channel']==''||$option['channel']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['channel'] = '聚拢';
            }
        }
        $dataReturn['dataList'] = $gourp_sum_data;
        $dataReturn['sum_data'] = $data['sum_data'];
        $dataReturn['pageInfo'] = $pageInfo;
        return $dataReturn;
    }

    public function checkOthersExists($channel,$account_name,$report_date){
        $semreport = M('SemnReport');

        $where = array();
        $where['channel'] = $channel;
        $where['report_date'] = $report_date;
        $where['account_name'] = $account_name;
        $where['keyword_name'] = "others";

        $rs = $semreport->where(array($where))->count();
        if($rs==0){
            $data = array();
            $data['channel'] = $channel;
            $data['report_date'] = $report_date;
            $data['channel'] = $channel;
            $data['device'] = "others";
            $data['account_name'] = $account_name;
            $data['campaign_name'] = "others";
            $data['adgroup_name'] = "others";
            $data['keyword_id'] = "others";
            $data['keyword_name'] = "others";
            $data['city'] = "others";
            $data['property'] = "others";
            $semreport->add($data);
        }

    }

    public function sumcity($option = array())
    {
        $semcityreport = M('SemnCityReport');
        //按月分组合计字段
        $group_month_sum_filds = array('channel','device','hot_flag','city','channel',"DATE_FORMAT(report_date, '%Y%m') months","DATE_FORMAT(report_date, '%Y%m') report_date", 'sum(views) as views', 'sum(clicks) as clicks', 'sum(cost) as cost','sum(cash) as cash', 'sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //按周分组合计字段
        $group_week_sum_fields = array('channel','device','hot_flag','city','channel',"DATE_FORMAT(report_date, '%Y%u') weeks","CONCAT(date_sub(report_date,INTERVAL WEEKDAY(report_date) + 0 DAY),'~',date_sub(report_date,INTERVAL WEEKDAY(report_date) - 6 DAY)) report_date", 'sum(views) as views', 'sum(clicks) as clicks', 'sum(cost) as cost','sum(cash) as cash', 'sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //按天分组合计字段
        $group_day_sum_fields = array('report_date','channel','device','hot_flag','city','sum(views) as views', 'sum(clicks) as clicks', 'sum(cost) as cost','sum(cash) as cash', 'sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //按合计统计
        $group_all_sum_fields = array('CONCAT(MIN(report_date),\'~\',MAX(report_date)) as report_date','channel','device','hot_flag','city','sum(views) as views','sum(clicks) as clicks','sum(cost) as cost','sum(cash) as cash','sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //总计合计字段
        $sum_fields = array('sum(views) as views','sum(clicks) as clicks','sum(cost) as cost','sum(cash) as cash','sum(order_nums) as order_nums', 'sum(status0_order_nums) as status0_order_nums', 'sum(status1_order_nums) as status1_order_nums', 'sum(status2_order_nums) as status2_order_nums', 'sum(status3_order_nums) as status3_order_nums', 'sum(status4_order_nums) as status4_order_nums', 'sum(status9_order_nums) as status9_order_nums', 'sum(effect_order_nums) as effect_order_nums', 'sum(order_nums-status1_order_nums) as real_order_nums');

        //查询条件
        if(!empty($option['channel'])&&!in_array($option['channel'],array('open','close'))){
            $where['channel'] = $option['channel'];
        }
        if(isset($option['hot_flag'])&&!in_array($option['hot_flag'],array('open','close',''))){
            $where['hot_flag'] = $option['hot_flag'];
        }
        if(!empty($option['city'])&&!in_array($option['city'],array('open','close'))){
            $where['city'] = $option['city'];
        }
        if(!empty($option['account'])&&!in_array($option['account'],array('open','close'))){
            $where['account_name'] = $option['account'];
        }
        if(!empty($option['is_pc'])&&!in_array($option['is_pc'],array('open','close'))){
            $where['device'] = $option['is_pc'];
        }
        //查询时间段
        if ($option['start_date'] && $option['end_date']){
            $where['report_date'] = array(array('egt', $option['start_date']), array('elt', $option['end_date']));
        }

        //查询总和
        $data['sum_data'] = $semcityreport->field($sum_fields)->where($where)->select();
        //排序
        $order_by = "report_date desc,order_nums desc";

        //分页
        $page = $option['page'] ? $option['page'] : 1;
        $pageSize = $option['pageSize'] ? $option['pageSize'] : 15;

        //分组
        $group_by_arr = array();
        if($option['group_type']=='day'){
            array_push($group_by_arr,'report_date');
        }elseif ($option['group_type']=='weeks'){
            array_push($group_by_arr,'weeks');
        }elseif ($option['group_type']=='months'){
            array_push($group_by_arr,'months');
        }
        if($option['channel']=='open'){
            array_push($group_by_arr,'channel');
        }
        if($option['hot_flag']=='open'){
            array_push($group_by_arr,'hot_flag');
        }
        if($option['city']=='open'){
            array_push($group_by_arr,'city');
        }
        if($option['is_pc']=='open'){
            array_push($group_by_arr,'device');
        }
        if(!empty($group_by_arr)){
            if(count($group_by_arr)==1){
                $group_by = $group_by_arr[0];
            }else{
                $group_by = implode(',',$group_by_arr);
            }
        }else{
            $group_by = '';
        }
        //合计
        if ($option['group_type'] == 'weeks') { //分周
            if($option['is_download'] == 1) { //下载
                $data['gourp_sum_data'] = $semcityreport->field($group_week_sum_fields)->group($group_by)->where($where)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semcityreport->field($group_week_sum_fields)->group($group_by)->page($option['page'], $option['pageSize'])->where($where)->order($order_by)->select();
                $count = $semcityreport->field($group_week_sum_fields)->group($group_by)->where($where)->group($group_by)->select();
                $allCount = count($count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        } elseif ($option['group_type'] == 'sum') { // 合计
            if ($option['is_download'] == 1) { //下载
                $data['gourp_sum_data'] = $semcityreport->field($group_all_sum_fields)->group($group_by)->where($where)->order("order_nums desc")->select();
            } else {
                $data['gourp_sum_data'] = $semcityreport->field($group_all_sum_fields)->group($group_by)->page($option['page'], $option['pageSize'])->where($where)->order("order_nums desc")->select();
                $data_count = $semcityreport->field($group_all_sum_fields)->group($group_by)->where($where)->select();
                $allCount = count($data_count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        } elseif ($option['group_type'] == 'months') { //分月
            if ($option['is_download'] == 1) { //下载
                $data['gourp_sum_data'] = $semcityreport->field($group_month_sum_filds)->group($group_by)->where($where)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semcityreport->field($group_month_sum_filds)->group($group_by)->page($option['page'], $option['pageSize'])->where($where)->order($order_by)->select();
                $data_count = $semcityreport->field($group_month_sum_filds)->group($group_by)->where($where)->select();
                $allCount = count($data_count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        } elseif ($option['group_type'] == 'day') { //分日
            if($option['is_download'] == 1){ //下载
                $data['gourp_sum_data'] = $semcityreport->field($group_day_sum_fields)->where($where)->group($group_by)->order($order_by)->select();
            } else {
                $data['gourp_sum_data'] = $semcityreport->field($group_day_sum_fields)->page($option['page'], $option['pageSize'])->where($where)->group($group_by)->order($order_by)->select();
                $data_count = $semcityreport->field($group_day_sum_fields)->where($where)->group($group_by)->select();
                $allCount = count($data_count,0);
                $pageInfo = $this->pagination($page, $allCount, $pageSize, 10);
            }
        }
        //处理聚拢字段
        $gourp_sum_data = $data['gourp_sum_data'];
        if($option['is_pc']==''||$option['is_pc']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['device'] = '聚拢';
            }
        }
        if($option['hot_flag']==''||$option['hot_flag']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['hot_flag'] = '聚拢';
            }
        }
        if($option['city']==''||$option['city']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['city'] = '聚拢';
            }
        }
        if($option['channel']==''||$option['channel']=='close'){
            foreach ($gourp_sum_data as &$temp){
                $temp['channel'] = '聚拢';
            }
        }
        $dataReturn['dataList'] = $gourp_sum_data;
        $dataReturn['sum_data'] = $data['sum_data'];
        $dataReturn['pageInfo'] = $pageInfo;
        return $dataReturn;
    }

    public function checkCampaign(){
        $semtask = M('SemTask');
        //获取系统计划数
        $count = $this->getCampaignNums();
        //获取api计划数
        $ids = $this->getAllCpcPlanId();
        $api_count = count($ids);
        if($count!=$api_count){
            $semtask->where(array('task_module'=>$this->relation_array[$this->module],'task_name'=>'getCampaign'))->data(array('finish'=>0))->save();
            $semtask->where(array('task_module'=>$this->relation_array[$this->module],'task_name'=>'getAdgroup'))->data(array('finish'=>0))->save();
        }
    }

    public function getCampaignNums(){
        $semcampaign = M('SemCampaign');
        $count = $semcampaign->where(array('module'=>$this->module))->count();
        return $count;
    }

}
