{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$courseNameShort}</h2>
</div>

{include file="findInclude:common/search.tpl" emphasized=false placeholder="Search keyword, #, or instructor" extraArgs=$extraSearchArgs}

{include file="findInclude:common/results.tpl" results=$classes}

{include file="findInclude:common/footer.tpl"}
