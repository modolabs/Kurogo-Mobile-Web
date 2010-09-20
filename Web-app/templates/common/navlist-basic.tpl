{extends file="common/navlist.tpl"}

{block name="navlistStart"} 
  {html_access_key_reset index=count($breadcrumbs)+1}
  <p class="nav">
{/block}
{block name="navlistItem"}
  {html_access_key_link href=$item['url'] class=$item['class']|default:null}
    {$item['html']}
  {/html_access_key_link}
  <br/>
{/block}
{block name="navlistEnd"}
  </p>
{/block}
