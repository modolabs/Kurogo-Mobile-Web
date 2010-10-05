{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>{$diningHall}</h2>
</div>

<p class="nonfocal fineprint">Harvard student ID required. Schedule shown does not account for holidays and other closures.</p>

<ul class="nav">
  {foreach $diningHallHours as $meal => $times}
    <li>
      <div class="label">{$meal}</div>
      <div class="value">{$times}</div>
    </li>
  {/foreach}
</ul>

<div class="nonfocal">
	<h3>Interhouse Restrictions</h3>
</div>

<ul class="nav">
  {foreach $diningHallRestrictions as $meal => $restriction}
    <li>
      <div class="label">{$meal}</div>
      <div class="value">{$restriction}</div>
    </li>
  {/foreach}
</ul>

{include file="findInclude:common/footer.tpl"}
