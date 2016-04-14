<?php $this->layout('layout', ['title' => $title]); ?>

<div style="margin: 0 auto; max-width: 600px;">
  <div id="header-graphic"><img src="/assets/webmention-rocks.png"></div>

  <section class="content">
    <h3>About this site</h3>
    <p><b><i>Webmention Rocks!</i></b> is a validator to help you test your <a href="https://www.w3.org/TR/webmention/">Webmention</a>
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

    <p>If you want a quick bit of text to copy+paste into a post, you can use the HTML or plaintext below, which link to all the tests.</p>

    <h4>HTML</h4>
    <textarea style="width: 100%;" rows="4"><?php 
      foreach($testData as $i=>$data):
        echo '<a href="' . Config::$base . 'test/' . $i . '">Test ' . $i . '</a>'."\n";
      endforeach;
    ?></textarea>

    <h4>Text</h4>
    <textarea style="width: 100%;" rows="4"><?php 
      foreach($testData as $i=>$data):
        echo Config::$base . 'test/' . $i . "\n";
      endforeach;
    ?></textarea>
  </section>

  <section class="content">
    <p>This code is <a href="https://github.com/aaronpk/webmention.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/webmention.rocks/issues">file an issue</a> if you notice any errors</p>
  </section>

</div>
