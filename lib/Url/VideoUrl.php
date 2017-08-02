<?php

/**
 * This file is part of the Url package.
 *
 * @author (c) Thomas Blum <thomas@addoff.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Url;

class VideoUrl extends Url
{

    public $aspectRatio = '16:9';
    public $autoPlay = false;

	/**
	 * Returns a embed code.
	 *
	 * @return string
	 */
	public function getEmbedCode()
	{
	    return '<div class="embed-responsive aspect-ratio-' . str_replace(':', 'by', $this->aspectRatio) . '"><iframe src="' . $this->getEmbedUrl() . '"></iframe></div>';
    }

	/**
	 * Returns a embed url.
	 *
	 * @return null|string
	 */
	public function getEmbedUrl()
	{
		if ($this->isVimeo()) {
			return $this->getVimeoEmbedUrl();
		} elseif ($this->isYoutube()) {
			return $this->getYoutubeEmbedUrl();
		}
		return null;
	}

	/*
	 * Returns the video id.
	 *
	 * @return null|string
	 */
	public function getId()
	{
		if ($this->isVimeo()) {
			return $this->getVimeoId();
		} elseif ($this->isYoutube()) {
			return $this->getYoutubeId();
		}
		return null;
	}

	/*
	 * Returns the last element of the url path
	 *
	 * @return string
	 */
	protected function getIdFromUrlPath()
	{
	    $pathParts = explode('/', $this->getPath());
		return end($pathParts);
	}

    /**
     * Returns the Service in lowercase
     *
     * @return null|string
     */
    public function getService()
    {
        $url = $this->getFullUrl();
        if (preg_match('%vimeo%i', $url)) {
			return 'vimeo';
		} elseif (preg_match('%youtube|youtu\.be%i', $url)) {
			return 'youtube';
		}

		return null;
    }

	/**
	 * Returns the thumbnail url from the service.
	 *
	 * @return null|string
	 */
	public function getThumbnailUrl()
	{
		if ($this->isVimeo()) {
			return $this->getVimeoThumbnailUrl();
		} elseif ($this->isYoutube()) {
			return $this->getYoutubeThumbnailUrl();
		}
		return null;
	}

	/**
	 * Returns a Vimeo embed url.
	 *
	 * @return string
	 */
	public function getVimeoEmbedUrl()
	{
		$url = 'http://player.vimeo.com/video/' . $this->getVimeoId() . '?byline=0&amp;portrait=0';
		if ($this->autoPlay) {
		    $url .= '&amp;autoplay=1';
        }
        return $url;
	}

	/**
	 * Returns a Vimeo video id.
	 *
	 * @return string
	 */
	public function getVimeoId()
	{
		return $this->getIdFromUrlPath();
	}

	/**
	 * Returns the Vimeo thumbnail url.
	 *
	 * @return null|string
	 */
	public function getVimeoThumbnailUrl()
	{
	    $data = json_decode(file_get_contents('http://vimeo.com/api/v2/video/' . $this->getVimeoId() . '.json'), true);
	    if (isset($data[0])) {
	        return $data[0]['thumbnail_large'];
        }
        return null;
	}

	/**
	 * Returns a Youtube embed url.
	 *
	 * @return string The url's id
	 */
	public function getYoutubeEmbedUrl()
	{
		$url = 'http://youtube.com/embed/' . $this->getYoutubeId();
		if ($this->autoPlay) {
		    $url .= '?autoplay=1';
        }
        return $url;
	}

	/**
	 * Returns a Youtube video id.
	 *
	 * @return string
	 */
	public function getYoutubeId()
	{
		$urlParamKeys = ['v','vi'];

		foreach ($urlParamKeys as $urlParamKey) {
		    if ($this->hasQueryParameter($urlParamKey)) {
		        return $this->getQueryParameter($urlParamKey);
            }
        }

        return $this->getIdFromUrlPath();
	}

	/**
	 * Returns the Youtube thumbnail url.
	 *
	 * @return string
	 */
	public function getYoutubeThumbnailUrl()
	{
        return 'http://img.youtube.com/vi/' . $this->getYoutubeId(). '/0.jpg';
	}


    /**
     * @return bool
     */
    public function isVimeo()
    {
        return ($this->getService() == 'vimeo');
    }

    /**
     * @return bool
     */
    public function isYoutube()
    {
        return ($this->getService() == 'youtube');
    }


	/**
     * @param $aspectRatio string
     *
     * @throws \rex_exception
     */
    public function setAspectRatio($aspectRatio)
    {
        if (count(explode(':', $aspectRatio)) != 2) {
            throw new \rex_exception('$aspectRatio is expected to define two numbers separate by a colon, "'. $aspectRatio .'" given!');
        }

        $this->aspectRatio = $aspectRatio;
    }

	/**
     * @param $autoPlay bool
     */
    public function setAutoPlay($autoPlay = true)
    {
        $this->autoPlay = $autoPlay;
    }

}
