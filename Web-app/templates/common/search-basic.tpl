<form method="get" action="{$searchURL|default:'search.php'}">
  <p id="search">
    {$placeholder|default:'Search'}:<br/>
    <span class="inputcombo{if $emphasized|default:$moduleHome} emphasized{/if}">
      <input class="forminput" type="search" id="filter" name="{$inputName|default:'filter'}" value="{$searchTerms|default:''}" />
      <input class="combobutton" name="search" id="sch_btn" type="submit" value="Search" />
      {$breadcrumbInputs|default: ''}
    </span>	
  </p>
</form>
{if $inlineSearchError}
  <p>{$inlineSearchError}</p>
{elseif isset($resultCount)}
  {if $resultCount == 0}
    <p>No matches found</p>
  {else}
    <p>{$resultCount} match{if $resultCount != 1}es{/if} found</p>
  {/if}
{/if}
