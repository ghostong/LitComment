<?php
/**
 * User: ghost
 * Date: 2019-12-23
 * Time: 14:34
 * Resource Access Management 访问控制
 */

namespace Lit\Comment;


class LiRAM{

    private $userDb = [
        1   =>  "OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB" //测试
    ];

    public function checkAccess ( $originId, $token ) {
        if ( $this->userDb[$originId] === $token ) {
            return true;
        }else{
            return false;
        }
    }
}