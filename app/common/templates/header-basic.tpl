{extends file="findExtends:common/templates/header.tpl"}

{block name="javascript"}{/block}
{block name="onLoad"}{/block}

{block name="additionalHeadTags"}
  <style type="text/css" media="screen">
    {$fontsizeCSS}
  </style>
{/block}

{block name="navbar"}
  <table cellpadding="0" cellspacing="0" border="0">
    <tr valign="middle">
      <td valign="middle">
        <a href="/home">
          <img src="/common/images/logo.gif" width="{$homelink_image_width|default:35}" height="{$homelink_image_height|default:35}" />
        </a>
      </td>
      <td>
        <h1 id="header">{$pageTitle|sanitize_html:'inline'}</h1>
      </td>
    </tr>
  </table>
{/block}
