{include file="findInclude:common/templates/header.tpl"}

{if $allowCustomize}
{block name="customize"}
  <div class="nonfocal smallprint"> 
    {if $customizeUserContextList}
    {block name="customizeUserContextList"}
    <div id="customizeUserContextList" class="userContextList">
    <p>{$customizeUserContextListDescription}</p>
    <ul>
    {foreach $customizeUserContextList as $contextItem name="userContextList"}
    <li context="{$contextItem.context}" url="{$contextItem.url|escape}"{if $contextItem.active} class="contextSelected"{/if}><a href="#" onclick="return customizeSetUserContext(this, 'customizemodules');">{$contextItem.title}</a></li>
    {/foreach}
    </ul>
    <p>{$customizeUserContextListDescriptionFooter}</p>
    </div>
    {/block}
    {else}
    {$customizeInstructions}
    {/if}
  </div> 

  <div id="customizemodules">  
  {include file="findInclude:modules/home/templates/customizemodules.tpl"}
  </div>
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle="CUSTOMIZE_RETURN_HOME"|getLocalizedString buttonURL="index"}
  </div>
{/block}
{else}
<div class="focal">
{"CUSTOMIZE_DISABLED"|getLocalizedString}
</div>
{/if}
{include file="findInclude:common/templates/footer.tpl"}
