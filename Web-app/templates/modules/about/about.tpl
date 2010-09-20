{include file="common/header.tpl"|for_device:$device}
<div class="focal"> 
  {foreach $INSTITUTION_ABOUT_HTML as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div> 
{include file="common/footer.tpl"|for_device:$device}
