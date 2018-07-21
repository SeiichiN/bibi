<?php // morpheme.php

// Yahoo API のアプリケーションID
define("YAHOO_API_ID", "dj00aiZpPVlXc3prb0lvOGtGTSZzPWNvbnN1bWVyc2VjcmV0Jng9YWE-");

// Yahoo_morphクラスの定義
class Yahoo_morph {

    var $xml;

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

        return $this->xml->ma_result->word_list->word;
    }

    // 形態素の総数を返すメソッド
    function Total_count() {
        return $this->xml->ma_result->total_count;
    }

    // フィルタにマッチした形態素数を返すメソッド
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
