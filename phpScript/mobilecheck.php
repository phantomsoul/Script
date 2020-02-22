<?php
$link = mysqli_connect('123.57.141.202','yuffiy','901004az','yihuan');
mysqli_set_charset($link, 'utf8');
date_default_timezone_set('PRC');
//要查询的电话号码
$sql = 'SELECT nickname, mobile, now_money, gold, frozen, FROM_UNIXTIME(regdate) FROM zq_meihuan_user';
$result = mysqli_query($link, $sql);
$i = 1;
$memberfile = fopen('shandong.xls','w');
while ($value = mysqli_fetch_assoc($result)) {
    if (empty($value)) {
        echo 'The query result is empty!', PHP_EOL;
        continue;
    }
    $mobile = $value['mobile'];
    $address = file_get_contents("http://sj.apidata.cn/?mobile=$mobile");
    $data = json_decode($address, true);
    if ($data['data']['city'] === '菏泽') {
        echo $i.'true', PHP_EOL;
        fwrite($memberfile, $value['nickname']."\t".$value['mobile']."\t".$value['now_money']."\t".$value['gold']."\t".$value['frozen']."\t".$value['regdate']."\n");
    } else {
        echo $i.'false', PHP_EOL;
    }
    $i ++;
}
fclose($memberfile);
