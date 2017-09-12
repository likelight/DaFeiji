<?php


define("LOG_PATH", "luck_error.log");
header('Content-Type', 'application/json');
date_default_timezone_set('Asia/Shanghai');
function db_connect() {
	// $host = 'localhost';
	$host = "phdicampdbcs.chinacloudapp.cn";
	// $host = "192.168.10.10";
	$user = "root";

	// $password = "TJ@d2c2014";
	$password = "passw0rd@WSX";
	// $password = "root";
	$db = "flight";
	$conn = @mysql_connect($host, $user, $password);
	mysql_select_db($db, $conn);
	mysql_query("SET NAMES 'utf8'", $conn);
	return $conn;
}

function is_open() {
	$now = new Datetime();
	$today = $now->format('Ymd');
	$row = mysql_query("SELECT * FROM `records` WHERE `date`=$today");
	if (!mysql_fetch_row($row)) {
		$past = $now->sub(new DateInterval('P1D'));
		$yesterday = $past->format('Ymd');
		$row = mysql_query("SELECT * FROM `records` WHERE `date`=$yesterday");
		$result = mysql_fetch_array($row);
		$left = $result ? $result['left'] + 1 : 1;
		$result = mysql_query("INSERT INTO `records` VALUES ('', 0, $left, $left, '$today')");
		if (!$result) throw new Exception("数据库操作异常（Add Record）");
	}
}

function get_count() {
	is_open();
	$now = new Datetime();
	$today = $now->format('Ymd');
	$row = mysql_query("SELECT `count` FROM `records` WHERE `date`=$today");
	$result = mysql_fetch_array($row);
	return $result['count'];
}

function roll($left) {
	// return true;
	return false;
	$number = rand(0, 1000);
	return ($number <= 5 * $left);
	// return ($number <= 1000);
}


function custom_error_log($str) {
	$datetime = new Datetime();
	$now = $datetime->format('Y-m-d H:i:s');
	file_put_contents(LOG_PATH, "$now - $str\n", FILE_APPEND);
}

function validate($id, $phone, $name, $id_card, $token) {
	if (!(preg_match("/^\\d{11}$/", $phone) && preg_match("/^[\\x{4e00}-\\x{9fa5}\\w]{2,12}$/u", $name)
		&& preg_match("/^\\w{18}$/", $id_card) && preg_match("/^\w{2,50}$/", $id) && preg_match("/^\w{32}$/", $token))) {
		return false;
	}
	$result = mysql_query("SELECT * FROM `winners` WHERE `token`='$token' AND `user_id`='$id'");
	if (!$result) return false;
	$row = mysql_fetch_array($result);
	if (!$row || $row['state'] != 'pending') return false;
	return true;
}

$action = @$_GET['action'];
// header('Content-Type: application/json');


switch ($action) {
	case 'mock':
		$conn = db_connect();

		$channel = 'h5';
		for($i = 0, $len = 15; $i < $len; $i++) {
			$time = date('Y-m-d', strtotime('2016-07-04') + $i * 24 * 60 * 60);
			mysql_query("INSERT INTO `config` (`date`, `49`, `10`, `free`, `channel`) VALUES ('$time', '400', '20000', '100', '$channel')");
		}
		for($i = 0, $len = 13; $i < $len; $i++) {
			$time = date('Y-m-d', strtotime('2016-07-19') + $i * 24 * 60 * 60);
			mysql_query("INSERT INTO `config` (`date`, `49`, `10`, `free`, `channel`) VALUES ('$time', '153', '7692', '38', '$channel')");
		}

		$channel = 'tv';
		for($i = 0, $len = 15; $i < $len; $i++) {
			$time = date('Y-m-d', strtotime('2016-07-11') + $i * 24 * 60 * 60);
			mysql_query("INSERT INTO `config` (`date`, `49`, `10`, `free`, `channel`) VALUES ('$time', '266', '13333', '66', '$channel')");
		}
		for($i = 0, $len = 34; $i < $len; $i++) {
			$time = date('Y-m-d', strtotime('2016-07-26') + $i * 24 * 60 * 60);
			mysql_query("INSERT INTO `config` (`date`, `49`, `10`, `free`, `channel`) VALUES ('$time', '117', '5882', '29', '$channel')");
		}

		/*for($i = 0, $len = 60; $i < $len; $i++) {
			$num_49 = 10;
			$num_10 = 10;
			$num_free = 10;
			$time = date('Y-m-d', time() + $i * 24 * 60 * 60);
			foreach($channels as $channel) {
				$result = mysql_query("INSERT INTO `config` (`date`, `49`, `10`, `free`, `channel`) VALUES ('$time', '$num_49', '$num_10', '$num_free', '$channel')");
			}

		}*/

		break;
	case 'redeem':
		$conn = db_connect();
		$id = @$_GET['id'];
		$type = @$_GET['type']; // 10, 49, free
		$channel = @$_GET['channel'];// tv or h5
		$date = new Datetime();
		$today = $date->format('Y-m-d');
		$row = mysql_fetch_array(mysql_query("SELECT * FROM `record` WHERE `user_id`='$id' and `date`='$today' and `channel`='$channel'"));
		if (!$row) {
			$config = mysql_fetch_array(mysql_query("SELECT * FROM `config` where `date`='$today' and `channel`='$channel'"));
			// var_dump("SELECT * FROM `config` where `date`='$today' and `channel`='$channel'");
			// var_dump($config);
			if($config[$type] <= 0) {
				// 配额已经用完
				echo json_encode(['result' => 'success', 'reason' => '配额已经用完', 'code' => -1]);
			} else {
				// 还有配额
				$newNum = $config[$type] - 1;
				mysql_query("UPDATE `config` SET `$type`='$newNum' WHERE (`date`='$today' and `channel`='$channel')");
				mysql_query("INSERT INTO `record` (`user_id`, `date`, `type`, `channel`) VALUES ('$id', '$today', '$type', '$channel')");

				echo json_encode(['result' => 'success', 'reason' => "可以领奖", 'code' => 1]);

			}
		} else if ($row['isDraw'] == 0) {
			// 已经领过奖了,但是没有真正领奖

			echo json_encode(['result' => 'success', 'reason' => '已经领过奖了，但是没有真正领奖', 'code' => -2, 'type' => $row['type']]);
		} else if ($row['isDraw'] == 1){
			// 已经领过奖了，也真正领过奖了
			echo json_encode(['result' => 'success', 'reason' => '已经领过奖了，也真正领过奖了', 'code' => -3, 'type' => $row['type']]);

		}
		break;
	case 'callback':
		$conn = db_connect();
		$id = @$_GET['id'];
		$type = @$_GET['type']; // 10, 49, free
		$channel = @$_GET['channel'];// tv or h5
		$date = @$_GET['date'];
		$status = @$_GET['status'];
		if ($status == '0') {
			mysql_query("UPDATE `record` SET `isDraw`=1 WHERE (`date`='$date' and `channel`='$channel' and `type`='$type' and `user_id`='$id')");
			error_log(date('Y-m-d H:i:s')."         id: {$id},type: {$type},channel: {$channel}, status: $status", 3, './logs/flight-'.date('Y-m-d').'.log');
		} else {
			echo(date('Y-m-d H:i:s')."    领取失败!!!!     id: {$id},type: {$type},channel: {$channel}, status: $status");
			echo './flight-'.date('Y-m-d').'.log';
			error_log(date('Y-m-d H:i:s')."    draw_fail     id: {$id},type: {$type},channel: {$channel}, status: $status", 3, './logs/flight-'.date('Y-m-d').'.log');
		}


}