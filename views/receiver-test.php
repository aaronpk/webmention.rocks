<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="post-container h-entry">
  <div class="post-main">
    <div class="left p-author h-card">
      <a href="/">
        <img src="/assets/webmention-rocks-icon.png" width="80" class="u-photo" alt="Webmention Rocks!">
      </a>
    </div>
    <div class="right">
      <h1 class="p-name"><a href="/receive/<?= $num ?>">Receiver Test #<?= $num ?></a></h1>
      <div class="e-content"><?= $test['description'] ?></div>
      <div class="meta">
        <a href="/test/<?= $num ?>" class="u-url">
          Published:
          <time class="dt-published" datetime="<?= $published->format('c') ?>">
            <?= $published->format('l F j, Y g:ia P') ?>
          </time>
        </a>
      </div>
    </div>
  </div>

  <?php if(isset($_SESSION['error'])): ?>
    <div class="ui error message">
    <?php
    switch($_SESSION['error']) {
      case 'host-mismatch':
        echo '<div class="header">Hostname Mismatch</div>';
        echo '<p>The URL you use for the test must be on the same domain as the URL you used to sign in.</p>';
        break;
    }
    unset($_SESSION['error']);
    ?>
    </div>
  <?php endif; ?>

  <?php if(!is_logged_in()): ?>
    <div class="ui warning message sign-in small">
      <div class="header">Please Sign In</div>
      <p>In order to prevent this site from being used to send spam, you need to first sign in to authenticate your domain. This site will only send Webmentions to the same domain that you used to sign in.</p>

      <form action="/auth/start" method="GET">
        <div class="ui fluid action input">
          <input type="url" name="url" placeholder="http://you.example.com">
          <button class="ui button">Sign In</button>
        </div>
        <input type="hidden" name="return-to" value="/receive/<?= $num ?>">
      </form>
    </div>
  <?php else: ?>
    <div class="ui success message sign-in small">
      <div class="header">Signed in</div>
      <p>You are signed in as <b><?= $this->e($_SESSION['me']) ?></b></p>
      <p>Enter the URL to a post on your website to use for this test. The URL must be on the same domain as the URL you signed in as.</p>

      <form action="/receive/<?= $num ?>/start" method="GET">
        <div class="ui fluid action input">
          <input type="url" name="url">
          <button class="ui button">Begin Test</button>
        </div>
      </form>

    </div>
  <?php endif; ?>

  <div class="post-footer">
    <p>Results are stored for 48 hours and may be deleted after that time.</p>
  </div>
</div>

<div id="test-num" data-num="<?= $num ?>"></div>
