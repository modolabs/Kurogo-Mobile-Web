{strip}

{block name="navlistStart"}
  <ul class="nav{if $secondary} secondary{/if}">
{/block}

    {foreach $navlistItems as $item}
      
      {block name="navlistItem"}
        <li>
          {include file="findInclude:common/listItem.tpl" subTitleNewline=false}
        </li>
      {/block}
      
    {/foreach}

{block name="navlistEnd"}
  </ul>
{/block}

{/strip}
