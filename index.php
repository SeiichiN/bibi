<?php
/*
 * ひと言だけツイートする
 */

/* 初期設定ファイルの読み込み */
require_once("ini.php");

// Botクラスの読み込み
require_once("bot_core.php");

// Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret,
				 $access_token, $access_token_secret);

// 送信する文字列を設定する
// $txt = "こんにちは";

// 送信する文字列を取得する
$text = $myBot->Speaks($txt);

// エラーレベルを変更
$level = error_reporting(error_reporting() & ~E_STRICT & ~E_DEPRECATED);

// コマンドプロンプトでの出力確認用
if (DEBUG_MODE) { Util::Debug_print($text); }

// エラーレベルをもとに戻す
error_reporting($level);

// ツイートを送信する
if($text){$myBot->Post($text);}


