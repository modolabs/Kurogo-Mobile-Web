{include file="findInclude:common/header.tpl"}

<div class="focal">
  <h2>{$title}</h2>
  <p class="smallprint">{$date|date_format:"%a %b %e, %Y"}</p>
  {$content}
</div>

{include file="findInclude:common/footer.tpl"}
