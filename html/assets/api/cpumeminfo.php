<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	}
	session_write_close();

/**
 * Created by PhpStorm.
 * User: erkki
 * Date: 25.1.2017
 * Time: 4:28
 *
 *
 * Simple php fie that returns cpu and mem usage in json format.
 */

// returns free mem in GB format 4.02/7.79
function getMem(){
    $exec_free = explode("\n", trim(shell_exec('free')));
    $get_mem = preg_split("/[\s]+/", $exec_free[1]);
    $mem = number_format(round($get_mem[2]/1024/1024, 2), 2) . '/' . number_format(round($get_mem[1]/1024/1024, 2), 2);

    return $mem;
}

// returns array with cpu usages in persentage. user,nice,sys,idle
function getCpuUsage(){
    $stat1 = file('/proc/stat');
    sleep(1);
    $stat2 = file('/proc/stat');

    $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0]));
    $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0]));

    $dif = array(
        $dif['user'] = $info2[0] - $info1[0],
        $dif['nice'] = $info2[1] - $info1[1],
        $dif['sys']  = $info2[2] - $info1[2],
        $dif['idle'] = $info2[3] - $info1[3]
    );

    $total = array_sum($dif);
    $cpu = array();

    foreach($dif as $x=>$y) {
        $cpu[$x] = round($y / $total * 100, 1);
    }

    return $cpu;
}

$cpu = getCpuUsage();
$mem = getMem();

$results = array(
    'cpu' => array(
        'user'=> $cpu[0],
        'nice'=> $cpu[1],
        'sys'=> $cpu[2],
        'idle'=> $cpu[3]
    ),
    'mem' => $mem,
);

echo json_encode($results);
die();
?>

