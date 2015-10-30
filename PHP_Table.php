<?php
//PHP解析HTML表格为二维数组（可能含合并单元格）
header("Content-type: text/html; charset=UTF-8");

set_time_limit (0);
ignore_user_abort();

$pageContent = file_get_contents('HTMLtable2.html');
// $url = 'http://www.stats.gov.cn/tjsj/zxfb/201510/t20151023_1260516.html';
// $Referer = $url;
// $pageContent = curl_get($url, $Referer);
$pageContent = mb_convert_encoding($pageContent, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');

$table = get_html_table_array($pageContent);
// var_dump($table);

//打印存储表格的二维数组的内容：
for($i=0; $i<count($table); $i++) {
	for($j=0; $j<count($table[$i]); $j++)
		echo $table[$i][$j] . "\t";
	echo "<br>";
}


function get_html_table_array($plantsContent) {  
  $reg = '/<TABLE[\S\s]+?<\/TABLE>/i'; //?表示非贪婪模式
  $result = preg_match($reg, $plantsContent, $match_result);
  $outstr = '';
  if($result) {
    $reg = '/<TR[^<]*(<[\S\s]+?>)[^<]*<[\s]*\/TR>/i';
    $result_tr = preg_match_all($reg, $match_result[0], $match_result_tr);
    if($result_tr) {
      $outstr = get_td_txt($match_result_tr);
    }
  }
  
  return $outstr;
}

function get_td_txt($match_result_tr) {
  $table = array();
  $rowsNum = count($match_result_tr[1]);

  $reg = '/<TD[^>]*?>([\s\S]+?)<[^>]*\/TD>/i'; //获得第一行的列数  
  $result_td = preg_match_all($reg, $match_result_tr[1][0], $match_result_td);
  if($result_td) {
  	// var_dump($match_result_td);
    $colsNum = count($match_result_td[1]);
    $colsNum2 = $colsNum;
    for($i=0; $i<$colsNum; $i++) {
      $reg = '/colSpan="?([\d]+)[^\d]+/i';
      $result = preg_match_all($reg, $match_result_td[0][$i], $match_result);
      if($result)
        $colsNum2 += $match_result[1][0]-1;	//考虑合并单元格后的列数
    }

    for($i=0; $i<$rowsNum; $i++) {
      $table[$i] = array();
      for($j=0; $j<$colsNum2; $j++) {
        $table[$i][] = '';					//初始化存储表格内容的二维数组
      }
    }
  }

  for($i=0; $i<$rowsNum; $i++) {
    $reg = '/<TD[^>]*?>([\s\S]+?)<[^>]*\/TD>/i';
    $result_td = preg_match_all($reg, $match_result_tr[1][$i], $match_result_td);
    if($result_td) {
      $td_num = count($match_result_td[1]);      
      for($j=0; $j<$td_num; $j++) {
        $td_txt = preg_replace('/[\s]*<[^>]+>[\s]*/', "", $match_result_td[1][$j]);
        $td_txt = trim($td_txt);
        $td_txt = str_replace(',', ' ', $td_txt);
        $td_txt = str_replace('\n', ' ', $td_txt);	//提取单元格内的纯文本内容

        $reg = '/colSpan="?([\d]+)[^\d]+/i';
        $result = preg_match_all($reg, $match_result_td[0][$j], $match_result);
        if($result)
          $colSpan = $match_result[1][0];
        else
          $colSpan = 1;

        $reg = '/rowSpan="?([\d]+)[^\d]+/i';
        $result = preg_match_all($reg, $match_result_td[0][$j], $match_result);
        if($result)
          $rowSpan = $match_result[1][0];
        else
          $rowSpan = 1;
        $col=$j;
        while($table[$i][$col] != '')
          $col++;
        for($tcol=$col; $tcol<$col+$colSpan; $tcol++) {
          for($trow=$i; $trow<$i+$rowSpan; $trow++) {
            $table[$trow][$tcol] = $td_txt;			//复制内容到被合并的单元格
          }
        }
      }
    }
  }
  return $table;
}

function curl_get($url, $Referer) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt ($ch, CURLOPT_PROXY, "http://10.10.10.10:8080");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Accept:image/jpeg, application/x-ms-application, image/gif, application/xaml+xml, image/pjpeg, application/x-ms-xbap, application/vnd.ms-excel, application/vnd.ms-powerpoint, application/msword, application/x-shockwave-flash, */*',
    'Accept-Language:zh-CN',
    'Accept-Encoding:gzip, deflate',
    'Connection: Keep-Alive',
    'Referer:'.$Referer
    ));
    $data = curl_exec($ch);
    // var_dump(curl_error($ch));
    curl_close($ch);    
    return $data;
}

?>
