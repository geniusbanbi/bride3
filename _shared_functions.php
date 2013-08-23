<?php
function isBot(){
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    //if no user agent is supplied then assume it's a bot
    if($userAgent == "")
        return 1;
    
    //array of bot strings to check for
    $bots = Array(
        "google",     "bot",
        "yahoo",     "spider",
        "archiver",   "curl",
        "python",     "nambu",
        "twitt",     "perl",
        "sphere",     "PEAR",
        "java",     "wordpress",
        "radian",     "crawl",
        "yandex",     "eventbox",
        "monitor",   "mechanize",
        "facebookexternal"
    );
    foreach($bots as $bot){
        if(strpos($userAgent,$bot) !== false) { return true; }
    }
    
    return false;
}

function fixstr($str, $len){
    if( $len==0 ){ return $str; }
    if( function_exists('mb_substr') ){
        $suffix='';
        if( mb_strlen($str) > $len ) $suffix=' ...';
        return nl2br(mb_substr($str, 0, $len)).$suffix;
    }
    $n_str ="<script>";
    $n_str.="var str='".str_replace("\r\n",'<br>',$str)."';";
    $n_str.="var len=".$len.";";
    $n_str.="document.write( str.substr(0,len) );";
    $n_str.="if( str.length>len ){ document.write(' ...'); }";
    $n_str.="</script>";
    return $n_str;
}


?>