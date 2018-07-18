<?php
if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

$autoload = __DIR__.'/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
    require_once $autoload;
}

try {
    WP_CLI::add_command('deploy', '\JazzMan\Deploy\Deploy_Command');
} catch (Exception $e) {
}
