<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

//設定環境
define('TIMEZONE', "Asia/Taipei");

define('APP', 1);
define('DS', DIRECTORY_SEPARATOR);
define('EXT', '.php');

$WEBROOT=dirname($_SERVER['SCRIPT_NAME']);
if( $WEBROOT!='/' ){ $WEBROOT=$WEBROOT.'/'; }
define('WEBROOT',  $WEBROOT );
unset($WEBROOT);
define('WEBLAYOUT', WEBROOT.'layouts/' );
define('WEBCABINET', WEBROOT.'cabinets/' );

// $my_base 來自網站 index.php 中的設定
$dir_base = $my_base;
define('DIRROOT', $dir_base.DS );

$BASEROOT = dirname($dir_base).DS.'core'.DS;
if( $SINGLE_SITE_MODE ){
    $BASEROOT = DIRROOT.'core/';
}
define('BASEROOT', $BASEROOT );

define('DIRLIB', BASEROOT.'lib'.DS );
define('DIRPLUGIN', BASEROOT.'plugins'.DS );

$WEBPLUGIN=dirname(WEBROOT).DS.'core'.DS.'plugins'.DS;
if( $SINGLE_SITE_MODE ){
    $WEBPLUGIN = WEBROOT.'/core/plugins/';
}
define('WEBPLUGIN', $WEBPLUGIN );

define('DIRCONFIG', DIRROOT.'config'.DS );
define('DIRCACHE', DIRROOT.'cache'.DS );
define('DIRCABINET', DIRROOT.'cabinets'.DS );
define('DIRLOCALE', DIRROOT.'locale'.DS );
define('DIRSESSION', DIRROOT.'cache'.DS.'sessions'.DS );
define('DIRVENDOR', DIRROOT.'vendors'.DS );


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
require( DIRLIB.'session'.EXT );
require( DIRLIB.'MRDB'.EXT );
require( DIRLIB.'kernel'.EXT );
require( DIRLIB.'url'.EXT );

marktime( 'Core' , 'Loading Libs');

include( DIRCONFIG.'config.php' );
include( DIRCONFIG.'databases.php' );

marktime( 'Core' , 'Loading Configs');

//儲存routing分析結果
APP::$routing = $routing_args;
APP::$app     = $routing_args['app'];
APP::$doctype = $routing_args['doctype'];
APP::$params  = $routing_args['params'];
APP::$parents  = $routing_args['parents'];
APP::$handler = $routing_args['handler']; //標示總管負責的程式
APP::$prefix  = $routing_args['prefix']; //網址前綴詞
APP::$prefixFull = $routing_args['prefixFull']; //網址前綴全名
APP::$p       = WEBROOT.$routing_args['p']; //傳入的路徑
APP::$ME      = WEBROOT.$routing_args['ME']; //排除GET字串的路徑
//Loading System Configs
//$configs = sfYaml::load( DIRCONFIG.'config.yml' );
$basic=APP::$systemConfigs;
define('DEBUG', $basic['Debug']);           //0 or 1，0關閉，1開發模式，顯示詳細的輔助除錯訊息
define('PRODUCTION', $basic['Production']); //0 or 1，0關閉，1產品模式，系統錯誤以溫柔、包裝過的方式呈現
/* 將與APP::cacheConfigs['region']['cache']['switch']重疊比對，決定是否啟用 */
//define('CACHE', $basic['Cache']);           //0 or 1，0關閉，1啟用快取，啟用或關閉快取
define('TIMEOUT', $basic['Timeout']); //Session Destoryed Time, 設定整體的Session消滅時間
define('PAGEROWS',  $basic['Pagerows']);

//檢查必要的系統資料夾
if( ! is_dir(DIRCACHE) ){ mkdirs(DIRCACHE); }
if( ! is_dir(DIRSESSION) ){ mkdirs(DIRSESSION); }
if( ! is_dir(DIRCABINET) ){ mkdirs(DIRCABINET); }

//設定PEAR環境
if( DS=='/'){
    ini_set('include_path', ini_get('include_path').':'.BASEROOT.'pears/' ); //UNIX
}else{
    ini_set('include_path', BASEROOT.'pears;'.ini_get('include_path') ); //Windows
}

marktime( 'Core' , 'Setting System Config');

APP::$mdb = new MRDB;
APP::$mdb->init( APP::$databaseConfigs );
APP::$mdb->connect(); //會自動連接 main @ $db_profiles

marktime( 'Core' , 'Initialize Database Connection');

//載入語系檔
$locale = DIRLOCALE.APP::$routing['locale'].'.php';
if( file_exists($locale) ){
    require( $locale );
}

$_default = array(
    'http_metas'=>array(),
    'sitename'=>'',
    'title'=>'',
    'metas'=>array(),
    'stylesheets'=>array(),
    'javascripts'=>array(),
    'extra'=>array(),
    'footers'=>array(),
);
include( DIRCONFIG.'layouts'.EXT );
$_default = array_merge( $_default , APP::$layoutsConfigs['default'] );
$prefix=APP::$routing['prefix'];
View::$layoutConfigs = array_merge( $_default , APP::$layoutsConfigs[ $prefix ] );


marktime( 'Core' , 'Parse Layout Configs');

//設定錯誤處理
set_error_handler('errorHandler');

marktime( 'Core' , 'Setting ErrorHandler');

//設定Session
ini_set('session.save_handler', 'user');
session_set_save_handler('sess_open', 'sess_close', 'sess_read', 'sess_write', 'sess_destroy', 'sess_gc');
session_save_path( DIRSESSION );
session_name('JBride');
session_start();

marktime( 'Core' , 'Setting Session');
marktime( 'SystemUser', 'System');

//載入全網站共用自訂項目
include( '_shared_functions.php' );
//載入網站專用自訂項目
include( DIRROOT.'_global_functions.php' );
//載入表單生成物件
$_form_path = DIRROOT.'layout_'.APP::$prefix.'/helper.form'.EXT;
if( file_exists($_form_path) ){
    //如果自訂表單存在，載入自訂版本
    include( $_form_path );
}else{
    //否則載入預設版本（或稱範例版本）
    include( DIRLIB.'form'.EXT );
}

//載入Controller
//因為index.php已經檢查過，載入時不用再檢查
$preload = DIRROOT.APP::$prefix.'.prefix'.EXT;
if( file_exists($preload) ){
    require( $preload );
}

//等同於載入 admin.prefix.php ，新的名稱格式為 _admin_functions.php
$preload = DIRROOT.'_'.APP::$prefix.'_functions'.EXT;
if( file_exists($preload) ){
    require( $preload );
}else{
    //除了main區域允許沒有 _main_functions.php 之外
    //其他區域 (ex. admin) 都必須要擁有 _{prefix}_functions.php ex. _admin_functions.php
    //這是為了安全性考量，避免此檔毀損或遺失時門戶大開卻難以察覺
    
    if( APP::$prefix !== 'main' ){
        errmsg('找不到必要的 _'.APP::$prefix.'_functions.php 檔案，禁止存取');
    }
}


require( DIRROOT.APP::$handler.EXT );


?>