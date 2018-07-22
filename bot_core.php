<?php
/*
 * Botクラス
 */

// ユーティリティファイルの読み書き
require_once("util.php");

// Responderクラスの読み込み
require_once("responder.php");

// Dictionaryクラスの読み込み
require_once("dictionary.php");

// Emotionクラスの読み込み
require_once("emotion.php");

// morphemeクラスの読み込み
require_once("morpheme.php");

// Oauthライブラリの読み込み
require_once("./oauth/autoload.php");
use Abraham\TwitterOAuth\TwitterOAuth;

/* Botクラスの定義 */
class Bot {
	/* メンバ変数 */
	// ユーザー名を格納する変数
	var $user;
	// Dictionaryオブジェクトを格納する変数
	var $dic;
	// OAuthオブジェクトを格納する変数
	var $Obj;
	// Responderオブジェクトを格納する変数
	var $responder;
	// RandResponderオブジェクトを格納する変数
	var $rand_responder;
	// TimeResponderオブジェクトを格納する変数
	var $time_responder;
	// WhatResponderオブジェクトを格納する変数
	var $what_responder;
	// GreetingResponderオブジェクトを格納する変数
	var $greet_responder;
	// PatternResponderオブジェクトを格納する変数
	var $pattern_responder;
	// TemplateResponderオブジェクトを格納する変数
	var $template_responder;
	// Emotionオブジェクトを格納する変数
	var $emotion;

	/* コンストラクタ（初期化用メソッド） */
	function __construct($usr, $consumer_key, $consumer_secret,
				 $oauth_token, $oauth_token_secret)
	{
		$this->user = $usr;
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		$this->oauth_token = $oauth_token;
		$this->oauth_token_secret = $oauth_token_secret;

		// OAuthオブジェクトの生成
		$this->Obj = new TwitterOAuth($consumer_key,
									  $consumer_secret,
									  $oauth_token,
									  $oauth_token_secret);

		// Dictionaryオブジェクトの生成
		$this->dic = new Dictionary();

		// Emotionオブジェクトの生成
		$this->emotion = new Emotion($this->dic);
		
		// Responderオブジェクトを生成する際にDictionaryオブジェクトを渡す
		
		// $this->responder = new Responder('OneWord');
		
		// TimeResponderオブジェクトの生成
		$this->time_responder = new TimeResponder('Time', $this->dic);
		// WhatResponderオブジェクトの生成
		$this->what_responder = new WhatResponder('What', $this->dic);
		// GreetingResponderオブジェクトの生成
		$this->greet_responder = new GreetingResponder('Greeting', $this->dic);
		// PatternResponderオブジェクトの生成
		$this->pattern_responder = new PatternResponder('Pattern', $this->dic);

		
		// RandomResponderオブジェクトの生成
		$this->rand_responder = new RandomResponder('Random', $this->dic);

		// TemplateResponderオブジェクトの生成
		$this->template_responder = new TemplateResponder('Template', $this->dic);
		
		// RandomResponderを規定のResponderにする
		$this->responder = $this->rand_responder;
	}

	/* リクエストを送信するメソッド */
	function Request($url, $method = "POST", $opt = array())
	{
		$req = $this->Obj->
			OAuthRequest("https://api.twitter.com/1.1/".$url,
						 $method, $opt);
		if ($req) {$result = $req;}
		else {$result = NULL;}
// チェック用
//		if (DEBUG_MODE) {
//			print '<br>$result='; var_dump($result);
//		}
// check-end
		return $result;
	}
    /**
     * Post -- ツイートを送信するメソッド
     *
     * 相手にリプライする場合
     * $status -- "@" . $screen_name . " " . $txt(発言文字列)
     * $req -- $sid(発言id)
     */
	function Post($status, $rep = null)
	{
		// つぶやく文字列($status)をリクエストパラメータにセットする
		$opt = array();
		$opt['status'] = $status;

		// $repは相手にリプライする場合にリプライ元の発言IDを指定する
		// リプライ元の発言とリプライする相手のユーザー名が一致しなければならない
		if ($rep) {
			$opt['in_reply_to_status_id'] = $rep;
		}
		
		// リクエストを送信する
		$req = $this->Request("statuses/update.json", "POST", $opt);

		return $req;
	}

	/* テキストをResponderオブジェクトに渡すメソッド */
	function Speaks($input)
	{
		// 2つのResponderオブジェクトをランダムに切り替える
		$this->responder = 
			rand(1, 2) - 1 == 0 ? $this->time_responder : $this->rand_responder;
		
		return $this->responder->Response($input);
	}

	/* テキストをResponderオブジェクトに渡すメソッド（リプライ用） */
	function Conversation($input, $uid = NULL)
	{
		// GreetingResponderをResponder に設定する
		// $this->responder = $this->greet_responder;
		
		// PatternResponderをResponder に設定する
		// $this->responder = $this->pattern_responder;

		// TemplateResponderをResponder に設定する
		$this->responder = $this->template_responder;

		// 宛先のユーザー名を消す
		$input = trim(preg_replace("/@[a-zA-Z0-9_]+/", "", $input));

		// パターンマッチをおこない、感情を変動させる
		$this->emotion->Update($input, $uid);

		// 形態素解析の結果を取得する
		$m = new Yahoo_morph();
		$words = $m->Request($input);

		// Studyメソッドにテキストを渡して学習する
		// 引数 $words で形態素解析の結果を渡す
		$this->dic->Study($input, $words);

		$this->save();  // 辞書ファイルの保存
		
		/**
		 * Responseを返す
		 * @param: string $input -- 発言
		 *                $this->emotion->mood -- 現在の機嫌値
		 *         object $words -- 形態素解析結果
		 */
		$res = $this->responder->Response($input, $this->emotion->mood, $words);
		
		// if (DEBUG_MODE) { echo '>>> $res=> ', $res, "\n"; }
		
		return $res;
	}

	/* Responderオブジェクトの名前を返すメソッド */
	function ResponderName()
	{
		return $this->responder->Name();
	}

	/* タイムラインを取得するメソッド */
	function GetTimeline($type, $sid = null, $count = 30)
	{
		// リクエストパラメータのセット
		$opt = array();
		// $countは取得数（最大200）
		$opt['count'] = $count;
		// $sidはツイート、リプライの発言ID
		if ($sid) { $opt['since_id'] = $sid; }
		// JSON形式でタイムラインを取得する
		$req = $this->Request("statuses/" . $type . ".json", "GET", $opt);
		
/*
		if (DEBUG_MODE) {
			echo 'DEBUG_MODE=' . DEBUG_MODE . "<br>\n";
			echo 'print_r($req)=> ';
			print_r($req);
		}
*/
		
		// PHP配列に変換する
		$result = json_decode($req);
		// エラー処理
		if (!is_array($result)) {die("Error\n");}
		// 配列を逆順にして返す
		return array_reverse($result);
	}

	// ファイルに記録したデータを読み込むメソッド
	// $opt -- 'f'を指定すると、返り値として $dat（ファイル名）を返す
	// bot.phpの中で、ReadDataメソッドを呼ぶときに、ファイル名を期待
	// している箇所があったので、それに対応した。
	function ReadData($type, $opt = NULL) {
		// ファイル名の指定
		$dat = './dat/' . $this->user . '_' . $type . '.dat';
		// if (DEBUG_MODE) { echo '$dat=> ', $dat, "\n"; die(); }
		// ファイルがなかったら空のファイルを作成する
		if (!file_exists($dat)) {
			touch($dat);
			chmod($dat, 0666);
			return null;
		}
		if ($opt === 'f') return $dat;
		// ファイルを配列に取り込む（1行ごと）
		return file($dat);
	}

    /* 
     * WriteData -- ファイルにデータを書き込むメソッド
     *
     * $type -- 'Mention' etc..
     * $data -- array[0] -- $sid (発言IDなど)
     *               [1] -- $text (発言テキスト)
     * */
	function WriteData($type, $data) {
		// ファイル名の指定
		$dat = './dat/' . $this->user . '_' . $type . '.dat';
		// if (DEBUG_MODE) { echo 'WriteData内：$data=> ',$data, "\n";  }
		// ファイルがなかったら空のファイルを作成する
		if (!file_exists($dat)) {
			touch($dat);
			chmod($dat, 0666);
		}
		//ファイルを開いてデータを書き込む
		$fdat = fopen($dat, 'w');
		flock($fdat, LOCK_EX);
		foreach ($data as $line) {
			fputs($fdat, $line . "\n");
		}
		flock($fdat, LOCK_UN);
		fclose($fdat);
	}

	// $sec秒更新のないファイルを削除するメソッド
	function DeleteFile($type, $sec) {
		$dat = glob("./dat/" . $this->user . "_*" . $type . ".dat");
		// if (DEBUG_MODE) { echo '$dat', var_dump($dat), "\n"; }
		foreach ($dat as $k => $v) {
			// if (DEBUG_MODE) { echo '$k=> ', var_dump($k), "\n"; }
			// if (DEBUG_MODE) { echo '$v=> ', var_dump($v), "\n"; }
			if (filectime($v) < time() - $sec) {
				unlink($v);
			}
		}
	}

	// フォロー・リムーブするメソッド
	function Follow($uid, $flg = true) {
		// ユーザーID($uid)をリクエストパラメータにセットする
		$opt = array();
		$opt['user_id'] = $uid;
		$opt['follow'] = true;
		// $flgが「true」ならフォロー、「false」ならリムーブ
		$req = $this->Request("friendships/" . ($flg ? "create" : "destroy")
							  . ".json", "POST", $opt);
		// PHP配列に変換する
		$result = json_decode($req);
		return $result;
	}

	// フォローしているユーザー一覧を取得
	function Friends($uid) {
		$opt = array();
		$opt['user_id'] = $uid;
		$req = $this->Request("friends/list.json", "GET", $opt);
		$result = json_decode($req);
		return $result;
	}

	// フォローされているユーザー一覧を取得
    function Followers($uid) {
        $opt = array();
        $opt['user_id'] = $uid;
        $req = $this->Request("followers/list.json", "GET", $opt);
        $result = json_decode($req);
        return $result;
    }

	// 機嫌値によってプロフィール画像を変更するメソッド
	function ProfileImage() {
		$no = round(($this->emotion->mood + 15) / 6);
		$image = "./img/bot_" . $no . ".png";

		$req = Util::ImageRequest($this->consumer_key,
								  $this->consumer_secret,
								  $this->oauth_token,
								  $this->oauth_token_secret,
								  $image);
		return $no;
	}

	// Dictionaryオブジェクトの Save メソッドにアクセスするためのメソッド
	function Save() {
		$this->dic->Save();
	}

	// 形態素解析をおこなう
	function Morph($input) {
		// 形態素解析の結果を取得する
		$m = new Yahoo_morph();
		$words = $m->Request($input);

		// Studyメソッドにテキストを渡して学習する
		// 引数 $words で形態素解析の結果を渡す
		$this->dic->Study($input, $words);

		$this->save();  // 辞書ファイルの保存
		
	}
	
}
