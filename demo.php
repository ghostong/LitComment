<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:11
 */

require_once ("./vendor/autoload.php");

class demo{

    private $comment;
    private $commentedId = 123;
    private $commentedUser = 999;
    private $userId = 1234;
    private $targetUser =  888;

    function __construct(){

        $this->commentedId = rand(100,999);

        //实例化化项目
        $lComment = new \Lit\Comment\Init();

        //增加权限
        $lComment->ram()->add( 1,"OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB", "测试", 1);
        $lComment->ram()->add( 2,"11111111232312321321312131231231", "测试2", 1);

        //配置项目
        $lComment->config()->setRedisConfig("192.168.31.246", 6379, "123@456@", 0);
        $lComment->config()->setMySqlConfig("192.168.31.246", 3306, "comment", "123456", "comment", "utf8mb4");
        $lComment->config()->setXunSearchConfig("./lcomment.ini");
        $lComment->config()->setMySqlTablePrefix("comment_");
        $lComment->config()->setRedisKeyPrefix("lc");

        //启动项目
        $lComment->start( 1, "OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB" );

        $this->comment = $lComment;
    }

    public function createTableSql(){
        $this->comment->createTableSql();
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
    }

    //获取评论时产生的错误
    public function getCommentError(){
        echo __FUNCTION__ . ":获取评论时产生的错误\n";
        $error = $this->comment->comment()->getLastError();
        var_dump($error);
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
    }

    //获取回复时产生的错误
    public function getReplyError(){
        echo __FUNCTION__ . ":获取评论时产生的错误\n";
        $error = $this->comment->reply()->getLastError();
        var_dump($error);
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
    }

    //初始化回复列表
    public function setReplyList( $commentId ){
        echo __FUNCTION__ . ":初始化回复列表\n";
        $list = $this->comment->list()->setReplyList(
            $this->commentedId, //被评物的ID
            $commentId //评论ID
        );
        var_dump ($list);
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
    }

    //删除所有评论
    public function delAllComment () {
        echo __FUNCTION__ . ":删除所有评论\n";
        $result = $this->comment->comment()->delAll(
            $this->commentedId
        );
        var_dump ($result);
    }

    //删除所有回复
    public function delAllReply( $commentId ){
        echo __FUNCTION__ . ":删除所有回复\n";
        $result = $this->comment->reply()->delAll(
            $this->commentedId,
            $commentId
        );
        var_dump($result);
    }

    //获取被评论物评论数量
    public function getCommentNum () {
        $result = $this->comment->comment()->getCommentNum(
            $this->commentedId
        );
        var_dump($result);
    }

    //获取评论回复数量
    public function getReplyNum ( $commentId ) {
        $result = $this->comment->reply()->getReplyNum(
            $commentId
        );
        var_dump($result);
    }

    public function setTop( $commentId ){
        $result = $this->comment->opt()->setCommentTop(
            $this->commentedId,
            $commentId,
            10
        );
        var_dump ($result);
    }
    public function getTop(){
        $result = $this->comment->opt()->getTopComment(
            $this->commentedId
        );
        var_dump($result);
    }

    public function delTop ( $commentId ){
        $result = $this->comment->opt()->delTopComment(
            $this->commentedId,
            $commentId
        );
    }

    public function commentPass ( $commentId ) {
        $result = $this->comment->opt()->commentPass(
            $this->commentedId,
            $commentId
        );
        var_dump ($result);
    }

    public function commentReject ( $commentId ) {
        $result = $this->comment->opt()->commentReject(
            $this->commentedId,
            $commentId
        );
        var_dump ($result);
    }

    public function replyPass ( $commentId, $replyId ) {
        $result = $this->comment->opt()->replyPass(
            $this->commentedId,
            $commentId,
            $replyId
        );
        var_dump ($result);
    }

    public function replyReject ( $commentId, $replyId ) {
        $result = $this->comment->opt()->replyReject(
            $this->commentedId,
            $commentId,
            $replyId
        );
        var_dump ($result);
    }

    public function test(){
        $ret = $this->comment->opt()->optList(
            [
                "comment_id"=>"5e05d5b849fd1893"
            ]
        );
        var_dump ($ret);
    }
}


$demo = new demo();

$demo->test();

//$demo->createTableSql();

// -------------------- 评论部分 --------------------
//
////增加一条评论
//$commentId = $demo->addComment();

//
////获取一条评论的信息
//$demo->getComment( $commentId );
//
////删除一条评论
//$demo->delComment( $commentId );
//
////删除所有评论
//$demo->delAllComment();
//
////设置评论点赞数量
//$demo->setCommentLike( "5e010d27f205f8.94025250" , 100);
//
////获取评论时产生的错误
//$demo->getCommentError();
//
//// -------------------- 回复 --------------------
//
////增加一个回复
//$replyId = $demo->addReply("5e04aa4adee2c7.89814180");
//
////获取回复
//$demo->getReply( "5e01069ed109b3.32294299", $replyId);
//
////删除回复
//$demo->delReply( "5e01069ed109b3.32294299", "5e010a14aec762.18053372" );
//
////删除所有回复
//$demo->delAllReply("5e01069ed109b3.32294299");
//
////获取回复时产生的错误
//$demo->getReplyError();
//
//
//// -------------------- 列表 --------------------
//
////初始化评论列表
//$demo->setCommentList();
//
////获取评论时间序列表
//$demo->newCommentList();
//
////获取评论点赞数序列表
//$demo->likeCommentList();
//
////获取回复数序列表
//$demo->replyCommentList();
//
////初始化回复列表
//$demo->setReplyList( "5e01069ed109b3.32294299");
//
////获取回复时间序列表
//$demo->newReplyList( "5e01069ed109b3.32294299" );
//
////获取被评论物评论数量
//$demo->getCommentNum();
//
////获取评论回复数量
//$demo->getReplyNum("5e0380dc039049.39147680");


// -------------------- 运营 --------------------
////置顶评论
//$demo->setTop("5e04b232130024.15458178");

////获取置顶评论
//$demo->getTop("5e04b232130024.15458178");

//删除置顶
//$demo->delTop("5e04b232130024.15458178");

//评论通过
//$demo->commentPass("5e04b232130024.15458178");

//评论拒绝
//$demo->commentReject( "5e04b232130024.15458178" );

//回复通过
//$demo->replyPass("5e04ab28989367.11773034","5e04ab289f2848.94288041");

//回复拒绝
//$demo->replyReject("5e04ab28989367.11773034","5e04ab289f2848.94288041");














/**


function buildIndex($xs) {
$xs->index->stopRebuild();
$xs->index->clean();
$xs->index->beginRebuild();
$sql = "select * from comment_merge ";
$pdo = new \Lit\Drivers\LiMySQL( "192.168.31.246", $port='3306', "comment", "123456", "comment", $charSet='utf8' );
$res = $pdo->query($sql);
foreach ($res as $val) {
$doc = new XSDocument;
$doc->setFields($val);
$xs->index->add($doc);
}
$result = $xs->index->endRebuild();
var_dump ($result);
}

function addData ( $xs ){
$data = array(
"comment_id" => uniqid(),
"origin_id" =>  uniqid(),
"commented_id" => uniqid(),
"parent_id" => uniqid (),
"user_id" => uniqid(),
"commented_user"=> uniqid(),
"content"=> uniqid(),
"status" => rand (-1,1),
"createtime"=>time(),
);

$doc = new XSDocument;
$doc->setFields($data);
var_dump ( $xs->index->add($doc) );


}

function search ( $xs ) {
try {

$search = $xs->search; // 获取 搜索对象
//        $search->addWeight('origin_id',"2");
$search->setSort ("createtime",false);
$search->setQuery('origin_id:1');
$search->addRange('createtime',1577440696,1577440704);
//        $search->setQuery('comment_id:14');
$search->setLimit(10);
$docs = $search->search(); // 执行搜索，将搜索结果文档保存在 $docs 数组

var_dump ( $search->count() );

var_dump ($docs);

}catch (XSException $e){

echo "\n" . $e->getTraceAsString() . "\n";

}

}

 */