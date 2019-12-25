<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:11
 */

require_once ("./vendor/autoload.php");

class demo{

    private $comment;
    private $commentedId = 1123;
    private $commentedUser = 999;
    private $userId = 1234;
    private $targetUser =  888;

    function __construct(){
        //初始化项目
        $lComment = new \Lit\Comment\Init(1, "OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB");
        $lComment->setRedisConfig("192.168.31.246", 6379, "123@456@", 0);
        $lComment->setMySqlConfig("192.168.31.246", 3306, "comment", "123456", "comment", "utf8mb4");
        $lComment->setMySqlTablePrefix("comment");
        $lComment->setRedisKeyPrefix("lc");
        $this->comment = $lComment;
    }

    //增加一条评论
    public function addComment(){
        echo __FUNCTION__ . ":增加一条评论\n";
        $commentId = $this->comment->comment()->add(
            $this->commentedId, //被评物的ID
            $this->commentedUser, //被评论物所属用户
            $this->userId, //发起评论的用户ID
            "这是一条评论" . rand(100000, 999999), //回复内容
            json_encode(["userName" => "笑哈哈", "url" => "https://baidu.com"])
        );
        var_dump($commentId);
        return $commentId;
    }

    //获取一条评论的信息
    public function getComment( $commentId ){
        echo __FUNCTION__ . ":获取一条评论\n";
        $commentInfo = $this->comment->comment()->getCommentInfo(
            $this->commentedId, //被评物的ID
            $commentId //评论ID
        );
        var_dump ($commentInfo);
        return $commentInfo;
    }

    //删除一条评论
    public function delComment( $commentId ){
        echo __FUNCTION__ . ":删除一条评论\n";
        $result = $this->comment->comment()->del(
            $this->commentedId, //被评物的ID
            $commentId, //评论ID
            $this->userId //评论所有人或者评论发起人
        );
        var_dump ($result);
        return $result;
    }

    //获取评论时产生的错误
    public function getCommentError(){
        echo __FUNCTION__ . ":获取评论时产生的错误\n";
        $error = $this->comment->comment()->getLastError();
        var_dump($error);
        return $error;
    }


    //增加一个回复
    public function addReply( $commentId ){
        echo __FUNCTION__ . ":增加一个回复\n";
        $replyId = $this->comment->reply()->add(
            $this->commentedId, //被评物的ID
            $this->commentedUser, //被评论物所属用户
            $commentId,
            $this->userId,
            $this->targetUser,
            "我就回你了",
            json_encode(["aa"=>11])
        );
        var_dump ($replyId);
        return $replyId;
    }

    //获取回复
    public function getReply( $commentId, $replyId ){
        echo __FUNCTION__ . ":获取回复\n";
        $replyInfo = $this->comment->reply()->getReplyInfo(
            $this->commentedId, //被评物的ID
            $commentId, //评论ID
            $replyId //回复ID
        );
        var_dump ($replyInfo);
        return $replyInfo;
    }

    //删除回复
    public function delReply( $commentId, $replyId ){
        echo __FUNCTION__ . ":获取回复\n";
        $result = $this->comment->reply()->del(
            $this->commentedId, //被评物的ID
            $commentId, //评论ID
            $replyId, //回复ID
            $this->commentedUser //回复所有人或者评论发起人
        );
        var_dump($result);
        return $result;
    }

    //获取回复时产生的错误
    public function getReplyError(){
        echo __FUNCTION__ . ":获取评论时产生的错误\n";
        $error = $this->comment->reply()->getLastError();
        var_dump($error);
        return $error;
    }

    //初始化评论列表
    public function setCommentList(){
        echo __FUNCTION__ . ":初始化评论列表\n";
        $this->comment->list()->setCommentList(
            $this->commentedId //被评物的ID
        );
    }

    //获取时间序列表
    public function newCommentList(){
        echo __FUNCTION__ . ":获取时间序列表\n";
        $list = $this->comment->list()->timeLineCommentList(
            $this->commentedId, //被评物的ID
            0, //索引开始
            4, //分页大小
            "desc"  //排序 asc|desc
        );
        var_dump ($list);
        return $list;
    }

    //获取点赞数序列表
    public function likeCommentList(){
        echo __FUNCTION__ . ":获取点赞数序列表\n";
        $list = $this->comment->list()->likeLineCommentList(
            $this->commentedId, //被评物的ID
            0, //索引开始
            4, //分页大小
            "desc"  //排序 asc|desc
        );
        var_dump ($list);
        return $list;
    }

    //获取回复数序列表
    public function replyCommentList(){
        echo __FUNCTION__ . ":获取回复数序列表\n";
        $list = $this->comment->list()->replyLineCommentList(
            $this->commentedId, //被评物的ID
            0, //索引开始
            4, //分页大小
            "desc"  //排序 asc|desc
        );
        var_dump ($list);
        return $list;
    }

    //初始化回复列表
    public function setReplyList( $commentId ){
        echo __FUNCTION__ . ":初始化回复列表\n";
        $list = $this->comment->list()->setReplyList(
            $this->commentedId, //被评物的ID
            $commentId //评论ID
        );
        var_dump ($list);
        return $list;
    }

    //获取回复时间序列表
    public function newReplyList ( $commentId ) {
        echo __FUNCTION__ . ":获取回复时间序列表\n";
        $list = $this->comment->list()->timeLineReplyList(
            $this->commentedId, //被评物的ID
            $commentId, //评论ID
            0,
            20,
            "asc"
        );
        var_dump ($list);
        return $list;
    }

    //设置评论点赞数量
    public function setCommentLike( $commentId , $num ){
        echo __FUNCTION__ . ":设置评论点赞数量\n";
        $result = $this->comment->comment()->setLikeNum (
            $this->commentedId,
            $commentId,
            $num
        );
        var_dump ($result);
        return $result;
    }

    //删除所有评论
    public function delAllComment () {
        echo __FUNCTION__ . ":删除所有评论\n";
        $result = $this->comment->comment()->delAll(
            $this->commentedId
        );
        var_dump ($result);
        return $result;
    }

    //删除所有回复
    public function delAllReply( $commentId ){
        echo __FUNCTION__ . ":删除所有回复\n";
        $result = $this->comment->reply()->delAll(
            $this->commentedId,
            $commentId
        );
        var_dump($result);
        return $result;
    }

    //获取被评论物评论数量
    public function getCommentNum () {
        $result = $this->comment->list()->getCommentNum(
            $this->commentedId
        );
        var_dump($result);
        return $result;
    }

    //获取评论回复数量
    public function getReplyNum ( $commentId ) {
        $result = $this->comment->list()->getReplyNum(
            $commentId
        );
        var_dump($result);
        return $result;
    }
}


$demo = new demo();

// -------------------- 评论部分 --------------------

//增加一条评论
//$commentId = $demo->addComment();

//获取一条评论的信息
//$demo->getComment( $commentId );

//删除一条评论
//$demo->delComment( "5e01069ed109b3.32294299" );

//删除所有评论
//$demo->delAllComment();

//设置评论点赞数量
//$demo->setCommentLike( "5e010d27f205f8.94025250" , 100);

//设置评论回复数量
//$demo->setReplyLike( "5e010d27f205f8.94025250" , 100);

//获取评论时产生的错误
//$demo->getCommentError();

// -------------------- 回复 --------------------

//增加一个回复
//$replyId = $demo->addReply("5e02501b58b086.35378135");

//获取回复
//$demo->getReply( "5e01069ed109b3.32294299", $replyId);

//删除回复
//$demo->delReply( "5e01069ed109b3.32294299", "5e010a14aec762.18053372" );

//删除所有回复
//$demo->delAllReply("5e01069ed109b3.32294299");

//$demo->getReplyError();


// -------------------- 列表 --------------------

//初始化评论列表
//$demo->setCommentList();

//设置评论被点赞数量
//$demo->setCommentLike("5e025835735f43.71367799",rand(1000,9999));

//获取评论时间序列表
//$demo->newCommentList();

//获取评论点赞数序列表
//$demo->likeCommentList();

//获取回复数序列表
//$demo->replyCommentList();

//初始化回复列表
//$demo->setReplyList( "5e01069ed109b3.32294299");

//获取回复时间序列表
//$demo->newReplyList( "5e01069ed109b3.32294299" );

//获取被评论物评论数量
//$demo->getCommentNum();

//获取评论回复数量
//$demo->getReplyNum("5e025d009770a7.32368736");

