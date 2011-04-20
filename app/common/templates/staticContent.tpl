{include file="findInclude:common/templates/header.tpl"}

{if $contentTitle}
<div class="nonfocal staticContentTitle {$page}">
  <h2>{$contentTitle}</h2>
</div>
{/if}

<div class="staticContentBody {$page}">
{$contentBody}
</div>
{include file="findInclude:common/templates/footer.tpl"}
