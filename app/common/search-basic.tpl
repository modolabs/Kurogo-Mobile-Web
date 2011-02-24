{extends file="findExtends:common/search.tpl"}

{block name="form"}
  {if !$insideForm}
    <form method="get" action="{$searchURL|default:'search.php'}">
      <p id="search">
  {/if}
        {if isset($placeholder) && strlen($placeholder)}
          {if $emphasized|default:$isModuleHome}
            <strong>
          {/if}
              {$placeholder}:
          {if $emphasized|default:$isModuleHome}
            </strong>
          {/if}
          <br/>
        {/if}
        <span class="inputcombo{if $emphasized|default:$isModuleHome} emphasized{/if}">
          <input class="forminput" type="search" id="filter" name="{$inputName|default:'filter'}" value="{$searchTerms|escape}" />
          {block name="button"}
            <input class="combobutton" name="search" id="sch_btn" type="submit" value="Search" />
          {/block}
          {if isset($additionalInputs)}
            <br/>
            {$additionalInputs}
          {/if}
          {$hiddenArgHTML}
        </span>	
  {if !$insideForm}
      </p>
  {/if}
      {$tipHTML}
  {if !$insideForm}
    </form>
  {/if}
  {$inlineErrorHTML}
{/block}
