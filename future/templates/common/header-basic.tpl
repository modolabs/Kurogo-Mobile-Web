{extends file="findExtends:common/header.tpl"}

{block name="javascript"}{/block}
{block name="onLoad"}{/block}

{block name="additionalHeadTags"}
    <style type="text/css" media="screen">
      {$fontsizeCSS}
    </style>
{/block}

{block name="header"}
  <table cellpadding="0" cellspacing="0" border="0">
    <tr valign="middle">
      <td>
        <img src="/common/images/logo.gif" width="35" height="35"/>
      </td>
      <td>
        <h1 id="header">{$pageTitle}</h1>
      </td>
    </tr>
  </table>
{/block}
