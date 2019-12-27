<?php
/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 17:23
 */

namespace Lit\Comment;

use Lit\Drivers\LiRedis;
use Lit\Drivers\LiMySQL;

class Init {

    //redis 客户端连接
    private $redisClient;
    //mysql 客户端连接
    private $mySqlClient;

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

    public function start( $originId, $token ){
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

    //评论
    public function comment () {
        if (! $this->serviceStart ){
            throw new \Exception("Error : must call start !", 0);

        }
        if (!is_object($this->commentObj)){
            $this->commentObj = new LiComment( $this->originId, $this->commentRule );
            $this->commentObj->setRedisClient( $this->getRedisClient() );
            $this->commentObj->setMySqlClient( $this->getMySqlClient() );
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
}