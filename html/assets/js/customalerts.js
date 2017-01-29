/**
* Created by erkki on 28.1.2017.
*/

/*
 * Js file must include bottom of the page it to work.
 * Alerts are stored to local storage so we won't lose everything every time page is loaded.
 * Example usage customAlerts.add("message","info",true);
 * or customAlerts.add("message").show();
 * msg = msg, level: info, warning, error, show: nothing or true, will show modal.
 * level default: info, show: false
 *
 * Modal html.
 * <div id="alert_modal" class="modal">
 *   <div id="content" class="modal-content">
 *     <span id="close_modal" class="close">&times;</span>
 *     <p>Log of messages</p>
 *     <div id="messages"></div>
 *   </div>
 * </div>
 */

var customAlerts = (function(){
    var modal = document.getElementById('alert_modal');
    var msg_log = [];
    var storage_size = 40; // amount of messages stored in localstorage.



    function show_modal(){
       modal.style.display = "block";
    }
    function hide_modal(){
       modal.style.display = "none";
    }
    function addMsgToModal(msg) {
        var date = new Date(msg.date);
        var messages = document.getElementById("messages");
        messages.innerHTML = "<div class='msg " + msg.level + "'>"+
            "<div class='msg-col'><span>"+msg.msg.replace(/(\r\n|\n|\r)/gm, "<br>")+"</span></div>"+
            "<div class='time-col'><span>"+date.toLocaleString()+"</span></div>"+
            "</div>" + messages.innerHTML;
    }

    document.getElementById("reset_alerts").onclick = function(){
        msg_log = [];
        localStorage.removeItem("alert_messages");
        document.getElementById("messages").innerHTML = "";
    };
    // modal close button
    document.getElementById("close_modal").onclick = function(){
        hide_modal();
    };
    // Close modal when clicked outside the box
    window.onclick = function(event) {
        if (event.target == modal) {
            hide_modal();
        }
    };

    function save_messages(){
        if (typeof(Storage) !== "undefined") {
            if(msg_log.length > storage_size){
                msg_log = msg_log.slice((msg_log.length - storage_size),msg_log.length);
            }
            localStorage.setItem("alert_messages", JSON.stringify(msg_log));
        }
    }

    function init(){
        if (typeof(Storage) !== "undefined") {
            if(localStorage.getItem("alert_messages") !== null){
                var messages = JSON.parse(localStorage.getItem("alert_messages"));
                messages.forEach(function(msg){
                    msg_log.push(msg);
                    addMsgToModal(msg);
                });
            }
        }
        msg_log.forEach(function(element) {
            addMsgToModal(element);
        });
    }

    init();

    return {
        // add nyw message.
        add: function (msg,level,show){
            msg   = (msg)? msg: '';
            level = (level)? level:"info";
            var message = {"msg": msg, "level": level, "date": Date.now()};
            msg_log.push(message);
            addMsgToModal(message);
            save_messages();
            if(show) show_modal();
            return this;
        },
        // show alert modal
        show: function(){
            show_modal();
            return this;
        },
        // hide alert modal
        hide: function () {
            hide_modal();
            return this;
        }
    }
})();

// for testing.


 //customAlerts.add('test war ad asd asd asd asd a\n asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd asdasdasdasd asd \n ad asd asd asd asd a\n asd asd ad asd asd asd asd a\n asd asd asd \nasd \nasd asd asd \n ning','warning');
 //customAlerts.add('test2 errro');
 //customAlerts.add('test3 info'  ,'info');
 //customAlerts.add('test4 info'  ,'warning');
 //customAlerts.add('test5 error' ,'error');



