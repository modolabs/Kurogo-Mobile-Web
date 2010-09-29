{strip}
{if !$insideForm}
  <form method="get" action="{$searchURL|default:'search.php'}">
    <p id="search">
{/if}
      {if isset($placeholder) && strlen($placeholder)}
        {if $emphasized|default:$moduleHome}
          <strong>
        {/if}
            {$placeholder}:
        {if $emphasized|default:$moduleHome}
          </strong>
        {/if}
        <br/>
      {/if}
      <span class="inputcombo{if $emphasized|default:$moduleHome} emphasized{/if}">
        <input class="forminput" type="search" id="filter" name="{$inputName|default:'filter'}" value="{$searchTerms|escape}" />
        <input class="combobutton" name="search" id="sch_btn" type="submit" value="Search" />
        {$hiddenArgs = $breadcrumbArgs}
        {if isset($extraArgs)}
          {$hiddenArgs = array_merge($hiddenArgs, $extraArgs)}
        {/if}
        {foreach $hiddenArgs as $arg => $value}
          <input type="hidden" name="{$arg}" value="{$value}" />
        {/foreach}
      </span>	
{if !$insideForm}
    </p>
  </form>
{/if}
{if $inlineSearchError}
  <p>{$inlineSearchError}</p>
{elseif isset($resultCount)}
  {if $resultCount == 0}
    <p>No matches found</p>
  {else}
    <p>{$resultCount} match{if $resultCount != 1}es{/if} found</p>
  {/if}
{/if}
{/strip}