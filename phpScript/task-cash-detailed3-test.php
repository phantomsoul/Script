<?php
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) { 
    die("连接错误: " . mysqli_connect_error()); 
}
$sql  = "SELECT order_id from zq_order where store_id = 2 and status = 4 and complate_time >= 1478326582";
$arr = [];
$str = '';
$result = mysqli_query($conn, $sql);
$myfile = fopen("cash_detailed.txt","w") or die("Unable to open file!");
while ($row = mysqli_fetch_assoc($result)) {
	$cash_sql = "SELECT COUNT(*) as number FROM `zq_cash_detailed` WHERE order_id={$row['order_id']}";
	$cash_result = mysqli_query($conn, $cash_sql);
	$cash_row = mysqli_fetch_assoc($cash_result);
	if ($cash_row['number'] === '3') {
		//file_put_contents('diff_gold.'.txt,'complete'.'/'.$row['order_id'].'/3');
		echo 'complete'.'/'.$row['order_id'].'/3', PHP_EOL;
	} elseif($cash_row['number'] === '2') {
		fwrite($myfile, 'error'.'/'.$row['order_id'].'/2'.PHP_EOL);
		//file_put_contents('diff_gold.'.txt,'error'.'/'.$row['order_id'].'/2');
	    echo 'error'.'/'.$row['order_id'].'/2', PHP_EOL;
	} elseif($cash_row['number'] === '1') {
		fwrite($myfile, 'error'.'/'.$row['order_id'].'/1'.PHP_EOL);
		echo 'error'.'/'.$row['order_id'].'/1', PHP_EOL;
	} else {
		//fwrite($myfile, 'error'.'/'.$row['order_id'].'/4'.PHP_EOL);
		//file_put_contents('diff_gold.'.txt,'error'.'/'.$row['order_id'].'/1');
		echo 'error'.'/'.$row['order_id'].'/4', PHP_EOL;
	}
}
// echo join("",$str);
// echo $str;
//file_put_content('diff_gold_'.time().txt,$str);
fclose($myfile);
$conn->close();
?>
