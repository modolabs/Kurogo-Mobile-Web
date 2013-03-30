{extends file="findExtends:common/templates/search.tpl"}

{block name="form"}
  {if !$insideForm}
    <div class="nonfocal" id="searchformcontainer">
      <form method="get" action="{$searchAction}">
  {/if}
      {if $placeholder}
        <h3>{$placeholder}:</h3>
      {/if}
      <table cellpadding="0" cellspacing="0" border="0" style="width:100%">
        <tr>
          <td width="90%">
            <input class="forminput" type="text" id="{$inputName|default:'filter'}" name="{$inputName|default:'filter'}" value="{$searchTerms|escape}" />
          </td>
          <td width="10%" align="right">
            <input class="imagebutton" name="search" id="sch_btn" value="Search" src="/common/images/search_button.png" type="image"/>
          </td>
        </tr>
        {if isset($additionalInputs)}
          <tr>
            <td colspan="2">
              {$additionalInputs}
            </td>
          </tr>
        {/if}
      </table>
      {$hiddenArgHTML}
      {$tipHTML}
      {$inlineErrorHTML}
      
  {if !$insideForm}
      </form>
    </div>
  {/if}
{/block}
