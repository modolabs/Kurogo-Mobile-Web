<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

 class KalturaRetriever extends URLDataRetriever  
 {

 	protected $DEFAULT_PARSER_CLASS	= 'KalturaDataParser';
 	protected $DEFAULT_BASE_URL = 'http://www.kaltura.com/api_v3/getFeed.php';

 	private $param_baseUrl;
 	private $param_partnerId;
 	private $param_feedId;

 	protected function init($args) {
        parent::init($args);

        if (isset($args['BASE_URL'])) {
        	$this->param_baseUrl	= $args['BASE_URL'];
        } else {
        	$this->param_baseUrl	= $this->DEFAULT_BASE_URL;
        }

        if (!isset($args['PARTNER_ID'])) {
        	throw new KurogoConfigurationException("Kaltura PARTNER_ID is a required configuration");
        }

        if (!isset($args['FEED_ID'])) {
        	throw new KurogoConfigurationException("Kaltura FEED_ID is a required configuration");
        }

        $this->param_partnerId 	= $args['PARTNER_ID'];
        $this->param_feedId 	= $args['FEED_ID'];

        $this->setStandardParameters();

        $json = $this->getData();
    }

    private function setStandardParameters() {

    	$this->setBaseUrl($this->param_baseUrl);

    	$this->addParameter('partnerId', $this->param_partnerId);
    	$this->addParameter('feedId', $this->param_feedId);
    }

 }


class KalturaDataParser extends JSONDataParser
{

	public function parseEntry($entry) {
		if (isset($entry['entryId'])) {
			$video = new KalturaVideoObject();
			$video->setID($entry['entryId']);
			$video->setTitle($entry['title']);
			$video->setDescription($entry['description']);

			$ts = $entry['updatedAt'];		// there is also a 'createdAt' property that could be used instead
			$video->setPublished(new DateTime("@$ts"));
			$video->setStillFrameImage($entry['thumbnail']['url']);
			$video->setImage($entry['thumbnailUrl']['url']);
			$video->setDuration($entry['media']['duration'] / 1000); 		// Need to convert to seconds

			$tags = Kurogo::arrayVal($entry,'tags', array());
			$video->setTags(Kurogo::arrayVal($tags,'tag', array()));							// currently an array of tags
			$video->setVideoSources($entry['itemContent']); 				
			$video->setSubtitleTracks($entry['subTitles']);

			return $video;
		}
	}

	public function parseData($data) {
		if ( $channel = parent::parseData($data) ) {

			if (isset($channel['channel']['items'])) {
				$videos = array();
				$this->setTotalItems(count($channel['channel']['items']));
				foreach ($channel['channel']['items'] as $entry) {
					$videos[] = $this->parseEntry($entry);
				}
				return $videos;
			}
		}
	}
}

class KalturaVideoObject extends VideoObject
{
	protected $type = 'kaltura';
	protected $videoSources;
	protected $subtitleTracks;

	public function setVideoSources($items) {
		$this->videoSources = $items;
	}

	public function getVideoSources() {
		return $this->videoSources;
	}

	public function setSubtitleTracks($tracks) {
		$this->subtitleTracks = $tracks;
	}

	public function getSubtitleTracks(){
		return $this->subtitleTracks;
	}

	public static function getCodecAttributesForSource($source) {
		// source is a single element of the videoSources array

		$codecs = array();
		if (isset($source['videoCodec']) && !empty($source['videoCodec'])) {
			$codecs[] = $source['videoCodec'];
		}

		if (isset($source['audioCodec']) && !empty($source['audioCodec'])) {
			$codecs[] = $source['audioCodec'];
		} 

		$attributeString = implode(', ', $codecs);
		return $attributeString;
	}

}
