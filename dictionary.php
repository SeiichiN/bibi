<?php // dictionary.php
// 辞書クラス

// 定数の定義
// パターン辞書のファイル名
define("PATTERN_DIC", "./dic/PatternDic2.txt");
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

			// PatternItemオブジェクトの生成
			$patternitem = new PatternItem($ptn['pattern'], $ptn['phrases']);
			// PatternItemオブジェクトのハッシュに格納する
            array_push($this->pattern, $patternitem);
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
    /* 
     * たとえば、以下のような辞書の一行があったとする。
     *   -15##バカ|ばか|馬鹿 <TAB> -10##なんですって!|-5##馬鹿じゃないもん!|1##なんか言った？|5##馬鹿なんていっちゃいやだよ|10##冗談でしょ？|10##またまたー。
     * これは、以下のような変数に格納される。
     *     $pattern -- -15##バカ|ばか|馬鹿
     *     $modify -- -15
     *     $phrases -- ['need' => -10, 'phrase' => 'なんですって!'],
     *                 ['need' => -5,  'phrase' => '馬鹿じゃないもん!'],
     *                 ['need' => 1,   'phrase' => 'なんか言った？'],
     *                 ['need' => 5,   'phrase' => '馬鹿なんていっちゃいやだよ'],
     *                 ['need' => 10,  'phrase' => '冗談でしょ？'],
     *                 ['need' => 1-,  'phrase' => 'またまたー。']
     *     */
    function __construct($pattern, $phrases) {
        // $pattern から機嫌変動値とパターンマッチ文字列を取り出す
		/* 
           $regex -- マッチした文字列が格納される
		   Array
		   (
		     [0] => -15##バカ|ばか|馬鹿
		     [1] => -15##
		     [2] => -15
		     [3] => バカ|ばか|馬鹿
		   )
         */
        preg_match(SEPARATOR, $pattern, $regex);
        // if (DEBUG_MODE) { print_r($regex); echo "\n"; die(); }
		
        // 機嫌変動値を変数に格納する
        // intval -- 整数値をとりだす
        $this->modify = intval($regex[2]);
        // パターンマッチ文字列を変数に格納する
        $this->pattern = $regex[3];
        // 応答例を連想配列に格納する
        foreach (explode("|", $phrases) as $phrase) {
            /* 
             * $regex の例
             *
             * Array
             * (
             *     [0] => -10##なんですって!
             *     [1] => -10##
             *     [2] => -10
             *     [3] => なんですって!
             * )
             * */
            preg_match(SEPARATOR, $phrase, $regex);
            // if (DEBUG_MODE) { print_r($regex); echo "\n"; die(); }
            $ph['need'] = intval($regex[2]);
            $ph['phrase'] = $regex[3];
            array_push($this->phrases, $ph);
        }
    }

    // パターンマッチをおこなうメソッド
    function Match($str) {
        return preg_match("/". $this->pattern . "/", $str);
    }

    // 現在の機嫌値($mood)によって応答例を選択するメソッド
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
    // 成立条件 -- $mood - 5 < $need < $mood + 5
    function Check($need, $mood) {
        if ($need == 0) { return TRUE; }
        if (($need < $mood + 5) && ($need > $mood - 5)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}