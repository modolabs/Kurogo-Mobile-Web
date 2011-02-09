{include file="findInclude:common/header.tpl"}

{block name="imageDetails"}
  {$imageExt = '.jpg'}
{/block}

{block name="customize"}
  <div class="focal"> 
    <p>Use the up and down links to customize the order of icons on your homepage. Use the checkboxes to toggle visibility. Changes are automatically saved, but <strong>you may need to manually refresh your homepage</strong> after you're finished here.</p>
  
    <table cellpadding="2" cellspacing="0" border="0" width="100%" id="homepageList">
      {foreach $modules as $id => $info}
        <tr>
          <td>
            {if $info['disableable']}
              <a href="{$info['toggleDisabledURL']}">
                {if $info['disabled']}
                  <img src="/common/images/check-off{$imageExt}" border="0" alt="On">
                {else}
                  <img src="/common/images/check-on{$imageExt}" border="0" alt="Off">
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
      <a href="../home/">Return to home</a>
    </p>
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}
