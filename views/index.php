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
      to help you test your <a href="https://www.w3.org/TR/webmention/#sender-discovers-receiver-webmention-endpoint">Webmention endpoint discovery</a> implementation.</p>
    <p>You should be able to write a post that links to each post below, and have your
      comment show up on each of them.</p>
    <p>In your comment, please describe what software and/or libraries you are using to send Webmentions. (name, link)</p>

    <ul>
      <?php foreach($discoveryTestData as $i=>$data): ?>
        <li>
          <a href="/test/<?= $i ?>">Discovery Test #<?= $i ?></a>
          -
          <?= htmlspecialchars($data['name']) ?>
        </li>
      <?php endforeach; ?>
    </ul>

    <p>If you want a quick bit of text to copy+paste into a post, you can use the HTML or plaintext below, which link to all the tests.</p>

    <h4>HTML</h4>
    <textarea style="width: 100%;" rows="4"><?php 
      foreach($discoveryTestData as $i=>$data):
        echo '<a href="' . Config::$base . 'test/' . $i . '">Test ' . $i . '</a>'."\n";
      endforeach;
    ?></textarea>

    <h4>Text</h4>
    <textarea style="width: 100%;" rows="4"><?php 
      foreach($discoveryTestData as $i=>$data):
        echo Config::$base . 'test/' . $i . "\n";
      endforeach;
    ?></textarea>
  </section>

  <section class="content">
    <h3>Webmention Updates</h3>

    <p>The tests below will test whether you properly support <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-updated-posts">sending Webmentions for updated posts</a>.</p>

    <ul>
      <?php foreach($updateTestData as $i=>$data): ?>
        <li>
          <a href="/update/<?= $i ?>">Update Test #<?= $i ?></a>
          -
          <?= htmlspecialchars($data['name']) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="content">
    <h3>Webmention Deletes</h3>

    <p>The tests below will test whether you properly support <a href="https://www.w3.org/TR/webmention/#sending-webmentions-for-deleted-posts">sending Webmentions for deleted posts</a>.</p>

    <ul>
      <?php foreach($deleteTestData as $i=>$data): ?>
        <li>
          <a href="/delete/<?= $i ?>">Delete Test #<?= $i ?></a>
          -
          <?= htmlspecialchars($data['name']) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="content">
    <p>This code is <a href="https://github.com/aaronpk/webmention.rocks">open source</a>. Feel free to <a href="https://github.com/aaronpk/webmention.rocks/issues">file an issue</a> if you notice any errors</p>
  </section>

</div>
