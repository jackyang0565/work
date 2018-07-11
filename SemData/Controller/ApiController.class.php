<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/14
 * Time: 20:04
 */

namespace SemData\Controller;
use Think\Controller;

class ApiController extends Controller
{
    public $key = 'stbs2017';

    public function __construct()
    {
        $apikey = !empty($_POST['apikey'])?$_POST['apikey']:'';
        if( (!in_array($apikey,array('NTf3c1Av7yR8BsHQg9eU','hR0S6Ir7oQaOPjc4NJ'))) && (ACTION_NAME!='loginauth') ){
            parse_to_json(200,'fail','apikey不正确');
        }
        if(ACTION_NAME!='loginauth'){
            $apisecret = !empty($_POST['apisecret'])?$_POST['apisecret']:'';
            if(!empty($apisecret)){
                $encode = new \Com\Tbs\Encoder();
                $encode->key = $this->key;
                $apisecret = $encode->decrypt(str_replace('*','+',$apisecret));
                if(msubstr($apisecret,0,20)!=$apikey){
                    parse_to_json(200,'fail','apisecret不正确');
                }
            }else{
                parse_to_json(200,'fail','apisecret不正确');
            }
        }
    }

    public function loginauth() {
        $datacenter = D('Datacenter');

        $username = !empty($_POST['username'])?$_POST['username']:'';

        $password = !empty($_POST['password'])?$_POST['password']:'';
        if(!empty($username)&&!empty($password)){
            $result = $datacenter->finduser($username."@tobosu.cn",md5_16bit($password));
            if($result){
                if($username=='zenghongliang'){
                    $encode = new \Com\Tbs\Encoder();
                    $encode->key = $this->key;
                    $data['apikey'] = 'NTf3c1Av7yR8BsHQg9eU';
                    $data['apisecret'] = str_replace('+','*',$encode->encrypt($data['apikey'].date('Y-m-d')));
                    parse_to_json(200,'success',$data);
                }elseif ($username=='yangjie'){
                    $encode = new \Com\Tbs\Encoder();
                    $encode->key = $this->key;
                    $data['apikey'] = 'hR0S6Ir7oQaOPjc4NJYJ';
                    $data['apisecret'] = $encode->encrypt($data['apikey']);
                    $data['apisecret'] = str_replace('+','*',$encode->encrypt($data['apikey'].date('Y-m-d')));
                    parse_to_json(200,'success',$data);
                }else{
                    $encode = new \Com\Tbs\Encoder();
                    $encode->key = $this->key;
                    $data['apikey'] = 'WeYNj2yx7si09qgIM14F';
                    $data['apisecret'] = str_replace('+','*',$encode->encrypt($data['apikey'].date('Y-m-d')));
                    parse_to_json(200,'success',$data);
                }
            }else{
                parse_to_json(200,'fail','该用户不存在');
            }
        }else{
            parse_to_json(200,'fail','登录出错');
        }

    }

    public function getaccount(){
        $semnaccount = M('SemnAccount');

        $result = $semnaccount->field('id,channel,username,token')->where(array('channel'=>'baidu','status'=>1))->select();

        if(!empty($result)){
            parse_to_json(200,'success',$result);
        }else{
            parse_to_json(200,'fail','没有账户');
        }
    }

    public function getpassword(){
        $semnaccount = M('SemnAccount');

        $username = !empty($_POST['username'])?$_POST['username']:'';
        $token = !empty($_POST['token'])?$_POST['token']:'';

        $result = $semnaccount->where(array('username'=>$username,'token'=>$token))->getField('password');

        if(!empty($result)){
            parse_to_json(200,'success',$result);
        }else{
            parse_to_json(200,'fail','没有账户');
        }
    }

}
