<?php
return [
    //定义各个任务的redis key
    'routingRedisKey' => 'routingRedisKey',
    'remapRedisKey' => 'remapRedisKey',
    'drsRedisKey' => 'drsRedisKey',
    'pingTaskRedisKey' => 'pingTaskRedisKey',

    //zabbix状态，流量修改的信息
    'zabbixApiRedisKey' => 'zabbixApiRedisKey',
    //ping api site/ping
    'pingApiRedisKey' => 'pingApiRedisKey',

    'dnsServerRedisKey' => 'dnsServerRedisKey',




    'user.passwordResetTokenExpire' => 3600,
];
