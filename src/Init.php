<?php
/**
 * Created by IntelliJ IDEA.
 * User: ghost
 * Date: 2019-12-21
 * Time: 17:23
 */

namespace Lit\Comment;

use http\Exception;
use Lit\Drivers\LiRedis;
use Lit\Drivers\LiMySQL;
use MongoDB\BSON\ObjectId;

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

    private $originId = "";

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

    // 设置一个 redis 连接或者通过配置生成一个 redis 连接
    public function setRedisClient( $redisClient = false ){
        if ( $redisClient == false ) {
            $this->redisClient = new LiRedis( $this->redisHost, $this->redisPort, $this->redisAuth, $this->redisDb );
        }else{
            $this->redisClient = $redisClient;
        }
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

    // 设置一个 mysql 连接或者通过配置生成一个 mysql 连接
    public function setMySqlClient(  $mySqlClient = false ){
        if ( $mySqlClient == false ) {
            $this->mySqlClient = new LiMySQL( $this->mySqlHost, $this->mySqlPort, $this->mySqlUserName, $this->mySqlPassWord, $this->mySqlDbName, $this->mySqlCharSet );
        }else{
            $this->mySqlClient = $mySqlClient;
        }
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
            $this->commentObj = new LiComment( $this->originId, $this->getRedisClient(), $this->getMySqlClient(),  $this->getRedisKeyPrefix(), $this->getMySqlTablePrefix() );
        }
        return $this->commentObj;
    }

    //回复
    public function reply () {
        if (!is_object($this->replyObj)) {
            $this->replyObj = new LiReply( $this->originId, $this->getRedisClient(), $this->getMySqlClient(), $this->getRedisKeyPrefix(), $this->getMySqlTablePrefix());
        }
        return $this->replyObj;
    }

    //列表
    public function list () {
        if (!is_object($this->listObj)) {
            $this->listObj = new LiList( $this->originId, $this->getRedisClient(), $this->getMySqlClient(), $this->getRedisKeyPrefix(), $this->getMySqlTablePrefix());
        }
        return $this->listObj;
    }

}