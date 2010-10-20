{include file="findInclude:common/header.tpl" pageTitle="What's New"}

<ul class="focal"> 
  {if count($items)}
    {foreach $items as $item}
      <li> 
        <strong>{$item['title']}</strong>: 
        <span class="smallprint">{$item['body']} ({$item['unixtime']|date_format: 'n/j'})</span> 
      </li> 
    {/foreach}
  {else}
    <li>No new items.</li>
  {/if}
</ul> 

{include file="findInclude:common/footer.tpl"}
