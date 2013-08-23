<?php
class MRDB{
    var $_link=array();
    var $_profiles=array();
    var $_active_profile='main';
    function init( $profiles ){
        $this->_profiles=$profiles;
        $this->_active_profile='main';
    }
    function connect( $name='main' ){
        $profile=$this->_profiles[$name];
        //$engine=$profile['engine'];//預留參數，目前僅支援mysql
        $host=$profile['host'];
        $dbname=$profile['dbname'];
        $username=$profile['username'];
        $password=$profile['password'];
        $encoding=$profile['encoding'];
        //pr($profile);die;
        
        $this->_link[ $name ] = mysql_connect( $host, $username, $password );
        mysql_query('SET NAMES '.$encoding , $this->_link[ $name ] ); 
        mysql_select_db($dbname, $this->_link[ $name ]) or errmsg("Could Not Select Database.");
    }
    function query($sql){
        //for READ
        $time1=gettime();
        $result=$this->mysql_query_slave($sql);
        $time2=gettime();
        markquery( 'query' , $sql , $time1 , $time2 );
        return $result;
    }
    function exec($sql){
        return $this->execute($sql);
    }
    function execute($sql){
        //for WRITE
        $time1=gettime();
        $result=mysql_query($sql, $this->_link[ $this->_active_profile ]);
        $time2=gettime();
        markquery( 'exec' , $sql , $time1 , $time2 );
        return $result;
    }
    function numRows( $result ){
        return mysql_num_rows($result);
    }
    function fetchAll( $result ){
        $rows=array();
        while( $r=mysql_fetch_array($result) ){
            $rows[]=$r;
        }
        return $rows;
    }
    function fetchRow( $result ){
        $r=mysql_fetch_array($result);
        return $r;
    }
    function fetchOne( $result ){
        $r=mysql_fetch_array($result);
        return $r[0];
    }
    function mysql_query_slave($sql){
        //預備給Load Balance資料庫使用
        $res = mysql_query($sql, $this->_link[ $this->_active_profile ]);
        if( $error=$this->isError ){
            errmsg( $error );
        }
        return $res;
    }
    
    /* Data Process */
    
    function quote($value, $type = null, $quote = true){
        if( is_null($value) || $value === '' ){
            if ( ! $quote ) {
                return null;
            }
            return "''";
        }
        
        if( is_null($type) ){
            $omit=false;
            if( ! $omit && is_int($type) ){
                $type = 'integer';
                $omit = true;
            }
            if( ! $omit && is_float($type) ){
                // todo: default to decimal as float is quite unusual
                // $type = 'float';
                $type = 'decimal';
                $omit = true;
            }
            if( ! $omit && is_bool($type) ){
                $type = 'boolean';
                $omit = true;
            }
            if( ! $omit && is_array($type) ){
                $value = serialize($value);
                $type = 'text';
                $omit = true;
            }
            if( ! $omit && is_object($type) ){
                $type = 'text';
                $omit = true;
            }
            if( ! $omit ){
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
                    $type = 'timestamp';
                } elseif (preg_match('/^\d{2}:\d{2}$/', $value)) {
                    $type = 'time';
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $type = 'date';
                } else {
                    $type = 'text';
                }
            }
        }
        
        $call=ucwords($type);
        if ( ! method_exists($this, "_quote{$call}")) {
            return errmsg('MRDB::quote() : Type Not Defined: '.$type);
        }
        $value = $this->{"_quote{$type}"}($value, $quote, $escape_wildcards);

        return $value;
    }
    function escape($text){
        return mysql_real_escape_string($text, $this->_link[ $this->_active_profile ] );
    }
    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteInteger($value, $quote){
        return (int)$value;
    }

    // }}}
    // {{{ _quoteText()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that already contains any DBMS specific
     *       escaped character sequences.
     * @access protected
     */
    function _quoteText($value, $quote){
        if ( ! $quote ) {
            return $value;
        }

        $value = $this->escape($value);

        return "'".$value."'";
    }

    // }}}
    // {{{ _readFile()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _readFile($value){
        $close = false;
        if (preg_match('/^(\w+:\/\/)(.*)$/', $value, $match)) {
            $close = true;
            if ($match[1] == 'file://') {
                $value = $match[2];
            }
            $value = @fopen($value, 'r');
        }

        if (is_resource($value)) {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }

            $fp = $value;
            $value = '';
            while (!@feof($fp)) {
                $value.= @fread($fp, 8192);
            }
            if ($close) {
                @fclose($fp);
            }
        }

        return $value;
    }

    // }}}
    // {{{ _quoteLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteLOB($value, $quote){
        $value = $this->_readFile($value);

        return $this->_quoteText($value, $quote);
    }

    // }}}
    // {{{ _quoteCLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteCLOB($value, $quote){
        return $this->_quoteLOB($value, $quote);
    }

    // }}}
    // {{{ _quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value, $quote){
        return $this->_quoteLOB($value, $quote);
    }

    // }}}
    // {{{ _quoteBoolean()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteBoolean($value, $quote){
        return ($value ? 1 : 0);
    }

    // }}}
    // {{{ _quoteDate()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteDate($value, $quote)
    {
        if ($value === 'CURRENT_DATE') {
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            if (isset($db->function) && is_a($db->function, 'MDB2_Driver_Function_Common')) {
                return $db->function->now('date');
            }
            return 'CURRENT_DATE';
        }
        return $this->_quoteText($value, $quote);
    }

    // }}}
    // {{{ _quoteTimestamp()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteTimestamp($value, $quote){
        if ($value === 'CURRENT_TIMESTAMP') {
            return $this->now('timestamp');
        }
        return $this->_quoteText($value, $quote);
    }
    function now($type = 'timestamp'){
        switch ($type) {
        case 'time':
            return 'CURRENT_TIME';
        case 'date':
            return 'CURRENT_DATE';
        case 'timestamp':
        default:
            return 'CURRENT_TIMESTAMP';
        }
    }

    // }}}
    // {{{ _quoteTime()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *       compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteTime($value, $quote){
        if ($value === 'CURRENT_TIME') {
            return $this->now('time');
        }
        return $this->_quoteText($value, $quote);
    }

    // }}}
    // {{{ _quoteFloat()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteFloat($value, $quote){
        if (preg_match('/^(.*)e([-+])(\d+)$/i', $value, $matches)) {
            $decimal = $this->_quoteDecimal($matches[1], $quote);
            $sign = $matches[2];
            $exponent = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $value = $decimal.'E'.$sign.$exponent;
        } else {
            $value = $this->_quoteDecimal($value, $quote);
        }
        return $value;
    }

    // }}}
    // {{{ _quoteDecimal()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access protected
     */
    function _quoteDecimal($value, $quote)
    {
        $value = (string)$value;
        $value = preg_replace('/[^\d\.,\-+eE]/', '', $value);
        if (preg_match('/[^.0-9]/', $value)) {
            if (strpos($value, ',')) {
                // 1000,00
                if (!strpos($value, '.')) {
                    // convert the last "," to a "."
                    $value = strrev(str_replace(',', '.', strrev($value)));
                // 1.000,00
                } elseif (strpos($value, '.') && strpos($value, '.') < strpos($value, ',')) {
                    $value = str_replace('.', '', $value);
                    // convert the last "," to a "."
                    $value = strrev(str_replace(',', '.', strrev($value)));
                // 1,000.00
                } else {
                    $value = str_replace(',', '', $value);
                }
            }
        }
        return $value;
    }
    function isError(){
        return mysql_errno();
    }
}
?>