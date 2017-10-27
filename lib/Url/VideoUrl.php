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
    public $fullscreen = true;
    public $related = false;

    /**
     * Returns a embed code.
     *
     * @return string the embed url
     */
    public function getEmbedCode()
    {
        $attributes = [
            'src' => $this->getEmbedUrl(),
        ];
        if ($this->fullscreen) {
            $attributes['allowfullscreen'] = 'allowfullscreen';
        }

        return '<div class="embed-responsive aspect-ratio-'.str_replace(':', 'by', $this->aspectRatio).'"><iframe '.\rex_string::buildAttributes($attributes).'></iframe></div>';
    }

    /**
     * Returns a embed code.
     *
     * @return string the embed url
     */
    public function getPlayerCode()
    {
        $attributes = [
            'class' => 'js-player',
            'data-type' => $this->getService(),
            'data-video-id' => $this->getId(),
        ];

        return '<div'.\rex_string::buildAttributes($attributes).'></div>';
    }


    /**
     * Builds a embed url from a video id.
     *
     * @return string the embed url
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
     * @return null|string Null on failure, the video's id on success
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
     * @return string The last element of the url path
     */
    protected function getIdFromUrlPath()
    {
        $pathParts = explode('/', $this->getPath());

        return end($pathParts);
    }

    /**
     * @return null|string Null on failure to match, the service's name on success
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
     * Get a thumbnail url from a video id.
     *
     * @return null|string the thumbnail url
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
     * Builds a Vimeo embed url from a video id.
     *
     * @return string The url's id
     */
    public function getVimeoEmbedUrl()
    {
        $params = [
            'byline' => '0',
            'portrait' => '0',
        ];
        if ($this->autoPlay) {
            $params['autoplay'] = '1';
        }
        $params = count($params) ? '?'.\rex_string::buildQuery($params) : '';

        return 'https://player.vimeo.com/video/'.$this->getVimeoId().$params;
    }

    /**
     * Parses various vimeo urls and returns video identifier.
     *
     * @return string The url's id
     */
    public function getVimeoId()
    {
        return $this->getIdFromUrlPath();
    }

    /**
     * Get a Vimeo thumbnail url from a video id.
     *
     * @return null|string The thumbnail url
     */
    public function getVimeoThumbnailUrl()
    {
        $data = json_decode(file_get_contents('http://vimeo.com/api/v2/video/'.$this->getVimeoId().'.json'), true);
        if (isset($data[0])) {
            return $data[0]['thumbnail_large'];
        }

        return null;
    }

    /**
     * Builds a Youtube embed url from a video id.
     *
     * @return string The url's id
     */
    public function getYoutubeEmbedUrl()
    {
        $params = [];

        if ($this->autoPlay) {
            $params['autoplay'] = '1';
        }

        $params['rel'] = $this->related ? '1' : '0';

        $params = count($params) ? '?'.\rex_string::buildQuery($params) : '';

        return 'https://youtube.com/embed/'.$this->getYoutubeId().$params;
    }

    /**
     * Parses various youtube urls and returns video identifier.
     *
     * @return string the url's id
     */
    public function getYoutubeId()
    {
        $url = $this->getUrl();
        $urlParamKeys = ['v', 'vi'];

        foreach ($urlParamKeys as $urlParamKey) {
            if ($this->hasQueryParameter($urlParamKey)) {
                return $this->getQueryParameter($urlParamKey);
            }
        }

        return $this->getIdFromUrlPath();
    }

    /**
     * Get a Youtube thumbnail url from a video id.
     *
     * @return string The thumbnail url
     */
    public function getYoutubeThumbnailUrl()
    {
        return 'https://img.youtube.com/vi/'.$this->getYoutubeId().'/0.jpg';
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
            throw new \rex_exception('$aspectRatio is expected to define two numbers separate by a colon, "'.$aspectRatio.'" given!');
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


    /**
     * @param $fullscreen bool
     *
     * @throws \rex_exception
     */
    public function setFullscreen($fullscreen = true)
    {
        $this->fullscreen = $fullscreen;
    }


    /**
     * @param $related bool
     *
     * @throws \rex_exception
     */
    public function setRelated($related = true)
    {
        $this->related = $related;
    }

}
