<?php
/**
 * User: ghost
 * Date: 2019-12-26
 * Time: 17:47
 */

namespace Lit\Comment;


class LiConfig {

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

    //redis 评论key前缀
    private $redisKeyPrefix = "lc";
    //redis 评论数据库表前缀
    private $tablePrefix = "lc";

    public function __construct( $originId ){
        $this->originId = $originId;
    }

    // redis 设置
    public function setRedisConfig( $host = "127.0.0.1", $port = 6479, $auth = "", $db = 0 ){
        $this->redisHost = $host;
        $this->redisPort = $port;
        $this->redisAuth = $auth;
        $this->redisDb   = $db;
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

    // 获取 Redis表前缀
    public function getRedisKeyPrefix(){
        return $this->redisKeyPrefix;
    }

    // 获取 MySQL表前缀
    public function getMySqlTablePrefix(){
        return $this->tablePrefix;
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


}