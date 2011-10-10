{extends file="findExtends:modules/news/templates/search.tpl"}

{block name="newsHeader"}
  <div id="newsHeader" class="splitview-header">
    {include file="findInclude:common/templates/search.tpl" extraArgs=$extraArgs}
  </div>
{/block}

{block name="stories"}
  <div id="tabletNews" class="splitview">
    <div id="stories" class="listcontainer">
      {include file="findInclude:modules/news/templates/stories.tpl"}
    </div>
    <div id="storyDetailWrapper" class="splitview-detailwrapper">
      <div id="storyDetail">
      </div><!-- storyDetail -->
    </div><!-- storyDetailWrapper -->
  </div><!-- tabletNews -->
{/block}
