<?php
/**
 * User: ghost
 * Date: 2019-12-23
 * Time: 17:28
 */

namespace Lit\Comment;


class LiList extends LiBase {

    private $listLastError;

    function  __construct( $originId, $commentRule ){
        parent::__construct($originId, $commentRule);
    }

    public function getLastError(){
        return $this->listLastError;
    }

    private function setLastError( $lastError ){
        $this->listLastError = $lastError;
    }

    //--------------- 评论部分 ---------------//

    public function timeLineCommentKey( $commentedId ){
        $key = $this->getRedisKeyPrefix().":".$this->originId.":comment:list:timeLine:".$commentedId;
        return $key;
    }

    public function likeLineCommentKey ( $commentedId ){
        $key = $this->getRedisKeyPrefix().":".$this->originId.":comment:list:likeLine:".$commentedId;
        return $key;
    }

    public function replyLineCommentKey ( $commentedId ){
        $key = $this->getRedisKeyPrefix().":".$this->originId.":comment:list:replyLine:".$commentedId;
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
            return  $this->getComment()->getCommentInfos($commentedId,array_keys($data));
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
            return  $this->getComment()->getCommentInfos($commentedId,array_keys($data));
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
            return  $this->getComment()->getCommentInfos($commentedId,array_keys($data));
        }else{
            return [];
        }
    }

    /**
     * 获取评论数
     * @param string $commentedId 被评论物ID
     * @return int
     */
    public function getCommentNum ( $commentedId ) {
        $key = $this->timeLineCommentKey( $commentedId );
        return $this->getRedisClient()->zCard($key);
    }

    //所有评论列表
    public function getAllComment( $commentedId ){
        $key = $this->timeLineCommentKey($commentedId);
        return $this->getRedisClient()->zRevRangeByScore($key,"+inf","-inf",["withscores" => true]);
    }

    //评论列表
    public function getCommentList( $key, $order, $start, $end, $pageSize ){
        if ($order == "desc"){
            $list = $this->getRedisClient()->zRevRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }else{
            $list = $this->getRedisClient()->zRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }
        return $list;
    }

    /**
     * 初始化评论列表
     * @param string $commentedId 被评论物ID
     */
    public function setCommentList( $commentedId ){
        if ($this->commentRule == 1) { //先发后审
            $status = 0;
        } elseif ($this->commentRule == 2){ //先审后发
            $status = 1;
        } elseif ($this->commentRule == 3){ //无需审核
            $status = null;
        } elseif ( $this->commentRule  == 4) { //无需展示
            return;
        }else{
            return;
        }
        $data = $this->getCommentListByDb( $commentedId );
        $timeLineKey = $this->timeLineCommentKey( $commentedId );
        $likeNumKey = $this->likeLineCommentKey( $commentedId );
        $replyNumKey = $this->replyLineCommentKey( $commentedId );
        $i = 0;
        $commentCount = count($data);
        if ($commentCount == 0) {
            $this->getRedisClient()->del($timeLineKey);
            $this->getRedisClient()->del($likeNumKey);
            $this->getRedisClient()->del($replyNumKey);
        }else{
            foreach ($data as $val) {
                if ( $status === null && $val["status"] == $status ) {
                    $this->getRedisClient()->zAdd($timeLineKey.":tmp", $this->getRedisScore($val["createtime"],$commentCount,$i),$val["comment_id"]);
                    $this->getRedisClient()->zAdd($likeNumKey.":tmp", $this->getRedisScore($val["like_num"],$commentCount,$i), $val["comment_id"]);
                    $this->getRedisClient()->zAdd($replyNumKey.":tmp", $this->getRedisScore($val["reply_num"],$commentCount,$i), $val["comment_id"]);
                    $i ++;
                }
            }
            $this->getRedisClient()->rename($timeLineKey.":tmp",$timeLineKey);
            $this->getRedisClient()->rename($likeNumKey.":tmp",$likeNumKey);
            $this->getRedisClient()->rename($replyNumKey.":tmp",$replyNumKey);
        }
    }

    //获取评论列表
    private function getCommentListByDb( $commentedId ){
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $commentedId);
        $sql = "select comment_id,like_num,reply_num,createtime,status from {$tableName} where origin_id = '{$this->originId}' and commented_id = '{$this->getCommentedId($commentedId)}' and parent_id = 0";
        $allComment = $this->getMySqlClient()->fetchAll( $sql );
        return $allComment;
    }

    //--------------- 回复部分 ---------------//

    public function timeLineReplyKey( $commentId ){
        $key = $this->getRedisKeyPrefix().":reply:list:timeLine:".$commentId;
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
            return  $this->getReply()->getReplyInfos($commentedId,$commentId,array_keys($data));
        }else{
            return [];
        }
    }

    /**
     * 获取评论回复数
     * @param string $commentId 评论ID
     * @return int
     */
    public function getReplyNum ($commentId) {
        $key = $this->timeLineReplyKey( $commentId );
        return $this->getRedisClient()->zCard($key);
    }

    //获取所有回复
    public function getAllReply( $commentId ){
        $key = $this->timeLineReplyKey( $commentId );
        return  $this->getRedisClient()->zRevRangeByScore($key, "+inf","-inf", ["withscores" => true]);
    }

    //评论列表
    public function getReplyList( $key, $order, $start, $end, $pageSize ){
        if ($order == "desc"){
            $list = $this->getRedisClient()->zRevRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }else{
            $list = $this->getRedisClient()->zRangeByScore( $key, $start , $end, [ "withscores" => true, "limit" => [ 0, $pageSize] ]);
        }
        return $list;
    }

    /**
     * 初始化回复列表
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     */
    public function setReplyList( $commentedId,$commentId ){
        if ($this->commentRule == 1) { //先发后审
            $status = 0;
        } elseif ($this->commentRule == 2){ //先审后发
            $status = 1;
        } elseif ($this->commentRule == 3){ //无需审核
            $status = null;
        } elseif ( $this->commentRule  == 4) { //无需展示
            return;
        }else{
            return;
        }
        $data = $this->getReplyListByDb( $commentedId, $commentId );
        $timeLineKey = $this->timeLineReplyKey( $commentId );
        $i = 0;
        $count = count($data);
        if($count == 0) {
            $this->getRedisClient()->del($timeLineKey);
        }else{
            foreach ($data as $val) {
                if ($status === null || $val["status"] == $status) {
                    $this->getRedisClient()->zAdd($timeLineKey . ":tmp", $this->getRedisScore($val["createtime"], $count, $i), $val["comment_id"]);
                    $i++;
                }
            }
            $this->getRedisClient()->rename($timeLineKey.":tmp",$timeLineKey);
        }
    }

    //回复部分
    private function getReplyListByDb( $commentedId, $commentId ){
        $tableName = $this->tableName($this->getMySqlTablePrefix(),$commentedId);
        $sql = "select comment_id,like_num,reply_num,createtime from {$tableName} where origin_id = '{$this->originId}' and commented_id = '{$this->getCommentedId($commentedId)}' and parent_id = '{$commentId}'";
        $allReply = $this->getMySqlClient()->fetchAll( $sql );
        return $allReply;
    }

}