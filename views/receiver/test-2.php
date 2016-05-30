<ul id="progress">
</ul>
<script>
var state = <?= json_encode($last_result) ?>;

function reset_state() {
  state = {
    endpoint: null,
    result_1: null,
    result_2: null,
    result_3: null
  };
}

function render_state() {
  $("#progress").children().remove();

  $("#progress").append('<li class="discover">'+loading_spinner+' Discovering Webmention endpoint <pre class="endpoint hidden"></pre></li>');

  if(state.endpoint) {
    $("#progress .discover .loader").remove();
    $("#progress .discover .endpoint").text(state.endpoint).removeClass('hidden');

    $("#progress").append('<li class="step-1">'+loading_spinner+' Sending Webmention with invalid source URL <span class="results hidden block"></span></li>');

    if(state.result_1) {
      $("#progress .step-1 .loader").remove();

      var result;
      if(state.result_1.result.code == 400) {
        result = green_check + ' Your endpoint rejected the request';
      } else {
        result = red_x + ' Your endpoint did not reject the request';
      }
      $("#progress .step-1 .results").html(result).removeClass('hidden');

      $("#progress").append('<li class="step-2">'+loading_spinner+' Sending Webmention with invalid target URL <span class="results hidden block"></span></li>');

      if(state.result_2) {
        $("#progress .step-2 .loader").remove();

        if(state.result_2.result.code == 400) {
          result = green_check + ' Your endpoint rejected the request';
        } else {
          result = red_x + ' Your endpoint did not reject the request';
        }
        $("#progress .step-2 .results").html(result).removeClass('hidden');

        $("#progress").append('<li class="step-3">'+loading_spinner+' Sending Webmention with invalid source and target URLs <span class="results hidden block"></span></li>');

        if(state.result_3) {
          $("#progress .step-3 .loader").remove();

          if(state.result_3.result.code == 400) {
            result = green_check + ' Your endpoint rejected the request';
          } else {
            result = red_x + ' Your endpoint did not reject the request';
          }
          $("#progress .step-3 .results").html(result).removeClass('hidden');

          if(state.result_3.result.code == 400) {
            $("#progress").append('<li>'+green_check+' You passed the test!');
          }

          $("#progress").prepend('<li class="head"><span>Showing Previous Results</span> <a href="javascript:start_test();">Run Again</a>');
        }
      }
    }
  }
}

function start_test() {
  reset_state();
  render_state();

  $.post("/receive/discover", {
    target: $("#target").val(),
    code: $("#code").val()+":endpoint"
  }, function(data) {
    state.endpoint = data.endpoint;
    render_state();

    send_webmention('1', data.endpoint, 'jwoijgoisdjlskjegisvjowuehjtkx', $("#target").val(), function(data){
      state.result_1 = data;
      render_state();

      send_webmention('2', data.endpoint, $("#source").val(), 'owiejduvyeiwljjjcjmvbpsouehgd', function(data){
        state.result_2 = data;
        render_state();

        send_webmention('3', data.endpoint, 'sjuhvhwieuhtiwudcjvhuh', 'owiejduvyeiwljjjcjmvbpsouehgd', function(data){
          state.result_3 = data;
          render_state();
        });

      });
    });

  });
}

function send_webmention(index, endpoint, source, target, callback) {
  $.post("/receive/send-webmention", {
    source: source,
    target: target,
    endpoint: endpoint,
    code: $("#code").val()+":"+index
  }, function(data) {
    callback(data);
  });
}

$(function(){
  if(!state.endpoint) {
    start_test();
  } else {
    render_state();
  }
});
</script>
