{extends file="findExtends:modules/home/templates/index.tpl"}

{block name="homeSpringboard"}
{$allModules = array_merge($modules['primary'], $modules['secondary'])}
{include file="findInclude:common/templates/springboard.tpl" springboardItems=$allModules springboardID="homegrid"}
{/block}

