<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <div class="ui success message">
    <div class="header">Great!</div>
    <p>We found your authorization endpoint! Click the button below to continue signing in. You will be redirected to your authorization endpoint to authenticate, and then will be signed in to this application.</p>
    <p><a href="<?= $authorization_url ?>" class="ui primary button">Continue</a></p>
    <p class="small">You will be redirected to <?= $authorization_endpoint ?></p>
  </div>
</div>
