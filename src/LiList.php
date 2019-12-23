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

    private $originId;
    private $listLastError;

    function  __construct( $originId, $redisClient, $mySqlClient,  $redisKeyPrefix, $tablePrefix ){
        parent::__construct($originId);
        $this->originId = $originId;
        $this->redisClient = $redisClient;
        $this->mySqlClient = $mySqlClient;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->tablePrefix = $tablePrefix;
    }

    public function newestListKey( $commentedId ){
        $key = $this->redisKeyPrefix.":newestList:".$commentedId;
        return $key;
    }

    public function getLastError(){
        return $this->listLastError;
    }

    private function setLastError( $lastError ){
        $this->listLastError = $lastError;
    }

    //最新评论
    public function getNewestList( $commentedId ){
        $key = $this->newestListKey( $commentedId );
        $this->setNewestList( $commentedId );
    }

    public function setNewestList( $commentedId , $data = null ){
        if ( $data === null ) {
            $data = $this->getCommentList( $commentedId );
        }
        $key = $this->newestListKey( $commentedId );
        $tmpKey = $key.":tmp";
        foreach ($data as $val) {
            $this->redisClient->Zadd($tmpKey,$val["createtime"],$val["comment_id"]);
        }
        $this->redisClient->Rename($tmpKey,$key);
    }

    private function getCommentList( $commentedId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $sql = "select comment_id,like_num,reply_num,createtime from {$tableName} where origin_id = '{$this->originId}' and commented_id = '{$this->getCommentedId($commentedId)}' and parent_id = 0";
        $allComment = $this->mySqlClient->FetchAll( $sql );
        return $allComment;
    }

    private function getReplyList( $commentedId, $commentId ){
        $tableName = $this->tableName($this->tablePrefix, $this->getCommentedId($commentedId));
        $sql = "select comment_id,like_num,reply_num,createtime from {$tableName} where origin_id = '{$this->originId}' and commented_id = '{$this->getCommentedId($commentedId)}' and parent_id = '{$commentId}'";
        $allReply = $this->mySqlClient->FetchAll( $sql );
        return $allReply;
    }

}