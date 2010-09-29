{extends file="findExtends:common/header.tpl"}

{block name="additionalHeadTags"}
  <meta name="viewport" id="viewport" 
    content="width=device-width, user-scalable={$scalable|default:yes}{if $fixed} initial-scale=1.0, maximum-scale=1.0{/if}" />
  <link rel="apple-touch-icon" href="/common/images/icon-{$moduleID}.png" />
{/block}
