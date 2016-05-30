<ul id="progress">
  <li>Sending webmention <pre>source=<?= $source ?><br>target=<?= $target ?></pre></li>
  <li class="discover"><span class="ui active small inline loader"></span> Discovering Webmention endpoint <pre class="endpoint hidden"></pre></li>
</ul>
<script>
$(function(){
  $.post("/receive/discover", {
    target: $("#target").val()
  }, function(data) {
    $("#progress .discover .loader").remove();
    $("#progress .discover .endpoint").text(data.endpoint).removeClass('hidden');
    $("#progress").append('<li class="send-webmention"><span class="ui active small inline loader"></span> Sending Webmention <div>Response Code: <code class="http-code"></code></div> <pre class="results hidden"></pre></li>');
    $.post("/receive/send-webmention", {
      source: $("#source").val(),
      target: $("#target").val(),
      endpoint: data.endpoint
    }, function(data) {
      console.log(data);
      $("#progress .send-webmention .loader").remove();
      $("#progress .send-webmention .http-code").text(data.result.code);
      $("#progress .send-webmention .results").text(data.result.body).removeClass('hidden');
      if([200,201,202].indexOf(data.result.code) == -1) {
        $("#progress").append('<li class="error"><span class="header">Failed!</span> Your Webmention endpoint did not return a valid HTTP status code. The raw response from your Webmention endpoint is displayed above.</li>');
      } else {
        if(data.result.code == 201) {
          // If the endpoint returned 201, check that there is a 'location' header
          if(data.result.headers.Location) {
            $("#progress").append('<li class="success"><span class="header">Success!</span> Your Webmention endpoint returned HTTP 201 and a Location header: <a href="'+data.result.headers.Location+'">'+data.result.headers.Location+'</a>.</li>');
          } else {
            $("#progress").append('<li class="error"><span class="header">Error!</span> Your Webmention endpoint returned HTTP 201 but did not return a Location header.</li>');
          }
        } else {
          $("#progress").append('<li class="success"><span class="header">Success!</span> Your Webmention endpoint returned HTTP '+data.result.code+' acknowledging the Webmention request.</li>');
        }
      }
    });
  });
});
</script>
