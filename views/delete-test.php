<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => (isset($test['link_tag']) ? $test['link_tag'] : ''),
                    ]); ?>

<div class="post-container h-entry">
  <div class="post-main <?= $num_responses > 0 ? 'has-responses' : '' ?>">
    <div class="left p-author h-card">
      <a href="/">
        <img src="/assets/webmention-rocks-icon.png" width="80" class="u-photo" alt="Webmention Rocks!">
      </a>
    </div>
    <div class="right">
      <h1 class="p-name"><a href="/delete/<?= $num ?>">Delete Test #<?= $num ?></a></h1>
      <div class="e-content"><?= $test['description'] ?></div>
      <div class="meta">
        <a href="/delete/<?= $num ?>" class="u-url">
          Published:
          <time class="dt-published" datetime="<?= $published->format('c') ?>">
            <?= $published->format('l F j, Y g:ia P') ?>
          </time>
        </a>
      </div>
    </div>
  </div>
  <div class="post-responses">

    <div class="responses-row <?= count($in_progress) ? '' : 'empty' ?>" style="background-color: #fffaeb; padding-bottom: 4px;">
      <div style="padding: 12px 12px 3px 12px;">
        <h3 style="margin: 0;">In Progress</h3>
        <p class="help-text">The mentions below are in progress, and have not yet completed the test. They will be deleted if they are not completed within 10 minutes of first posting.</p>
      </div>
      <ul class="comments stream mention">
        <?php foreach($in_progress as $comment): ?>
          <?php $this->insert('partials/in-progress-comment', ['comment'=>$comment, 'group'=>'delete', 'type'=>'mention', 'test'=>$num, 'checkboxes' => $test['checkboxes']]); ?>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="responses-row <?= count($responses) ? '' : 'empty' ?>" style="background-color: #dfffe1;">
      <div style="padding: 12px 12px 3px 12px;">
        <h3 style="margin: 0;">Successful Tests</h3>
        <p class="help-text">The mentions below have successfully passed the test! If you visit any of the links below, you should see an indication that the post was deleted.</p>
      </div>
      <ul class="comments stream mention">
        <?php foreach($responses as $comment): ?>
          <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'mention']); ?>
        <?php endforeach; ?>
      </ul>
    </div>

  </div>
  <div class="post-footer">
    <p>Responses are stored for 48 hours and may be deleted after that time.</p>
  </div>
</div>

<div id="test-num" data-num="<?= $num ?>"></div>
<script src="/assets/streaming.js"></script>
