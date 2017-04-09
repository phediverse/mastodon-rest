<?php

namespace Phediverse\MastodonRest\Resource;

use Phediverse\MastodonRest\Exception\ResolveException;

class Instance
{
    use ResourceTrait;

    protected $name;
    protected $hostname;
    protected $description;
    protected $email;

    protected $isMastodon;

    public function getName() : string
    {
        return $this->resolve()->name;
    }

    public function getHostname() : string
    {
        return $this->resolve()->hostname;
    }

    public function getDescription() : string
    {
        return $this->resolve()->description;
    }

    public function getAdminEmail() : string
    {
        return $this->resolve()->email;
    }

    public function allowsDirectPosts() : bool
    {
        return $this->isMastodon();
    }

    public function isMastodon() : bool
    {
        try {
            return $this->resolve()->isMastodon;
        } catch (ResolveException $e) {
            if ($e->assertWrapsClientException(404)) {
                $this->isMastodon = false;
                return false;
            }
            throw new \LogicException("Assertion that should have happened didn't", 500, $e);
        }
    }

    protected function hydrate(array $data): self
    {
        $this->isMastodon = true;

        $this->name = $data['title'];
        $this->description = $data['description'];
        $this->hostname = $data['uri'];
        $this->email = $data['email'];

        return $this;
    }
}
