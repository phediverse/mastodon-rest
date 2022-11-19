<?php

namespace Phediverse\MastodonRest\Resource;

class Account extends BaseResource
{
    protected $id;

    protected $username;
    protected $acct;

    protected $displayName;
    protected $bio;
    protected $instanceHostname = false;

    protected $profileUrl;
    protected $avatarUrl;
    protected $headerImageUrl;

    protected $requiresFollowApproval;

    /** @var \DateTimeImmutable */
    protected $createdAt;

    protected $followerCount;
    protected $followingCount;
    protected $statusCount;

    public function getId() : int
    {
        return $this->resolve()->id;
    }

    public function getDisplayName() : string
    {
        return $this->resolve()->displayName;
    }

    public function getBio() : string
    {
        return $this->resolve()->bio ?: '';
    }

    // Account name related functions

    public function isLocal() : bool
    {
        $this->resolve();
        return $this->username == $this->acct;
    }

    public function getAcct() : string
    {
        return $this->resolve()->acct;
    }

    public function getUsername() : string
    {
        return $this->resolve()->username;
    }

    /**
     * @return string the full account name, even if they're on the current instance
     */
    public function getQualifiedAccountName() : string
    {
        $this->resolve();
        return $this->acct != $this->username ? $this->acct : ($this->username . '@' . $this->getInstanceHostname());
    }

    // instance capabilities related methods

    public function getInstanceHostname() : string
    {
        if ($this->instanceHostname === false) {
            $this->resolve();

            $this->instanceHostname = $this->username != $this->acct ?
                @end(explode('@', $this->acct)) : $this->getClient()->getInstanceHostname();
        }

        return $this->instanceHostname;
    }

    public function getInstance() : Instance
    {
        return $this->getClient()->getInstance($this->isLocal() ? null : $this->getInstanceHostname());
    }

    public function supportsDirectPosts() : bool
    {
        return $this->getInstance()->supportsDirectPosts();
    }

    // URLs

    public function getProfileUrl() : string
    {
        return $this->resolve()->profileUrl;
    }

    public function getAvatarUrl() : string
    {
        return $this->resolve()->avatarUrl ?: '';
    }

    public function getHeaderImageUrl() : string
    {
        return $this->resolve()->headerImageUrl ?: '';
    }

    // general permissions

    /**
     * @return bool uses the "locked" API response value; figured this method name would be more descriptive
     */
    public function requiresFollowApproval() : bool
    {
        return $this->resolve()->requiresFollowApproval;
    }

    public function isLocked() : bool
    {
        return $this->requiresFollowApproval();
    }

    // Dates!

    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->resolve()->createdAt;
    }

    // Counts

    /**
     * If you're connected to the user's home instance, this returns the number of followers the user has across
     * the fediverse. If not, only counts followers whose home instance is this one.
     *
     * @return int
     */
    public function getFollowerCount() : int
    {
        return $this->resolve()->followerCount;
    }

    /**
     * If you're connected to the user's home instance, this returns the number of people this user follows across
     * the fediverse. If not, only counts accounts that the user follows on this instance.
     *
     * @return int
     */
    public function getFollowingCount() : int
    {
        return $this->resolve()->followingCount;
    }

    /**
     * If you're connected to the user's home instance, this returns the number of statuses the user has posted for
     * all time. If not, only counts statuses that have been syndicated to this instance for that remote user.
     *
     * @return int
     */
    public function getStatusCount() : int
    {
        return $this->resolve()->statusCount;
    }

    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->id = $data['id'];

        $this->username = $data['username'];
        $this->acct = $data['acct'];

        $this->displayName = $data['display_name'];
        $this->bio = $data['note'];

        $this->profileUrl = $data['url'];
        $this->avatarUrl = $data['avatar'];
        $this->headerImageUrl = $data['header'];

        $this->requiresFollowApproval = $data['locked'];

        $this->createdAt = new \DateTimeImmutable($data['created_at']);

        $this->followerCount = $data['followers_count'];
        $this->followingCount = $data['following_count'];
        $this->statusCount = $data['statuses_count'];

        return $this;
    }

    protected function toArray() : array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'acct' => $this->acct,
            'display_name' => $this->displayName,
            'note' => $this->bio,
            'url' => $this->profileUrl,
            'avatar' => $this->avatarUrl,
            'header' => $this->headerImageUrl,
            'locked' => $this->requiresFollowApproval,
            'created_at' => $this->createdAt->format(\DateTime::ATOM),
            'followers_count' => $this->followerCount,
            'following_count' => $this->followingCount,
            'statuses_count' => $this->statusCount
        ];
    }
}
