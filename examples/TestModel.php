<?php
namespace LilyTest;

class TestModel {
    public $a;

    private $b;

    public function __construct($a, $b) {
        $this->a = $a;
        $this->b = $b;
    }

    public function show() {
        return $this->a + $this->b;
    }
}