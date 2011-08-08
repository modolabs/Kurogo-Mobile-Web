{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" class="{$section}">
<input id="adminSubmit" type="submit" value="{getLocalizedString key="BUTTON_SAVE"}" /> 
<h1>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_TITLE"}</h1>
<p id="moduleDescription" class="preamble">{getLocalizedString key="ADMIN_MODULES_OVERVIEW_DESCRIPTION"}</p>

<dl class="legend">
    <dt>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_ID_TITLE"}</dt>
    <dd>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_ID_DESCRIPTION"}</dd>
    <dt>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_ENABLED_TITLE"}</dt>
    <dd>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_ENABLED_DESCRIPTION"}</dd>
    <dt>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_HOME_TITLE"}</dt>
    <dd>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_HOME_DESCRIPTION"}</dd>
    <dt>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_SSL_TITLE"}</dt>
    <dd>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_SSL_DESCRIPTION"}</dd>
    <dt>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_FEDSEARCH_TITLE"}</dt>
    <dd>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_FEDSEARCH_DESCRIPTION"}</dd>
</dl>
<table class="configtable" summary="Overview table of modules and their high-level configuration">
<thead>
    <tr>
        <th colspan="2">{getLocalizedString key="ADMIN_MODULES_OVERVIEW_MODULENAME_TITLE"}</th>
        <th>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_ID_TITLE"}</th>
        <th>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_ENABLED_TITLE"}</th>
        <th>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_HOME_TITLE"}</th>
        <th>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_SSL_TITLE"}</th>
        <th>{getLocalizedString key="ADMIN_MODULES_OVERVIEW_FEDSEARCH_TITLE"}</th>
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
