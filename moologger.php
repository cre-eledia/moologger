<?php

require_once('Logger.php');
require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/psr/LogLevel.php');

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

set_exception_handler(function($exception) {
    moologger::get_logger()->error('Uncaught Exception: '.$exception->getMessage());
    echo 'An error occurred. Please contact the administrator.';
});

class moologger {
    private static $logger = [];

    private function __construct($name) {
        global $CFG;
        if ($CFG->debug == DEBUG_DEVELOPER) {
            $loglevel = LogLevel::DEBUG;
        } else {
            $loglevel = LogLevel::ERROR;
        }
        self::$logger[$name] = new Logger($CFG->dataroot . '/plugin_logs', $loglevel, [
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

    public static function get_logger($name = null) {
        if (is_null($name)){
            $name = self::get_plugin_name();
        }
        if (!isset(self::$logger[$name])){
            new moologger($name);
        }
        return self::$logger[$name];
    }

    public static function debug($message, $name = null) {
        self::get_logger($name)->debug($message);
    }

    public static function info($message, $name = null) {
        self::get_logger($name)->info($message);
    }

    public static function notice($message, $name = null) {
        self::get_logger($name)->notice($message);
    }

    public static function warning($message, $name = null) {
        self::get_logger($name)->warning($message);
    }

    public static function error($message, $name = null) {
        self::get_logger($name)->error($message);
    }

    public static function critical($message, $name = null) {
        self::get_logger($name)->critical($message);
    }

    public static function alert($message, $name = null) {
        self::get_logger($name)->alert($message);
    }

    public static function emergency($message, $name = null) {
        self::get_logger($name)->emergency($message);
    }

    public static function log($level, $message, $name = null) {
        self::get_logger($name)->log($level, $message);
    }

}

