{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
<p>{"ABOUT_CREDITS_KUROGO"|getLocalizedString:$strings.SITE_NAME:$smarty.const.KUROGO_VERSION}</p>

{include file="findInclude:modules/$moduleID/templates/credits_html.tpl"}

</div>
{include file="findInclude:common/templates/footer.tpl"}
