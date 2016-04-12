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
    <?php foreach(Rocks\Response::facepileTypes() as $type): ?>
      <?php if($responses[$type]): ?>
        <ul class="facepile">
          <li class="icon"><i class="ui <?= Rocks\Response::facepileTypeIcon($type) ?> icon"></i></li>
          <?php foreach($responses[$type] as $res): ?>
            <?php $this->insert('partials/facepile-icon', ['res'=>$res, 'type'=>$type]); ?>
          <?php endforeach; ?>
        </ul>
        <div style="clear:both;"></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <ul class="comments">
      <?php foreach($responses['reply'] as $comment): ?>
        <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'reply']); ?>
      <?php endforeach; ?>
    </ul>

    <?php if(count($responses['mention'])): ?>
    <div style="border-top: 1px #fbf6bd solid;">
      <div style="padding: 12px 12px 3px 12px;">
        <h3>Other Mentions</h3>
        <p style="font-size: 0.6em; color: #666;">The mentions below linked to this post, but did not include this post's URL as an <code>in-reply-to</code> property.</p>
      </div>
      <ul class="comments">
        <?php foreach($responses['mention'] as $comment): ?>
          <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'mention']); ?>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
  <div class="post-footer">
    <p>Responses are stored for 48 hours and may be deleted after that time.</p>
  </div>
</div>


<style type="text/css">

.post-container {
  max-width: 600px;
  margin: 20px auto;

  font-size: 14pt;
  line-height: 17pt;
}
@media(max-width: 616px) {
  .post-container {
    margin-left: 8px;
    margin-right: 8px;
  }
}
.post-container .post-main {
  display: flex;
  flex-direction: row;  

  border-radius: 8px;
  border: 1px #fbf6bd solid;

  background: #fff;
  padding: 12px;
}
.post-container .post-main .meta {
  font-size: 10pt;
}
.post-container .post-main .meta a {
  color: #777;
}
.post-container .post-main .meta a:hover {
  color: #999;
}

@media(max-width: 400px) {
  .post-container .post-main {
    display: block;
  }
  .post-container .left {
    float: left;
  }
}

.post-container .post-main.has-responses {
  border-bottom-right-radius: 0;
  border-bottom-left-radius: 0;
  border-bottom: 0;
}

.post-container .post-responses {
  background: #fffef1;
  border-bottom-right-radius: 8px;
  border-bottom-left-radius: 8px;
  border: 1px #fbf6bd solid;
  border-top: 0;
}

.post-container .left {
  flex: 0 auto; /* this needs to be "0 auto" for Safari, but works as "0" for everything else */
  padding-right: 12px;
}
.post-container .right {
  flex: 1;
}
.post-container img {
  background: white;
  border: 1px #fbf6bd solid;
  border-radius: 6px;
}
.post-container h3 {
  margin: 0;
  font-size: 16pt;
  font-weight: bold;
}

/* facepile */

.post-container .post-responses > ul.facepile {
  margin: 0;
  padding: 0;
  padding-top: 6px;
  list-style-type: none;
}

.post-responses > ul.facepile > li {
  float: left;
}

.post-responses > ul.facepile > li .icon {
  font-size: 2em;
  padding-top: 14px;
}

/* comments */

.post-container .post-responses ul.comments {
  margin: 0;
  padding: 0;
  list-style-type: none;
}
.post-responses ul.comments > li {
  padding: 0;
  margin: 0;
  padding-top: 6px;
  padding-right: 12px;
  border-top: 1px #fbf6bd solid;
}
.post-responses ul.comments > li .comment {
  margin-left: 66px;
  margin-bottom: 6px;
}
.post-responses ul.comments > li .comment .author img {
  margin-left: -54px;
  float: left;
}
.post-responses ul.comments > li .comment .author {
  font-size: 0.8em;
  margin-bottom: 6px;
}
.post-responses ul.comments > li .comment .author-url {
  color: #888;
  font-weight: normal;
}
.post-responses ul.comments > li .comment .comment-content.plaintext {
  white-space: pre-line;
}
.post-responses ul.comments > li .comment .comment-content .missing {
  color: #888;
}
.post-responses ul.comments > li .comment .meta {
  color: #777;
  margin-top: 8px;
  font-size: 0.65em;
  line-height: 1.1em;
}
.post-responses ul.comments > li .comment .meta a {
  color: #777;
}
.post-responses ul.comments > li .comment .meta a:hover, .post-responses ul.comments > li .comment .author a:hover {
  text-decoration: underline;
}
.post-responses ul.comments > li .comment blockquote {
  border-left: 4px #bbb solid;
  margin-left: 0;
  padding-left: 12px;
}

.post-footer {
  padding-top: 20px;
  font-size: 0.7em;
  color: #777;
  text-align: center;
}

</style>
