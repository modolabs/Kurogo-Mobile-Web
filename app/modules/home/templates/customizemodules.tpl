  <ul class="nav iconic" id="homepageList">
    {foreach $modules as $id => $info}
      <li id="{$id}" class="{if $info['visible']}moduleVisible{else}moduleHidden{/if}">
        {if $info['hideable']}
          <input type="checkbox" onclick="toggle(this);"{if $info['visible']} checked="checked"{/if} />
        {/if}
        <span class="nolinkbuttons"> 
          <a href="#" onclick="moveUp(this); return false;" class="moveup"><img src="/modules/{$configModule}/images/button-up.png" border="0" alt="Up" /></a>
          <a href="#" onclick="moveDown(this); return false;" class="movedown"><img src="/modules/{$configModule}/images/button-down.png" border="0" alt="Down" /></a> 
        </span> 
        <span class="nolink">
          <img src="{$info.img}" width="30" height="30" class="homeicon" />{$info['title']}
        </span>                   
      </li>
    {/foreach}
  </ul>
