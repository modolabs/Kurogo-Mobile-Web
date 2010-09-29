{include file="findInclude:common/header.tpl"}

<div class="focal">
  <h2>{$summary}</h2>

  <ul class="nav">
    <li>{$date}<br/>{$time}</li>

    {if isset($location)}
      <li>Location: {$location}</li>
    {/if}

    {if strlen($ticketsLink)}
      <li>Link to Tickets: <a href="{$ticketsLink}" class="action external" target="_new">{$ticketsLink}></a>
      </li>
    {/if}

    <li class="description">{$description}</li>

    {if strlen($phone)}
      <li>For info call: <a href="{$phoneUrl}" class="action phone">{$phone}</a></li>
    {/if}
    
    {if strlen($email)}
      <li>Email: <a href="{$email}" class="action email">{$email}</a></li>
    {/if}

    {if strlen($url)}
      <li>Website: <a href="{$url}" class="action external" target="_new">{$url}</a></li>
    {/if}
  </ul>
  
  <p class="legend">
    Categorized as: 
    {foreach $categories as $category}
      <a href="{$category['url']}">{$category['title']}</a>{if !$category@last}, {/if}
    {/foreach}
  </p>
</div>

{include file="findInclude:common/footer.tpl"}
