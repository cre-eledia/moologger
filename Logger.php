<?php
namespace Katzgrau\KLogger;

require_once(__DIR__.'/psr/LoggerInterface.php');
require_once(__DIR__. '/psr/LogLevel.php');
require_once(__DIR__.'/psr/AbstractLogger.php');
require_once(__DIR__.'/psr/InvalidArgumentException.php');
require_once(__DIR__.'/psr/LoggerAwareInterface.php');
require_once(__DIR__.'/psr/LoggerAwareTrait.php');
require_once(__DIR__. '/psr/NullLogger.php');


use DateTime;
use RuntimeException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Finally, a light, permissions-checking logging class.
 *
 * Originally written for use with wpSearch
 *
 * Usage:
 * $log = new Katzgrau\KLogger\Logger('/var/log/', Psr\Log\LogLevel::INFO);
 * $log->info('Returned a million search results'); //Prints to the log file
 * $log->error('Oh dear.'); //Prints to the log file
 * $log->debug('x = 5'); //Prints nothing due to current severity threshhold
 *
 * @author  Kenny Katzgrau <katzgrau@gmail.com>
 * @since   July 26, 2008
 * @link    https://github.com/katzgrau/KLogger
 * @version 1.0.0
 * @package auth_eledia_system_order
 */

/**
 * Class documentation
 * @package auth_eledia_system_order
 */
class Logger extends AbstractLogger {

    /**
     * KLogger options
     *  Anything options not considered 'core' to the logging library should be
     *  settable view the third parameter in the constructor
     *
     *  Core options include the log file path and the log threshold
     *
     * @var array
     */
    protected $options = [
        'extension'      => 'txt',
        'dateFormat'     => 'Y-m-d G:i:s.u',
        'filename'       => false,
        'flushFrequency' => false,
        'prefix'         => 'log_',
        'logFormat'      => false,
        'appendContext'  => true,
    ];

    /**
     * Path to the log file
     * @var string
     */
    private $logfilepath;

    /**
     * Current minimum logging threshold
     * @var int
     */
    protected $loglevelthreshold = LogLevel::DEBUG;

    /**
     * The number of lines logged in this instance's lifetime
     * @var int
     */
    private $loglinecount = 0;

    /**
     * Log Levels
     * @var array
     */
    protected $loglevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    /**
     * This holds the file handle for this instance's log file
     * @var resource
     */
    private $filehandle;

    /**
     * This holds the last line logged to the logger
     *  Used for unit tests
     * @var string
     */
    private $lastline = '';

    /**
     * Octal notation for default permissions of the log file
     * @var int
     */
    private $defaultpermissions = 0777;

    /**
     * Class constructor
     *
     * @param string $logDirectory      File path to the logging directory
     * @param string $loglevelthreshold The LogLevel Threshold
     * @param array  $options
     *
     * @internal param string $logFilePrefix The prefix for the log file name
     * @internal param string $logFileExt The extension for the log file
     */
    public function __construct($logdirectory, $loglevelthreshold = LogLevel::DEBUG, array $options = []) {
        $this->loglevelthreshold = $loglevelthreshold;
        $this->options = array_merge($this->options, $options);

        $logdirectory = rtrim($logdirectory, DIRECTORY_SEPARATOR);
        if ( ! file_exists($logdirectory)) {
            mkdir($logdirectory, $this->defaultpermissions, true);
        }

        if(strpos($logdirectory, 'php://') === 0) {
            $this->setLogToStdOut($logdirectory);
            $this->setFileHandle('w+');
        } else {
            $this->setLogFilePath($logdirectory);
            if(file_exists($this->logfilepath) && !is_writable($this->logfilepath)) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
            }
            $this->setFileHandle('a');
        }

        if ( ! $this->filehandle) {
            throw new RuntimeException('The file could not be opened. Check permissions.');
        }
    }

    /**
     * @param string $stdOutPath
     */
    public function setlogtostdout($stdoutpath) {
        $this->logfilepath = $stdoutpath;
    }

    /**
     * @param string $logDirectory
     */
    public function setlogfilepath($logdirectory) {
        if ($this->options['filename']) {
            if (strpos($this->options['filename'], '.log') !== false || strpos($this->options['filename'], '.txt') !== false) {
                $this->logfilepath = $logdirectory.DIRECTORY_SEPARATOR.$this->options['filename'];
            }
            else {
                $this->logfilepath = $logdirectory.DIRECTORY_SEPARATOR.$this->options['filename'].'.'.$this->options['extension'];
            }
        } else {
            $this->logfilepath = $logdirectory.DIRECTORY_SEPARATOR.$this->options['prefix'].date('Y-m-d').'.'.$this->options['extension'];
        }
    }

    /**
     * @param $writeMode
     *
     * @internal param resource $filehandle
     */
    public function setfilehandle($writemode) {
        $this->filehandle = fopen($this->logfilepath, $writemode);
    }


    /**
     * Class destructor
     */
    public function __destruct() {
        if ($this->filehandle) {
            fclose($this->filehandle);
        }
    }

    /**
     * Sets the date format used by all instances of KLogger
     *
     * @param string $dateFormat Valid format string for date()
     */
    public function setdateformat($dateformat) {
        $this->options['dateFormat'] = $dateformat;
    }

    /**
     * Sets the Log Level Threshold
     *
     * @param string $loglevelthreshold The log level threshold
     */
    public function setloglevelthreshold($loglevelthreshold) {
        $this->loglevelthreshold = $loglevelthreshold;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = []) {
        if ($this->loglevels[$this->loglevelthreshold] < $this->loglevels[$level]) {
            return;
        }
        $message = $this->formatMessage($level, $message, $context);
        $this->write($message);
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $message Line to write to the log
     * @return void
     */
    public function write($message) {
        if (null !== $this->filehandle) {
            if (fwrite($this->filehandle, $message) === false) {
                throw new RuntimeException('The file could not be written to. Check that appropriate permissions have been set.');
            } else {
                $this->lastline = trim($message);
                $this->loglinecount++;

                if ($this->options['flushFrequency'] && $this->loglinecount % $this->options['flushFrequency'] === 0) {
                    fflush($this->filehandle);
                }
            }
        }
    }

    /**
     * Get the file path that the log is currently writing to
     *
     * @return string
     */
    public function getlogfilepath() {
        return $this->logfilepath;
    }

    /**
     * Get the last line logged to the log file
     *
     * @return string
     */
    public function getlastlogline() {
        return $this->lastline;
    }

    /**
     * Formats the message for logging.
     *
     * @param  string $level   The Log Level of the message
     * @param  string $message The message to log
     * @param  array  $context The context
     * @return string
     */
    protected function formatmessage($level, $message, $context) {
        if ($this->options['logFormat']) {
            $parts = [
                'date'          => $this->getTimestamp(),
                'level'         => strtoupper($level),
                'level-padding' => str_repeat(' ', 9 - strlen($level)),
                'priority'      => $this->loglevels[$level],
                'message'       => $message,
                'context'       => json_encode($context),
            ];
            $message = $this->options['logFormat'];
            foreach ($parts as $part => $value) {
                $message = str_replace('{'.$part.'}', $value, $message);
            }

        } else {
            $message = "[{$this->getTimestamp()}] [{$level}] {$message}";
        }

        if ($this->options['appendContext'] && ! empty($context)) {
            $message .= PHP_EOL.$this->indent($this->contextToString($context));
        }

        return $message.PHP_EOL;

    }

    /**
     * Gets the correctly formatted Date/Time for the log entry.
     *
     * PHP DateTime is dump, and you have to resort to trickery to get microseconds
     * to work correctly, so here it is.
     *
     * @return string
     */
    private function gettimestamp() {
        $originaltime = microtime(true);
        $micro = sprintf("%06d", ($originaltime - floor($originaltime)) * 1000000);
        $date = new DateTime(date('Y-m-d H:i:s.'.$micro, (int)$originaltime));

        return $date->format($this->options['dateFormat']);
    }

    /**
     * Takes the given context and coverts it to a string.
     *
     * @param  array $context The Context
     * @return string
     */
    protected function contexttostring($context) {
        $export = '';
        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace([
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ], [
                '=> $1',
                'array()',
                '    ',
            ], str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }
        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }

    /**
     * Indents the given string with the given indent.
     *
     * @param  string $string The string to indent
     * @param  string $indent What to use as the indent.
     * @return string
     */
    protected function indent($string, $indent = '    ') {
        return $indent.str_replace("\n", "\n".$indent, $string);
    }
}


