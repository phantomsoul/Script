<?php 
//$conn = mysqli_connect('localhost', 'script', '880408yl', 'yihuan', '3306');
$conn = mysqli_connect('localhost', 'root', 'root', 'test', '3306');
if (!$conn) { 
    die("连接错误: " . mysqli_connect_error()); 
}
mysqli_query($conn, "set character set 'utf8'");
mysqli_query($conn, "set names 'utf8'");
$hasConfig = "select status from zq_config where name = 'maker'";
$hasResult = mysqli_query($conn, $hasConfig);
$status = mysqli_fetch_assoc($hasResult);
if (empty($status)) {
    $result = 'Config is empty!';
    exit($result);
}
if ($status['status'] == 0) {
    $configSql = "update zq_config set `status` = 1 where name = 'maker'";
    mysqli_query($conn, $configSql);
    if (!mysqli_affected_rows($conn)) {
        $result = 'Config-setting was failed';
        exit($result);
    }
}
$startSql = 'SELECT frozen_maker FROM zq_meihuan_user WHERE frozen_maker != 0 AND is_maker=1';
$startResult = mysqli_query($conn, $startSql);
$row = mysqli_fetch_assoc($startResult);
if (empty($row)) {
    $result = 'Have been executed to complete!';
    exit($result);
}
//判断是否有未完成创客
$unfinished_user = "SELECT uid,nickname,frozen_maker FROM zq_meihuan_user WHERE frozen_maker < 200 AND frozen_maker > 0 AND is_maker=1";
mysqli_query($conn,"START TRANSACTION");
$errorlog = fopen("task-maker-monthly-statement-error-log-".date('Y-m-d H-i-s').'.txt','w') or die('Unable to open file!');
$now = time();
$unfinished_user_result = mysqli_query($conn, $unfinished_user);
$IP = get_client_ip();
$unfinished_money = '';
while ($row = mysqli_fetch_assoc($unfinished_user_result)) {
    $unfinished_uid = $row['uid'];
    $unfinished_name = $row['nickname'];
    $unfinished_money = $row['frozen_maker'];
    //冻结金额给上级
    $upUid = toSuperior($unfinished_uid, $conn);
    if (empty($upUid)) {
        //总部添加金额
        $up = "UPDATE zq_meihuan_merchant SET `frozen_maker` = `frozen_maker` + $unfinished_money WHERE mer_id = 1";
        mysqli_query($conn, $up);
        if (!mysqli_affected_rows($conn)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo 'Change merchant table frozen_maker failed!', PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'frozen='.$unfinished_money.'总部添加金额失败!';
            fwrite($errorlog, $log);
            continue;
        }
        //总部添加详情
        $insert = "INSERT INTO zq_merchant_detailed(mer_id, relatedid, operation, amount, type, description, dateline) VALUES (1, $unfinished_uid, 'UNF', $unfinished_money, 2, '月结算下级未完成任务增加冻结金额".$unfinished_money."元',$now)";
        if (!mysqli_query($conn, $insert)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo 'maker add merchant failed!',PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'添加总部详情失败!';
            fwrite($errorlog, $log);
            continue;
        }
        //添加当前用户冻结金额详情
        $cashDetailed = "INSERT INTO zq_cash_detailed(uid, relatedid, operation, cash, description, dateline, IP) VALUES ($unfinished_uid, '0', 'UNF', -$unfinished_money, '月结算未完成任务扣除冻结金额".$unfinished_money."元',$now, '$IP')";
        if (!mysqli_query($conn, $cashDetailed)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo "$cashDetailed".PHP_EOL;
            echo 'maker add frozen_maker failed!', PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'添加用户冻结金额详情失败!';
            fwrite($errorlog, $log);
            continue;
        }
    } else {
        //添加上级冻结金额
        $upInc = "UPDATE zq_meihuan_user SET `frozen_maker` = `frozen_maker` + $unfinished_money WHERE uid = $upUid";
        mysqli_query($conn, $upInc);
        if (!mysqli_affected_rows($conn)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo 'update up frozen_maker failed!',PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'添加上级冻结金额'.$unfinished_money.'失败!';
            fwrite($errorlog, $log);
            continue;
        }
        //上级添加详情
        $insert = "INSERT INTO zq_cash_detailed(uid, relatedid, operation, cash, description, dateline, IP) VALUES ($upUid, $unfinished_uid, 'UNF', $unfinished_money, '月结算下级未完成任务增加冻结金额".$unfinished_money."元',$now, '$IP')";
        if (!mysqli_query($conn, $insert)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo 'maker add merchant failed!',PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'添加上线详情失败!';
            fwrite($errorlog, $log);
            continue;
        }
        //扣除当前用户冻结金额
        $Dec = "UPDATE zq_meihuan_user SET `frozen_maker` = `frozen_maker` - $unfinished_money WHERE uid = $unfinished_uid";
        mysqli_query($conn, $Dec);
        if (!mysqli_affected_rows($conn)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo 'update user frozen_maker failed!', PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'扣除未完成用户冻结金额'.$unfinished_money.'失败!';
            fwrite($errorlog, $log);
            continue;
        }
        //添加当前用户冻结金额详情
        $cashDetailed = "INSERT INTO zq_cash_detailed(uid, relatedid, operation, cash, description, dateline, IP) VALUES ($unfinished_uid, $upUid, 'UNF', -$unfinished_money, '月结算未完成任务扣除冻结金额".$unfinished_money."元',$now, '$IP')";
        if (!mysqli_query($conn, $cashDetailed)) {
            $a = true;
            //mysqli_query($conn, "ROLLBACK");
            echo "$cashDetailed".PHP_EOL;
            echo 'maker add frozen_maker failed!', PHP_EOL;
            $log = '创客uid='.$unfinished_uid.'添加用户冻结金额详情失败!';
            fwrite($errorlog, $log);
            continue;
        }
    }
}
//所有表冻结金额打入当月收益
$sql = 'UPDATE `zq_meihuan_user` SET `ultimo` = `frozen_maker` WHERE `is_maker`=1 AND `frozen_maker` >= 200';
mysqli_query($conn, $sql);
if (!mysqli_affected_rows($conn)) {
    $a = true;
    //mysqli_query($conn, "ROLLBACK");
    $info = 'Update all user amount frozen into the monthly income failed!';
    $log = '所有表冻结金额打入当月收益失败！';
    fwrite($errorlog, $log);
    exit($info);
}
//未完成任务的清空当月收益
$sql1 = 'UPDATE `zq_meihuan_user` SET `ultimo` = 0 WHERE `is_maker` = 1 AND `frozen_maker` < 200';
mysqli_query($conn, $sql1);
if (!mysqli_affected_rows($conn)) {
    $a = true;
    //mysqli_query($conn, "ROLLBACK");
    $info = 'To empty the month income failed!';
    $log = '未完成任务的清空当月收益!';
    fwrite($errorlog, $log);
    exit($info);
}
//所有表冻结金额打入现金
$finished = 'SELECT uid, nickname, frozen_maker FROM zq_meihuan_user WHERE `frozen_maker` >= 200';
$query = mysqli_query($conn, $finished);
while ($value = mysqli_fetch_assoc($query)) {
    $value['uid'];
    $sql2 = "UPDATE `zq_meihuan_user` SET `now_money` = `now_money` + `frozen_maker` WHERE uid = {$value['uid']}";
    mysqli_query($conn, $sql2);
    if (!mysqli_affected_rows($conn)) {
        $a = true;
        //mysqli_query($conn, "ROLLBACK");
        echo 'All the tables amount frozen into cash failure!';
        $log = '所有表冻结金额打入现金失败！';
        fwrite($errorlog, $log);
        continue;
    }
    $sql3 = "INSERT INTO zq_cash_detailed (uid, operation, relatedid, cash, description, dateline, IP) VALUES ({$value['uid']}, 'DRP', '0', {$value['frozen_maker']}, '月结算已完成任务添加可提现金额{$value['frozen_maker']}元', $now, '$IP')";
    if (!mysqli_query($conn, $sql3)) {
        $a = true;
        //mysqli_query($conn, "ROLLBACK");
        echo 'Users to add details to fail!';
        $log = $value['uid'].'用户添加完成详情失败!';
        fwrite($errorlog, $log);
        continue;
    }
}
//所有表冻结金额打入累计收益
$sql4 = 'UPDATE `zq_meihuan_user` SET `accumulative` = `accumulative` + `frozen_maker` WHERE `is_maker`= 1 AND `frozen_maker` >= 200';
mysqli_query($conn, $sql4);
if (!mysqli_affected_rows($conn)) {
    $a = true;
    //mysqli_query($conn, "ROLLBACK");
    $info = 'All the tables amount frozen into the accumulated earnings to fail!';
    $log = '所有冻结金额打入累计收益失败';
    fwrite($errorlog, $log);
    exit($info);
}
//清空所有表冻结金额
$sql5 = 'UPDATE `zq_meihuan_user` SET `frozen_maker` = 0 WHERE `is_maker` = 1';
mysqli_query($conn, $sql5);
if (!mysqli_affected_rows($conn)) {
    $a = true;
    //mysqli_query($conn, "ROLLBACK");
    $info = 'The amount of empty all tables to freeze failed';
    $log = '清空所有表冻结金额';
    fwrite($errorlog, $log);
    exit($info);
}
$setConfig = "update `zq_config` set `status` = 0 where `name` = 'maker'";
mysqli_query($conn, $setConfig);
if (!mysqli_affected_rows($conn)) {
    $a = true;
    $info = 'Config_setting is failed when everything done';
    $log = '配置恢复失败';
    fwrite($errorlog, $log);
    exit($info);
}
if (isset($a)) {
    echo 'ERROR';
    mysqli_query($conn, 'ROLLBACK');
} else {
    echo 'SUCCESS';
    mysqli_query($conn, 'COMMIT');
}

/**
 * 递归查找上级冻结金额大于200
 * @param integer $uid
 * @return integer
 */
function toSuperior($unfinished_uid, $conn) {
    $sql = 'SELECT shareid FROM zq_maker WHERE from_id = '.$unfinished_uid;
    $upUid = mysqli_query($conn, $sql);
    $upUser = mysqli_fetch_assoc($upUid);
    $upUid = $upUser['shareid'];
    if (!empty($upUser)) {
        $sql = "SELECT frozen_maker FROM zq_meihuan_user WHERE uid = {$upUser['shareid']}";
        $query = mysqli_query($conn, $sql);
        $user = mysqli_fetch_assoc($query);
        $up_frozen_maker = $user['frozen_maker'];
        if ($up_frozen_maker < 200 && $up_frozen_maker >= 0) {
            return toSuperior($upUid, $conn);
        }
        return $upUid;
    }
    return $upUid;
}
mysqli_close($conn);
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
function get_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

