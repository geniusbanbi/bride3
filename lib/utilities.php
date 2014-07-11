<?php
function stop_progress(){
    if( DEBUG==0 ){
        die;
    }
    if( APP::$systemConfigs['Debug']==0 ){
        //提供APP於執行期決定是否關閉訊息
        die;
    }
    echo '<b>Cookies:</b>';
    pr($_COOKIE);
    echo '<b>PageBeforeLogin:</b>';
    pr($_SESSION['PageBeforeLogin']);

    $memory_peak = memory_get_peak_usage();
    $memory_peak_readable = sprintf('%.2f', ($memory_peak / (1024*1024)) ).' MB';
    echo "memory_get_peak_usage: ".$memory_peak_readable." (".$memory_peak.")<br>";
    /*$memory_usage = memory_get_peak_usage();
    $memory_usage_readable = sprintf('%.2f', ($memory_usage / (1024*1024)) ).' MB';
    echo "memory_get_usage: ".$memory_usage_readable." (".$memory_usage.")<br>";*/
    
    echo '<b>Routing:</b>';
    pr(APP::$routing);
    
    echo '<b>Layout Settings:</b>';
    ob_start();
    print_r(View::$layoutConfigs);
    $html = htmlspecialchars( ob_get_contents() );
    ob_end_clean();
    echo '<pre>'.str_replace('  ', '&nbsp; ', $html).'</pre>';
    
    pr(RoutingConfigs::$parents);
    //pr($pageConfig);
    markquery_report();
    marktime_report();

    'APP::$loadedFiles<br>';
    pr(APP::$loadedFiles);
    die;
}
function stop(){
    stop_progress();
}
function pr($var){
    if( APP::$systemConfigs['Production']==1 ) return ;
    
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}
function vd($var){
    if( APP::$systemConfigs['Production']==1 ) return ;
    
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}
function ve($var){
    if( APP::$systemConfigs['Production']==1 ) return ;
    
    echo '<pre>';
    var_export($var);
    echo '</pre>';
}
function mkdirs($dir, $mode = 0777){
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE; 
    if (!mkdirs(dirname($dir), $mode)) return FALSE;
    return @mkdir($dir, $mode);
}
function rmdirs($dir){
	$handle = @opendir($dir);
	while (false!==($item = @readdir($handle))){
		if($item != '.' && $item != '..'){
			if(is_dir($dir.'/'.$item)) {
			    rmdirs($dir.'/'.$item);
            }else{
                @unlink($dir.'/'.$item);
            }
        }
    }
    @closedir($handle);
    if(@rmdir($dir)){
        $success = true;
    }
    return $success;
}
function str_match( $substr , $string ){
    if( strpos( $string , $substr )===false ){
        return false;
    }
    return true;
}
function uc2ul( $str ){
    return ucwords2underline( $str );
}
function ul2uc( $str ){
    return underline2ucwords( $str );
}
function ucwords2underline( $str ){
    $str=trim($str);
    $str=preg_replace('/([A-Z]{1})/', '_$1', $str);
    $str=preg_replace('/^\_/', '', $str);
    $str=strtolower($str);
    return $str;
}
function underline2ucwords( $str ){
    $str=trim($str);
    $str=str_replace(' ', '', $str);
    $strs=explode('_', $str);
    $res='';
    foreach( $strs as $s ){
        $res.=ucfirst($s);
    }
    return $res;
}
/**
 * Returns a human readable filesize
 *
 * @author      wesman20 (php.net)
 * @author      Jonas John
 * @version     0.3
 * @link        http://www.jonasjohn.de/snippets/php/readable-filesize.htm
 */
function readableFilesize($size) {
 
    // Adapted from: http://www.php.net/manual/en/function.filesize.php
 
    $mod = 1024;
 
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
 
    return round($size, 2) . ' ' . $units[$i];
}
function errmsg( $errmsg='' ){
    if( APP::$systemConfigs['Production']==1 ){
        View::render('error', 500);
    }
    
    $backtrace=debug_backtrace();
    //pr($backtrace);
    $loc=$backtrace[0];
    $err=$backtrace[1];
    echo '<META CONTENT="text/html; charset=utf-8" HTTP-EQUIV="Content-Type">';
    $msg ='<p style="font-size:14px;color:black;font-weight:normal;"><b>'.$loc['file'].' Line '.$loc['line'].'</b></p>';
    $msg.='<p style="font-size:12px;color:red;font-weight:normal;"><b>'.$err['function'].'() Error';
    if( !empty($err['class']) ){
        $msg.='<p style="font-size:12px;color:red;font-weight:normal;"><b>'.$err['class'].'::'.$err['function'].'() Error';
    }
    $msg.='<p style="font-size:12px;color:black;font-weight:normal;"><b>Error Message:</b> '.$errmsg.'</p>';
    //pr($backtrace);
    $msg.=debugBacktrace();
    
    echo $msg;
    stop_progress();
}
function debugBacktrace(){
    $backtrace=debug_backtrace();
    array_shift($backtrace);
    array_shift($backtrace);
    
    $msg='';
    $msg.='<table style="font-size:13px;">';
    $msg.='<tr style="background:#ccf;">';
    $msg.='<th>Position</th>';
    $msg.='<th>Function</th>';
    $msg.='</tr>';
    foreach( $backtrace as $b ){
        $msg.='<tr>';
        if( isset($b['file']) ){
            $msg.='<td style="font-weight:bold;">'.str_replace(DIRROOT, '', $b['file']).' Line: '.$b['line'].'</td>';
        }else{
            $msg.='<td></td>';
        }
        $msg.='<td>';
        $msg.=$b['class'].$b['type'].'<b>'.$b['function'].'</b>';
        $args=array();
        foreach( $b['args'] as $arg ){
            if( is_string($arg) ){ $args[]="'".$arg."'"; continue; }
            if( is_array($arg) ){ $args[]="Array[".count($arg)."]"; continue; }
            if( is_object($arg) ){ $args[]="Object"; continue; }
            if( is_numeric($arg) ){ $args[]=$arg; continue; }
        }
        $msg.='( '.implode(', ', $args).' )';
        $msg.='</td>';
        $msg.='</tr>';
    }
    $msg.="</table>";
    
    return $msg;
}

function errorHandler($errno, $errstr, $errfile, $errline){
    if ( ! (error_reporting() & $errno) ){
        //pr(debug_backtrace());
        return;
    }
    if( APP::$systemConfigs['Production']==1 ){
        View::render('error', 500);
    }
    
    switch ($errno) {
    case E_USER_ERROR:
        echo "<p><b>ERROR</b> [$errno] $errstr</p>\n";
        echo "  Fatal error on line $errline in file $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Aborting...<br />\n";
        exit(1);
        break;

    case E_USER_WARNING:
        echo "<p><b>WARNING</b> [$errno] $errstr</p>\n";
        break;

    case E_USER_NOTICE:
        echo "<p><b>NOTICE</b> [$errno] $errstr</p>\n";
        break;

    default:
        echo "<p><b>Unknown Error Type</b>: [$errno] $errstr</p>\n";
        break;
    }
    echo "<p><b>".$errfile.", on line ".$errline."</b></p>";
    echo debugBacktrace();
    /* Don't execute PHP internal error handler */
    return true;
}

/*****  Extended Functions  *****/

function arrayMerge($a, $b){
    /**
    * Merges two arrays
    *
    * Merges two array like the PHP function array_merge but recursively.
    * The main difference is that existing keys will not be renumbered
    * if they are integers.
    */
    foreach ($b as $k => $v) {
        if (is_array($v)) {
            if (isset($a[$k]) && !is_array($a[$k])) {
                $a[$k] = $v;
            } else {
                if (!isset($a[$k])) {
                    $a[$k] = array();
                }
                $a[$k] = arrayMerge($a[$k], $v);
            }
        } else {
            $a[$k] = $v;
        }
    }
    return $a;
} // end func arrayMerge
function recursiveFilter($filter, $value){
    /**
     * Recursively apply a filter function
     */
    if (is_array($value)) {
        $cleanValues = array();
        foreach ($value as $k => $v) {
            $cleanValues[$k] = recursiveFilter($filter, $v);
        }
        return $cleanValues;
    } else {
        return $filter($value);
    }
} // end func _recursiveFilter


function slug($str, $replacement='-', $length=50) {
    if( mb_strlen($str) > $length ){
        $str=mb_substr($str, 0, $length);
    }
    //$str=$this->Hanyu->slug($str, $replacement);
    $str=Inflector::slug($str, $replacement);
    
    return $str;
}

class Inflector{

/**
 * Returns a string with all spaces converted to underscores (by default), accented
 * characters converted to non-accented characters, and non word characters removed.
 *
 * @param string $string
 * @param string $replacement
 * @return string
 * @access public
 * @static
 * @link http://book.cakephp.org/view/572/Class-methods
 */
	function slug($string, $replacement = '-', $forceSpace = false) {
        $a1="\x{2e80}-\x{33ff}";//中日韓符號區
        $a2="\x{3400}-\x{4dff}";//中日韓認同表意文字擴充A區
        $a3="\x{4e00}-\x{9fff}";//中日韓認同表意文字區
        $a4="\x{fb00}-\x{fffd}";//中日韓相容表意文字區，總計收容302個中日韓漢字
        $a5="\x{f900}-\x{faff}";//文字表現形式區，收容組合拉丁文字、希伯來文、阿拉伯文、中日韓直式標點、小符號、半形符號、全形符號等
        
        $remove = '';
        if( $forceSpace ){
            $remove = ' ';
        }
		$map = array(
			'/à|á|å|â/' => 'a',
			'/è|é|ê|ẽ|ë/' => 'e',
			'/ì|í|î/' => 'i',
			'/ò|ó|ô|ø/' => 'o',
			'/ù|ú|ů|û/' => 'u',
			'/ç/' => 'c',
			'/ñ/' => 'n',
			'/ä|æ/' => 'ae',
			'/ö/' => 'oe',
			'/ü/' => 'ue',
			'/Ä/' => 'Ae',
			'/Ü/' => 'Ue',
			'/Ö/' => 'Oe',
			'/ß/' => 'ss',
            '/【|】|～|「|」|『|』|《|》|（|）|〈|〉|？|～|！|＠|＃|＄|％|︿|＆|＊|＿|＋|“|”|〝|〞|‵|′｜/' => $remove,
            '/〤|○|⊙|◎|●|☆|★|□|■|▼|▲|▽|△|◇|◆|♀|♂|﹡|＊|※|§|＠|⊕|㊣|\*/' => $remove,
            '/↑|↓|←|→|↖|↗|↙|↘|\?/' => '',
            '/,|，|：|；|、|。|　|●|─|…|＜|＞|\_/' => $replacement,
			//'/[^\w\s]/' => $replacement, // 這行會把中文濾掉
			'/\\s+/' => $replacement,
			'/^[-]+|[-]+$/' => $remove,
			// 變更 $replacement 時，請執行以下程式碼，將產生的結果改為最後的索引
			// String::insert('/^[:replacement]+|[:replacement]+$/', array('replacement' => preg_quote($replacement, '/')))
			// ===>   /^[-]+|[-]+$/
		);
		return preg_replace(array_keys($map), array_values($map), $string);
	}
}

class String{
/**
 * Replaces variable placeholders inside a $str with any given $data. Each key in the $data array corresponds to a variable
 * placeholder name in $str. Example:
 *
 * Sample: String::insert('My name is :name and I am :age years old.', array('name' => 'Bob', '65'));
 * Returns: My name is Bob and I am 65 years old.
 *
 * Available $options are:
 * 	before: The character or string in front of the name of the variable placeholder (Defaults to ':')
 * 	after: The character or string after the name of the variable placeholder (Defaults to null)
 * 	escape: The character or string used to escape the before character / string (Defaults to '\')
 * 	format: A regex to use for matching variable placeholders. Default is: '/(?<!\\)\:%s/' (Overwrites before, after, breaks escape / clean)
 * 	clean: A boolean or array with instructions for String::cleanInsert
 *
 * @param string $str A string containing variable placeholders
 * @param string $data A key => val array where each key stands for a placeholder variable name to be replaced with val
 * @param string $options An array of options, see description above
 * @return string
 * @access public
 * @static
 */
	function insert($str, $data, $options = array()) {
		$defaults = array(
			'before' => ':', 'after' => null, 'escape' => '\\', 'format' => null, 'clean' => false
		);
		$options += $defaults;
		$format = $options['format'];

		if (!isset($format)) {
			$format = sprintf(
				'/(?<!%s)%s%%s%s/',
				preg_quote($options['escape'], '/'),
				str_replace('%', '%%', preg_quote($options['before'], '/')),
				str_replace('%', '%%', preg_quote($options['after'], '/'))
			);
		}
		if (!is_array($data)) {
			$data = array($data);
		}

		if (array_keys($data) === array_keys(array_values($data))) {
			$offset = 0;
			while (($pos = strpos($str, '?', $offset)) !== false) {
				$val = array_shift($data);
				$offset = $pos + strlen($val);
				$str = substr_replace($str, $val, $pos, 1);
			}
		} else {
			asort($data);

			$hashKeys = array_map('md5', array_keys($data));
			$tempData = array_combine(array_keys($data), array_values($hashKeys));
			foreach ($tempData as $key => $hashVal) {
				$key = sprintf($format, preg_quote($key, '/'));
				$str = preg_replace($key, $hashVal, $str);
			}
			$dataReplacements = array_combine($hashKeys, array_values($data));
			foreach ($dataReplacements as $tmpHash => $data) {
				$str = str_replace($tmpHash, $data, $str);
			}
		}

		if (!isset($options['format']) && isset($options['before'])) {
			$str = str_replace($options['escape'].$options['before'], $options['before'], $str);
		}
		if (!$options['clean']) {
			return $str;
		}
		return String::cleanInsert($str, $options);
	}
/**
 * Cleans up a Set::insert formated string with given $options depending on the 'clean' key in $options. The default method used is
 * text but html is also available. The goal of this function is to replace all whitespace and uneeded markup around placeholders
 * that did not get replaced by Set::insert.
 *
 * @param string $str
 * @param string $options
 * @return string
 * @access public
 * @static
 */
	function cleanInsert($str, $options) {
		$clean = $options['clean'];
		if (!$clean) {
			return $str;
		}
		if ($clean === true) {
			$clean = array('method' => 'text');
		}
		if (!is_array($clean)) {
			$clean = array('method' => $options['clean']);
		}
		switch ($clean['method']) {
			case 'html':
				$clean = array_merge(array(
					'word' => '[\w,.]+',
					'andText' => true,
					'replacement' => '',
				), $clean);
				$kleenex = sprintf(
					'/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);
				if ($clean['andText']) {
					$options['clean'] = array('method' => 'text');
					$str = String::cleanInsert($str, $options);
				}
				break;
			case 'text':
				$clean = array_merge(array(
					'word' => '[\w,.]+',
					'gap' => '[\s]*(?:(?:and|or)[\s]*)?',
					'replacement' => '',
				), $clean);

				$kleenex = sprintf(
					'/(%s%s%s%s|%s%s%s%s)/',
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/'),
					$clean['gap'],
					$clean['gap'],
					preg_quote($options['before'], '/'),
					$clean['word'],
					preg_quote($options['after'], '/')
				);
				$str = preg_replace($kleenex, $clean['replacement'], $str);
				break;
		}
		return $str;
	}
}

?>