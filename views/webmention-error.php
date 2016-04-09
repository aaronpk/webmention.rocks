<?php $this->layout('layout', [
                      'title' => $title,
                    ]); ?>

<div style="margin: 0 auto; max-width: 600px;">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <section class="content">
    <h2><?= $error ?></h2>

    <?php if($description): ?>
      <p style="font-size: 14pt; line-height: 17pt; white-space: pre-wrap;"><?= htmlspecialchars($description) ?></p>
    <?php endif; ?>

    <p>You're seeing this message in HTML format because you sent the Webmention from the HTML form. When you send a Webmention automatically from code, this message will be returned in plaintext format.</p>
  </section>
</div>
