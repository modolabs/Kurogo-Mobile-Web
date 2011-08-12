<div class="nonfocal formlist"{if $formlistID} id="{$formlistID}"{/if}>
  {foreach $advancedFields as $item}
    <p class="formelement">
      {include file="findInclude:common/templates/formListItem.tpl"}
    </p>
  {/foreach}
</div>
