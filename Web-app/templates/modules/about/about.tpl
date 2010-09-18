{include file="common/header.tpl"|for_device:$device}
<div class="focal"> 
  <h2>About {$INSTITUTION_NAME}</h2> 
  {foreach $INSTITUTION_ABOUT_HTML as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
</div> 
{include file="common/footer.tpl"|for_device:$device}
