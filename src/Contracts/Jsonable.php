<?php

namespace tp5er\Contracts;


interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = JSON_ERROR_NONE);
}