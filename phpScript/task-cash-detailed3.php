<?php
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) { 
    die("连接错误: " . mysqli_connect_error()); 
}

$sql  = "SELECT `order_id` FROM `zq_cash_detailed` WHERE uid=992 GROUP BY order_id ASC";
$arr = [];
$str = '';
$result = mysqli_query($conn, $sql);
$myfile = fopen("task-cash-detailed-3".date('Y-m-d').".txt","w") or die("Unable to open file!");
while ($row = mysqli_fetch_assoc($result)) {
	$cash_sql = "SELECT COUNT(*) as number FROM `zq_cash_detailed` WHERE order_id={$row['order_id']}";
	$cash_result = mysqli_query($conn, $cash_sql);
	$cash_row = mysqli_fetch_assoc($cash_result);
	if ($cash_row['number'] === '3') {
		//fwrite($myfile, 'complete'.'/'.$row['order_id'].'/3'.PHP_EOL);
		echo 'complete'.'/'.$row['order_id'], PHP_EOL;
	} elseif($cash_row['number'] === '2') {
		//fwrite($myfile, 'error'.'/'.$row['order_id'].'/2'.PHP_EOL);
	    echo 'error'.'/'.$row['order_id'].'/2', PHP_EOL;
	} else {
		//fwrite($myfile, 'error'.'/'.$row['order_id'].'/1'.PHP_EOL);
		echo 'error'.'/'.$row['order_id'].'/1', PHP_EOL;
	}
}
fclose($myfile);
$conn->close();
?>
