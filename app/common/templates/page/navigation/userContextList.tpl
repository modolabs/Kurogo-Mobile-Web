{if $userContextListStyle!='none'}
<div id="userContextList" class="userContextList">
{if $userContextListStyle=='link'}
<a href="{$customizeURL}">{$strings.USER_CONTEXT_CUSTOM}</a>
{else}
<div class="userContextListDescription">{$userContextListDescription}</div>
{if $userContextListStyle=='list'}
<ul>
{foreach $userContextList as $contextItem name="userContextList"}
<li context="{$contextItem.context}" url="{$contextItem.url|escape}" ajax="{$contextItem.ajax}"{if $contextItem.active} class="contextSelected"{/if}><a href="{if $contextItem.ajax}#{else}{$contextItem.url}{/if}"{if $contextItem.ajax} onclick="return updateUserContextLink(this, '{$navContainerID}');{/if}">{$contextItem.title}</a> {if !$smarty.foreach.userContextList.last}{/if}</li>
{/foreach}
</ul>
{elseif $userContextListStyle=='select'}
<select onchange="updateUserContextSelect(this,'{$navContainerID}')">
{foreach $userContextList as $contextItem name="userContextList"}
  <option value="{$contextItem.context}" url="{$contextItem.url|escape}" ajax="{$contextItem.ajax}"{if $contextItem.active} selected="true"{/if}>{$contextItem.title}</option>
{/foreach}
</select>
{/if}
{/if}
</div>
{/if}