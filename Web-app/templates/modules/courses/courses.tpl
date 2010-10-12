{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$schoolNameShort}</h2>
</div>

{include file="findInclude:common/search.tpl" emphasized=false placeholder="Search keyword, #, or instructor" extraArgs=$extraSearchArgs}

{include file="findInclude:common/navlist.tpl" navlistItems=$courses}

{include file="findInclude:common/footer.tpl"}
