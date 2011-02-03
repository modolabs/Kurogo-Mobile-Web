{include file="findInclude:common/header.tpl"}

<div class="focal">
  <h2>{$title}</h2>
  <p class="smallprint">{$date|date_format:"%l:%M %p, %a %b %e, %Y"}</p>
  {$content}
</div>

{if $hasContacts}
  <div>
    {include file="findInclude:common/navlist.tpl"
       navlistItems=$contactNavListItems
       accessKey=false
       nested=true
       subtitleNewLine=false}
  </div>
{/if}
{include file="findInclude:common/footer.tpl"}
