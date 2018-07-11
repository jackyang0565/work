<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/27
 * Time: 16:14
 */

namespace SemData\Model;
use Common\Model\TbsModel;

class DatacenterModel extends TbsModel
{
    public function __construct()
    {
        $this->db(2,"DB_CONFIG_NEW");
    }

    public function finduser($email_account,$password){
        $res = $this->table(C('DB_PREFIX').'erp_employee')->where(array('email_account'=>$email_account,'password'=>$password))->count();
        if($res){
            return 1;
        }else{
            return 0;
        }
    }
}
