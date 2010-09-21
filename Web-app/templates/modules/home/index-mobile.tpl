{extends file="modules/home/index.tpl"}

{block name="modulesStart"}<div id="homegrid">{/block}

{block name="modulesCategoryStart"}{/block}

{block name="module"}
  <div id="{$id}"{if $category == 'secondary'} class="utility"{/if}>
    {strip}
    <a href="../{$info['url']|default:$id}/">
      <img src="../modules/{$moduleID}/images/{$id}.png" alt="{$info['title']}" />
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

{block name="modulesCategoryEnd"}
  {if $category != $lastCategory}
    <div class="separator">&nbsp;</div>
  {/if}
{/block}

{block name="modulesEnd"}</div>{/block}
