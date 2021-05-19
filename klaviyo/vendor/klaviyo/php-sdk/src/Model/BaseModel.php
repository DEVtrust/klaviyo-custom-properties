<?php

namespace Klaviyo\Model;

abstract class BaseModel implements \JsonSerializable
{
    /**
     * Convert model to array.
     */
    public function toArray() {
        return json_decode(json_encode($this), true);
    }
}
