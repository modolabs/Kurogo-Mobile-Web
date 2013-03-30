{*
 * A link which appears as a style-able button for user interaction.
 * 
 * If your module is compliant/tablet-only you only need to specify 
 * buttonOnclick.  If your module supports the basic pagetype you 
 * must provide a buttonURL and make sure that any onclick Javascript  
 * is not necessary for the link to work.  
 *}
<a href="{if $buttonURL}{$buttonURL}{else}javascript:void(0);{/if}" class="formbutton"{if $buttonOnclick} onclick="{$buttonOnclick}"{/if}>
  <div>{$buttonTitle}</div>
</a>
