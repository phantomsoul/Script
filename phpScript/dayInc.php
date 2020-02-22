<?php

	$link = mysqli_connect('123.57.141.202','yuffiy','901004az','yihuan');

	mysqli_set_charset($link ,'utf8');

	//设置北京时区
	date_default_timezone_set('PRC'); 

	$dayEnd = strtotime(date('Y-m-d'));
	
	$dayBegin = $dayEnd - 24*60*60;

	//用户总量
	$sql = "select count(uid) as dayTol from zq_meihuan_user where regdate < ".$dayEnd;

	$flog = mysqli_query($link,$sql);

	$data = mysqli_fetch_array($flog);

	$dayTol = $data['dayTol'];

	//用户日增量
	$sql1 = "select count(uid) as dayInc from zq_meihuan_user where regdate between ".$dayBegin." and ".$dayEnd;

	$result = mysqli_query($link,$sql1);

	$result = mysqli_fetch_array($result);

	$dayInc = $result['dayInc'];

	//创客总量
	$sql2 = "select count(id) as makerTol from zq_maker where dateline < ".$dayEnd;

	$result = mysqli_query($link,$sql2);

	$makerTol = mysqli_fetch_array($result);

	$makerTol = $makerTol['makerTol'];

	//创客日增量
	$sql3 = "select count(id) as makerInc from zq_maker where dateline between ".$dayBegin." and ".$dayEnd; 

	$result = mysqli_query($link,$sql3);

	$makerInc = mysqli_fetch_array($result);

	$makerInc = $makerInc['makerInc'];

	$time = date("Y-m-d",$dayBegin);

	echo $time."\r\n".'易换用户总人数达'.$dayTol.',易换用户当日增长'.$dayInc.";\r\n".'易换创客总人数达'.$makerTol.',易换创客当日增长'.$makerInc.'。';

	mysqli_close($link);

?>


