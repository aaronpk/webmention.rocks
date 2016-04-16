      <li class="p-<?= $type ?> h-cite" data-response-id="<?= $comment->hash ?>">
        <div class="comment">
          <div class="p-author h-card author">
            <img class="u-photo" src="<?= $comment->author_photo ?: '/assets/no-photo.png' ?>" width="48">
            <?php if($comment->author_url): ?>
              <a class="p-name u-url" href="<?= $comment->author_url ?>" rel="nofollow">
                <?= htmlspecialchars($comment->author_name ?: 'No Name') ?>
              </a>
              <a class="author-url" href="<?= $comment->author_url ?>" rel="nofollow">
                <?= parse_url($comment->author_url, PHP_URL_HOST) ?>
              </a>
            <?php else: ?>
              <span class="p-name"><?= htmlspecialchars($comment->author_name ?: 'No Name') ?></span>
            <?php endif; ?>
          </div>
          <div class="comment-content">
            <?php if($comment->name): ?>
              <a href="<?= $comment->url ?: $comment->source ?>" rel="nofollow">
                <h3 class="p-name"><?= htmlspecialchars($comment->name) ?></h3>
              </a> 
            <?php else: ?>
              <div class="e-content <?= $comment->content_is_html ? '' : 'plaintext' ?>"><?= $comment->content ?: '<span class="missing">Comment text not found</span>' ?></div>
            <?php endif; ?>

            <code style="font-size: 0.93em;">
            A: [<?= Rocks\Redis::hasSourcePassedPart($comment->id, $test, 1) ? 'X' : ' ' ?>] 
            B: [<?= Rocks\Redis::hasSourcePassedPart($comment->id, $test, 2) ? 'X' : ' ' ?>] 
            C: [<?= Rocks\Redis::hasSourcePassedPart($comment->id, $test, 3) ? 'X' : ' ' ?>]
            </code>

          </div>
          <div class="meta">
            <a class="u-url" href="<?= $comment->url ?: $comment->source ?>" rel="nofollow">
              <?php if($comment->published): ?>
                <time class="dt-published" datetime="<?= $comment->published->format('c') ?>">
                  <?= $comment->published->format('l, F j, Y g:ia P') ?>
                </time>
              <?php else: ?>
                <?= $comment->href ?>
              <?php endif; ?>
            </a>
            <?php if($comment->url == null): ?>
              <p>The post did not provide a URL, using source instead</p>
            <?php elseif($comment->url_host != $comment->source_host): ?>
              <a href="<?= $comment->source ?>">via <?= $comment->source_host ?></a>
            <?php endif; ?>
          </div>
        </div>
      </li>
