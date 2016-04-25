<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div style="margin: 0 auto; max-width: 600px;">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <section class="content">
    <p>This is a Webmention endpoint. You should send a POST request to this URL with <code>source</code> and <code>target</code> parameters to send a Webmention.</p>

    <p>Webmention endpoints don't have to respond to a GET request, but sometimes it's useful to tell people what it is with a short description like this.</p>
  </section>
</div>
