<?php

//形態素解析チェック

//morphemeクラスの読み込み
require_once("morpheme.php");

require_once("util.php");


$text = "今日は天気が良いです";

$m = new Yahoo_morph();
$xml = $m->Request($text);

//XML展開
foreach ($xml as $k => $v) {
	$txt = $v->surface." ".$v->reading." ".$v->pos;
	Util::debug_print($txt);
}


?>
