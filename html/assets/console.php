<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	echo "And what do you think you're doing?";
	die();
}
$base_dir="/var/www/factorio/";
$html_dir="/var/www/html";
include($html_dir.'/getserver.php');
if(!isset($server_select)) {
	//die('Invalid Server');
	$server_select = "server1";
}
?>
var refreshtime=500;
function tc()
{
	asyncAjax("GET","/console.php?d=<?php echo $server_select; ?>&m=",Math.random(),display,{});
	setTimeout(tc,refreshtime);
}
function display(xhr,cdat)
{
	if(xhr.readyState==4 && xhr.status==200)
	{
		var scrollContainer = document.getElementById('console');
		var shouldScroll = scrollContainer.scrollTop + scrollContainer.offsetHeight >= scrollContainer.scrollHeight;
		document.getElementById("console").innerHTML=xhr.responseText;
		if(shouldScroll) {
			scrollContainer.scrollTop = scrollContainer.scrollHeight;
		}
	}
}
function asyncAjax(method,url,qs,callback,callbackData)
{
    var xmlhttp=new XMLHttpRequest();
    //xmlhttp.cdat=callbackData;
    if(method=="GET")
    {
        url+="?"+qs;
    }
    var cb=callback;
    callback=function()
    {
        var xhr=xmlhttp;
        //xhr.cdat=callbackData;
        var cdat2=callbackData;
        cb(xhr,cdat2);
        return;
    }
    xmlhttp.open(method,url,true);
    xmlhttp.onreadystatechange=callback;
    if(method=="POST"){
            xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            xmlhttp.send(qs);
    }
    else
    {
            xmlhttp.send(null);
    }
}
tc();