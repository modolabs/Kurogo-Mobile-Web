{include file="findInclude:common/templates/header.tpl"}

<div class="focal author"><img src="{$authorImageURL}" /> {$authorName}</div>

<div class="nonfocal created">{$postDate}</div>
<p class="focal">{$postBody}</p>

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$postLinks subTitleNewline=true}


{include file="findInclude:common/templates/footer.tpl"}
