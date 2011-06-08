{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" class="{$section}">
<input id="adminSubmit" type="submit" value="Save" /> 
<h1>Modules Overview</h1>
<p id="moduleDescription" class="preamble">Use this section to view and manage the overall properties of each module.

</p>
<dl class="legend">
    <dt>ID</dt>
    <dd>The unique internal module ID. This can only be changed in the source code.</dd>
    <dt>Enabled</dt>
    <dd>Module is active for this site.</dd>
    <dt>Home</dt>
    <dd>Module is featured on the home screen.</dd>
    <dt>SSL</dt>
    <dd>Module requires a secure connection.</dd>
    <dt>Federated Search</dt>
    <dd>Module's contents are included in site-wide federated search.</dd>
</dl>
<table class="configtable" summary="Overview table of modules and their high-level configuration">
<thead>
    <tr>
        <th colspan="2">Module name</th>
        <th>ID</th>
        <th>Enabled</th>
        <th>Home</th>
        <th>SSL</th>
        <th>Federated<br/>Search</th>
    </tr>
</thead>
<tbody>
{foreach $modules as $moduleData}
<tr>
    <td><img src="/modules/home/images/compliant/{$moduleData.id}.png" width="30" height="30" alt="{$moduleData.title|escape}" /></td>
    <td><a href="{$moduleData.url}">{$moduleData.title|escape}</a></td>
    <td>{$moduleData.id}</td>
    <td><input type="hidden" name="{$moduleData.id}[disabled]" value="1" /> <input type="checkbox" name="{$moduleData.id}[disabled]" value="0"{if !$moduleData.disabled} checked{/if} /></td>
    <td>{if $moduleData.home}<img src="/common/images/available.png" alt="Yes" />{/if}</td>
    <td><input type="hidden" name="{$moduleData.id}[secure]" value="0" /> <input type="checkbox" name="{$moduleData.id}[secure]" value="1"{if $moduleData.secure} checked{/if} /></td>
    <td><input type="hidden" name="{$moduleData.id}[search]" value="0" /> <input type="checkbox" name="{$moduleData.id}[search]" value="1"{if $moduleData.search} checked{/if} /></td>
</tr>
{/foreach}
</tbody>
</table>
</form>
{include file="findInclude:modules/admin/templates/footer.tpl"}
