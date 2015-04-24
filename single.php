<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
include('lib/marktime.php');

//設定環境
define('TIMEZONE', "Asia/Taipei");

define('APP', 1);
define('DS', DIRECTORY_SEPARATOR);
define('EXT', '.php');

$WEBROOT=dirname($_SERVER['SCRIPT_NAME']);
if( $WEBROOT!='/' ){ $WEBROOT=$WEBROOT.'/'; }
define('WEBROOT',  $WEBROOT );
unset($WEBROOT);

$WEBPLUGIN=dirname(WEBROOT).DS.'core'.DS.'plugins'.DS;
if( $SINGLE_SITE_MODE ){
    $WEBPLUGIN = WEBROOT.'/core/plugins/';
}

// $my_base 來自網站 index.php 中的設定
$dir_base = dirname(__FILE__);
define('DIRROOT', $dir_base.DS );
$BASEROOT = dirname($dir_base).DS.'core'.DS;
if( $SINGLE_SITE_MODE ){
    $BASEROOT = DIRROOT.'core/';
}
define('BASEROOT', $BASEROOT );

define('DIRLIB', BASEROOT.'lib'.DS );


date_default_timezone_set(TIMEZONE);

if( DEBUG===0 ){ ini_set('display_errors', false); }
else{ ini_set('display_errors', true); }

//設定編碼
if( function_exists('mb_internal_encoding') ){
    mb_internal_encoding("UTF-8");
    mb_regex_encoding('UTF-8');
}

marktime( 'Core' , 'Define Env.');

/*******************************************************************\
*** 載入程式庫前  **************************************************
\*******************************************************************/

//如果啟用語系支援，且用戶在網址中指定預設語系, ex: 預設為zh-tw時, /zh-tw/news/ , 應導引至 /news/
if( ! empty($routing_args['locale']) && $routing_args['locale'] === pos($routing_args['params'])
    && $routing_args['locale'] === RoutingConfigs::$locale['default'] ){

    header('Location: '.WEBROOT.substr($routing_args['p'], strlen($routing_args['locale'])+1 ) );
    exit;
}

/*******************************************************************\
*** 載入程式庫  ****************************************************
\*******************************************************************/
//Loading Basic Methods
require( DIRLIB.'utilities'.EXT );
require( DIRLIB.'MRDB'.EXT );
require( DIRLIB.'kernel'.EXT );
require( DIRLIB.'url'.EXT );

marktime( 'Core' , 'Loading Libs');

//設定PEAR環境
if( DS=='/'){
    ini_set('include_path', ini_get('include_path').':'.BASEROOT.'pears/' ); //UNIX
}else{
    ini_set('include_path', BASEROOT.'pears;'.ini_get('include_path') ); //Windows
}

marktime( 'Core' , 'Setting System Config');




//設定錯誤處理
set_error_handler('errorHandler');

marktime( 'Core' , 'Setting ErrorHandler');

//設定Session
/*ini_set('session.save_handler', 'user');
session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy', 'sess_gc');
session_save_path( DIRSESSION );
session_name('JBride');
session_start();*/

marktime( 'Core' , 'Setting Session');
marktime( 'SystemUser', 'System');


?>
