<?php
/**
 * Created by IntelliJ IDEA.
 * User: ghost
 * Date: 2019-12-21
 * Time: 17:23
 */

namespace Lit\Comment;

use Lit\Drivers\LiRedis;
use Lit\Drivers\LiMySQL;

class Main {

    private $redisClient;
    private $mySqlClient;
    private $redisKeyPrefix;
    private $tablePrefix;

    function __construct( $redisConfig, $mySqlConfig, $redisKeyPrefix, $tablePrefix ){

        $this->redisClient = new LiRedis(
            $redisConfig["host"],
            $redisConfig["port"],
            $redisConfig["auth"],
            $redisConfig["db"]
        );

        $this->mySqlClient = new LiMySQL(
            $mySqlConfig["host"],
            $mySqlConfig["port"],
            $mySqlConfig["username"],
            $mySqlConfig["password"],
            $mySqlConfig["dbname"],
            $mySqlConfig["charset"]
        );

        $this->redisKeyPrefix = $redisKeyPrefix;

        $this->tablePrefix = $tablePrefix;
    }

    //评论
    public function comment () {
        $comment = new LiComment( $this->redisClient, $this->mySqlClient,  $this->redisKeyPrefix, $this->tablePrefix );
        return $comment;
    }

    //回复
//    public function reply () {
//        $reply = new LiReply( $this->redisClient, $this->mySqlClient,  $this->redisKeyPrefix, $this->tablePrefix );
//        return $reply;
//    }

}