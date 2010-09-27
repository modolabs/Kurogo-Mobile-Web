<div class="nonfocal">
  <form method="get" action="{$searchURL|default:'search.php'}">
    <fieldset class="inputcombo{if $emphasized|default:$moduleHome} emphasized{/if}">
      <input class="forminput" type="text" id="filter" name="{$inputName|default:'filter'}" placeholder="{$placeholder|default:''}" value="{$searchTerms|default:''}" />
      <input class="combobutton" id="sch_btn" src="/common/images/search-button.png" type="image" />
      {$breadcrumbInputs|default: ''}
    </fieldset>	
    {if $inlineSearchError}
      <p>{$inlineSearchError}</p>
    {elseif isset($resultCount)}
      {if $resultCount == 0}
        <p>No matches found</p>
      {else}
        <p>{$resultCount} match{if $resultCount != 1}es{/if} found</p>
      {/if}
    {/if}
  </form>
</div>
