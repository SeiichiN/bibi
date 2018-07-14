<?php
/*
 * ユーティリティ
 */

class Util {

	// 文字コードをSJISに変換して出力するメソッド
	static function Debug_print($text){
		print mb_convert_encoding($text, "UTF-8", "auto") . "\n"; // for Linux
//		print mb_convert_encoding($text, "SJIS", "auto") . "\n";  // for Windows
	}
}
?>
