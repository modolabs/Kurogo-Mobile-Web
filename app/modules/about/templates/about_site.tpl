{include file="findInclude:common/templates/header.tpl"}

<div class="focal"> 
  {foreach $moduleStrings.SITE_ABOUT_HTML as $paragraph}
    <p>{$paragraph}</p>
  {/foreach}
  <p>
    {"ABOUT_SITE_VERSION"|getLocalizedString:$devicePhrase}
  </p>
  <p>
    {"ABOUT_SITE_FEEDBACK_MESSAGE"|getLocalizedString:$strings.FEEDBACK_EMAIL}
  </p> 
</div> 

<div class="nonfocal legend"> 
  <p>{"ABOUT_SITE_NOTE"|getLocalizedString:$strings.SITE_NAME}</p>
</div> 
{include file="findInclude:common/templates/footer.tpl"}
