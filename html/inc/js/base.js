$(document).ready(function() {
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
        return false;
      }
    } else {
      alert("Please select one!");
    }
  });
});
