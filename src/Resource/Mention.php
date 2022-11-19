<?php

namespace Phediverse\MastodonRest\Resource;

class Mention extends BaseResource
{
    protected $id;
    protected $username;
    protected $url;
    protected $acct;

    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->id = $data["id"];
        $this->username = $data["username"];
        $this->url = $data["url"];
        $this->acct = $data["acct"];

        return $this;
    }

    protected function toArray() : array
    {
        return [
        ];
    }
}
