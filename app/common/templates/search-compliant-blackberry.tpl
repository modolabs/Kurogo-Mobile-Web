{extends file="findExtends:common/templates/search-touch.tpl"}
{* Yes this should be search-touch.tpl.  The touch version is better for blackberries *}

{block name='inputWidth'}90%{/block}
{block name='submitWidth'}10%{/block}

{block name="formSubmit"}
  <input class="imagebutton" name="search" id="sch_btn" value="Search" src="/common/images/search_button.png" type="image"/>
{/block}
