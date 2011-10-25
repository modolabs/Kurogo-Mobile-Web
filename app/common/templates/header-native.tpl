{extends file="findExtends:common/templates/header.tpl"}

{block name="javascript"}
  {$smarty.const.URL_BASE = ''}
  <script type="text/javascript">var KUROGO_SERVER='__KUROGO_SERVER_URL_BASE__';</script>
  {$GOOGLE_ANALYTICS_ID = ''}
  {$PERCENT_MOBILE_ID = ''}
  {$smarty.block.parent}
{/block}

{block name="viewportHeadTag"}
  {$scalable = false}
  {$smarty.block.parent}
{/block}

{block name="homeScreenIcon"}{/block}

{block name="navbar"}{/block}
