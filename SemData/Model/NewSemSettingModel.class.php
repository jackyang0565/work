<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/14
 * Time: 17:05
 */

namespace SemData\Model;


use Think\Model;

class NewSemSettingModel extends Model
{
    protected $tableName = 'sem_setting';


    public function get($key){
        $result = $this->where(array('key'=>$key))->order('id desc')->find();
        return $result['value'];
    }

    public function set($key,$value){
        $count = $this->where(array('key'=>$key))->count();
        if($count==0){
            $result = $this->data(array('key'=>$key,'value'=>$value))->add();
        }else{
            $result = $this->where(array('key'=>$key))->data(array('value'=>$value))->save();
        }
        return $result;
    }

}