{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm">
<input name="submit" id="adminSubmit" type="submit" value="Save" />
<h1>Module Configuration</h1>
<table class="configtable" summary="Overview table of modules and their high-level configuration">
<thead>
    <tr>
        <th colspan="2">Module name</th>
        <th>ID</th>
        <th>Enable</th>
<!--        <th>Home<br/>Screen</th> -->
        <th>Protect</th>
        <th>SSL</th>
        <th>Federated<br/>Search</th>
    </tr>
</thead>
<tbody>
{foreach $modules as $moduleData}
<tr>
    <td><img src="/modules/home/images/compliant/{$moduleData.id}.png" width="30" height="30" alt="{$moduleData.title|escape}" /></td>
    <td>{$moduleData.title|escape}</td>
    <td>{$moduleData.id}</td>
    <td><input type="hidden" name="{$moduleData.id}[disabled]" value="1" /> <input type="checkbox" name="{$moduleData.id}[disabled]" value="0"{if !$moduleData.disabled} checked{/if} /></td>
<!--     <td>x</td> -->
    <td><input type="hidden" name="{$moduleData.id}[protected]" value="0" /> <input type="checkbox" name="{$moduleData.id}[protected]" value="1"{if $moduleData.protected} checked{/if} /></td>
    <td><input type="hidden" name="{$moduleData.id}[secure]" value="0" /> <input type="checkbox" name="{$moduleData.id}[secure]" value="1"{if $moduleData.secure} checked{/if} /></td>
    <td><input type="hidden" name="{$moduleData.id}[search]" value="0" /> <input type="checkbox" name="{$moduleData.id}[search]" value="1"{if $moduleData.search} checked{/if} /></td>
</tr>
{/foreach}
</tbody>
</table>
<dl class="legend">
    <dt>ID</dt>
    <dd>The unique internal module ID. This can only be changed in the source code.</dd>
    <dt>Enable</dt>
    <dd>Module is active for this site.</dd>
<!--    <dt>Home screen</dt>
    <dd>Module is featured on the home screen.</dd> -->
    <dt>Protect</dt>
    <dd>Module requires authentication.</dd>
    <dt>SSL</dt>
    <dd>Module requires a secure connection.</dd>
    <dt>Federated Search</dt>
    <dd>Module's contents are included in site-wide federated search.</dd>
</dl>
</form>
{include file="findInclude:modules/admin/templates/footer.tpl"}
