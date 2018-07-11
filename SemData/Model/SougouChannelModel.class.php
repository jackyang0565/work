<?php
/**
 * Created by PhpStorm.
 * User: gerrant
 * Date: 2017/10/11
 * Time: 14:57
 */

namespace SemData\Model;


class SougouChannelModel extends CampaignChannelModel
{

    public function __construct($task_aid)
    {
        $this->channel = 'sougou';
        parent::__construct($task_aid);
        if(!empty($task_aid)){
            $this->loginInfo = array('name'=>$this->account['username'],'pwd'=>$this->account['password'],'apiKey'=>$this->account['token']);
        }
    }


    /************************************推广计划***************************************************/

    public function getCampaign($taskid){
        $semtask = M('SemnTask');
        $semcampaign = M('SemnCampaign');

        $allcamp = S($this->account['id'].'_allcamp');
        if(empty($allcamp)){
            $allcamp = $this->getAllCpcPlan();
            $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
            S($this->account['id'].'_allcamp',$allcamp,$remain_time);
        }

        foreach ($allcamp as &$camp){
            $where = array();
            $where['channel_aid'] = $this->account['id'];
            $where['campaign_id'] = $camp['cpcPlanId'];
            $count = $semcampaign->where($where)->count();
            if($count==0){
                $data['channel'] = $this->channel;
                $data['channel_aid'] = $this->account['id'];
                $data['campaign_id'] = $camp['cpcPlanId'];
                $data['campaign_name'] = $camp['cpcPlanName'];
                if($camp['pause']){
                    $data['campaign_status'] = 2;
                }else{
                    $data['campaign_status'] = 1;
                }
                $data['status'] = 0;
                $semcampaign->add($data);
            }
        }

        $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
    }


    /************************************推广组***************************************************/

    public function getAdgroup($taskid){
        $semtask = M('SemnTask');
        $semcampaign = M('SemnCampaign');
        $semadgroup = M('SemnAdgroup');
        $semproperty = M('SemnProperty');

        $result = $semcampaign->where(array('channel_aid'=>$this->account['id'],'status'=>0))->limit(7)->field('campaign_id')->select();
        $campaignIds = array();
        foreach ($result as $value){
            array_push($campaignIds,$value['campaign_id']);
        }

        if(!empty($campaignIds)){
            $adgroup = S($this->account['id'].'_adgroups'.md5(json_encode($campaignIds)));
            if(empty($adgroup)){
                $adgroup = $this->getCpcGrpByCpcGrpIds($campaignIds);
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S($this->account['id'].'_adgroups'.md5(json_encode($campaignIds)),$adgroup,$remain_time);
            }
            foreach ($adgroup as $key=>$ad){
                $where = array();
                $where['channel_aid'] = $this->account['id'];
                $where['adgroup_id'] = $ad['cpcGrpId'];
                $count = $semadgroup->where($where)->count();
                if($count==0){
                    $data = array();
                    $data['channel'] = $this->channel;
                    $data['channel_aid'] = $this->account['id'];
                    $data['campaign_id'] = $ad['cpcPlanId'];
                    $data['adgroup_id'] = $ad['cpcGrpId'];
                    $data['adgroup_name'] = $ad['cpcGrpName'];
                    if($ad['pause']){
                        $data['adgroup_status'] = 2;
                    }else{
                        $data['adgroup_status'] = 1;
                    }
                    $data['status'] = 0;
                    $semadgroup->add($data);
                }
                //词性
                $where = array();
                $where['channel_aid'] = $this->account['id'];
                $adgroup_name_arr = explode('-',$ad['cpcGrpName']);
                $where['property'] = $adgroup_name_arr[count($adgroup_name_arr)-2];
                $count = $semproperty->where($where)->count();
                if($count==0&&!empty($where['property'])){
                    $data = array();
                    $data['channel'] = $this->channel;
                    $data['channel_aid'] = $this->account['id'];
                    $data['campaign_id'] = $ad['cpcPlanId'];
                    $data['property'] = $where['property'];
                    $semproperty->add($data);
                }
            }

            $semcampaign->where(array('channel_aid'=>$this->account['id'],'campaign_id'=>array('in',$campaignIds)))->data(array('status'=>1))->save();
        }else{
            $semcampaign->where(array('channel_aid'=>$this->account['id'],'status'=>1))->data(array('status'=>0))->save();
            $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
        }

    }


    /************************************推广关键词***************************************************/

    public function getKeyword($taskid){
        $semtask = M('SemnTask');
        $semadgroup = M('SemnAdgroup');
        $semkeyword = M('SemnKeyword');

        $campaign_arr = $semadgroup->where(array('channel_aid'=>$this->account['id']))->getField('adgroup_id,campaign_id');

        $result = $semadgroup->where(array('channel_aid'=>$this->account['id'],'status'=>0))->limit(45)->field('adgroup_id')->select();
        $adgroupIds = array();
        foreach ($result as $value){
            array_push($adgroupIds,$value['adgroup_id']);
        }

        if(!empty($adgroupIds)){
            $keywords = S($this->account['id'].'_keyword'.md5(json_encode($adgroupIds)));
            if(empty($keywords)){
                $keywords = $this->getCpcByCpcGrpId($adgroupIds);
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S($this->account['id'].'_keyword'.md5(json_encode($adgroupIds)),$keywords,$remain_time);
            }

            $where = array();
            $where['channel_aid'] = $this->account['id'];
            $where['adgroup_id'] = array('in',$adgroupIds);
            $exist_keyword = $semkeyword->where($where)->count();

            if($exist_keyword!=count($keywords)&&$exist_keyword!=0&&count($keywords)!=0){
                $semkeyword->where($where)->delete();

                $keyword_info = array();
                foreach ($keywords as $key=>$keyword){
                    $data = array();
                    $data['channel'] = $this->channel;
                    $data['channel_aid'] = $this->account['id'];
                    $data['campaign_id'] = $campaign_arr[$keyword['cpcGrpId']];
                    $data['adgroup_id'] = $keyword['cpcGrpId'];
                    $data['keyword_id'] = $keyword['cpcId'];
                    $data['keyword_name'] = $keyword['cpc'];
                    if($keyword['pause']){
                        $data['keyword_status'] = 2;
                    }else{
                        $data['keyword_status'] = 1;
                    }
                    array_push($keyword_info,$data);
                }

                $datas_arr = array_chunk($keyword_info,1000);
                foreach ($datas_arr as &$datas){
                    $semkeyword->addAll($datas);
                }
            }elseif ($exist_keyword==0&&count($keywords)!=0){
                $keyword_info = array();
                foreach ($keywords as $key=>$keyword){
                    $data = array();
                    $data['channel'] = $this->channel;
                    $data['channel_aid'] = $this->account['id'];
                    $data['campaign_id'] = $campaign_arr[$keyword['cpcGrpId']];
                    $data['adgroup_id'] = $keyword['cpcGrpId'];
                    $data['keyword_id'] = $keyword['cpcId'];
                    $data['keyword_name'] = $keyword['cpc'];
                    if($keyword['pause']){
                        $data['keyword_status'] = 2;
                    }else{
                        $data['keyword_status'] = 1;
                    }
                    array_push($keyword_info,$data);
                }

                $datas_arr = array_chunk($keyword_info,1000);
                foreach ($datas_arr as &$datas){
                    $semkeyword->addAll($datas);
                }
            }

            $semadgroup->where(array('channel_aid'=>$this->account['id'],'adgroup_id'=>array('in',$adgroupIds)))->data(array('status'=>1))->save();
        }else{
            $semadgroup->where(array('channel_aid'=>$this->account['id']))->data(array('status'=>0))->save();
            $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
        }
    }

    /************************************获取账户信息***************************************************/


    public function getAccount($taskid){
        $semtask = M('SemnTask');
        $semschedule = M('SemnSchedule');

        $result = $semschedule->where(array('channel_aid'=>$this->account['id']))->field('MIN(work_date) as min')->find();
        $startDate = $result['min'];
        $result = $semschedule->where(array('channel_aid'=>$this->account['id']))->field('MAX(work_date) as max')->find();
        $endDate = $result['max'];

        $info = $this->getAccountInfo($startDate,$endDate);

        foreach ($info as $value){
            if(!empty($value['kpis']['2'])){
                $where = array();
                $where['channel_aid'] = $this->account['id'];
                $where['work_date'] = $value['date'];
                $where['report_error'] = array('neq',1);
                $data = array();
                $data['cost'] = $value['kpis']['2'];
                $semschedule->where($where)->data($data)->save();
            }
        }

        $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
    }

    /************************************推广报告***************************************************/

    public function getReport($taskid){
        $semcampaign = M('SemnCampaign');
        $semreport = M('SemnReport');
        $semschedule = M('SemnSchedule');
        $semtask = M('SemnTask');

        $schedule = $semschedule->where(array('channel_aid'=>$this->account['id'],'report_finish'=>0))->find();
        if(!empty($schedule)){

            $report = S($this->account['id']."_report_".$schedule['work_date']);
            if(empty($report)){
                $report = $this->getReportInfo($schedule['work_date']);
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S($this->account['id']."_report_".$schedule['work_date'],$report,$remain_time);
            }

            if(!empty($report)){
                $datas = array();
                foreach ($report as $key=>$value){
                    $data = array();
                    $data['keyword_id'] = $value['keyword_id'];
                    $data['channel'] = $this->channel;
                    $data['views'] = $value['views'];
                    $data['clicks'] = $value['clicks'];
                    $data['cost'] = $value['cost'];
                    if($data['cost']==0){
                        $data['cash'] = 0;
                    }else{
                        $data['cash'] = $data['cost']/$this->return_point;
                    }
                    $data['account_name'] = $this->loginInfo['name'];
                    $data['campaign_id'] = $value['campaign_id'];
                    $data['campaign_name'] = $value['campaign_name'];
                    //城市
                    $campaign_name_arr = explode('-',$data['campaign_name']);
                    $data['city'] = !empty($campaign_name_arr[count($campaign_name_arr)-2])?$campaign_name_arr[count($campaign_name_arr)-2]:'无城市';
                    $data['adgroup_id'] = $value['adgroup_id'];
                    $data['adgroup_name'] = $value['adgroup_name'];
                    //词性
                    $adgroup_name_arr = explode('-',$data['adgroup_name']);
                    $data['property'] = $adgroup_name_arr[count($adgroup_name_arr)-2];
                    if(empty($data['property'])){
                        $data['property'] = '其他';
                    }
                    $data['keyword_name'] = $value['keyword_name'];
                    $data['report_date'] = $schedule['work_date'];
                    $data['device'] = $value['device'];
                    array_push($datas,$data);
                }

                $datas_arr = array_chunk($datas,1000);
                foreach ($datas_arr as $data){
                    $semreport->addAll($data);
                }
            }

            $semschedule->where(array('channel_aid'=>$this->account['id'],'work_date'=>$schedule['work_date']))->data(array('report_finish'=>1))->save();
        }else{
            $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
        }

    }

    public function getReportInfo($date){
        $file_path = "./dynamicRes/data/sem/".$this->account['id'];
        if (!file_exists($file_path)){
            mkdir($file_path);
        }
        $pc_file_name = "./dynamicRes/data/sem/".$this->account['id']."/report_".$date."_pc.csv";
        $wap_file_name = "./dynamicRes/data/sem/".$this->account['id']."/report_".$date."_wap.csv";

        if(!file_exists($pc_file_name)) {
            $task_id = S($this->account['id'] . '_reportid_1_' . $date);
            if (empty($task_id)) {
                $task_id = $this->getKeywordReportByCampaignIds($date,1);
                $remain_time = strtotime(date('Y-m-d') . " 23:59:59") - time();
                S($this->account['id'] . '_reportid_1_' . $date, $task_id, $remain_time);
            }
            sleep(2);
            $task_status = $this->getTaskByTaskId($task_id);
            if ($task_status == '1') {
                $filepath = $this->downloadFileByFileId($task_id);
                $csv_content = gzdecode(file_get_contents($filepath));
                file_put_contents($pc_file_name,$csv_content);
            } else {
                file_put_contents($pc_file_name,"编号,日期,账户,推广计划ID,推广计划,推广组ID,推广组,关键词id,关键词,消耗,点击数,展示数");
            }
        }

        if(!file_exists($wap_file_name)) {
            $task_id = S($this->account['id'] . '_reportid_2_' . $date);
            if (empty($task_id)) {
                $task_id = $this->getKeywordReportByCampaignIds($date,2);
                $remain_time = strtotime(date('Y-m-d') . " 23:59:59") - time();
                S($this->account['id'] . '_reportid_2_' . $date, $task_id, $remain_time);
            }
            sleep(2);
            $task_status = $this->getTaskByTaskId($task_id);
            if ($task_status == '1') {
                $filepath = $this->downloadFileByFileId($task_id);
                $csv_content = gzdecode(file_get_contents($filepath));
                file_put_contents($wap_file_name,$csv_content);
            } else {
                file_put_contents($wap_file_name,"编号,日期,账户,推广计划ID,推广计划,推广组ID,推广组,关键词id,关键词,消耗,点击数,展示数");
            }
        }

        if(file_exists($pc_file_name)&&file_exists($wap_file_name)){
            $pc_csv_object = new \SplFileObject($pc_file_name, 'rb');
            $pc_csv_object->seek(filesize($pc_file_name));
            $pc_line = $pc_csv_object->key();
            $pc_array = array();
            if($pc_line>1){
                $pc_csv_object->seek(0);
                while (!$pc_csv_object->eof()) {
                    $content = $pc_csv_object->fgetcsv();

                    if(is_numeric($content[7])){
                        $rs['campaign_id'] = $content[3];
                        $rs['campaign_name'] = mb_convert_encoding($content[4], "UTF-8", "GBK");;
                        $rs['adgroup_id'] = $content[5];
                        $rs['adgroup_name'] = mb_convert_encoding($content[6], "UTF-8", "GBK");;
                        $rs['keyword_id'] = $content[7];
                        $rs['keyword_name'] = mb_convert_encoding($content[8], "UTF-8", "GBK");;
                        $rs['views'] = $content[11];
                        $rs['clicks'] = $content[10];
                        $rs['cost'] = $content[9];
                        $rs['device'] = 1;

                        $pc_array[] = $rs;
                    }

                    $pc_csv_object->next();
                }
            }

            $wap_csv_object = new \SplFileObject($wap_file_name, 'rb');
            $wap_csv_object->seek(filesize($wap_file_name));
            $wap_line = $wap_csv_object->key();
            $wap_array = array();
            if($wap_line>1){
                $wap_csv_object->seek(0);
                while (!$wap_csv_object->eof()) {
                    $content = $wap_csv_object->fgetcsv();

                    if(is_numeric($content[7])){
                        $rs['campaign_id'] = $content[3];
                        $rs['campaign_name'] = mb_convert_encoding($content[4], "UTF-8", "GBK");;
                        $rs['adgroup_id'] = $content[5];
                        $rs['adgroup_name'] = mb_convert_encoding($content[6], "UTF-8", "GBK");;
                        $rs['keyword_id'] = $content[7];
                        $rs['keyword_name'] = mb_convert_encoding($content[8], "UTF-8", "GBK");;
                        $rs['views'] = $content[11];
                        $rs['clicks'] = $content[10];
                        $rs['cost'] = $content[9];
                        $rs['device'] = 2;

                        $wap_array[] = $rs;
                    }

                    $wap_csv_object->next();
                }
            }

            $result = array_merge($pc_array,$wap_array);

            if(!empty($result)){
                return $result;
            }else{
                return 1;
            }
        }else{
            echo $this->module.'任务'.$task_id.'正在进行中';
        }

    }

    /************************************渠道报告***************************************************/


    public function getOrderReport($taskid){
        $semschedule = M('SemnSchedule');
        $semtask = M('SemnTask');
        $ordermodel = TBS_D('Order');
        $semreport = M('SemnReport');
        $semchannelreport = M('SemnChannelReport');

        $schedule = $semschedule->where(array('channel'=>$this->channel,'order_finish'=>0,'report_finish'=>1))->find();
        if(!empty($schedule)){
            //--------------------------1.初始化条目-----------------------------------//
            //1.获取发布订单信息
            $real_fabu_orders = S("real_fabu_orders_channel_".$this->channel.$schedule['work_date']);
            if(empty($real_fabu_orders)){
                $real_fabu_orders  = $ordermodel->where(array('urlhistory'=>array('like','%channel=sem&%subchannel=sougou%'),'addtime'=>array('between',array($schedule['work_date']." 00:00:00",$schedule['work_date']." 23:59:59"))))->getField('orderid,dealstatus,urlhistory,shi');
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S("real_fabu_orders_channel_".$this->channel.$schedule['work_date'],$real_fabu_orders,$remain_time);
            }
            //2.获取有效订单信息
            $real_effect_orders = S("real_effect_orders_channel_".$this->channel.$schedule['work_date']);
            if(empty($real_effect_orders)){
                $real_effect_orders  = $ordermodel->where(array('urlhistory'=>array('like','%channel=sem&%subchannel=sougou%'),'addtime'=>array('between',array($schedule['work_date']." 00:00:00",$schedule['work_date']." 23:59:59"))))->getField('orderid,dealstatus,urlhistory,shi,autoSplitComNum as fd');
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S("real_effect_orders_channel_".$this->channel.$schedule['work_date'],$real_effect_orders,$remain_time);
            }
            //3.组合初始数据
            $count = $semchannelreport->where(array('report_date'=>$schedule['work_date'],'channel'=>$this->channel))->count();
            if($count==0){
                $where = array();
                $where['report_date'] = $schedule['work_date'];
                $where['channel'] = $this->channel;
                $field = "report_date,channel,account_name,device,campaign_id,campaign_name,city,adgroup_id,adgroup_name,property,sum(views) as views,sum(clicks) as clicks,sum(cost) as cost,sum(cash) as cash";
                $result = $semreport->where($where)->field($field)->group("adgroup_id,device")->select();
                $data = array('report_date'=>$schedule['work_date'],'channel'=>$this->channel,'account_name'=>'others','device'=>1,'campaign_id'=>'1002','campaign_name'=>'others','city'=>'others','adgroup_id'=>'1002','adgroup_name'=>'others','property'=>'others','views'=>'0','clicks'=>'0','cost'=>'0','cash'=>'0');
                array_push($result,$data);
                $data = array('report_date'=>$schedule['work_date'],'channel'=>$this->channel,'account_name'=>'others','device'=>2,'campaign_id'=>'1002','campaign_name'=>'others','city'=>'others','adgroup_id'=>'1002','adgroup_name'=>'others','property'=>'others','views'=>'0','clicks'=>'0','cost'=>'0','cash'=>'0');
                array_push($result,$data);

                $datas_arr = array_chunk($result,1000);
                foreach ($datas_arr as &$datas){
                    $semchannelreport->addAll($datas);
                }
            }
            //--------------------------2.填充数据-----------------------------------//
            //初始化
            $data = array();
            $data['order_nums'] = 0;
            $data['status0_order_nums'] = 0;
            $data['status1_order_nums'] = 0;
            $data['status2_order_nums'] = 0;
            $data['status3_order_nums'] = 0;
            $data['status4_order_nums'] = 0;
            $data['status9_order_nums'] = 0;
            $data['effect_order_nums'] = 0;
            $semchannelreport->where(array('channel'=>$this->channel,'report_date'=>$schedule['work_date']))->data($data)->save();
            //填充
            $pcdata = array();
            $h5data = array();
            foreach ($real_fabu_orders as $fabu_order){
                $url_info = parse_url(urldecode($fabu_order['urlhistory']));
                parse_str($url_info['query'], $param_info);

                $where = array();
                $where['report_date'] = $schedule['work_date'];
                $where['channel'] = $this->channel;
                $where['campaign_name'] = $param_info['tbs_campaign'];
                $where['group_name'] = $param_info['tbs_group'];
                $where['keyword_name'] = $param_info['tbs_keyword'];

                $adgroup_id = $semreport->where($where)->getField('adgroup_id');
                if(!empty($adgroup_id)){
                    if(strpos($fabu_order['urlhistory'],'m.tobosu.com')===false){
                        $pcdata[$adgroup_id]['order_nums'] = $pcdata[$adgroup_id]['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $pcdata[$adgroup_id]['status0_order_nums'] = $pcdata[$adgroup_id]['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $pcdata[$adgroup_id]['status1_order_nums'] = $pcdata[$adgroup_id]['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $pcdata[$adgroup_id]['status2_order_nums'] = $pcdata[$adgroup_id]['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $pcdata[$adgroup_id]['status3_order_nums'] = $pcdata[$adgroup_id]['status3_order_nums'] + 1;
                        }
                    }else{
                        $h5data[$adgroup_id]['order_nums'] = $h5data[$adgroup_id]['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $h5data[$adgroup_id]['status0_order_nums'] = $h5data[$adgroup_id]['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $h5data[$adgroup_id]['status1_order_nums'] = $h5data[$adgroup_id]['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $h5data[$adgroup_id]['status2_order_nums'] = $h5data[$adgroup_id]['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $h5data[$adgroup_id]['status3_order_nums'] = $h5data[$adgroup_id]['status3_order_nums'] + 1;
                        }
                    }
                }else{
                    if(strpos($fabu_order['urlhistory'],'m.tobosu.com')===false){
                        $pcdata['1002']['order_nums'] = $pcdata['1002']['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $pcdata['1002']['status0_order_nums'] = $pcdata['1002']['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $pcdata['1002']['status1_order_nums'] = $pcdata['1002']['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $pcdata['1002']['status2_order_nums'] = $pcdata['1002']['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $pcdata['1002']['status3_order_nums'] = $pcdata['1002']['status3_order_nums'] + 1;
                        }
                    }else{
                        $h5data['1002']['order_nums'] = $h5data['1002']['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $h5data['1002']['status0_order_nums'] = $h5data['1002']['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $h5data['1002']['status1_order_nums'] = $h5data['1002']['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $h5data['1002']['status2_order_nums'] = $h5data['1002']['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $h5data['1002']['status3_order_nums'] = $h5data['1002']['status3_order_nums'] + 1;
                        }
                    }
                }

            }

            foreach ($real_effect_orders as $effect_order){
                $url_info = parse_url(urldecode($effect_order['urlhistory']));
                parse_str($url_info['query'], $param_info);

                $where = array();
                $where['report_date'] = $schedule['work_date'];
                $where['channel'] = $this->channel;
                $where['campaign_name'] = $param_info['tbs_campaign'];
                $where['group_name'] = $param_info['tbs_group'];
                $where['keyword_name'] = $param_info['tbs_keyword'];

                $adgroup_id = $semreport->where($where)->getField('adgroup_id');
                if(!empty($adgroup_id)){
                    if(strpos($effect_order['urlhistory'],'m.tobosu.com')===false){
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $pcdata[$adgroup_id]['status9_order_nums'] = $pcdata[$adgroup_id]['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $pcdata[$adgroup_id]['status4_order_nums'] = $pcdata[$adgroup_id]['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $pcdata[$adgroup_id]['effect_order_nums'] = $pcdata[$adgroup_id]['effect_order_nums'] + 1;
                        }
                    }else{
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $h5data[$adgroup_id]['status9_order_nums'] = $h5data[$adgroup_id]['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $h5data[$adgroup_id]['status4_order_nums'] = $h5data[$adgroup_id]['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $h5data[$adgroup_id]['effect_order_nums'] = $h5data[$adgroup_id]['effect_order_nums'] + 1;
                        }
                    }
                }else{
                    if(strpos($effect_order['urlhistory'],'m.tobosu.com')===false){
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $pcdata['1002']['status9_order_nums'] = $pcdata['1002']['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $pcdata['1002']['status4_order_nums'] = $pcdata['1002']['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $pcdata['1002']['effect_order_nums'] = $pcdata['1002']['effect_order_nums'] + 1;
                        }
                    }else{
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $h5data['1002']['status9_order_nums'] = $h5data['1002']['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $h5data['1002']['status4_order_nums'] = $h5data['1002']['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $h5data['1002']['effect_order_nums'] = $h5data['1002']['effect_order_nums'] + 1;
                        }
                    }
                }

            }

            $deal_pcdata = array();
            foreach ($pcdata as $key=>$value){
                foreach ($value as $k=>$v){
                    $deal_pcdata[$k][$key] = $v;
                }
            }
            if(!empty($deal_pcdata)){
                $sql = $this->updateChannelReport($deal_pcdata,$this->channel,$schedule['work_date'],1);
                M()->execute($sql);
            }

            $deal_h5data = array();
            foreach ($h5data as $key=>$value){
                foreach ($value as $k=>$v){
                    $deal_h5data[$k][$key] = $v;
                }
            }
            if(!empty($deal_h5data)) {
                $sql = $this->updateChannelReport($deal_h5data, $this->channel, $schedule['work_date'], 2);
                M()->execute($sql);
            }

            $semschedule->where(array('channel'=>$this->channel,'work_date'=>$schedule['work_date']))->data(array('order_finish'=>1))->save();
        }else{
            $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
        }
    }

    public function updateChannelReport($data,$channel,$date,$device){
        $start_sql = "UPDATE ".C('DB_PREFIX')."semn_channel_report SET ";
        foreach ($data as $key=>$value){
            $mid_sql ='';
            $mid_sql .= "{$key} = CASE adgroup_id";
            foreach ($value as $k=>$v){
                $mid_sql .= " WHEN '{$k}' THEN {$v}";
            }
            $mid_sql .=" END";
            $mid_sql_arr[] = $mid_sql;
        }
        $end_sql = " WHERE channel='".$channel."' AND report_date='".$date."' AND device='".$device."'";

        return $start_sql.implode(',',$mid_sql_arr).$end_sql;
    }

    /************************************城市报告***************************************************/


    public function getCityReport($taskid){
        $semschedule = M('SemnSchedule');
        $semtask = M('SemnTask');
        $ordermodel = TBS_D('Order');
        $semreport = M('SemnReport');
        $semcityreport = M('SemnCityReport');
        $semdistrictreport = M('SemnDistrictReport');

        $schedule = $semschedule->where(array('channel'=>$this->channel,'city_finish'=>0,'report_finish'=>1))->find();
        if(!empty($schedule)){
            $citys=TBS_D('City')->getField('cityID,simpname');
            $citys_hot_flag=TBS_D('City')->getField('simpname,hot_flag');
            //--------------------------1.初始化条目-----------------------------------//
            //1.获取发布订单信息
            $real_fabu_orders = S("real_fabu_orders_city_".$this->channel.$schedule['work_date']);
            if(empty($real_fabu_orders)){
                $real_fabu_orders  = $ordermodel->where(array('urlhistory'=>array('like','%channel=sem&%subchannel=sougou%'),'addtime'=>array('between',array($schedule['work_date']." 00:00:00",$schedule['work_date']." 23:59:59"))))->getField('orderid,dealstatus,urlhistory,shi');
                foreach ($real_fabu_orders as &$real_fabu_order){
                    $real_fabu_order['shi_name'] = $citys[$real_fabu_order['shi']];
                }
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S("real_fabu_orders_city_".$this->channel.$schedule['work_date'],$real_fabu_orders,$remain_time);
            }
            //2.获取有效订单信息
            $real_effect_orders = S("real_effect_orders_city_".$this->channel.$schedule['work_date']);
            if(empty($real_effect_orders)){
                $real_effect_orders  = $ordermodel->where(array('urlhistory'=>array('like','%channel=sem&%subchannel=sougou%'),'effecttime'=>array('between',array($schedule['work_date']." 00:00:00",$schedule['work_date']." 23:59:59"))))->getField('orderid,dealstatus,urlhistory,shi,autoSplitComNum as fd');
                foreach ($real_effect_orders as &$real_effect_order){
                    $real_effect_order['shi_name'] = $citys[$real_effect_order['shi']];
                }
                $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
                S("real_effect_orders_city_".$this->channel.$schedule['work_date'],$real_effect_orders,$remain_time);
            }
            //3.组合初始数据
            $count = $semcityreport->where(array('report_date'=>$schedule['work_date'],'channel'=>$this->channel))->count();
            if($count==0){
                $data['report_date'] = $schedule['work_date'];
                $data['channel'] = $this->channel;

                if($schedule['work_date']>='2017-08-01'){
                    $result= $semreport->where(array('report_date'=>$schedule['work_date'],'channel'=>$this->channel))->field('distinct(city)')->select();
                }else{
                    $result= $semdistrictreport->where(array('report_date'=>$schedule['work_date'],'channel'=>$this->channel))->field('distinct(city)')->select();
                }
                $sem_city = array();
                foreach ($result as $v){
                    array_push($sem_city,$v['city']);
                }
                $fabu_order_city = array_column($real_fabu_orders,'shi_name');
                $effect_order_city = array_column($real_effect_orders,'shi_name');
                $others = array('others');
                if(!empty($fabu_order_city)&&!empty($effect_order_city)){
                    $all_city = array_unique(array_merge($fabu_order_city,$effect_order_city,$sem_city,$others));
                }elseif (empty($fabu_order_city)&&!empty($effect_order_city)){
                    $all_city = array_unique(array_merge($effect_order_city,$sem_city,$others));
                }elseif (!empty($fabu_order_city)&&empty($effect_order_city)){
                    $all_city = array_unique(array_merge($fabu_order_city,$sem_city,$others));
                }elseif (empty($fabu_order_city)&&empty($effect_order_city)){
                    $all_city = array_unique(array_merge($sem_city,$others));
                }

                $sem_device = array(1,2);

                $datas = array();
                foreach ($all_city as $city){
                    foreach ($sem_device as $device){
                        $data['report_date'] = $schedule['work_date'];
                        $data['device'] = $device;
                        $data['channel'] = $this->channel;
                        $data['city'] = $city;
                        $data['hot_flag'] = !empty($citys_hot_flag[$city])?$citys_hot_flag[$city]:'0';
                        if(!is_null($data['city'])&&!is_null($data['hot_flag'])){
                            array_push($datas,$data);
                        }
                    }
                }
                $semcityreport->addAll($datas);
            }
            //--------------------------2.填充数据-----------------------------------//
            //初始化
            $data = array();
            $data['views'] = 0;
            $data['clicks'] = 0;
            $data['cost'] = 0;
            $data['cash'] = 0;
            $data['order_nums'] = 0;
            $data['status0_order_nums'] = 0;
            $data['status1_order_nums'] = 0;
            $data['status2_order_nums'] = 0;
            $data['status3_order_nums'] = 0;
            $data['status4_order_nums'] = 0;
            $data['status9_order_nums'] = 0;
            $data['effect_order_nums'] = 0;
            $semcityreport->where(array('channel'=>$this->channel,'report_date'=>$schedule['work_date']))->data($data)->save();
            //填充
            $pcdata = array();
            $h5data = array();
            foreach ($real_fabu_orders as $fabu_order){

                $where = array();
                $where['report_date'] = $schedule['work_date'];
                $where['channel'] = $this->channel;
                $where['city'] = $fabu_order['shi_name'];
                $city_exist = $semcityreport->where($where)->count();
                if($city_exist>0){
                    if(strpos($fabu_order['urlhistory'],'m.tobosu.com')===false){
                        $pcdata[$fabu_order['shi_name']]['order_nums'] = $pcdata[$fabu_order['shi_name']]['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $pcdata[$fabu_order['shi_name']]['status0_order_nums'] = $pcdata[$fabu_order['shi_name']]['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $pcdata[$fabu_order['shi_name']]['status1_order_nums'] = $pcdata[$fabu_order['shi_name']]['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $pcdata[$fabu_order['shi_name']]['status2_order_nums'] = $pcdata[$fabu_order['shi_name']]['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $pcdata[$fabu_order['shi_name']]['status3_order_nums'] = $pcdata[$fabu_order['shi_name']]['status3_order_nums'] + 1;
                        }
                    }else{
                        $h5data[$fabu_order['shi_name']]['order_nums'] = $h5data[$fabu_order['shi_name']]['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $h5data[$fabu_order['shi_name']]['status0_order_nums'] = $h5data[$fabu_order['shi_name']]['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $h5data[$fabu_order['shi_name']]['status1_order_nums'] = $h5data[$fabu_order['shi_name']]['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $h5data[$fabu_order['shi_name']]['status2_order_nums'] = $h5data[$fabu_order['shi_name']]['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $h5data[$fabu_order['shi_name']]['status3_order_nums'] = $h5data[$fabu_order['shi_name']]['status3_order_nums'] + 1;
                        }
                    }
                }else{
                    if(strpos($fabu_order['urlhistory'],'m.tobosu.com')===false){
                        $pcdata['others']['order_nums'] = $pcdata['others']['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $pcdata['others']['status0_order_nums'] = $pcdata['others']['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $pcdata['others']['status1_order_nums'] = $pcdata['others']['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $pcdata['others']['status2_order_nums'] = $pcdata['others']['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $pcdata['others']['status3_order_nums'] = $pcdata['others']['status3_order_nums'] + 1;
                        }
                    }else{
                        $h5data['others']['order_nums'] = $h5data['others']['order_nums'] + 1;
                        if ($fabu_order['dealstatus'] == 0) {//未处理
                            $h5data['others']['status0_order_nums'] = $h5data['others']['status0_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 3) {//重单
                            $h5data['others']['status1_order_nums'] = $h5data['others']['status1_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 1) {//无效
                            $h5data['others']['status2_order_nums'] = $h5data['others']['status2_order_nums'] + 1;
                        }elseif ($fabu_order['dealstatus'] == 2) {//待定
                            $h5data['others']['status3_order_nums'] = $h5data['others']['status3_order_nums'] + 1;
                        }
                    }
                }

            }

            foreach ($real_effect_orders as $effect_order){

                $where = array();
                $where['report_date'] = $schedule['work_date'];
                $where['channel'] = $this->channel;
                $where['city'] = $effect_order['shi_name'];
                $city_exist = $semcityreport->where($where)->count();
                if($city_exist>0){
                    if(strpos($effect_order['urlhistory'],'m.tobosu.com')===false){
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $pcdata[$effect_order['shi_name']]['status9_order_nums'] = $pcdata[$effect_order['shi_name']]['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $pcdata[$effect_order['shi_name']]['status4_order_nums'] = $pcdata[$effect_order['shi_name']]['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $pcdata[$effect_order['shi_name']]['effect_order_nums'] = $pcdata[$effect_order['shi_name']]['effect_order_nums'] + 1;
                        }
                    }else{
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $h5data[$effect_order['shi_name']]['status9_order_nums'] = $h5data[$effect_order['shi_name']]['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $h5data[$effect_order['shi_name']]['status4_order_nums'] = $h5data[$effect_order['shi_name']]['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $h5data[$effect_order['shi_name']]['effect_order_nums'] = $h5data[$effect_order['shi_name']]['effect_order_nums'] + 1;
                        }
                    }
                }else{
                    if(strpos($effect_order['urlhistory'],'m.tobosu.com')===false){
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $pcdata['others']['status9_order_nums'] = $pcdata['others']['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $pcdata['others']['status4_order_nums'] = $pcdata['others']['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $pcdata['others']['effect_order_nums'] = $pcdata['others']['effect_order_nums'] + 1;
                        }
                    }else{
                        //不可分
                        if($effect_order['dealstatus']==9){
                            $h5data['others']['status9_order_nums'] = $h5data['others']['status9_order_nums'] + 1;
                        }
                        //有效未分单
                        if($effect_order['dealstatus']==6&&$effect_order['fd']==0){
                            $h5data['others']['status4_order_nums'] = $h5data['others']['status4_order_nums'] + 1;
                        }
                        //有效已分单
                        if(in_array($effect_order['dealstatus'],array(6,7,8,9,10,11,12))&&$effect_order['fd']>0){
                            $h5data['others']['effect_order_nums'] = $h5data['others']['effect_order_nums'] + 1;
                        }
                    }
                }

            }

            $deal_pcdata = array();
            foreach ($pcdata as $key=>$value){
                foreach ($value as $k=>$v){
                    $deal_pcdata[$k][$key] = $v;
                }
            }
            if(!empty($deal_pcdata)){
                $sql = $this->updateCityDevice($deal_pcdata,$this->channel,$schedule['work_date'],1);
                M()->execute($sql);
            }

            $deal_h5data = array();
            foreach ($h5data as $key=>$value){
                foreach ($value as $k=>$v){
                    $deal_h5data[$k][$key] = $v;
                }
            }
            if(!empty($deal_h5data)) {
                $sql = $this->updateCityDevice($deal_h5data, $this->channel, $schedule['work_date'], 2);
                M()->execute($sql);
            }
            //--------------------------3.填充sem数据-----------------------------------//
            //pc
            if($schedule['work_date']>='2017-08-01') {
                $pc_sem_data = $semreport->field('city,sum(views) as views,sum(clicks) as clicks,sum(cost) as cost,sum(cash) as cash')->where(array('channel' => $this->channel, 'report_date' => $schedule['work_date'], 'device' => 1))->group('city')->select();
            }else{
                $pc_sem_data = $semdistrictreport->field('city,sum(views) as views,sum(clicks) as clicks,sum(cost) as cost')->where(array('channel' => $this->channel, 'report_date' => $schedule['work_date'], 'device' => 1))->group('city')->select();
                if($schedule['work_date']>='2016-01-01'&&$schedule['work_date']<='2016-12-31'){
                    $this->return_point = 1.5;
                }
            }
            $deal_pc_sem = array();
            foreach ($pc_sem_data as $data){
                $deal_pc_sem[$data['city']]['views'] = $data['views'];
                $deal_pc_sem[$data['city']]['clicks'] = $data['clicks'];
                $deal_pc_sem[$data['city']]['cost'] = $data['cost'];
                if($data['cost']==0){
                    $deal_pc_sem[$data['city']]['cash'] = 0;
                }else{
                    if($schedule['work_date']<'2017-07-01') {
                        $this->return_point = 1.5;
                    }
                    $deal_pc_sem[$data['city']]['cash'] = !empty($data['cash'])?$data['cash']:$data['cost']/$this->return_point;
                }
            }
            $deal_pc_sem_data = array();
            foreach ($deal_pc_sem as $key=>$value){
                foreach ($value as $k=>$v){
                    $deal_pc_sem_data[$k][$key] = $v;
                }
            }
            if(!empty($deal_pc_sem_data)) {
                $sql = $this->updateCityDevice($deal_pc_sem_data, $this->channel, $schedule['work_date'], 1);
                M()->execute($sql);
            }
            //h5
            if($schedule['work_date']>='2017-08-01') {
                $h5_sem_data = $semreport->field('city,sum(views) as views,sum(clicks) as clicks,sum(cost) as cost,sum(cash) as cash')->where(array('channel' => $this->channel, 'report_date' => $schedule['work_date'], 'device' => 2))->group('city')->select();
            }else{
                $h5_sem_data = $semdistrictreport->field('city,sum(views) as views,sum(clicks) as clicks,sum(cost) as cost')->where(array('channel' => $this->channel, 'report_date' => $schedule['work_date'], 'device' => 2))->group('city')->select();
                if($schedule['work_date']>='2016-01-01'&&$schedule['work_date']<='2016-12-31'){
                    $this->return_point = 1.5;
                }
            }
            $deal_h5_sem = array();
            foreach ($h5_sem_data as $data){
                $deal_h5_sem[$data['city']]['views'] = $data['views'];
                $deal_h5_sem[$data['city']]['clicks'] = $data['clicks'];
                $deal_h5_sem[$data['city']]['cost'] = $data['cost'];
                if($data['cost']==0){
                    $deal_h5_sem[$data['city']]['cash'] = 0;
                }else{
                    if($schedule['work_date']<'2017-07-01') {
                        $this->return_point = 1.5;
                    }
                    $deal_h5_sem[$data['city']]['cash'] = !empty($data['cash'])?$data['cash']:$data['cost']/$this->return_point;
                }
            }
            $deal_h5_sem_data = array();
            foreach ($deal_h5_sem as $key=>$value){
                foreach ($value as $k=>$v){
                    $deal_h5_sem_data[$k][$key] = $v;
                }
            }
            if(!empty($deal_h5_sem_data)) {
                $sql = $this->updateCityDevice($deal_h5_sem_data, $this->channel, $schedule['work_date'], 2);
                M()->execute($sql);
            }

            $semschedule->where(array('channel'=>$this->channel,'work_date'=>$schedule['work_date']))->data(array('city_finish'=>1))->save();
        }else{
            $semtask->where(array('id'=>$taskid))->data(array('finish'=>1))->save();
        }
    }

    public function updateCityDevice($data,$channel,$date,$device){
        $start_sql = "UPDATE ".C('DB_PREFIX')."semn_city_report SET ";
        foreach ($data as $key=>$value){
            $mid_sql ='';
            $mid_sql .= "{$key} = CASE city";
            foreach ($value as $k=>$v){
                $mid_sql .= " WHEN '{$k}' THEN {$v}";
            }
            $mid_sql .=" END";
            $mid_sql_arr[] = $mid_sql;
        }
        $end_sql = " WHERE channel='".$channel."' AND report_date='".$date."' AND device='".$device."'";

        return $start_sql.implode(',',$mid_sql_arr).$end_sql;
    }

    //--------------接口请求--------------------------//

    /**
     * 获取搜狗推广所有的计划
     * @return array|bool
     */
    public function getAllCpcPlan()
    {
        vendor('Sogou.CpcPlanService');
        $service = new \CpcPlanService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getAllCpcPlanResponse' => array());
        $output_response = $service->soapCall('getAllCpcPlan', $arguments, $output_headers);
        $response = object_array($output_response);
        $response = $response['cpcPlanTypes'];
        if (!$response) return FALSE;
        return $response;
    }

    /**
     * 根据计划数组查询该计划下的所有单元
     * @param $campaignIds
     * @return array|bool|mixed
     */
    public function getCpcGrpByCpcGrpIds($planIds)
    {
        vendor('Sogou.CpcGrpService');
        $service = new \CpcGrpService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getCpcGrpByCpcPlanIdRequest' => array('cpcPlanIds' => $planIds));
        $output_response = $service->soapCall('getCpcGrpByCpcPlanId', $arguments, $output_headers);
        $response = object_array($output_response);
        if (!$response) return FALSE;
        $result = array();
        foreach ($response['cpcPlanGrps'] as $value){
            foreach ($value['cpcGrpTypes'] as $v){
                array_push($result,$v);
            }
        }
        return $result;
    }

    /** 根据指定的推广组 id 获取关键词
     * @param int $cpcGrpId
     * @return bool
     */
    public function getCpcByCpcGrpId($cpcGrpId)
    {
        vendor('Sogou.CpcService');
        $service = new \CpcService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getCpcByCpcGrpIdRequest' => array('cpcGrpIds' => $cpcGrpId));
        $output_response = $service->soapCall('getCpcByCpcGrpId', $arguments, $output_headers);
        $response = object_array($output_response);
        if (!$response) return FALSE;
        $result = array();
        foreach ($response['cpcGrpCpcs'] as $value){
            foreach ($value['cpcTypes'] as $v){
                array_push($result,$v);
            }
        }
        return $result;
    }

    public function getAccountInfo($startDate,$endDate){
        $task_id = S($this->account['id'].'_accountreportid_'.$startDate.$endDate);
        if(empty($task_id)){
            $task_id = $this->getRealAccountReport($startDate,$endDate);
            $remain_time = strtotime(date('Y-m-d')." 23:59:59") - time();
            S($this->account['id'].'_accountreportid_'.$startDate.$endDate,$task_id,$remain_time);
        }
        sleep(3);
        $task_status = $this->getTaskByTaskId($task_id);
        if($task_status=='1'){
            $file_path = "./dynamicRes/data/sem/".$this->account['id'];
            if (!file_exists($file_path)){
                mkdir($file_path);
            }
            $account_file_name = "./dynamicRes/data/sem/".$this->account['id']."/account_report.csv";

            if(!file_exists($account_file_name)) {
                $filepath = $this->downloadFileByFileId($task_id);
                $csv_content = gzdecode(file_get_contents($filepath));
                file_put_contents($account_file_name,$csv_content);
            }

            $account_csv_object = new \SplFileObject($account_file_name, 'rb');
            $account_csv_object->seek(1);

            $result = array();
            while (!$account_csv_object->eof()) {
                $content = $account_csv_object->fgetcsv();
                $date = mb_convert_encoding($content[1], "UTF-8", "GBK");
                $cost = mb_convert_encoding($content[3], "UTF-8", "GBK");
                $data = array();
                $data['date'] = $date;
                $data['cost'] = $cost;
                array_push($result,$data);
            }

            unlink($account_file_name);
            return $result;
        }else{
            return 0;
        }

    }

    public function getRealAccountReport($startDate,$endDate){
        vendor('Sogou.ReportService');
        $startTime = $startDate . 'T00:00:00.000';
        $endTime = $endDate . 'T23:59:59.000';
        $service = new \ReportService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getReportIdRequest' => array('reportRequestType' => array(
            'performanceData' => array('cost', 'click', 'impression'),
            'reportType' =>1,
            'statRange'=>1,
            'startDate' => $startTime,
            'endDate' => $endTime)));
        $output_response = $service->soapCall('getReportId', $arguments, $output_headers);
        $response = object_array($output_response);
        return $response['reportId'];
    }

    public function getTaskByTaskId($taskId)
    {
        vendor('Sogou.ReportService');
        $service = new \ReportService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getReportStateRequest' => array('reportId' => $taskId));
        $output_response = $service->soapCall('getReportState', $arguments, $output_headers);
        $response = object_array($output_response);
        return $response['isGenerated'];
    }

    public function downloadFileByFileId($fileId)
    {
        vendor('Sogou.ReportService');
        $service = new \ReportService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getReportPathRequest' => array('reportId' => $fileId));
        $output_response = $service->soapCall('getReportPath', $arguments, $output_headers);
        $res =  object_array($output_response);
        return $res['reportFilePath'];
    }

    /**
     * 获取关键字报表
     * @return mixed
     */
    public function getKeywordReportByCampaignIds($date,$is_pc=0)
    {
        vendor('Sogou.ReportService');
        $startTime = $date . 'T00:00:00.000';
        $endTime = $date . 'T23:59:59.000';
        $service = new \ReportService($this->loginInfo);
        $output_headers = array();
        $arguments = array('getReportIdRequest' => array('reportRequestType' => array(
            'performanceData' => array('cost', 'click', 'impression'),
            'reportType' =>5,
            'statRange'=>1,
            'statIds'=>null,
            'startDate' => $startTime,
            'endDate' => $endTime,
            'platform'=>$is_pc)));
        $output_response = $service->soapCall('getReportId', $arguments, $output_headers);
        $response = object_array($output_response);
        return $response['reportId'];
    }
}
