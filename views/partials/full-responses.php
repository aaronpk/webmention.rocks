    <div class="responses-row reacji <?= count($responses['reacji']) ? '' : 'empty' ?>">
      <div style="padding: 12px 12px 3px 12px;">
        <h3 style="margin: 0;">Reacji</h3>
        <p class="help-text">The emoji below show <a href="http://indiewebcamp.com/reacji">Reacji</a> responses, created by people posting a comment linking to this post with an <code><a href="http://indiewebcamp.com/in-reply-to">in-reply-to</a></code> property, whose text is a single emoji character.</p>
      </div>
      <ul class="reacji stream reacji">
        <?php foreach($responses['reacji'] as $emoji=>$reacjis): ?>
          <?php $this->insert('partials/reacji', ['emoji'=>$emoji, 'reacjis'=>$reacjis]); ?>
        <?php endforeach; ?>
      </ul>
      <div style="clear:both;"></div>
    </div>

    <?php foreach(Rocks\Response::facepileTypes() as $type): ?>
      <div class="responses-row <?= $type ?> <?= count($responses[$type]) ? '' : 'empty' ?>">
        <div style="padding: 12px 12px 3px 12px;">
          <h3 style="margin: 0;"><?= ucfirst($type) ?>s</h3>
          <p class="help-text">
            <?php 
              switch($type) {
                case 'like':
                  ?>The profile icons below are the <a href="http://indiewebcamp.com/authorship">author</a> photos from people who have posted a "like" post on their site, where their post links to this post with the <code><a href="http://indiewebcamp.com/like-of">like-of</a></code> property.</p><?php
                  break;
                case 'repost':
                  ?>The profile icons below are the <a href="http://indiewebcamp.com/authorship">author</a> photos from people who have posted a "repost" of this post on their site, where their post links to this post with the <code><a href="http://indiewebcamp.com/repost-of">repost-of</a></code> property.</p><?php
                  break;
                case 'bookmark':
                  ?>The profile icons below are the <a href="http://indiewebcamp.com/authorship">author</a> photos from people who have posted a bookmark of this post on their site, where their post links to this post with the <code><a href="http://indiewebcamp.com/bookmark-of">bookmark-of</a></code> property.</p><?php
                  break;
              }
            ?>
          </p>
        </div>
        <div class="facepile-type-icon"><i class="ui <?= Rocks\Response::facepileTypeIcon($type) ?> icon"></i></div>
        <ul class="facepile stream <?= $type ?>">
          <?php if($responses[$type]): ?>
            <?php foreach($responses[$type] as $res): ?>
              <?php $this->insert('partials/facepile-icon', ['res'=>$res, 'type'=>$type]); ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
        <div style="clear:both;"></div>
      </div>
    <?php endforeach; ?>

    <div class="responses-row reply <?= count($responses['reply']) ? '' : 'empty' ?>">
      <div style="padding: 12px 12px 3px 12px;">
        <h3 style="margin: 0;">Comments</h3>
        <p class="help-text">The comments below are replies to this post, and marked up their link to the post with the <code><a href="http://indiewebcamp.com/in-reply-to">in-reply-to</a></code> property.</p>
      </div>
      <ul class="comments stream reply">
        <?php foreach($responses['reply'] as $comment): ?>
          <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'reply']); ?>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="responses-row mention <?= count($responses['mention']) ? '' : 'empty' ?>">
      <div style="padding: 12px 12px 3px 12px;">
        <h3 style="margin: 0;">Mentions</h3>
        <p class="help-text">The mentions below linked to this post, but did not include this post's URL as an <code><a href="http://indiewebcamp.com/in-reply-to">in-reply-to</a></code> property.</p>
      </div>
      <ul class="comments stream mention">
        <?php foreach($responses['mention'] as $comment): ?>
          <?php $this->insert('partials/comment', ['comment'=>$comment, 'type'=>'mention']); ?>
        <?php endforeach; ?>
      </ul>
    </div>