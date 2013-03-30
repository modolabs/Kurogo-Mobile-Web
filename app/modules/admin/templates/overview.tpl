{include file="findInclude:modules/admin/templates/header.tpl"}
<form method="post" id="adminForm" class="{$section}">
<input id="adminSubmit" type="submit" value="{"BUTTON_SAVE"|getLocalizedString}" /> 
<h1>{"ADMIN_MODULES_OVERVIEW_TITLE"|getLocalizedString}</h1>
<p id="moduleDescription" class="preamble">{"ADMIN_MODULES_OVERVIEW_DESCRIPTION"|getLocalizedString}</p>

<dl class="legend">
    <dt>{"ADMIN_MODULES_OVERVIEW_ID_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_ID_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_TYPE_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_TYPE_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_ENABLED_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_ENABLED_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_HOME_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_HOME_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_SSL_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_SSL_DESCRIPTION"|getLocalizedString}</dd>
    <dt>{"ADMIN_MODULES_OVERVIEW_FEDSEARCH_TITLE"|getLocalizedString}</dt>
    <dd>{"ADMIN_MODULES_OVERVIEW_FEDSEARCH_DESCRIPTION"|getLocalizedString}</dd>
</dl>
<table id="overviewTable" class="configtable" summary="Overview table of modules and their high-level configuration">
<thead>
    <tr>
        <th></th>
        <th>{"ADMIN_MODULES_OVERVIEW_MODULENAME_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_ID_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_TYPE_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_ENABLED_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_HOME_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_SSL_TITLE"|getLocalizedString}</th>
        <th>{"ADMIN_MODULES_OVERVIEW_FEDSEARCH_TITLE"|getLocalizedString}</th>
        {*
        <th></th>
        *}
    </tr>
</thead>
<tbody>
{*
<tr>
    <td>Add</td>
    <td><input type="input" id="newModuleTitle" size="10" /></td>
    <td><input type="input" id="newModuleConfig" size="10" /></td>
    <td><select id="newModuleID"><option value="">-</option>{html_options values=$moduleClasses output=$moduleClasses first="-"}</select></td>
    <td><input type="checkbox" id="newModuleDisabled" value="0" /></td>
    <td></td>
    <td><input type="checkbox" id="newModuleSecure" value="1" /></td>
    <td><input type="checkbox" id="newModuleSearch" value="1" /></td>
    <td><input type="submit" id="addNewModule" value="+" /></td>
</tr>
*}
{foreach $modules as $moduleData}
<tr>
    <td><img src="{if $navigation_icon_set}/common/images/iconsets/{$navigation_icon_set}/30/{else}/modules/{$homeModuleID}/images/compliant/{/if}{$moduleData.icon}.png" width="30" height="30" alt="" /></td>
    <td><a href="{$moduleData.url}">{$moduleData.title|escape}</a></td>
    <td>{$moduleData.id}</td>
    <td>{$moduleData.type}</td>
    <td>{if $moduleData.canDisable}<input type="hidden" name="{$moduleData.id}[disabled]" value="1" /> <input type="checkbox" name="{$moduleData.id}[disabled]" value="0"{if !$moduleData.disabled} checked{/if} />{else}
    <img src="/common/images/available.png" alt="Yes" />{/if}
    </td>
    <td>{if $moduleData.home}<img src="/common/images/available.png" alt="Yes" />{/if}</td>
    <td><input type="hidden" name="{$moduleData.id}[secure]" value="0" /> <input type="checkbox" name="{$moduleData.id}[secure]" value="1"{if $moduleData.secure} checked{/if} /></td>
    <td><input type="hidden" name="{$moduleData.id}[search]" value="0" /> <input type="checkbox" name="{$moduleData.id}[search]" value="1"{if $moduleData.search} checked{/if} /></td>
    {*
    <td>{if $moduleData.canRemove}<input type="submit" id="removeModule_{$moduleData.id}" class="removeModule" value="-" />{/if}</td>
    *}
</tr>
{/foreach}
</tbody>
</table>
</form>
{include file="findInclude:modules/admin/templates/footer.tpl"}
