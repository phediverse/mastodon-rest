<?php

namespace Phediverse\MastodonRest\Resource;

class Metadata extends BaseResource
{
    protected $width;
    protected $height;
    protected $size;
    protected $aspect;

    public function getWidth() : int {
        return $this->width;
    }
    public function getHeight() : int {
        return $this->height;
    }
    public function getSize() : String {
        return $this->size;
    }
    public function getAspect() : float {
        return $this->aspect;
    }

    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->width = $data["width"];
        $this->height = $data["height"];
        $this->size = $data["size"];
        $this->aspect = $data["aspect"];
        return $this;
    }

    protected function toArray() : array
    {
        return [
            "width" => $this->width,
            "height" => $this->height,
            "size" => $this->size,
            "aspect" => $this->aspect
        ];
    }
}
