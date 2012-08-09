{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h3>Simple emphasized search bar:</h3>
</div>
{include file="findInclude:common/templates/search.tpl" emphasized=true}

<div class="nonfocal">
  <h3>Simple search bar:</h3>
</div>
{include file="findInclude:common/templates/search.tpl"}

<div class="nonfocal">
  <h3>Advanced search form:</h3>
</div>
<form method="get" id="advancedSearchForm" action="/{$configModule}/search">
  {include file="findInclude:common/templates/formList.tpl" advancedFields=$formFields}
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonSubmit.tpl" buttonTitle="Search"}
  </div>
</form>

{include file="findInclude:common/templates/footer.tpl"}
