<?php $this->layout('layout', ['title' => $title]); ?>

<ul>
<?php foreach($testData as $i=>$data): ?>
  <li><a href="/test/<?= $i ?>">Test <?= $i ?></a></li>
<?php endforeach; ?>

</ul>
