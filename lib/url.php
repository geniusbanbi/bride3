<?php
function redirect_message(){
    //檢查Redirect Message並取出
    $RedirectMSG='';
    
    $redirect_messages = &$_SESSION['Redirect'];
    $ME_alias=preg_replace('/index\.html$/','', APP::$ME );
    $RMSG='';
    if( isset($redirect_messages[ APP::$ME ]) ){ //取出
        $RMSG=$redirect_messages[ APP::$ME ];
        unset($redirect_messages[ APP::$ME ]);
    }
    if( APP::$ME!=$ME_alias && isset($redirect_messages[ $ME_alias ]) ){
        $RMSG=$redirect_messages[ $ME_alias ];
        unset($redirect_messages[ $ME_alias ]);
    }
    if( isset($RMSG['timeout']) && $RMSG['timeout'] < mktime() ){ //如果逾期就刪除
        unset($RMSG);
    }
    if( isset($RMSG) && is_array( $RMSG ) ){
        $RedirectMSG=$RMSG['message'];
    }
    return $RedirectMSG;
}
function redirect( $href , $message='' , $message_template='' ){
    $url=url($href);
    if( !empty($message) ){
        if( !empty($message_template) ){
            $template = 'notice_'.$message_template;
            $msg = RenderRedirectMSG( $message , $template );
            $_SESSION['Redirect'][$url]=array(
                'timeout' => strtotime('+1 min'),
                'message' => $msg,
            );
        }else{
            $_SESSION['Redirect'][$url]=array(
                'timeout' => strtotime('+1 min'),
                'message' => $message,
            );
        }
    }
    //pr(headers_list());die;
    if( count($_POST)>0 ){
        $delay=1;
        $waitimg=layout_url('admin', 'loading.gif');
        $redirectMiddlePage=<<<EOF
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Refresh" content='{$delay}; url={$url}'>
<meta name="HandheldFriendly" content="True">
<meta name="MobileOptimized" content="320">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

</head>
<body>
<div style="width:100%;height:55%;background:url({$waitimg}) center bottom no-repeat;"></div>
</body>
</html>
EOF;
        echo $redirectMiddlePage;
        
        //呼叫系統的全程式終止程序
        stop_progress();
    }else{
    	$headers=headers_list();
    	$cookies=array();
    	foreach( $headers as $h ){
            if( preg_match('/^Set\-Cookie/i', $h) ){
                $cookies[]=$h;
            }
        }
    	
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: '. gmdate ('D, d M Y H:i:s') . 'GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false );
		header('Pragma: no-cache');
		header('Location: ' . $url );
		foreach( $cookies as $cookie ){
            header( $cookie );
        }
        
        //呼叫系統的全程式終止程序
        stop_progress();
    }
}

function anchor( $name, $href, $options=array() ){
    $url=url($href);
    if( is_string($options) ){
        $options=array($options);
    }
    $attrs=array();
    foreach( $options as $key=>$value ){
        if( is_int($key) ){
            $attrs[]=$value;
            continue;
        }
        $attrs[]=$key.'="'.$value.'"';
    }
    $html='';
    $html.='<a href="'.$url.'" '.implode(' ', $attrs).'>'.$name.'</a>';
    return $html;
}
function get_parents_app( $app ){
    if( isset(RoutingConfigs::$maps[$app]['path']) ){
        
        $parents = pos(RoutingConfigs::$maps[ $app ]['path']);
        
        return $parents;
    }
    
    //沒有 path 記錄，表示這不是此 $app 的 parents，是查閱別人 app 的 parents，因此全部回傳
    return RoutingConfigs::$maps[$app];
}
function get_app_path( $app, $options=array() ){
    $set_parents = '';
    if( is_string($options) ){
        $set_parents = $options;
    }else{
        if( isset($options['parents']) ){
            $set_parents = $options['parents'];
            unset($options['parents']);
        }
        $set_path = '';
        if( isset($options['path']) ){
            $set_path = $options['path'];
            unset($options['path']);
        }
    }
    
    if( isset( RoutingConfigs::$maps[ $app ]['path'] ) ){
        $my_parents = get_parents_app($app);
        if( empty($set_parents) || $set_parents === $my_parents ){ //沒有指定 parents，或指定的parents就是自己的parents
        
            $app_path = key(RoutingConfigs::$maps[ $app ]['path']);

            if( ! is_string($app_path) ){ errmsg('找不到路徑，錯誤的回傳值 - Error 1'); }
            
            return $app_path;
        }
        
        //有指定 parents 的狀況
        $app_path = array_search( $set_parents, RoutingConfigs::$maps[ $app ]);
        
        if( ! is_string($app_path) ){ errmsg('找不到路徑，錯誤的回傳值 - Error 2'); }
        
        return $app_path;
    }
    if( isset( RoutingConfigs::$maps[ $app ] ) ){
        $app_path = key(RoutingConfigs::$maps[ $app ]);
        
        return $app_path;
    }
    return '';
}
function get_base_root( $with_locale = true ){
    $base = '';
    //判斷 prefix
    if( APP::$prefix !== 'main' ){
        $base = APP::$prefixFull.'/';
    }
    if( APP::$prefix === 'main' ){
        $base = '/';
    }
    //判斷 locale
    //如果語系支援有啟用，才判斷語系
    //未啟用時 APP::$routing['locale'] 將會空白
    if( APP::$routing['locale'] !== '' && $with_locale ){
        if( APP::$routing['locale'] !== RoutingConfigs::$locale['default'] ){
            $base .= APP::$routing['locale'].'/';
        }
    }
    return $base;
}
function url( $href ){
    //如果傳入的參數是字串，則以字串URL方式處理
    if( is_string($href) ){
        $status = 0; //記錄曾啟用過哪些特殊功能
        
        // "/" 時，表示為所在 prefix + locale 下的絕對路徑
        if( substr($href, 0, 1)==='/' ){
            $href = substr($href, 1);
            $base = get_base_root();
            $href = $base.$href;
            $status += 8;
        }
        // ".." 表示取得app 的母親app，若無則指 prefix+locale 的根目錄，即 main app
        if( $status===0 && substr($href, 0, 2)==='..' ){
            $href = preg_replace('/[\.]{2,}/', '..', $href);
            $base = '';
            $base_prefix = '';
            $href = substr($href, 2);
            $base_prefix = get_base_root();

            $app = APP::$app;
            $parents_app=get_parents_app($app);
            if( empty($parents_app) ){
                $base = $base_prefix;
            }
            while( ! empty($parents_app) ){
                $base = $base_prefix.get_app_path($parents_app).'/';
                
                if( substr($href, 0, 3)==='/..' ){
                    $parents_app=get_parents_app($parents_app);
                    $href=substr($href, 3);
                }else{
                    $parents_app='';
                }
            }
            //如果parents已經找完，還有/..的話，強制成為根目錄
            if( substr($href, 0, 3)==='/..' ){
                $base = $base_prefix;
                $href = str_replace('/..', '', $href);
            }
            
            //如果 $base & $href 同時非空白，此時會多一個 "/" ，因此需要移除其中一個
            if( ! empty($base) && ! empty($href) ){
                $base = substr($base, 0, -1);
            }
            $href = $base.$href;
            $status += 1;
        }
        // "." 永遠表示 prefix + app 的根目錄
        if( $status===0 && substr($href, 0, 1)==='.' ){
            $href = substr($href, 1);
            $base = get_base_root();
            if( APP::$app != 'main' ){
                $app_path = get_app_path( APP::$app );
                $base .= $app_path.'/';
            }
            //如果 $base & $href 同時非空白，此時會多一個 "/" ，因此需要移除其中一個
            if( ! empty($base) && ! empty($href) ){
                $base = substr($base, 0, -1);
            }
            $href = $base.$href;
            $status += 2;
        }
        // "_" 表示為系統內部的絕對路徑（一定要放在路徑開頭），則只需要補上WEBROOT即可
        // status === 0 表示尚未被處理過
        if( $status === 0 && substr($href, 0, 1)==='_' ){
            $href = substr($href, 1);
            $status += 4;
        }
        // "*" 為切換語系專用符號，後方僅能接語系代號
        // status === 0 表示尚未被處理過
        if( $status === 0 && substr($href, 0, 1)==='*' && APP::$routing['locale'] !== ''/* 確認語系支援啟用 */ ){
            // 若前綴符號僅 "*" ，則表示切換語系後回到根目錄 
            // 若前綴符號為 "*_" ，則表示切換語系後，回到原頁面
            $new_locale = substr($href, 1);
            $href=''; //因為不是正統路徑，因此清除內容
            
            $mode = 'root';
            if( substr($new_locale, 0, 1) === '_' ){
                $mode = 'current';
                $new_locale = substr($new_locale, 1);
            }
            $base = get_base_root( false ); //false 表不要 locale 路徑
            
            if( $new_locale !== RoutingConfigs::$locale['default'] ){
                $base .= $new_locale.'/';
            }
            if( $mode === 'current' ){
                $href = APP::$routing['p'];
                if( APP::$routing['locale'] !== RoutingConfigs::$locale['default'] ){
                    $href = substr($href, strlen(APP::$routing['locale'])+1 );
                }
            }
            
            $href = $base.$href;
            $status += 16;
        }
        //如果不曾啟用過以上特殊功能，表示為相對路徑，自動補上 prefix 和 app
        if( $status === 0 ){
            $base = get_base_root();
            if( APP::$app != 'main' ){
                $app_path = get_app_path( APP::$app );
                $base .= $app_path.'/';
            }
            $href = $base.$href;
        }
        
        return txturl($href);
    }
    if( ! is_array($href) ){
        return false;
    }
    
    return false;
}
function layout_url(){
    $args = func_get_args();
    
    //layout預設使用目前所在的prefix
    $layout = APP::$prefix;
    
    //判讀使用的layout名稱
    $href = array_shift($args);
    if( array_key_exists( $href, RoutingConfigs::$apps) ){
        $layout = $href;
        $href = array_shift($args);
    }

    //如果傳入的參數是字串，則以字串URL方式處理
    if( is_string($href) ){
        if( substr($href, 0, 1) !== '/' ){
            $href = '/'.$href;
        }
        return txturl('/layout_'.$layout.$href);
    }
    if( ! is_array($href) ){
        return false;
    }
    
    return false;
}
function repos_url( $href ){
    //如果傳入的參數是字串，則以字串URL方式處理
    if( is_string($href) ){
        if( substr($href, 0, 1) !== '/' ){
            $href = '/'.$href;
        }
        return txturl('/cabinets'.$href);
    }
    if( ! is_array($href) ){
        return false;
    }
    
    return false;
}
function app_url( $app , $parents='' ){
    //傳入app名稱，回傳該app的根路徑
    if( ! array_key_exists( $app, RoutingConfigs::$maps ) ){
        errmsg('指定的 app 尚未設定');
    }
    
    if( is_string(RoutingConfigs::$maps[ $app ]) ){ //單純的單層路徑狀況
        $app_path = get_app_path($app);
        return '/'.$app_path.'/';
    }
    if( ! empty($parents) ){ //指定parents的狀況
        $app_path = get_app_path($app, $parents);
        return '/'.$app_path.'/';
    }
    
    $app_path = get_app_path($app);
    return '/'.$app_path.'/';
}
function repos_path( $href ){
    //如果傳入的參數是字串，則以字串URL方式處理
    if( is_string($href) ){
        if( substr($href, 0, 1) === '/' ){
            $href = substr($href, 1);
        }
        //return './cabinets'.$href;
        return DIRCABINET.$href;
    }
    
    return false;
}
function cache_path( $href ){
    //如果傳入的參數是字串，則以字串URL方式處理
    if( is_string($href) ){
        if( substr($href, 0, 1) === '/' ){
            $href = substr($href, 1);
        }
        //return './cabinets'.$href;
        return DIRCACHE.$href;
    }
    
    return false;
}
function txturl( $href ){
    $href=trim($href);
    $href_abs=$href;
    //for empty path: imply ME
    if( empty($href) ){ return ''; }
    //for 絕對路徑
    if( $href=='/' ){ return WEBROOT; }
    //開頭有http(不分大小寫)時，表示絕對路徑
    if( preg_match( '/^http/i', $href) ){ return $href; }
    
    
    if( substr($href, 0, 1) === '/' ){
        return WEBROOT.substr($href, 1);
    }
    return WEBROOT.$href;
}
?>