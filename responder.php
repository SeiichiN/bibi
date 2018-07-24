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

	/**
     * Response -- 受け取った文字列をそのまま返すメソッド
     * 
     * @param: string $text -- 発言
     *                $mood -- 数値 (-15 .. 15)
     *                $words -- 形態素解析の結果（オブジェクト）
     *
	 *  PHP5.4以上では、メソッドのオーバーライドにおいて
	 *  引数が一致しないとエラーが出る。
	 *  TimeResponder の Responseメソッドである。
	 *  こちらのメソッドの名前を変えておいた
	 *    by Seiichi Nukayama 2017.02.11
     */
//	function MirrorResponse($text) {
	function Response($text, $mood, $words) {
		return "";    // 何も返さないようにする
	}

	/* 名前を返すメソッド */
	function Name() {
		return $this->name;
	}
}

// TimeResponderクラスの定義(Responderクラスを継承)
class TimeResponder extends Responder {

	// 現在時によって送信する言葉をセットするメソッド
	function Response($text, $mood = NULL, $words = NULL) {
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
	var $text = array();  // テキストを格納する変数（配列）

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
			$msg = "辞書ファイルが開けません。";
			putErrLog($msg);
			die($msg);
		}

		// 辞書ファイルの内容を1行ずつを配列に格納する
		$this->text = file($dic);
		
	}

	
	// 読み込んだ辞書ファイルからランダムに文字列を取り出すメソッド
	function Response($text, $mood = NULL, $words = NULL) {
		$res = $this->text[rand(0, count($this->text) - 1)];
		// 改行コードを取り除く
		return rtrim($res, "\n");
	}
}

/* WhatResponderクラスの定義(Responderクラスを継承) */
class WhatResponder extends Responder {

	// 受け取った文字列に「って何？」をつけて返すメソッド
	function Response($text, $mood = NULL, $words = NULL) {
		return $text . 'って何？';
	}
}

/* GreetingResponderクラスの定義（Responderクラスを継承） */
class GreetingResponder extends Responder {

	// 発言に挨拶文が含まれていたら、対応する挨拶を返すメソッド
	function Responder($text, $mood = NULL, $words = NULL) {
		if (preg_match("/おは(よ)?(う|ー|～)/", $text)) {
			$txt = "おはようございます";}
		if (preg_match("/こんにち(は|わ)/", $text)) {
			$txt = "こんにちは";}
		if (preg_match("/こんばん(は|わ)/", $text)) {
			$txt = "こんばんは";}
		return $txt;
	}
}


/*
 * PatternResponderクラスの定義
 *
 * $ptn_item
 * { ["pattern"]=> "バカ|ばか|馬鹿",
 * 		["modify"]=> -15,
 * 		["phrases"]=> { [0]=> { ["need"] => -10, ["phrase"] => "なんですって!" },
 * 						[1]=> { ["need"] =>  -5, ["phrase"] => "馬鹿じゃないもん!" },
 * 						[2]=> { ["need"] =>   1, ["phrase"] => "なんか言った？" },
 * 						[3]=> { ["need"] =>   5, ["phrase"] => "冗談でしょ？" },
 * 						[4]=> { ["need"] =>  10, ["phrase"] => "またまたー。" } }
 */
class PatternResponder extends Responder {

	// パターン辞書をもとに応答メッセージを作るメソッド
	function Response($text, $mood, $words = NULL) {
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

/* TemplateResponderクラスの定義（Responderクラスを継承） */
class TemplateResponder extends Responder {

    // テンプレート辞書を元に応答メッセージを作るメソッド
    // 引数$words に形態素解析の結果を渡す
    /**
     * @param: object $words
     *      $this->xml->ma_result_word_list->word
     *     （例） 
     *     [0]=> { ["surface"]=> "今日", ["reading"]=> "きょう", ["pos"]=> "名詞" }
     *     [1]=> { ["surface"]=> "は"  , ["reading"]=> "は"    , ["pos"]=> "助詞" }
     *     [2]=> { ["surface"]=> "天気", ["reading"]=> "てんき", ["pos"]=> "名詞"  }
     *     [3]=> { ["surface"]=> "が"  , ["reading"]=> "が"    , ["pos"]=> "助詞"  }
     *     [4]=> { ["surface"]=> "良い", ["reading"]=> "よい"  , ["pos"]=> "形容詞" }
     *     [5]=> { ["surface"]=> "です", ["reading"]=> "です"  , ["pos"]=> "助動詞" }
     * 
     * $keywords -- （例）['今日', '天気']
     */
    function Response($text, $mood, $words) {
        // 文章に含まれるキーワード（名詞）を配列に格納する
        $keywords = array();
        foreach ($words as $k => $v) {
            if (preg_match("/名詞/", $v->pos)) {
                array_push($keywords, $v->surface);
            }
        }
        // キーワードの数を数える -- （例）$count = 2
        $count = count($keywords);
        // 辞書に使えるテンプレートがあったら
        //   （例）$this->template[2] -- [ '%noun%は%noun%が良いです', '%noun%は%noun%だ', ... ]
        if ($count > 0 && $templates = $this->dictionary->template[$count]) {
            // キーワード数にマッチするテンプレートを辞書からランダムに選択する
            $template = $templates[rand(0, count($templates) - 1)];
            // 「%noun%」をキーワードに置き換える
            foreach ($keywords as $v) {
                $templ = preg_replace("/%noun%/", $v, $template, 1);
                $template = $templ;
            }
            return $template;
        }
    }
}

/* MarkovResponderクラスの定義（Responderクラスを継承） */
class MarkovResponder extends Responder {

	function Response($text, $mood, $words) {
		$keywords = array();
		// キーワード（名詞）の抽出
		foreach ($words as $v) {
			if (preg_match("/名詞/", $v->pos)) {
				array_push($keywords, $v->surface);
			}
		}

		// キーワードから文章を生成して返す
		if (count($keywords)) {
			$keyword = $keywords[rand(0, count($keywords) - 1)];
			// MarkovクラスのGenerateメソッドで文章を生成する
			$res = $this->dictionary->markov->Generate(chop($keyword));
			if ($res) { return $res; }
		}
	}
}
		
