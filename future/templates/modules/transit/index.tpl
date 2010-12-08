{include file="findInclude:common/header.tpl"}

{$tabBodies = array()}

{capture name="runningPane" assign="runningPane"}
  {strip}
  {block name="runningPane"}
    {foreach $runningRoutes as $section}
      <h3>{$section['heading']}</h3>
      {include file="findInclude:common/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true}
    {/foreach}
  {/block}
  {strip}
{/capture}
{$tabBodies['running'] = $runningPane}

{capture name="offlinePane" assign="offlinePane"}
  {strip}
  {block name="offlinePane"}
    {foreach $offlineRoutes as $section}
      <h3>{$section['heading']}</h3>
      {include file="findInclude:common/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true}
    {/foreach}
  {/block}
  {strip}
{/capture}
{$tabBodies['offline'] = $offlinePane}

{capture name="newsPane" assign="newsPane"}
  {strip}
  {block name="newsPane"}
    {foreach $news as $section}
      <h3>{$section['heading']}</h3>
      {foreach $section['items'] as $index => $item}
        {$section['items'][$index]['subtitle'] = $item['date']|date_format:"%a %b %e, %Y"}
      {/foreach}
      {include file="findInclude:common/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true subTitleNewline=true}
    {/foreach}
  {/block}
  {strip}
{/capture}
{$tabBodies['news'] = $newsPane}

{capture name="infoPane" assign="infoPane"}
  {strip}
  {block name="infoPane"}
    {foreach $infosections as $section}
      <h3>{$section['heading']}</h3>
      {include file="findInclude:common/navlist.tpl" navlistItems=$section['items'] accessKey=false nested=true subTitleNewline=true}
    {/foreach}
  {/block}
  {strip}
{/capture}
{$tabBodies['info'] = $infoPane}

{block name="tabView"}
	<a name="scrolldown"></a>		
  <div class="nonfocal">
	  {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies smallTabs=true}
	</div>
{/block}

{include file="findInclude:common/footer.tpl"}
