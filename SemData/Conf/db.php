<?php
$online= array(
    'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '10.10.1.200', // 服务器地址
    'DB_NAME'               =>  'tbs_erp',          // 数据库名
    'DB_USER'               =>  'user_writer',      // 用户名
    'DB_PWD'                =>  'tbs_db_user_writer_9812',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  't_',    // 数据库表前缀
    'DB_CONFIG_OLD' => array(
        'DB_DEPLOY_TYPE'=> 1, // 设置分布式数据库支持
        'DB_RW_SEPARATE'=>1, //读写分离
        'db_type'  => 'mysql',
        'db_user'  => 'user_writer',
        'db_pwd'   => 'tbs_db_user_writer_9812',
        'db_host'  => '10.10.1.200',
        'db_port'  => '3306',
        'db_name'  => 'tbslast'),
    'DB_CONFIG_NEW' => array(
        'DB_TYPE'  => 'mysql',
        'DB_USER'  => 'user_writer',
        'DB_PWD'   => 'tbs_db_user_writer_9812',
        'DB_HOST'  => '10.10.1.200',
        'DB_PORT'  => '3306',
        'DB_NAME'  => 'tbs_datacenter'),
);

$offline= array(
		'DB_TYPE'               =>  'mysql',     // 数据库类型
		'DB_HOST'               =>  'localhost', // 服务器地址
		'DB_NAME'               =>  'tbs_erp',          // 数据库名
		'DB_USER'               =>  'root',      // 用户名
		'DB_PWD'                =>  'root',          // 密码
		'DB_PORT'               =>  '3306',        // 端口
		'DB_PREFIX'             =>  't_',    // 数据库表前缀
		'DB_CONFIG_OLD' => array(
				'DB_DEPLOY_TYPE'=> 1, // 设置分布式数据库支持
				'DB_RW_SEPARATE'=>1, //读写分离
				'DB_TYPE'  => 'mysql',
				'DB_USER'  => 'root',
				'DB_PWD'   => 'root',
				'DB_HOST'  => '127.0.0.1',
				'DB_PORT'  => '3306',
				'DB_NAME'  => 'tbslast'),
        'DB_CONFIG_NEW' => array(
            'DB_TYPE'  => 'mysql',
            'DB_USER'  => 'root',
            'DB_PWD'   => 'root',
            'DB_HOST'  => '127.0.0.1',
            'DB_PORT'  => '3306',
            'DB_NAME'  => 'tbs_datacenter'),
);


if(dev_mode())
{
	return $offline;
}
else 
{
	return $online;
}


