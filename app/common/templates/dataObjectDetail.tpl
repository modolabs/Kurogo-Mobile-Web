{if $dataObjectDetails}
<div class="dataObjectDetails">
{foreach $dataObjectDetails as $section=>$sectionData}
{if $sectionData.type == 'html'}
{if $sectionData.heading}
<div class="nonfocal {$sectionData.class}">
  <h3>{$sectionData.heading}</h3>
  {if $sectionData.subheading}<p class="smallprint">{$sectionData.subheading}</p>{/if}
</div>
{/if}
<div class="detailhtml {$sectionData.htmlclass}">{$sectionData.html}</div>
{elseif $sectionData.type == 'navlist'}{if $sectionData.items}
{include file="findInclude:common/templates/navlist.tpl" accessKey=false navlistClass=$sectionData.class navListHeading=$sectionData.heading navListSubheading=$sectionData.subheading navlistItems=$sectionData.items subTitleNewline=$sectionData.subTitleNewline}{/if}
{elseif $sectionData.type == 'list'}{if $sectionData.items}
{if $sectionData.heading}
<div class="nonfocal {$sectionData.class}">
  <h3>{$sectionData.heading}</h3>
  {if $sectionData.subheading}<p class="smallprint">{$sectionData.subheading}</p>{/if}
</div>
{/if}
<ul class="{$sectionData.class}">
{foreach $sectionData.items as $item}
<li class="$item.class">{$item.title}</li>
{/foreach}
</ul>
{/if}
{/if}
{/foreach}
</div>
{else}
<p>{"NO_INFO"|getLocalizedString}</p>
{/if}