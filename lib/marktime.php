<?php
class MT{ static $marktime=array(); static $queries=array(); }
function gettime(){ return (float)microtime(true)*1000; }
function marktime( $type='Core', $name='' ){
    MT::$marktime[$type][]=array( 'name'=>$name, 'time'=>gettime() );
}
function marktime_report( $type='' ){
    if( APP::$systemConfigs['Debug']==0 ) return ;
    
    $cycles=array( $type );
    if( empty($type) ){ $cycles=array_keys( MT::$marktime ); }
    
    foreach( $cycles as $cycle ){
        $marktime=MT::$marktime[ $cycle ];
        $prev=pos($marktime);
        $first=$prev;
        $last=end($marktime);
        reset($marktime);
        $total=$last['time']-$first['time'];
        echo '<b>Marktime Report @ '.$cycle.':</b><br><br>';
        foreach( $marktime as $k=>$now ){
            $consume=($now['time']-$prev['time']);
            echo '<i>Seg '.($k).'. '.sprintf('%01.4f', $consume ).' ms.</i>';
            if( $now['name'] ) echo str_repeat('&nbsp;', 1).'<b>'.sprintf('%01.1f', ($consume/$total)*100 ).'% - '.$now['name'].'</b><br>';
            $prev=$now;
        }
        echo '<i><u>Total Execute: '.sprintf('%01.4f', $total ).' ms.</u></i><br><br>';
    }
}
function markquery( $type , $sql , $time1 , $time2 ){
    MT::$queries[]=array( 'type'=>ucfirst($type) , 'sql'=>$sql , 'time'=>($time2-$time1) );
}
function markquery_report(){
    if( APP::$systemConfigs['Debug']==0 ) return ;
    
    $marktime=MT::$queries;
    echo '<b>Queries Report:</b><br><br>';
    echo '<table width="100%" style="border:1px black solid;">';
    echo '<tr style="text-align:left;">';
    echo '<th width="70px"><i>#</i></th>';
    echo '<th width="70px">Type</th>';
    echo '<th>SQL</th>';
    echo '<th width="100px">Time</th>';
    echo '</tr>';
    $sum=0;
    foreach( $marktime as $k=>$data ){
        echo '<tr>';
        echo '<td><i>SQL '.($k).'. </i></td>';
        echo '<td>'.$data['type'].'</td>';
        echo '<td>'.$data['sql'].'</td>';
        echo '<td>'.sprintf('%01.4f',$data['time']).' ms</td>';
        echo '</tr>';
        $sum+=$data['time'];
    }
    echo '<tr><td colspan="4">';
    echo '<i><u>Total Execute: '.sprintf('%01.4f', $sum ).' ms.</u></i>';
    echo '</td></tr>';
    echo '</table>';
}
?>