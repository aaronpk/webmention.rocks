<?php $this->layout('layout', ['title' => $title]); ?>

<div style="margin: 0 auto; max-width: 600px;">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <section class="content">
    <h3>About this site</h3>
    <p><b><i>Webmention Rocks!</i></b> is a validator to help you test your Webmention
      implementation. Several kinds of tests are available on the site.</p>
  </section>

  <section class="content">
    <h3>Webmention Endpoint Discovery</h3>
    <p>The test posts below advertise their Webmention endpoints in a variety of ways, 
      to help you test your endpoint discovery code.</p>
    <p>You should be able to write a post that links to each post below, and have your
      comment show up on each of them.</p>
    <ul>
      <?php foreach($testData as $i=>$data): ?>
        <li>
          <a href="/test/<?= $i ?>">Test <?= $i ?></a>
          -
          <?= htmlspecialchars($data['name']) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="content">
    <p>This code is <a href="https://github.com/aaronpk/webmention.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/webmention.rocks/issues">file an issue</a> if you notice any errors</p>
  </section>

  <?php if(Config::$relMeEmail): ?>
    <a href="mailto:<?= Config::$relMeEmail ?>" rel="me"></a>
  <?php endif; ?>

</div>
