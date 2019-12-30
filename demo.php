<?php

/**
 * User: ghost
 * Date: 2019-12-21
 * Time: 16:11
 */

require_once ("./vendor/autoload.php");

//一些变量
$defCommentedId = "1000" ; //被评论物ID
$defCommentedUser = "1433";//被评论物所属用户
$defUserId = "9000";
$defContent = "不是评论就是回复".rand(1000,9999);
$defTargetUser = "8080";
$defExpands = json_encode(["bala"=>11223,"username"=>"备注名称","userlevel"=>"100"]);

//实例化化项目
$lComment = new \Lit\Comment\Init();

//增加权限(增加可被访问的来源)
//参数: 来源ID, token字符串, 来源名称, 评论审核规则( 1. 先发后审, 2. 先审后发, 3. 无需审核, 4. 无需展示 )
$lComment->ram()->add( 1,"OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB", "测试", 1);
$lComment->ram()->add( 2,"11111111232312321321312131231231", "测试2", 1);

//配置项目
//参数: redis用户名, redis端口, redis认证信息, redisDB
$lComment->config()->setRedisConfig("127.0.0.1", 6379, "123456", 0);

//参数: mysql用户名, mysql端口, mysql用户名, mysql密码, mysql库名, mysql字符集
$lComment->config()->setMySqlConfig("127.0.0.1", 3306, "comment", "123456", "comment", "utf8mb4");

//参数: 讯搜配置文件
$lComment->config()->setXunSearchConfig("./lcomment.ini");

//参数: mysql表名前缀, 用于分表
$lComment->config()->setMySqlTablePrefix("comment_");

//参数: rediskey前缀
$lComment->config()->setRedisKeyPrefix("lc");

//启动项目
//参数 要使用的来源ID, 要使用的token
$lComment->start( 1, "OZR3YpmEwd9r4l3igTNJGdnq2SEKZKhB" );

//打印建表语句
$lComment->createTableSql();

/**
 * 评论部分
 **/
$comment = $lComment->comment();

//增加一条评论
//参数: 被评论物品ID, 被评论物所属用户, 发起评论的用户ID, 评论内容, 扩展信息(json字符串)
$commentId = $comment->add( $defCommentedId,$defCommentedUser,$defUserId,$defContent,$defExpands);

//获取一条评论的信息
//参数: 被评论物品ID, 评论ID
$info = $comment->getCommentInfo( $defCommentedId, "5e069ee189c4a598" );

//删除一条评论
//参数: 被评论物品ID, 评论ID, 评论发起用户或者被评论物所属用户ID
$success = $comment->del($defCommentedId, "5e069f2e8d699737", $defCommentedUser);
$success = $comment->del($defCommentedId, "5e069f2e8d699737", $defUserId);

//设置评论点赞数量
//参数: 被评论物品ID, 评论ID, 点赞数设置
$comment->setLikeNum( $defCommentedId, "5e069f2e8d699737" , 10);

//删除所有评论
//参数: 被评论物品ID
$comment->delAll($defCommentedId);

//获取被评论物评论数量
//参数: 被评论物品ID
$comment->getCommentNum($defCommentedId);

//获取评论时产生的错误
$error = $comment->getLastError();


/**
 * 回复部分
 **/
$reply = $lComment->reply();

//增加一个回复
//参数: 被评论物品ID, 被评论物所属用户, 评论ID, 发起回复的用户ID, 评论内容, 扩展信息(json字符串)
$replyId = $reply->add( $defCommentedId,$defCommentedUser,"5e069ee189c4a598",$defUserId,$defTargetUser,$defContent,$defExpands );

//获取回复
//参数: 被评论物品ID, 评论ID, 回复ID
$info = $reply->getReplyInfo( $defCommentedId, "5e069ee189c4a598", "5e06a1da71bf0609" );

//删除回复
//参数: 被评论物品ID, 评论ID, 回复ID, 评论发起用户或者被评论物所属用户ID
$success = $reply->del( $defCommentedId, "5e069ee189c4a598", "5e06a1da71bf0609" ,$defUserId );

//删除评论回复
//参数: 被评论物品ID, 评论ID
$reply->delAll( $defCommentedId, "5e069ee189c4a598" );

//获取评论回复数量
//参数: 评论ID
$reply->getReplyNum( "5e069ee189c4a598" );

//获取回复时产生的错误
$error = $reply->getLastError();


/**
 * 各种列表
 **/
$list = $lComment->list();

//初始化评论列表  (此方法一般为自动触发,数据无异常时不要调用)
//参数: 被评物ID
$list->setCommentList( $defCommentedId );

//获取时间序列表
//参数: 被评物ID, 索引开始, 分页大小, 排序 asc|desc
$list->timeLineCommentList( $defCommentedId, 0, 4, "desc" );

//获取点赞数序列表
//参数: 被评物ID, 索引开始, 分页大小, 排序 asc|desc
$list->likeLineCommentList( $defCommentedId, 0, 4, "desc" );

//获取回复数序列表
//参数: 被评物ID, 索引开始, 分页大小, 排序 asc|desc
$list->replyLineCommentList( $defCommentedId, 0, 4, "desc" );

//初始化回复列表
//参数: 被评物的ID, 评论ID
$list->setReplyList( $defCommentedId, "5e069ee189c4a598");

//获取回复时间序列表
//参数: 被评物ID, 评论ID, 索引开始, 分页大小, 排序 asc|desc
$list->timeLineReplyList( $defCommentedId, "5e069ee189c4a598", 0, 5 , "asc");

/**
 * 运营部分
 **/
$opt = $lComment->opt();

//评论置顶
//参数: 被评物的ID, 评论ID, 置顶等级
$opt->setCommentTop($defCommentedId,"5e069ee189c4a598", 10);

//获取置顶评论
//参数: 被评物的ID
$opt->getTopComment($defCommentedId);

//取消置顶
//参数: 被评物的ID, 评论ID
$opt->delTopComment($defCommentedId,"5e069ee189c4a598");

//审核通过
//参数: 被评物的ID, 评论ID
$opt->commentPass( $defCommentedId,"5e069ee189c4a598" );

//审核拒绝
//参数: 被评物的ID, 评论ID
$opt->commentReject(  $defCommentedId,"5e069ee189c4a598" );

//回复审核通过
//参数: 被评物的ID, 评论ID, 回复ID
$opt->replyPass(  $defCommentedId,"5e069ee189c4a598","5e06a1da71bf0609" );

//回复审核拒绝
//参数: 被评物的ID, 评论ID, 回复ID
$opt->replyReject(  $defCommentedId,"5e069ee189c4a598","5e06a1da71bf0609" );

//重新构建索引 ( 建议每小时更新一次索引 )
$opt->buildIndex();

//运营后台查询列表
//参数: 字段过滤[origin_id,commented_id,parent_id,user_id,commented_user,status], 时间区间(十位时间戳), 排序字段[createtime,like_num,reply_num], 搜素关键词, 分页[从第几个开始,获取多少个]
$opt->optList( [ "origin_id"=> "1"], [ "1577440695","1577440704"],[ "like_num"=>"desc"], "关键词",[100,20] );