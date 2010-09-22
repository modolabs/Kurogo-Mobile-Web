{include file="findInclude:common/header.tpl" pageTitle="What's New"}

<ul class="focal"> 
  {foreach $items as $item}
    <li> 
      <strong>{$item['title']}</strong>: 
      <span class="smallprint">{$item['body']} ({$item['unixtime']|date_format: 'n/j'})</span> 
    </li> 
  {/foreach}
</ul> 

{include file="findInclude:common/footer.tpl"}
