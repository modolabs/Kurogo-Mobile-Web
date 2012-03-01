{extends file="findExtends:modules/photos/templates/album.tpl"}

{block name="springboard"}
  {include file="findInclude:modules/photos/templates/springboard-basic.tpl" springboardItems=$photos}
{/block}
