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
      <h3 class="p-name"><a href="/test/<?= $num ?>">Test #<?= $num ?></a></h3>
      <div class="e-content"><?= $test['description'] ?></div>
      <div class="meta">
        <a href="/test/<?= $num ?>" class="u-url">
          <time class="dt-published" datetime="<?= $date->format('c') ?>">
            <?= $date->format('l F j, Y g:ia P') ?>
          </time>
        </a>
      </div>
    </div>
  </div>
  <div class="post-responses">
    <div id="debug"></div>

    <?php foreach(Rocks\Response::facepileTypes() as $type): ?>
      <div class="responses-row <?= $type ?> <?= count($responses[$type]) ? '' : 'empty' ?>">
        <div class="facepile-type-icon"><i class="ui <?= Rocks\Response::facepileTypeIcon($type) ?> icon"></i></div>
        <ul class="facepile stream <?= $type ?>">
          <?php if($responses[$type]): ?>
            <?php foreach($responses[$type] as $res): ?>
              <?php $this->insert('partials/facepile-icon', ['res'=>$res, 'type'=>$type]); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
        <div style="clear:both;"></div>
      </div>
    <?php endforeach; ?>

    <div class="responses-row reply <?= count($responses['reply']) ? '' : 'empty' ?>">
      <div style="padding: 12px 12px 3px 12px;">
        <h3>Comments</h3>
        <p style="font-size: 0.6em; color: #666;">The mentions below are replies to this post, and marked up their link to the post with the <code><a href="http://indiewebcamp.com/in-reply-to">in-reply-to</a></code> property.</p>
      </div>
      <ul class="comments stream reply">
        <?php foreach($responses['reply'] as $comment): ?>
          <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'reply']); ?>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="responses-row mention <?= count($responses['mention']) ? '' : 'empty' ?>">
      <div style="padding: 12px 12px 3px 12px;">
        <h3>Other Mentions</h3>
        <p style="font-size: 0.6em; color: #666;">The mentions below linked to this post, but did not include this post's URL as an <code><a href="http://indiewebcamp.com/in-reply-to">in-reply-to</a></code> property.</p>
      </div>
      <ul class="comments stream mention">
        <?php foreach($responses['mention'] as $comment): ?>
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
