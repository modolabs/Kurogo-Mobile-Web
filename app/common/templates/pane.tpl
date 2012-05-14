{block name="inlineCSSBlocks"}
  {foreach $inlineCSSBlocks as $css}
    <style type="text/css" media="screen">
      {$css}
    </style>
  {/foreach}
{/block}

{block name="inlineJavascriptBlocks"}
  {foreach $inlineJavascriptBlocks as $script}
    <script type="text/javascript">
      {$script}
    </script>
  {/foreach}
{/block}

{block name="onOrientationChangeBlocks"}
  {if count($onOrientationChangeBlocks)}
    <script type="text/javascript">
      registerPaneResizeHandler(function () {ldelim}
        {foreach $onOrientationChangeBlocks as $script}{$script}{/foreach}
      {rdelim});
    </script>
  {/if}
{/block}

{block name="content"}
  {$content}
{/block}

{block name="inlineJavascriptFooterBlocks"}
  {foreach $inlineJavascriptFooterBlocks as $script}
    <script type="text/javascript">
      {$script}
    </script>
  {/foreach}
{/block}

{block name="onLoadBlocks"}
  {foreach $onLoadBlocks as $script}
    <script type="text/javascript">
      {$script}
    </script>
  {/foreach}
  {foreach $onOrientationChangeBlocks as $script}
    <script type="text/javascript">
      {$script}
    </script>
  {/foreach}
{/block}
