<?php
/*
 * タイムラインの取得
 */

// 初期設定ファイルの読み込み
require_once("ini.php");

// Botクラスの読み込み
require_once("bot_core.php");

// Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret, $access_token,
				 $access_token_secret);

if (DEGUB_MODE) { echo '$user=> ', $user, "\n"; }

// タイムラインの取得
// $mentions = $myBot->GetTimeline("home_timeline");
$mentions = $myBot->GetTimeline("mentions_timeline");

echo 'DEBUG_MODE:' . DEBUG_MODE . "<br>\n";

// タイムラインの出力
foreach($mentions as $Timeline) {

	// ユーザーのスクリーン名の出力
	$screen_name = $Timeline->user->screen_name;

	// if (DEBUG_MODE) { echo 'スクリーンネーム=> ', $screen_name, "\n"; }
	
	// ボット自身の発言、RT、QTに反応しないようにする
	if($screen_name == $user || preg_match("/(R|Q)T( |:)/", $text)){
		continue;
	}

 	if (DEBUG_MODE == 1) {
		print $screen_name . " > ";
		// 本文をSJISに変換して出力する（コマンドプロンプトでの確認用）
		Util::Debug_print($Timeline->text);
	} else {
		echo $screen_name . "> ";
		echo $Timeline->text . "<br>\n\n";
	}
}
?>
