<?php
class APP{
    static $SESSION=array(); //連結至 $_SESSION
    /* Controller */
    static $pageTitle=''; //頁面標題，會出現在 <title>{$pageTitle}</title>內容中的
    // $pageTitle = ( !empty($prefixTitle) ) ? $prefixTitle.'：'.$actionTitle : $actionTitle ;
    
    static $prefixTitle=''; //標題前綴詞
    static $mainTitle=''; //應用程式名稱
    static $actionTitle=''; //action名稱
    static $mainName=''; //程式關鍵字
    /* Database */
    static $mdb; //資料庫操作元件
    static $modelClass=''; //Model的物件名稱
    static $useModel=''; //Model的物件名稱，準備用來取代 $modelClass
    static $options=array(); //可隨程式需要自行放置資料
    
    /* Config */
    static $systemConfigs; //系統設定
    static $localeConfigs; //設定支援的語系檔
    static $databaseConfigs; //設定資料庫
    static $layoutsConfigs; //layout顯示設定
    
    static $routing=array();
    static $params=array();
    static $parents=array();
    static $handler=''; //紀錄本次執行，總管負責的程式名稱（不含.php）
    static $app='';     //app (controller) 的名稱
    static $doctype=''; //副檔名格式
    static $appBuffer=array(); //action執行完畢的結果回傳
    static $p=''; //傳入的URL
    static $ME=''; //傳入的URL，排除GET字串
    
    static $prefix=''; //網址前綴，系統使用的代號
    static $prefixFull=''; //網址前綴，真實路徑

    static $action=''; //記錄action
    static $id=''; //記錄APP 的 id，僅在setAction時產生，權限表對照依據 
    
    static $locale=array(); //語系資料儲存位置

    static $loadedFiles=array(
        'vendors'=>array(),
        'plugins'=>array(),
        'pears'=>array(),
        'controllers'=>array(),
        'models'=>array(),
        'helpers'=>array(),
        'behaviors'=>array(),
    ); //記錄以載入的library
    
    /* Syslog */
    static $prior = array(
        7=>'Emergency',
        6=>'Alert',
        5=>'Critical',
        4=>'Error',
        3=>'Warning',
        2=>'Notice',
        1=>'Info',
        0=>'Debug'
    );
    function setAction( $action='' ){
        self::$action = $action;
        self::$id=''; //app 連同 action 的代表號
        if( !empty($action) ){
            self::$id = self::$prefix.'.'.self::$app.'.'.$action;
        }
    }
    function setPrefix( $prefix ){
        APP::$prefix = $prefix;
        APP::$routing['prefix'] = $prefix;
    }
    function getAction(){
        return self::$action;
    }
    function getModelClassName( $app_name = '' ){
        if( empty($app_name) )
        {
            $app_name = self::$app;
        }
        return ul2uc($app_name);
    }
    function ID(){
        return self::$id;
    }
    function parseFullID( $item_id='' ){
        $item_levels = 0;
        if( ! empty($item_id) ){
            $items=explode('.', $item_id);
            $item_levels = count($items);
        }
        
        $prefix=APP::$prefix;
        $app=APP::$app;
        switch( $item_levels ){
            case 1:
                // 1 個參數的時候，item_id 表示 app
                $item_id=$prefix.'.'.$app.'.'.$item_id;
                break;
            case 2:
                // 2 個參數的時候，item_id 表示 app.action
                $item_id=$prefix.'.'.$item_id;
                break;
            case 3:
                // 3 個參數的時候，item_id 表示 prefix.app.action
                $item_id=$item_id;
                break;
            default:
                // 0 個或超過 3 個參數的時候，錯誤
                return '';
        }
        return $item_id;
    }
    function syslog($message, $prior='Notice', $type='MESSAGE', $custom_userid=''){
        $mdb=self::$mdb;
        
        $userid='<--SYSTEM-->';
        if( isset($_SESSION['admin']['userid']) )
            $userid=$_SESSION['admin']['userid'];
        if( ! empty($custom_userid) ){
            $userid=$custom_userid;
        }
        
        $data_id = '';
        if( strtoupper($type)=='DATA' ){
            $data_id = $custom_userid;
            $userid = '';
            if( isset($_SESSION['admin']['userid']) ){
                $userid=$_SESSION['admin']['userid'];
            }
        }
        
        APP::load('plugin', 'auth.component');
        $ip=AuthComponent::getUserClientIP();
        
        $fields=array();
        $fields['id']=$mdb->quote( uniqid('LOG' ), 'text' );
        $fields['site_key']=$mdb->quote( APP::$routing['site'] , 'text' );
        $fields['prefix_key']=$mdb->quote( APP::$routing['prefix'] , 'text' );
        $fields['app_key']=$mdb->quote( APP::$routing['app'] , 'text' );
        if( !empty($data_id) ) $fields['data_id']=$mdb->quote( $data_id , 'text' );
        if( !empty($type) )    $fields['type']=$mdb->quote( strtoupper($type) , 'text' );
        if( !empty($prior) ){
            $fields['prior_name']=$mdb->quote( $prior , 'text' );
            $fields['prior_id']=$mdb->quote( array_search($prior, self::$prior) , 'text' );
        }
        if( !empty($userid) )  $fields['manager_name']=$mdb->quote( $userid , 'text' );
        if( !empty($ip) )      $fields['ip']=$mdb->quote( $ip , 'text' );
        if( !empty($message) ) $fields['name']=$mdb->quote( $message , 'text' );
    
    	$tb='syslog';$fs=array();$vs=array();
    	foreach( $fields as $f=>$v ){ $fs[]=$f; $vs[]=$v; }
    	$fs[]='created'; $vs[]='NOW()';
    	
    	$sql=sprintf("INSERT INTO $tb ( %s ) VALUES ( %s )",implode(',',$fs),implode(',',$vs));
    	
    	echo $sql;exit();
    	$res=APP::$mdb->exec($sql);
    	if( APP::$mdb->isError() ){
    		errmsg('Syslog Error');
        }
    }
    function pass(){
        // 傳遞變數至 APP::$appBuffer 的標準函式
        $args = func_get_args();
        
        if( count($args) < 1 ){
            errmsg('必須傳入至少1個參數');
        }
        if( count($args) === 1 ){
            $args = (array)$args[0];
        }
        $buffers = (array)APP::$appBuffer;
        $buffers = array_merge( $buffers, $args );
        
        APP::$appBuffer = $buffers;
    }
    function take(){
        // 取出 APP::$appBuffer 內容的標準函式
        return APP::$appBuffer;
    }
    function load( $type , $name='' ){
        $msg=$name;
        if( empty($msg) ) $msg='current';
        marktime('AppExecute', 'Start Loading '.ul2uc($type).': <span style="color:green;">'.ul2uc($msg).'</span>' );
        switch( $type ){
            case 'pear':
                self::_loadPear( $name );
                break;
            case 'vendor':
                self::_loadVendor( $name );
                break;
            case 'plugin':
                self::_loadPlugin( $name );
                break;
            case 'controller':
                self::_loadController( $name );
                break;
            case 'model':
                self::_loadModel( $name );
                break;
            case 'helper':
                self::_loadHelper( $name );
                break;
            case 'behavior':
                self::_loadBehavior( $name );
                break;
            default:
                errmsg('指定的參數錯誤: TYPE: '.$type.' , NAME: '.$name.'</strong> Error.');
                return false;
        }
        marktime('AppExecute', 'Load'.ul2uc($type).':  <span style="color:green;">'.ul2uc($msg).'</span> Over' );
    }
    function _loadPear( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('pears',$name) ){
            errmsg('指定的 Pear: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function _loadVendor( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('vendors',$name) ){
            errmsg('指定的 Vendor: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function _loadPlugin( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('plugins',$name) ){
            errmsg('指定的 Plugin: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function _loadController( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('controllers',$name) ){
            errmsg('指定的 Controller: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function _loadModel( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('models',$name) ){
            errmsg('指定的 Model: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function _loadHelper( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('helpers',$name) ){
            errmsg('指定的 Helper: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function _loadBehavior( $name ){
        if( ! is_string($name) ){
            errmsg('指定的參數錯誤: <strong>必須是字串</strong>');
            return false;
        }
        
        if( ! self::FileLoader('behaviors',$name) ){
            errmsg('指定的 Behavior: <strong>'.$name.'</strong> 找不到.');
            return false;
        }
        return true;
    }
    function FileLoader( $type , $name , $resource=array() ){
        
        if( !in_array( $type , array('pears','vendors','plugins','controllers','models','helpers','behaviors') ) ){
            errmsg('Given Parameters: '.$type.' Not Allowed in '.__FUNCTION__);
        }
        $basepath=DIRLIB.$type.DS;
        switch( $type ){
            case 'pears':
                $basepath='';
                if( !in_array( strtolower($name) , APP::$loadedFiles[$type] ) ){
                    require($basepath.$name.EXT);
                    //marktime(__FUNCTION__, 'Load '.ucfirst($type).' '.$name);
                    APP::$loadedFiles[ $type ][]=strtolower($name);
                }
                return true;
                break;
            case 'plugins':
                $basepath=BASEROOT.$type.DS;
                if( !in_array( strtolower($name) , APP::$loadedFiles[$type] ) ){
                    require($basepath.$name.EXT);
                    //marktime(__FUNCTION__, 'Load '.ucfirst($type).' '.$name);
                    APP::$loadedFiles[ $type ][]=strtolower($name);
                }
                return true;
                break;
            case 'vendors':
                $basepath=DIRROOT.$type.DS;
                if( !in_array( strtolower($name) , APP::$loadedFiles[$type] ) ){
                    require($basepath.$name.EXT);
                    //marktime(__FUNCTION__, 'Load '.ucfirst($type).' '.$name);
                    APP::$loadedFiles[ $type ][]=strtolower($name);
                }
                return true;
                break;
            case 'controllers':
                $basepath=DIRROOT;
                if( ! in_array( uc2ul($name) , APP::$loadedFiles[$type] ) ){
                    $modelPath = uc2ul($name).'_controller'.EXT;
                    if( APP::$prefix!='main' ){ $modelPath = APP::$prefix.'#'.$modelPath; }
                    require($basepath.$modelPath);
                    //marktime(__FUNCTION__, 'Load '.ucfirst($type).' '.$name);
                    APP::$loadedFiles[ $type ][]=uc2ul($name);
                }
                return true;
                break;
            case 'models':
                $basepath=DIRROOT;
                if( ! in_array( uc2ul($name) , APP::$loadedFiles[$type] ) ){
                    $modelPath = uc2ul($name).'_model'.EXT;
                    if( APP::$prefix!='main' ){ $modelPath = APP::$prefix.'#'.$modelPath; }
                    require($basepath.$modelPath);
                    //marktime(__FUNCTION__, 'Load '.ucfirst($type).' '.$name);
                    APP::$loadedFiles[ $type ][]=uc2ul($name);
                }
                return true;
                break;
            case 'helpers':
                $basepath=DIRROOT.'model_'.APP::$prefix.DS;
                if( ! in_array( strtolower($name) , APP::$loadedFiles[$type] ) ){
                    require($basepath.'helper.'.strtolower($name).EXT);
                    APP::$loadedFiles[ $type ][]=strtolower($name);
                }
                return true;
                break;
            case 'behaviors':
                $basepath=DIRROOT.'model_'.APP::$prefix.DS;
                if( ! in_array( strtolower($name) , APP::$loadedFiles[$type] ) ){
                    require($basepath.'behavior.'.strtolower($name).EXT);
                    APP::$loadedFiles[ $type ][]=strtolower($name);
                }
                return true;
                break;
        }
    }

}
class AppModel{
    static $useTable='';
    static $upload_dir='';

    static $parent_id='';
    static $parent_data=array();
    static $queryMasks=array();

    public static function findById( $id ){
        $multiple = true;
        if( is_string($id) ){
            $multiple = false;
            $id = array($id);
        }
        $id_list=array();
        foreach( $id as $r ){
            $id_list[]=Model::quote($r, 'text');
        }
        $sql = "SELECT * FROM ".static::$useTable." WHERE id IN (".implode(',', $id_list).")";
        if( is_array(static::$queryMasks) && count(static::$queryMasks) > 0 ){
            $sql.=' AND '.implode(' AND ', static::$queryMasks);
        }
        if( $multiple ){
            $data = Model::fetchAll( $sql );
        }else{
            $data = Model::fetchRow( $sql );
        }
        return $data;
    }
    public static function findByUrn( $urn ){
        $id = $urn;

        $multiple = true;
        if( is_string($id) ){
            $multiple = false;
            $id = array($id);
        }
        $id_list=array();
        foreach( $id as $r ){
            $id_list[]=Model::quote($r, 'text');
        }
        $sql = "SELECT * FROM ".static::$useTable." WHERE urn IN (".implode(',', $id_list).") AND is_deleted='0'";
        if( is_array(static::$queryMasks) && count(static::$queryMasks) > 0 ){
            $sql.=' AND '.implode(' AND ', static::$queryMasks);
        }
        if( $multiple ){
            $data = Model::fetchAll( $sql );
        }else{
            $data = Model::fetchRow( $sql );
        }
        return $data;
    }
    public static function setParentID( $parent_id ){
        static::$parent_id = $parent_id;
    }
    public static function getParentID(){
        return static::$parent_id;
    }
    public static function setParentData( $data ){
        static::$parent_data = $data;
    }
    public static function getParentData(){
        return static::$parent_data;
    }
}


class Model{
    static $relation=array();
    static $useTable='';
    static $plugin='';
    static $mask;
    
    static $masterModel=''; //記錄誰將成為主要Model，紀錄 Model Class Name, ex. MainModel
    static $masterTable=''; 
    static $masterConfigs=array(); //記錄主要Model的各項設定

    static $dryRun=false; // 模擬運作，不執行
    static $showQuery=false; // 一邊運作，一邊輸出執行的程式碼
    
    function insert( $fields , $useTable='', $register_fields=array() ){
        if( ! is_array($register_fields) ){
            $register_fields=array();
        }
        //pr($fields);die;
    	$fs=array();$vs=array();
    	foreach( $fields as $f=>$v ){
            if( ! array_key_exists( $f , $register_fields ) ){
                continue;
            }
            $fs[]="`".$f."`";
            $field_type = $register_fields[ $f ];
            $vs[]=self::quote($v, $field_type);
        }
    	
        if( count($fs) < 1 ){
            self::_listFields($fields);
            errmsg('沒有寫入的欄位，請檢查您的輸入，或是否尚未指定欄位註冊表 $register_fields');
    	}
    	
    	$sql=sprintf("INSERT INTO ".$useTable." ( %s ) VALUES ( %s )",implode(',',$fs),implode(',',$vs));
    	
    	return Model::execute($sql);
    }
    function _listFields( $fields ){
        //pr($fields);
        /*  允許的格式參數及其預設值
        var $valid_default_values = array(
            'text'      => '',
            'boolean'   => true,
            'integer'   => 0,
            'decimal'   => 0.0,
            'float'     => 0.0,
            'timestamp' => '1970-01-01 00:00:00',
            'time'      => '00:00:00',
            'date'      => '1970-01-01',
            'clob'      => '',
            'blob'      => '',
        );
        */
        if( APP::$systemConfigs['Production'] == 1 ){ return; }
        echo '<pre>';
        echo '        $register_fields=array('."\n";
        foreach( $fields as $key=>$value ){
            //if( ! is_string($value) ){ continue; }
            $type = 'text';
            if( is_string($value) ){ $type = 'text'; }
            elseif( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value) ){ $type="timestamp"; }
            elseif( preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ){ $type="date"; }
            elseif( preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) ){ $type="time"; }
            elseif( is_integer($value) ){ $type='integer'; }
            elseif( is_float($value) ){ $type='float'; }
            elseif( is_bool($value) ){ $type='boolean'; }
            echo "            '".$key."' => '".$type."',"."\n";
        }
        echo '        );'."\n";
        echo '</pre>';
    }
    function inserts( $rows , $useTable='' , $register_fields=array() ){
        if( count($rows) < 1 ){
            return;
    	}
        if( ! is_array($register_fields) ){
            $register_fields=array();
        }

    	$fs=array();
    	$first=true;
        foreach( $rows as $fields ){
            
            $vs=array();
        	foreach( $fields as $f=>$v ){
                if( ! array_key_exists( $f , $register_fields ) ){
                    continue;
                }
                if( $first ){ $fs[]="`".$f."`"; }
                
                $field_type = $register_fields[ $f ];
                $vs[]=Model::quote( $v, $field_type );
            }
        	$first=false;
        	
        	$values[]='('.implode(',',$vs).')';
        }
    	
        if( count($fs) < 1 ){
            self::_listFields( pos($rows) );
            errmsg('沒有寫入的欄位，請檢查您的輸入，或是否尚未指定欄位註冊表 $register_fields');
    	}
    	
    	$sql=sprintf("INSERT INTO ".$useTable." ( %s ) VALUES %s",implode(',',$fs),implode(',',$values));
    	
    	return Model::execute($sql);
    }
    function update( $fields , $identify='id' , $useTable='' , $register_fields=array() ){
        if( is_string($identify) ){
            if( empty($identify) ){ errmsg('更新的對照欄位不能空白（以哪個欄位的內容為更新範圍）'); }
            $identify=array($identify);
        }
        if( ! is_array($register_fields) ){
            $register_fields=array();
        }
        $where_list=array();

        //update條件參數是陣列的情況
        if( is_array($identify) ){
            foreach($identify as $idf){
                //如果不是陣列，則視為一般值（ = or LIKE）處理
                if( ! is_array($fields[$idf]) ){
                    if( substr($fields[$idf], 1, 1)==='%' || substr($fields[$idf], -1, 1) ){
                        $where_list[]=$idf.' LIKE '.self::quote($fields[$idf], 'text');
                        unset($fields[$idf]);
                        continue;
                    }
                    $where_list[]=$idf.'='.self::quote($fields[$idf], 'text');
                    unset($fields[$idf]);
                    continue;
                }
                //如果是陣列且陣列元素大於0，則以 IN 清單處理
                if( is_array($fields[$idf]) && count($fields[$idf])>0 ){
                    $idf_list=array();
                    foreach( $fields[$idf] as $idf_value ){
                        $idf_list[] = self::quote( $idf_value );
                    }
                    $where_list[]=$idf.' IN ('.implode(', ', $idf_list).')';
                    unset($fields[$idf]);
                    continue;
                }
                //如果都不是（是陣列，但元素小於1。或根本不是陣列），直接捨棄
                unset($fields[$idf]);
            }
        }
        
    	$fs=array();$vs=array();$field_type='';
        foreach( $fields as $f=>$v ){
            if( is_numeric($f) ){ //如果 $field 為純數字(沒有指定欄位名稱，表示這個項目是條件)，表示 $value 直接成為條件
                $fs[]="`".implode('`=`', explode('=', $v) )."`";
                continue;
            }
            //過濾未出現在列表中的欄位
            if( ! array_key_exists( $f , $register_fields ) ){
                continue;
            }

            $field_type = $register_fields[ $f ];
            
            $fs[]="`".$f."`".'='.self::quote($v, $field_type);
        }
    	
        if( count($fs) < 1 ){
            self::_listFields($fields);
            errmsg('沒有寫入的欄位，請檢查您的輸入，或是否尚未指定欄位註冊表 $register_fields');
    	}
    	
    	if(count($where_list)<1){ errmsg('更新基準欄位 $identify (ex.id) 不能空白'); }
    	
    	$sql=sprintf("UPDATE ".$useTable." SET %s WHERE %s",implode(',',$fs), implode(' AND ',$where_list) );
    	//pr($sql);echo "\n\n";
        //if( in_array('contents', $fs) ){die;}
    	//file_put_contents(DIRROOT.'sql_log.txt', $sql."\n", FILE_APPEND);
    	return Model::execute($sql);
    }
    function updates( $rows , $identify='id' , $useTable='' , $register_fields=array() )
    {
        if( count($rows) < 1 ){
            // 如果沒有傳入值，直接當執行完畢處理
            return true;
        }
        if( is_string($identify) ){
            if( empty($identify) ){ errmsg('更新的對照欄位不能空白（以哪個欄位的內容為更新範圍）'); }
            $identify=array($identify);
        }
        if( ! is_array($register_fields) ){
            $register_fields=array();
        }

        //update條件參數是陣列的情況
        if( is_array($identify) ){
            $identifies_sql = array();
            foreach($identify as $idf){
                $identifies_sql[] = $idf.' = VALUES('.$idf.')';
            }
        }

        $fs=array();
        $fs_list=array();
        $first=true;
        foreach( $rows as $fields ){
            
            $vs=array();
            foreach( $fields as $f=>$v ){
                if( ! array_key_exists( $f , $register_fields ) ){
                    continue;
                }
                if( $first ){
                    $fs[] = "`".$f."`";
                    $fs_list[] = $f;
                }
                
                $field_type = $register_fields[ $f ];
                $vs[]=Model::quote( $v, $field_type );
            }
            $first=false;
            
            $values[]='('.implode(',',$vs).')';
        }
        
        if( count($fs) < 1 ){
            self::_listFields( pos($rows) );
            errmsg('沒有寫入的欄位，請檢查您的輸入，或是否尚未指定欄位註冊表 $register_fields');
        }

        $need_update_fields_sql = array();
        foreach( $fs_list as $f ){
            if( in_array($f, $identify) ){ continue; }
            $need_update_fields_sql[] = $f.' = VALUES('.$f.')';
        }
        
        $sql = sprintf("INSERT INTO ".$useTable." ( %s ) VALUES %s",implode(',',$fs),implode(',',$values));
        $sql.= " ON DUPLICATE KEY UPDATE ".implode(", ", $need_update_fields_sql);

        return Model::execute($sql);
    }
    /*function updates( $fields , $identify='id' , $useTable='' , $register_fields=array() ){
        $tmpTable = 'tmp_'.uniqid();
        //pr($fields);die;
        if( count($fields) < 1 ){
            errmsg('抱歉，您必須提供寫入資料');
        }

        // 判斷需要建立的暫存表欄位
        $table_data = array();
        foreach( $fields as $row ){
            $field_count = 0;
            foreach( $row as $field_name => $field_value ){
                $field_count += 1;
                // 如果未設定，或大於現有值，則記錄之
                if( ! isset($table_data[$field_name]) || (strlen($field_value) > $table_data[$field_name]) ){
                    $table_data[$field_name] = strlen($field_value);
                }
            }
        }
        if( $field_count < 1 ){
            errmsg('抱歉，您沒有提供寫入欄位');
        }
        //pr('here');die;
        //建立暫存表批次寫入，再用JOIN兩個表的方式批次更新
        $sql_rows = array();
        $fs = array();
        $sql_updates = array();
        $sql_update_joins = array();
        foreach( $table_data as $field_name => $field_length ){
            $sql_rows[] = " `".$field_name."` char(".$field_length.")";
            if( $field_name===$identify || (is_array($identify) && in_array($field_name, $identify))  ){
                $sql_update_joins[] = " u.`".$field_name."` = t.`".$field_name."`";
            }else{
                $sql_updates[] = " u.`".$field_name."` = t.`".$field_name."`";
            }
            //過濾未出現在列表中的欄位
            if( array_key_exists( $field_name , $register_fields ) ){
                $fs[ $field_name ] = 'text';
            }
        }
        if( count($fs) < 1 ){
            self::_listFields( pos($fields) );
            errmsg('沒有寫入的欄位，請檢查您的輸入，或是否尚未指定欄位註冊表 $register_fields');
        }

        $sql = "CREATE TEMPORARY TABLE ".$tmpTable." (\n";
        $sql.= implode(", \n", $sql_rows);
        $sql.= "\n) ENGINE = MEMORY DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        //pr($sql);die;
        if( ! Model::exec($sql) ){
            errmsg('建立暫存表失敗');
            return false;
        }

        $inserts = $fields;

        if( ! Model::inserts( $inserts, $tmpTable, $register_fields ) ){
            errmsg('寫入暫存的更新資料時失敗');
            return false;
        }

        $sql = "UPDATE ".$useTable." u, ".$tmpTable." t";
        $sql.= " SET";
        $sql.= implode(", \n", $sql_updates);
        $sql.= " WHERE";
        $sql.= implode(" AND ", $sql_update_joins);
        //pr($sql);die;
        $err = Model::exec($sql);

        $sql = "DROP TABLE ".$tmpTable;
        Model::exec($sql);
        if( $err ){
            return true;
        }
        return false;
    }*/
    function query($sql){
        self::_debugDryRun($sql);
        self::_debugShowQuery($sql);

        $result=APP::$mdb->query($sql);
        if( APP::$mdb->isError() ){
            Model::query_error($sql);
        }

        self::queryLog($sql);
        return $result;
    }
    function exec($sql){
        return Model::execute($sql);
    }
    function execute($sql){
        self::_debugDryRun($sql);
        self::_debugShowQuery($sql);

        $result=APP::$mdb->exec($sql);
        if( APP::$mdb->isError() ){
            Model::query_error($sql);
        }

        if( $result!==false ){
            self::execLog($sql);

            return true;
        }
        return false;
    }
    function numRows($sql){
        self::_debugShowQuery($sql);

        $res=$sql;
        if( is_string($sql) ){
            $res=APP::$mdb->query($sql);
            if( APP::$mdb->isError() ){
                Model::query_error($sql);
            }
        }
        $rows=APP::$mdb->numRows($res);
        self::queryLog($sql);
        return $rows;
    }
    function fetchAll($sql){
        self::_debugShowQuery($sql);

        $res=$sql;
        if( is_string($sql) ){
            $res=APP::$mdb->query($sql);
            if( APP::$mdb->isError() ){
                Model::query_error($sql);
            }
        }
        $rows=APP::$mdb->fetchAll($res);
        self::queryLog($sql);
        return $rows;
    }
    function fetchRow($sql){
        self::_debugShowQuery($sql);

        $res=$sql;
        if( is_string($sql) ){
            $res=APP::$mdb->query($sql);
            if( APP::$mdb->isError() ){
                Model::query_error($sql);
            }
        }
        $row=APP::$mdb->fetchRow($res);
        self::queryLog($sql);
        return $row;
    }
    function fetchOne($sql){
        self::_debugShowQuery($sql);

        $res=$sql;
        if( is_string($sql) ){
            $res=APP::$mdb->query($sql);
            if( APP::$mdb->isError() ){
                Model::query_error($sql);
            }
        }
        $col=APP::$mdb->fetchOne($res);
        self::queryLog($sql);
        return $col;
    }
    function queryLog($sql){
        //file_put_contents( DIRCACHE.'db_query.log' , date('Y-m-d H:i:s').' '.$sql."\n" , FILE_APPEND | LOCK_EX );
    }
    function execLog($sql){
        //file_put_contents( DIRCACHE.'db_exec.log' , date('Y-m-d H:i:s').' '.$sql."\n" , FILE_APPEND | LOCK_EX );
    }
    function execErrorLog($sql, $errmsg){
        file_put_contents( DIRCACHE.'db_exec_errors.log' , date('Y-m-d H:i:s').' '.$sql."\n".$errmsg."\n" , FILE_APPEND | LOCK_EX );
    }
    function quote($value, $type = null, $quote = true){
        /*  允許的格式參數及其預設值
        var $valid_default_values = array(
            'text'      => '',
            'boolean'   => true,
            'integer'   => 0,
            'decimal'   => 0.0,
            'float'     => 0.0,
            'timestamp' => '1970-01-01 00:00:00',
            'time'      => '00:00:00',
            'date'      => '1970-01-01',
            'clob'      => '',
            'blob'      => '',
        );
        */
        return APP::$mdb->quote($value, $type, $quote);
    }
    function escape($value){
        return APP::$mdb->escape($value);
    }

    function getOffsetStart($pageID=1, $pageRows=PAGEROWS ){
        $offsetStart = ($pageID-1) * $pageRows ;
        return $offsetStart;
    }

    function dryRun(){
        self::$dryRun = true;
        return true;
    }
    function stopDryRun(){
        self::$dryRun = false;
        return true;
    }
    function showQuery(){
        self::$showQuery = true;
        return true;
    }
    function stopShowQuery(){
        self::$showQuery = false;
        return true;
    }
    function _debugDryRun($sql){
        if( self::$dryRun ){ //模擬運作並回傳 $sql
            echo $sql.'<br><br>';
            return true;
        }
    }
    function _debugShowQuery($sql){
        if( self::$showQuery ){ //輸出 $sql 但繼續執行
            echo $sql.'<br><br>';
        }
        return true;
    }
    
    /**** Error Function ****/
    function query_error( $sql ){
        echo '<META CONTENT="text/html; charset=utf-8" HTTP-EQUIV="Content-Type">';
        $backtrace=debug_backtrace();
        $err=$backtrace[1];

        if( ! APP::$mdb->isError() ){ return; }
        
        $msg ='<p style="font-size:15px;color:black;font-weight:normal;"><b>'.$err['file'].' Line '.$err['line'].'</b></p>';
        $msg.='<p style="font-size:13px;color:black;font-weight:normal;"><b>'.$err['class'].'::'.$err['function'].'() Complain:</p>';
        $msg.='<p style="font-size:13px;color:black;font-weight:bold;">Error: <span style="color:red;">'.$sql.'</span></p>';
        $msg.='<p style="font-size:13px;color:black;font-weight:normal;"><b>Message:</b> '.mysql_errno($_link).' '.mysql_error($_link).'</p>';
        $msg.=debugBacktrace();

        $log = $err['file'].' Line '.$err['line']."\n";
        $log.= $err['class'].'::'.$err['function'].'() Complain:'."\n";
        $log.= mysql_errno($_link).' '.mysql_error($_link)."\n";
        self::execErrorLog($sql, $log);

        echo $msg;
        stop_progress();
    }
    function connect_error( $func , $mdb ){
        echo '<META CONTENT="text/html; charset=utf-8" HTTP-EQUIV="Content-Type">';
        if( APP::$systemConfigs['Production']==1 ){ redirect( 500 ); }
        print( "Database Connection Error: " . $mdb->getMessage() . '<br>' . $mdb->getDebugInfo() );
        stop_progress();
    }
}

class View{
    static $Code=200;
    static $cacheLifeTime=-1; //render時的cache存活時間，-1時表示使用layout cache的預設值
    static $layoutConfigs=array(); //輸出頁面的設定資料
    static $hasRendered = false;

    static $errorTplPath=''; // may not been use, just preserved.
    static $templateTplPath=''; //may not been use, just preserved.
    static $viewTplPath='';
    
    function render( $type='', $name='', $options=array() ){
        // 指定特定layout, ex. main: layout_main, admin: layout_admin，否則就是預設版型
        // 除了 layout 之外，其他 options 參數都會變成傳入的變數（只有template支援）
        $layout = APP::$prefix;
        if( isset($options['layout']) && ! empty($options['layout']) ){
            $layout = $options['layout'];
            unset($options['layout']);
        }
        // 設定$type的預設值
        if( empty($type) ){
            $type = 'view';
        }
        // 檢查是否有 prefix functions file
        $run_bootstrap = false;
        $file_name = '_'.APP::$prefix.'_functions.php';
        
        $global_class = ul2uc(APP::$prefix).'Function'; // ex. MainFunction , AdminFunction
        if( file_exists( DIRROOT.$file_name ) ){
            $run_bootstrap = true;
        }
        if( $run_bootstrap && method_exists($global_class, 'beforeRender') ){
            $global_class::beforeRender();
        }

        //type: error, template, view
        switch( $type ){
            case 'error':
                if( $run_bootstrap && method_exists($global_class, 'beforeRenderError') ){
                    $global_class::beforeRenderError();
                }

                $path = 'layout_'.$layout.'/errors/'.$name.EXT;
                $file = DIRROOT.$path;
                if( ! file_exists($file) ){
                    errmsg('您指定的錯誤呈現頁: '.$path.' 不存在');
                }
                if( ! View::isRendered() ){
                    View::setRendered();
                    include( $file );
                }
                
                if( APP::$systemConfigs['Production']==0 ){
                    //除錯模式下，出現ERROR PAGE應主動標示執行程序
                    echo '<h1>Error '.$name.'</h1>';
                    echo debugBacktrace();
                    echo '<br><br><br><br><br>';
                }

                //呼叫系統的全程式終止程序
                stop_progress();
                
                break;
            case 'template':
                marktime( 'Core', 'Start Rendering Template: '.$name );
                if( $run_bootstrap && method_exists($global_class, 'beforeRenderTemplate') ){
                    $global_class::beforeRenderTemplate();
                }

                $path = 'layout_'.$layout.'/tpl_'.$name.EXT;
                $file = DIRROOT.$path;
                if( ! file_exists($file) ){
                    errmsg('您指定的 Template: '.$path.' 不存在');
                }

                View::setRendered();

                // 將options陣列展開為變數
                extract($options);

                include( $file );

                break;
            case 'view':
                if( empty($name) && empty(self::$viewTplPath) ){
                    $action_name = $name;
                    self::setViewTplPath($action_name);
                }

                marktime( 'Core', 'Start Rendering View: '.$name );
                
                $path = self::getViewTplPath();
                if( ! $path ){
                    errmsg('抱歉！不帶任何參數呼叫 View::render() 前，您必須先使用 APP::setAction() 指定 action 名稱');
                }

                if( $run_bootstrap && method_exists($global_class, 'beforeRenderView') ){
                    $global_class::beforeRenderView();
                }

                $file = DIRROOT.$path;
                if( ! file_exists($file) ){
                    errmsg('您指定的 View: '.$path.' 不存在');
                }

                View::setRendered();

                include( $file );

                break;
            default:
                errmsg('非預期的 type 參數，不知道該怎麼做');
        }
    }
    function setRendered(){
        View::$hasRendered = true;
    }
    function isRendered(){
        return View::$hasRendered;
    }
    function set(){ //設定要使用的action template (viewTpl)
        $args = func_get_args();
        $args_count = func_num_args();

        switch( $args_count ){
            case 1:
                $view_name = array_shift($args);
                self::setViewTplPath( APP::$handler.'='.$view_name.EXT );
                break;
            case 2:
                $app_name = array_shift($args);
                $view_name = array_shift($args);

                $prefix = APP::$prefix.'#';
                if( APP::$prefix === 'main' ){ $prefix = ''; }
                self::setViewTplPath( $prefix.$app_name.'='.$view_name.EXT );
                break;
            case 3:
                $prefix_name = array_shift($args);
                $prefix = $prefix_name.'#';
                if( $prefix_name === 'main' ){ $prefix = ''; }

                $app_name = array_shift($args);
                $view_name = array_shift($args);
                self::setViewTplPath( $prefix.$app_name.'='.$view_name.EXT );
                break;
            case 4:
                $site_name = array_shift($args);

                $prefix_name = array_shift($args);
                $prefix = $prefix_name.'#';
                if( $prefix_name === 'main' ){ $prefix = ''; }

                $app_name = array_shift($args);
                $view_name = array_shift($args);
                self::setViewTplPath( '../site.'.$site_name.'/'.$prefix.$app_name.'='.$view_name.EXT );
                break;
            default:
                errmsg('設定的參數有問題');
                break;
        }
        return true;
    }
    function getLayoutPath(){
        return DIRROOT.'layout_'.APP::$prefix.'/';
    }
    function getViewTplPath(){
        $viewTplPath = self::$viewTplPath;
        if( empty($viewTplPath) ){
            $action_name = APP::getAction();
            if( empty($action_name) ){
                return false;
            }
            $viewTplPath = APP::$handler.'='.$action_name.EXT;
            self::setViewTplPath( $viewTplPath );
        }
        return self::$viewTplPath;
    }
    private static function setViewTplPath( $tplPath ){
        self::$viewTplPath = $tplPath;
        return true;
    }
    function setTitle( $pageTitle ){
        self::$layoutConfigs['title']=$pageTitle;
        return true;
    }
    function setLayout( $prefix ){
        if( ! array_key_exists( $prefix, APP::$layoutsConfigs ) ){
            errmsg('這個 layout config profile 不存在');
            return false;
        }
        
        $_default = array(
            'http_metas'=>array(),
            'sitename'=>'',
            'title'=>'',
            'metas'=>array(),
            'stylesheets'=>array(),
            'javascripts'=>array(),
            'extra'=>array(), // 提供 use_extra_header 使用
            'footers'=>array(), // 提供 use_extra_footer 使用
        );
        $_default = array_merge( $_default , APP::$layoutsConfigs['default'] );
        
        View::$layoutConfigs = array_merge( $_default , APP::$layoutsConfigs[ $prefix ] );
        
        return true;
    }
    function getTitle(){
        return self::$layoutConfigs['title'];
    }
    function getHeader( $name ){
        $key='';
        if( strpos($name, '.')!==false ){ list($name, $key)=explode('.', $name); }

        $_keys = array(
            'http_meta','http_metas','sitename','title','meta','metas','stylesheets','javascripts','stylesheet','javascript',
            'has_layout','layout','template','link','links',
        );
        if( ! in_array($name, $_keys) ){
            errmsg('不支援這個屬性設定：'.$name);
        }
        if( in_array($name, array('http_meta','meta','stylesheet','javascript','link')) ){
            $name.='s';
        }

        if( empty($key) ){
            $data = self::$layoutConfigs[$name];
        }else{
            $data = self::$layoutConfigs[$name][$key];
        }
        return $data;
    }
    function setHeader( $name, $value ){ //設定Layout標頭<head>
        if( strpos($name, '.')!==false ){ list($name, $key)=explode('.', $name); }
        
        $_keys = array(
            'http_meta','http_metas','sitename','title','meta','metas','stylesheets','javascripts','stylesheet','javascript',
            'has_layout','layout','template','link','links',
        );
        if( ! in_array($name, $_keys) ){
            errmsg('不支援這個屬性設定：'.$name);
        }
        if( in_array($name, array('http_meta','meta','stylesheet','javascript','link')) ){
            $name.='s';
        }
        
        $_appends = array('http_metas','metas','links');
        if( in_array($name, $_appends) ){
            self::$layoutConfigs[$name][$key] = $value;
            return true;
        }
        //以下屬性用疊加的方式設定參數
        $_appends = array('stylesheets','javascripts');
        if( in_array($name, $_appends) ){
            if( is_string($value) ){
                self::$layoutConfigs[$name][] = $value;
                return true;
            }
            self::$layoutConfigs[$name]=array_merge( self::$layoutConfigs[$name], $value );
            return true;
        }
        if( $name == 'has_layout' ){
            if( $value ){
                self::$layoutConfigs[$name]=true;
                return true;
            }
            self::$layoutConfigs[$name]=false;
            return true;
        }
        self::$layoutConfigs[$name]=$value;
        return true;
    }
    function element(){
        $args = func_get_args();
        
        //判讀tpl名稱
        $tpl_name = array_shift($args);
        
        //設定預設的prefix
        $prefix = APP::$prefix;

        //判讀輸出錯誤頁
        if( $tpl_name === 'error' ){
            $tpl_name = array_shift($args);
            
            $tpl_path = DIRROOT.'layout_'.$prefix.DS.'errors'.DS.$tpl_name.EXT;
            if( ! file_exists($tpl_path) ){
                errmsg('指定的 錯誤頁 不存在: layout_'.$prefix.DS.'errors'.DS.$tpl_name.EXT);
            }
            
            //檢查有沒有額外參數，有的話，取出額外參數的陣列
            $params = array();
            if( count($args)>0 ){
                $params = array_shift($args);
                if( ! is_array($params) ){
                    errmsg('傳入的參數必須是陣列');
                }
            }
            
            //拆解參數為變數
            extract($params);
            //取出tpl
            include($tpl_path);
            
            //錯誤頁輸出將強制結束程式
            die;
        }
        
        //如果第一個參數是prefix名稱，則第二個參數便是tpl名稱
        //否則自動使用預設的prefix
        if( array_key_exists( $tpl_name, RoutingConfigs::$apps) ){
            $prefix = $tpl_name;
            $tpl_name = array_shift($args);
        }
        
        //檢查有沒有額外參數，有的話，取出額外參數的陣列
        $params = array();
        if( count($args)>0 ){
            $params = array_shift($args);
            if( ! is_array($params) ){
                errmsg('傳入的參數必須是陣列');
            }
        }
        
        $tpl_path = DIRROOT.'layout_'.$prefix.DS.'tpl_'.$tpl_name.EXT;
        if( ! file_exists($tpl_path) ){
            errmsg('指定的 template 不存在: layout_'.$prefix.DS.'tpl_'.$tpl_name.EXT);
        }
        
        //拆解參數為變數
        extract($params);
        //取出tpl
        include($tpl_path);
    }
    /**** Head tag utilities start ****/
    function link( $params, $absolute_src=false ){
        if( is_string($params) ){
            $params=array('href'=>$params);
        }
        $_default = array(
            'rel' => 'stylesheet',
        );
        $_data = array_merge( (array)$_default , (array)$params );
        
        if( ! $absolute_src ){
            $_data['href']=self::layout_url($_data['href']);
        }

        $prefix=self::_attrs_to_str( $_data );
        
        return '<link'.$prefix.'>';
    }
    function script( $params, $absolute_src=false ){
        if( is_string($params) ){
            $params=array('src'=>$params);
        }
        $_default = array(
            //'type' => 'text/javascript',
        );
        $_data = array_merge( (array)$_default , (array)$params );
        
        if( ! $absolute_src ){
            $_data['src']=self::layout_url($_data['src']);
        }

        $prefix=self::_attrs_to_str( $_data );
        
        return '<script'.$prefix.'></script>';
    }
    function meta( $params ){
        $name='';
        if( isset($params['name']) ){ $name='name="'.$params['name'].'" '; unset($params['name']); }
        $httpEquiv='';
        if( isset($params['httpEquiv']) ){ $httpEquiv='http-equiv="'.$params['httpEquiv'].'" '; unset($params['httpEquiv']); }
        $content='';
        if( isset($params['content']) ){ $content=$params['content']; unset($params['content']); }
        $prefix=self::_attrs_to_str( $params );
        return '<meta '.$name.$httpEquiv.'content="'.$content.'"'.$prefix.'>';
    }
    /**** Head tag utilities End ****/
    
    function include_http_metas( $return=false ){
        $contents='';
        $httpMetas=View::$layoutConfigs['http_metas'];
        if( is_array($httpMetas) && count($httpMetas)>0 ){
            foreach( $httpMetas as $http_meta=>$value ){
                if( empty($value) ) continue;
                $contents.='<meta http-equiv="'.$http_meta.'" content="'.$value.'">'."\n";
            }
        }
        $metas=View::$layoutConfigs['metas'];
        if( is_array($metas) && count($metas)>0 ){
            foreach( $metas as $meta=>$value ){
                if( empty($value) ) continue;
                if( in_array( $meta , array('title','sitename') ) ){ continue; }
                if( $meta=='language' ) $meta='content-'.$meta;
                $contents.='<meta name="'.$meta.'" content="'.$value.'">'."\n";
            }
        }
        $links=View::$layoutConfigs['links'];
        if( is_array($links) && count($links)>0 ){
            foreach( $links as $key=>$value ){
                if( empty($value) ) continue;
                
                if( is_array($value) ){
                    $_='';
                    foreach($value as $k=>$v){ $_.=$k.'="'.$v.'" '; }
                    $contents.='<link rel="'.$key.'" '.$_.'>'."\n";
                }else{
                    $contents.='<link rel="'.$key.'" href="'.$value.'" >'."\n";
                }
            }
        }
        
        if( $return ) return $content;
        echo $contents;
    }
    function include_title( $return=false ){
        $pageTitle = APP::$pageTitle;
        
        $contents='';
        $metas=View::$layoutConfigs;
        $sitename='';
        if( isset($metas['sitename']) && !empty($metas['sitename']) ){
            $sitename=$metas['sitename'];
        }
        $title='';
        if( isset($metas['title']) && !empty($metas['title']) ){
            $title=$metas['title'];
        }
        if( !empty($pageTitle) && empty($title) ){
            $title=$pageTitle;
        }
        $docTitle =$title;
        $docTitle.=( !empty($title) && !empty($sitename) )?' - ':'';
        $docTitle.=$sitename;
        
        $contents.='<title>'.$docTitle.'</title>'."\n";
        if( $return ) return $content;
        echo $contents;
    }
    function include_stylesheets( $return=false ){
        $contents='';
        $_css=View::$layoutConfigs['stylesheets'];
        foreach($_css as $url){
            $_link = array(
                'rel' => 'stylesheet',
            );
            if( is_array($url) ){
                $_link = array_merge( $_link, $url );
                $url = $url['href'];
            }
            
            if( substr($url,0,4)=='http' ){
                //網址的狀況
                $absolute_url=true;
                $_link['href']=$url;
                $contents.=self::link($_link, $absolute_url)."\n";
                continue;
            }

            if( substr($url,0,1) === '_' ){
                //表示這是絕對路徑，此路徑與layout無關，只要幫他補上webroot就可以了
                $absolute_url=true;
                $url = url($url); //補上WEBROOT
                $_link['href']=$url;
                $contents.=self::link($_link, $absolute_url)."\n";
                continue;
            }
            
            if( substr($url,0,1) === '/' ){
                //表示不預設前綴路徑/css/，由使用者決定layout內的路徑
                $_link['href']=substr($url,1); //修改為相對於layout的相對路徑
                $contents.=self::link($_link)."\n";
                continue;
            }
            //預設為相對路徑，base 為 layout 的 css 資料夾
            $_link['href']='/css/'.$url;
            $contents.=self::link($_link)."\n";
        }
        if( $return ) return $content;
        echo $contents;
    }
    function include_javascripts( $return=false ){
        $contents='';
        $_js=View::$layoutConfigs['javascripts'];
        foreach($_js as $url){
            if( substr($url,0,4)=='http' ){
                //網址的狀況
                $absolute_url=true;
                $contents.=self::script($url, $absolute_url)."\n";
                continue;
            }

            if( substr($url,0,1) === '_' ){
                //表示這是絕對路徑，此路徑與layout無關，只要幫他補上webroot就可以了
                $absolute_url=true;
                $url = url($url); //補上WEBROOT
                $contents.=self::script($url, $absolute_url)."\n";
                continue;
            }

            if( substr($url,0,1) === '/' ){
                //表示不預設前綴路徑/css/，由使用者決定layout內的路徑
                $contents.=self::script($url)."\n"; //修改為相對於layout的相對路徑
                continue;
            }
            //預設路徑為 layout 的 js資料夾
            $url='/js/'.$url;
            $contents.=self::script($url)."\n";
        }
        if( $return ) return $content;
        echo $contents;
    }
    function include_extra_headers( $return=false ){
        $contents='';
        $_extra=View::$layoutConfigs['extra'];
        $contents.=implode("\n", (array)$_extra);
        
        if( $return ) return $content;
        echo $contents;
    }
    function include_extra_footers( $return=false ){
        $contents='';
        $_extra=View::$layoutConfigs['footers'];
        $contents.=implode("\n", (array)$_extra);
        
        if( $return ) return $content;
        echo $contents;
    }
    function get_configs(){
        return View::$layoutConfigs;
    }
    function use_stylesheets(){
        $args = func_get_args();
        
        if( count($args) < 1 ){
            errmsg('必須傳入至少1個參數');
        }
        if( count($args) === 1 ){
            $args = (array)$args[0];
        }
        $configs = (array)View::$layoutConfigs['stylesheets'];
        $configs = array_merge( $configs, $args );
        
        View::$layoutConfigs['stylesheets'] = $configs;
    }
    function remove_stylesheets(){ // 移除一或多個引用的外部 CSS，執行完畢後會回傳共移除多少個項目
        $args = func_get_args();
        
        if( count($args) < 1 ){
            errmsg('必須傳入至少1個參數');
        }
        if( count($args) === 1 ){
            $args = (array)$args[0];
        }

        $configs = (array)View::$layoutConfigs['stylesheets'];
        $count = 0;
        foreach( $args as $arg ){
            $key = array_search( $arg, $configs );
            if( $key !== false ){ // 找到的話，移除該項目
                unset( $configs[$key] );
                $count+=1;
            }
        }
        
        View::$layoutConfigs['stylesheets'] = $configs;

        return $count;
    }
    function use_javascripts(){
        $args = func_get_args();
        
        if( count($args) < 1 ){
            errmsg('必須傳入至少1個參數');
        }
        if( count($args) === 1 ){
            $args = (array)$args[0];
        }
        $configs = (array)View::$layoutConfigs['javascripts'];
        $configs = array_merge( $configs, $args );
        
        View::$layoutConfigs['javascripts'] = $configs;
    }
    function remove_javascripts(){ // 移除一或多個引用的外部 javascript，執行完畢後會回傳共移除多少個項目
        $args = func_get_args();
        
        if( count($args) < 1 ){
            errmsg('必須傳入至少1個參數');
        }
        if( count($args) === 1 ){
            $args = (array)$args[0];
        }

        $configs = (array)View::$layoutConfigs['javascripts'];
        $count = 0;
        foreach( $args as $arg ){
            $key = array_search( $arg, $configs );
            if( $key !== false ){ // 找到的話，移除該項目
                unset( $configs[$key] );
                $count+=1;
            }
        }
        
        View::$layoutConfigs['javascripts'] = $configs;

        return $count;
    }
    function use_extra_header(){
        $args = func_get_args();
        
        $name = '';
        $content = '';
        switch( count($args) ){
            case 2:
                list( $name , $content ) = $args;
                break;
            case 1:
                list( $content ) = $args;
                break;
            default:
                errmsg('必須傳入至少 1~2 個參數');
        }
        
        $configs = (array)View::$layoutConfigs['extra'];
        if( empty($name) ){
            $configs[] = $content;
        }else{
            $configs[$name] = $content;
        }
        View::$layoutConfigs['extra'] = $configs;
    }
    function use_extra_footer(){
        $args = func_get_args();
        
        $name = '';
        $content = '';
        switch( count($args) ){
            case 2:
                list( $name , $content ) = $args;
                break;
            case 1:
                list( $content ) = $args;
                break;
            default:
                errmsg('必須傳入至少 1~2 個參數');
        }
        
        $configs = (array)View::$layoutConfigs['footers'];
        if( empty($name) ){
            $configs[] = $content;
        }else{
            $configs[$name] = $content;
        }
        View::$layoutConfigs['footers'] = $configs;
    }

    /**** Body tag utilities start ****/
    function a( $href , $name='' , $attrs=array() ){
        return self::anchor( $href , $name='' , $attrs=array() );
    }
    function anchor( $href , $name='' , $attrs=array() ){
        $href_abs=self::url($href);
        if( empty($name) ){ $name=$href_abs; }
        $prefix=self::_attrs_to_str( $attrs );
        return '<a href="'.$href_abs.'"'.$prefix.'>'.$name.'</a>';
    }
    function img( $src , $params=array() ){
        $_default=array();
        $_data = $params + $_default ;
        $prefix=self::_attr_to_str( $_data );
        
        $src_abs=self::image_url($src);
        
        return '<img src="'.$src_abs.'"'.$prefix.' />';
    }
    /**** Head tag utilities End ****/
    function js_url( $src ){ return self::layout_url(APP::$routing['prefix'], $src); }
    function css_url( $src ){ return self::layout_url(APP::$routing['prefix'], $src); }
    function layout_url( $href='' ){ return layout_url(APP::$routing['prefix'], $href); }
    function image_url( $src ){ return image_url($src); }
    function url( $href ){ return url($href); }
    protected function _attrs_to_str( $attrs ){
        $prefix='';
        foreach( $attrs as $key=>$value ){
            $prefix.=' '.$key.'="'.$value.'"';
        }
        return $prefix;
    }

}

?>