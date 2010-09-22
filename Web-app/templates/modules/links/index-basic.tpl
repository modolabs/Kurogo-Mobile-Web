{extends file="findExtends:modules/links/index.tpl"}

{block name="textStart"}<p>{/block}
{block name="textEnd"}</p>{/block}

{block name="linksStart"}
  {html_access_key_reset index=count($breadcrumbs)+1}
  <p class="nav">
{/block}

{block name="link"}
  {html_access_key_link href=$link['url']}
    {$link['name']}
  {/html_access_key_link}
  <br/>
{/block}

{block name="linksEnd"}
  </p>
{/block}
