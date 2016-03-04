<?php
class MT{ static $marktime=array(); static $queries=array(); }
function gettime(){ return (float)microtime(true)*1000; }
function marktime( $type='Core', $name='' ){
    MT::$marktime[$type][]=array( 'name'=>$name, 'time'=>gettime(), 'memory'=>memory_get_usage() );
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
        $memory_total=$last['memory']-$first['memory'];
        echo '<h2>Marktime Report @ '.$cycle.':</h2>'."\n";
        echo '<table style="width:100%;" border="1">'."\n";
        echo '<tr>'."\n";
        echo '    <th>Name</th>'."\n";
        echo '    <th style="width:10%;">Seg.</th>'."\n";
        echo '    <th style="width:25%;" colspan="3">Time Execution</th>'."\n";
        echo '    <th style="width:25%;"colspan="3">Memory Consume</th>'."\n";
        echo '</tr>'."\n";
        foreach( $marktime as $k=>$now ){
            echo '<tr style="text-align:right;">'."\n";
            $consume=($now['time']-$prev['time']);
            $memory_consume=($now['memory']-$prev['memory']);
            echo '<td>';
            if( $now['name'] ){
                echo '<b>'.$now['name'].'</b>'."\n";
            }
            echo '</td>';
            echo '<td><i>Seg '.($k).'. </td>';
            echo '<td><b>'.sprintf('%01.4f', $consume ).' ms.</b></td> '."\n";
            echo '<td><b>'.sprintf('%01.1f', ($consume/$total)*100 ).'%</b> </td>'."\n";
            echo '<td><b>'.sprintf('%01.4f', $now['time'] - $first['time'] ).' ms.</b></td> '."\n";
            echo '<td><b>'.marktime_filesize($memory_consume).'. </b></td> '."\n";
            echo '<td><b>'.sprintf('%01.1f', ($memory_consume/$memory_total)*100 ).'% </b> </td>'."\n";
            echo '<td><b>'.marktime_filesize($now['memory'] - $first['memory']).'. </b></td> '."\n";
            echo '</tr>'."\n";
            $prev=$now;
        }
        echo '<tr>'."\n";
        echo '    <th colspan="2"></th>'."\n";
        echo '    <th colspan="3"><i><u>Total Execute: '.sprintf('%01.4f', $total ).' ms.</u></i></th>'."\n";
        echo '    <th colspan="3"><i><u>Total Consume: '.marktime_filesize($memory_total).'. ('.readable_filesize($memory_total).')</u></i></th>'."\n";
        echo '</tr>'."\n";
        echo '</table>'."\n";
    }
}
function marktime_filesize($size){
    return sprintf('%.2f', ($size / (1024)) ).' KB';
}
function markquery( $type , $sql , $time1 , $time2 ){
    //$backtrace=debug_backtrace();
    //MT::$queries[]=array( 'type'=>ucfirst($type) , 'sql'=>$sql , 'time'=>($time2-$time1) , 'backtrace'=>$backtrace );
    MT::$queries[]=array( 'type'=>ucfirst($type) , 'sql'=>$sql , 'time'=>($time2-$time1) );
}
function markquery_report(){
    if( APP::$systemConfigs['Debug']==0 ) return ;
    
    $marktime=MT::$queries;
    echo '<b>Queries Report:</b><br><br>';
    echo '<table width="100%" border="1">';
    echo '<tr style="text-align:left;">';
    echo '<th width="70px"><i>#</i></th>';
    echo '<th width="70px">Type</th>';
    echo '<th>SQL</th>';
//echo '<th></th>'; // for backtrace mode
    echo '<th width="100px">Time</th>';
    //echo '<th width="100px">Memory</th>';
    echo '</tr>';
    $sum=0;
    foreach( $marktime as $k=>$data ){
        echo '<tr>';
        echo '<td><i>SQL '.($k).'. </i></td>';
        echo '<td>'.$data['type'].'</td>';
        echo '<td>'.$data['sql'].'</td>';
//echo '<td><pre>'.print_r($data['backtrace']).'</pre></td>'; // for backtrace mode
        echo '<td>'.sprintf('%01.4f',$data['time']).' ms</td>';
        //echo '<td>'.marktime_filesize($data['memory']).'</td>';
        echo '</tr>';
        $sum+=$data['time'];
    }
    echo '<tr><td colspan="4">';
    echo '<i><u>Total Execute: '.sprintf('%01.4f', $sum ).' ms.</u></i>';
    echo '</td></tr>';
    echo '</table>';
}
?>