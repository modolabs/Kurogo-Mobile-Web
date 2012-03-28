{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal">

  
  <h2>{$detailConfig['details']['title']}</h2>
  <p class="smallprint">{$detailConfig['details']['subtitle']}</p>
  
  <div class="somebuttons">
    {include file="findInclude:common/templates/bookmark.tpl" name=$cookieName item=$bookmarkItem exdate=$expireDate}
    {include file="findInclude:common/templates/share.tpl" shareURL=$shareURL shareRemark=$shareRemark shareEmailURL=$shareEmailURL}
  </div>
  
  
  {include file="findInclude:common/templates/tabs.tpl" tabBodies=$detailConfig['tabs']}
</div>


{include file="findInclude:common/templates/footer.tpl"}
