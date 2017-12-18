$(document).ready(function() {
  sort('date');
})

function sort(sorting) {
  if (sorting.length == 0) {
        document.getElementById("filetable").innerHTML = "";
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("filetable").innerHTML = this.responseText;
            }
        }
        xmlhttp.open("GET", "./inc/functions/func_filetable.php?s=$server_select&sort="+sorting, true);
        xmlhttp.send();
    }
}
