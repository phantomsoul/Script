<?php
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
//$conn = mysqli_connect('localhost', 'root', 'root', 'yihuan');
if (!$conn) {
    die('连接错误:' . mysqli_connect_error());
}
$myfile = fopen('task-id-discontinuity-'.date('Y-m-d H-i-s').'.txt','w')or die('Unable to open file!');
date_default_timezone_set('Asia/Shanghai');
for ($i = 1;$i <= 32940; $i++) {
$sql = "SELECT uid FROM zq_meihuan_user WHERE uid = $i ORDER BY uid ASC";
$result = mysqli_query($conn, $sql);
    if (!$result->num_rows) {
        echo $r = $i.PHP_EOL;
        fwrite($myfile, $r);
    } else {
        echo $i.'true'.PHP_EOL;
    }
}
fclose($myfile);
$conn->close();
