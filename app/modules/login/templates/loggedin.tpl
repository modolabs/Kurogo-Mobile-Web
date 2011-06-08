{include file="findInclude:common/templates/header.tpl"}

<div class="focal">You are signed in to {$strings.SITE_NAME}
{if $users|@count==1}
via {$session_authority_title} as {$session_fullName}</div>
{else}
via the following accounts</div>
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$users subTitleNewline=true navlistID="loggedInUsers"}

{include file="findInclude:common/templates/footer.tpl"}
