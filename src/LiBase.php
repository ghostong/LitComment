<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

use Lit\Drivers\LiMySQL;
use Lit\Drivers\LiRedis;

class LiBase {

    protected $originId;
    protected $commentRule;
    private $baseLastError;

    private $redisClient;
    private $mySqlClient;
    private $tablePrefix;
    private $redisKeyPrefix;

    private $commentObj;
    private $replyObj;
    private $listObj;
    private $configObj;
    private $ramObj;

    protected function __construct( $originId, $commentRule ){
        $this->originId = $originId;
        $this->commentRule = $commentRule;
    }

    //连接配置
    protected function getRedisClient () {
        return $this->redisClient;
    }

    public function setRedisClient( $redisClient ){
        $this->redisClient = $redisClient;
    }

    protected function getMySqlClient (){
        return $this->mySqlClient;
    }
    public function setMySqlClient( $mySqlClient ){
        $this->mySqlClient = $mySqlClient;
    }

    protected function getRedisKeyPrefix (){
        return $this->redisKeyPrefix;
    }

    public function setRedisKeyPrefix ($redisKeyPrefix){
        $this->redisKeyPrefix = $redisKeyPrefix;
    }

    protected function getMySqlTablePrefix () {
        return $this->tablePrefix;
    }
    public function setMySqlTablePrefix ($tablePrefix) {
        $this->tablePrefix = $tablePrefix;
    }

    //obj设置
    protected function getReply(){
        return $this->replyObj;
    }

    public function setReply($reply){
        $this->replyObj = $reply;
    }

    protected function getComment(){
        return $this->commentObj;
    }

    public function setComment($comment){
        $this->commentObj = $comment;
    }

    protected function getList(){
        return $this->listObj;
    }

    public function setList($list){
        $this->listObj = $list;
    }

    protected function getConfig(){
        return $this->configObj;
    }

    public function setConfig($config){
        $this->configObj = $config;
    }

    protected function getRam( $ram ){
        return $this->ramObj;
    }

    public function setRam( $ram ){
        $this->ramObj = $ram;
    }

    //生成唯一ID
    protected function getUniqId(){
        return uniqid("",true);
    }

    //评论ID
    protected function getCommentId(){
        return $this->getUniqId();
    }

    //被评论物品
    protected function getCommentedId( $commentedId ){
        return md5 ( $commentedId );
    }

    //被评论物ID
    protected function getCommentedUser ( $commentedUser ){
        return md5 ( $commentedUser );
    }

    //评论者ID
    protected function getUserId ( $userId ) {
        return md5 ( $userId );
    }

    //评论是否针对某个用户
    protected function getTargetUser ( $targetUser ) {
        return md5 ( $targetUser );
    }

    //是否是评论
    protected function isComment( $commentInfo ){
        if ( !empty($commentInfo) && isset($commentInfo["parent_id"]) && $commentInfo["parent_id"] == "0" ) {
            return true;
        }else{
            $this->setBaseLastError( "数据错误,此id不是评论" );
            return false;
        }
    }

    //是否是评论
    protected function isReply( $commentInfo ){
        if ( isset($commentInfo["parent_id"]) && $commentInfo["parent_id"] != "0" ) {
            return true;
        }else{
            $this->setBaseLastError( "数据错误,此id不是回复" );
            return false;
        }
    }

    //检查 originId
    protected function isOriginId ( $commentInfo ){
        if ( $commentInfo["origin_id"] == $this->originId ) {
            return true;
        }else{
            $this->setBaseLastError( "来源错误");
            return false;
        }
    }

    //检查 创建者
    protected function isOperatorUser ( $commentInfo, $actionUserId ){
        if ( $commentInfo["user_id"] == $actionUserId || $commentInfo["commented_user"] == $actionUserId ) {
            return true;
        }else{
            $this->setBaseLastError( $actionUserId." 不是评论创建者也不是被评论物所有者");
            return false;
        }
    }

    //检查 被评论物
    protected function isCommented( $commentInfo, $commentedId){
        if ( $commentInfo["commented_id"] == $commentedId ) {
            return true;
        }else{
            $this->setBaseLastError( "被评论物ID:".$commentInfo["commented_id"]."不等于传入的被评论物ID:".$commentedId);
            return false;
        }
    }

    //检查 是否所属评论ID
    protected function isParentId( $commentInfo, $commentId){
        if ( $commentInfo["parent_id"] == $commentId ) {
            return true;
        }else{
            $this->setBaseLastError( "评论ID:".$commentInfo["parent_id"]."不等于传入的评论ID:".$commentId);
            return false;
        }
    }

    protected function isShowRule( $status ){
        if ($this->commentRule == 1 && ($status == 0 || $status == 1)) { //先发后审 未审核或审核通过的展示
            return true;
        } elseif ($this->commentRule == 2 && $status == 1 ){ //先审后发 审核通过的展示
            return true;
        } elseif ($this->commentRule == 3 ){ //无需审核
            return true;
        } elseif ( $this->commentRule  == 4) { //无需展示
            return false;
        }else{
            return false;
        }
    }

    //对写入数据进行编码
    protected function dbDataEncode( $data ){
        $extArray = json_decode($data["expands"],true);
        $extArray["lc_ext_commented_id"] = $data["commented_id"];
        $extArray["lc_ext_user_id"] = $data["user_id"];
        $extArray["lc_ext_target_user"] = $data["target_user"];
        $extArray["lc_ext_commented_user"] = $data["commented_user"];

        $data["comment_id"] = $this->getCommentId();
        $data["commented_id"] = $this->getCommentedId($data["commented_id"]);
        $data["user_id"] = $this->getUserId($data["user_id"]);
        $data["target_user"] = $this->getTargetUser($data["target_user"]);
        $data["commented_user"] = $this->getCommentedUser($data["commented_user"]);
        $data["content"] = htmlentities($data["content"]);
        $data["createtime"] = time();
        $data["expands"] = json_encode($extArray);

        return $data;
    }

    //对写入数据进行解码
    protected function dbDataDecode( $data ){
        $extArray = json_decode($data["expands"],true);
        $data["commented_id"] = $extArray["lc_ext_commented_id"];
        $data["user_id"] = $extArray["lc_ext_user_id"];
        $data["target_user"] = $extArray["lc_ext_target_user"];
        $data["commented_user"] = $extArray["lc_ext_commented_user"];
        unset($extArray["lc_ext_commented_id"],$extArray["lc_ext_user_id"],$extArray["lc_ext_target_user"],$extArray["lc_ext_commented_user"]);
        $data["expands"] = json_encode($extArray);
        return $data;
    }

    //数据库表名
    protected function tableName( $tablePrefix, $commentedId ){
        $commentedId = $this->getCommentedId($commentedId);
        $tabNum = substr($commentedId,-1);
        echo $tablePrefix.$tabNum.": \n";
        //TODO
        return $tablePrefix;
    }

    //获取最后错误
    protected function getBaseLastError(){
        return $this->baseLastError;
    }

    protected function setBaseLastError( $lastError ){
        $this->baseLastError = $lastError;
    }

    //redis评分的一个算法
    protected function getRedisScore( $score, $maxInt, $i ){
        $score = floatval( $score.".".$i );
        $num = intval("1".str_repeat(0,strlen($maxInt))) ;
        return $score*$num;
    }
}