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
if( ! isset($console_mode) || ! $console_mode ){
	$base=dirname( getenv('SCRIPT_NAME') );
	$p=filter_var( getenv('REQUEST_URI'), FILTER_SANITIZE_URL);
	$p=urldecode( $p );
	$p=substr( $p, strlen($base) );

}else{
	unset($console_mode);

	$p='';
	if( isset($argv[1]) && ! empty($argv[1]) ){
	    $p=$argv[1];
	}
}
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

// 清除核心的快取資料夾 for thumb (如果有縮圖失敗的殘留檔可能會影響運作)
$cache_path = dirname(__FILE__).'/cache/';
if($handle = opendir($cache_path)) {
	while (false !== ($entry = readdir($handle))) {
	    if( ! in_array($entry, array('.', '..', 'index.html')) ){
	    	unlink( $cache_path.$entry );
	    }
	}
}


marktime( 'Core' , 'Garbage Collection');
marktime( 'SystemUser', 'User');

//呼叫系統的全程式終止程序
stop_progress();


?>
