{include file="findInclude:common/templates/header.tpl"}

{if $searchError}
  <div class="nonfocal">{$searchError}</div>
{else}

{if $headerSections}
  <div class="{block name="headerClass"}nonfocal header{/block}">
    {if isset($headerSections['HEADER_THUMBNAIL'])}
      {$item = reset($headerSections['HEADER_THUMBNAIL'])}
      {block name="headerPhoto"}
        {if $item['img']}
          <img src="{$item['img']}" class="photo" alt="{$item['title']}"{if $item['imgWidth']}
            width="{$item['imgWidth']}"{/if}{if $item['imgHeight']}
            height="{$item['imgHeight']}"{/if}{if $item['imgAlt']}
            alt="{$item['imgAlt']}"{/if} />
        {/if}
      {/block}
    {/if}
    <div class="header-text">
	  <div class="action-buttons">
	    {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
	  </div>
      {if isset($headerSections['HEADER_TITLE'])}
      {$item = reset($headerSections['HEADER_TITLE'])}
      {block name="headerTitle"}
        {if $item['url']}<a href="{$item['url']}" class="{$item['class']}">{/if}
          <h2>{$item['title']}</h2>
        {if $item['url']}</a>{/if}
      {/block}
      {else}
      <h2>{"NO_HEADER_TITLE"|getLocalizedString}</h2>
      {/if}
      {if isset($headerSections['HEADER_SUBTITLE'])}
        {foreach $headerSections['HEADER_SUBTITLE'] as $item}
          {block name="headerSubtitles"}
            {if $item['url']}<a href="{$item['url']}" class="{$item['class']}">{/if}
              <div class="smallprint">{$item['title']}</div>
            {if $item['url']}</a>{/if}
          {/block}
        {/foreach}
      {/if}
    </div>
  </div>
{/if}

{foreach $personDetails as $sectionName => $section}
  {foreach $section as $key => $item}
    {if $item['label']}
      {capture name="label" assign="label"}
        {block name="itemLabel"}
          <div class="label">{$item['label']}</div>
        {/block}
      {/capture}
      {$section[$key]['label'] = $label}
    {/if}
    {capture name="title" assign="title"}
      {block name="itemTitle"}
        <div class="value">{$item['title']}</div>
      {/block}
    {/capture}
    {$section[$key]['title'] = $title}
  {/foreach}
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$section accessKey=false labelColon=false}
{/foreach}

{/if}

{include file="findInclude:common/templates/footer.tpl"}

