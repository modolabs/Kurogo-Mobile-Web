{extends file="findExtends:modules/links/index.tpl"}

{block name="headingClass"}{/block}

{block name="linksStart"}
  {html_access_key_reset index=$accessKeyStart}
  <p class="nav">
{/block}

    {block name="link"}
      {html_access_key_link href=$link['url']}
        {$link['title']}
      {/html_access_key_link}
      <br/>
    {/block}

{block name="linksEnd"}
  </p>
{/block}
