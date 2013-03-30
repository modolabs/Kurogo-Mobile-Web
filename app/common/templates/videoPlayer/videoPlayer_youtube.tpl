<script type="text/javascript" src="{$http_protocol}://www.youtube.com/player_api"></script>
{* List of template parameters to control youtube player behavior *}
{$enableJSAPI       =       $enableJSAPI|default:false}
{$showControls      =      $showControls|default:true}
{$showInfo          =          $showInfo|default:false}
{$modestBranding    =    $modestBranding|default:true}
{$showRelatedVideos = $showRelatedVideos|default:false}
<iframe id="ytplayer" class="kgo-videoplayer-iframe" type="text/html" width="100%" height="100%" scrolling="no" src="{$http_protocol}://www.youtube.com/embed/{$video->getID()}{*
    *}?enablejsapi={if $enableJSAPI}1{else}0{/if}{*
    *}&controls={if $showControls}1{else}0{/if}{*
    *}&showinfo={if $showInfo}1{else}0{/if}{*
    *}&modestbranding={if $modestBranding}1{else}0{/if}{*
    *}&rel={if $showRelatedVideos}1{else}0{/if}{*
    
    The following undocumented parameter is to work around 
    Android devices always thinking they have Flash installed:
    *}{if $platform == 'android'}&html5=1{/if}{* 
  *}" frameborder="0">
</iframe>
