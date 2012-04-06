<div class="actionbuttons">
    {foreach $actionButtons as $button}
        <div class="actionbutton {$button['service']}">
            <a href="{$button['url']}" ontouchstart="this.className='pressedaction'" ontouchend="this.classname=''">{$button['title']}</a>
        </div>
    {/foreach}
    <div class="clear"></div>
</div>
