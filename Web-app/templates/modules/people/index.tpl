{if isset($personDetails)}
  {include file="findInclude:modules/{$moduleID}/detail.tpl"}

{elseif $resultCount|default:0 > 0}
  {include file="findInclude:modules/{$moduleID}/search.tpl"}
  
{else}
  {include file="findInclude:common/header.tpl"}

  {include file="findInclude:common/search.tpl" placeholder="Search" resultCount=$resultCount}

  <div class="legend nonfocal">
    <strong>Search tips:</strong> You can search by part or all of a person's name, email address or phone number.
  </div>
  
  {include file="findInclude:common/navlist.tpl" navlistItems=$peopleContacts secondary=true accessKey=false}

  {include file="findInclude:common/footer.tpl"}
{/if}

