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

    //数据库表名
    protected function tableName( $tablePrefix, $commentedId ){
        $tabNum = substr(md5($commentedId),-1);
        return $tablePrefix;
        return $tablePrefix.$tabNum;
    }

    //redisKey
    protected function newestKey ( $redisKeyPrefix, $commentedId ) {
        $key = "";
    }
}