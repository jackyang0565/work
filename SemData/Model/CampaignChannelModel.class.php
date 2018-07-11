<?php
/**
 * Created by PhpStorm.
 * User: gerrant
 * Date: 2017/10/11
 * Time: 14:55
 */

namespace SemData\Model;

class CampaignChannelModel
{

    public $channel = '';
    public $loginInfo;
    public $return_point='';
    public $account;

    public function __construct($task_aid)
    {
        $SemnAccount = M('SemnAccount');
        if(!empty($task_aid)){
            $account = $SemnAccount->where(array('id'=>$task_aid))->find();
            $this->account = $account;
            $this->return_point = $account['return_point'];
        }else{
            $this->return_point = $SemnAccount->where(array('channel'=>$this->channel))->getField('return_point');
        }
    }

    public function initSchedule($taskid){
        $semschedule = M('SemnSchedule');
        $semtask = M('SemnTask');
        $SemnAccount = M('SemnAccount');

        $accounts = $SemnAccount->where(array('status'=>1))->select();
        foreach ($accounts as $account){
            $start_date = $account['start_date'];
            $end_date = date("Y-m-d",strtotime("-1 day"));
            for($date=$start_date;$date<=$end_date;$date=date('Y-m-d',strtotime($date." +1 day"))){
                $count = $semschedule->where(array('channel'=>$account['channel'],'channel_aid'=>$account['id'],'work_date'=>$date))->count();
                if($count==0){
                    $data = array();
                    $data['work_date'] = $date;
                    $data['channel'] = $account['channel'];
                    $data['channel_aid'] = $account['id'];
                    $data['cost'] = 0;
                    $data['report_finish'] = 0;
                    $data['order_finish'] = 1;
                    $data['city_finish'] = 1;
                    $data['deal_time'] = 0;
                    $semschedule->add($data);
                }
            }
        }

        //创建日常关闭总任务重的报告任务
        $where = array();
        $where['task_name'] = array('in',array('getOrderReport','getCityReport'));
        $where['finish'] = 0;
        $semtask->where($where)->data(array('finish'=>1))->save();

        $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
    }

    public function busybug(){
        $semaccount = M('SemnAccount');
        $semschedule = M('SemnSchedule');
        $ordermodel = TBS_D('Order');
        $semreport = M('SemnReport');
        $semchannelreport = M('SemnChannelReport');
        $semcityreport = M('SemnCityReport');
        $semtask = M('SemnTask');

        //1.判断该不该建立新的日程任务，更新
        $result = $semschedule->field('max(work_date) as max_date')->find();
        if($result['max_date']<date('Y-m-d',strtotime("-1 day"))){
            $semtask->where(array('task_name'=>'initSchedule'))->data(array('finish'=>0))->save();
            $where = array();
            $where['task_module'] = 'BaiduChannel';
            $where['task_name'] = 'getCampaign';
            $semtask->where($where)->data(array('finish'=>0))->save();
            $where = array();
            $where['task_module'] = 'BaiduChannel';
            $where['task_name'] = 'getAdgroup';
            $semtask->where($where)->data(array('finish'=>0))->save();
//          $semtask->where(array('task_name'=>'getAccount'))->data(array('finish'=>0))->save();
        }

        //2.检查报告数是否异常
        /*$where = array();
        $where['report_error'] = 0;
        $where['cost'] = array('neq',0);
        $where['report_finish'] = 1;
        $where['order_finish'] = 1;
        $where['city_finish'] = 1;
        $schedule = $semschedule->where($where)->find();
        if(!empty($schedule)) {
            $account_name = $semaccount->where(array('id'=>$schedule['channel_aid']))->getField('username');
            $where = array();
            $where['account_name'] = $account_name;
            $where['report_date'] = $schedule['work_date'];
            $total_cost = $semreport->where($where)->sum('cost');

            if($total_cost==$schedule['cost']){
                $semschedule->where(array('id'=>$schedule['id']))->data(array('report_error'=>1))->save();
            }else{
                $semschedule->where(array('id'=>$schedule['id']))->data(array('report_error'=>2))->save();
            }
        }*/

        //3.检查订单数值是否需要更新(计划报表)
        /*$time = time();
        $auto_run_time = S('semdata_auto_run_datetime');
        if(!$auto_run_time||$time-$auto_run_time>3600*6){
            $start_date = "2018-01-01";
            $end_date = date("Y-m-d",strtotime("-1 day"));

            $run_arr = array('baidu','360','sm','sougou');
            foreach ($run_arr as $channeltype) {
                //1.渠道报告
                $field = "DATE_FORMAT(addtime, '%Y-%m') month,count(*) nums";
                $where = array();
                $where['channel_source'] = 4;
                $where['urlhistory'] = array('like','%channel=sem&%subchannel='.$channeltype.'&%');
                $where['addtime'] = array('between',array($start_date." 00:00:00",$end_date." 23:59:59"));
                $where['dealstatus'] = array('in',array('6','7','8'));
                $where['autoSplitComNum'] = array('gt',0);
                $month_effect_addtime_order_arr  = $ordermodel->where($where)->field($field)->group("month")->select();

                $field = "DATE_FORMAT(report_date, '%Y-%m') month,sum(effect_order_nums) nums";
                $where = array();
                $where['channel'] = $channeltype;
                $where['report_date'] = array('between',array($start_date,$end_date));
                $month_effect_order_channel_result  = $semchannelreport->where($where)->field($field)->group("month")->select();
                $month_effect_order_channel_arr = array();
                foreach ($month_effect_order_channel_result as $value){
                    $month_effect_order_channel_arr[$value['month']] = $value['nums'];
                }

                foreach ($month_effect_addtime_order_arr as $key=>$value){
                    if($value['nums']!=$month_effect_order_channel_arr[$value['month']]){
                        $month_arr = explode('-',$value['month']);
                        $days = mFristAndLast($month_arr[0],$month_arr[1]);
                        $where = array();
                        $where['channel'] = $channeltype;
                        $where['work_date'] = array('between',array($days['firstday'],$days['lastday']));
                        if($channeltype=='baidu'){
                            $semschedule->where($where)->data(array('order_finish'=>0,'city_finish'=>0))->save();
                        }else{
                            $semschedule->where($where)->data(array('order_finish'=>0))->save();
                        }
                    }
                }
                //2.城市报告
                if($channeltype=='baidu'){
                }else{
                    $field = "DATE_FORMAT(effecttime, '%Y-%m') month,count(*) nums";
                    $where = array();
                    $where['channel_source'] = 4;
                    $where['urlhistory'] = array('like', '%channel=sem&%subchannel=' . $channeltype . '&%');
                    $where['effecttime'] = array('between', array($start_date . " 00:00:00", $end_date . " 23:59:59"));
                    $where['dealstatus'] = array('in', array('6', '7', '8'));
                    $where['autoSplitComNum'] = array('gt', 0);
                    $month_effect_effecttime_order_arr = $ordermodel->where($where)->field($field)->group("month")->select();
                }

                $field = "DATE_FORMAT(report_date, '%Y-%m') month,sum(effect_order_nums) nums";
                $where = array();
                $where['channel'] = $channeltype;
                $where['report_date'] = array('between', array($start_date, $end_date));
                $month_effect_city_city_result = $semcityreport->where($where)->field($field)->group("month")->select();
                $month_effect_city_city_arr = array();
                foreach ($month_effect_city_city_result as $value) {
                    $month_effect_city_city_arr[$value['month']] = $value['nums'];
                }

                foreach ($month_effect_effecttime_order_arr as $key => $value) {
                    if ($value['nums'] != $month_effect_city_city_arr[$value['month']]) {
                        $month_arr = explode('-', $value['month']);
                        $days = mFristAndLast($month_arr[0], $month_arr[1]);
                        $where = array();
                        $where['channel'] = $channeltype;
                        $where['work_date'] = array('between', array($days['firstday'], $days['lastday']));
                        $semschedule->where($where)->data(array('city_finish' => 0))->save();
                    }
                }

            }

            S('semdata_auto_run_datetime',$time);
        }*/

        //4.判断有没有需要执行的任务
        $no_report = $semschedule->where(array('report_finish'=>0))->select();
        if(!empty($no_report)){
            $task_module_arr = array();
            foreach ($no_report as $report){
                if($report['channel']=="360"){
                    $report['channel'] = 'qihu';
                }
                $task_module_arr[] = ucfirst($report['channel'])."Channel";
            }
            $where = array();
            $where['task_module'] = array('in',array_unique($task_module_arr));
            $where['task_name'] = 'getReport';
            $semtask->where($where)->data(array('finish'=>0))->save();
        }
        $no_order = $semschedule->where(array('order_finish'=>0))->select();
        if(!empty($no_order)){
            $task_module_arr = array();
            foreach ($no_order as $report){
                if($report['channel']=="360"){
                    $report['channel'] = 'qihu';
                }
                $task_module_arr[] = ucfirst($report['channel'])."Channel";
            }
            $where = array();
            $where['task_module'] = array('in',array_unique($task_module_arr));
            $where['task_name'] = 'getOrderReport';
            $semtask->where($where)->data(array('finish'=>0))->save();
        }
        $city_report = $semschedule->where(array('city_finish'=>0))->select();
        if(!empty($city_report)){
            $task_module_arr = array();
            foreach ($city_report as $report){
                if($report['channel']=="360"){
                    $report['channel'] = 'qihu';
                }
                $task_module_arr[] = ucfirst($report['channel'])."Channel";
            }
            $where = array();
            $where['task_module'] = array('in',array_unique($task_module_arr));
            $where['task_name'] = 'getCityReport';
            $semtask->where($where)->data(array('finish'=>0))->save();
        }



    }

}