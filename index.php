<?php

//請注意，本 script 要由各站內的 index.php 取得執行中網站的路徑
//設定好 $my_base 後，再 include 呼叫執行

require('lib/marktime.php');
require('lib/routing.php');

marktime('Core', 'Start');
marktime('SystemUser', 'Start');

//phpinfo();
/*******************************************************************\
*** Routing     ****************************************************
\*******************************************************************/

//過濾不安全的url輸入
//$path=$_GET['p'];
//$path=filter_var( $path, FILTER_SANITIZE_URL); //for php version > 5.2
//$p=$path;

//清除$_GET全域陣列中的 p （rewrite所引入的路徑資料）
unset($_GET['p']);
//直接從系統環境取得REDIRECT_URL
$base=dirname( getenv('SCRIPT_NAME') );
$p=filter_var( getenv('REQUEST_URI'), FILTER_SANITIZE_URL);
$p=urldecode( $p );
$p=substr( $p, strlen($base) );
if( substr($p,0,1)=='/' ) $p=substr($p,1);

$routing_args=Routing::parse( $p );

//以安全的方式重建$p
$ME = pos(explode('?', $p));
$p = $ME;
if( count($_GET)>0 ){
    $p.='?'.http_build_query($_GET, '', "&");
}
$routing_args['p']=$p;
$routing_args['ME']=$ME;
$routing_args['DIRROOT']=$my_base;

/*echo '<pre>';
print_r($routing_args);
echo '</pre>';
echo '<pre>';
print_r(RoutingConfigs::$maps);
echo '</pre>';
echo '<pre>$_GET:'."<br>\n";
print_r($_GET);
echo '</pre>';
die;*/

marktime('Core', 'Routing');

/*******************************************************************\
*** 執行程式     ****************************************************
\*******************************************************************/

//呼叫初始化程式
require( 'app.php' );


marktime('Core', 'App Executed');

/*******************************************************************\
*** 垃圾回收     ****************************************************
\*******************************************************************/

marktime( 'Core' , 'Garbage Collection');
marktime( 'SystemUser', 'User');

//呼叫系統的全程式終止程序
stop_progress();


?>
