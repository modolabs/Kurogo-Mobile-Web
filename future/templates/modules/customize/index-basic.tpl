{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


<div class="focal"> 
  <p>Use the up and down links to customize the order of icons on your homepage. Use the checkboxes to toggle visibility. Changes are automatically saved, but <strong>you may need to manually refresh your homepage</strong> after you're finished here.</p>

  <table cellpadding="2" cellspacing="0" border="0" width="100%" id="homepageList">
    {foreach $modules as $id => $info}
      <tr>
        <td></td>
        <td>
          {if $info['disableable']}
            <a href="{$info['toggleDisabledURL']}">
              {if $info['visible']}
                <img src="/modules/{$moduleID}/images/check-on.gif" border="0" alt="Off">
              {else}
                <img src="/modules/{$moduleID}/images/check-off.gif" border="0" alt="On">
              {/if}              
            </a>
          {/if}
        </td>
        <td>{$info['title']}</td>
        <td>
          {if isset($info['swapUpURL']) && $info['movable']}
            <a href="{$info['swapUpURL']}">
              <img src="/modules/{$moduleID}/images/up.gif" border="0" alt="Up">
            </a>
          {/if}
        </td>
        <td>
          {if isset($info['swapDownURL']) && $info['movable']}
            <a href="{$info['swapDownURL']}">
              <img src="/modules/{$moduleID}/images/down.gif" border="0" alt="Down">
            </a>
          {/if}
        </td>
      </tr>
    {/foreach}
  </table>
</div>

{include file="findInclude:common/footer.tpl"}
