<div id="campus-select">
    <table border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td class="formlabel">{"SEARCH_IN_GROUP"|getLocalizedString}</td>
            <td class="inputfield">
                <select name="group">
                    {foreach $campuses as $campus}
                        <option value="{$campus['id']}" {if $campus['id'] == $group}selected{/if}>{$campus['title']}</option>
                    {/foreach}
                </select>
            </td>
    </tr>
    </table>
</div>
