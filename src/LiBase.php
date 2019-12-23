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

    private $originId;
    private $baseLastError;

    protected function __construct( $originId ){
        $this->originId = $originId;
    }

    //生成唯一ID
    protected function getUniqId(){
        return uniqid("",true);
    }

    //评论ID
    protected function getCommentId(){
        return $this->getUniqId();
    }

    //被评论物品
    protected function getCommentedId( $commentedId ){
        return md5 ( $commentedId );
    }

    //被评论物ID
    protected function getCommentedUser ( $commentedUser ){
        return md5 ( $commentedUser );
    }

    //评论者ID
    protected function getUserId ( $userId ) {
        return md5 ( $userId );
    }

    //评论是否针对某个用户
    protected function getTargetUser ( $targetUser ) {
        return md5 ( $targetUser );
    }

    //是否是评论
    protected function isComment( $commentInfo ){
        if ( $commentInfo["parent_id"] == "0" ) {
            return true;
        }else{
            $this->setBaseLastError( $commentInfo["comment_id"]." 数据错误,此id不是评论" );
            return false;
        }
    }

    //是否是评论
    protected function isReply( $commentInfo ){
        if ( $commentInfo["parent_id"] != "0" ) {
            return true;
        }else{
            $this->setBaseLastError( $commentInfo["comment_id"]." 数据错误,此id不是回复" );
            return false;
        }
    }

    //检查 originId
    protected function isOriginId ( $commentInfo ){
        if ( $commentInfo["origin_id"] == $this->originId ) {
            return true;
        }else{
            $this->setBaseLastError( "来源错误");
            return false;
        }
    }

    //检查 创建者
    protected function isOperatorUser ( $commentInfo, $actionUserId ){
        if ( $commentInfo["user_id"] == $actionUserId || $commentInfo["commented_user"] == $actionUserId ) {
            return true;
        }else{
            $this->setBaseLastError( $actionUserId." 不是评论创建者也不是被评论物所有者");
            return false;
        }
    }

    //对写入数据进行编码
    protected function dbDataEncode( $data ){
        $extArray = json_decode($data["expands"],true);
        $extArray["lc_ext_commented_id"] = $data["commented_id"];
        $extArray["lc_ext_user_id"] = $data["user_id"];
        $extArray["lc_ext_target_user"] = $data["target_user"];
        $extArray["lc_ext_commented_user"] = $data["commented_user"];

        $data["comment_id"] = $this->getCommentId();
        $data["commented_id"] = $this->getCommentedId($data["commented_id"]);
        $data["user_id"] = $this->getUserId($data["user_id"]);
        $data["target_user"] = $this->getTargetUser($data["target_user"]);
        $data["commented_user"] = $this->getCommentedUser($data["commented_user"]);
        $data["content"] = htmlentities($data["content"]);
        $data["createtime"] = time();
        $data["expands"] = json_encode($extArray);

        return $data;
    }

    //对写入数据进行解码
    protected function dbDataDecode( $data ){
        $extArray = json_decode($data["expands"],true);
        $data["commented_id"] = $extArray["lc_ext_commented_id"];
        $data["user_id"] = $extArray["lc_ext_user_id"];
        $data["target_user"] = $extArray["lc_ext_target_user"];
        $data["commented_user"] = $extArray["lc_ext_commented_user"];
        unset($extArray["lc_ext_commented_id"],$extArray["lc_ext_user_id"],$extArray["lc_ext_target_user"],$extArray["lc_ext_commented_user"]);
        $data["expands"] = json_encode($extArray);
        return $data;
    }

    //数据库表名
    protected function tableName( $tablePrefix, $commentedId ){
        $commentedId = $this->getCommentedId($commentedId);
        $tabNum = substr($commentedId,-1);
        echo $tablePrefix.$tabNum.":";
        //TODO
        return $tablePrefix;
    }

    //获取最后错误
    protected function getBaseLastError(){
        return $this->baseLastError;
    }

    protected function setBaseLastError( $lastError ){
        $this->baseLastError = $lastError;
    }
}