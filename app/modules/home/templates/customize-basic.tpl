{include file="findInclude:common/templates/header.tpl"}

{if $allowCustomize}
  <div class="focal"> 
    {if $customizeUserContextList}
    {block name="userContextList"}
    <div id="customizeUserContextList" class="userContextList">
    <p>{$customizeUserContextListDescription}</p>
    <ul>
    {foreach $customizeUserContextList as $contextItem name="userContextList"}
    <li{if $contextItem.active} class="contextActive"{/if}><a href="{$contextItem.url}">{$contextItem.title}</a>{if $contextItem.active} *{/if}</li>
    {/foreach}
    </ul>
    <p>{$customizeUserContextListDescriptionFooter}</p>
    </div>
    {/block}
    {else}
    <p>{$customizeInstructions}</p>
    {/if}
  
    <table cellpadding="2" cellspacing="0" border="0" width="100%" id="homepageList">
      {foreach $modules as $id => $info}
        <tr>
          <td>
            {if $info['hideable']}
              <a href="{$info['toggleVisibleURL']}">
                {if $info['visible']}
                  <img src="/common/images/check-on{$imageExt}" border="0" alt="Off">
                {else}
                  <img src="/common/images/check-off{$imageExt}" border="0" alt="On">
                {/if}              
              </a>
            {/if}
          </td>
          <td>{$info['title']}</td>
          <td>
            {if isset($info['swapUpURL'])}
              <a href="{$info['swapUpURL']}">
                <img src="/common/images/up{$imageExt}" border="0" alt="Up">
              </a>
            {/if}
          </td>
          <td>
            {if isset($info['swapDownURL'])}
              <a href="{$info['swapDownURL']}">
                <img src="/common/images/down{$imageExt}" border="0" alt="Down">
              </a>
            {/if}
          </td>
        </tr>
      {/foreach}
    </table>
    
    <p class="nonfocal centered">
      <a href="./index">{"CUSTOMIZE_RETURN_HOME"|getLocalizedString}</a><br />
    </p>
  </div>
{else}
<div class="focal">
{"CUSTOMIZE_DISABLED"|getLocalizedString}
</div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
