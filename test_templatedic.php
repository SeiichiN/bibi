<?php
// 発言を形態素解析してテンプレート辞書を作成する

//応答チェック

//初期設定ファイルの読み込み
require_once("ini.php");

//Botクラスの読み込み
require_once("bot_core.php");

require_once("mylib.php");


//Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret, $access_token, $access_token_secret);



set_time_limit(0);
($stdin = fopen("php://stdin", "r")) || die("Cannot open stdin.");

while (1) {

	print(">");
 	$input = trim(fgets($stdin, 256));

	if($input == "exit") {
		$myBot->Save();	//辞書を保存
		break;
	}

	$text = mb_convert_encoding($input, "UTF-8", "auto");

	//送信する文字列の取得
	$txt = $myBot->Conversation($text);

	$txt = "Bot(".$myBot->ResponderName()."-機嫌値[".$myBot->emotion->mood."])>".$txt;
	Util::Debug_print($txt);

}


?>
