$(function(){

  if(window.EventSource) {
    // Subscribe to the streaming channel and insert responses as they come in
    var socket = new EventSource('/streaming/sub?id=test-'+$("#test-num").data("num"));

    socket.onopen = function() {
    };

    function updateEmojiCount(emoji, by) {
      var count = parseInt($("li[data-emoji='"+emoji+"'] .count").text());
      $("li[data-emoji='"+emoji+"'] .count").text(count+by);      
      return count+by;
    }

    socket.onmessage = function(event) {
      var data = JSON.parse(event.data);
      console.log(data);
      if(data.text.action == 'delete') {
        if(data.text.emoji) {
          // Remove this emoji block if this was the only reacji
          if(updateEmojiCount(data.text.emoji, -1) == 0) {
            $("li[data-emoji="+data.text.emoji+"]").remove();
          }
        } else {
          $("li[data-response-id="+data.text.hash+"]").remove();
        }
      } else if(data.text.emoji) {
        // Add the emoji block if it's not there yet
        if($("li[data-emoji="+data.text.emoji+"]").length == 0) {
          $(".responses-row ul.reacji").append(data.text.reacji_html);
        } else {
          updateEmojiCount(data.text.emoji, 1);
        }
      } else {
        if($("li[data-response-id="+data.text.hash+"]").length == 0) {
          $(".stream."+data.text.type).prepend(data.text.html);
        } else {
          $("li[data-response-id="+data.text.hash+"]").replaceWith(data.text.html);
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
