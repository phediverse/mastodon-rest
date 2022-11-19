<?php

namespace Phediverse\MastodonRest\Resource;

class Status extends BaseResource
{
    protected $id;
    protected $inReplyToId;
    protected $inReplyToAccountId;

    protected $sensitive = false;
    protected $visibility;
    protected $spoilerText;

    protected $language;
    protected $uri;
    protected $url;
    protected $repliesCount = 0;
    /**
     * Set to a given value when the status is a reblog
     */
    protected $reblog;
    protected $reblogged = false;
    protected $reblogsCount = 0;
    protected $favourited = false;
    protected $favouritesCount = 0;
    protected $muted = false;
    protected $bookmarked = false;

    protected $content = "";

    protected $editedAt;
    protected $createdAt;

    protected $account;
    protected $mediaAttachments = array();
    protected $mentions = array();
    protected $tags = array();
    protected $emojis = array();

    public function getId() : String {
        return $this->id;
    }

    public function getContent() : String {
        return $this->content;
    }

    public function getEditedAt() : \DateTimeImmutable {
        return $this->editedAt;
    }

    public function getCreatedAt() : \DateTimeImmutable {
        return $this->createdAt;
    }

    public function getUri() : String {
        return $this->uri;
    }

    public function getUrl() : String {
        return $this->url;
    }

    public function getAccount() : Account {
        return $this->account;
    }

    public function getMedias() : array {
        return $this->mediaAttachments;
    }

    public function getReblog() : Status|null {
        return $this->reblog;
    }

    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->id = $data["id"];
        if($data["reblog"]) {
            $this->reblog = Status::fromData($data["reblog"], $this->client);
        }
        $this->createdAt = new \DateTimeImmutable($data['created_at']);
        $this->editedAt = new \DateTimeImmutable($data['edited_at']);
        $this->inReplyToId = $data["in_reply_to_id"];
        $this->inReplyToAccountId = $data["in_reply_to_account_id"];
        $this->sensitive = $data["sensitive"];
        $this->spoilerText = $data["spoiler_text"];
        $this->visibility = $data["visibility"];
        $this->language = $data["language"];
        $this->uri = $data["uri"];
        $this->url = $data["url"];
        $this->repliesCount = $data["replies_count"];
        $this->reblogsCount = $data["reblogs_count"];
        $this->favouritesCount = $data["favourites_count"];
        $this->favourited = $data["favourited"];
        $this->reblogged = $data["reblogged"];
        $this->muted = $data["muted"];
        $this->bookmarked = $data["bookmarked"];
        $this->content = $data["content"];
        $this->account = Account::fromData($data["account"], $this->client);
        if(count($data["media_attachments"])>0) {
            foreach ($data["media_attachments"] as $mediaJson) {
                $this->mediaAttachments[]= MediaAttachment::fromData($mediaJson, $this->client);
            }
        }
        if(count($data["mentions"])>0) {
            foreach ($data["mentions"] as $mentionJson) {
                $this->mentions[]= Mention::fromData($mentionJson, $this->client);
            }
        }
        if(count($data["tags"])>0) {
            foreach ($data["tags"] as $tagJson) {
                $this->tags[]= Tag::fromData($tagJson, $this->client);
            }
        }
        if(count($data["emojis"])>0) {
            foreach ($data["emojis"] as $emojiJson) {
                $this->emojis[]= Emoji::fromData($emojiJson, $this->client);
            }
        }

        return $this;
    }

    protected function toArray() : array
    {
        return [
        ];
    }
}
