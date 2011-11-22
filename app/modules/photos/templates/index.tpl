{include file="findInclude:common/templates/header.tpl"}

{capture name="categorySelect" assign="categorySelect"}
  <select class="input" id="section" name="section" onchange="loadSection(this);">
    {foreach $sections as $section}
      {if $section['value']==$currentSection}
        <option value="{$section['value']}" selected="true">{$section['title']}</option>
      {else}
        <option value="{$section['value']}">{$section['title']}</option>
      {/if}
    {/foreach}
  </select>
{/capture}

{$categorySelect}

{block name="navList"}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$photos listItemTemplateFile="findInclude:modules/photos/templates/photoListItem.tpl"}
{/block}

{include file="findInclude:common/templates/footer.tpl"}
