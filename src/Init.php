<?php
/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 17:23
 */

namespace Lit\Comment;

use Lit\Drivers\LiRedis;
use Lit\Drivers\LiMySQL;
use function Sodium\randombytes_uniform;

class Init {

    //redis 客户端连接
    private $redisClient;
    //mysql 客户端连接
    private $mySqlClient;
    //xunSearch 客户端连接
    private $xunSearchClient;

    //来源ID
    private $originId;
    private $commentRule;
    private $serviceStart = false;

    //各对象
    private $commentObj;
    private $replyObj;
    private $listObj;
    private $configObj;
    private $ramObj;
    private $optObj;


    /**
     * @param $originId 要使用的来源
     * @param $token 要使用的token
     * @throws \Exception
     */
    public function start($originId, $token ){
        $this->ram()->checkAccess($originId,$token ) ;
        $this->originId = $originId;
        $this->commentRule = $this->ram()->getRule($this->originId);
        $this->serviceStart = true;
    }

    // 通过配置生成一个 redis 连接
    private function setRedisClient(){
        $this->redisClient = new LiRedis(
            $this->config()->getRedisHost(),
            $this->config()->getRedisPort(),
            $this->config()->getRedisAuth(),
            $this->config()->getRedisDb()
        );
        return $this;
    }

    // 通过配置生成一个 mysql 连接
    private function setMySqlClient(){
        $this->mySqlClient = new LiMySQL(
            $this->config()->getMySqlHost(),
            $this->config()->getMySqlPort(),
            $this->config()->getMySqlUserName(),
            $this->config()->getMySqlPassWord(),
            $this->config()->getMySqlDbName(),
            $this->config()->getMySqlCharSet()
        );
        return $this;
    }

    // 通过配置生成一个 xunSearch 连接
    private function setXunSearchClient () {
        $this->xunSearchClient = $xs = new \XS(
            $this->config()->getXunSearchConfig()
        );
        return $this;
    }

    // redis 连接
    private function getRedisClient(){
        if ( ! $this->redisClient ) {
             $this->setRedisClient();
        }
        return $this->redisClient;
    }

    // mysql 连接
    private function getMySqlClient(){
        if ( ! $this->mySqlClient ) {
            $this->setMySqlClient();
        }
        return $this->mySqlClient;
    }

    // xunsearch 连接
    private function getXunSearchClient( ){
        if ( ! $this->xunSearchClient ) {
            $this->setXunSearchClient();
        }
        return $this->xunSearchClient;
    }

    //评论
    public function comment () {
        if (! $this->serviceStart ){
            throw new \Exception("Error : must call start !", 0);

        }
        if (!is_object($this->commentObj)){
            $this->commentObj = new LiComment( $this->originId, $this->commentRule );
            $this->commentObj->setRedisClient( $this->getRedisClient() );
            $this->commentObj->setMySqlClient( $this->getMySqlClient() );
            $this->commentObj->setXunSearchClient( $this->getXunSearchClient() );
            $this->commentObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->commentObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->commentObj->setReply( $this->reply() );
            $this->commentObj->setList( $this->list() );
            $this->commentObj->setConfig($this->config());
        }
        return $this->commentObj;
    }

    //回复
    public function reply () {
        if (! $this->serviceStart ){
            throw new \Exception("Error : must call start !", 0);

        }
        if (!is_object($this->replyObj)) {
            $this->replyObj = new LiReply( $this->originId, $this->commentRule );
            $this->replyObj->setRedisClient( $this->getRedisClient() );
            $this->replyObj->setMySqlClient( $this->getMySqlClient() );
            $this->replyObj->setXunSearchClient( $this->getXunSearchClient() );
            $this->replyObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->replyObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->replyObj->setComment( $this->comment() );
            $this->replyObj->setList( $this->list() );
            $this->replyObj->setConfig($this->config());
        }
        return $this->replyObj;
    }

    //列表
    public function list () {
        if (! $this->serviceStart ){
            throw new \Exception("Error : must call start !", 0);

        }
        if (!is_object($this->listObj)) {
            $this->listObj = new LiList( $this->originId, $this->commentRule );
            $this->listObj->setRedisClient( $this->getRedisClient() );
            $this->listObj->setMySqlClient( $this->getMySqlClient() );
            $this->listObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->listObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->listObj->setReply( $this->reply() );
            $this->listObj->setComment( $this->comment() );
            $this->listObj->setConfig($this->config());
        }
        return $this->listObj;
    }

    //运营
    public function opt(){
        if (! $this->serviceStart ){
            throw new \Exception("Error : must call start !", 0);
        }
        if (!is_object($this->optObj)) {
            $this->optObj = new LiOpt( $this->originId, $this->commentRule );
            $this->optObj->setRedisClient( $this->getRedisClient() );
            $this->optObj->setMySqlClient( $this->getMySqlClient() );
            $this->optObj->setXunSearchClient( $this->getXunSearchClient() );
            $this->optObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->optObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->optObj->setReply( $this->reply() );
            $this->optObj->setComment( $this->comment() );
            $this->optObj->setList( $this->list() );
            $this->optObj->setConfig($this->config());
        }
        return $this->optObj;
    }

    //访问控制
    public function ram(){
        if (!is_object($this->ramObj)) {
            $this->ramObj = new LiRAM( );
        }
        return $this->ramObj;
    }

    //配置
    public function config(){
        if (!is_object($this->configObj)) {
            $this->configObj = new LiConfig();
        }
        return $this->configObj;
    }

    //创建表语句
    public function createTableSql() {
        $sql = "
        CREATE TABLE `%s` (
         `comment_id` char(16) NOT NULL COMMENT '主键ID,评论ID',
         `origin_id` int(11) NOT NULL COMMENT '来源ID',
         `commented_id` char(32) NOT NULL COMMENT '被评论物ID',
         `parent_id` char(23) NOT NULL COMMENT '父级ID,用于区别评论还是回复',
         `user_id` char(32) NOT NULL COMMENT '评论创建者用户ID',
         `target_user` char(32) NOT NULL COMMENT '评论是否针对某人',
         `like_num` int(11) NOT NULL DEFAULT '0' COMMENT '喜欢数',
         `reply_num` int(11) NOT NULL DEFAULT '0' COMMENT '回复数量',
         `commented_user` char(32) NOT NULL COMMENT '被评论物的作者',
         `content` varchar(4000) NOT NULL COMMENT '评论内容',
         `createtime` int(11) NOT NULL COMMENT '创建时间',
         `expands` varchar(4000) NOT NULL COMMENT '扩展信息',
         `status` enum('1','0','-1') NOT NULL DEFAULT '0' COMMENT '审核状态 0 未审核,1 通过,-1 拒绝',
         PRIMARY KEY (`comment_id`),
         KEY `origin_id` (`origin_id`,`commented_id`,`parent_id`)
        ) %s ;
        ";

        $tableArray = [];
        for ($i = 0; $i < 16 ; $i ++) {
            $tabExt =  $this->config()->getMySqlTablePrefix().base_convert($i,10,16);
            $tableArray[]=$tabExt;
            echo sprintf($sql,$tabExt,"ENGINE=MyISAM DEFAULT CHARSET=utf8mb4"),"\n";
        }

        $tableAll = implode(",",$tableArray);
        echo sprintf($sql,$this->config()->getMySqlTablePrefix()."merge","ENGINE = MERGE UNION = ($tableAll)");

    }
}