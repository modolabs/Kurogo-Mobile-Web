{include file="findInclude:common/header.tpl"}

<div class="nonfocal smallprint"> 
  Use the arrow buttons to customize the order of icons on your homepage, and the checkboxes to toggle visibility. Your changes will be automatically saved.
</div> 

<ul class="nav iconic" id="homepageList">
  {foreach $modules as $id => $info}
    <li id="{$id}">
      {if $info['disableable']}
        <input type="checkbox" onclick="toggle(this);"{if !$info['disabled']} checked="checked"{/if} />
      {/if}
      <span class="nolink" style="background-image: url(/modules/{$moduleID}/images/{$id}-tiny.png)">
        {$info['title']}
        <span class="nolinkbuttons"> 
          <a href="#" onclick="moveUp(this); return false;">
            <!--<img src="/modules/{$moduleID}/images/button-up.png" width="26" height="26" class="moveup" alt="Move up"/>-->
            <div class="moveup">&nbsp;</div>
          </a> 
          <a href="#" onclick="moveDown(this); return false;">
            <!--<img src="/modules/{$moduleID}/images/button-down.png" width="26" height="26" class="movedown" alt="Move down"/>-->
            <div class="movedown">&nbsp;</div>
          </a> 
        </span> 
      </span>                   
    </li>
  {/foreach}
</ul>
<div class="formbuttons">
  <a class="formbutton" href="../home/"><div>Return to Home</div></a>
</div>

{include file="findInclude:common/footer.tpl"}
