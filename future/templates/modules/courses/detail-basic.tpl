{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="infoPane"}
    {if !count($times) && !count($infoItems)}
      <p> No detailed information to display </p>
    {else}
      <h4>Lecture:</h4>
      {foreach $times as $time}
        <p>
          {if isset($time['location']) && isset($time['url'])}
            {$time['days']} {$time['time']} 
            (<a class="map" href="{$time['url']}">{$time['location']}</a>)
          {else}
            {$time['days']} {$time['time']}
          {/if}
        </p>
      {/foreach}
      {foreach $infoItems as $item}
        <p class="divider"></p>
        <h3>{$item['header']}</h3>
        <p>{$item['content']|escape}</p>
      {/foreach}
    {/if}
{/block}

{block name="staffPane"}
      {foreach $staff['instructors'] as $instructor}
        <p><a href="{$instructor['url']}" class="people">{$instructor['title']}</a></p>
      {/foreach}
      
      {if count($staff['tas'])}
        <h3>TAs:</h3>
        {foreach $staff['tas'] as $ta}
          <p><a href="{$ta['url']}" class="people">{$ta['title']}</a></p>
        {/foreach}
      {/if}
{/block}
