<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
//    'bootstrap' => ['queue'],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            //'password'=>'cqhd888'
        ],
//        'queue' => [
//            'class' =>  '\yii\queue\redis\Queue',
//            'as log' => '\yii\queue\LogBehavior',//错误日志 默认为 console/runtime/logs/app.log
//            'redis' => 'redis', // 连接组件或它的配置
//            'channel' => 'queue', // Queue channel key
//        ],
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=comlesu',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
            'tablePrefix'=> 'lc_'
        ],
        'dbIpku_bak' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=218.32.211.25;dbname=comlesu',
            'username' => 'comlesu',
            'password' => 'whoareyou@b',
            'charset' => 'utf8',
            'tablePrefix'=> 'lc_'
        ],
        'dbFlow_bak' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=218.32.211.13;dbname=comlesu',
            'username' => 'comlesu',
            'password' => 'whoareyou@b',
            'charset' => 'utf8',
            'tablePrefix'=> 'lc_'
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            //'useFileTransport' => false,
            'useFileTransport' =>false,//这句一定有，false发送邮件，true只是生成邮件在runtime文件夹下，不发邮件</span>
            'transport' => [
                'class' => 'Swift_SmtpTransport', //使用的类
                'host' => 'smtp.exmail.qq.com', //邮箱服务一地址
                'username' => 'lin@21idc.com.cn',//邮箱地址，发送的邮箱
                'password' => 'Cqhd@2018',  //自己填写邮箱密码
                'port' => '465',  //服务器端口
                'encryption' => 'ssl', //加密方式
            ],
            'messageConfig'=>[
                'charset'=>'UTF-8', //编码
                'from'=>['lin@21idc.com.cn'=>'lesu报警邮件']  //邮件里面显示的邮件地址和名称
            ],
        ]

    ],
];
