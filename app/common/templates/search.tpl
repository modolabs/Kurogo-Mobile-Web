{capture name="hiddenArgHTML" assign="hiddenArgHTML"}
  {if (!isset($searchPage) && ($page == 'search')) || ($page == $searchPage)}
    {$hiddenArgs = $breadcrumbSamePageArgs}
  {else}
    {$hiddenArgs = $breadcrumbArgs}
  {/if}
  
  {if isset($extraArgs)}
    {$hiddenArgs = array_merge($extraArgs, $hiddenArgs)}
  {/if}
  {foreach $hiddenArgs as $arg => $value}
    <input type="hidden" name="{$arg}" value="{$value|escape}" />
  {/foreach}
{/capture}

{capture name="inlineErrorHTML" assign="inlineErrorHTML"}
  {if $inlineSearchError}
    <p>{$inlineSearchError}</p>
  {elseif isset($resultCount)}
    {if $resultCount == 0}
      <p>{"NO_MATCHES_FOUND"|getLocalizedString}</p>
    {elseif $resultCount == 1}
      <p>{"ONE_MATCH_FOUND"|getLocalizedString}</p>
    {else}
      <p>{"NUM_MATCHES_FOUND"|getLocalizedString:$resultCount}</p>
    {/if}
  {/if}
{/capture}

{capture name="tipHTML" assign="tipHTML"}
  {if isset($tip)}
    <p class="legend nonfocal">
      <strong>{"SEARCH_TIP_TITLE"|getLocalizedString}</strong> {$tip}
    </p>
  {/if}
{/capture}

{$searchAction = $searchPage|default:"/$configModule/search"}

{block name="form"}
  {if !$insideForm}
    <div class="nonfocal" id="searchformcontainer">
      <form method="get" action="{$searchAction}">
  {/if}
  
        <fieldset class="inputcombo{if $emphasized|default:$isModuleHome} emphasized{/if}">
          <div class="searchwrapper"><input class="forminput" type="text" id="{$inputName|default:'filter'}" name="{$inputName|default:'filter'}" placeholder="{$placeholder|default:''}" value="{$searchTerms|escape}" onfocus="androidPlaceholderFix(this);" /></div>

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
