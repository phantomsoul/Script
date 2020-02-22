<?php 
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) { 
    die("连接错误: " . mysqli_connect_error()); 
}
$sql  = "SELECT `uid` FROM `zq_meihuan_user` ORDER BY uid ASC";
$arr = [];
$str = '';
$result = mysqli_query($conn, $sql);
$myfile = fopen("task-diff-user-gold-".date('Y-m-d H-i-s').".txt","w") or die("Unable to open file!");
while ($row = mysqli_fetch_assoc($result)) {
	$gold_sql = "SELECT `gold` FROM `zq_meihuan_user` WHERE uid={$row['uid']}";
	$gold_detailed_sql = "SELECT SUM(`coin`) as goldsum FROM `zq_gold_detailed` WHERE uid={$row['uid']}";
	$gold_user = "SELECT COUNT(*) as num FROM `zq_meihuan_user`";
	$gold_result = mysqli_query($conn, $gold_sql);
	$gold_row = mysqli_fetch_assoc($gold_result);
	$gold_detailed_sql = mysqli_query($conn, $gold_detailed_sql);
	$gold_detailed_row = mysqli_fetch_assoc($gold_detailed_sql);
    $gold_user_result = mysqli_query($conn, $gold_user);
	$gold_user_row = mysqli_fetch_assoc($gold_user_result);
    if ($gold_row['gold'] != 0 || $gold_detailed_row['goldsum'] != '') {
        if ($gold_row['gold'] !== $gold_detailed_row['goldsum']) {
            echo $row['uid'].'/'.$gold_user_row['num'].'complete::error'.PHP_EOL;
            $errorstr = 'user='.$row['uid'].'  '.'gold:'.$gold_row['gold'].'  goldsum: '.$gold_detailed_row['goldsum'].PHP_EOL;
            fwrite($myfile,$errorstr);
	    } else {
	        echo $row['uid'].'/'.$gold_user_row['num'].'complete::correct'.PHP_EOL;
    	}
    } else {
        echo $row['uid'].'/'.$gold_user_row['num'].'@@'.$gold_row['gold'].'##'.$gold_detailed_row['goldsum'].'@@'.PHP_EOL;
    }
}
fclose($myfile);
$conn->close();
?>
