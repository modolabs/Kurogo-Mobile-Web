{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" class="{$section}">
<input id="adminSubmit" type="submit" value="{"BUTTON_SAVE"|getLocalizedString}" /> 
<h1>{"ADMIN_MODULES_OVERVIEW_TITLE"|getLocalizedString}</h1>
<p id="moduleDescription" class="preamble">{"ADMIN_MODULES_OVERVIEW_DESCRIPTION"|getLocalizedString}</p>

<dl class="legend">
    <dt>{"ADMIN_MODULES_OVERVIEW_ID_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_ID_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_ENABLED_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_ENABLED_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_HOME_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_HOME_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_SSL_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_SSL_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_FEDSEARCH_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_FEDSEARCH_DESCRIPTION"|getLocalizedString}</dd>
</dl>
<table class="configtable" summary="Overview table of modules and their high-level configuration">
<thead>
    <tr>
        <th colspan="2">{"ADMIN_MODULES_OVERVIEW_MODULENAME_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_ID_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_ENABLED_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_HOME_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_SSL_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_FEDSEARCH_TITLE"|getLocalizedString}</th>
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
