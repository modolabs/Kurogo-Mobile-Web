<div id="share">
  <a onclick="showShare()"><img src="/common/images/share.png"/></a>
  <div id="sharesheet" style="display:none">
    <div id="shareback"> </div>
    <div id="sharedialog">
      <h1>Share this story</h1>
      <ul>
        {if $shareEmailURL}
          <li>
            <a class="sharelink" href="{$shareEmailURL}"><img src="/common/images/button-email.png" alt="" width="32" height="32" />Email</a>
          </li>
        {/if}
        {if $shareURL}
          <li>
            <a class="sharelink" href="http://m.facebook.com/sharer.php?u={$shareURL}&t={$shareRemark}"><img src="/common/images/button-facebook.png" alt="" width="32" height="32" />Facebook</a>
          </li>
          <li>
            <a class="sharelink" href="http://twitter.com/intent/tweet?url={$shareURL}&amp;text={$shareRemark}"><img src="/common/images/button-twitter.png" alt="" width="32" height="32" />Twitter</a>
          </li>
        {/if}
			</ul>
      <div class="formbuttons">
        <a class="formbutton" onclick="hideShare()"><div>Cancel</div></a>
      </div>
		</div>
	</div>
</div>
