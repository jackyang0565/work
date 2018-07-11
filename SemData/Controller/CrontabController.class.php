<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/14
 * Time: 20:04
 */

namespace SemData\Controller;
use Think\Controller;

class CrontabController extends Controller
{

    public function index() {
        //0点-9点20分不执行
        if(date('Y-m-d H:i:s')<date('Y-m-d')." 08:00:00"){
            return;
        }
        //配置关闭计划任务
        if (!C('OPEN_CRONTAB')) {
            return;
        }

        set_time_limit(0);
        ignore_user_abort(false);

        $sem_report_file =  APP_PATH.'SemNew/Model/NewCommonReportModel.class.php';
        $crontab_file = './dynamicRes/data/sem/crontab.file';
        $kill_file = './dynamicRes/data/sem/kill.file';
        $param_file = './dynamicRes/data/sem/crontab_param.file';

        if (!file_exists($param_file)) {
            return;
        }

        //5分钟内程序不会被重复执行
        if (file_exists($crontab_file)) {
            $time = file_get_contents($crontab_file);
            if (time() - $time <= 240) {
                return;
            }
        }


        $edit_sem_report_time = filemtime($sem_report_file);

        // 进程一次执行时间过长，服务器性能相对较低时，重启进程
        $start_time = time();
        $end_time = time();
        while(true) {
            // 订单文件发生修改，将重新启动进程
            if ($edit_sem_report_time != filemtime($sem_report_file)) {
                exit;
            }

            // 判断是否杀死此进程
            if (file_exists($kill_file)) {
                exit;
            }

            //如果执行一个任务超过27s,跳出
            if ($end_time - $start_time > 27) {
                exit;
            }
            $start_time = time();

            // 更改此值，此进程正在运行
            file_put_contents($crontab_file, time());
            $has_jobs = false;

            //初始化任务
            if ($this->initTask()) {
                $has_jobs = true;
            }

            //获取任务并执行
            if ($this->runTask()) {
                $has_jobs = true;
            }

            // 没有任务时，停留一段时间，再执行
            if (!$has_jobs) {
                sleep(5);
            }

            $end_time = time();

        }
    }

    //初始化任务
    private function initTask(){
        $module_array = array('baidu'=>'BaiduChannel','360'=>'QihuChannel','sm'=>'SmChannel','sougou'=>'SougouChannel');

        $SemSetting = D('NewSemSetting');
        $SemnAccount = M('SemnAccount');
        $value = $SemSetting->get('init_task2');
        if($value==0) {
            $semtask = M('SemnTask');

            $public_module = array('CampaignChannel');
            $public_task = array(1=>'initSchedule',9=>'busybug');
            foreach ($public_module as $module) {
                foreach ($public_task as $key => $name) {
                    $count = $semtask->where(array('task_module' => $module, 'task_name' => $name))->count();
                    if ($count == 0) {
                        $semtask->data(array('task_module' => $module, 'task_name' => $name, 'task_level' => $key))->add();
                    }
                }
            }

            $channel_module = array('BaiduChannel','QihuChannel','SmChannel','SougouChannel');
            $channel_task = array(7=>'getOrderReport',8=>'getCityReport');
            foreach ($channel_module as $module) {
                foreach ($channel_task as $key => $name) {
                    $count = $semtask->where(array('task_module' => $module, 'task_name' => $name))->count();
                    if ($count == 0) {
                        $semtask->data(array('task_module' => $module, 'task_name' => $name, 'task_level' => $key))->add();
                    }
                }
            }

            $account_module = $SemnAccount->field('id,channel')->select();
            $account_task = array(2=>'getCampaign',3=>'getAdgroup',4=>'getKeyword',5=>'getAccount',6=>'getReport');
            foreach ($account_module as $module) {
                foreach ($account_task as $key => $name) {
                    $count = $semtask->where(array('task_aid'=>$module['id'],'task_module' => $module_array[$module['channel']], 'task_name' => $name))->count();
                    if ($count == 0) {
                        $semtask->data(array('task_aid'=>$module['id'],'task_module' => $module_array[$module['channel']], 'task_name' => $name, 'task_level' => $key))->add();
                    }
                }
            }

            //删除没有使用的账号的计划
            $result = $SemnAccount->where(array('status'=>0))->field('id,channel')->select();
            $module_arr = array();
            $account_arr = array();
            foreach ($result as $value){
                $count = $SemnAccount->where(array('status'=>1,'channel'=>$value['channel']))->count();
                if($count==0){
                    array_push($module_arr,$module_array[$value['channel']]);
                }
                array_push($account_arr,$value['id']);
            }
            if(!empty($account_arr)&&!empty($module_arr)){
                $where = array();
                $where['task_aid'] = array('in',$account_arr);
                $where['task_module'] = array('in',$module_arr);
                $where['_logic'] = 'OR';
                $semtask->where($where)->delete();
            }

            $SemSetting->set('init_task2','1');
        }
        return true;
    }

    //初始化历史日程数据
    public function initSchedule(){
        $semaccount = M('SemnAccount');
        $semschedule = M('SemnSchedule');
        $semdistrictreport = M('SemnDistrictReport');

        $where = array();
        $where['report_date'] = array('neq','');
        $where['account_name'] = array('neq','');
        $result = $semdistrictreport->where($where)->group('report_date,account_name')->field('report_date,account_name')->select();

        $account_result = $semaccount->field('id,username,channel')->select();
        $account_arr = array();
        foreach ($account_result as $value){
            $account_arr[$value['username']]['channel'] = $value['channel'];
            $account_arr[$value['username']]['channel_aid'] = $value['id'];
        }

        foreach ($result as $value){
            $where = array();
            $where['work_date'] = $value['report_date'];

            $where['channel_aid'] = $account_arr[$value['account_name']]['channel_aid'];

            $count = $semschedule->where($where)->count();
            if($count==0){
                $data = array();
                $data['work_date'] = $value['report_date'];
                $data['channel'] =$account_arr[$value['account_name']]['channel'];
                $data['channel_aid'] = $account_arr[$value['account_name']]['channel_aid'];
                $data['cost'] = 0;
                $data['report_finish'] = 1;
                $data['order_finish'] = 1;
                $data['city_finish'] = 0;
                $semschedule->add($data);
            }
        }
    }

    //补充日程
    public function fullSchedule(){
        $semaccount = M('SemnAccount');
        $semschedule = M('SemnSchedule');

        $startday = !empty($_REQUEST['startday'])?$_REQUEST['startday']:'';
        $endday = !empty($_REQUEST['endday'])?$_REQUEST['endday']:'';
        $channel = !empty($_REQUEST['channel'])?$_REQUEST['channel']:'';

        if(!empty($startday)&&!empty($startday)&&!empty($startday)){
            $account_result = $semaccount->where(array('channel'=>$channel))->field('id,username,channel')->find();

            for($date=$startday;$date<=$endday;$date=date('Y-m-d',strtotime($date." +1 day"))){
                $where = array();

                $where['work_date'] = $date;
                $where['channel'] = $account_result['channel'];

                $count = $semschedule->where($where)->count();
                if($count==0){
                    $data = array();
                    $data['work_date'] = $date;
                    $data['channel'] = $account_result['channel'];
                    $data['channel_aid'] = $account_result['id'];
                    $data['cost'] = 0;
                    $data['report_finish'] = 1;
                    $data['order_finish'] = 1;
                    $data['city_finish'] = 0;
                    $semschedule->add($data);
                }
            }
        }else{
            echo '配置不正确';die();
        }

    }

    //获取任务并执行
    private function runTask() {
        $semntask = M('SemnTask');
        $where = array();
        $where['finish'] = 0;
        $task = $semntask->where($where)->order('task_level,task_module,task_aid')->find();
        if(!empty($task)){
            $className = 'SemData\Model\\'.$task['task_module']."Model";
            $model = new $className($task['task_aid']);
            call_user_func_array(array($model,$task['task_name']),array($task['id']));
        }else{
            exit('没有发现任务');
        }
        return true;
    }

}
