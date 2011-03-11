{include file="findInclude:common/templates/header.tpl"}
<div class="nonfocal">
<h2>Links</h2>
</div>
<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}" />
<input type="hidden" name="merge" value="0" />
{strip}
<input id="addLink" type="button" value="Add Link" />
<ul id="admin_links" class="nav">
{foreach $links as $index=>$linkData}
    <li>
        <div class="deletehandle"></div> 
        <label>Title</label>
        <input type="hidden" name="_type[moduleData][{$section}][title][]" value="text" />
        <input type="text" name="moduleData[{$section}][title][]" value="{$linkData.title|escape}" />
        <label>URL</label>
        <input type="hidden" name="_type[moduleData][{$section}][url][]" value="text" />
        <input type="text" name="moduleData[{$section}][url][]" value="{$linkData.url|escape}" />
        <label>Icon</label>
        <input type="hidden" name="_type[moduleData][{$section}][icon][]" value="text" />
        <input type="text" name="moduleData[{$section}][icon][]" value="{$linkData.icon|escape}" />
        <span class="movebuttons">
            <div class="moveup"></div>
            <div class="movedown"></div>
        </span> 
    </li>
{/foreach}
</ul>
<div class="nonfocal"><input type="submit" name="submit" value="Save" /></div>
{/strip}
</form>
{include file="findInclude:common/templates/footer.tpl"}
