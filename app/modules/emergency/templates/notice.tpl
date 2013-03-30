{include file="findInclude:common/templates/header.tpl"}

<div class="notice">
  {block name="slugline"}
  <h1 class="slugline">{$emergencyNotice['title']}</h1>
  {/block}
  <p class="byline">
  {block name="byline"}
    {if $emergencyNotice['date']}
      <span class="pubdate">{$emergencyNotice['date']|date_format:$dateFormat} {$emergencyNotice['date']|date_format:$timeFormat}</span>
    {/if}
  {/block}
  </p>    
  
  <div id="notice">
    {block name="body"}    
    <span id="noticebody">
      {$emergencyNotice.body}
    </span>
    {/block}
  </div><!--#notice-->
</div><!-- .notice -->

{include file="findInclude:common/templates/footer.tpl"}
