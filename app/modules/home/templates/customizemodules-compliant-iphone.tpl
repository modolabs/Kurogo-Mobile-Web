  <ul id="dragReorderList">
    {foreach $modules as $id => $info}
      <li id="{$id}" class="{if $info['visible']}moduleVisible{else}moduleHidden{/if}">
        <a name="{$id}"></a>
        {if $info['hideable']}
          <input type="checkbox" name="{$id}" onclick="toggle(this);"{if $info['visible']} checked="checked"{/if} />
        {/if}
        <a class="title" href="/{$id}/">
          <img src="{$info.img}" width="30" height="30" class="homeicon" />{$info['title']}
        </a>
        <div class="draghandle"></div>
      </li>
    {/foreach}
  </ul>
