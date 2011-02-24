{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" placeholder="Search Map" tip=$searchTip}

{if $campuses}
<div class="nonfocal">
  <a name="campuses"/>
  <h3>Campuses</h3>
</div>
{include file="findInclude:common/navlist.tpl" navlistItems=$campuses}
{/if}

{if $places}
<div class="nonfocal">
  <a name="places"/>
  <h3>Places</h3>
</div>
{include file="findInclude:common/navlist.tpl" navlistItems=$places}
{/if}

{include file="findInclude:common/footer.tpl"}
