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

    public function optList ( $fields = array() ,$limit = array(), $time = array() ) {
        $xs = $this->getXunSearchClient();
        if ( isset($fields["comment_id"]) ) { //主键搜索
            $xs->search->setQuery('comment_id:'.$fields["comment_id"]);
            $xs->search->setLimit(1);
        }else{
            $query = [];
            if (isset($fields["origin_id"])) {
                $query[] = "origin_id:".$fields["origin_id"];
            }
            if (isset($fields["commented_id"])) {
                $query[] = "commented_id:".$fields["commented_id"];
            }
            if (isset($fields["parent_id"])) {
                $query[] = "parent_id:".$fields["parent_id"];
            }
            if (isset($fields["user_id"])) {
                $query[] = "user_id:".$fields["user_id"];
            }
            if (isset($fields["commented_user"])) {
                $query[] = "commented_user:".$fields["commented_user"];
            }
            if (isset($fields["status"])) {
                $query[] = "status:".$fields["status"];
            }
            if (empty($query)) {
                $queryStr = implode(" ",$query);
                $xs->search->setQuery($queryStr);
            }
            if (!empty($time) && count($time) == 2) {
                sort ($time);
                $xs->search->addRange( "createtime", $time[0] - 1, $time[1] );
            }
            if (!empty($limit) && count($limit) == 2) {
                $xs->search->setLimit($limit[1],$limit[0]);
            }
        }

        $doc = $xs->search->search();
        return $doc;
    }
}