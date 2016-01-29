#!/usr/bin/env php
<?php

use Kynx\ServiceManager\Migrator;
use Kynx\ServiceManager\MigrationException;

$basename = basename($argv[0]);
$usage =<<<EOU
$basename fqcn
EOU;

$class = isset($argv[1]) ? $argv[1] : false;
if (!$class) {
    fwrite(STDERR, "Please specify a class\n");
    exit(1);
}

require 'vendor/autoload.php';

$migrator = new Migrator($class);
try {
    echo $migrator->getFactoriesString();
} catch (MigrationException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
