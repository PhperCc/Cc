function getCookie(c_name)
{
    if (document.cookie.length>0)
      {
      c_start=document.cookie.indexOf(c_name + "=")
      if (c_start!=-1)
        { 
        c_start=c_start + c_name.length+1 
        c_end=document.cookie.indexOf(";",c_start)
        if (c_end==-1) c_end=document.cookie.length
        return unescape(document.cookie.substring(c_start,c_end))
        } 
      }
    return ''
}

var ServiceApi = {
    socket: null,

    socketState: 0,

    sendTimerId: 0,

    socketInit: function(){
        if(ServiceApi.socket != null) return;
		var host = window.location.host;
        ServiceApi.socket = new WebSocket("ws://" + host + ":1228");
        ServiceApi.socket.onopen = function(){
            console.log("socket connected");
            ServiceApi.socketState = 1;
            ServiceApi.sendTimerId = window.setInterval(function(){
                if(ServiceApi.sendTaskList.length == 0) return;
                var $task = ServiceApi.sendTaskList.shift();
                var $request = $task.request;
                var token = getCookie('enhApiToken');
                $request.params['token'] = token;
                ServiceApi.socket.send(JSON.stringify($request));

                if($task.callback && $task.callback instanceof Function)
                {
                    ServiceApi.replyTaskList[$request.seq] = $task;
                }
                else if($task.interval && $task.interval > 0)
                {
                    window.setTimeout(function(){
                        $task.request.seq = ServiceApi.newSeq();
                        ServiceApi.sendTaskList.push($task);
                    }, $task.interval);
                }
            }, 10);
        };

        ServiceApi.socket.onmessage = function($evt){
            var $reply = JSON.parse($evt.data);
            var $task = ServiceApi.replyTaskList[$reply.seq];

            if($task.callback && $task.callback instanceof Function)
            {
                $task.callback($reply.seq, $reply.result, $reply.info, $reply.data);
            }

            if($task.interval && $task.interval > 0)
            {
                window.setTimeout(function(){
                    $task.request.seq = ServiceApi.newSeq();
                    ServiceApi.sendTaskList.push($task);
                }, $task.interval);
            }

            delete ServiceApi.replyTaskList[$reply.seq];
        };
        
        ServiceApi.socket.onclose = function()
        {
            console.log("socket closed");
            ServiceApi.socketState = 0;
            ServiceApi.socket = null;
            window.clearInterval(ServiceApi.sendTimerId);
            window.setTimeout(function(){
                console.log("socket reconnect");
                ServiceApi.socketInit();
            }, 500);
        };
    },

    newSeq: function(){return (new Date().valueOf() * 1000 + parseInt(Math.random() * 1000)).toString()},

    replyTaskList: {},

    sendTaskList: [],

    request: function($action, $params, $callback, $interval){
        ServiceApi.socketInit();
        var $request = {"seq": ServiceApi.newSeq(), "action": $action, "params": $params};
        ServiceApi.sendTaskList.push({"request": $request, "callback": $callback, "interval": $interval});
        return $request.seq;
    }
};
