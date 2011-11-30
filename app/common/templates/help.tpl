{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
  <h2>{"HELP_TEXT"|getLocalizedString:$moduleName}</h2>
  
  {foreach $moduleStrings.help as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div>

{include file="findInclude:common/templates/footer.tpl"}
