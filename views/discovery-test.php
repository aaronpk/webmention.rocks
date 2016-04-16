<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => $test['link_tag']
                    ]); ?>

<div class="post-container h-entry">
  <div class="post-main <?= $num_responses > 0 ? 'has-responses' : '' ?>">
    <div class="left p-author h-card">
      <a href="/">
        <img src="/assets/webmention-rocks-icon.png" width="80" class="u-photo" alt="Webmention Rocks!">
      </a>
    </div>
    <div class="right">
      <h1 class="p-name"><a href="/test/<?= $num ?>">Discovery Test #<?= $num ?></a></h1>
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
  <div class="post-responses">
    <div id="debug"></div>

    <?php $this->insert('partials/full-responses', [
      'responses' => $responses,
    ]); ?>    

  </div>
  <div class="post-footer">
    <p>Responses are stored for 48 hours and may be deleted after that time.</p>
  </div>
</div>

<div id="test-num" data-num="<?= $num ?>"></div>
<script src="/assets/streaming.js"></script>
