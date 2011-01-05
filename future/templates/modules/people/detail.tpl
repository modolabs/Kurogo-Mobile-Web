{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="detailsStart"}
{/block}
  {foreach $personDetails as $section}
    {block name="sectionStart"}
      <ul class="nav">
    {/block}        
        {foreach $section as $item}
          {strip}
          {block name="detail"}
            <li>
              {if isset($item['url'])}
                <a href="{$item['url']}" class="{$item['class']|default:''}">
              {/if}
                  <div class="label">{$item['label']}</div>
                  <div class="value">{$item['title']}</div>
              {if isset($item['url'])}
                </a>
              {/if}
            </li>
          {/block}
          {/strip}
        {/foreach}    
    {block name="sectionEnd"}
      </ul>
    {/block} 
  {/foreach}
{block name="detailsEnd"}
{/block}

{include file="findInclude:common/footer.tpl"}

