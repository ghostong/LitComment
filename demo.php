<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:11
 */

require_once ("./vendor/autoload.php");

$lc = new \Lit\Comment\Init();

$lc ->setRedisConfig( "127.0.0.1", 6379, "123@456@", 0 )
    ->setMySqlConfig( "127.0.0.1", 3306, "comment", "123456", "comment", "utf8mb4" )
    ->setMySqlTablePrefix("comment")
    ->setRedisKeyPrefix("lc");

$lc->comment()->add(
    0, //来源
    12132, //被评论的ID
    1000, //发起评论的用户ID
    0, //回复谁的信息
    json_encode([])
);
