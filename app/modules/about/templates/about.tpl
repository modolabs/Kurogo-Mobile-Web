{include file="findInclude:common/templates/header.tpl"}

<div class="focal"> 
  {foreach $moduleStrings.ABOUT_HTML as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div> 

{include file="findInclude:common/templates/footer.tpl"}
