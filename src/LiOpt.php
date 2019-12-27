<?php
/**
 * User: ghost
 * Date: 2019-12-27
 * Time: 11:31
 */

namespace Lit\Comment;

class LiOpt extends LiBase {
    function  __construct( $originId, $commentRule ){
        parent::__construct($originId, $commentRule );
    }

    private function getCommentTopKey ( $commentedId ) {
        return $this->getRedisKeyPrefix().":".$this->originId.":comment:top:".$this->getCommentedId($commentedId);
    }

    public function setCommentTop ( $commentedId, $commentId , $topLevel ) {
        $info = $this->getComment()->getCommentInfo( $commentedId, $commentId );
        if ($info) {
            $key = $this->getCommentTopKey($commentedId);
            $this->getRedisClient()->zAdd( $key, $topLevel, $commentId );
            return true;
        }else{
            return false;
        }
    }

    public function getTopComment ( $commentedId ){
        $key = $this->getCommentTopKey($commentedId);
        $list = $this->getRedisClient()->zRevRangeByScore( $key, "+inf" , "-inf", [ "withscores" => true] );
        return $this->getComment()->getCommentInfos($commentedId,array_keys($list));
    }

    public function delTopComment ( $commentedId, $commentId ) {
        $key = $this->getCommentTopKey($commentedId);
        $this->getRedisClient()->zRem( $key, $commentId );
        return true;
    }

    public function commentPass( $commentedId, $commentId ){
        return $this->getComment()->pass( $commentedId, $commentId );
    }

    public function commentReject( $commentedId, $commentId ){
        return $this->getComment()->reject( $commentedId, $commentId );
    }

    public function replyPass ( $commentedId, $commentId, $replyId ) {
        return $this->getReply()->pass( $commentedId, $commentId, $replyId );
    }

    public function replyReject ( $commentedId, $commentId, $replyId ) {
        return $this->getReply()->reject( $commentedId, $commentId, $replyId );
    }
}