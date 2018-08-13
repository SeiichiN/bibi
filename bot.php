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

$specialUsers = 'kuon5505 / saigonohito';

// Botオブジェクトの生成
$myBot = new Bot($user, $consumer_key, $consumer_secret,
				 $access_token, $access_token_secret);

// 最後に取得した発言のIDを取得する
// ./dat/bot_billie_Since.dat を file()関数で読み込んでいる。
// ゆえに返り値は配列である。
$since_id = $myBot->ReadData("Since");
if (DEBUG_MODE) { echo "前回の最後に取得した発言ID=> ", $since_id[0], "\n"; }

// タイムラインの取得（前回最後に取得したつぶやき以降を取得する）
$mentions = $myBot->GetTimeline("home_timeline", trim($since_id[0]));

// if (DEBUG_MODE) {var_dump($mentions); echo "\n"; die(); }

// リプライ済みのユーザーを格納する配列の初期
$replied_users = array();

// ボット相手に返信する上限回数
$reply_limit = 3;

// 無視するユーザーの一覧を取得する
$pass_list = $myBot->ReadData("Pass");
/* $pass_list は配列 */

if (DEBUG_MODE) echo '============== タイムライン => =================', "\n";

$textarry = array();

// タイムラインの出力
foreach ($mentions as $Timeline) {

	if (DEBUG_MODE) echo "----------------------------------------------------------\n";

	$txt = null;  // こちらが発言する内容を格納する変数

	$specialUser = 'no';
	
	// 発言のIDの取得（文字列で取得）
	$sid = $Timeline->id_str;
	// if(DEBUG_MODE){	echo '$sid（発言ID）=> ', $sid, "\n"; }
	
	// ユーザーのスクリーン名の取得
	$screen_name = $Timeline->user->screen_name;
    if (DEBUG_MODE) {
        echo "$screen_name > 「 $Timeline->text 」 \n";
    }

	// このユーザーの発言には、レスポンスをつける。（辞書で学習する）
	if (preg_match("/{$screen_name}/", $specialUsers)) {
		$specialUser = 'yes';
	}
    
	// 送信元の取得
	$source = $Timeline->source;

	// if (DEBUG_MODE) { echo '送信元=> ', $source, "\n"; }
	
    // ユーザーIDの取得（文字列で取得）
	$uid = $Timeline->user->id_str;
	// if(DEBUG_MODE){	echo 'ユーザーID($uid)=> ', $uid, "\n";	}

	// ユーザーのつぶやきを取得
	// つぶやき内容の余分なスペースを消し、半角カナを全角カナ、
	// 全角英数を半角英数に変換する
	$text = mb_convert_kana(trim($Timeline->text), "rnKHV", "utf-8");

	// 発言の冒頭言葉の配列をつくる
	array_push($textarry, substr($text, 0, 10));
	
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
        
        // 返信カウンタファイル名を取得する
		$reply_cnt_filename = $myBot->ReadData($uid . "Count", 'f');
		if (DEBUG_MODE) echo '返信カウンタファイル=> ', $reply_cnt_filename, "\n";

        // 返信カウンタファイルの内容を配列に読み込む
		if (!empty($reply_cnt_filename))
			$reply_cnt_file = file($reply_cnt_filename);
		
		if (DEBUG_MODE) { echo '返信カウンタファイルの内容=> ', $reply_cnt_file[0], "\n"; }
				
		$reply_cnt = $reply_cnt_file[0];
		if (!$reply_cnt) {
			$reply_cnt = 0;
		}
		// 上限に達したら
		if ($reply_cnt >= $reply_limit) {
			// $reply_cnt_filename = $myBot->ReadData($uid . "Count", 'f');
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

    
	// 相互フォローしているユーザーの発言、またはボット宛のリプライなら、特別ユーザーなら
	if (stristr($text, "@".$user) || strstr($text, "@") || $specialUser === 'yes') {

	    if (DEBUG_MODE) {
			echo "---------------- 相互フォローの発言 あるいは、ボット宛、特別ユーザー  ------------------ \n";

			echo '>>> スクリーン名=> ', $screen_name,  ' $text=> ', $text, "\n";
		}

		// 現在の機嫌値をファイルから読み込んでセットする
		if (MOOD_MODE) { $myBot->emotion->User_mood($uid); }
		
		// 送信する文字列を取得する（いろんな辞書をランダムに切り替える）
		$txt = $myBot->Conversation($text, $uid, $user);  // <-- p122  $user となっているけど、こっちでは？
		

		// コマンドプロンプトでの出力確認用
		if (DEBUG_MODE) {
			if (empty($txt)) echo "レスポンス文=> （空） \n";
            else echo "レスポンス文=> ";
			Util::Debug_print($txt);
		}

		// $txt が空でなかったら送信する
		if ($txt) {
			// $txt を発言する（返事）
			$myBot->Post("@" . $screen_name . " " . $txt, $sid);
			
			$logText = "@{$screen_name}(SpecialUser='{$specialUser}') に対して返信： {$txt}\n";
			putMsgLog($logText);        // ツイートをログに残す


			if (DEBUG_MODE) echo "\n", $screen_name, ' に対して返信> 「', $txt,  "」\n\n";
			
			// 返信済みユーザーを配列に記憶する
			$replied_users[] = $screen_name;
			// 返信カウンタを+1して保存する
			$reply_cnt++;
			if (DEBUG_MODE) {
				echo '返信カウンタ=> ', $reply_cnt, "\n";
			}

			$option = array();
			array_push($option, $reply_cnt, $screen_name, $text);
			$myBot->WriteData($uid . "Count", $option);
		}
	}
	
}

if (DEBUG_MODE) echo "================== タイムラインの出力終了 ===================== \n";


// プロフィール画像を更新する
$no = $myBot->ProfileImage();

if (DEBUG_MODE) { echo ">>>>> プロファイル画像を bot_{$no}.png に変更したよ。<<<<< \n"; }

// 最後に取得した発言のIDをファイルに記録する
/* $option = array();
 * array_push($option, $sid, $screen_name, $text);
 * $myBot->WriteData("Since", $option);
 * */
if (!empty($sid)) {
    $option = array();
    array_push($option, $sid, $screen_name, $text);
    $myBot->WriteData("Since", $option);
    
    if (DEBUG_MODE) { echo "--- 最後に取得した発言ID \n --- $sid \n --- $screen_name \n --- $text \n"; }
} else {
    if (DEBUG_MODE) { echo "新しく取得した発言はありませんでした。\n"; }
}


// 返信カウンタは30分（1800秒）更新がなければ削除する
$myBot->DeleteFile("Count", 1800);

// ================================== 必ずおこなう処理 =============================

// 送信する文字列を設定する
// $txt = "こんにちは";

// つぶやきの先頭10文字の配列のどれかを rand で取得
$txt = $textarry[rand(0, count($textarry) - 1)];
if (DEBUG_MODE) { echo ">>>>> このつぶやきに反応=> ", $txt, "\n";}

// 送信する文字列を取得する
// Speaksは、time/randomに設定してある。
//$mytxt = $myBot->Speaks($txt);
$mytxt = $myBot->Conversation($txt);
if (DEBUG_MODE) {echo ">>>>> これを発言=> ", $mytxt, "\n";}


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
if($mytxt){
 	$myBot->Post($mytxt);
 	putMsgLog($mytxt);        // ツイートをログに残す
}

// ================================== 必ずおこなう処理 終了 ========================


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

if (DEBUG_MODE) { echo '前回の最後に取得したリプライ $since_id=> ', $since_id[0], "\n"; }

// ボット宛のリプライの取得（前回最後に取得したリプライ以降を取得する）
$mentions = $myBot->GetTimeline("mentions_timeline", trim($since_id[0]));


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

        $followList = $myBot->Friends($uid);
        foreach ($followList->users as $list) {
            if ($list->id_str == $uid) {
                $txt = "@" . $screen_name . "さん、もうフォローしてますけど。";
                if (DEBUG_MODE) { echo "$screen_name さんはもうフォローしてました。\n"; }
                continue 2;
            }
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

        $follow = 0;
        $followList = $myBot->Friends($uid);
        foreach ($followList->users as $list) {
            if ($list->id_str == $uid) {
                $follow = 1;
            }
        }

        if ($follow == 0) {
            $txt = "@" . $screen_name . "さん、フォローしてませんけど。";
            if (DEBUG_MODE) { echo "$screen_name さんはフォローしてないので、リムーブしませんでした。\n"; }
            continue;
        }
        
		// りむーぶする
		if ($follow) $result = $myBot->Follow($uid, false);
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

        $logText = "@" . $screen_name . " " . $txt . "\n";
		putMsgLog($logText);        // ツイートをログに残す

	}
}

// 最後に取得したリプライのIDをファイルに記録する
/* $option = array();
 * array_push($option, $sid, $screen_name, $text);
 * $myBot->WriteData("Mentions", $option);
 * */

if (!empty($sid)) { 
    $option = array();
    array_push($option, $sid, $screen_name, $text);
    $myBot->WriteData("Mentions", $option);
    
    if (DEBUG_MODE) { echo "--- 最後に取得したリプライのID \n --- $sid \n --- $screen_name \n --- $text \n"; }
} else {
    if (DEBUG_MODE) { echo "リプライはありませんでした。\n"; }
}

