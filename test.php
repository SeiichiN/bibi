<?php // マルコフ辞書

/*
  Usage:  $ php test.php anotokino_ojikun.txt

*/

require_once("markov.php");
require_once("morpheme.php");
require_once("util.php");
require_once("mylib.php");

// オブジェクト生成
$xml = new Yahoo_morph();
$markov = new Markov();

$file = $argv[1];

echo '$file=> ', $file, "\n";


if (!file_exists($file)) {
	$msg = "$file が開けません";
	putErrLog($msg);
	die($msg);
}

$f = file($file);

// 指定したファイルを1行ずつ読み込んで辞書に学習する
foreach ($f as $line) {

	$text = mb_convert_encoding(rtrim($line, "\n"), "UTF-8", "auto");

	if(empty($text)) { continue; }

	$markov->Add_Sentence($xml->Request($text));
	print ".";
}

print "\n";

// コマンドラインから入力した文章を解析してキーワードを抽出し、
// キーワードをもとにマルコフ連鎖で文章を生成する。
set_time_limit(0);
($stdin = fopen("php://stdin", "r")) || die("Cannot open stdin.");

while(1) {
	print ">";
	$input = trim(fgets($stdin, 256));
	if ($input == "exit") {
		$markov->Save();
		break;
	}
	$text = mb_convert_encoding($input, "UTF-8", "auto");
	$words = $xml->Request($text);  // 形態素解析
	$keywords = array();
	// キーワード（名詞）の抽出
	foreach ($words as $v) {
		if (preg_match("/名詞/", $v->pos)) {
			array_push($keywords, $v->surface);
		}
	}
	// キーワードから文章を生成して表示する
	if (count($keywords)) {
		$keyword = $keywords[rand(0, count($keywords) - 1)];
		$res = $markov->Generate(chop($keyword));
		print mb_convert_encoding(">".$res, "UTF-8", "auto") . "\n";
	}
}

	