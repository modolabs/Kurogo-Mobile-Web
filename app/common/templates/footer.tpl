{if !$webBridgeAjaxContentLoad && !$ajaxContentLoad}
  
  {block name="kgoFooterLinks"}
    {if !$hideFooterLinks}
      <div id="footerlinks">
        <a href="#top">{$footerBackToTop}</a> | <a href="{$homeLink}">{$homeLinkText}</a>
      </div>
    {/if}
  {/block}

  {block name="kgoFooterLogin"}
    {include file="findInclude:common/templates/page/login.tpl"}
  {/block}
  
  {block name="kgoFooterFontSizeSelection"}{/block}
  
  {block name="kgoFooterCredits"}
    <div id="footer">
      {include file="findInclude:common/templates/page/credits.tpl"}
    </div>
  {/block}

  {block name="kgoFooterDeviceDetection"}
    {include file="findInclude:common/templates/page/deviceDetection.tpl"}
  {/block}

  {block name="kgoFooterModuleDebug"}
    {include file="findInclude:common/templates/page/moduleDebug.tpl"}
  {/block}

  {capture name="kgoFooterJavascript" assign="kgoFooterJavascript"}
    {block name="kgoFooterJavascriptBlocks"}
      {foreach $inlineJavascriptFooterBlocks as $script}
        <script type="text/javascript">
          {$script} 
        </script>
      {/foreach}
    {/block}
    
    {block name="kgoFooterJavascriptAnalytics"}
      {if strlen($GOOGLE_ANALYTICS_ID)}
        <script type="text/javascript">
          (function() {ldelim}
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          {rdelim})();
        </script>
      {/if}
    {/block}
  {/capture}
  
  {block name="kgoFooterJavascript"}
    {$kgoFooterJavascript}
  {/block}
  
  {block name="kgoFooterContainerEnd"}
    </div> <!--container -->
  </div> <!--nonfooternav -->
  {/block}
  
  {block name="kgoFooterBelowContent"}
  {/block}
  </body>
  </html>
{else}
  {block name="kgoFooterAJAXContent"}
    <script type="text/javascript">
      {foreach $inlineJavascriptFooterBlocks as $script}
        {$script}
      {/foreach}
      
      {foreach $onLoadBlocks as $script}
        {$script}
      {/foreach}
    
      {if count($onOrientationChangeBlocks)}
        addOnOrientationChangeCallback(function () {ldelim}
          {foreach $onOrientationChangeBlocks as $script}
            {$script}
          {/foreach}
        {rdelim});
      {/if}
      
      onOrientationChange();
    </script>
  {/block}
{/if}
