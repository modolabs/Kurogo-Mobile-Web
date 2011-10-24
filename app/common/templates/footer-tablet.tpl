{extends file="findExtends:common/templates/footer.tpl"}

{block name="containerEnd"}
        </div> <!-- containerinset -->
      </div> <!-- container -->
    </div> <!-- wrapper -->
  </div> <!--nonfooternav -->
{/block}

{block name="footer"}
  {if $moduleID != 'home'}
    <div id="footer">
      {$footerHTML}
    </div>
  {/if}
{/block}

{block name="footerNavLinks"}
{/block}

{block name="deviceDetection"}
{/block}

{block name="loginHTML"}
{/block}

{block name="belowContent"}
  <div id="footernav">
    <div id="navsliderwrapper">
      <div id="navslider">
        {foreach $moduleNavList as $item}
          {if !$item['separator']}
            <div class="module{if $item['class']} {$item['class']}{/if}">
              <a href="{$item['url']}">
                <img src="{$item['img']}" alt="{$item['title']}" />
                <div>{$item['shortTitle']}</div>
                {if isset($item['badge'])}
                  <span class="badge">{$item['badge']}</span>
                {/if}
              </a>
            </div>
          {/if}
        {/foreach}
      </div>
    </div>
    <div id="slideleft" style="display:none" onclick="navSliderScrollLeft()"></div>
    <div id="slideright" style="display:none" onclick="navSliderScrollRight()"></div>
  </div>
{/block}
