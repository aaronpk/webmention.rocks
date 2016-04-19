<?php $this->layout('layout', ['title' => $title]); ?>

<div class="post-container h-entry">
  <div class="post-main <?= $num_responses > 0 ? 'has-responses' : '' ?>">
    <div class="left p-author h-card">
      <a href="/">
        <img src="/assets/webmention-rocks-icon.png" width="80" class="u-photo" alt="Webmention Rocks!">
      </a>
    </div>
    <div class="right">
      <h1 class="p-name">Discovery Test Results</a></h1>
    </div>
  </div>
  <div class="post-responses">
    <div id="debug"></div>

    <ul class="comments stream reply">
      <?php foreach($responses as $comment): ?>
        <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'mention']); ?>
      <?php endforeach; ?>
    </ul>

  </div>
  <div class="post-footer">
    <p>Responses are stored for 48 hours and may be deleted after that time.</p>
  </div>
</div>