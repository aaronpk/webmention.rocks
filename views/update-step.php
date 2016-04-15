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
      <h1 class="p-name"><a href="/test/<?= $num ?>/step/<?= $step ?>">Update Test #<?= $num ?> Step #<?= $step ?></a></h1>
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
</div>
