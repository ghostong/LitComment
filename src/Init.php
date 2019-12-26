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

    //各对象
    private $commentObj;
    private $replyObj;
    private $listObj;
    private $configObj;
    private $ramObj;

    public function start( $originId, $token ){
        $this->originId = $originId;
        if ( ! $this->ram()->checkAccess($originId,$token ) ) {
            throw new \Exception("Access Denied !", 0);
        }
        var_dump ( $this->ram()->getInfo( $originId ) );
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
        if (!is_object($this->commentObj)){
            $this->commentObj = new LiComment( $this->originId );
            $this->commentObj->setRedisClient( $this->getRedisClient() );
            $this->commentObj->setMySqlClient( $this->getMySqlClient() );
            $this->commentObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->commentObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->commentObj->setReply( $this->reply() );
            $this->commentObj->setList( $this->list() );
        }
        return $this->commentObj;
    }

    //回复
    public function reply () {
        if (!is_object($this->replyObj)) {
            $this->replyObj = new LiReply( $this->originId );
            $this->replyObj->setRedisClient( $this->getRedisClient() );
            $this->replyObj->setMySqlClient( $this->getMySqlClient() );
            $this->replyObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->replyObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->replyObj->setComment( $this->comment() );
            $this->replyObj->setList( $this->list() );
        }
        return $this->replyObj;
    }

    //列表
    public function list () {
        if (!is_object($this->listObj)) {
            $this->listObj = new LiList( $this->originId );
            $this->listObj->setRedisClient( $this->getRedisClient() );
            $this->listObj->setMySqlClient( $this->getMySqlClient() );
            $this->listObj->setRedisKeyPrefix( $this->config()->getRedisKeyPrefix() );
            $this->listObj->setMySqlTablePrefix( $this->config()->getMySqlTablePrefix() );
            $this->listObj->setReply( $this->reply() );
            $this->listObj->setComment( $this->comment() );
        }
        return $this->listObj;
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
            $this->configObj = new LiConfig( $this->originId );
        }
        return $this->configObj;
    }

}