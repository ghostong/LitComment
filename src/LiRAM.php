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
        if (!$this->isAllowOrigin($originId)) {
            throw new \Exception("Error : originId ". $originId." !", 0);
            return false;
        }
        if ( $this->getToken( $originId ) === $token && !empty($token) ) {
            return true;
        }else{
            throw new \Exception("Error : Access denied !", 0);
            return false;
        }
    }

    /**
     * @param $originId 增加来源 不能重复
     * @param $token 身份认证串 长度不小于32位
     * @param $name 来源别名
     * @param $rule 审核规则
     * @return bool
     * @throws \Exception
     */
    public function add( $originId, $token, $name, $rule){
        if( isset($this->db[$originId]) ){ //ID重复
            throw new \Exception("Error : duplicate originId ". $originId." !", 0);

        }
        if( isset($this->db[$originId]["name"] ) && $this->db[$originId]["name"] == $name) { //名字重复
            throw new \Exception("Error : duplicate name ". $name." !", 0);

        }
        if ( strlen( $token ) < 32 ) {
            throw new \Exception("Error : token length mast greater than or equal 32 !", 0);
        }
        $config = new LiConfig();
        if (! $config->isAllowRule($rule) ) {
            throw new \Exception("Error : rule error !", 0 );
        }
        $this->db[$originId] = [
            "originId" => $originId,
            "token" => $token,
            "name" => $name,
            "rule" => $rule
        ];
        return true;
    }

    private function getToken( $originId){
        if(isset($this->db[$originId])) {
            return $this->db[$originId]["token"];
        }else{
            return "";
        }
    }

    public function getInfo( $originId ){
        if(isset($this->db[$originId])) {
            $info = $this->db[$originId];
            $info["token"] = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
            return $info;
        }else{
            return [];
        }
    }

    public function getRule( $originId ){
        $info = $this->getInfo( $originId );
        $config = new LiConfig();
        if (! $config->isAllowRule( $info["rule"] ) ) {
            throw new \Exception("Error : rule error !", 0 );
        }else{
            return $info["rule"];
        }
    }

    public function isAllowOrigin( $originId ){
        if ( in_array( $originId,array_keys($this->db) ) ) {
            return true;
        }else{
            return false;
        }
    }
}