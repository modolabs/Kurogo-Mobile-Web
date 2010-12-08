{include file="findInclude:common/header.tpl"}

<div class="focal">
  {foreach $infoConfig[$infoType]['content'] as $index => $paragraph}
    {if $infoConfig[$infoType]['heading'][$index]}
      <h2>{$infoConfig[$infoType]['heading'][$index]}</h2>
    {/if}
    {if $paragraph}
      <p>{$paragraph}</p>
    {/if}
  {/foreach}
</div>

{include file="findInclude:common/footer.tpl"}
