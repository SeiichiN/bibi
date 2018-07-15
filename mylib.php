<?php
// namespace billiesworks

function exception_handler($exception) {
	$logtime = date('Y-m-d_His');
	$log = 'log/error_' . $logtime . '.log';
	
	$msg = "捕捉できない例外: " . $exception->getMessage();
	$msg = $msg . "(File: " . $exception->getFile() . ")";
 	$msg = $msg .  "(Line: " . $exception->getLine() . ")\n";
	sleep(1);
	return error_log($msg, 3, $log);
	// exit();
}

set_exception_handler('exception_handler');


function putMsgLog($msg) {
	$logtime = date('Y-m-d_His');
	$log = 'log/msg_' . $logtime . '.log';
	$msg = 'ツイート:' . $msg . "\n";
	return error_log($msg, 3, $log);
}
	
function putErrLog($msg) {
	$logtime = date('Y-m-d_His');
	$log = 'log/error_' . $logtime . '.log';
	$msg = 'エラー:' . $msg . "\n";
	return error_log($msg, 3, $log);
}
	

