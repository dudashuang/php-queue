<?php
namespace Lily\Jobs;

class BaseJob extends Job{

    /**
     * BaseJob constructor.
     * init the params.
     *
     * @param array $options
     */
    public function __construct(Array $options = []) {

        $keys = array_keys(get_object_vars($this));

        foreach ($options as $key => $value) {
            if (in_array($key, $keys)) {
                $this->{$key} = $value;
            }
        }
    }

    public function handle() {
        // TODO: Implement handle() method.
    }
}