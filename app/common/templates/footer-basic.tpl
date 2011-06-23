{extends file="findExtends:common/templates/footer.tpl"}

{block name="footerNavLinks"}
  {if $hasHelp}
    <p class="secondary">
      <a href="help.php">{$moduleName} Help</a>
    </p>
  {/if}
  
  {if $moduleID != 'home' || $page != 'index'}
    {html_access_key_reset index=0 force=true}
    <p class="bottomnav">
      <a href="#top">Back to top</a>
      <br />
      {html_access_key_link href="/home/"}{$strings.SITE_NAME} Home{/html_access_key_link}
      {if !$isModuleHome}
        {foreach $breadcrumbs as $breadcrumb}
          {if $moduleID != 'home' || !$breadcrumb@first}
            <br/>
            {html_access_key_link href=$breadcrumb['url']}
              {if $breadcrumb@first}
                {$moduleName} Home
              {else}
                {$breadcrumb['longTitle']}
              {/if}
            {/html_access_key_link}
          {/if}
        {/foreach}
      {/if}
      {foreach $additionalLinks as $link}
        <br/>
        {html_access_key_link href=$link['url']}{$link['title']}{/html_access_key_link}
      {/foreach}
    </p>
  {/if}

{/block}

  {block name="loginHTML"}
    {if $session && $moduleID == 'home'}
	<div class="loginstatus">
        {if $session_isLoggedIn}
        {if $session_multiple_logins}
			<p><a href="{$session_logout_url}">Signed in with multiple identities</a></p>
        {else}
			<p class="{$session_authority_class}"><a href="../login">Signed in via {$session_authority_title} as {$session_fullName}{if $session_multiple_logins} (and other identities){/if}</a></p>
		{/if}
		{else}
			<p class="noauth"><a href="../login">Sign in to {$strings.SITE_NAME}</a></p>
		{/if}
	</div>
	{/if}
  {/block}

{block name="footer"}
  <div class="nonfocal">
    <p class="fontsize">
      Font size:&nbsp;
      {foreach $fontsizes as $size}
        {if $size == $fontsize}
          <span class="font{$fontsize}">A</span>
        {else}
          <a href="{$fontSizeURLs[$size]}" class="font{$size}">A</a>
        {/if}
        {if !$size@last}&nbsp;|&nbsp;{/if}
      {/foreach}
    </p>

    <p class="bb"> </p>
    
    <p id="footer">
      {$footerHTML}
    </p>
  </div>
{/block}

{block name="footerJavascript"}
  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
{/block}
