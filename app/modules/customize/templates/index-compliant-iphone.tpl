{extends file="findExtends:modules/customize/templates/index.tpl"}

{block name="customize"}
  <div class="nonfocal smallprint"> 
    {"CUSTOMIZE_INSTRUCTIONS_COMPLIANT_DRAG"|getLocalizedString}
  </div> 
  
  <ul id="dragReorderList">
    {foreach $modules as $id => $info}
      <li>
        <a name="{$id}"></a>
        <input type="checkbox" name="{$id}" checked="true" value="" {if !$info['disableable']}class="required prefs_{$moduleID}"{/if} />
        <a class="title" href="../{$id}/">
          <img src="/modules/home/images/{$id}.png" width="30" height="30" class="homeicon">{$info['title']}
        </a>
        <div class="draghandle"></div>
      </li>
    {/foreach}
  </ul>
  <div class="formbuttons">
    {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle="RETURN_HOME"|getLocalizedString buttonURL="../home/"}
  </div>
  <p id="savedMessage">Saved</p>
{/block}
