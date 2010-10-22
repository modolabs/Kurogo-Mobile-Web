{include file="findInclude:common/header.tpl"}

{$key = 'index'}
{$helpTitle = "$moduleName Help"}
{if isset($help[$moduleID][$page])}
  {$key = $page}
{/if}

<div class="focal">
  <h2>{$helpTitle}</h2>
  {foreach $help[$moduleID][$key] as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div>

{include file="findInclude:common/footer.tpl"}
