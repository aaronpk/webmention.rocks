<ul>
<?php foreach($responses as $response): ?>
  <li>
    <a href="<?= $response->source ?>" rel="nofollow"><?= $response->source ?></a><br>
    <code>A: [<?= Rocks\Redis::hasSourcePassedPart($response->id, $test, 1) ? 'X' : ' ' ?>] 
    B: [<?= Rocks\Redis::hasSourcePassedPart($response->id, $test, 2) ? 'X' : ' ' ?>] 
    C: [<?= Rocks\Redis::hasSourcePassedPart($response->id, $test, 3) ? 'X' : ' ' ?>]</code>
  </li>
<?php endforeach; ?>
</ul>
