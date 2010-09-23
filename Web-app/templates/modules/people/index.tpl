{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" placeholder="Search" searchURL="index.php"}

<div class="legend nonfocal">
  <strong>Search tips:</strong> You can search by part or all of a person's name, email address or phone number.
</div>

{if isset($person)}
  {include file="findInclude:modules/{$moduleID}/detail.tpl" person=$person}
  

{elseif $resultCount|default:0}
  <p>{$resultCount} match{if $resultCount > 1}es{/if} found</p>
  {include file="findInclude:common/results.tpl" results=$searchResults}
  {include file="findInclude:common/search.tpl" placeholder="Search" searchURL="index.php"}
  
{else}
  {if isset($searchError)}
    <p>{$searchError}</p>
  {/if}
  {include file="findInclude:common/navlist.tpl" navlistItems=$peopleContacts secondary=true accessKey=false}
{/if}

{include file="findInclude:common/footer.tpl"}

