<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => $test['link_tag']
                    ]); ?>

<div class="post-container h-entry">
  <div class="post-main <?= count($responses) ? 'has-responses' : '' ?>">
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
      <li>One</li>
      <li>Two</li>
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
  display: -webkit-flex;
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

.post-container .post-responses ul {
  margin: 0;
  padding: 0;
  list-style-type: none;
}


.post-container .left {
  flex: 0;
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

</style>
