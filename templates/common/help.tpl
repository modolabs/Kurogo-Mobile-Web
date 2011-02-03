{include file="findInclude:common/header.tpl"}

{$helpTitle = "$moduleName Help"}

<div class="focal">
  <h2>{$helpTitle}</h2>
  
  {foreach $moduleStrings.help as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div>

{include file="findInclude:common/footer.tpl"}
