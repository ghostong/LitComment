<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

class LiComment extends LiBase {

    private $commentLastError;

    function  __construct( $originId, $commentRule ){
        parent::__construct($originId, $commentRule );
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
        return $this->getRedisKeyPrefix().":".$this->originId.":comment:info:".$commentId;
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
        if ( $redisRes ){
            $commentInfo = json_decode($redisRes,true);
            if (!empty($commentInfo) && !$this->getCheck($commentedId, $commentInfo)){
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
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $commentedId);
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
        if(empty($commentInfo)){
            $this->getRedisClient()->expire($this->getCommentInfoKey($commentId),3600*24);
        }
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
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $commentedId);
        $rowCount = $this->getMySqlClient()->Del ( $tableName, 'comment_id = ? limit 1', $commentId );
        if ( $rowCount > 0 ) {
            $this->onDel( $commentInfo );
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除所有评论
     * @param string $commentedId 被评论物ID
     * @return bool
     */
    public function delAll ( $commentedId ){
        $allComment = $this->getList()->getAllComment( $commentedId );
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $commentedId);
        $rowCount = $this->getMySqlClient()->Del ( $tableName, 'origin_id = ? and commented_id = ? and parent_id = "0"', $this->originId, $this->getCommentedId($commentedId) );
        if ( $rowCount > 0 ) {
            $this->onDelAll( $commentedId, $allComment );
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取评论数
     * @param string $commentedId 被评论物ID
     * @return int
     */
    public function getCommentNum ( $commentedId ) {
        return $this->getList()->getCommentNum($commentedId);
    }

    /**
     * 设置点赞数量
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $num 评论数量
     * @return bool
     */
    public function setLikeNum ( $commentedId, $commentId, $num ){
        return $this->update( $commentedId, $commentId, "like_num = ? " , [ $num ] );
    }

    /**
     * 设置回复数量
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $num 评论数量
     * @return bool
     */
    public function setReplyNum ( $commentedId, $commentId, $num ){
        return $this->update( $commentedId, $commentId, "reply_num = ? " , [ $num ] );
    }

    /**
     * 更新评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $setStr 修改信息
     * @param array $setValue 修改值
     * @return bool
     */
    private function update ( $commentedId, $commentId, $setStr, $setValue ){
        $commentInfo = $this->getCommentInfo( $commentedId, $commentId );
        if (!$this->getCheck($commentedId, $commentInfo)){
            return false;
        }
        $tableName = $this->tableName($this->getMySqlTablePrefix(), $commentedId);
        $sql = "update {$tableName} set {$setStr} where `comment_id` = ? limit 1";
        $setValue[] = $commentId;
        $pdoStatement = $this->getMySqlClient()->execute($sql,$setValue);
        echo $this->getMySqlClient()->LastSql();
        if ( $pdoStatement ) {
            $rowCount = $pdoStatement->rowCount();
            if($rowCount){
                $this->onUpdate( $commentInfo );
                return true;
            }
        }
        return false;
    }

    //检查是否能删除
    private function deleteCheck ( $commentedId, $commentInfo, $actionUserId ) {
        if (empty($commentInfo)) {
            $this->setLastError( "评论ID 不存在");
            return false;
        }
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
        //重置评论有关的列表
        $this->getList()->setCommentList( $commentInfo["commented_id"] );
    }

    private function onUpdate( $commentInfo ){
        //重置评论有关的列表
        $this->setCommentInfo( $commentInfo["commented_id"], $commentInfo["comment_id"]);
        //重置评论有关的列表
        $this->getList()->setCommentList( $commentInfo["commented_id"] );
    }

    private function onDel( $commentInfo ){
        //删除评论对应的redis key
        $this->getRedisClient()->del( $this->getCommentInfoKey($commentInfo["comment_id"]) );
        //删除所有回复
        $this->getReply()->delAll( $commentInfo["commented_id"], $commentInfo["comment_id"] );
        //重置评论有关的列表
        $this->getList()->setCommentList( $commentInfo["commented_id"] );
    }

    private function onDelAll ( $commentedId,$allComment ){
        foreach ($allComment as $commentId => $val) {
            //删除所有回复
            $this->getReply()->delAll($commentedId,$commentId);
            //删除评论对应的redis key
            $key = $this->getCommentInfoKey($commentId);
            $this->getRedisClient()->del($key);
        }
        //删除评论下面所有的回复
        $this->getList()->setCommentList( $commentedId );
    }
}