<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="post-container h-entry">

  <div class="post-main" style="margin-bottom: 12px;">
    <a href="<?= $target ?>"><?= $target ?></a>
  </div>

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
  <div class="post-footer">
    <p>This post will only exist for 48 hours.</p>
  </div>
</div>

<?php if(is_logged_in()): ?>
<div class="single-column">
  
</div>
<?php endif; ?>

<div id="test-num" data-num="<?= $num ?>"></div>
