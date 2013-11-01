<?php
include( $my_base.'/config/routing.php');
class Routing{
    function parse( $p ){
        $sitekey=self::getSiteKey();
        
        $localeDefault='';
        if( property_exists('RoutingConfigs', 'locale') ){
            $localeDefault=RoutingConfigs::$locale['default'];
        }
        //決定預設prefix
        $prefix='main';
        $defaultPrefix='main';
        $prefixFull='';
        $prefixMap = RoutingConfigs::$prefixs;
        if( array_key_exists( '__default__' , $prefixMap ) ){
            $prefix=$prefixMap['__default__']['name'];
            $defaultPrefix=$prefixMap['__default__']['name'];
        }

        if( empty($p) ){
            $handler = 'main';
            if( $prefix !== 'main' ){
                $handler = $prefix.'#'.$handler;
            }
            return array(
                'locale'=>$localeDefault,
                'site'=>$sitekey,
                'prefix'=>$prefix,
                'prefixFull'=>'',
                'app'=>'main',
                'params'=>array('index'),
                'parents'=>array(),
                'doctype'=>'html',
                'handler'=>$handler,
            );
        }
        
        //過濾不必要的空白
        $p=trim($p);
        //去除GET string（路徑中"?"以後的字串）
        if( mb_strpos($p, '?')!==false ){
            //用拆解的方式，只取以"?"區隔的第一個元素
            $_ = explode('?', $p);
            $p=$_[0];
        }
        //若結尾是 "/" ，暗示使用預設 "index.html"，自動補上
        if( substr($p, -1)==='/' ){
            $p.="index.html";
        }
        //取得副檔名
        $ext = strtolower( substr( strrchr($p, ".") ,1 ) );
        //if( empty($ext) ) $ext='html';
        $p = preg_replace( "/\.".$ext."$/", '', $p ); //移除副檔名
        
        //拆解路徑
        $nodes = explode('/', $p);
        
        //判別第一個節點，取得所屬的prefix
        $current=pos($nodes);
        if( ! empty($current) && array_key_exists( $current , $prefixMap ) ){
            $prefix=$prefixMap[ $current ]['name'];
            array_shift($nodes);
            //記錄路徑前綴的全名
            $prefixFull = $current;
            
        }
        
        //判別語系
        $locale=$localeDefault;
        //如果有設定語系支援，才需要判斷語系，否則就不需理會，$locale留空即可
        if( property_exists('RoutingConfigs', 'locale') ){
            $localeSupport=RoutingConfigs::$locale['support'];
            
            $current=pos($nodes);
            if( ! empty($current) && in_array($current, $localeSupport) ){
                array_shift($nodes);
                $locale=$current;
            }
        }
        
        //排除prefix和語系之後，只判斷第一層級，如有註冊，就指定為app
        //其他自動保留為參數
        $arg_1 = pos($nodes);
        //若prefix未在apps中設定，則視為找不到
        if( ! isset(RoutingConfigs::$apps[ $prefix ]) ){
            return array('error'=>'404');
        }
        
        //建立路徑對應表
        $routingTable = RoutingConfigs::$apps[ $prefix ];
        foreach( $routingTable as $path=>$config ){
            if( ! isset($config['parents']) ){
                RoutingConfigs::$maps[ $config['name'] ] = $path;
            }else{
                RoutingConfigs::$maps[ $config['name'] ][ $config['parents'] ] = $path;
            }
            RoutingConfigs::$r_maps[ $path ] = $config['name'];
            //設定各app的母親app
            if( ! isset(RoutingConfigs::$parents[ $config['name'] ]) ){
                RoutingConfigs::$parents[ $config['name'] ]='';
            }
            if( isset($config['parents']) ){
                $parents = RoutingConfigs::$parents[ $config['name'] ];
                if( is_string($parents) && ! empty($parents) ){
                    RoutingConfigs::$parents[ $config['name'] ] = array(
                        RoutingConfigs::$maps[ $config['name'] ][ $parents ] => $parents,
                        $path => $config['parents'],
                    );
                    continue;
                }
                if( is_array($parents) ){
                    RoutingConfigs::$parents[ $config['name'] ][ $config['parents'] ] = $path;
                    continue;
                }
                RoutingConfigs::$parents[ $config['name'] ] = $config['parents'];
            }
        }
        unset($parents);
        //print_r(RoutingConfigs::$maps);
        //print_r(RoutingConfigs::$r_maps);
        //print_r(RoutingConfigs::$parents);
        //die;
        
        //排除prefix在實際路徑上的資料
        $p_app = $p;
        if( $prefix !== $defaultPrefix ){
            $p_app = substr($p_app, strlen($prefixFull)+1 );
        }
        
        //排除locale在實際路徑上的資料
        if( $locale !== $localeDefault ){
            $p_app = substr($p_app, strlen($locale)+1 );
        }
        
        $app='main';
        $default = array('name'=>'main');
        if( isset($routingTable['__default__']) ){
            $default=$routingTable['__default__'];
        }
        
        //取出並比對Routing資料
        $match=false;
        $app='';
        $app_path='';
        $path_vars=array();
        foreach( $routingTable as $path=>$config ){
            //二級以上動態路由的判定
            if( strpos($path, '*') !== false ){
                $re_path='^'.preg_quote($path).'/';
                $re_path=str_replace('/', '\/', $re_path);
                $re_path=str_replace('\*', '([^\/]+?)', $re_path);
                $re_path='/'.$re_path.'/';
                //print($path).'<br>';
                if( preg_match_all( $re_path, $p_app, $matches ) ){ // $p_app 去除 ext 的路徑
                    //取得母 app
                    $parents = RoutingConfigs::$parents[ $config['name'] ];
                    if( is_array($parents) ){
                        $parents = RoutingConfigs::$parents[ $config['name'] ][ $path ];
                    }
                    
                    $match=true;
                    $app = $config['name'];
                    $app_path = array_shift($matches);
                    $app_path = substr($app_path[0], 0, -1);

                    //將比對出來的變數一一儲存下來
                    //動態路由內的變數，將依序插入APP::$params陣列的前方
                    $matches_vars = array();
                    foreach( $matches as $ms ){
                        $matches_vars[] = $ms[0];
                    }
                    /*echo '<pre>';
                    print_r($matches_vars).'</pre><br>';*/
                    
                    //更新自身和所有母親APP的路徑
                    $i=0;
                    $renew_app=$app;
                    $renew_levels = count($matches);
                    do{
                        $renew_parents = RoutingConfigs::$parents[ $renew_app ];
                        if( is_array($renew_parents) ){
                            $renew_parents = RoutingConfigs::$parents[ $config['name'] ][ $path ];
                        }

                        //檢查是否有母APP
                        if( ! empty(RoutingConfigs::$parents[$renew_app]) ){
                            $renew_path=RoutingConfigs::$maps[ $renew_app ][ $renew_parents ]; //原路徑（含星號的路徑）
                            $updated_path=vsprintf( str_replace('*', '%s', $renew_path), $matches_vars ); //更新後的路徑
                            
                            //echo $i.' APP:'.$renew_app.' PARENTS:'.$renew_parents.' PATH:'.$updated_path."\n";
                            
                            //更新正查app->path
                            RoutingConfigs::$maps[ $renew_app ][ $renew_parents ]=$updated_path;
                            //更新反查path->app
                            RoutingConfigs::$r_maps[ $updated_path ]=$renew_app;
                            unset(RoutingConfigs::$r_maps[ $renew_path ]);
                            
                            $renew_app=RoutingConfigs::$parents[$renew_app];
                            //更新 APP::$params['parents']
                            $path_vars[ $renew_parents ] = $matches[($renew_levels-$i-1)][0];
                            /*$path_vars[] = array(
                                'parent_id' => $matches[($renew_levels-$i-1)][0],
                                'parents' => $renew_parents,
                            );*/    
                        }else{
                            $renew_app='';
                        }
                        $i+=1;

                        if( $i>10 ){ break; }
                    }while( ! empty($renew_app) );
                    
                    break;
                }
                if( $match ) continue;
            }
            //一般路由
            if( $path.'/' === substr($p_app, 0, strlen($path)+1) ){
                $match=true;
                $app = $config['name'];
                $app_path = $path;
                break;
            }
        }
        /*echo '<pre>';
        print_r(RoutingConfigs::$maps).'</pre><br>';
        echo '<pre>';
        print_r(RoutingConfigs::$r_maps).'</pre><br>';
        echo '<pre>';
        print_r(RoutingConfigs::$parents).'</pre><br>';
        echo $i;*/
        
        //echo 'app_path: '.$app_path.'<br>';
        if( ! $match ){
            $app = $default['name'];
        }
        
        //移除屬於app的路徑
        $p_params = $p_app;
        if( $app!='main' ){
            $p_params = substr($p_app, strlen($app_path)+1 );
        }
        /*echo 'p: '.$p.'<br>';
        echo 'p_app: '.$p_app.'<br>';
        echo 'p_params: '.$p_params.'<br>';*/
        
        //更新屬於參數的路徑區域
        $nodes=explode('/', $p_params);
        /*echo '<pre>';
        print_r($nodes).'</pre><br>';*/
        
        $handler = $app;
        if( $prefix!='main' ){
            $handler = $prefix.'#'.$handler;
        }
        
        return array(
            'locale'=>$locale,
            'site'=>$sitekey,
            'prefix'=>$prefix,
            'prefixFull'=>$prefixFull,
            'app'=>$app,
            'params'=>$nodes,
            'parents'=>$path_vars,
            'doctype'=>$ext,
            'handler'=>$handler,
        );
        
    }
    function getSiteKey(){
        global $my_base;
        
        //取得網站代號
        $sitekey=basename($my_base);
        $sitekey=str_replace('site.', '', $sitekey);
        $sitekey=str_replace('site_', '', $sitekey);
        
        return $sitekey;
    }
}

?>