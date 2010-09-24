{include file="findInclude:common/header.tpl"}

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
      <a class="title" href="../{$id}/" style="background: url('/modules/{$moduleID}/images/{$id}-tiny.png') no-repeat left;">
        {$info['title']}
      </a>
      <div class="draghandle"></div>
    </li>
  {/foreach}
</ul>
<div class="formbuttons">
<a class="formbutton" href="../home/"><div>Return to Home</div></a>
</div>
<p id="savedMessage">Saved</p>

{include file="findInclude:common/footer.tpl"}
