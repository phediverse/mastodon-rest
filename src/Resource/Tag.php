<?php

namespace Phediverse\MastodonRest\Resource;

class Tag extends BaseResource
{
    protected $name;
    protected $url;

    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->name = $data["name"];
        $this->url = $data["url"];

        return $this;
    }

    protected function toArray() : array
    {
        return [
        ];
    }
}
