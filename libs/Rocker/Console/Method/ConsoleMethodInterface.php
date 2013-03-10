<?php
namespace Rocker\Console\Method;


interface ConsoleMethodInterface {

    public function call($args, $flags);

    public function help();
}