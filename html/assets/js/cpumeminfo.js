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

        setInterval(() => {
            getData();
            var data = cpulog[cpulog.length - 1];
            if (data !== undefined) updateload(data);
        }, delay);
    }

    function updateload(data) {
        var cpuElem = $("#cpu");
        var memElem = $("#mem");
    
        var cpu = Math.round(100 - parseInt(data.cpu.idle));
        var [usedMem, totalMem] = data.mem.split("/");
        var memPercentage = (parseFloat(usedMem) / parseFloat(totalMem)).toFixed(2);
    
        cpuElem.text(cpu + " %");
        cpuElem.css("background-color", getColor(cpu / 100));
    
        memElem.text(data.mem + " GB");
        memElem.css("background-color", getColor(memPercentage));
    }

    function getColor(value){
        //value from 0 to 1
        var hue=((1-value)*120).toString(10);
        return ["hsl(",hue,",100%,50%)"].join("");
    }

    function getData() {
        $.ajax({
            dataType: "json",
            url: "/assets/api/cpumeminfo.php"
        }).done(function(data) {
            if (cpulog.push(data) > 20) cpulog.shift();
        }).fail(function() {
            console.error("error: cpumeminfo get fail.");
        });
    }
});
