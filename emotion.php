<?php // emotion.php

// 定数の定義
// 機嫌値下限
define ("MODE_MIN", "-15");
// 機嫌値上限
define ("MODE_MAX", "15");
// 機嫌値の回復する度合い
define ("MODE_RECOVERY", "0.5");

// Emotionクラスの定義
class Emotion {

	// メンバ変数
	// パターン辞書オブジェクトを格納する変数
	var $dictionary;
	// 現在の機嫌値を格納する変数
	var $mood;

	// コンストラクタ
	function __construct($dictionary) {
		// パターン辞書オブジェクトを格納する
		$this->dictionary = $dictionary;
		// 現在の機嫌値を読み込む
		$this->mood = $this->Load_mood();
	}

	// 会話によって機嫌値を変動させるメソッド
	function Update($input) {
		// パターン辞書の要素を繰り返し処理する
		foreach ($this->dictionary->Pattern() as $ptn_item) {
			// パターンマッチをおこなう
			if ($ptn_item->Match($input)) {
				// マッチしたら Adjust_mood メソッドで機嫌値を変動させる
				$this->Adjust_mood($ptn_item->modify);
				break;
			}
		}

		// 機嫌を徐々に平静な状態（機嫌値0）に回復させる処理
		if ($this->mood < 0) {
			// 0 以下なら、+0.5 ずつ 0 に近づける
			$this->mood += MODE_RECOVERY;
		} else if ($this->mood > 0) {
			// 0 以上なら、-0.5 ずつ 0 に近づける
			$this->mood -= MODE_RECOVERY;
		}
		// 現在の機嫌値を保存する
		$this->Save_mood($this->mood);
	}

	// 機嫌値を変動させるメソッド
	function Adjust_mood($val) {
		// 機嫌変動値($val)によって機嫌値を変動させる
		$this->mood += $val;
		// 機嫌値が上限・下限を超えないようにする処理
		if ($this->mood > MODE_MAX) {
			$this->mood = MODE_MAX;
		} elseif ($this->mood < MODE_MIN) {
			$this->mood = MODE_MIN;
		}
	}

	// 機嫌値(mood)をファイルから読み込むメソッド
	function Load_mood() {
		$dat = "./dat/mood.dat";
		if (!file_exists($dat)) {
			touch($dat);
			chmod($dat, 0666);
			return null;
		}
		$fdat = fopen($dat, 'r');
		$mood = fgets($fdat);
		fclose($fdat);
		return $mood;
	}
	// 機嫌値($mood)をファイルに書き込むメソッド
	function Save_mood($data) {
		$dat = "./dat/mood.dat";
		if (!file_exists($dat)) {
			touch($dat);
			chmod ($dat, 0666);
		}
		$fdat = fopen($dat, 'w');
		flock($fdat, LOCK_EX);
		fputs($fdat, $data);
		flock($fdat, LOCK_UN);
		fclose($fdat);
	}

	// 現在の機嫌値を取得するメソッド
	function Mood() {
		return $this->mood;
	}
}
