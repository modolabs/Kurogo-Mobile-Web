{extends file="findExtends:modules/customize/templates/index.tpl"}

{block name="customize"}
  <div class="nonfocal smallprint"> 
    Use the arrow buttons to customize the order of icons on the site-wide navigation bar, and the checkboxes to toggle visibility. Your changes will be automatically saved.
  </div> 
  
  <ul class="nav iconic" id="homepageList">
    {foreach $modules as $id => $info}
      <li id="{$id}">
        {if $info['disableable']}
          <input type="checkbox" onclick="toggle(this);"{if !$info['disabled']} checked="checked"{/if} />
        {/if}
        <span class="nolinkbuttons"> 
          <a href="#" onclick="moveUp(this); return false;" class="moveup"><img src="/modules/{$moduleID}/images/button-up.png" border="0" alt="Up"></a>
          <a href="#" onclick="moveDown(this); return false;" class="movedown"><img src="/modules/{$moduleID}/images/button-down.png" border="0" alt="Down"></a> 
        </span> 
        <span class="nolink">
          <img src="/modules/home/images/{$id}.png" width="30" height="30" class="homeicon">{$info['title']}
        </span>                   
      </li>
    {/foreach}
  </ul>
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle="Return to Home" buttonURL="../home/"}
  </div>
{/block}
