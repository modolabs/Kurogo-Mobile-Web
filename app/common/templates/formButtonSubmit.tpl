{*
 * A style-able submit button for user interaction.
 * 
 * Make sure that any onclick Javascript is not necessary for the form to work 
 * so that the basic pagetype functions properly (eg: form validation).
 *}
<span class="formbuttoncontainer">
  <input type="submit" name="{$buttonName|default:$buttonTitle}" class="formbutton" value="{$buttonTitle}"{if $buttonOnclick} onclick="{$buttonOnclick}"{/if} />
</span>
