function uploadProgress(evt) {
	if (evt.lengthComputable) {
		var percentComplete = Math.round(evt.loaded * 100 / evt.total);
		document.getElementById('prog').value = percentComplete;
		if(document.getElementById('prog').value<100) {
			document.getElementById("prog").style.display = "block";
		} else {
			document.getElementById("prog").style.display = "none";
		}
	} else {
		document.getElementById('fileStatus').innerHTML = 'Error in percentage calculation';
	}
}

function uploadComplete(evt) {
	if(evt.target.readyState == 4 && evt.target.status == 200) {
		document.getElementById('fileStatus').innerHTML = evt.target.responseText;
		if(evt.target.responseText.includes("complete")) {
			location.reload();
		}
	}
}

function uploadFailed() {
	document.getElementById('fileStatus').innerHTML = "There was an error attempting to upload the file.";
	document.getElementById("prog").style.display = "none";
}

function uploadCanceled() {
	document.getElementById('fileStatus').innerHTML = "The upload has been canceled by the user or the browser dropped the connection.";
	document.getElementById("prog").style.display = "none";
}

function upload() {
	if ($('#upload_file').val == "" || user_level == "viewonly") {
		return;
	}
	var the_file;
	$('#fileStatus').html("");
	if ($('#upload_file')[0].files[0]) {
		the_file = $('#upload_file')[0].files[0];
	} else {
		$('#fileStatus').html("Error finding file.");
		return;
	}
	var fd = new FormData();
	fd.append("file", the_file);
	var xhr = new XMLHttpRequest();
	xhr.open('POST', 'files.php?d=' + server_select + '&p=upload', true);

	xhr.upload.addEventListener("progress", uploadProgress, false);
	xhr.addEventListener("load", uploadComplete, false);
	xhr.addEventListener("error", uploadFailed, false);
	xhr.addEventListener("abort", uploadCanceled, false);

	xhr.send(fd);
	$('#upload_file').val("");
}

$(document).ready(function() {
  $('#upload_file').on('change', function() {
		upload();
	});


  $('#upload_button').on('click', function() {
        if(user_level == "viewonly") {
            alert("You have view only access","warning",true);
        	return;
        }
		$('#upload_file').click();
	});


  $("#delete_files_").click(function(){
    var delete_files = [];
    if ($("input[name='filecheckbox']:checked").length > 0){
      $.each($("input[name='filecheckbox']:checked"), function(){
        delete_files.push($(this).val());
      });
      if (confirm("Do you really want to destroy those files?") == true) {
        all_files = delete_files + "";
        var x = new XMLHttpRequest();
        x.open("GET","./files.php?p=delete&d=" + server_select + "&del=" + all_files,true);
        x.send();
				location.reload();
        return false;
      }
    } else {
      alert("Please select one!");
    }
  });


  $("#make_latest_").click(function(){
    var make_latest = [];
    if ($("input[name='filecheckbox']:checked").length == 1){
      $.each($("input[name='filecheckbox']:checked"), function(){
        make_latest.push($(this).val());
      });
      if (confirm("Do you want to use this on startup?") == true) {
        var x = new XMLHttpRequest();
        x.open("GET","./files.php?p=latest&d=" + server_select + "&latest=" + make_latest,true);
        x.send();
				location.reload();
        return false;
      }
    } else {
      alert("Please select one!");
    }
  });
});
