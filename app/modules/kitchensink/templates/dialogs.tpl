{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">
  {foreach $buttons as $button}
    {$button['description']}:
    <div class="formbuttons">
      {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle=$button['title'] buttonOnclick=$button['javascript']}
    </div>
    <br/><br/>
  {/foreach}
</div>

{include file="findInclude:common/templates/footer.tpl"}
