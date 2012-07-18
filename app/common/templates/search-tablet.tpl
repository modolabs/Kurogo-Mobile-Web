{extends file="findExtends:common/templates/search.tpl"}

{block name="form"}
  {if !$insideForm}
    <div class="nonfocal" id="searchformcontainer">
      <form method="get" action="{$searchAction}">
  {/if}
  
        <fieldset class="inputcombo{if $emphasized|default:$isModuleHome} emphasized{/if}">
          <input class="forminput" type="text" id="{$inputName|default:'filter'}" name="{$inputName|default:'filter'}" placeholder="{$placeholder|default:''}" value="{$searchTerms|escape}" />
          <input class="hiddensubmit" type="submit" value="submit" />
          {$hiddenArgHTML}
        </fieldset>
        {if isset($additionalInputs)}
          <fieldset>
            {$additionalInputs}
          </fieldset>
        {/if}
        {$tipHTML}
        {$inlineErrorHTML}
      
  {if !$insideForm}
      </form>
    </div>
  {/if}
{/block}
