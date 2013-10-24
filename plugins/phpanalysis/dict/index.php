<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Language" content="zh-tw">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <title>彰化一整天的blog-UTF8繁體轉簡體function範例</title>
</head>
<body>
彰化一整天的blog-UTF8繁體轉簡體function範例<br/>
<?php
ini_set('memory_limit', '128M');
error_reporting(E_ALL);


//include('not-build/zhtw_zhcn.php');
include('zhtw_zhcn.php');

//$str = '彰化一整天的blog-UTF8繁體轉簡體function範例';
//print hanyu::tw2cn($str).'<br>';
//$str = 'abcdefg';
//echo $str{0};
//print hanyu::cn2tw('彰化一整天的blog-UTF8繁体转简体function范例');
//print big2gb('彰化一整天的blog-UTF8繁體轉簡體function範例');

//die;

$file = fopen("not-build/base_dic_source.txt", "r") or exit("Unable to open file!");
$input = fopen("not-build/base_dic_source_zhtw.txt", "w") or exit("Unable to open file!");
//Output a line of the file until the end is reached
$i = 0;
$j = 0;
$k = 0;
$zhcn = '';
while(!feof($file)){
    $row = fgets($file);
    
    $zhcn.= $row;
    //$zhtw = hanyu::cn2tw($zhcn);
    //fputs($input, $zhtw);
    
    if( $row{0} === '@' ){ echo $row.'<br>'; }
    
    $i+=1;
    $j+=1;
    $k+=1;
    if( $j >= 10 ){
        $zhtw = hanyu::cn2tw($zhcn);
        fputs($input, $zhtw);
        
        $zhcn = '';
        $j=0;
    }
    if( $k >= 1000 ){ echo $i.'<br>'; $k=0; }
}
fclose($file);
fclose($input);

echo $i.'<br>';
echo '轉換完畢';
?>
</body>
</html>
