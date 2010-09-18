{strip}
<div id="navbar"{if $help} class="helpon"{/if}>
  <div class="breadcrumbs{if $moduleHome} homepage{/if}">
    <a name="top" href="../home/" class="homelink">
      <img src="common/images/homelink.png" width="57" height="45" alt="{$siteName} Home" />
    </a>
    
    {if !$moduleHome}
      <a href="./" class="module">
        <img src="common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" />
      </a>
      {foreach $breadcrumbs as $breadcrumb}
        <a href="{$breadcrumb['url']}" class="{$breadcrumb['class']}">
          <span>{$breadcrumb['text']}</span>
        </a>
      {/foreach}
    {/if}
    <span class="pagetitle">
      {if $moduleHome}
        <img src="common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" class="moduleicon" />
      {/if}
      {$pageTitle}
    </span>
  </div>
  {if $showHelp}
    <div class="help">
      <a href="help.php"><img src="common/images/help.png" width="46" height="45" alt="Help" /></a>
    </div>
  {/if}
</div>
{/strip}
