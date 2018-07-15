<?php // dictionary.php
// 辞書クラス

// 定数の定義
// パターン辞書のファイル名
define("PATTERN_DIC", "./dic/PatternDic1.txt");
define("SEPARATOR", "/^((-?\d+)##)?(.*)$/");

// Dictionaryクラスの定義
class Dictionary {

    // メンバ変数
    // ファイルから読み込んだテキストを格納する変数
    var $pattern = array();

    // コンストラクタ
    function __construct() {
        $this->PatternLoad();
    }

    // パターン辞書ファイルを読み込むメソッド
    function PatternLoad() {
        // パターン辞書ファイルを読み込む
        $dic = PATTERN_DIC;
        if (!file_exists($dic)) {
            $msg = "$dic ファイルが開けません";
            putErrLog($msg);
            die($msg);
        }
        $file = file($dic);
        // パターン辞書ファイルを連想配列に展開する
        foreach ($file as $line) {
            // 1行ずつ読み込んで処理する
            // タブで分割したテキストのそれぞれを $key, $val に代入する
            list($key, $val) = explode("\t", rtrim($line . "\n"));
            // 連想配列に要素を格納する
            $ptn['pattern'] = $key;
            $ptn['phrases'] = $val;
            array_push($this->pattern, $ptn);
        }
    }

    // パターン辞書にアクセスするためのメソッド
    function Pattern() {
        return $this->pattern;
    }
}

// PaternItemクラスの定義
class PatternItem {

    // メンバ変数
    // パターンマッチ文字列を格納する変数
    var $pattern;
    // 機嫌変動値を格納する変数
    var $modify;
    // 応答例を格納する変数
    var $phrases = array();

    // コンストラクタ
    function __construct($pattern, $phrases) {
        // $pattern から機嫌変動値とパターンマッチ文字列を取り出す
        preg_match(SEPARATOR, $pattern, $regex);
        // 機嫌変動値を変数に格納する
        $this->modify = intval($regex[2]);
        // パターンマッチ文字列を変数に格納する
        $this->pattern = $regex[3];
        // 応答例を連想配列に格納する
        foreach (explode("|", $phrases) as $phrase) {
            preg_match(SEPARATOR, $phrase, $regex);
            $ph['need'] = intval($regex[2]);
            $ph['phrase'] = regex[3];
            array_push($this->phrases, $ph);
        }
    }

    // パターンマッチをおこなうメソッド
    function Match($str) {
        return preg_match("/". $this->pattern . "/", $str);
    }

    // 現在の起源地($mood)によって応答例を選択するメソッド
    function Choice($mood) {
        // 応答例の候補を配列に格納する
        $choice = array();
        foreach ($this->phrases as $p) {
            if ($this->Check($p['need'], $mood)) {
                array_push($choice, $p['phrase']);
            }
        }
        // 候補からランダムに１つ選んだ応答例を返す
        return empty($choice)? null : $choice[rand(0, count($choice) - 1)];
    }

    // 応答例が必要機嫌値の条件を満たしているかをチェックするメソッド
    function Check($need, $mood) {
        if ($need == 0) { return TRUE; }
        if (($need < $mood + 5) && ($need > $mood - 5)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
