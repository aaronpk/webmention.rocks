<?php $this->layout('layout', [
                      'title' => $title,
                      'link_tag' => $link_tag
                    ]); ?>

<h2>Test #<?= $num ?></h2>

<?php if($a_tag): ?>
  <?= $a_tag ?>
<?php endif; ?>

