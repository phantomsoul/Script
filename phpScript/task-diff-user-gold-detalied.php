<?php
$mysqli = new mysqli('123.57.141.202','yuffiy','901004az','yihuan');

if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}
$myfile = fopen("task-diff-user-gold-".date('Y-m-d H-i-s')."QQQ.txt","w") or die("Unable to open file!");
$sql = "SELECT `uid` FROM `zq_meihuan_user` ORDER BY uid ASC";
$i = 1;
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $gold_sql = "SELECT `gold` FROM `zq_meihuan_user` WHERE uid={$row['uid']}";
        $gold_detailed_sql = "SELECT SUM(`coin`) as goldsum FROM `zq_gold_detailed` WHERE uid={$row['uid']}";
        $gold_user = "SELECT COUNT(*) as num FROM `zq_meihuan_user`";
        $gold_result = $mysqli->query($gold_sql);
        $gold_row = $gold_result->fetch_assoc();
        $gold_detailed_query = $mysqli->query($gold_detailed_sql);
        $gold_detailed_row = $gold_detailed_query->fetch_assoc();
        $gold_user_result = $mysqli->query($gold_user);
        $gold_user_row = $gold_user_result->fetch_assoc();
        if ($gold_row['gold'] != 0 || $gold_detailed_row['goldsum'] != '') {
            if ($gold_row['gold'] !== $gold_detailed_row['goldsum']) {
                $errorstr = 'user='.$row['uid'].'  '.'gold:'.$gold_row['gold'].'  goldsum: '.$gold_detailed_row['goldsum'].PHP_EOL;
                fwrite($myfile, $errorstr);
            } else {
                echo $i.'/'.$row['uid'].'/'.$gold_user_row['num'].'complete::correct'.PHP_EOL;
            }
        } else {
            echo $row['uid'].'/'.$gold_user_row['num'].'@@'.$gold_row['gold'].'##'.$gold_detailed_row['goldsum'].'@@'.PHP_EOL;
        }
        $i++;
    }
}
$result->free();
$mysqli->close();