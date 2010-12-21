{strip}
{foreach $feeds as $key=>$item}     
  <ul class="nav">
  {if $showFeedLabels}
        <li>
         <label>Label</label>
         {$key}
      </li>   
      {/if}
      <li>
         <label>Title</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][TITLE]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][TITLE]" value="{$item.TITLE|escape}" />
      </li>
      <li>
         <label>URL</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][BASE_URL]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][BASE_URL]" value="{$item.BASE_URL|escape}" />
      </li>
{if $feedFields}
      <li>
         <a href="#" onclick='toggleAdvancedFields()'>Show Advanced Fields</a>
         <ul class="advancedFeedFields">
         {foreach $feedFields as $field=>$label}
      <li>
         <label>{$label}</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][{$field}]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][{$field}]" value="{$item.$field|escape}" />
      </li>
{/foreach}
         </ul>

      </li>
{/if}
      {if $showDelete}
      <li>
         <a href="{$feedURL}&amp;removeFeed={$key}" onclick="return(confirm('Are you sure you want to delete this feed?'));">Remove Feed</a>
      </li>
      {/if}
      
  </ul>
{/foreach}
{if $showNew}
  <ul class="nav">
  {if $showFeedLabels}
        <li>
         <label>Label</label>
         <input type="hidden" name="_type[addFeedData][LABEL]" value="text" />
         <input type="text" name="addFeedData[LABEL]" />
      </li>   
      {/if}
      <li>
         <label>Title</label>
         <input type="hidden" name="_type[addFeedData][TITLE]" value="text" />
         <input type="text" name="addFeedData[TITLE]" />
      </li>
      <li>
         <label>URL</label>
         <input type="hidden" name="_type[addFeedData][BASE_URL]" value="text" />
         <input type="text" name="addFeedData[BASE_URL]" />
      </li>
      <li>
         <input type="submit" name="addFeed" value="Add Feed" />
      </li>
      
  </ul>
{/if}
{/strip}
