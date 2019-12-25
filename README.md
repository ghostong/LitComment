litComment 
==============
基于 Redis, MySQL 的评论系统.


### 初始化项目

````php
<?php

require_once ("./vendor/autoload.php");

//参数1 $originId  数据来源,由微服务统一配发
//参数2 $token     访问密钥,由微服务统一配发
$lComment = new \Lit\Comment\Init(1, "OZR3Ypm...........dnq2SEKZKhB");

//参数1 $host redis主机名
//参数2 $port redis端口
//参数3 $auth redis密码
//参数4 $db   redis db 号
$lComment->setRedisConfig("192.168.31.246", 6379, "123@456@", 0);

//参数1 $host mysql主机名
//参数2 $port mysql端口
//参数3 $userName mysql用户名
//参数4 $passWord mysql密码
//参数4 $passWord mysql db名
//参数4 $passWord mysql 字符集
$lComment->setMySqlConfig("192.168.31.246", 3306, "comment", "123456", "comment", "utf8mb4");

//参数1 设置mysql数据表前缀,用户分表, 例如  lc_comment_
$lComment->setMySqlTablePrefix("comment");

//参数1 设置redis key 前缀, 例如  lc
$lComment->setRedisKeyPrefix("lc");

````

### 评论部分
````php
<?php
//增加一条评论, 返回值为写入的评论ID
$lComment->comment()->add(
    $this->commentedId, //被评物的ID
    $this->commentedUser, //被评论物所属用户
    $this->userId, //发起评论的用户ID
    "这是一条评论" , //回复内容
    json_encode(["userName" => "笑哈哈", "url" => "https://baidu.com"]) //扩展信息,必须为json字符串
);

//其他评论相关 参考 demo.php

````


### 回复部分
````php
<?php
//增加一条回复, 返回值为写入的评论ID
$replyId = $lComment->reply()->add(
    $this->commentedId, //被评物的ID
    $this->commentedUser, //被评论物所属用户
    $commentId,  //评论ID
    $this->userId,  //回复者ID
    $this->targetUser, //是否@某人,针对某人
    "我就回你了",  //回复内容
    json_encode(["aa"=>11]) //扩展信息,必须为json字符串
);

//其他回复相关 参考 demo.php

````
