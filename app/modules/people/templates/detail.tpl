{include file="findInclude:common/templates/header.tpl"}

{block name="detailsStart"}
{/block}
  {foreach $personDetails as $section}
    {block name="sectionStart"}
      <ul class="nav">
    {/block}        
        {foreach $section as $item}
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
              
              
              {if $item['img']}
			      <img src="{$item['img']}" alt="{$item['title']}"{if $item['imgWidth']}
			        width="{$item['imgWidth']}"{/if}{if $item['imgHeight']}
			        height="{$item['imgHeight']}"{/if}{if $item['imgAlt']}
			        alt="{$item['imgAlt']}"{/if} />
    		  {/if}
              
              
            </li>
          {/block}
        {/foreach}    
    {block name="sectionEnd"}
      </ul>
    {/block} 
  {/foreach}
{block name="detailsEnd"}
{/block}

{include file="findInclude:common/templates/footer.tpl"}

