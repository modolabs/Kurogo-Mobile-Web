{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle="Current Location" buttonOnclick="getLocation(false);"}
  </div>
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle="High Accuracy Location" buttonOnclick="getLocation(true);"}
  </div>
</div>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$links}

{include file="findInclude:common/templates/footer.tpl"}
