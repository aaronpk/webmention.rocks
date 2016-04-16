<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="post-container h-entry">
  <div class="post-main has-responses">
    <div class="left p-author h-card">
      <a href="/">
        <img src="/assets/webmention-rocks-icon.png" width="80" class="u-photo" alt="Webmention Rocks!">
      </a>
    </div>
    <div class="right">
      <h1 class="p-name"><a href="/test/<?= $num ?>/step/<?= $step ?>">Update Test #<?= $num ?> Step <?= $step ?>.</a></h1>
      <div class="e-content"><?= $test['steps'][$step]['description'] ?></div>
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
  <div class="post-responses mini-responses">
    <style type="text/css">
    .mini-responses {
      padding: 12px;

    }
    .mini-responses ul {
      margin: 0;
      padding: 0;
      margin-left: 30px;
    }
    </style>

    <?php $this->insert('partials/mini-responses', [
      'responses' => $responses,
      'test' => $num,
    ]); ?>    

  </div>
  <div class="post-footer">
    <p>Responses are stored for 48 hours and may be deleted after that time.</p>
  </div>
</div>
