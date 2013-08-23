<?
$IPaddress=$_SERVER['REMOTE_ADDR']; 
$two_letter_country_code=iptocountry($IPaddress);
  
include("IP_FILES/countries.php");
$three_letter_country_code=$countries[ $two_letter_country_code][0];
$country_name=$countries[$two_letter_country_code][1];

print "Two letters code: $two_letter_country_code<br>";
print "Three letters code: $three_letter_country_code<br>";
print "Country name: $country_name<br>";

function iptocountry($ip) {
    $numbers = preg_split( "/\./", $ip);
    include("ip_files/".$numbers[0].".php");
    $code=($numbers[0] * 16777216) + ($numbers[1] * 65536) + ($numbers[2] * 256) + ($numbers[3]);
    foreach($ranges as $key => $value){
        if($key<=$code){
            if($ranges[$key][0]>=$code){$two_letter_country_code=$ranges[$key][1];break;}
            }
    }
    if ($two_letter_country_code==""){$two_letter_country_code="unkown";}
    return $two_letter_country_code;
}
?>