

<script src="http://html5.kaltura.org/js"></script>
{* <!-- doc: http://html5video.org/wiki/Getting_Started_with_Kaltura_HTML5 -->
<!-- Skeleton video object -->
<!-- 
		<video controls poster="./media/sintel.jpg" width="720" height="306">
		  <source type="video/mp4" src="./media/sintel.mp4">
		  <source type="video/ogg" src="./media/sintel.ogg">
		  <track kind="subtitles" srclang="en" src="./media/sintel_en.srt"> 
		  <track kind="subtitles" srclang="ru" src="./media/sintel_ru.srt"> 
		</video> 
--> *}

<video controls class="kgo-videoplayer-object" poster="{$video->getStillFrameImage()}" width="100%">
	{foreach $video->getVideoSources() as $source}
		<source src="{$source['url']}" />
	{/foreach}

	{* <!-- TODO:::srclang should not always be en, will need to convert from KalturaLanguage type -->
	<!-- http://www.kaltura.com/api_v3/xsdDoc/?type=syndication#element-subTitle -->
	<!-- http://www.kaltura.com/api_v3/testmeDoc/index.php?object=KalturaLanguage --> *}

	{foreach $video->getSubtitleTracks() as $track}
		<track kind="subtitles" srclang="en" src="{$track['href']}" />
	{/foreach}

</video>