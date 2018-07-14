<?php
/*
 * ボット宛リプライの取得
 */

// 初期設定ファイルの読み込み
require_once("ini.php");

// Botクラスの読み込み
require_once("bot_core.php");

// Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret, $access_token,
				 $access_token_secret);

// ボット宛のリプライを取得する
$mentions = $myBot->GetTimeline("mentions_timeline");

// ボット宛のリプライ内容の出力
foreach($mentions as $reply) {

	// ユーザーのスクリーン名の出力
	$screen_name = $reply->user->screen_name;
	
	if (DEBUG_MODE == 1) {
		echo "----------------------------------------------------\n";
		print $screen_name . " > ";
		// 本文をSJISに変換して出力する（コマンドプロンプトでの確認用）
		Util::Debug_print($reply->text);
	} else {
		echo $screen_name . "> ";
		echo $reply->text . "<br>\n\n";
	}
}
?>
