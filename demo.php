<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:11
 */

require_once ("./vendor/autoload.php");


//初始化项目
$lc = new \Lit\Comment\Init(1,"OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB");

$lc ->setRedisConfig( "192.168.31.246", 6379, "123@456@", 0 )
    ->setMySqlConfig( "192.168.31.246", 3306, "comment", "123456", "comment", "utf8mb4" )
    ->setMySqlTablePrefix("comment")
    ->setRedisKeyPrefix("lc");


// -------------------- 评论部分 --------------------
////增加一条评论
//echo "增加一条评论\n";
//$commentId = $lc->comment()->add(
//    12132, //被评物的ID
//    1122, //被评论物所属用户
//    1000, //发起评论的用户ID
//    "hello".rand(100000,999999), //回复内容
//    json_encode(["from"=>1])
//);
//var_dump ($commentId);
//
//echo "删除一条评论\n";
////删除一条评论
//var_dump ( $lc->comment()->del( 12132,"5e0080579e0390.52135070",1000 ) );
//var_dump ($lc->comment()->getLastError());
//
//echo "获取一条评论\n";
////获取一条评论的信息
//var_dump ( $lc->comment()->getComment(121322, $commentId ) );
//var_dump ($lc->comment()->getLastError());
//
//// -------------------- 回复 --------------------
//
//echo "增加一个回复\n";
////增加一个回复
//$replyId = $lc->reply()->add(
//    12132,
//    11211,
//    $commentId,
//    "999",
//    "8788",
//    "我就回你了",
//    json_encode(["aa"=>11])
//);
//var_dump($replyId);
//
//echo "获取回复\n";
//var_dump ( $lc->reply()->getReply("12132", $replyId) );
//var_dump ($lc->reply()->getLastError());
//
//echo "删除回复\n";
//$lc->reply()->del( 12132,"5e0087b0c47c33.36815052", 999);
//var_dump ( $lc->reply()->getLastError() );


// -------------------- 列表 --------------------

$lc->list()->getNewestList( 12132 );