<?php
/**
 * Created by IntelliJ IDEA.
 * User: ghost
 * Date: 2019-12-23
 * Time: 17:28
 */

namespace Lit\Comment;


class LiList extends LiBase {
    private $redisClient;
    private $mySqlClient;
    private $tablePrefix;
    private $redisKeyPrefix;
    private $comment;
    private $reply;

    private $originId;
    private $listLastError;

    function  __construct( $originId, $comment, $reply, $redisClient, $mySqlClient,  $redisKeyPrefix, $tablePrefix ){
        parent::__construct($originId);
        $this->originId = $originId;
        $this->comment = $comment;
        $this->reply = $reply;
        $this->redisClient = $redisClient;
        $this->mySqlClient = $mySqlClient;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->tablePrefix = $tablePrefix;
    }

    public function getLastError(){
        return $this->listLastError;
    }

    private function setLastError( $lastError ){
        $this->listLastError = $lastError;
    }

    //--------------- 评论部分 ---------------//

    private function comment(){
        return $this->comment;
    }

    public function timeLineCommentKey( $commentedId ){
        $key = $this->redisKeyPrefix.":comment:list:timeLine:".$commentedId;
        return $key;
    }

    public function likeLineCommentKey ( $commentedId ){
        $key = $this->redisKeyPrefix.":comment:list:likeLine:".$commentedId;
        return $key;
    }

    public function replyLineCommentKey ( $commentedId ){
        $key = $this->redisKeyPrefix.":comment:list:replyLine:".$commentedId;
        return $key;
    }

    /**
     * 获取时间序评论列表
     * @param string $commentedId 被评论物ID
     * @param int $index 翻页索引
     * @param int $pageSize 操作的用户ID
     * @param string $order 排序 asc|desc
     * @return array
     */
    public function timeLineCommentList( $commentedId, $index = 0, $pageSize = 5, $order = "desc" ){
        $key = $this->timeLineCommentKey($commentedId);
        if ($order == "desc") {
            $start = $index ? $index - 1 : "+inf";
            $end = "-inf";
        }else{
            $start = $index ? $index + 1 : "-inf";
            $end = "+inf";
        }
        $data = $this->getCommentList( $key, $order, $start, $end, $pageSize);
        if ($data) {
            return  $this->comment()->getCommentInfos($commentedId,array_keys($data));
        }else{
            return [];
        }
    }

    /**
     * 获取点赞序评论列表
     * @param string $commentedId 被评论物ID
     * @param int $index 翻页索引
     * @param int $pageSize 操作的用户ID
     * @param string $order 排序 asc|desc
     * @return array
     */
    public function likeLineCommentList( $commentedId, $index = 0, $pageSize = 5, $order = "desc" ){
        $key = $this->likeLineCommentKey($commentedId);
        if ($order == "desc") {
            $start = $index ? $index : "+inf";
            $end = "-inf";
        }else{
            $start = $index ? $index : "-inf";
            $end = "+inf";
        }
        $data = $this->getCommentList( $key, $order, $start, $end, $pageSize);
        if ($data) {
            return  $this->comment()->getCommentInfos($commentedId,array_keys($data));
        }else{
            return [];
        }
    }

    /**
     * 获取回复数量序评论列表
     * @param string $commentedId 被评论物ID
     * @param int $index 翻页索引
     * @param int $pageSize 操作的用户ID
     * @param string $order 排序 asc|desc
     * @return array
     */
    public function replyLineCommentList( $commentedId, $index = 0, $pageSize = 5, $order = "desc" ){
        $key = $this->replyLineCommentKey($commentedId);
        if ($order == "desc") {
            $start = $index ? $index : "+inf";
            $end = "-inf";
        }else{
            $start = $index ? $index : "-inf";
            $end = "+inf";
        }
        $data = $this->getCommentList( $key, $order, $start, $end, $pageSize);
        if ($data) {
            return  $this->comment()->getCommentInfos($commentedId,array_keys($data));
        }else{
            return [];
        }
    }

    //评论列表
    public function getCommentList( $key, $order, $start, $end, $pageSize ){
        if ($order == "desc"){
            $list = $this->redisClient->zRevRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }else{
            $list = $this->redisClient->zRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }
        return $list;
    }

    /**
     * 初始化评论列表
     * @param string $commentedId 被评论物ID
     */
    public function setCommentList( $commentedId ){
        $data = $this->getCommentListByDb( $commentedId );
        $timeLineKey = $this->timeLineCommentKey( $commentedId );
        $likeNumKey = $this->likeLineCommentKey( $commentedId );
        $replyNumKey = $this->replyLineCommentKey( $commentedId );
        $i = 0;
        $count = count($data);
        foreach ($data as $val) {
            $this->redisClient->zAdd($timeLineKey.":tmp", $this->getRedisScore($val["createtime"],$count,$i),$val["comment_id"]);
            $this->redisClient->zAdd($likeNumKey.":tmp", $this->getRedisScore($val["like_num"],$count,$i), $val["comment_id"]);
            $this->redisClient->zAdd($replyNumKey.":tmp", $this->getRedisScore($val["reply_num"],$count,$i), $val["comment_id"]);
            $i ++;
        }
        $this->redisClient->rename($timeLineKey.":tmp",$timeLineKey);
        $this->redisClient->rename($likeNumKey.":tmp",$likeNumKey);
        $this->redisClient->rename($replyNumKey.":tmp",$replyNumKey);
    }

    //获取评论列表
    private function getCommentListByDb( $commentedId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $sql = "select comment_id,like_num,reply_num,createtime from {$tableName} where origin_id = '{$this->originId}' and commented_id = '{$this->getCommentedId($commentedId)}' and parent_id = 0";
        $allComment = $this->mySqlClient->fetchAll( $sql );
        return $allComment;
    }

    //--------------- 回复部分 ---------------//

    private function reply(){
        return $this->reply;
    }

    public function timeLineReplyKey( $commentId ){
        $key = $this->redisKeyPrefix.":reply:list:timeLine:".$commentId;
        return $key;
    }

    /**
     * 获取时间序回复列表
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param int $index 翻页索引
     * @param int $pageSize 操作的用户ID
     * @param string $order 排序 asc|desc
     * @return array
     */
    public function timeLineReplyList( $commentedId, $commentId, $index = 0, $pageSize = 5, $order = "desc" ){
        $key = $this->timeLineReplyKey( $commentId );
        if ($order == "desc") {
            $start = $index ? $index - 1 : "+inf";
            $end = "-inf";
        }else{
            $start = $index ? $index + 1 : "-inf";
            $end = "+inf";
        }
        $data = $this->getReplyList( $key, $order, $start, $end, $pageSize);
        if ($data) {
            return  $this->reply()->getReplyInfos($commentedId,array_keys($data));
        }else{
            return [];
        }
    }

    //评论列表
    public function getReplyList( $key, $order, $start, $end, $pageSize ){
        if ($order == "desc"){
            $list = $this->redisClient->zRevRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }else{
            $list = $this->redisClient->zRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }
        return $list;
    }

    /**
     * 初始化回复列表
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     */
    public function setReplyList( $commentedId,$commentId ){
        $data = $this->getReplyListByDb( $commentedId, $commentId );
        $timeLineKey = $this->timeLineReplyKey( $commentId );
        $i = 0;
        $count = count($data);
        foreach ($data as $val) {
            $this->redisClient->zAdd($timeLineKey.":tmp", $this->getRedisScore($val["createtime"],$count,$i),$val["comment_id"]);
            $i ++;
        }
        $this->redisClient->rename($timeLineKey.":tmp",$timeLineKey);
    }

    //回复部分
    private function getReplyListByDb( $commentedId, $commentId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $sql = "select comment_id,like_num,reply_num,createtime from {$tableName} where origin_id = '{$this->originId}' and commented_id = '{$this->getCommentedId($commentedId)}' and parent_id = '{$commentId}'";
        echo $sql;
        $allReply = $this->mySqlClient->fetchAll( $sql );
        return $allReply;
    }

}