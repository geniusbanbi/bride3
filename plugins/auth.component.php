<?php
class AuthComponent{
    static $pears=array();
    static $params=array(
        'dsn' => DSN,
        'table' => 'managers',
        'usernameCol' => 'account',
        'passwordCol' => 'password',
        'cryptTypeCol' => 'algorithm',
        'saltCol' => 'salt',
        'isActiveCol' => 'is_actived',
        'isActiveAllowed' => '1',
        'deletedCol' => 'is_deleted',
        'deletedAllowed' => '0',
        'db_fields' => '*',
    );
    static $AuthData=array();
    static $encryptPassword='';
    
    function __call($name, $arguments) {
    }
    function login( $username , $password , $autologin=false ){
        if( is_string(self::$params['db_fields']) ){
            self::$params['db_fields']=array(self::$params['db_fields']);
        }
        
        $sql ="SELECT ".implode(',', self::$params['db_fields'])." FROM ".self::$params['table'];
        $sql.=" WHERE ".self::$params['isActiveCol']."=".APP::$mdb->quote(self::$params['isActiveAllowed'],'text');
        $sql.=" AND ".self::$params['deletedCol']."=".APP::$mdb->quote(self::$params['deletedAllowed'],'text');
        if( ! $autologin ){
            $sql.=" AND ".self::$params['usernameCol']."=".APP::$mdb->quote($username,'text');
        }else{
            $sql.=" AND id=".APP::$mdb->quote($username,'text');
        }
        $rows=Model::fetchAll($sql);
        //echo count($rows).'<br>';
        //開始驗證
        //如果取出超過一筆，表示資料完整性有問題，驗證失敗
        if( count($rows) != 1 ){
            return false;
        }
        $userdata=pos($rows);
        if( empty($userdata['algorithm']) || empty($userdata['salt']) || empty($userdata['password']) ){
            return false;
        }
        $userid=$userdata['id'];
        $algorithm=$userdata['algorithm'];
        $salt=$userdata['salt'];
        
        if( ! $autologin ){
            if( ! function_exists($algorithm) ){
                return false;
            }
            $encrypt = $algorithm( $salt.$password.$salt );
            if( $encrypt !== $userdata[ self::$params['passwordCol'] ] ){
                return false;
            }
        }else{
            if( $password !== $userdata[ self::$params['passwordCol'] ] ){
                return false;
            }
        }
        
        // When Verify Passed, go throuth here.
        self::$AuthData = $userdata;
        self::$encryptPassword = $encrypt;
        
        $sql ='UPDATE '.self::$params['table'];
        $sql.=' SET last_login='.APP::$mdb->quote( date('Y-m-d H:i:s'), 'date');
        $sql.=' , last_login_ip='.APP::$mdb->quote( self::getUserClientIP() , 'text');
        $sql.=' WHERE id = '.APP::$mdb->quote( $userdata['id'], 'text' );
        APP::$mdb->exec($sql);
        
        return true;
    }
    function getLoginForm( $formname='' ){
        APP::load('pear', 'HTML/QuickForm');
        
        $form=Form::create($formname, 'post', APP::$ME );
        
        $form->addElement('password', 'account', '管理者');
        $form->addElement('password', 'password', '密碼');
        $form->addElement('advcheckbox', 'remember', '', '兩週內記得我的登入', '', array('no','auto'));
        $form->addElement('submit', '', '送出');
        
        $form->addRule( 'account', '管理者名稱必填', 'required', '', 'client');
        //$form->addRule( 'account', '管理者名稱長度區間', 'rangelength', array( 2,32 ), 'client');
        //$form->addRule( 'account', '管理者名稱只允許英文和數字', 'alphanumeric', '', 'client');
        //$form->addRule('account', '管理者名稱必須是中文', 'regex', '/^[\x{4e00}-\x{9fff}]+$/u', '');
        $form->addRule( 'account', '管理者名稱只允許包含中文、英文、數字或符號"_ @ ."', 'regex', '/^[a-zA-Z0-9\_\@\.\x{4e00}-\x{9fff}]+$/u', '');
        $form->addRule( 'password', '密碼必填', 'required', '', 'client');
        //$form->addRule( 'password', '密碼長度區間', 'rangelength', array(6,64), 'client');
        
        $form->applyFilter('account', 'trim');
        
        return $form;
    }
    function changePassword( $data ){
        //確認密碼輸入後變更密碼
        $sql = "SELECT * FROM managers WHERE id=".Model::quote($data['id'], 'text');
        $row = Model::fetchRow( $sql );
        $algorithm=$row['algorithm'];
        $check['salt']=$row['salt'];
        $check['password']=$algorithm( $check['salt'].$data['password'].$check['salt'] );
        if( $check['password'] != $row['password'] ){
            return '原密碼輸入錯誤';
        }
        if( $data['password1'] !== $data['password2'] ){
            return '兩次密碼輸入不同';
        }
        
        
        if( self::passwd( $data['id'], $data['password1'] ) ){
            return true;
        }
        return '密碼變更失敗，請再試一次';
    }
    function passwd( $id, $password ){
        //直接變更密碼
        //encrypt password
        $data=array();
        $data['id']=$id;
        $encrypt=self::encrypt( $password );
        $data['algorithm']=$encrypt['algorithm'];
        $data['salt']=$encrypt['salt'];
        $data['password']=$encrypt['encrypt'];
        
        $register_fields=array(
            'algorithm' => 'text',
            'salt' => 'text',
            'password' => 'text',
        );
        if( Model::update( $data , 'id' , 'managers', $register_fields ) ){
            return true;
        }
        return false;
    }
    function encrypt( $password ){
        //產生加密資訊
        $algorithm='sha1';
        $salt=$algorithm(uniqid());
        $data['algorithm']=$algorithm;
        $data['salt']=$salt;
        $data['encrypt']=$algorithm( $salt.$password.$salt );
        
        return $data;
    }
    function getAuthData(){
        $authdata=self::$AuthData;
        if( count($authdata)<1 ){ return array(); }
        
        unset($authdata[ self::$params['cryptTypeCol'] ]);
        unset($authdata[ self::$params['saltCol'] ]);
        unset($authdata[ self::$params['passwordCol'] ]);
        unset($authdata[ self::$params['isActiveCol'] ]);
        unset($authdata[ self::$params['deletedCol'] ]);
        unset($authdata[ 'plugin' ]);
        
        if( isset($authdata['contacts']) ){
            $authdata['contacts'] = unserialize($authdata['contacts']);
        }
        
        
        return $authdata;
    }
    function getEncryptPassword(){
        return self::$encryptPassword;
    }
    function getPrivileges( $userid ){
        //個人層級權限設定
        APP::load('model', 'Managers');
        
        $data = Managers::loadPrivileges( $userid );
        
        $privs = array();
        foreach( $data as $action => $info ){
            $privs[ $action ] = ( $info['access']==='allow' ) ? 'allow' : 'deny';
        }
        
        return $privs;
    }
    function getUserClientIP(){
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip){
                array_unshift($ips, $ip); $ip = FALSE;
            }
            $ips_levels=count($ips);
            for ($i = 0; $i<$ips_levels; $i++){
                if (!preg_match ('/^(10|172\.16|192\.168)\./', $ips[$i])){
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }
}
?>