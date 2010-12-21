{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="detailsStart"}
<div class="focal">
  <table class="dl" cellpadding="0" cellspacing="0" border="0" style="margin: 0; padding: 0">
    <tbody>
{/block}

      {block name="sectionStart"}{/block}        
    
      {block name="detail"}
        <tr valign="top">
          <td><span class="dllabel">{$item['label']}</span>&nbsp;</td>
          <td>
            <span class="value">
              {if isset($item['url'])}
                <a href="{$item['url']}" class="{$item['class']|default:''}">
              {/if}
                  {$item['title']}
              {if isset($item['url'])}
                </a>
              {/if}
            </span>
          </td>
        </tr>
      {/block}
  
      {block name="sectionEnd"}{/block} 

{block name="detailsEnd"}
    </tbody>
  </table>
</div>
{/block}
