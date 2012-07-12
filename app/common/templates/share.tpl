{if $shareEmailURL || $shareURL}
<div id="share">
  <a onclick="showShare()"><img src="/common/images/share.png" width="44" height="38" /></a>
  <div id="sharesheet" style="display:none">
    <div id="shareback"> </div>
    <div id="sharedialog">
      <h1>{$shareTitle|default:{"SHARE_THIS_ITEM"|getLocalizedString}}</h1>
      <ul>
        {if $shareEmailURL}
          <li>
            <a class="sharelink" href="{$shareEmailURL}"><img src="/common/images/button-email.png" alt="" width="32" height="32" />{"SHARE_OPTION_EMAIL"|getLocalizedString}</a>
          </li>
        {/if}
        {if $shareURL}
          <li>
            <a class="sharelink" href="http://m.facebook.com/sharer.php?u={$shareURL|escape:'url'}&t={$shareRemark|escape:'url'}"><img src="/common/images/button-facebook.png" alt="" width="32" height="32" />{"SHARE_OPTION_FACEBOOK"|getLocalizedString}</a>
          </li>
          <li>
            <a class="sharelink" href="http://twitter.com/share?url={$shareURL|escape:'url'}&text={$shareRemark|escape:'url'}"><img src="/common/images/button-twitter.png" alt="" width="32" height="32" />{"SHARE_OPTION_TWITTER"|getLocalizedString}</a>
          </li>
        {/if}
			</ul>
      <div class="formbuttons">
        {include file="findInclude:common/templates/formButtonLink.tpl" buttonTitle={"BUTTON_CANCEL"|getLocalizedString} buttonOnclick="hideShare()"}
      </div>
		</div>
	</div>
</div>
{/if}
