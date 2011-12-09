{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  <h3>Simple search bar:</h3>
</div>
{include file="findInclude:common/templates/search.tpl"}

<div class="nonfocal">
  <h3>Advanced search form:</h3>
</div>
<form method="get" id="advancedSearchForm" action="search.php">
  {include file="findInclude:common/templates/formlist.tpl" formListItems=$advancedFields}
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonSubmit.tpl" buttonTitle="Search"}
  </div>
</form>


{include file="findInclude:common/templates/footer.tpl"}
