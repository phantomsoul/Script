<?php
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) {
    die('连接错误：'. mysqli_connect_errno());
}
date_default_timezone_set('PRC');
$sql = "SELECT from_id FROM zq_maker where dateline < 1490889600 ORDER BY id";
//$sql = "SELECT from_id FROM zq_maker where from_id = 12206 ORDER BY id";
$result = mysqli_query($conn, $sql);
$myfile = fopen("task-maker-crosswise-num-".date('Y-m-d H-i-s').".txt", "w") or die("Unable to open file!");
$i = 0;
$num3 = 0;
while ($row = mysqli_fetch_assoc($result)) {
    //echo $row['from_id'], PHP_EOL;
    $sql1 = "SELECT from_id, realname, mobile, ultimo, (select sum(cash) from zq_cash_detailed where uid = {$row['from_id']} and operation = 'FRP' and dateline between 1488297600 and 1490889600) as frozen_maker, (SELECT count(id) FROM zq_maker WHERE shareid = {$row['from_id']} and dateline < 1490889600) as num FROM zq_meihuan_user,zq_maker WHERE from_id = {$row['from_id']} and from_id=uid and dateline < 1490889600 ORDER BY num DESC";
    $result1 = mysqli_query($conn, $sql1);
    while ($row1 = mysqli_fetch_assoc($result1)) {
        if ($row1['num'] != 0) {
            $sql2 = "SELECT from_id FROM zq_maker WHERE shareid = {$row1['from_id']} and dateline < 1490889600";
            $result2 = mysqli_query($conn, $sql2);
$num3 = 0;
            while ($row2 = mysqli_fetch_assoc($result2)) {
                $sql3 = "SELECT COUNT(from_id) as num2 FROM zq_maker WHERE shareid = {$row2['from_id']} and dateline < 1490889600";
                $result3 = mysqli_query($conn, $sql3);
                while ($row3 = mysqli_fetch_assoc($result3)) {
                    $num3 += $row3['num2'];
                }
            }
          echo $res1 = ++$i ."\t".$row1['from_id']."\t".$row1['realname']."\t".$row1['mobile']."\t".$row1['num']."\t".$num3."\t".$row1['ultimo']."\t".$row1['frozen_maker']."\t\n";
          fwrite($myfile, $res1);
          unset($num3);
       }
    }
}
fclose($myfile);
$conn->close();
