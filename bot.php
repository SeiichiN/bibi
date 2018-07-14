<?php
/*
 * 発言（ツイート、リプライ）をオウム返しする
 */

/* 初期設定ファイルの読み込み */
require_once("ini.php");

// ログ出力設定
require_once("mylib.php");

// Botクラスの読み込み
require_once("bot_core.php");

// Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret,
				 $access_token, $access_token_secret);

// 最後に取得した発言のIDを取得する
// ./dat/bot_billie_Since.dat を file()関数で読み込んでいる。
// ゆえに返り値は配列である。
$since_id = $myBot->ReadData("Since");
if (DEBUG_MODE) { echo "最後に取得した発言ID=> ", $since_id[0], "\n"; }

// タイムラインの取得（前回最後に取得したつぶやき以降を取得する）
$mentions = $myBot->GetTimeline("home_timeline", $since_id[0]);

// リプライ済みのユーザーを格納する配列の初期化
$replied_users = array();

// ボット相手に返信する上限回数
$reply_limit = 3;

// 無視するユーザーの一覧を取得する
$pass_list = $myBot->ReadData("Pass");
/* $pass_list は配列 */

if (DEBUG_MODE) echo '============== タイムライン => =================', "\n";

// タイムラインの出力
foreach ($mentions as $Timeline) {

	if (DEBUG_MODE) echo "----------------------------------------------------------\n";

	$txt = null;

	// 発言のIDの取得（文字列で取得）
	$sid = $Timeline->id_str;
	// if(DEBUG_MODE){	echo '$sid（発言ID）=> ', $sid, "\n"; }
	
	// ユーザーのスクリーン名の取得
	$screen_name = $Timeline->user->screen_name;
    if (DEBUG_MODE) {
        echo "$screen_name > 「 $Timeline->text 」 \n";
    }
    
	// 送信元の取得
	$source = $Timeline->source;

	// if (DEBUG_MODE) { echo '送信元=> ', $source, "\n"; }
	
// ユーザーIDの取得（文字列で取得）
	$uid = $Timeline->user->id_str;
	// if(DEBUG_MODE){	echo 'ユーザーID($uid)=> ', $uid, "\n";	}
	
	// つぶやき内容の余分なスペースを消し、半角カナを全角カナ、
	// 全角英数を半角英数に変換する
	$text = mb_convert_kana(trim($Timeline->text), "rnKHV", "utf-8");
	
	// ボット自身の発言、RT、QTに反応しないようにする
	if ($screen_name == $user || preg_match("/(R|Q)T( |:)/", $text)) {
		if (DEBUG_MODE) { echo ">>>>> 自分自身の発言 or RT/QT なので、パス \n"; }
		continue;
	}

	// 同じ相手でリプライ済みなら返信しないようにする
	if (in_array($screen_name, $replied_users)) {
		if (DEBUG_MODE) { echo ">>>>> 同じ相手でリプライ済み なので、パス \n"; }
		continue;
	}

	// Webからの投稿以外なら返信カウンタをチェックする
	// つまり、ボットとかそういうのを対象にする
	if (!stristr($source, 'web')) {
		if (DEBUG_MODE) { echo "----------- Webからの投稿以外 --------------------\n"; }
        // if (DEBUG_MODE) {
		// 	echo '$uid=> ', $uid, ' スクリーンname=> ',$screen_name, "\n";
		// 	echo '送信元=> ', $source, "\n";
		// }
        
        // 返信カウンタファイルの内容を配列に読み込む
		$reply_cnt_filename = $myBot->ReadData($uid . "Count", 'f');
		if (DEBUG_MODE) echo '返信カウンタファイル=> ', $reply_cnt_filename, "\n";

		if (!empty($reply_cnt_filename))
			$reply_cnt_file = file($reply_cnt_filename);
		
		if (DEBUG_MODE) { echo '返信カウンタファイルの内容=> ', $reply_cnt_file[0], "\n"; }
				
		$reply_cnt = $reply_cnt_file[0];
		if (!$reply_cnt) {
			$reply_cnt = 0;
		}
		// 上限に達したら
		if ($reply_cnt >= $reply_limit) {
			$reply_cnt_filename = $myBot->ReadData($uid . "Count", 'f');
			// 送信カウンタファイルを削除して、返信処理をスキップする
			unlink($reply_cnt_filename);
			continue;
		}
	}

	if (DEBUG_MODE == 2) {
		echo '$pass_list=> ';
		var_dump($pass_list);
		echo "\n";
	}
	
	// 無視するユーザーIDが一致したら、返信処理をスキップする
	foreach ($pass_list as $p) {
		if ($p == $uid) {
			continue 2;
		}
	}

    
	// 相互フォローしているユーザーの発言、またはボット宛のリプライなら
	if (stristr($text, "@".$user) || strstr($text, "@")) {

	    if (DEBUG_MODE) {
			echo "---------------- 相互フォローの発言 あるいは、ボット宛 ------------------ \n";

			// echo '$user=> ' , $user, 'スクリーン名=> ', $screen_name,  ' $text=> ', $text, "\n";
		}
		
		// 送信する文字列を取得する（現在、パターンによる返事）
		$txt = $myBot->Conversation($text);

		// コマンドプロンプトでの出力確認用
		if (DEBUG_MODE) {
            echo "レスポンス文=> ";
			Util::Debug_print($txt);
		}

		// $txt が空でなかったら送信する
		if ($txt) {
			// $txt を発言する（返事）
			$myBot->Post("@" . $screen_name . " " . $txt, $sid);
			
			$logText = "@" . $screen_name . " " . $txt . " > $text";
			putMsgLog($logText);        // ツイートをログに残す


			echo $screen_name, ' に対して返信> 「', $txt,  "」\n";
			// 返信済みユーザーを配列に記憶する
			$replied_users[] = $screen_name;
			// 返信カウンタを+1して保存する
			$reply_cnt++;
			if (DEBUG_MODE) {
				echo '返信カウンタ=> ', $reply_cnt, "\n";
				// echo '$sid=> ', $sid, "\n";
				// echo '$uid=> ', $uid, "\n";
			}

			$option = array();
			array_push($option, $reply_cnt, $screen_name, $text);
			$myBot->WriteData($uid . "Count", $option);
		}
	}
	
    // コマンドプロンプトでの出力確認用
	/* echo '送信する文字列=>';
	   if (DEBUG_MODE) {
	   Util::Debug_print($text);
	   } else {
	   echo $text . "<br>\n";
	   }*/

}

if (DEBUG_MODE) echo "================== タイムラインの出力終了 ===================== \n";


// if (DEBUG_MODE) { echo '記録前：$sid=> ', $sid, "\n"; }

// 最後に取得した発言のIDをファイルに記録する
$option = array();
array_push($option, $sid, $screen_name, $text);
$myBot->WriteData("Since", $option);

// 返信カウンタは30分（1800秒）更新がなければ削除する
$myBot->DeleteFile("Count", 1800);

// 送信する文字列を設定する
// $txt = "こんにちは";

// 送信する文字列を取得する
// Speaksは、timeかrandomのどちらかである。
// $text = $myBot->Speaks($txt);

// エラーレベルを変更
$level_org = error_reporting();
$level = error_reporting($level_org & ~E_STRICT & ~E_DEPRECATED);
// $level = error_reporting(error_reporting() & ~E_STRICT & ~E_DEPRECATED);

if (DEBUG_MODE) {
	echo '変更前のエラーレベル=> ', $level_org, "\n";
	echo '変更後のエラーレベル=> ', $level, "\n";
}

// エラーレベルをもとに戻す
error_reporting($level_org);

// ツイートを送信する
// if($text){
// 	$myBot->Post($text);
// 	putMsgLog($text);        // ツイートをログに残す
// }

/*
 * ===========================================================
 * フォロー・リムーブ処理
 * ===========================================================
 */
if (DEBUG_MODE) {
	echo '======================== フォロー・リムーブ処理に入ります。==================' . "\n";
}

// 最後に取得したリプライのIDを取得する
$since_id = $myBot->ReadData("Mentions");

if (DEBUG_MODE) { echo '最後に取得したリプライ $since_id=> ', $since_id[0], "\n"; }

// ボット宛のリプライの取得（前回最後に取得したリプライ以降を取得する）
$mentions = $myBot->GetTimeline("mentions_timeline", $since_id[0]);


if (DEBUG_MODE) echo "==============> ボット宛のリプライ処理 <======================\n";

// ボット宛のリプライの処理
foreach ($mentions as $reply) {
	$txt = null;

    if (DEBUG_MODE) echo "-----------------------------------------------------\n";

	// ユーザーのスクリーン名の取得
	$screen_name = $reply->user->screen_name;

	// ボット自身の発言、RT、QTに反応しないようにする
	if ($screen_name == $user || preg_match("/(R|Q)T( |:)/", $text)) {
        if (DEBUG_MODE) echo "スクリーン名（", $screen_name,  "）=> スキップ！\n";
		continue;
    }

    
    // 発言のIDの取得（文字列で取得）
	$sid = $reply->id_str;
	// 送信元の取得
	$source = $reply->source;
	// ユーザーIDの取得（文字列で取得）
	$uid = $reply->user->id_str;
	// ユーザー名
	$name = $reply->user->name;

	// 発言本文の余分なスペースを消し、半角カナを全角カナ、
	// 全角英数を半角英数に変換する
	$text = mb_convert_kana(trim($reply->text), "rnKHV", "utf-8");

    if (DEBUG_MODE) {
        echo "$name ( $screen_name ) > 「 $text 」\n";
//        echo "送信元：", $source, "\n";
//        echo "ユーザー名：", $name, " ID:", $uid, "\n";
    }


    // 「ふぉろーして」という語が含まれていたら
	if(preg_match("/(follow ?|フォロー)して/i", $text) | 
	   preg_match("/(follow ?|フォロー)お願い/i", $text)){
		if (DEBUG_MODE) {
			echo '---------- フォロー処理 ---------------' . "\n";
		}
		// フォローする
		$result = $myBot->Follow($uid, true);

		if (DEBUG_MODE) { echo $result->name, "さんをフォローしました。\n"; }

		if (DEBUG_MODE) { var_dump($result->following); echo "\n"; }
		
		// エラー処理
		if($result->error) {
			// デバッグ用出力
			if (DEBUG_MODE == 1) {
				Util::Debug_print($result->error);
			} elseif (DEBUG_MODE == 2) {
				echo $result->error;
			}
			if (preg_match("/登録されています/", $result->error)) {
				$txt = "もうフォローしていますよ？";
			}
        }
		// エラーでなかったらフォローしたことを知らせるメッセージをせっとする
		else {
			$txt = $name . "さん、よろしくお願いします。";
			if (DEBUG_MODE == 1) {
				Util::Debug_print($name . "さんをフォローしました。");
			} elseif (DEBUG_MODE == 2) {
				echo $name . "さんをフォローしました。<br>\n";
			}
		}
	}

	if (DEBUG_MODE) { echo $txt, "\n"; }
	
	// 「リムーブして」という語が含まれていたら
	if (preg_match("/(remove ?|リムーブ)して/i", $text) |
	    preg_match("/(remove ?|リムーブ)お願い/i", $text)) {
		if (DEBUG_MODE) {
			echo 'リムーブ処理<br>' . "\n";
		}

		// りむーぶする
		$result = $myBot->Follow($uid, false);
        // if (DEBUG_MODE) { var_dump($result); die(); }

        // エラーメッセージの表示
		if ($result->error) {
			// デバッグ用出力
			if (DEBUG_MODE == 1) {
				Util::Debug_print($result->error);
			} elseif (DEBUG_MODE == 2) {
				echo $result->error;
			}
		} else {
			if (DEBUG_MODE == 1) {
				Util::Debug_print($name . "さんをリムーブしました。");
			} elseif (DEBUG_MODE == 2) {
				echo ($name . "さんをリムーブしました。<br>\n");
			}
		}
	}

   
	
	// $txtが空でなかったら、送信する
	if ($txt) {
		$myBot->Post("@" . $screen_name . " " . $txt, $sid);
	}
}

// 最後に取得したリプライのIDをファイルに記録する
$option = array();
array_push($option, $sid, $screen_name, $text);
$myBot->WriteData("Mentions", $option);


