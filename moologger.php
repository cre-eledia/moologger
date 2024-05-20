<?php

require_once('Logger.php');
require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/psr/LogLevel.php');

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

set_exception_handler(function($exception) {
    Moologger::getlogger()->error('Uncaught Exception: '.$exception->getMessage());
    echo 'An error occurred. Please contact the administrator.';
});

class Moologger {
    private static $logger = [];

    private function __construct($name) {
        global $CFG;
        if ($CFG->debug == DEBUG_DEVELOPER) {
            $loglevel = LogLevel::DEBUG;
        } else {
            $loglevel = LogLevel::ERROR;
        }
        self::$logger[$name] = new Logger($CFG->dataroot . '/moodlelogs', $loglevel, [
          'extension' => 'log',
          'prefix' => $name.'__',
            'flushFrequency' => 1,
        ]);
    }

    public static function get_plugin_name() {
        $plugin = new stdClass();
        require(__DIR__.'/../../version.php');
        return $plugin->component;
    }

    public static function getlogger($name = null) {
        if (is_null($name)){
            $name = self::get_plugin_name();
        }
        if (!isset(self::$logger[$name])){
            new Moologger($name);
        }
        return self::$logger[$name];
    }
}

