{extends file="findExtends:modules/home/index.tpl"}

{block name="modules"}
  <div id="homegrid">
    {$smarty.block.parent}
  </div>
{/block}

{block name="category"}
  {$smarty.block.parent}
  {if $category != $lastCategory}
    <div class="separator">&nbsp;</div>
  {/if}
{/block}

{block name="module"}
  <div id="{$id}"{if $category == 'secondary'} class="utility"{/if}>
    {strip}
    <a href="{$info['url']}">
      <img src="/modules/{$moduleID}/images/{$id}.png" alt="{$info['title']}" />
      <br/>{$info['title']}
      {if isset($info['subTitle'])}
        <br/><span class="fineprint">{$info['subTitle']}</span>
      {/if}
    {if $id == 'about' && $whatsNewCount > 0}
      <span class="badge">{$whatsNewCount}</span>
    {/if}
    </a>
    {/strip}
  </div>
{/block}
