<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

class LiComment extends LiBase {

    private $redisClient;
    private $mySqlClient;
    private $tablePrefix;
    private $redisKeyPrefix;

    private $originId;
    private $commentLastError;

    function  __construct( $originId, $redisClient, $mySqlClient,  $redisKeyPrefix, $tablePrefix ){
        parent::__construct($originId);
        $this->originId = $originId;
        $this->redisClient = $redisClient;
        $this->mySqlClient = $mySqlClient;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->tablePrefix = $tablePrefix;
    }

    public function getLastError(){
        return $this->commentLastError;
    }

    private function setLastError( $lastError ){
        $this->commentLastError = $lastError;
    }

    /**
     * 获取单个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @return array
     */
    public function getComment( $commentedId, $commentId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $commentInfo = $this->mySqlClient->GetOne( $tableName, 'comment_id = ?', $commentId) ;
        if (!$this->getCheck($commentInfo)){
            return [];
        }
        if ($commentInfo) {
            $commentInfo = $this->dbDataDecode($commentInfo);
            return $commentInfo;
        }else{
            return [];
        }
    }

    public function getComments(){

    }

    /**
     * 创建一个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentedUser 被评论物所属用户ID
     * @param string $userId 产生评论的用户ID
     * @param string $content 评论内容
     * @param string $expands 扩展信息
     * @return string
     */
    public function add ( $commentedId, $commentedUser, $userId, $content, $expands ){
        $tableName = $this->tableName($this->tablePrefix, $commentedId);
        $data = [
            "origin_id"  => $this->originId,
            "commented_id" => $commentedId,
            "parent_id" => 0,
            "user_id"   => $userId,
            "target_user" => 0,
            "commented_user" => $commentedUser,
            "content" => $content,
            "expands" => $expands
        ];
        $insertData = $this->dbDataEncode($data);
        if ( $this->mySqlClient->Add( $tableName, $insertData ) ) {
            return $insertData["comment_id"];
        }else{
            $this->setLastError( $this->mySqlClient->LastError() );
            return "0";
        }
    }

    /**
     * 删除一个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $actionUserId 操作的用户ID
     * @return bool
     */
    public function del ( $commentedId, $commentId, $actionUserId ){
        $commentInfo = $this->getComment( $commentedId, $commentId );
        if ( ! $this->deleteCheck($commentInfo,$actionUserId) ) {
            return false;
        }
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $rowCount = $this->mySqlClient->Del ( $tableName, 'comment_id = ? limit 1', $commentId );
        if ( $rowCount > 0 ) {
            //TODO  删除回复
            return true;
        }else{
            return false;
        }
    }

    /**
     * 置顶
     * @param string $commentId 评论ID
     * @param string $level 级别
     * @return bool
     */
    function top ( $commentId, $level ){

        return true;
    }

    //检查是否能删除
    private function deleteCheck ( $commentInfo, $actionUserId ) {
        if ($this->isComment($commentInfo) && $this->isOriginId($commentInfo) && $this->isOperatorUser($commentInfo,$actionUserId)) {
            return true;
        }else{
            $this->setLastError( $this->getBaseLastError() );
            return false;
        }
    }

    //检查是否合法评论
    private function getCheck ( $commentInfo ) {
        if ( $this->isComment($commentInfo) && $this->isOriginId($commentInfo) ) {
            return true;
        }else{
            $this->setLastError($this->getBaseLastError() );
            return false;
        }
    }
}