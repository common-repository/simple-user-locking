<?php
namespace Sulock;

class LockMeta {

    public $time;
    public $by_user;
    public $expires;
    public $ip;

    public function __construct($expires=null) {
        $this->time = microtime();
        $this->by_user = get_current_user_id();
        $this->expires = $expires;
        $this->ip = sulock_get_simple_ip();
    }

}