<?php

namespace Phediverse\MastodonRest\Resource;

class Emoji extends BaseResource
{
    protected $shortCode;
    protected $url;
    protected $staticUrl;
    protected $visibleInPicker;

    public function getShortCode() {
        return $this->shortCode;
    }
    public function getUrl() {
        return $this->url;
    }
    public function getStaticUrl() {
        return $this->staticUrl;
    }
    public function getVisibleInPicker() {
        return $this->visibleInPicker;
    }
    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->shortCode = $data["shortcode"];
        $this->url = $data["url"];
        $this->staticUrl = $data["static_url"];
        $this->visibleInPicker = $data["visible_in_picker"];
        return $this;
    }

    protected function toArray() : array
    {
        return [
            "shortcode" => $this->shortCode,
            "url" => $this->url,
            "staticUrl" => $this->staticUrl,
            "visibleInPicker" => $this->visibleInPicker
        ];
    }
}
