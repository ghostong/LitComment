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
    private $redisClient = null;
    //mysql 客户端连接
    private $mySqlClient = null;

    //redis 评论key前缀
    private $redisKeyPrefix = "lc";
    //redis 评论数据库表前缀
    private $tablePrefix = "lc";

    //redis 配置
    private $redisHost = "";
    private $redisPort = "";
    private $redisAuth = "";
    private $redisDb = "";

    //mysql 配置
    private $mySqlHost = "";
    private $mySqlPort = "";
    private $mySqlUserName = "";
    private $mySqlPassWord = "";
    private $mySqlDbName = "";
    private $mySqlCharSet = "";

    //来源ID
    private $originId = "";

    //各对象
    private $commentObj = "";
    private $replyObj = "";
    private $listObj = "";


    function __construct( $originId, $token ){
        $liRam = new LiRAM();
        if ( ! $liRam->checkAccess( $originId, $token ) ) {
            throw new \Exception("Access Denied !", 0);
        }else{
            $this->originId = $originId;
        }
    }

    // redis 设置
    public function setRedisConfig( $host = "127.0.0.1", $port = 6479, $auth = "", $db = 0 ){
        $this->redisHost = $host;
        $this->redisPort = $port;
        $this->redisAuth = $auth;
        $this->redisDb   = $db;
        return $this;
    }

    // 通过配置生成一个 redis 连接
    public function setRedisClient(){
        $this->redisClient = new LiRedis( $this->redisHost, $this->redisPort, $this->redisAuth, $this->redisDb );
        return $this;
    }

    // mysql 设置
    public function setMySqlConfig( $host = "127.0.0.1", $port = 3306, $userName = "", $passWord = "", $dbName = "", $charSet = "" ){
        $this->mySqlHost = $host;
        $this->mySqlPort = $port;
        $this->mySqlUserName = $userName;
        $this->mySqlPassWord = $passWord;
        $this->mySqlDbName   = $dbName;
        $this->mySqlCharSet  = $charSet;
        return $this;
    }

    // 通过配置生成一个 mysql 连接
    public function setMySqlClient(){
        $this->mySqlClient = new LiMySQL( $this->mySqlHost, $this->mySqlPort, $this->mySqlUserName, $this->mySqlPassWord, $this->mySqlDbName, $this->mySqlCharSet );
        return $this;
    }

    // 设置 Redis表前缀
    public function setRedisKeyPrefix( $redisKeyPrefix = ""){
        $this->redisKeyPrefix = $redisKeyPrefix;
        return $this;
    }

    // 设置 MySQL表前缀
    public function setMySqlTablePrefix( $tablePrefix = ""){
        $this->tablePrefix = $tablePrefix;
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

    // 获取 Redis表前缀
    public function getRedisKeyPrefix(){
        return $this->redisKeyPrefix;
    }

    // 获取 MySQL表前缀
    public function getMySqlTablePrefix(){
        return $this->tablePrefix;
    }

    //评论
    public function comment () {
        if (!is_object($this->commentObj)){
            $this->commentObj = new LiComment( $this->originId );
            $this->commentObj->setRedisClient( $this->getRedisClient() );
            $this->commentObj->setMySqlClient( $this->getMySqlClient() );
            $this->commentObj->setRedisKeyPrefix( $this->getRedisKeyPrefix() );
            $this->commentObj->setMySqlTablePrefix( $this->getMySqlTablePrefix() );
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
            $this->replyObj->setRedisKeyPrefix( $this->getRedisKeyPrefix() );
            $this->replyObj->setMySqlTablePrefix( $this->getMySqlTablePrefix() );
            $this->replyObj->setComment( $this->comment() );
            $this->replyObj->setList( $this->list() );
        }
        return $this->replyObj;
    }

    //列表
    public function list () {
        if (!is_object($this->listObj)) {
            $this->listObj = new LiList( $this->originId, $this->comment(), $this->reply() );
            $this->listObj->setRedisClient( $this->getRedisClient() );
            $this->listObj->setMySqlClient( $this->getMySqlClient() );
            $this->listObj->setRedisKeyPrefix( $this->getRedisKeyPrefix() );
            $this->listObj->setMySqlTablePrefix( $this->getMySqlTablePrefix() );
            $this->listObj->setReply( $this->reply() );
            $this->listObj->setComment( $this->comment() );
        }
        return $this->listObj;
    }

}