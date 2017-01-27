/**
 * Created by erkki on 26.1.2017.
 */

//quick and dirty cpu / mem updater
$( document ).ready(function() {
    var cpulog = []; // array for future if multiple samples are needed.
    var delay = 2000;

    loop();

    function loop(){
        getData();

        var data = cpulog[cpulog.length - 1];

        if(data !== undefined) updateload(data);

        setTimeout(loop,delay);
    }

    function updateload(data){
        //<div id="serverload" style="float: right; margin-right: 20px;">
        //    <span id="cpu" style="padding: 6px;background-color: rgb(102, 255, 0);">00 %</span>
        //    <span id="mem" style="padding: 6px;background-color: rgb(102, 255, 0);">0.00/0.00 GB</span>
        //</div>

        var cpuElem = $("#cpu");
        var memElem = $("#mem");



        var cpu = Math.round(100 - parseInt(data.cpu.idle));
        var mempercentage = parseInt(data.mem.split("/")[0]) / 100 * parseInt(data.mem.split("/")[1]);

        cpuElem.text(cpu+" %");
        cpuElem.css("background-color", getColor(cpu / 100));

        memElem.text(data.mem + " GB");
        memElem.css("background-color", getColor(mempercentage));
    }

    function getColor(value){
        //value from 0 to 1
        var hue=((1-value)*120).toString(10);
        return ["hsl(",hue,",100%,50%)"].join("");
    }

    function getData(){
        $.ajax({
            dataType: "json",
            url: "/assets/api/cpumeminfo.php"
        }).done(function(data) {
            if(cpulog.push(data) > 20) cpulog.shift();
        }).fail(function() {
            if(cpulog.push(undefined) > 20) cpulog.shift();
            console.log( "error: cpumeminfo get fail." );
        });
    }
});

