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
          <td width="{block name='inputWidth'}99%{/block}">
            <input class="forminput" type="text" id="{$inputName|default:'filter'}" name="{$inputName|default:'filter'}" value="{$searchTerms|escape}" />
          </td>
          <td width="{block name='submitWidth'}1%{/block}" align="right">
            {block name="formSubmit"}
              <input type="submit" value="Search" class="submitbutton" />
            {/block}
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
