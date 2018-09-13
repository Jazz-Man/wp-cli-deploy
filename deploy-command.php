<?php
use JazzMan\Deploy\Deploy_Command;

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

$autoload = __DIR__.'/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

try {
    WP_CLI::add_command('deploy', Deploy_Command::class);
} catch (Exception $e) {
}
