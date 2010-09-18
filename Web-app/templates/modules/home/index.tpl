{include file="common/header.tpl"|for_device:$device navbar=false}

  <h1{if isset($topItem)} class="roomfornew"{/if}>
    <img src="modules/home/images/harvard-logo-home.png" width="265" height="45" alt="{$siteName}" />
  </h1>

  {if isset($topItem)}
      <div id="new"><a href="../mobile-about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
  {/if}

{include file="common/search.tpl"|for_device:$device placeholder="Search "|cat:#SITE_NAME#}  

  <div id="homegrid">
    {foreach $homeModules as $category => $moduleCategory} 
      {foreach $moduleCategory as $id => $info}
        <div id="{$id}"{if $category == 'secondary'} class="utility"{/if}>
          <a href="../{$info['url']|default:$id}/">
            <img src="modules/{$moduleID}/images/{$id}.png" alt="{$info['title']}" />
            <br/>{$info['title']}
            {if isset($info['subTitle'])}
              <br/><span class="fineprint">{$info['subTitle']}</span>
            {/if}
          {if $id == 'about' && $whatsNewCount > 0}
            <span class="badge">{$whatsNewCount}</span>
          {/if}
          </a>
        </div>
      {/foreach}
      
      {if $i == 0}
        <div class="separator">&nbsp;</div>
      {/if}   
    {/foreach}
  </div>

{include file="common/footer.tpl"|for_device:$device}
