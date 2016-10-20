var loc = window.location.pathname;
var dir = loc.substring(0, loc.lastIndexOf('/'));
var refreshtime=500;
function tc_console()
{
	asyncAjax("GET",dir + "/assets/console.php?d=" + server_select + "&s=console",Math.random(),display,{},"console");
	asyncAjax("GET",dir + "/assets/console.php?d=" + server_select + "&s=chat",Math.random(),display,{},"chat");
	setTimeout(tc_console,refreshtime);
}

function display(xhr,cdat,scr)
{
	if(xhr.readyState==4 && xhr.status==200)
	{
		var scrollContainer = document.getElementById(scr);
		var shouldScroll = scrollContainer.scrollTop + scrollContainer.offsetHeight >= scrollContainer.scrollHeight;
		scrollContainer.innerHTML=xhr.responseText;
		if(shouldScroll) {
			scrollContainer.scrollTop = scrollContainer.scrollHeight;
		}
	}
}
function asyncAjax(method,url,qs,callback,callbackData,scr)
{
    var xmlhttp=new XMLHttpRequest();
    //xmlhttp.cdat=callbackData;
    if(method=="GET")
    {
        url+="&t="+qs;
    }
    var cb=callback;
    callback=function()
    {
        var xhr=xmlhttp;
        //xhr.cdat=callbackData;
        var cdat2=callbackData;
        cb(xhr,cdat2,scr);
        return;
    };
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
//tc_console('chat');
<?php
 //end it
?>