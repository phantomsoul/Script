<?php 
$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
if (!$conn) {
    die('connection error:' . mysqli_connect_errno());
}
mysqli_query($conn, "set character set 'utf8'");//读库   
mysqli_query($conn, "set names 'utf8'");//写库 
$sql = "SELECT order_id, uid, complate_time, status, total FROM zq_order WHERE shipping_method = 'express' AND status = 4 AND order_id BETWEEN 7500 AND 8000";
$result = mysqli_query($conn, $sql);
//$myfile = fopen("order-complete-recycle-detail".date('Y-m-d').".txt","w") or die("Unable to open file!");
while ($value = mysqli_fetch_assoc($result)) {
    $sql = "SELECT nickname FROM zq_meihuan_user WHERE uid = {$value['uid']}";
    $query = mysqli_query($conn, $sql);
    while ($User = mysqli_fetch_assoc($query)) {
        $username = $User['nickname'];
    }
    $sql = "SELECT relatedid, amount, count(*) as count FROM zq_merchant_detailed WHERE relatedid = {$value['uid']} AND amount = {$value['total']}";
    $query = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($query)) {
        if ($row['count'] !== '1') {
            $errorstr = $value['order_id'].PHP_EOL;
            //fwrite($myfile,$errorstr);
            $insert = "INSERT INTO zq_merchant_detailed(mer_id, order_id, relatedid, operation, amount, type, description, dateline) VALUES (1, {$value['order_id']}, {$value['uid']}, 'RCV', {$value['total']}, 0, '$username下单获得{$value['total']}金币')";
            echo $insert, PHP_EOL;
        } else {
            echo $value['order_id']."/correct",PHP_EOL;
        }
    }
    continue;
}
echo 'The query result is empty!', PHP_EOL;
