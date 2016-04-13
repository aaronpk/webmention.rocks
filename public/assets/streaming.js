$(function(){

  if(window.EventSource) {
    // Subscribe to the streaming channel and insert responses as they come in
    var socket = new EventSource('/streaming/sub?id=test-'+$("#test-num").data("num"));

    socket.onopen = function() {
    };

    socket.onmessage = function(event) {
      var data = JSON.parse(event.data);
      console.log(data);
      if(data.text.action == 'delete') {
        $("li[data-response-id="+data.text.hash+"]").remove();
      } else {
        if($("li[data-response-id="+data.text.hash+"]").length == 0) {
          $(".stream."+data.text.type).prepend(data.text.html);
        } else {
          $("li[data-response-id="+data.text.hash+"]").html(data.text.html);
        }
      }
      $(".responses-row ul").each(function(row){ 
        if($(this).children("li").length == 0) { 
          $(this).parent().addClass("empty");
        } else {
          $(this).parent().removeClass("empty");
        }
      });
    };

    socket.addEventListener('update', function(event) {
      // log('UPDATE(' + event.lastEventId + '): ' + event.data);
    });

    socket.onerror = function(event) {
      console.log("error: ", event);
    };
  }

});
