{extends file="findExtends:modules/customize/templates/index.tpl"}

{block name="customize"}
  {if $newCount > 0}
    <div id="statusmsg" class="focal collapsed" onclick="showHideFuller(this);">
      <strong>{$newCount} New App{if $newCount > 1}s{/if}</strong>
      <span class="smallprint">
        <span class="summary">
          <span class="more">show</span>
        </span>
        <span class="fulltext">
          <span class="more">hide</span>
          <ul class="newapps{if $newCount >= 2} twoPlus{/if}{if $newCount >= 3} threePlus{/if}">
            {foreach $modules as $id => $info}
              {if $info['new']}
                <li><a href="#{$id}">{$info['title']}</a></li>
              {/if}
            {/foreach}
          </ul>
        </span>
      </span>
    </div>
    <h3 id="allapps">All Apps</h3>
  {else}
    <h3 id="allapps"></h3>
  {/if}
  
  <ul id="dragReorderList">
    {foreach $modules as $id => $info}
      <li>
        <a name="{$id}"></a>
        <input type="checkbox" name="{$id}" checked="true" value="" {if !$info['disableable']}class="required prefs_{$moduleID}"{/if} />
        <a class="title" href="../{$id}/">
          <img src="/modules/home/images/{$id}{$customize_icon_suffix}.png" width="30" height="30" class="homeicon" />{$info['title']}
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
