<?php
require_once 'MDB2.php';
require_once 'Auth/Container.php';

class BridiiAuthContainer extends Auth_Container
{
    var $params=array();
    function BridiiAuthContainer($params){
        $_default=array(
            'dsn'=>'',
            'table' => '',
            'usernameCol' => 'userid',
            'passwordCol' => 'password',
            'cryptTypeCol' => 'algorithm',
            'saltCol' => 'salt',
            'isActiveCol' => 'is_active',
            'isActiveAllowed' => '1',
            'deletedCol' => 'deleted',
            'deletedAllowed' => '0',
            'plugin' => 'managers',
            'db_fields' => '*',
        );
        $params += $_default;
        $this->params = $params;
    }

    function fetchData($username, $password){
        $this->log('BridiiAuthContainer::fetchData() called.', AUTH_LOG_DEBUG);
        
        $mdb=& MDB2::factory($this->params['dsn']);
    	if(PEAR::isError($mdb))
    		die('db error:'. $mdb->getMessage());
		$mdb->setFetchMode(MDB2_FETCHMODE_ASSOC);
        
        $sql ="SELECT * FROM ".$this->params['table'];
        $sql.=" WHERE ".$this->params['isActiveCol']."=".$mdb->quote($this->params['isActiveAllowed'],'text');
        $sql.=" AND ".$this->params['deletedCol']."=".$mdb->quote($this->params['deletedAllowed'],'text');
        $sql.=" AND ".$this->params['usernameCol']."=".$mdb->quote($username,'text');
        if( !empty($this->params['plugin']) ){
            $sql.=" AND ".$mdb->quote($this->params['plugin'], 'text');
        }
        $res=$mdb->query($sql);
        $rows=$res->fetchAll();
        
        // Began Verify
        if( count($rows) != 1 ){
            return false;
        }
        $userdata=$rows[0];
        if( empty($userdata['algorithm']) || empty($userdata['salt']) || empty($userdata['password']) ){
            return false;
        }
        $algorithm=$userdata['algorithm'];
        $salt=$userdata['salt'];
        
        if( ! function_exists($algorithm) ){
            return false;
        }
        $encrypt = $algorithm( $salt.$password.$salt );
        if( $encrypt != $userdata[ $this->params['passwordCol'] ] ){
            return false;
        }
        // Verify Passed
        foreach( $userdata as $key=>$value ){
            $this->_auth_obj->setAuthData($key, $value);
        }
        
        return true;
    }
}
?>