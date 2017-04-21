<?php

namespace Phediverse\MastodonRest\Resource;

class Timeline extends BaseResource
{
    protected $statuses;

    public function getStatuses() : array
    {
        return $this->resolve()->statuses;
    }

    /**
     * @param array $data
     * @return $this
     */
    protected function hydrate(array $data)
    {
        if (is_array($data)) {
            $this->statuses=array();
            foreach($data as $one) {
                $this->statuses[]=Status::fromData($one);
            }
        }
        return $this;
    }
    
    protected function toArray() : array
    {
        // recursive toArray for array of objects
        if (is_array($this->statuses) && count($this->statuses)) {
            $statuses=array();
            foreach($this->statuses as $one) {
                $statuses[]=$one->jsonSerialize();
            }
        } else {
            $statuses=null;
        }

        return [ 'statuses' => $statuses ];
    }
}
