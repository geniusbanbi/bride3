<?php
/* Example:
$ip = "114.42.189.191";
$two_letter_country_code=iptocountry( $ip );
echo "The IP ".$ip." comes from Country ";
echo '"'.$two_letter_country_code.'".';
echo '<img src="flags/'.$two_letter_country_code.'.gif" />';
*/

/* Example:
$ip = "60.245.64.191";
$two_letter_country_code=iptocountry( $ip );
print_r( IP2Country::parse($ip) );
*/


class IP2Country{
    static $ip_files = "ip2country/ip_files/";
    static $flag_files = "ip2country/flags/";
    static $flag_pngs = "ip2country/flags_png/";
    static $countries=array();

    function parse( $ip='' ){
        $iplib_dir=dirname(__FILE__)."/".self::$ip_files;
        
        $numbers = preg_split( "/\./", $ip);
        
        $iplib_file = $iplib_dir.$numbers[0].".php";
        if( file_exists($iplib_file) ){
            include( $iplib_file );

            $code=($numbers[0] * 16777216) + ($numbers[1] * 65536) + ($numbers[2] * 256) + ($numbers[3]);    
            foreach($ranges as $key => $value){
                if($key<=$code){
                    if($ranges[$key][0]>=$code){$country=$ranges[$key][1];break;}
                }
            }
            
        }else{
            $country = "";
        }
        
        if ( $country=="" ){$country="unknown";}
        
        $two_letter_country_code = $country;
        
        $countries = self::getCountries();
        
        $country_name_en            = $countries[ $two_letter_country_code ][1];
        $three_letter_country_code  = $countries[ $two_letter_country_code ][0];
        $country_name_tw            = $countries[ $two_letter_country_code ][2];
        $country_name_iso           = $countries[ $two_letter_country_code ][3];
        
        $flag_path = self::$flag_pngs . $two_letter_country_code.'.png';
        if( ! file_exists( dirname(__FILE__).'/'.$flag_path ) ){
            $flag_path = self::$flag_files . $two_letter_country_code.'.gif';
        }

        if( $two_letter_country_code ==='unknown' ){
            $country_name_en = $two_letter_country_code;
            $country_name_tw = $two_letter_country_code;
            $country_name_iso = $two_letter_country_code;
            $three_letter_country_code = $two_letter_country_code;
            $flag_path = self::$flag_files . 'noflag.gif';
        }
        
        $data = array(
            'name' => $country_name_iso,
            'name_tw' => $country_name_tw,
            'name_en' => $country_name_en,
            'code_2c' => $two_letter_country_code,
            'code_3c' => $three_letter_country_code,
            'flag' => $flag_path,
        );
        
        return $data;
        
    }
    function getCountries(){
        if( count(self::$countries)<1 ){
            $iplib_dir = self::$ip_files;
            include( $iplib_dir."countries_plus.php" );
            self::$countries = $countries;
        }
        return self::$countries;
    }
}
/*
function iptocountry($ip) {    
    $numbers = preg_split( "/\./", $ip);    
    include("ip_files/".$numbers[0].".php");
    $code=($numbers[0] * 16777216) + ($numbers[1] * 65536) + ($numbers[2] * 256) + ($numbers[3]);    
    foreach($ranges as $key => $value){
        if($key<=$code){
            if($ranges[$key][0]>=$code){$country=$ranges[$key][1];break;}
            }
    }
    if ($country==""){$country="unkown";}
    return $country;
}
*/
?>
