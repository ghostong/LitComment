<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

class LiReply extends LiBase {

    private $redisClient;
    private $mySqlClient;
    private $tablePrefix;
    private $redisKeyPrefix;

    private $originId;
    private $replyLastError;

    function  __construct( $originId, $redisClient, $mySqlClient,  $redisKeyPrefix, $tablePrefix ){
        parent::__construct($originId);
        $this->originId = $originId;
        $this->redisClient = $redisClient;
        $this->mySqlClient = $mySqlClient;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->tablePrefix = $tablePrefix;
    }

    public function getLastError(){
        return $this->replyLastError;
    }

    private function setLastError( $lastError ){
        $this->replyLastError = $lastError;
    }

    //获取单个回复
    public function getReply( $commentedId, $replyId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $replyIdInfo = $this->mySqlClient->GetOne( $tableName, 'comment_id = ?', $replyId) ;
        if (!$this->getCheck($replyIdInfo)){
            return [];
        }
        if($replyIdInfo){
            $replyIdInfo = $this->dbDataDecode($replyIdInfo);
            return $replyIdInfo;
        }else{
            return [];
        }
    }

    //获取多个回复
    public function getReplies( ){

    }

    /**
     * 创建一个回复
     * @param string $commentedId 被评论物ID
     * @param string $commentedUser 被评论物所属用户ID
     * @param string $commentId 评论ID
     * @param string $userId 产生评论的用户ID
     * @param string $targetUser 评论针对人ID
     * @param string $content 评论内容
     * @param string $expands 扩展信息
     * @return string
     */
    public function add ( $commentedId, $commentedUser, $commentId, $userId, $targetUser, $content, $expands ){
        $tableName = $this->tableName($this->tablePrefix, $commentedId);
        $data = [
            "origin_id"  => $this->originId,
            "commented_id" => $commentedId,
            "parent_id" => $commentId,
            "user_id"   => $userId,
            "target_user" => $targetUser,
            "commented_user" => $commentedUser,
            "content" => $content,
            "expands" => $expands
        ];
        $insertData = $this->dbDataEncode($data);
        if ( $this->mySqlClient->Add( $tableName, $insertData ) ) {
            return $insertData["comment_id"];
        }else{
            $this->setLastError( $this->mySqlClient->LastError() );
            return 0;
        }
    }

    //删除一个回复
    public function del ( $commentedId, $replyId, $actionUserId ){
        $replyInfo = $this->getReply( $commentedId, $replyId );
        if ( ! $this->deleteCheck($replyInfo,$actionUserId) ) {
            return false;
        }
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($replyId));
        $rowCount = $this->mySqlClient->Del ( $tableName, 'comment_id = ? limit 1', $replyId );
        if ($rowCount > 0) {
            return true;
        }else{
            return false;
        }
    }

    //检查是否能删除
    private function deleteCheck ( $replyInfo, $actionUserId ) {
        if ($this->isReply($replyInfo) && $this->isOriginId($replyInfo) && $this->isOperatorUser($replyInfo,$actionUserId)) {
            return true;
        }else{
            $this->setLastError( $this->getBaseLastError() );
            return false;
        }
    }

    //检查是否合法评论
    private function getCheck ( $replyInfo ) {
        if ( $this->isReply($replyInfo) && $this->isOriginId($replyInfo) ) {
            return true;
        }else{
            $this->setLastError($this->getBaseLastError() );
            return false;
        }
    }
}