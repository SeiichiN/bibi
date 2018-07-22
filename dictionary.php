<?php // dictionary.php

// 辞書クラス

// 定数の定義
// パターン辞書のファイル名
define("PATTERN_DIC", "./dic/PatternDic2.txt");
// ランダム辞書のファイル名
define("RANDOM_DIC", "./dic/RandomDic1.txt");
// テンプレート辞書のファイル名
define("TEMPLATE_DIC", "./dic/TemplateDic1.txt");
// セパレータ
define("SEPARATOR", "/^((-?\d+)##)?(.*)$/");

/* 
 * Dictionaryクラスの定義
 *
 * $pattern
 *   [ 'pattern' => <パターン>, 'modify' => <数値>, 'phrases' => 配列 ]
 * phrases
 *   [ 'need' => <数値>, 'phrase' => <string> ]
 *
 * （例）$pattern の一部
 *     [0] [pattern] => バカ|ばか|馬鹿,
 *         [modify] => -15,
 *         [phrases] [0] [need] => -10, [phrase] => なんですって!
 *                   [1] [need] => -5,  [phrase] => 馬鹿じゃないもん!
 *                   [2] [need] => 1,   [phrase] => なんか言った？
 *                   [3] [need] => 5,   [phrase] => 馬鹿なんていっちゃいやだよ
 *                   [4] [need] => 10,  [phrase] => 冗談でしょ？
 *                   [5] [need] => 10,  [phrase] => またまたー。
 * */
class Dictionary {

    // メンバ変数
    // ファイルから読み込んだテキストを格納する変数
    var $pattern = array();
	
	// ランダム辞書から読み込んだテキストを格納する変数
	var $random = array();

	// テンプレート辞書から読み込んだテキストを格納する変数
	var $template = array();

    // コンストラクタ
    function __construct() {
		// パターン辞書を読み込む
        $this->PatternLoad();
		// ランダム辞書を読み込む
		$this->RandomLoad();
		// テンプレート辞書を読み込む
		$this->TemplateLoad();
    }

    // パターン辞書ファイルを読み込むメソッド
    function PatternLoad() {
        // パターン辞書ファイルを読み込む
        $dic = PATTERN_DIC;
        if (!file_exists($dic)) {
            $msg = "$dic ファイルが開けません\n";
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

	/**
     * Study_Pattern -- パターン辞書の学習メソッド
     * @param:
     *   string $text -- 発言文（1行）
     *   object $words -- 形態素解析で取得したオブジェクト
     *      $this->xml->ma_result_word_list->word 
     *     [0]=> { ["surface"]=> "今日", ["reading"]=> "きょう", ["pos"]=> "名詞" }
     *     [1]=> { ["surface"]=> "は"  , ["reading"]=> "は"    , ["pos"]=> "助詞" }
     *     [2]=> { ["surface"]=> "天気", ["reading"]=> "てんき", ["pos"]=> "名詞"  }
     *     [3]=> { ["surface"]=> "が"  , ["reading"]=> "が"    , ["pos"]=> "助詞"  }
     *     [4]=> { ["surface"]=> "良い", ["reading"]=> "よい"  , ["pos"]=> "形容詞" }
     *     [5]=> { ["surface"]=> "です", ["reading"]=> "です"  , ["pos"]=> "助動詞" }
     */
	function Study_Pattern($text, $words) {
		foreach ($words as $k => $v) {
			//名刺でなかったら処理しない
			if (!(preg_match("/名詞/", $v->pos))) { continue; }
			// キーワードの重複チェック
			foreach ($this->pattern as $ptn_item) {
				$s = preg_match("/".$v->surface."/", $ptn_item->pattern);
				$r = preg_match("/".$v->reading."/", $ptn_item->pattern);
				if ($s == 1 || $r == 1) {
					// 重複ありなら Add_phraseメソッドを実行する
					$p = $ptn_item->Add_phrase($text);
					continue 2;
				}
			}
			
			// 重複なしならキーワードと応答例を辞書に追加する
			// 読みがなが同じでなかったら
			if ($v->surface != $v->reading) {
				// 読みがなもキーワードとして登録する
				$key = $v->surface."|".$v->reading;
			} else {
				$key = $v->surface;
			}
			$patternitem = new PatternItem($key, $text);
			// PatternItemオブジェクトのハッシュに格納する
			array_push($this->pattern, $patternitem);
		}
	}
			

	// ランダム辞書ファイルを読み込むメソッド
	function RandomLoad() {
		$dic = RANDOM_DIC;
		if (!file_exists($dic)) {
			$msg = "$dic ファイルが開けません\n";
            putErrLog($msg);
            die($msg);
        }
        $file = file($dic);
        // ランダム辞書を連想配列に格納する
        foreach ($file as $line) {
            $l = rtrim($line, "\n");
            if (empty($l)) { continue; }
            array_push($this->random, $l);
        }
    }

    /**
     * Study -- 辞書の学習メソッドを実行
     * @param: string $text -- 1行の発言文
     *         
     */
    function Study($text, $words) {
		// ランダム辞書に、発言($text)を追加する
        $this->Study_Random($text);

		// パターン辞書に形態素解析の結果を追加する
		$this->Study_Pattern($text, $words);

		// テンプレート辞書学習メソッド
		$this->Study_template($words);
    }

    // ランダム辞書の学習メソッド
    function Study_Random($text) {
        // 引数のテキストと同じ内容が辞書内にあるかどうかをチェック
        if (array_search($text, $this->random) !== FALSE) { return; }
        // なかったらランダム辞書のハッシュに登録する
        array_push($this->random, $text);
    }

	// テンプレート辞書を読み込むメソッド
	function TemplateLoad() {
		$dic = TEMPLATE_DIC;
		if (!file_exists($dic)) {
			$msg = "$dic ファイルが開けません。";
			putErrLog($msg);
			die($msg);
		}
		$file = file($dic);
		foreach ($file as $line) {
			// タブで分割されたテキストのそれぞれを $key, $val に代入する
			list($key, $val) = explode("\t", rtrim($line, "\n"));
			// 連想配列に要素を格納する
			if (!$this->template[$key]) { $this->template[$key] = array(); }
			array_push($this->template[$key], $val);
		}
	}

	/**
     * Study_Template -- テンプレート辞書の学習メソッド
     * @param:
     *   object $words -- 形態素解析で取得したオブジェクト
     *      $this->xml->ma_result_word_list->word 
     *     [0]=> { ["surface"]=> "今日", ["reading"]=> "きょう", ["pos"]=> "名詞" }
     *     [1]=> { ["surface"]=> "は"  , ["reading"]=> "は"    , ["pos"]=> "助詞" }
     *     [2]=> { ["surface"]=> "天気", ["reading"]=> "てんき", ["pos"]=> "名詞"  }
     *     [3]=> { ["surface"]=> "が"  , ["reading"]=> "が"    , ["pos"]=> "助詞"  }
     *     [4]=> { ["surface"]=> "良い", ["reading"]=> "よい"  , ["pos"]=> "形容詞" }
     *     [5]=> { ["surface"]=> "です", ["reading"]=> "です"  , ["pos"]=> "助動詞" }
	 *
	 * できあがりのテンプレート（例）
	 *   $this->template[1] -- [ '%noun%がほしいね', '%noun%が笑った', ... ]
	 *   $this->template[2] -- [ '%noun%は%noun%が良いです', '%noun%は%noun%だ', ... ]
	 *   $this->template[3] -- [ '%noun%と%noun%は%noun%です', '%noun%は%noun%が好きで、%noun%が嫌いだ', ... ]
	 *   ....
     */
	function Study_Template($words) {
		$template = "";
		$count = 0;
		foreach ($words as $k => $v) {
			$surface = $v->surface;
			// 単語が名詞だったら
			if (preg_match("/名詞/", $v->pos)) {
				// 単語を %noun% に置き換える
				$surface = "%noun%";
				$count += 1;  // 空欄の数をカウントする
			}
			// 単語を連結する
			$template = $template . $surface;
		}
		// $template -- %noun%は%noun%が良いです
		// $count -- 2

		// 空欄が1つもないなら登録しない
		if ($count == 0) { return; }
		if (!$this->template[$count]) { $this->template[$count] = array(); }
		// テンプレートの重複チェック
		if (array_search($template, $this->template[$count]) !== FALSE) { return; }
		// 重複がなかったら追加する
		array_push($this->template[$count], $template);
	}

	// テンプレート辞書にアクセスするためのメソッド
	function Template() {
		return $this->template;
	}
	

    // 辞書のハッシュをファイルに保存する
    function Save() {
		
		// --- ランダム辞書の保存 --------------------------
        $dat = RANDOM_DIC;
		if (!file_exists($dat)) {
			$msg = "$dat ファイルが開けません\n";
            putErrLog($msg);
            die($msg);
        }
        $fdat = fopen($dat, 'w');
        flock($fdat, LOCK_EX);
        foreach($this->random as $line) {
            fputs($fdat, $line . "\n");
        }
        flock($fdat, LOCK_UN);
        fclose($fdat);

		// --- パターン辞書の保存 --------------------------
		$dat = PATTERN_DIC;
		if (!file_exists($dat)) {
			$msg = "$dat ファイルが開けません";
			putErrLog($msg);
			die($msg);
		}
        $fdat = fopen($dat, 'w');
        flock($fdat, LOCK_EX);
        foreach($this->pattern as $ptn_item) {
			// Make_lineメソッドでハッシュから1行分のデータを生成する
            fputs($fdat, $ptn_item->Make_line() . "\n");
        }
        flock($fdat, LOCK_UN);
        fclose($fdat);

		// --- テンプレート辞書の保存 -------------------------
		$dat = TEMPLATE_DIC;
		if (!file_exists($dat)) {
			$msg = "$dat ファイルが開けません";
			putErrLog($msg);
			die($msg);
		}
        $fdat = fopen($dat, 'w');
        flock($fdat, LOCK_EX);
		// テンプレート辞書のハッシュを展開する
        foreach($this->template as $key1 => $val1) {
			foreach ($val1 as $key2 => $val2) {
				// テンプレート辞書のフォーマットにしたがって、1行ずつ保存する
				fputs($fdat, $key1 . "\t" . $val2 . "\n");
			}
        }
        flock($fdat, LOCK_UN);
        fclose($fdat);
		
    }

    // ランダム辞書にアクセスするためのメドッド
    function Random() {
        return $this->random;
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
            
            $ph['need'] = intval($regex[2]);
            $ph['phrase'] = $regex[3];
            array_push($this->phrases, $ph);
        }
    }

    /*
     * パターンマッチをおこなうメソッド
     * @params: string $str
     *   たとえば、以下のパターンと照合する。
     *     $this->pattern -- -15##バカ|ばか|馬鹿
	 */
    function Match($str) {
        return preg_match("/". $this->pattern . "/", $str);
    }

    /*
     * 現在の機嫌値($mood)によって応答例を選択するメソッド
     * <例>
     *  >かわいいね。
     *  Bot(Pattern-機嫌値[-6.5])>いまさら褒めたってゆるさない!
     *  >いやいや、ほんとにかわいいよ。
     *  Bot(Pattern-機嫌値[-1])>かわいくないもん!
     *  >絶対、かわいいよ。
     *  Bot(Pattern-機嫌値[3.5])>あら、ありがとう
     *  >サイコー、かわいいよ。
     *  Bot(Pattern-機嫌値[8])>うれしいよ!
     *  >とても、かわいいよ。
     *  Bot(Pattern-機嫌値[12.5])>うれしいよ!
     *  >いいね。かわいい。
     *  Bot(Pattern-機嫌値[14.5])>今度、デートしよう!
     */
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

	// 応答例の重複をチェックするメソッド
	function Add_phrase($text) {
		// 応答例の中にテキスト($text)と重複する内容があるかどうかをチェックする
		foreach ($this->phrases as $p) {

			// 重複していたら何もしない
			if ($p[phrase] == $text) { return; }
		}

		// 重複する応答例がなかったら、応答例にテキストを追加する
		$ph['need'] = 0;        // 追加する応答例の必要機嫌値は 0 にする
		$ph['phrase'] = $text;
		array_push($this->phrases, $ph);
	}

	// パターン辞書のハッシュからファイル1行分のデータを生成する
	function Make_line() {
		$ph = array();
		$pattern = $this->modify."##".$this->pattern;
		foreach ($this->phrases as $p ) {
			$phrases = $p[need]."##".$p[phrase];
			array_push($ph, $phrases);
		}
		return $pattern."\t".join("|", $ph);
	}
	
}
	

