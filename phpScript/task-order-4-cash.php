<?php 
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) { 
    die("连接错误: " . mysqli_connect_error()); 
}
$sql  = "SELECT `uid`,`order_id`,`total`,`status` FROM `zq_order` WHERE `status`=4 AND complate_time!='0' ORDER BY order_id ASC";
$arr = [];
$str = '';
$amount = [];
$result = mysqli_query($conn, $sql);
$myfile = fopen("task-order-4-cash-".date('Y-m-d').".txt","w") or die("Unable to open file!");
while ($row = mysqli_fetch_assoc($result)) {
	$cash_sql = "SELECT COUNT(*) as number FROM `zq_cash_detailed` WHERE order_id={$row['order_id']}";
	$cash_result = mysqli_query($conn, $cash_sql);
	$cash_row = mysqli_fetch_assoc($cash_result);

    if ($cash_row['number'] !== '3') {
		//fwrite($myfile, 'complete'.'/'.$row['order_id'].'/3'.PHP_EOL);
		echo 'ERROR'.'/'.$row['order_id'], PHP_EOL;
	} else {
		//fwrite($myfile, 'error'.'/'.$row['order_id'].'/1'.PHP_EOL);
		echo 'complete'.'/'.$row['order_id'], PHP_EOL;
	}
}
fclose($myfile);
$conn->close();
?>
