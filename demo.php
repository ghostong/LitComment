<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:11
 */

require_once ("./vendor/autoload.php");

$redisConfig["host"] = "127.0.0.1";
$redisConfig["port"] = "3306";
$redisConfig["auth"] = "123@456@";
$redisConfig["db"] = "0";

$mySqlConfig["host"] = "127.0.0.1";
$mySqlConfig["port"] = "3306";
$mySqlConfig["username"] = "comment";
$mySqlConfig["password"] = "123456";
$mySqlConfig["dbname"] = "comment";
$mySqlConfig["charset"] = "utf8mb4";

$lc = new \Lit\Comment\Main( $redisConfig, $mySqlConfig,"li_comment_","li_comment_" );

$lc->comment()->add(
    0, //来源
    12132, //被评论的ID
    1000, //发起评论的用户ID
    0, //回复谁的信息
    json_encode([])
);
