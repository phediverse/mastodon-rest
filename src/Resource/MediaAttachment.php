<?php

namespace Phediverse\MastodonRest\Resource;

class MediaAttachment extends BaseResource
{
    protected $id;
    protected $type;
    protected $url;
    protected $previewUrl;
    protected $remoteUrl;
    protected $previewRemoteUrl;
    protected $textUrl;
    protected $meta;
    protected $description;
    protected $blurhash;

    public function getId() {
        return $this->id;
    }
    public function getType() {
        return $this->type;
    }
    public function getUrl() {
        return $this->url;
    }
    public function getPreviewurl() {
        return $this->previewUrl;
    }
    public function getRemoteurl() {
        return $this->remoteUrl;
    }
    public function getPreviewremoteurl() {
        return $this->previewRemoteUrl;
    }
    public function getTexturl() {
        return $this->textUrl;
    }
    public function getMeta() {
        return $this->meta;
    }
    public function getDescription() {
        return $this->description;
    }
    public function getBlurhash() {
        return $this->blurhash;
    }

    // under-the-hood methods

    /**
     * @param array $data
     * @return $this
     */
    public function hydrate(array $data)
    {
        $this->id = $data["id"];
        $this->type = $data["type"];
        $this->url = $data["url"];
        $this->previewUrl = $data["preview_url"];
        $this->remoteUrl = $data["remote_url"];
        $this->previewRemoteUrl = $data["preview_remote_url"];
        $this->textUrl = $data["text_url"];
        $this->meta = $data["meta"];
        foreach ($data["meta"] as $key=>$meta) {
            $this->meta[$key] = Metadata::fromData($meta, $this->client);
        }
        $this->description = $data["description"];
        $this->blurhash = $data["blurhash"];
        return $this;
    }

    protected function toArray() : array
    {
        return [
            "id"=>$this->id,
            "type"=>$this->type,
            "url"=>$this->url,
            "preview_url"=>$this->previewUrl,
            "remote_url"=>$this->remoteUrl,
            "preview_remote_url"=>$this->previewRemoteUrl,
            "text_url"=>$this->textUrl,
            "meta"=>$this->meta,
            "description"=>$this->description,
            "blurhash"=>$this->blurhash
        ];
    }
}
