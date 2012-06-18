{extends file="findExtends:common/templates/pane.tpl"}

{block name="content"}
{$breadcrumbSamePageArgs = array()}
{$breadcrumbArgs = array()}
{include file="findInclude:common/templates/search.tpl" resultCount=$resultCount searchPage="/people/search"}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$contacts secondary=true accessKey=false}
{/block}
