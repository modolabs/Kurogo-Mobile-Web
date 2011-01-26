<div id="share">
	<a onclick="showShare()"><img src="/device/compliant/common/images/share.png"/></a>
	<div id="sharesheet">
		<div id="shareback"> </div>
		<div id="sharedialog">
			<h1>Share this story</h1>
			<ul>
				<li><a href="mailto:?"><img src="/device/compliant/common/images/button-email.png" alt="" width="32" height="32" />Email</a></li>
				<li>
				    <!--
				    <a href="https://graph.facebook.com/oauth/authorize?
                        client_id=API_KEY&
                        redirect_uri=http://{$smarty.server.SERVER_NAME}{$smarty.server.REQUEST_URI}&
                        display=touch">
				        <img src="/device/compliant/common/images/button-facebook.png" 
				        alt="" width="32" height="32" />Facebook
				    </a>
				    -->
				    <img src="/device/compliant/common/images/button-facebook.png" 
			        alt="" width="32" height="32" />Facebook
				</li>
				<li>
				    <a href="http://m.twitter.com/share?url={$urlToBeShared}&amp;text={$shareRemark}&amp;Via=Harvard">
				        <img src="/device/compliant/common/images/button-twitter.png" alt="" width="32" height="32" />Twitter
				    </a>
                </li>
			</ul>
			<a onclick="hideShare()">Cancel</a>
		</div>
	</div>
</div>
