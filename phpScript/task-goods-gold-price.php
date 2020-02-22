<?php 
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) { 
    die("连接错误: " . mysqli_connect_error()); 
}
$sql  = "SELECT `goods_id`,`gold`,`price` FROM `zq_goods` WHERE sale_type=0 ORDER BY goods_id ASC";
$arr = [];
$str = '';
$amount = [];
$result = mysqli_query($conn, $sql);
$myfile = fopen("task-goods-gold-price".date('Y-m-d H-i-s').".txt","w") or die("Unable to open file!");
while ($row = mysqli_fetch_assoc($result)) {
    $goods = "SELECT COUNT(*) as num FROM `zq_goods`";
    $goods_result = mysqli_query($conn, $goods);
	$goods_row = mysqli_fetch_assoc($goods_result);
    $ratio = $row['gold'] / $row['price'];
    if (!in_array($ratio, array(5.8, 6))) {
		fwrite($myfile,'ERROR'.'/'.$row['goods_id'].'/'. $ratio .PHP_EOL);
		echo 'ERROR'.'/'.$row['goods_id'].'/'.($row['gold'] / $row['price']).'/'.$goods_row['num'], PHP_EOL;
	} else {
	//	//fwrite($myfile, 'error'.'/'.$row['order_id'].'/1'.PHP_EOL);
		echo 'CORRECT'.'/'.$row['goods_id'].'/'.($row['gold'] / $row['price']).'/'.$goods_row['num'], PHP_EOL;
	}
}
fclose($myfile);
$conn->close();
?>
