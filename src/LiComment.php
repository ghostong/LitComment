<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

class LiComment extends LiBase {

    private $commentLastError;

    function  __construct( $originId ){
        parent::__construct($originId);
    }

    //获取错误信息
    public function getLastError(){
        return $this->commentLastError;
    }

    //设置错误信息
    private function setLastError( $lastError ){
        $this->commentLastError = $lastError;
    }

    //评论信息的key
    private function getCommentInfoKey( $commentId ){
        return $this->getRedisKeyPrefix().":comment:info:".$commentId;
    }

    /**
     * 获取单个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @return array
     */
    public function getCommentInfo( $commentedId, $commentId ){
        $key = $this->getCommentInfoKey($commentId);
        $redisRes = $this->getRedisClient()->get($key);
        if ($redisRes){
            $commentInfo = json_decode($redisRes,true);
            if (!$this->getCheck($commentedId, $commentInfo)){
                return [];
            }else{
                return $commentInfo;
            }
        }else{
            return $this->setCommentInfo( $commentedId, $commentId );
        }
    }

    /**
     * 设置单个评论缓存
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @return array
     */
    public function setCommentInfo( $commentedId, $commentId ){
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $this->getCommentedId($commentedId));
        $commentInfo = $this->getMySqlClient()->GetOne( $tableName, 'comment_id = ?', $commentId) ;
        if ($commentInfo) {
            $commentInfo = $this->dbDataDecode($commentInfo);
            if (!$this->getCheck($commentedId, $commentInfo)){
                return [];
            }
        }else{
            $commentInfo = [];
        }
        $this->getRedisClient()->set($this->getCommentInfoKey($commentId),json_encode($commentInfo));
        return $commentInfo;
    }

    /**
     * 获取多个评论
     * @param string $commentedId 被评论物ID
     * @param array $commentIds 多个评论ID
     * @return array
     */
    public function getCommentInfos( $commentedId, $commentIds = array() ){
        $redisKeys = array_map(function( $commentId ){ return $this->getCommentInfoKey($commentId); },$commentIds);
        $commentInfos = $this->getRedisClient()->mGet($redisKeys);
        $combine = array_combine($commentIds,$commentInfos);
        foreach ( $combine as $commentId => $info ) {
            $commentInfo = $info ?  json_decode($info,true) : $this->getCommentInfo($commentedId,$commentId);
            if (!$this->getCheck($commentedId,$commentInfo)) {
                $combine[$commentId] = [];
            }else{
                $combine[$commentId] = $commentInfo;
            }
        }
        return $combine;
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
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $commentedId);
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
        if ( $this->getMySqlClient()->Add( $tableName, $insertData ) ) {
            $this->onAdd( $this->getCommentInfo( $commentedId, $insertData["comment_id"] ) );
            return $insertData["comment_id"];
        }else{
            $this->setLastError( $this->getMySqlClient()->LastError() );
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
        $commentInfo = $this->getCommentInfo( $commentedId, $commentId );
        if ( ! $this->deleteCheck($commentedId, $commentInfo,$actionUserId) ) {
            return false;
        }
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $this->getCommentedId($commentedId));
        $rowCount = $this->getMySqlClient()->Del ( $tableName, 'comment_id = ? limit 1', $commentId );
        if ( $rowCount > 0 ) {
            $this->onDelete( $commentInfo );
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
    private function deleteCheck ( $commentedId, $commentInfo, $actionUserId ) {
        if ( $this->isComment($commentInfo) && $this->isOriginId($commentInfo) && $this->isOperatorUser($commentInfo,$actionUserId) && $this->isCommented($commentInfo, $commentedId) ) {
            return true;
        }else{
            $this->setLastError( $this->getBaseLastError() );
            return false;
        }
    }

    //检查是否合法评论
    private function getCheck ( $commentedId, $commentInfo ) {
        if ( $this->isComment($commentInfo) && $this->isOriginId($commentInfo) && $this->isCommented($commentInfo, $commentedId) ) {
            return true;
        }else{
            $this->setLastError($this->getBaseLastError() );
            return false;
        }
    }


    //hook
    private function onAdd( $commentInfo ){

    }
    private function onUpdate( $commentInfo ){

    }
    private function onDelete( $commentInfo ){

    }
}