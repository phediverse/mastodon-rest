<?php

namespace Phediverse\MastodonRest\Resource;

class Status extends BaseResource
{
    protected $id;

    protected $uri;
    protected $url;
    protected $account;
    protected $inReplyToId;
    protected $inReplyToAccountId;
    protected $reblog;
    protected $content;
    protected $reblogsCount;
    protected $favouritesCount;
    protected $reblogged;
    protected $favourited;
    protected $sensitive;
    protected $spoilerText;
    protected $visibility;
    protected $mediaAttachments;
    protected $mentions;
    protected $tags;
    protected $application;

    /** @var \DateTimeImmutable */
    protected $createdAt;

    public function getId() : int
    {
        return $this->resolve()->id;
    }

    public function getUri() : string
    {
        return $this->resolve()->uri;
    }

    public function getUrl() : string
    {
        return $this->resolve()->url;
    }

    public function getAccount() : Account
    {
        return $this->resolve()->account;
    }

    public function getInReplyToId() : int
    {
        return $this->resolve()->inReplyToId;
    }

    public function getInReplyToAccountId() : int
    {
        return $this->resolve()->inReplyToAccountId;
    }

    public function getReblog() : string
    {
        return $this->resolve()->reblog;
    }

    public function getContent() : string
    {
        return $this->resolve()->content;
    }

    public function getReblogsCount() : int
    {
        return $this->resolve()->reblogsCount;
    }

    public function getFavouritesCount() : int
    {
        return $this->resolve()->favouritesCount;
    }

    public function getReblogged() : bool
    {
        return $this->resolve()->reblogged;
    }

    public function getFavourited() : bool
    {
        return $this->resolve()->favourited;
    }

    public function getSensitive() : bool
    {
        return $this->resolve()->sensitive;
    }

    public function getSpoilerText() : string
    {
        return $this->resolve()->spoilerText;
    }

    public function getVisibility() : string // @TODO: enum?
    {
        return $this->resolve()->visibility;
    }

    public function getMediaAttachments() : array
    {
        return $this->resolve()->mediaAttachments;
    }

    public function getMentions() : array
    {
        return $this->resolve()->Mentions;
    }

    public function getTags() : array
    {
        return $this->resolve()->tags;
    }

    public function getApplication() : string // will be Application later ?
    {
        return $this->resolve()->application;
    }

    // Dates!

    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->resolve()->createdAt;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function hydrate(array $data)
    {
        $this->id = $data['id'];
        // simple data :
        $this->uri = $data['uri'];
        $this->url = $data['url'];
        $this->inReplyToId = $data['in_reply_to_id'];
        $this->inReplyToAccountId = $data['in_reply_to_account_id'];
        $this->content = $data['content'];
        $this->reblogsCount = $data['reblogs_count'];
        $this->favouritesCount = $data['favourites_count'];
        $this->reblogged = $data['reblogged'];
        $this->favourited = $data['favourited'];
        $this->sensitive = $data['sensitive'];
        $this->spoilerText = $data['spoiler_text'];
        $this->visibility = $data['visibility'];
        // Objects:
        if ($data['account']) {
            $this->account = Account::fromData($data['account']);
        }
        if ($data['reblog']) {
            $this->reblog = Status::fromData($data['reblog']);
        }
//        if ($data['application']) { // "application" there is not "Application" here :/
            $this->application = $data['application'];
//        }
        // Arrays: 
 /* @TODO Attachments, Mention and Tag not defined yet...
        if (is_array($data['media_attachments'])) {
            $this->mediaAttachments=array();
            foreach($data['media_attachments'] as $one) {
                $this->mediaAttachments[]=Attachments::fromData($one);
            }
        }
        if (is_array($data['mentions'])) {
            $this->mentions=array();
            foreach($data['mentions'] as $one) {
                $this->mentions[]=Mention::fromData($one);
            }
        }
        if (is_array($data['tags'])) {
            $this->tags=array();
            foreach($data['tags'] as $one) {
                $this->mentions[]=Tag::fromData($one);
            }
        }
*/

        $this->createdAt = new \DateTimeImmutable($data['created_at']);


        return $this;
    }

    protected function toArray() : array
    {
        // recursive toArray for array of objects
        if (is_array($this->mediaAttachments) && count($this->mediaAttachments)) {
            $mediaAttachments=array();
            foreach($this->mediaAttachments as $one) {
                $mediaAttachments[]=$one->jsonSerialize();
            }
        } else {
            $mediaAttachments=null;
        }

        if (is_array($this->mentions) && count($this->mentions)) {
            $mentions=array();
            foreach($this->mentions as $one) {
                $mentions[]=$one->jsonSerialize();
            }
        } else {
            $mentions=null;
        }

        if (is_array($this->tags) && count($this->tags)) {
            $tags=array();
            foreach($this->tags as $one) {
                $tags[]=$one->jsonSerialize();
            }
        } else {
            $tags=null;
        }

        return [
            'id' => $this->id,
            'uri' => $this->uri,
            'url' => $this->url,
            'in_reply_to_id' => $this->inReplyToId,
            'in_reply_to_account_id' => $this->inReplyToAccountId,
            'content' => $this->content,
            'reblogs_count' => $this->reblogsCount,
            'favourites_count' => $this->favouritesCount,
            'reblogged' => $this->reblogged,
            'favourited' => $this->favourited,
            'sensitive' => $this->sensitive,
            'spoiler_text' => $this->spoilerText,
            'visibility' => $this->visibility,

            'media_attachments' => $mediaAttachments, 
            'mentions' => $mentions,
            'tags' => $tags,

            'application' => $this->application,

            // @TODO : toArray of sub-object (done, but is it working?)
            'reblog' => (is_object($this->reblog))?$this->reblog->jsonSerialize():null,
            'account' => (is_object($this->account))?$this->account->jsonSerialize():null,

            'created_at' => $this->createdAt->format(\DateTime::ATOM),
        ];
    }
}
