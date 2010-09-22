<div class="nonfocal">
  <form method="get" action="{$searchURL|default:'search.php'}">
    <fieldset class="inputcombo emphasized">
      <input class="forminput" type="text" id="filter" name="{$inputName|default:'filter'}" placeholder="{$placeholder|default:''}" value="{$searchTerms|default:''}" />
      <input class="combobutton" id="sch_btn" src="../common/images/search-button.png" type="image" />
    </fieldset>	
    {if $inlineSearchError}
      <p>{$inlineSearchError}</p>
    {/if}
  </form>
</div>
