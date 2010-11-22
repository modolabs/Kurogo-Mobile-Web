{include file="findInclude:common/header.tpl"}
<div class="nonfocal">
<h2>Home Screen Modules</h2>
</div>
<form method="POST">
<input type="hidden" name="moduleID" value="{$module.id}" />
<input type="hidden" name="merge" value="0" />
{strip}
<div class="nonfocal">
<select id="addModuleID">
    <option value="">Add Module</option>
    {foreach $allModules as $_moduleID=>$moduleTitle}
    <option value="{$_moduleID}">{$moduleTitle}</option>
    {/foreach}
</select> <input id="addModule" type="image" src="/common/images/ok.png" />
</div>
<ul id="module_order" class="nav">
{foreach $sectionModules as $_moduleID=>$moduleTitle}
    <li>
        <div class="deletehandle"></div> 
        <label>{$_moduleID}</label> 
        <input type="text" name="moduleData[{$section}][{$_moduleID}]" value="{$moduleTitle|escape}" />
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
{include file="findInclude:common/footer.tpl"}
