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

    //xunSearch 配置
    private $xunSearchConfigFile="";


    //redis 评论key前缀
    private $redisKeyPrefix = "lc";
    //redis 评论数据库表前缀
    private $tablePrefix = "lc";

    public function isAllowRule ( $rule ){
        $ruleAll = $this->getRuleAll();
        if (! in_array( $rule,array_keys($ruleAll)) ) {
            return false;
        }else{
            return true;
        }
    }
    public function getRuleAll(){
        return [
            1 => "先发后审",  //评论先发布展示, 再审核
            2 => "先审后发",  //评论必须审核后, 再展示
            3 => "无需审核",  //忽略审核状态, 无论如何都展示
            4 => "无需展示"   //忽略审核状态, 无论如何都不展示
        ];
    }

    /**
     * @return string
     */
    public function getRedisHost(){
        return $this->redisHost;
    }

    /**
     * @return string
     */
    public function getRedisPort(){
        return $this->redisPort;
    }

    /**
     * @return string
     */
    public function getRedisAuth(){
        return $this->redisAuth;
    }

    /**
     * @return string
     */
    public function getRedisDb(){
        return $this->redisDb;
    }

    /**
     * @return string
     */
    public function getMySqlHost(){
        return $this->mySqlHost;
    }

    /**
     * @return string
     */
    public function getMySqlPort(){
        return $this->mySqlPort;
    }

    /**
     * @return string
     */
    public function getMySqlUserName(){
        return $this->mySqlUserName;
    }

    /**
     * @return string
     */
    public function getMySqlPassWord(){
        return $this->mySqlPassWord;
    }

    /**
     * @return string
     */
    public function getMySqlDbName(){
        return $this->mySqlDbName;
    }

    /**
     * @return string
     */
    public function getMySqlCharSet(){
        return $this->mySqlCharSet;
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

    /**
     * @return string
     */
    public function getXunSearchConfig ( ) {
        return $this->xunSearchConfigFile;
    }

    // xunSearch 配置文件
    public function setXunSearchConfig ( $configFile ) {
        $this->xunSearchConfigFile = $configFile;
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