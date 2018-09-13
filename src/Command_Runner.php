<?php

namespace JazzMan\Deploy;

/**
 * Class Command_Runner
 *
 * @package WP_Deploy_Command
 */
class Command_Runner
{

    public $commands;

    public static $verbosity;

    /**
     * Command_Runner constructor.
     *
     * @param $v
     */
    public function __construct($v)
    {
        $this->commands = [];
        self::$verbosity = $v;
    }

    /**
     * Accepted arguments:
     *
     * [condition] : bool (Default: true)
     * command / array( command, cwd ) : string / array( string, string )
     * [exit_on_fail] : bool (Default: true)
     * [success_message] : string (Default: none)
     * [fail_message] : string (Default: none)
     *
     * - If present condition needs to be first.
     * - Command can either be a string, or an array containing the
     * command string and the working directory in which it will be exec
     * - Exit on failure needs to be boolean. If missing, it's set as true
     * - The success and fail messages need to be strings and always in
     * this order.
     *
     * [args] (bracketed arguments) are optional.
     *
     */
    public function add()
    {

        $args = func_get_args();

        if (is_bool($args[0])) {
            $condition = array_shift($args);
            if (!$condition) {
                return;
            }
        }

        if (empty($args)) {
            return;
        }

        $command = $args[0];

        $messages = array_filter(array_slice($args, 1), 'is_string');

        $exit_on_failure = function ($default) use ($args) {
            foreach ($args as $arg) {
                if (is_bool($arg)) {
                    return $arg;
                }
            }

            return $default;
        };

        $meta = [
          'command'  => is_array($command) ? $command[0] : $command,
          'cwd'      => is_array($command) ? $command[1] : false,
          'exit'     => $exit_on_failure(true),
          'messages' => count($messages) ? $messages : false,
        ];

        $this->commands[] = $meta;
    }

    public function run()
    {
        foreach ($this->commands as $key => $command) {
            if (defined('WP_DEPLOY_DEBUG') && (WP_DEPLOY_DEBUG === 'all')) {
                ini_set('error_reporting', E_ALL & ~E_STRICT);
                ini_set('display_errors', 'STDERR');
                var_dump($command); //['command'] );
            } else {
                self::launch($command);
            }
            /** Remove command from queue. */
            unset($this->commands[$key]);
        }
    }

    /**
     * @param $meta
     */
    private static function launch($meta)
    {

        $command = $meta['command'];

        $verbosity = self::$verbosity;
        $descriptors = call_user_func(function () use ($verbosity, $command) {
            $options = [
              0 => [STDIN, STDOUT, STDERR],
              1 => [STDIN, ['pipe', 'r'], STDERR],
              2 => [STDIN, ['pipe', 'r'], STDERR],
            ];
            /** For level 1, make an exception of blocking for rsync. */
            if (strpos($command, 'rsync') !== false) {
                $options[1] = [STDIN, STDOUT, STDERR];
            }

            return $options[$verbosity];
        });

        $cwd = $meta['cwd'] ?: null;

        $code = proc_close(proc_open($command, $descriptors, $pipes, $cwd));

        if ($code && $meta['exit']) {
            exit($code);
        }

        if (!$meta['messages']) {
            return;
        }

        $success = array_shift($meta['messages']);
        $fail = !empty($messages) ? array_shift($meta['messages']) : $success;

        if ($code) {
            \WP_CLI::warning($fail);
        } else {
            \WP_CLI::success($success);
        }
    }

    /**
     * @param $command
     *
     * @return string
     */
    public static function get_result($command)
    {
        $cwd = null;
        $descriptors = [
          0 => ['pipe', 'r'],
          1 => ['pipe', 'w'],
        ];
        $pipes = [];
        $handler = proc_open($command, $descriptors, $pipes, $cwd);

        $output = stream_get_contents($pipes[1]);

        proc_close($handler);

        return trim($output);
    }

}
