<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div style="margin: 0 auto; max-width: 600px;">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <section class="content">
    <p>This is a Webmention endpoint. You should send a POST request to this URL with <code>source</code> and <code>target</code> parameters to send a Webmention.</p>

    <p>Webmention endpoints don't have to respond to a GET request, but sometimes it's useful to tell people what it is with a short description like this.</p>

    <p>You can fill in the source and target URLs in the form below and send a webmention manually as well!</p>
  </section>

  <section class="content webmention-form">
    <h3>Send a Webmention manually</h3>
    <form action="/test/<?= $num ?>/webmention" method="POST" class="ui form">
      <div class="field">
        <input type="url" placeholder="source" name="source" required="required">
      </div>    
      <div class="field">
        <input type="url" placeholder="target" name="target" required="required">
      </div>
      <button class="ui primary button" type="submit">Send Webmention</button>
      <input type="hidden" name="via" value="browser">
    </form>
  </section>
</div>
