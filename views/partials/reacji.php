          <li data-emoji="<?= $emoji ?>">
            <span class="emojichar"><?= $emoji ?></span>
            <span class="count"><?= count($reacjis) ?></span>
            <div class="ui special popup">
              <ul class="reacji-links">
                <?php foreach($reacjis as $reacji): ?>
                  <li><a href="<?= $reacji->href ?>" rel="nofollow"><?= $reacji->href ?></a>
                <?php endforeach; ?>
              </ul>
            </div>
          </li>
