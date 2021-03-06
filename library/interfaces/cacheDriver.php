<?php
namespace Phiber\Interfaces;

interface cacheDriver
{
    public function set($key, $value, $ttl = null);

    public function getKey();

    public function get($key);

    public function getBag();

    public function getMulti(array $keys);

    public function deleteAll();

    public function delete($key);

    public function isHit();

    public function exists($key);

    public function setExpiration($ttl = null);

    public function getExpiration();
}

?>