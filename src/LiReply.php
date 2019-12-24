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

    //获取错误信息
    public function getLastError(){
        return $this->replyLastError;
    }

    //设置错误信息
    private function setLastError( $lastError ){
        $this->replyLastError = $lastError;
    }

    //评论信息的key
    private function getReplyInfoKey( $replyId ){
        return $this->redisKeyPrefix.":reply:info:".$replyId;
    }

    /**
     * 获取单个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $replyId 回复ID
     * @return array
     */
    public function getReplyInfo( $commentedId, $commentId, $replyId ){
        $key = $this->getReplyInfoKey($replyId);
        $redisRes = $this->redisClient->get($key);
        if ($redisRes){
            $replyIdInfo = json_decode($redisRes,true);
            if ( !$this->getCheck( $commentId,$replyIdInfo ) ){
                return [];
            }else{
                return $replyIdInfo;
            }
        }else{
            return $this->setReplyInfo( $commentedId, $commentId, $replyId );
        }
    }

    /**
     * 设置单个回复缓存
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $replyId 回复ID
     * @return array
     */
    public function setReplyInfo( $commentedId, $commentId, $replyId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $replyIdInfo = $this->mySqlClient->GetOne( $tableName, 'comment_id = ?', $replyId) ;
        if (!$this->getCheck( $commentId, $replyIdInfo )){
            return [];
        }
        if($replyIdInfo){
            $replyIdInfo = $this->dbDataDecode($replyIdInfo);
        }else{
            $replyIdInfo = [];
        }
        $this->redisClient->set($this->getReplyInfoKey($replyId),json_encode($replyIdInfo));
        return $replyIdInfo;

    }

    /**
     * 获取单个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param array $replyIds 多个回复ID
     * @return array
     */
    public function getReplyInfos( $commentedId, $commentId, $replyIds = array() ){
        $redisKeys = array_map(function( $replyId ){ return $this->getReplyInfoKey($replyId); },$replyIds);
        $replyInfos = $this->redisClient->mGet($redisKeys);
        $combine = array_combine($replyIds,$replyInfos);
        foreach ( $combine as $replyId => $info ) {
            $replyInfo = $info ?  json_decode($info,true) : $this->getReplyInfo($commentedId,$commentId,$replyId);
            if( !$this->getCheck( $commentId, $replyInfo ) ) {
                $combine[$replyId] = [];
            }else{
                $combine[$replyId] = $replyInfo;
            }
        }
        return $combine;
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

    /**
     * 删除一个回复
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $replyId 回复ID
     * @param string $actionUserId 操作的用户ID
     * @return bool
     */
    public function del ( $commentedId, $commentId, $replyId, $actionUserId ){
        $replyInfo = $this->getReplyInfo( $commentedId, $commentId, $replyId );
        if ( ! $this->deleteCheck( $commentId, $replyInfo,$actionUserId ) ) {
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
    private function deleteCheck ( $commentId, $replyInfo, $actionUserId ) {
        if ($this->isReply($replyInfo) && $this->isOriginId($replyInfo) && $this->isOperatorUser($replyInfo,$actionUserId) && $this->isParentId($replyInfo,$commentId)) {
            return true;
        }else{
            $this->setLastError( $this->getBaseLastError() );
            return false;
        }
    }

    //检查是否合法评论
    private function getCheck ( $commentId, $replyInfo ) {
        if ( $this->isReply($replyInfo) && $this->isOriginId($replyInfo) && $this->isParentId($replyInfo,$commentId)) {
            return true;
        }else{
            $this->setLastError($this->getBaseLastError() );
            return false;
        }
    }
}