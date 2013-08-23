<?php
function watermark( $src_image , $watermark_image , $options=array() ){
    //$src_image 來源圖片路徑
    //$dst_image 生成圖片路徑 = 來源圖片路徑
    //$watermark_image 圖水印圖片路徑
    //$options['align'] 對齊位置
    //      default: bottomright
    //      attribute:
    //          random: random
    //          valign: top middle bottom
    //          align: left center right
    //              topleft topcenter topright
    //              middleleft middlecenter middleright
    //              bottomleft bottomcenter bottomright
    //$options['x'] X座標，預設10，單位px
    //$options['y'] Y座標，預設10，單位px
    //$options['quality'] 輸出圖片品質，預設 100
    //$options['output_type'] 輸出圖片格式，僅支援 jpg / png ，預設 jpg
    
    $dst_image = $src_image; //直接取代原圖
    
    $align = 'bottomright';
    if( isset($options['align']) && !empty($options['align']) ){
        if( in_array( $options['align'] , array('random','topleft','topcenter','topright','middleleft','middlecenter','middleright','bottomleft','bottomcenter','bottomright') ) ){
            $align = strtolower($options['align']);
        }
    }
    
    $x = 10; //預設值
    //遇到隨機時自動歸零
    if( $align === 'random' ){ $x=0; }
    //遇到置中時自動歸零
    if( strpos( $align , 'center' ) !== false ){ $x = 0; }
    //但有指定值時以指定的為準
    if( isset($options['x']) && !empty($options['x']) ){ $x = $options['x']; }
    
    $y = 10; //預設值
    //遇到隨機時自動歸零
    if( $align === 'random' ){ $y=0; }
    //遇到置中時自動歸零
    if( strpos( $align , 'middle' ) !== false ){ $y = 0; }
    //但有指定值時以指定的為準
    if( isset($options['y']) && !empty($options['y']) ){ $y = $options['y']; }
    //echo 'x: '.$x.', y: '.$y.'<br>';
    
    //設定圖像品質
    $quality = 80;
    if( isset($options['quality']) && !empty($options['quality']) ){
        $quality = $options['quality'];
    }
    //設定輸出格式
    $output_type = 'jpg';
    if( isset($options['output_type']) && !empty($options['output_type']) ){
        $output_type = $options['output_type'];
    }
    //設定半透明水準
    $alpha = 50;
    if( isset($options['alpha']) && !empty($options['alpha']) ){
        $alpha = $options['alpha'];
    }
    
    // 載入浮水印圖
    $thumb = imagecreatefromjpeg($src_image);
    $w_image = imagecreatefrompng($watermark_image);

    // 取出浮水印圖 寬 與 高
    $w_width = imagesx($w_image);
    $w_height = imagesy($w_image);
    // 取出輸出圖 寬 與 高
    $i_width = imagesx($thumb);
    $i_height = imagesy($thumb);
    
    switch( $align ){
        case 'random':     //随机位置   
            $xpos = rand(0, ($i_width - $w_width) );   
            $ypos = rand(0, ($i_height - $w_height) );   
            break;
        case 'topleft':     //上左   
            $xpos = $x;
            $ypos = $y;
            break;
        case 'topcenter':     //上中   
            $xpos = ($i_width - $w_width)/2 + $x;
            $ypos = $y;
            break;
        case 'topright':     //上右   
            $xpos = $i_width - $w_width - $x ;
            $ypos = $y;
            break;
        case 'middleleft':     //中左   
            $xpos = $x;
            $ypos = ($i_height - $w_height)/2 + $y ;
            break;
        case 'middlecenter':     //中中   
            $xpos = ($i_width - $w_width)/2 + $x;
            $ypos = ($i_height - $w_height)/2 + $y ;
            break;
        case 'middleright':     //中右   
            $xpos = $i_width - $w_width - $x ;
            $ypos = ($i_height - $w_height)/2 + $y ;
            break;
        case 'bottomleft':     //下左   
            $xpos = $x;
            $ypos = $i_height - $w_height - $y ;
            break;
        case 'bottomcenter':     //下中   
            $xpos = ($i_width - $w_width)/2 + $x;
            $ypos = $i_height - $w_height - $y ;
            break;
        case 'bottomright':
        default:
            // 計算 浮水印出現位置
            $xpos = $i_width - $w_width - $x ;
            $ypos = $i_height - $w_height - $y ;
            break;
    }
    

    //複製一塊浮水印覆蓋位置的原圖
    $cut = imagecreatetruecolor($w_width,$w_height);
    imagecopy($cut, $thumb, 0, 0, $xpos, $ypos, $w_width, $w_height);

    //結合浮水印
    imagecopy($thumb, $w_image, $xpos, $ypos, 0, 0, $w_width, $w_height);
    
    //imagecopy($cut,$this->water_im,0,0,0,0,$this->waterImg_info[0],$this->waterImg_info[1]);
    //用裁切的原圖再覆蓋，進行半透明處理
    $apply_alpha = floor(100-$alpha);   
    imagecopymerge($thumb, $cut, $xpos, $ypos, 0, 0, $w_width, $w_height, $apply_alpha );
        
    if( $output_type=='jpg' || $output_type=='jpeg' ){
        imagejpeg($thumb, $dst_image, $quality);
    }elseif( $output_type=='png' ){
    	$quality = floor(100 * 0.09);
    	imagepng($tbumb, $dst_image, $quality);
    }else{
    	die("Unsupported output filetype. Only support jpg/jpeg, png.");
    }


    imagedestroy($w_image);
    imagedestroy($thumb);
}
?>