<?php // morpheme.php
/*
 * 参考サイト
 * https://developer.yahoo.co.jp/webapi/jlp/ma/v1/parse.html
 */

// Yahoo API のアプリケーションID
define("YAHOO_API_ID", "dj00aiZpPVlXc3prb0lvOGtGTSZzPWNvbnN1bWVyc2VjcmV0Jng9YWE-");

// Yahoo_morphクラスの定義
class Yahoo_morph {

    var $xml;

    /**
     * Request -- 形態素解析をして、その結果を得る
     * @param: string $text -- 元となる言葉
     *                         「今日は天気が良いです」
     * @return: object $this->xml->ma_result_word_list->word 
     *   [0]=> { ["surface"]=> "今日", ["reading"]=> "きょう", ["pos"]=> "名詞" }
     *   [1]=> { ["surface"]=> "は"  , ["reading"]=> "は"    , ["pos"]=> "助詞" }
     *   [2]=> { ["surface"]=> "天気", ["reading"]=> "てんき", ["pos"]=> "名詞"  }
     *   [3]=> { ["surface"]=> "が"  , ["reading"]=> "が"    , ["pos"]=> "助詞"  }
     *   [4]=> { ["surface"]=> "良い", ["reading"]=> "よい"  , ["pos"]=> "形容詞" }
     *   [5]=> { ["surface"]=> "です", ["reading"]=> "です"  , ["pos"]=> "助動詞" }
     */
    function Request($text) {

        $text = rtrim($text, "\n");

        // API用パラメータ
        $params = array(
            'appid' => YAHOO_API_ID,
            'sentence' => $text,
            'results' => 'ma',
        );

        $url = "http://jlp.yahooapis.jp/MAService/V1/parse";

        // APIリクエスト
        $api = new Web_API("Yahoo_Morph");
        $this->xml = $api->Request($url, $params);

		// var_dump($this->xml); die();  // オブジェクトを調べる
		
        return $this->xml->ma_result->word_list->word;
    }

    /**
     * 形態素の総数を返すメソッド
     * 「今日は天気が良いです」
     * @return: string  -- 6
     */
    function Total_count() {
        return $this->xml->ma_result->total_count;
    }

    /**
     * フィルタにマッチした形態素数を返すメソッド
     * 「今日は天気が良いです」
     * @return: string  -- 6
     */
    function Filtered_count() {
        return $this->xml->ma_result->filtered_count;
    }

    // 形態素（配列）を返すメソッド
    function Words() {
        return $this->xml->ma_result->word_list->word;
    }

    // xmlオブジェクトを返すメソッド
    function Response() {
        return $this->xml;
    }
}
