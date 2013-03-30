{extends file="findExtends:common/templates/paneStories.tpl"}

      {block name="paneStory"}
        <a href="{$story['url']}" class="pane-story{if $story@first} current{/if}{if $story['class']} {$story['class']}{/if}">
          <div class="pane-story-caption">
            <div class="ellipsis">
              <h2 class="title">
	              {$story["title"]}
              </h2>
              <div class="smallprint">
                <div class="source-info">
                  {if $story['img']}<img src="{$story['img']}" alt="" />{/if}
                  {if $story['author'] || $story['created']}
                    <div class="author-created">
                      {if $story['author']}<span class="author">{$story['author']}</span>{/if}
                      {if $story['created']}<span class="post-date">{$story['created']}</span>{/if}
                    </div>
                  {/if}
                </div>
              </div>
            </div>
          </div>
        </a>
      {/block}
