{extends file="findExtends:common/header.tpl"}

{block name="additionalHeadTags"}
  <meta name="HandheldFriendly" content="true" />
  <meta name="viewport" id="viewport" 
    content="width=device-width, {if $scalable|default:true}user-scalable=yes{else}user-scalable=no, initial-scale=1.0, maximum-scale=1.0{/if}" />
  <link rel="apple-touch-icon" href="/common/images/icon-{$moduleID}.png" />
{/block}
