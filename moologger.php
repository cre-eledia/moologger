<?php

require_once('Logger.php');
require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/psr/LogLevel.php');

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class Moologger {
    private static $logger = [];

    private function __construct($name) {
        global $CFG;

        if ($CFG->debug == DEBUG_DEVELOPER) {
            $loglevel = LogLevel::DEBUG;
        } else {
            $loglevel = LogLevel::ERROR;
        }
        self::$logger[$name] = new Logger($CFG->dataroot . '/moodlelogs', LogLevel::DEBUG, [
          'extension' => 'log',
          'prefix' => $name.'__',
            'flushFrequency' => 1,
        ]);
    }

    public static function getlogger($name) {
        if (!isset(self::$logger[$name])){
            new Moologger($name);
        }
        return self::$logger[$name];
    }
}

