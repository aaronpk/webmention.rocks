<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => $test['link_tag']
                    ]); ?>

<div class="post-container h-entry">
  <div class="post-main <?= count($comments) ? 'has-responses' : '' ?>">
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
    <ul>
      <?php foreach($comments as $comment): ?>
      <li class="p-comment h-cite comment">
        <div class="p-author h-card author">
          <img class="u-photo" src="<?= $comment->author_photo ?: '/assets/no-photo.png' ?>" width="48">
          <?php if($comment->author_url): ?>
            <a class="p-name u-url" href="<?= $comment->author_url ?>">
              <?= $comment->author_name ?: 'No Name' ?>
            </a>
            <a class="author-url" href="<?= $comment->author_url ?>">
              <?= parse_url($comment->author_url, PHP_URL_HOST) ?>
            </a>
          <?php else: ?>
            <span class="p-name"><?= $comment->author_name ?: 'No Name' ?></span>
          <?php endif; ?>
        </div>
        <div class="e-content comment-content"><?= $comment->content ?: '<span class="missing">Comment text not found</span>' ?></div>
        <div class="meta">
          <a class="u-url" href="<?= $comment->url ?: $comment->source ?>">
            <?php if($comment->published): ?>
              <time class="dt-published" datetime="<?= $comment->published->format('c') ?>">
                <?= $comment->published->format('l F j, y g:ia P') ?>
              </time>
            <?php endif; ?>
          </a>
          <?php if($comment->url == null): ?>
            The post did not provide a URL, using source instead
          <?php elseif($comment->url_host != $comment->source_host): ?>
            <a href="<?= $comment->source ?>">via <?= $comment->source_host ?></a>
          <?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
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

/* comments */

.post-container .post-responses ul {
  margin: 0;
  margin-left: 12px;
  padding: 0;
  list-style-type: none;
}
.post-responses li.comment {
  padding: 0;
  margin: 0;
  margin-left: 54px;
  margin-bottom: 6px;
  padding-top: 6px;
}
.post-responses li.comment .author img {
  margin-left: -54px;
  float: left;
}
.post-responses li.comment .author {
  font-size: 0.8em;
  margin-bottom: 6px;
}
.post-responses li.comment .author-url {
  color: #888;
  font-weight: normal;
}
.post-responses li.comment .comment-content {
  white-space: pre-line;
}
.post-responses li.comment .comment-content .missing {
  color: #888;
}
.post-responses li.comment .meta {
  color: #777;
  margin-top: 6px;
  font-size: 0.65em;
}
.post-responses li.comment .meta a {
  color: #777;
}
.post-responses li.comment .meta a:hover, .post-responses li.comment .author a:hover {
  text-decoration: underline;
}

</style>
