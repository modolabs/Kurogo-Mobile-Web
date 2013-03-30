{if $navigationModules}
  {if $navmenuID}<div id="navmenu">{/if}
    <ul class="navmenu-items">
      {if $configModule == $homeModuleID && $showFederatedSearch}
        <li class="navmenu-item navmenu-search">{include file="findInclude:common/templates/search.tpl"}</li>
      {/if}
      {foreach $navigationModules as $item}
        {if !$item['separator']}
          <li class="navmenu-item navmenu-module{if $item['class']} {$item['class']}{/if}">
            <a href="{$item['url']}"{if $item['linkTarget']} target="{$item['linkTarget']}"{/if}>
              <img class="navmenu-icon" src="{$item['img']}" alt="{$item['title']}" width="30" height="30" /> <span>{$item['shortTitle']}</span>
              {if isset($item['badge'])}
                <span class="badge">{$item['badge']}</span>
              {/if}
            </a>
          </li>
        {/if}
      {/foreach}
      <li class="navmenu-item navmenu-footer">
        <div id="footer">
          {if $userContextList}
          {include file="findInclude:common/templates/page/navigation/userContextList.tpl" navContainerID="navmenu"}
          {/if}
          {include file="findInclude:common/templates/page/credits.tpl"}
          {include file="findInclude:common/templates/page/deviceDetection.tpl"}
          {include file="findInclude:common/templates/page/moduleDebug.tpl"}
        </div>
      </li>
    </ul>
  {if $navmenuID}</div>{/if}
{/if}
