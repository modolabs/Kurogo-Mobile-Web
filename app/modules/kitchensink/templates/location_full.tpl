{$customHeader = null}
{if $pagetype != "tablet"}
  {$customHeader = ""}
{/if}
{include file="findInclude:common/templates/header.tpl" customHeader=$customHeader}

<div id="map">
</div>

{include file="findInclude:common/templates/footer.tpl" hideFooterLinks=true}
