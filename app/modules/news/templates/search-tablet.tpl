{extends file="findExtends:modules/news/templates/search.tpl"}

{block name="newsHeader"}
  <div id="newsHeader">
    {include file="findInclude:common/templates/search.tpl" extraArgs=$extraArgs}
  </div>
{/block}

{block name="stories"}
  <div id="tabletNews">
    <div id="stories">
      {include file="findInclude:modules/news/templates/stories.tpl"}
    </div>
    <div id="storyDetailWrapper">
      <div id="storyDetail">
      </div><!-- storyDetail -->
    </div><!-- storyDetailWrapper -->
  </div><!-- tabletNews -->
{/block}
