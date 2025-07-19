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

    /**
     * @var string $aspectRatio The aspect ratio of the video (e.g., '16:9').
     * @api
     *
     */
    public string $aspectRatio = '16:9';
    /**
     * @var bool $autoPlay Whether the video should start playing automatically.
     * @api
     *
     */
    public bool $autoPlay = false;
    /**
     * @var bool $fullscreen Whether fullscreen mode is enabled for the video.
     * @api
     *
     */
    public bool $fullscreen = true;
    /**
     * @var bool $related Whether to show related videos after playback ends.
     * @api
     *
     */
    public bool $related = false;
    /**
     * @var array<string, bool|string> $urlParams Additional URL parameters for the video.
     * @api
     */
    public array $urlParams = [];

    /**
     * @api
     * Returns a embed code.
     *
     * @return string the embed url
     */
    public function getEmbedCode(): string
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
     * @api
     * Returns a embed code.
     *
     * @return string the embed url
     */
    public function getPlayerCode(): string
    {
        $attributes = [
            'class' => 'js-player',
            'data-type' => $this->getService(),
            'data-video-id' => $this->getId(),
        ];

        return '<div'.\rex_string::buildAttributes($attributes).'></div>';
    }

    /**
     * @api
     * Builds a embed url from a video id.
     *
     * @return string|null the embed url
     */
    public function getEmbedUrl(): ?string
    {
        if ($this->isVimeo()) {
            return $this->getVimeoEmbedUrl();
        }
        if ($this->isYoutube()) {
            return $this->getYoutubeEmbedUrl();
        }

        return null;
    }

    /*
     * @api
     * @return null|string Null on failure, the video's id on success
     */
    public function getId(): ?string
    {
        if ($this->isVimeo()) {
            return $this->getVimeoId();
        }
        if ($this->isYoutube()) {
            return $this->getYoutubeId();
        }

        return null;
    }

    /**
     * @api
     * @return null|string Null on failure to match, the service's name on success
     */
    public function getService(): ?string
    {
        $url = method_exists($this, 'getFullUrl') ? $this->getFullUrl() : '';
        if (preg_match('%vimeo%i', $url)) {
            return 'vimeo';
        }
        if (preg_match('%youtube|youtu\\.be%i', $url)) {
            return 'youtube';
        }

        return null;
    }

    /**
     * Get a thumbnail url from a video id.
     *
     * @return null|string the thumbnail url
     * @api
     */
    public function getThumbnailUrl(): ?string
    {
        if ($this->isVimeo()) {
            return $this->getVimeoThumbnailUrl();
        }
        if ($this->isYoutube()) {
            return $this->getYoutubeThumbnailUrl();
        }

        return null;
    }

    /**
     * Builds a Vimeo embed url from a video id.
     *
     * @return string The url's id
     */
    public function getVimeoEmbedUrl(): string
    {
        $params = [
            'byline' => '0',
            'portrait' => '0',
        ];
        if ($this->autoPlay) {
            $params['autoplay'] = '1';
        }
        $params = array_merge($params, $this->urlParams);
        $params = count($params) > 0 ? '?'.\rex_string::buildQuery($params) : '';

        return 'https://player.vimeo.com/video/'.$this->getVimeoId().$params;
    }

    /**
     * @api
     * Parses various vimeo urls and returns video identifier.
     *
     * @return string The url's id
     */
    public function getVimeoId(): string
    {
        return $this->getIdFromUrlPath();
    }

    /**
     * @api
     * Get a Vimeo thumbnail url from a video id.
     *
     * @return null|string The thumbnail url
     */
    public function getVimeoThumbnailUrl(): ?string
    {
        $data = json_decode(file_get_contents('http://vimeo.com/api/v2/video/'.$this->getVimeoId().'.json'), true);
        if (isset($data[0])) {
            return $data[0]['thumbnail_large'];
        }

        return null;
    }

    /**
     * @api
     * Builds a Youtube embed url from a video id.
     *
     * @return string The url's id
     */
    public function getYoutubeEmbedUrl(): string
    {
        $params = [];

        if ($this->autoPlay) {
            $params['autoplay'] = '1';
        }

        $params['rel'] = $this->related ? '1' : '0';
        $params = array_merge($params, $this->urlParams);
        $params = count($params) > 0 ? '?'.\rex_string::buildQuery($params) : '';

        return 'https://youtube.com/embed/'.$this->getYoutubeId().$params;
    }

    /**
     * @api
     * Parses various youtube urls and returns video identifier.
     *
     * @return string the url's id
     */
    public function getYoutubeId(): string
    {
        $url = method_exists($this, 'getUrl') ? $this->getUrl() : '';
        $urlParamKeys = ['v', 'vi'];

        foreach ($urlParamKeys as $urlParamKey) {
            if (method_exists($this, 'hasQueryParameter') && $this->hasQueryParameter($urlParamKey)) {
                return method_exists($this, 'getQueryParameter') ? $this->getQueryParameter($urlParamKey) : '';
            }
        }

        return $this->getIdFromUrlPath();
    }

    /**
     * @api
     * Get a Youtube thumbnail url from a video id.
     *
     * @return string The thumbnail url
     */
    public function getYoutubeThumbnailUrl(): string
    {
        return 'https://img.youtube.com/vi/'.$this->getYoutubeId().'/0.jpg';
    }

    /**
     * @api
     * @return bool
     */
    public function isVimeo(): bool
    {
        return $this->getService() === 'vimeo';
    }

    /**
     * @api
     * @return bool
     */
    public function isYoutube(): bool
    {
        return $this->getService() === 'youtube';
    }

    /**
     * @api
     * @param $aspectRatio string
     *
     * @throws \rex_exception
     */
    public function setAspectRatio(string $aspectRatio): void
    {
        if (count(explode(':', $aspectRatio)) !== 2) {
            throw new \rex_exception('$aspectRatio is expected to define two numbers separate by a colon, "'.$aspectRatio.'" given!');
        }

        $this->aspectRatio = $aspectRatio;
    }

    /**
     * @api
     * @param $autoPlay bool
     */
    public function setAutoPlay(bool $autoPlay = true): void
    {
        $this->autoPlay = $autoPlay;
    }

    /**
     * @api
     * @param $fullscreen bool
     */
    public function setFullscreen(bool $fullscreen = true): void
    {
        $this->fullscreen = $fullscreen;
    }

    /**
     * @api
     * @param $related bool
     */
    public function setRelated(bool $related = true): void
    {
        $this->related = $related;
    }

    /**
     * @api
     * @param $key string
     * @param $value bool|string
     */
    public function addUrlParam(string $key, bool|string $value): void
    {
        $this->urlParams[$key] = $value;
    }

    /*
     * @return string The last element of the url path
     */
    protected function getIdFromUrlPath(): string
    {
        $pathParts = explode('/', $this->getPath());

        return end($pathParts);
    }
}
