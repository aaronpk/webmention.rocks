<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div class="single-column">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <div class="ui error message">
    <div class="header"><?= $this->e($error) ?></div>
    <p><?= $this->e($error_description) ?></p>
    <?php if(isset($_SESSION) && array_key_exists('return-to', $_SESSION)): ?>
      <a href="<?= $_SESSION['return-to'] ?>">Start Over</a>
    <?php endif; ?>
  </div>

  <?php 
    if(isset($include)) {
      $this->insert($include);
    }
  ?>

</div>
