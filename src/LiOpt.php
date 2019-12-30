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

    /**
     * 置顶评论
     * @param $commentedId 被评论物ID
     * @param $commentId  评论ID
     * @param $topLevel 置顶等级
     * @return bool
     */
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

    /**
     * 获取所有置顶评论
     * @param $commentedId 被评论物ID
     * @return bool
     */
    public function getTopComment ( $commentedId ){
        $key = $this->getCommentTopKey($commentedId);
        $list = $this->getRedisClient()->zRevRangeByScore( $key, "+inf" , "-inf", [ "withscores" => true] );
        return $this->getComment()->getCommentInfos($commentedId,array_keys($list));
    }

    /**
     * 取消置顶评论
     * @param $commentedId 被评论物ID
     * @param $commentId  评论ID
     * @return bool
     */
    public function delTopComment ( $commentedId, $commentId ) {
        $key = $this->getCommentTopKey($commentedId);
        $this->getRedisClient()->zRem( $key, $commentId );
        return true;
    }

    /**
     * 评论审核通过
     * @param $commentedId 被评论物ID
     * @param $commentId  评论ID
     * @return array
     */
    public function commentPass( $commentedId, $commentId ){
        return $this->getComment()->pass( $commentedId, $commentId );
    }

    /**
     * 评论审核拒绝
     * @param $commentedId 被评论物ID
     * @param $commentId  评论ID
     * @return array
     */
    public function commentReject( $commentedId, $commentId ){
        return $this->getComment()->reject( $commentedId, $commentId );
    }

    /**
     * 回复审核通过
     * @param $commentedId 被评论物ID
     * @param $commentId  评论ID
     * @param $replyId  回复ID
     * @return array
     */
    public function replyPass ( $commentedId, $commentId, $replyId ) {
        return $this->getReply()->pass( $commentedId, $commentId, $replyId );
    }

    /**
     * 回复审核拒绝
     * @param $commentedId 被评论物ID
     * @param $commentId  评论ID
     * @param $replyId  回复ID
     * @return array
     */
    public function replyReject ( $commentedId, $commentId, $replyId ) {
        return $this->getReply()->reject( $commentedId, $commentId, $replyId );
    }

    /**
     * 列表搜索条件
     * @param array $filter 过滤条件 ["comment_id"=>1,"origin_id"=>2]
     * @param array $time 时间区间 ["1577440695","1577450695"]  开始时间,结束时间
     * @param array $sort 排序条件 ["createtime"=>"desc","like_num"=>"asc"]
     * @param string $content 评论内容
     * @param array $limit 输出显示  [15,5] 跳过15条获取5条
     * @return array
     */
    public function optList ( $filter = array(), $time = array() ,$sort = array() ,$content = "", $limit = array() ) {
        $xs = $this->getXunSearchClient();
        $xs->index->flushIndex();
        if ( isset($filter["comment_id"]) ) { //主键搜索
            $xs->search->setQuery('comment_id:'.$filter["comment_id"]);
            $xs->search->setLimit(1);
        }else{
            //过滤
            $query = [];
            if (isset($filter["origin_id"])) {
                $query[] = "origin_id:".$filter["origin_id"];
            }
            if (isset($filter["commented_id"])) {
                $query[] = "commented_id:".$filter["commented_id"];
            }
            if (isset($filter["parent_id"])) {
                $query[] = "parent_id:".$filter["parent_id"];
            }
            if (isset($filter["user_id"])) {
                $query[] = "user_id:".$filter["user_id"];
            }
            if (isset($filter["commented_user"])) {
                $query[] = "commented_user:".$filter["commented_user"];
            }
            if (isset($filter["status"])) {
                $query[] = "status:".$filter["status"];
            }
            //content
            if($content) {
                $query[] = $content;
            }
            if (!empty($query)) {
                $queryStr = implode(" ",$query);
                $xs->search->setQuery($queryStr);
            }
            //时间区间
            if (!empty($time) && count($time) == 2) {
                sort ($time);
                $xs->search->addRange( "createtime", $time[0], $time[1] );
            }
            //排序
            if(!empty($sort)){
                $sorts = [];
                foreach ( $sort as $field => $order) {
                    if ($order == "desc") {
                        $sorts[$field] = false;
                    }else{
                        $sorts[$field] = true;
                    }
                }
                $xs->search->setMultiSort($sorts);
            }
            //展示限制
            if (!empty($limit) && count($limit) == 2) {
                $xs->search->setLimit($limit[1],$limit[0]);
            }
        }
        $doc = $xs->search->search();
        $count = $xs->search->count();
        if ($doc) {
            $ret = [];
            foreach ($doc as $val) {
                $ret[] = $this->dbDataDecode($this->opGetInfo($val->comment_id));
            }
            return ["list"=>$ret,"count"=>$count];
        }else{
            return [];
        }
    }

    /**
     * 构建搜索引擎索引
     */
    public function buildIndex(){
        $xs = $this->getXunSearchClient();
        $xs->index->stopRebuild();
        $xs->index->beginRebuild();
        $sql = "select * from ".$this->mergeTableName();
        $res = $this->getMySqlClient()->query($sql);
        foreach ($res as $val) {
            $doc = new \XSDocument;
            $doc->setFields($val);
            $xs->index->add($doc);
        }
        $result = $xs->index->endRebuild();
        return $result;
    }

    /**
     * 获取评论信息
     * @param $commentId
     * @return array
     */
    private function opGetInfo( $commentId ){
        $commentInfo = $this->getMySqlClient()->GetOne( $this->mergeTableName(), 'comment_id = ?', $commentId) ;
        return $commentInfo;
    }
}