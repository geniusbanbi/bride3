<?php
//编译词库
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');
require_once('phpanalysis.class.php');
$dicAddon = dirname(__FILE__).'/dict/not-build/base_dic_source_zhtw.txt';

if( empty($_GET['ac']) )
{
    echo "<div style='line-height:28px;'>请选择要进行的操作：<br />";
    echo "1、<a href='?ac=make'>用原始文件(dict/not-build/base_dic_full.txt)生成一个标准词典；</a><br />";
    echo "2、<a href='?ac=revert'>从默认词典(dict/base_dic_full.dic)，反编译出原始文件。</a></div>";
    exit();
}

if( $_GET['ac']=='make' )
{
    set_time_limit(600);
    $target_path = dirname(__FILE__).'/dict/base_dic_full.dic';
    
    echo $dicAddon.'<br>';
    echo $target_path.'<br>';
    PhpAnalysis::$loadInit = false;
    $pa = new PhpAnalysis('utf-8', 'utf-8', false);
    echo '開始<br>';
    $pa->MakeDict( $dicAddon, $target_path );
    echo "完成词典创建！<br>";
    exit();
}
else
{
    $pa = new PhpAnalysis('utf-8', 'utf-8', true);
    $pa->ExportDict('base_dic_source.txt');
    echo "完成反编译词典文件，生成的文件为：base_dic_source.txt ！";
    exit();
}
?> 