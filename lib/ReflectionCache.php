<?php

namespace Atreyu;

interface ReflectionCache
{
    public function fetch($key);
    public function store($key, $data);
}
