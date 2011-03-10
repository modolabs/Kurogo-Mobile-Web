{strip}
{foreach $feeds as $key=>$item}     
  <ul class="nav feedItem">
        <li>
         <label>Page</label>
         {$key}
      </li>   
      <li>
         <label>Title</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][TITLE]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][TITLE]" value="{$item.TITLE|escape}" />
      </li>
      <li>
         <label>Type</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][CONTENT_TYPE]" value="text" />
         {html_options id="content_admin_type[$key]" class="content_admin_type" name="moduleData[feeds][$key][CONTENT_TYPE]" options=$content_types selected=$item.CONTENT_TYPE}
      </li>
      <li class="content_admin_optional content_html_url content_rss">
         <label>URL</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][BASE_URL]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][BASE_URL]" value="{$item.BASE_URL|escape}" />
      </li>
      <li class="content_admin_optional content_html_url">
         <label>HTML id</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][HTML_ID]" value="text" />
         <input type="text" name="moduleData[feeds][{$key}][HTML_ID]" value="{$item.HTML_ID|escape}" />
      </li>
      <li class="content_admin_optional content_html">
         <label>Content</label>
         <input type="hidden" name="_type[moduleData][feeds][{$key}][CONTENT_HTML]" value="paragraph" />
         <textarea name="moduleData[feeds][{$key}][CONTENT_HTML]" cols="28" rows="8">{$item.CONTENT_HTML|escape}</textarea>
      </li>
      <li>
         <a href="{$feedURL}&amp;removeFeed={$key}" onclick="return(confirm('Are you sure you want to delete this feed?'));">Remove Feed</a>
      </li>
  </ul>
{/foreach}
{if $showNew}
  <ul class="nav feedItem">
      <li>
         <label>New Page</label>
         <input type="hidden" name="_type[addFeedData][LABEL]" value="text" />
         <input type="text" name="addFeedData[LABEL]" />
      </li>   
      <li>
         <label>Title</label>
         <input type="hidden" name="_type[addFeedData][TITLE]" value="text" />
         <input type="text" name="addFeedData[TITLE]" />
      </li>
      <li>
         <label>Type</label>
         <input type="hidden" name="_type[addFeedData][CONTENT_TYPE]" value="text" />
         {html_options class="content_admin_type" name="addFeedData[CONTENT_TYPE]" options=$content_types first='--Type--'}
      </li>
      <li class="content_admin_optional content_html_url content_rss">
         <label>URL</label>
         <input type="hidden" name="_type[addFeedData][BASE_URL]" value="text" />
         <input type="text" name="addFeedData[BASE_URL]" />
      </li>
      <li class="content_admin_optional content_html_url">
         <label>HTML id</label>
         <input type="hidden" name="_type[addFeedData][HTML_ID]" value="text" />
         <input type="text" name="addFeedData[HTML_ID]" />
      </li>
      <li class="content_admin_optional content_html">
         <label>Content</label>
         <input type="hidden" name="_type[addFeedData][CONTENT_HTML]" value="paragraph" />
         <textarea name="addFeedData[CONTENT_HTML]" cols="28" rows="8"></textarea>
      </li>
      <li>
         <input type="submit" name="addFeed" value="Add Feed" />
      </li>
      
  </ul>
{/if}
{/strip}
