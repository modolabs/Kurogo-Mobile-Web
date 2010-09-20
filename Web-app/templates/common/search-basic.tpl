<form method="get" action="./search.php">
<p id="search">
	{$placeholder|default:'Search'}:<br/>
	<span class="inputcombo emphasized">
		<input class="forminput" type="search" id="filter" name="search_terms" value="{$searchTerms|default:''}" />
		<input class="combobutton" name="search" id="sch_btn" type="submit" value="Search" />
	</span>	
</p>
</form>
