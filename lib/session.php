<?php
function sess_open($save_path,$session_name){
      
    define('SESSION_NAME', $session_name);
    
    return true;
}
function sess_close(){
    global $sess_fp;
    
    flock($sess_fp, LOCK_UN);
    fclose($sess_fp);
    $sess_fp=null;
    
    return true;
}
function sess_read($id){
    global $sess_fp;
    
    $sess_file = DIRSESSION."sess_".$id;
    if ($sess_fp = @fopen($sess_file, "r+")) {
        flock($sess_fp, LOCK_EX);
        $expire=fgets($sess_fp);
        if( $expire < mktime() ){
            @unlink( $sess_file );
            return '';
        }
        $data=fgets($sess_fp);
        $data=unserialize(urldecode($data));
        return($data);
    } else {
        return(""); // Must return "" here.
    }
}
function sess_write($id,$data){
    global $sess_fp;
    
    $sess_file = DIRSESSION."sess_".$id;
    $timeout = strtotime( TIMEOUT );
    $data=urlencode(serialize($data));
    if (!empty($sess_fp)) {
        fseek($sess_fp,0);
        return(fwrite($sess_fp, $timeout."\n".$data));
    } elseif ($sess_fp = @fopen($sess_file, "w")) {
        flock($sess_fp, LOCK_EX);
        return(fwrite($sess_fp, $timeout."\n".$data));
    } else {
        return(false);
    }
}
function sess_destroy($id){
    $sess_file = DIRSESSION."sess_".$id;
    return(@unlink($sess_file));
}
function sess_gc($maxlifetime){
    $sess_file = DIRSESSION."sess_".$id;
    return true;
}
?>