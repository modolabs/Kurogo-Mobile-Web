{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
    <h2>{"TRUNCATE_FORM_TITLE"|getLocalizedString}</h2>
    <form method="post" action="{$action}">
        <fieldset class="formelement">
            <label>Length:</label> 
            <input type="number" name="length" size="6" value="200" />
        </fieldset>
        <fieldset class="formelement">
            <label>Margin:</label> 
            <input type="number" name="margin" size="6" value="80" />
        </fieldset>
        <fieldset class="formelement">
            <label>Min Line:</label> 
            <input type="number" name="minLineLength" size="6" value="40" /> 
            <span class="smallprint">(widow threshold character count)</span>
        </fieldset>
        <fieldset class="formelement">
            <textarea name="html" rows="10" style="width:99%;margin:0;padding:0;"></textarea>
        </fieldset>
        <div class="formbuttons">
            {include file="findInclude:common/templates/formButtonSubmit.tpl" buttonTitle="Truncate"}
        </div>
    </form>
</div>

{include file="findInclude:common/templates/footer.tpl"}
