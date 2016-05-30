<ul id="progress">
</ul>
<script>
var last_result = <?= json_encode($last_result) ?>;

function start_test() {
  $("#progress").children().remove();

  $("#progress").append('<li>Sending Webmention <pre>source=<?= $source ?><br>target=<?= $target ?></pre></li>');
  $("#progress").append('<li class="discover">'+loading_spinner+' Discovering Webmention endpoint <pre class="endpoint hidden"></pre></li>');

  $.post("/receive/discover", {
    target: $("#target").val(),
    code: $("#code").val()+":endpoint"
  }, function(data) {
    $("#progress .discover .loader").remove();
    $("#progress .discover .endpoint").text(data.endpoint).removeClass('hidden');
    $("#progress").append('<li class="send-webmention">'+loading_spinner+' Sending Webmention <div>Response Code: <code class="http-code"></code></div> <pre class="results hidden"></pre></li>');
    $.post("/receive/send-webmention", {
      source: $("#source").val(),
      target: $("#target").val(),
      endpoint: data.endpoint,
      code: $("#code").val()+":1"
    }, function(data) {
      $("#progress .send-webmention .loader").remove();
      $("#progress .send-webmention .http-code").text(data.result.code);
      $("#progress .send-webmention .results").text(data.result.body).removeClass('hidden');
      show_results(data);
    });
  });
}

function load_results() {
  $("#progress").append('<li class="head"><span>Showing Previous Results</span> <a href="javascript:start_test();">Run Again</a></li>');
  $("#progress").append('<li>Sending Webmention <pre>source='+last_result.source+'<br>target='+last_result.target+'</pre></li>');
  $("#progress").append('<li class="discover">Discovering Webmention endpoint <pre class="endpoint">'+last_result.endpoint+'</pre></li>');
  $("#progress").append('<li class="send-webmention">Sending Webmention <div>Response Code: <code class="http-code">'
    +last_result.result.code+'</code></div> <pre class="results">'+
    last_result.result.body+'</pre></li>');
  show_results(last_result);
}

function show_results(data) {
  if([200,201,202].indexOf(data.result.code) == -1) {
    $("#progress").append('<li class="error">'+red_x+' Your Webmention endpoint did not return a valid HTTP status code. The raw response from your Webmention endpoint is displayed above.</li>');
  } else {
    if(data.result.code == 201) {
      // If the endpoint returned 201, check that there is a 'location' header
      if(data.result.headers.Location) {
        $("#progress").append('<li class="success">'+green_check+' Your Webmention endpoint returned HTTP 201 and a Location header: <a href="'+data.result.headers.Location+'">'+data.result.headers.Location+'</a>.</li>');
      } else {
        $("#progress").append('<li class="error">'+red_x+' Your Webmention endpoint returned HTTP 201 but did not return a Location header.</li>');
      }
    } else {
      $("#progress").append('<li class="success">'+green_check+' Your Webmention endpoint returned HTTP '+data.result.code+' acknowledging the Webmention request.</li>');
    }
  }
}

$(function(){
  <? if(!$last_result): ?>
    start_test();
  <? else: ?>
    load_results();
  <? endif; ?>
});
</script>
