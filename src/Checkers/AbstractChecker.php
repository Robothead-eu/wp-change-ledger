<?php

namespace WPChangeLedger\Checkers;

use WPChangeLedger\Logger;

abstract class AbstractChecker {

    public function __construct(protected Logger $logger) {}

    abstract public function run(): void;
}
