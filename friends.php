<?php
/*
 * 発言（ツイート、リプライ）をオウム返しする
 */

/* 初期設定ファイルの読み込み */
require_once("ini.php");

// ログ出力設定
require_once("mylib.php");

// Botクラスの読み込み
require_once("bot_core.php");

// Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret,
				 $access_token, $access_token_secret);


$followList = $myBot->Friends($user);

echo "----- フォローしているユーザー -----\n";

foreach($followList->users as $list) {
	// print_r($list);

	echo $list->name, "\n";
}


