<?php  // markov.php

// 文章の終わりを示すマークの設定
define("END_MARK", "%END%");
// 文書生成時の最大連鎖数
define("CHAIN_MAX", 30);
// 辞書ファイル名の設定
define("MARKOV_DIC", "./dic/markovdic.dat");
define("STARTS_DIC", "./dic/starts.dat");

class Markov {

    var $dic;
    var $starts;

    function __construct() {
        // ハッシュ初期化
        $this->dic = array();
        $this->starts = array();
    }

    /* マルコフ辞書を作成するメソッド
     * @param: object $words -- 形態素解析の結果
     *   [0]=> { ["surface"]=> "今日", ["reading"]=> "きょう", ["pos"]=> "名詞" }
     *   [1]=> { ["surface"]=> "は"  , ["reading"]=> "は"    , ["pos"]=> "助詞" }
     *   [2]=> { ["surface"]=> "天気", ["reading"]=> "てんき", ["pos"]=> "名詞"  }
     *   [3]=> { ["surface"]=> "が"  , ["reading"]=> "が"    , ["pos"]=> "助詞"  }
     *   [4]=> { ["surface"]=> "良い", ["reading"]=> "よい"  , ["pos"]=> "形容詞" }
     *   [5]=> { ["surface"]=> "です", ["reading"]=> "です"  , ["pos"]=> "助動詞" }
     */
    function Add_Sentence($words) {
        if (count($words) < 3) { return; }

        // 単語を配列に格納する
        // $w = ['今日', 'は', '天気', 'が', '良い', 'です']
        $w = array();
        foreach ($words as $k => $v) {
            array_push($w, chop($v->surface));
        }

        // 文頭の2単語で初期化する
        // array_shift -- 配列の先頭を取り出す。添字は振り直される。
        $prifix1 = array_shift($w);    // '今日'
        $prifix2 = array_shift($w);    // 'は'

        //  $w = ['天気', 'が', '良い', 'です']

        // 文頭をハッシュ($starts)に登録する（文頭となっている文脈を蓄積する）
        // $this->starts['今日'] = 1 -- 文頭が '今日' の $words が出てくるたびに +1 される。
        $this->Add_start($prifix1);

        // すべての単語をテーブル（辞書）に登録する
        // $dic['今日']['は'] = '天気';
        // $dic['は']['天気'] = 'が';
        // $dic['天気']['が'] = '良い';
        // $dic['が']['良い'] = 'です';
        foreach ($w as $k => $v) {
            $suffix = $v;
            $this->Add_Suffix($prifix1, $prifix2, $suffix);
            $prifix1 = $prifix2;
            $prifix2 = $suffix;
        }

        // 最後に終了のマークをつける
        // $dic['良い']['です'] = END_MARK;
        $this->Add_Suffix($prifix1, $prifix2, END_MARK);
    }

    /**
     * Generate -- ナルコフ連鎖で文章を生成するメソッド
     *
     * @param: string $keyword -- （例）「天気」
     */
    function Generate($keyword) {
        if (empty($this->dic)) { return NULL; }
        $words = array();
        
        // 最初のプリフィックスを選択する
        // もし、$this->dic['天気'] があるならば、$prifix1 = 「天気」
        // もしなければ、文頭の単語をあつめた配列 $this->starts から
        //     ランダムにに選んだキー値（単語）を $prifix1とする。
        // $keyword が「天気」なら、dic['天気']は存在するので、
        //     $prifix1 は、「天気」となる。
        $prifix1 = ($this->dic[$keyword]) ? $keyword :
                   $this->Select_Random(array_keys($this->starts));
        
        // $prifix2 は、dic[$prifix1] という配列の中からランダムに
        //     選んだキー値（単語）を $prifix2 とする。
        // $keyword が「天気」ならば、dic['天気']は一つなので、
        //     $prifix2 は、「が」となる。
        $prifix2 = $this->Select_Random(array_keys($this->dic[$prifix1]));

        // $keyword が「天気」の場合、$words = ['天気', 'が'] となる。
        // array_push は、要素を配列の最後に付け加える。
        array_push($words, $prifix1, $prifix2);
        
        $loop = 1;

        while ($loop <= CHAIN_MAX) {
            // 最初のサフィックスをランダムに選択する
            $suffix = $this->Select_Random($this->dic[$prifix1][$prifix2]);
            // END_MARKが出たら終了する
            if ($suffix == END_MARK) { break; }
            // 単語だったら $words に追加する
            array_push($words, $suffix);
            // プリフィックス、サフィックスをスライドする
            $prifix1 = $prifix2;
            $prifix2 = $suffix;
            $loop += 1;
        }
        // $words に格納された単語を1つにつなげて文章を生成する
        return join("", $words);
    }

    // 辞書($dic)にサフィックス（接頭辞）を追加するメソッド
    private function Add_Suffix($prifix1, $prifix2, $suffix) {
        if (!$this->dic[$prifix1]) {
            $this->dic[$prifix1] = array();
        }
        if (!$this->dic[$prifix1][$prifix2]) {
            $this->dic[$prifix1][$prifix2] = array();
        }
        array_push($this->dic[$prifix1][$prifix2], $suffix);
    }

    // 文書の先頭の単語をハッシュ $starts に登録するメソッド
    private function Add_Start($prifix1) {
        if (!$this->starts[$prifix1]) {
            $this->starts[$prifix1] = 0;
        }
        $this->starts[$prifix1] += 1;
    }

    // 配列の中からランダムに1つの要素を返すメソッド
    function Select_Random($ary) {
        return $ary[rand(0, count($ary) - 1)];
    }

    // マルコフ辞書を保存するメソッド
    function Save() {
        $fname = MARKOV_DIC;
        $fp = fopen($fname, 'w');
        if ($fp != NULL) {
            // シリアル化して保存
            fputs($fp, serialize($this->dic));
            fclose($fp);
        }

        $fname = STARTS_DIC;
        $fp = fopen($fname, 'w');
        if ($fp != NULL) {
            // シリアル化して保存
            fputs($fp, serialize($this->starts));
            fclose($fp);
        }
    }

    // マルコフ辞書を読み込むメソッド
    function Load() {
        $file = file(MARKOV_DIC);
        if ($file) {
            // 逆シリアル化
            $data = unserialize($file[0]);
            $this->dic = $data;
        }
        $file = file(STARTS_DIC);
        if ($file) {
            // 逆シリアル化
            $data = unserialize($file[0]);
            $this->starts = $data;
        }
    }
}

