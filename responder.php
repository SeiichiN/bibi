<?php
/*
 * 応答クラス
 */

/* Responderクラスの定義 */
class Responder {
	/* メンバ変数 */
	// オブジェクト名を格納する変数
	var $name;

	/* コンストラクタ（初期化用メソッド） */
	function __construct($name, $dictionary) {
		$this->name = $name;
		$this->dictionary = $dictionary;
	}

	/* 受け取った文字列をそのまま返すメソッド
	   PHP5.4以上では、メソッドのオーバーライドにおいて
	   引数が一致しないとエラーが出る。
	   TimeResponder の Responseメソッドである。
	   こちらのメソッドの名前を変えておいた
	   by Seiichi Nukayama 2017.02.11 */
//	function MirrorResponse($text) {
	function Response($text, $mood) {
		return $text;
	}

	/* 名前を返すメソッド */
	function Name() {
		return $this->name;
	}
}

// TimeResponderクラスの定義(Responderクラスを継承)
class TimeResponder extends Responder {

	// 現在時によって送信する言葉をセットするメソッド
	function Response($text, $mood) {
		$hour = date("G");
		
		switch ($hour) {
			case 6:
				$text = 'おはよう！今日もがんばろう！';
				break;
			case 13:
				$text = 'お昼、何食べた？';
				break;
			case 17:
				$text = '仕事終わった～';
				break;
			case 21:
				$text = 'おやすみなさい。';
				break;
			default:
				$today = date("F j, Y, g: i a");
				$text = $today;
		}
		return $text;
	}
}


/* RandomResponderクラスの定義 */
class RandomResponder extends Responder {

	// メンバ変数
	var $text;  // テキストを格納する変数

	// コンストラクタ（初期化用メソッド）
	function __construct($name) {
		$this->name = $name;
/*		$this->text = array('がんばる！', '眠い。', 'またあとで',
							'なんですと！？', 'ぼちぼちね'); 
*/

		// 乱数の生成
		$no = rand(1, 3);
		

		// 乱数に応じた辞書ファイル名の設定
		$dic = "./dic/RandomDic" . $no . ".txt";
		
		// 辞書ファイルの存在チェック
		if (!file_exists($dic)) {
			die("辞書ファイルが開けません。");
		}

		// 辞書ファイルを変数に格納する
		$this->text = file($dic);
		
	}

	
	// 読み込んだ辞書ファイルからランダムに文字列を取り出すメソッド
	function Response($text, $mood) {
		$res = $this->text[rand(0, count($this->text) - 1)];
		// 改行コードを取り除く
		return rtrim($res, "\n");
	}
}

/* WhatResponderクラスの定義(Responderクラスを継承) */
class WhatResponder extends Responder {

	// 受け取った文字列に「って何？」をつけて返すメソッド
	function Response($text, $mood) {
		return $text . 'って何？';
	}
}

/* GreetingResponderクラスの定義（Responderクラスを継承） */
class GreetingResponder extends Responder {

	// 発言に挨拶文が含まれていたら、対応する挨拶を返すメソッド
	function Responder($text, $mood) {
		if (preg_match("/おは(よ)?(う|ー|～)/", $text)) {
			$txt = "おはようございます";}
		if (preg_match("/こんにち(は|わ)/", $text)) {
			$txt = "こんにちは";}
		if (preg_match("/こんばん(は|わ)/", $text)) {
			$txt = "こんばんは";}
		return $txt;
	}
}


// PatternResponderクラスの定義
class PatternResponder extends Responder {

	// パターン辞書をもとに応答メッセージを作るメソッド
	function Response($text, $mood) {
		// パターン辞書の先頭行から順にパターンマッチをおこなう
		foreach($this->dictionary->Pattern() as $ptn_item) {
			if ($ptn = $ptn_item->Match($text)) {
				$res = $ptn_item->Choice($mood);
				if ($res == null) next;
				// 応答例に「%match%/」という文字列があったら、マッチした文字列を置き換える
				return preg_replace("/%match%/", $ptn, $res);
			}
		}
	}
}

