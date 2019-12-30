<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

class LiReply extends LiBase {

    private $replyLastError;

    function  __construct( $originId, $commentRule ){
        parent::__construct( $originId, $commentRule );
    }

    //获取错误信息
    public function getLastError(){
        return $this->replyLastError;
    }

    //设置错误信息
    private function setLastError( $file, $line, $lastError ){
        $this->replyLastError = $file.":".$line ."-> [". $lastError."[". $this->replyLastError."]]";
    }

    //评论信息的key
    private function getReplyInfoKey( $replyId ){
        return $this->getRedisKeyPrefix().":".$this->originId.":reply:info:".$replyId;
    }

    /**
     * 获取单个评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $replyId 回复ID
     * @return array
     */
    public function getReplyInfo( $commentedId, $commentId, $replyId ){
        if (empty($commentedId) || empty($commentId) || empty($replyId)) {
            return [];
        }
        $key = $this->getReplyInfoKey($replyId);
        $redisRes = $this->getRedisClient()->get($key);
        if ($redisRes){
            $replyInfo = json_decode($redisRes,true);
            if ( empty($replyInfo) || !$this->getCheck( $commentId,$replyInfo ) || !$this->isShowRule($replyInfo['status']) ){
                return [];
            }else{
                $replyInfo["reply_id"] = $replyInfo["comment_id"];
                return $replyInfo;
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
        if (empty($commentedId) || empty($commentId) || empty($replyId)) {
            return [];
        }
        $tableName = $this->tableName($commentedId);
        $replyIdInfo = $this->getMySqlClient()->GetOne( $tableName, 'comment_id = ?', $replyId) ;
        if (!$this->getCheck( $commentId, $replyIdInfo )){
            return [];
        }
        if($replyIdInfo){
            $replyIdInfo = $this->dbDataDecode($replyIdInfo);
        }else{
            $replyIdInfo = [];
        }
        $this->getRedisClient()->set($this->getReplyInfoKey($replyId),json_encode($replyIdInfo));
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
        if (empty($commentedId) || empty($commentId) || empty($replyIds)) {
            return [];
        }
        $redisKeys = array_map(function( $replyId ){ return $this->getReplyInfoKey($replyId); },$replyIds);
        $replyInfos = $this->getRedisClient()->mGet($redisKeys);
        $combine = array_combine($replyIds,$replyInfos);
        foreach ( $combine as $replyId => $info ) {
            $replyInfo = $info ?  json_decode($info,true) : $this->getReplyInfo($commentedId,$commentId,$replyId);
            if( empty($replyInfo) || !$this->getCheck( $commentId, $replyInfo ) || !$this->isShowRule($replyInfo['status']) ) {
                $combine[$replyId] = [];
            }else{
                $replyInfo["reply_id"] = $replyInfo["comment_id"];
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
    public function add ( $commentedId, $commentedUser, $commentId, $userId, $targetUser, $content, $expands = ""){
        if (empty($commentedId) || empty($commentedUser)|| empty($commentId) || empty($userId) || empty($content)) {
            return 0;
        }
        $commentInfo = $this->getComment()->getCommentInfo( $commentedId, $commentId );
        if ( empty($commentInfo) ){
            $this->setLastError(__FILE__,__LINE__,"评论ID:".$commentId." 不存在!");
            return 0;
        }
        $tableName = $this->tableName($commentedId);
        $data = [
            "origin_id"  => $this->originId,
            "commented_id" => $commentedId,
            "parent_id" => $commentId,
            "user_id"   => $userId,
            "target_user" => $targetUser,
            "commented_user" => $commentedUser,
            "content" => $content,
            "expands" => $expands,
            "status" => 0
        ];
        $insertData = $this->dbDataEncode($data);
        if ( $this->getMySqlClient()->Add( $tableName, $insertData ) ) {
            $this->onAdd( $this->getReplyInfo( $commentedId, $insertData["parent_id"], $insertData["comment_id"]));
            return $insertData["comment_id"];
        }else{
            $this->setLastError( __FILE__, __LINE__, $this->getMySqlClient()->LastError() );
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
        $replyInfo = $this->setReplyInfo( $commentedId, $commentId, $replyId );
        if ( ! $this->deleteCheck( $commentId, $replyInfo,$actionUserId ) ) {
            return false;
        }
        $tableName = $this->tableName($commentedId);
        $rowCount = $this->getMySqlClient()->Del ( $tableName, 'comment_id = ? limit 1', $replyId );
        if ($rowCount > 0) {
            $this->onDel( $replyInfo );
            return true;
        }else{
            return false;
        }
    }

    public function pass( $commentedId, $commentId, $replyId ){
        return $this->update( $commentedId, $commentId, $replyId, "status = ? " , [ 1 ] );
    }

    public function reject( $commentedId, $commentId, $replyId ){
        return $this->update( $commentedId, $commentId, $replyId, "status = ? " , [ -1 ] );
    }

    /**
     * 更新评论
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @param string $replyId 回复ID
     * @param string $setStr 修改信息
     * @param array $setValue 修改值
     * @return bool
     */
    public function update ( $commentedId, $commentId, $replyId, $setStr, $setValue ){
        $replyInfo = $this->setReplyInfo( $commentedId, $commentId, $replyId );
        if (!$this->getCheck($commentId, $replyInfo)){
            return false;
        }
        $tableName = $this->tableName($commentedId);
        $sql = "update {$tableName} set {$setStr} where `comment_id` = ? limit 1";
        $setValue[] = $replyId;
        $pdoStatement = $this->getMySqlClient()->execute($sql,$setValue);
        echo $this->getMySqlClient()->LastSql();
        if ( $pdoStatement ) {
            $rowCount = $pdoStatement->rowCount();
            if($rowCount){
                $this->onUpdate( $replyInfo );
                return true;
            }
        }
        return false;
    }

    /**
     * 删除所有回复
     * @param string $commentedId 被评论物ID
     * @param string $commentId 评论ID
     * @return bool
     */
    public function delAll ( $commentedId, $commentId ){
        $allReply = $this->getList()->getReplyListByDb( $commentedId, $commentId );
        $tableName = $this->tableName($commentedId);
        $rowCount = $this->getMySqlClient()->Del ( $tableName, 'origin_id = ? and commented_id = ? and parent_id = ? ', $this->originId, $this->getCommentedId($commentedId), $commentId );
        if ( $rowCount > 0 ) {
            $this->onDelAll( $commentedId, $commentId, $allReply );
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取评论回复数
     * @param string $commentId 评论ID
     * @return int
     */
    public function getReplyNum ($commentId) {
        return $this->getList()->getReplyNum($commentId);
    }

    //检查是否能删除
    private function deleteCheck ( $commentId, $replyInfo, $actionUserId ) {
        if ($this->isReply($replyInfo) && $this->isOriginId($replyInfo) && $this->isOperatorUser($replyInfo,$actionUserId) && $this->isParentId($replyInfo,$commentId)) {
            return true;
        }else{
            $this->setLastError( __FILE__, __LINE__, $this->getBaseLastError() );
            return false;
        }
    }

    //检查是否合法评论
    private function getCheck ( $commentId, $replyInfo ) {
        if ( $this->isReply($replyInfo) && $this->isOriginId($replyInfo) && $this->isParentId($replyInfo,$commentId)) {
            return true;
        }else{
            $this->setLastError( __FILE__, __LINE__, $this->getBaseLastError() );
            return false;
        }
    }

    //hook
    private function onAdd ( $replyInfo ) {
        //修改评论的回复数
        $replyNum = $this->getList()->getReplyNum($replyInfo["parent_id"]) + 1;
        $this->getComment()->setReplyNum($replyInfo["commented_id"], $replyInfo["parent_id"], $replyNum);
        //重置回复有关的列表
        $this->getList()->setReplyList( $replyInfo["commented_id"], $replyInfo["parent_id"] );

        //写入评论搜索引擎
        $doc = new \XSDocument;
        $doc->setFields($replyInfo);
        $this->getXunSearchClient()->index->add($doc);
    }

    private function onDel ( $replyInfo ) {
        //删除回复对应的redis key
        $key = $this->getReplyInfoKey($replyInfo["comment_id"]);
        $this->getRedisClient()->del($key);
        //修改评论的回复数
        $replyNum = $this->getList()->getReplyNum($replyInfo["parent_id"]) - 1;
        if($replyNum >= 0) {
            $this->getComment()->setReplyNum($replyInfo["commented_id"], $replyInfo["parent_id"], $replyNum);
        }
        //重置回复有关的列表
        $this->getList()->setReplyList( $replyInfo["commented_id"], $replyInfo["parent_id"] );

        //删除评论搜索引擎
        $this->getXunSearchClient()->index->del($replyInfo["comment_id"]);
    }

    private function onDelAll ( $commentedId, $commentId, $allReply ) {
        //删除回复对应的redis key
        $this->getRedisClient()->multi();
        foreach ($allReply as $key=>$val) {
            $replyId = $val["comment_id"];
            $key = $this->getReplyInfoKey($replyId);
            $this->getRedisClient()->del($key);
            //删除评论搜索引擎
            $this->getXunSearchClient()->index->del($replyId);
        }
        $this->getRedisClient()->exec();
        //修改评论的回复数
        $this->getComment()->setReplyNum($commentedId, $commentId, 0);
        //重置回复有关的列表
        $this->getList()->setReplyList( $commentedId, $commentId);
    }

    private function onUpdate ( $replyInfo ){
        $this->setReplyInfo($replyInfo["commented_id"],$replyInfo["parent_id"],$replyInfo["comment_id"]);
        $this->getList()->setReplyList( $replyInfo["commented_id"],$replyInfo["parent_id"] );

        //更新评论搜索引擎
        $doc = new \XSDocument;
        $doc->setFields($replyInfo);
        $this->getXunSearchClient()->index->update($doc);
    }
}