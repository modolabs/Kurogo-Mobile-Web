{extends file="findExtends:common/templates/pane.tpl"}

{block name="content"}
  <div class="nonfocal">
    {foreach $moduleStrings.SITE_ABOUT_HTML as $paragraph}
      <p>{$paragraph}</p>
    {/foreach}
  </div>
{/block}
