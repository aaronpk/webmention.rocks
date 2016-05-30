<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <section class="content">
    <h2><?= $error ?></h2>

    <?php if($description): ?>
      <p><?= $this->e($description) ?></p>
    <?php endif; ?>

    <p><a href="/receive/<?= $num ?>">Return to Test #<?= $num ?></a></p>

  </section>
</div>
