<?php
/**
 * User: ghost
 * Date: 2019-12-23
 * Time: 14:34
 * Resource Access Management 访问控制
 */

namespace Lit\Comment;


class LiRAM  {

    private $db = [];


    public function checkAccess ( $originId, $token ) {
        if ( $this->getToken( $originId ) === $token && !empty($token) ) {
            return true;
        }else{
            return false;
        }
    }

    public function add( $originId, $token, $name, $rule){
        if( isset($this->db[$originId]) ){ //ID重复
            return false;
        }
        if( isset($this->db[$originId]["name"] ) && $this->db[$originId]["name"] == $name) { //名字重复
            return false;
        }
        $this->db[$originId] = [
            "originId" => $originId,
            "token" => $token,
            "name" => $name,
            "rule" => $rule
        ];
    }

    private function getToken( $originId){
        if(isset($this->db[$originId])) {
            return $this->db[$originId]["token"];
        }else{
            return "";
        }
    }

    public function getInfo( $originId){
        if(isset($this->db[$originId])) {
            $info = $this->db[$originId];
            $info["token"] = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
            return $info;
        }else{
            return [];
        }
    }
}