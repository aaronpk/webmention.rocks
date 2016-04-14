          <li class="p-<?= $type ?> h-cite" data-response-id="<?= $res->hash() ?>">
            <a href="<?= $res->href ?>" class="u-url" rel="nofollow">
              <span class="p-author h-card author">
                <img class="u-photo" src="<?= $res->author_photo ?: '/assets/no-photo.png' ?>" 
                  width="48" alt="<?= $res->author_name ?>">
              </span>
            </a>
          </li>
