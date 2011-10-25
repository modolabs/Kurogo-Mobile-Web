{foreach $sections as $section}
  {if $section['selected']}
    <option value="{$section['value']}" selected="true">{$section['title']|escape}</option>
  {else}
    <option value="{$section['value']}">{$section['title']|escape}</option>
  {/if}
{/foreach}
