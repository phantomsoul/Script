<?php 
//$conn = mysqli_connect('123.57.141.202', 'yuffiy', '901004az', 'yihuan', '3306');
//$conn = mysqli_connect('192.168.1.32', 'test', 'root', 'test', '3306');
$conn = mysqli_connect('localhost', 'root', 'root', 'yihuan', '3306');
if (!$conn) {
    die('connection error:' . mysqli_connect_errno());
}
mysqli_query($conn, "set character set 'utf8'");//读库   
mysqli_query($conn, "set names 'utf8'");//写库 
$limit_time = 604700;
$sql = "SELECT order_id, order_no, goods_id, store_id, uid, pro_count, sent_time, status, total FROM zq_order WHERE shipping_method = 'express' AND status = 3 AND sent_time != 0 ORDER BY sent_time ASC";
$rowResult = [];
$now = time();
$result = mysqli_query($conn, $sql);
while ($value = mysqli_fetch_assoc($result)) {
    if (empty($value)) {
        echo 'The query result is empty!', PHP_EOL;
        continue;
    }
    $limit = $value['sent_time'] + $limit_time;
    if ($now >= $limit) {
        mysqli_query($conn, "START TRANSACTION");
        $update = "UPDATE zq_order SET status = 4, complate_time = $now WHERE order_id = {$value['order_id']}";
        $query = mysqli_query($conn, $update);
        if(!mysqli_affected_rows($conn)) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Change order status failed!', PHP_EOL;
            continue;
        }
        $select = "SELECT pro_price FROM zq_order_goods WHERE order_id = {$value['order_id']}";
        $query = mysqli_query($conn, $select);
        $money = '';
        while ($row = mysqli_fetch_assoc($query)) {
            $money = $row['pro_price'] * $value['pro_count'];
        }
        $select = "SELECT uid FROM zq_store WHERE store_id = {$value['store_id']}";
        $query = mysqli_query($conn, $select);
        while ($row = mysqli_fetch_assoc($query)) {
            $store_uid = $row['uid'];
        }
        $select = "SELECT frozen FROM zq_meihuan_user WHERE uid = $store_uid";
        $query = mysqli_query($conn, $select);
        while ($row = mysqli_fetch_assoc($query)) {
            $frozen = $row['frozen'];
        }
        if ($frozen < $money) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Merchants freeze amount is less than the goods in cash!', PHP_EOL;
            continue;
        }
        $update = "UPDATE zq_meihuan_user SET `frozen` = `frozen` - $money WHERE uid = $store_uid";
        $query = mysqli_query($conn, $update);
        if (!mysqli_affected_rows($conn)) {
            mysqli_query($conn,"ROLLBACK");
            echo 'Merchants freeze amount failure!', PHP_EOL;
            continue;
        }
        $insert = "INSERT INTO zq_cash_detailed(uid, order_id, order_no, relatedid, operation, cash, description, dateline, IP)
            VALUES ($store_uid, {$value['order_id']}, {$value['order_no']}, {$value['uid']}, 'FRE', -$money, '冻结金额转换可提现金额，扣除冻结金额{$money}元', $now, '0.0.0.0')";
        if (!mysqli_query($conn, $insert)) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Add the businessman amount frozen failure for details!', PHP_EOL;
            continue;
        }
        $update = "UPDATE zq_meihuan_user SET `now_money` = `now_money` + $money WHERE uid = $store_uid";
        $query = mysqli_query($conn, $update);
        if (!mysqli_affected_rows($conn)) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Add the businessman withdrawal amount failures!', PHP_EOL;
            continue;
        }
        $insert = "INSERT INTO zq_cash_detailed (uid, order_id, order_no, relatedid, operation, cash, description, dateline, IP)
            VALUES ($store_uid, {$value['order_id']}, {$value['order_no']}, {$value['uid']}, 'RCV', $money, '冻结金额转换可提现金额，扣除冻结金额{$money}元', $now, '0.0.0.0')";
        if (!mysqli_query($conn, $insert)) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Add the businessman may withdrawal amount details failure!', PHP_EOL;
            continue;
        }
        $update = "UPDATE zq_meihuan_merchant SET gold = gold + {$value['total']} WHERE mer_id = 1";
        if (!mysqli_affected_rows($conn)) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Failure recovery of gold COINS!', PHP_EOL;
            continue;
        }
        $insert = "INSERT INTO zq_merchant_detailed (mer_id, order_id, relatedid, operation, type, amount, description, dateline)
            VALUES (1, {$value['order_id']}, {$value['uid']}, 'RCV', 0, {$value['total']}, '{$value['order_no']}用户下单获得{$value['total']}金币', $now)";
        if (!mysqli_query($conn, $insert)) {
            mysqli_query($conn, "ROLLBACK");
            echo 'Add recycling failure for details!', PHP_EOL;
            continue;
        }
        $uid = $value['uid'];
        $select = "SELECT * FROM zq_maker WHERE from_id = $uid";
        $query = mysqli_query($conn, $select);
        $makerRow = mysqli_fetch_assoc($query);
        if (!empty($makerRow)) {
            $cMakerUid = $makerRow['shareid'];
            if ($cMakerUid != 0) {
                $makerMoney = bcdiv(bcmul($money, 1.5), 100, 2);
                $update = "UPDATE zq_meihuan_user SET `now_money` = `now_money` + $makerMoney WHERE uid = $cMakerUid";
                $query = mysqli_query($conn, $update);
                if (!mysqli_affected_rows($conn)) {
                    mysqli_query($conn, "ROLLBACK");
                    echo 'Change Maker cash Fail', PHP_EOL;
                    continue;
                }
                $insert = "INSERT INTO zq_cash_detailed (uid, operation, relatedid, cash, description, dateline, IP)
                    VALUES ($cMakerUid, 'CSP', $uid, $makerMoney, '{$value['order_no']}消费获得消费奖{$makerMoney}元', $now, '0.0.0.0')";
                if (!mysqli_query($conn, $insert)) {
                    mysqli_query($conn, "ROLLBACK");
                    echo 'Change Maker cash details Fail', PHP_EOL;
                    continue;
                }
            } else {
                mysqli_query($conn, "COMMIT");
                echo '{code:1,result:success}', PHP_EOL;
                continue;
            }
            $select = "SELECT * FROM zq_maker WHERE from_id = $cMakerUid";
            $query = mysqli_query($conn, $select);
            $row = mysqli_fetch_assoc($query);
            $bMakerUid = $row['shareid'];
            if ($bMakerUid != 0) {
                $makerMoney = bcdiv(bcmul($money,1),100,2);
                $update = "UPDATE zq_meihuan_user SET `now_money` = `now_money` + $makerMoney WHERE uid = $bMakerUid";
                $query = mysqli_query($conn, $update);
                if (!mysqli_affected_rows($conn)) {
                    mysqli_query($conn, "ROLLBACK");
                    echo 'Change Maker cash Fail', PHP_EOL;
                    continue;
                }
                $insert = "INSERT INTO zq_cash_detailed (uid, operation, relatedid, cash, description, dateline, IP)
                    VALUES ($bMakerUid, 'CSP', $cMakerUid, $makerMoney, '{$value['order_no']}消费获得消费奖{$makerMoney}元', $now, '0.0.0.0')";
                if (!mysqli_query($conn, $insert)) {
                    mysqli_query($conn, "ROLLBACK");
                    echo 'Change Maker cash details Fail', PHP_EOL;
                    continue;
                }
            } else {
                mysqli_query($conn, "COMMIT");
                 echo '{code:2,result:success}', PHP_EOL;
                continue;
            }
            $select = "SELECT * FROM zq_maker WHERE from_id = $bMakerUid";
            $query = mysqli_query($conn, $select);
            $row = mysqli_fetch_assoc($query);
            $aMakerUid = $row['shareid'];
            if ($aMakerUid != 0) {
                $makerMoney = bcdiv(bcmul($money, 0.5), 100, 2);
                $update = "UPDATE zq_meihuan_user SET `now_money` = `now_money` + $makerMoney WHERE uid = $aMakerUid";
                $query = mysqli_query($conn, $update);
                if (!mysqli_affected_rows($conn)) {
                    mysqli_query($conn, "ROLLBACK");
                    echo 'Change Maker cash Fail', PHP_EOL;
                    continue;
                }
                $insert = "INSERT INTO zq_cash_detailed (uid, operation, relatedid, cash, description, dateline, IP)
                    VALUES ($aMakerUid, 'CSP', $bMakerUid, $makerMoney, '{$value['order_no']}消费获得消费奖{$makerMoney}元', $now, '0.0.0.0')";
                if (!mysqli_query($conn, $insert)) {
                    mysqli_query($conn, "ROLLBACK");
                    echo 'Change Maker cash details Fail', PHP_EOL;
                    continue;
                }
            } else {
                mysqli_query($conn, "COMMIT");
                echo '{code:3,result:success}', PHP_EOL;
                continue;
            }
            mysqli_query($conn, "COMMIT");
            echo '{code:4,result:success}', PHP_EOL;
            continue;
        } 
        mysqli_query($conn, "COMMIT");
        echo '{code:5,result:success}', PHP_EOL;
        continue;
    }
    echo 'The query results in 7 days', PHP_EOL;
    continue;
}
echo 'The query result is empty!', PHP_EOL;
