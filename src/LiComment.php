<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:13
 */

namespace Lit\Comment;

class LiComment extends LiBase {
    private $redisClient;
    private $mySqlClient;
    private $tablePrefix;
    private $redisKeyPrefix;
    function  __construct( $redisClient, $mySqlClient,  $redisKeyPrefix, $tablePrefix ){
        $this->redisClient = $redisClient;
        $this->mySqlClient = $mySqlClient;
        $this->redisKeyPrefix = $redisKeyPrefix;
        $this->tablePrefix = $tablePrefix;
    }

    function add ( $from, $commentedId, $userId, $content, $ext ){
        $tableName = $this->tableName($this->tablePrefix, $commentedId);
        $data = [
            "from" => $from,
            "commented_id" => $commentedId,
            "parent_id" => 0,
            "user_id" => $userId,
            "target_id" => 0,
            "content" => $content,
            "ext" => $ext,
            "createtime" => time(),
        ];

        $this->mySqlClient->Add( $tableName, $data );

        var_dump (  $this->mySqlClient->LastError() );

        var_dump (  $this->mySqlClient->LastSql() );

        var_dump ( $this->mySqlClient->LastInsertId() );
    }

    function del (){

    }

    function top (){

    }


}