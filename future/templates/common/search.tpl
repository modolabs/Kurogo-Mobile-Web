{strip}

{capture name="hiddenArgHTML" assign="hiddenArgHTML"}
  {if (!isset($searchPage) && ($page == 'search')) || ($page == $searchPage)}
    {$hiddenArgs = $breadcrumbSamePageArgs}
  {else}
    {$hiddenArgs = $breadcrumbArgs}
  {/if}
  
  {if isset($extraArgs)}
    {$hiddenArgs = array_merge($hiddenArgs, $extraArgs)}
  {/if}
  {foreach $hiddenArgs as $arg => $value}
    <input type="hidden" name="{$arg}" value="{$value}" />
  {/foreach}
{/capture}

{capture name="inlineErrorHTML" assign="inlineErrorHTML"}
  {if $inlineSearchError}
    <p>{$inlineSearchError}</p>
  {elseif isset($resultCount)}
    {if $resultCount == 0}
      <p>No matches found</p>
    {else}
      <p>{$resultCount} match{if $resultCount != 1}es{/if} found</p>
    {/if}
  {/if}
{/capture}

{capture name="tipHTML" assign="tipHTML"}
  {if isset($tip)}
    <p class="legend nonfocal">
      <strong>Search tip:</strong> {$tip}
    </p>
  {/if}
{/capture}

{block name="form"}
  {if !$insideForm}
    <div class="nonfocal">
      <form method="get" action="{$searchPage|default:'search'}.php">
  {/if}
  
        <fieldset class="inputcombo{if $emphasized|default:$isModuleHome} emphasized{/if}">
          <input class="forminput" type="text" id="{$inputName|default:'filter'}" name="{$inputName|default:'filter'}" placeholder="{$placeholder|default:''}" value="{$searchTerms|escape}" />
          <input class="combobutton" id="sch_btn" src="/common/images/search-button.png" type="image" />
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
  

{/strip}
