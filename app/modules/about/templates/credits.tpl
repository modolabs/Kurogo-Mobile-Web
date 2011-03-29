{include file="findInclude:common/templates/header.tpl"}

<div class="focal">
<p>{$strings.SITE_NAME} is built using version {$smarty.const.KUROGO_VERSION} of the <a href="http://modolabs.com/kurogo">Kurogo Framework</a>.</p>

{include file="findInclude:modules/$moduleID/templates/credits_html.tpl"}

</div>
{include file="findInclude:common/templates/footer.tpl"}
