<?php
class UTSessionHandler
{
    private $savePath;

    function open($savePath, $sessionName)
    {
        define('SESSION_NAME', $session_name);

        $this->savePath = $savePath;

        if( ! is_dir($this->savePath) ) {
            errmsg( 'Session savePath Error: You must create "'.$this->savePath.'" directory and made it writable.' );
        }

        return true;
    }

    function close()
    {
        // call garbage collection myself
        $this->gc( get_cfg_var("session.gc_maxlifetime") );
        
        return true;
    }

    function read($id)
    {
        $sess_file = $this->savePath."sess_".$id;

        return (string)@file_get_contents( $sess_file );
    }

    function write($id, $data)
    {
        $sess_file = $this->savePath."sess_".$id;

        return file_put_contents( $sess_file, $data) === false ? false : true;
    }

    function destroy($id)
    {
        $sess_file = $this->savePath."sess_".$id;

        if( file_exists($sess_file) ){
            unlink($file);
        }

        return true;
    }

    function gc($maxlifetime)
    {
        foreach( glob($this->savePath."sess_*") as $file ){
            if( filemtime($file) + $maxlifetime < time() && file_exists($file)){
                unlink($file);
            }
        }

        return true;
    }
}
?>