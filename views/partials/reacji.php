          <li data-emoji="<?= $emoji ?>">
            <a <?= count($reacjis) == 1 ? 'href="'.$reacjis[0]->href.'"' : '' ?> class="emojichar"><?= $emoji ?></a>
            <span class="count"><?= count($reacjis) ?></span>
            <?php foreach($reacjis as $reacji): ?>
            <?php endforeach; ?>
          </li>
