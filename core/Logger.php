<?php

namespace Core;

/**
 * Class Logger
 * 
 * Provides logging functionality with different levels (INFO, ERROR, WARNING).
 * ERROR and WARNING logs are stored in files, while all logs are shown in the browser console in debug mode.
 */
class Logger
{
    /** @var string Directory where log files are stored */
    private $logDir;

    /** @var bool Whether debug mode is enabled */
    private $isDebug;

    /** @var string Path to the current log file */
    private $logFile;

    /** @var array Accumulated logs for browser console output */
    private static $browserLogs = [];

    /**
     * Logger constructor.
     */
    public function __construct()
    {
        $this->logDir = __DIR__ . '/../storage/logs/';
        $this->isDebug = env('DEBUG', false);
        $this->logFile = $this->logDir . 'app_' . date('Y-m-d') . '.log';
        
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Log an INFO level message (console only in debug mode).
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context, false);
    }

    /**
     * Log an ERROR level message (file and console in debug mode).
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context, true);
    }

    /**
     * Log a WARNING level message (file and console in debug mode).
     *
     * @param string $message The message to log
     * @param array $context Additional context data
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context, true);
    }

    /**
     * Write a log entry to the file (for ERROR/WARNING) and store for browser output (in debug mode).
     *
     * @param string $level The log level (INFO, ERROR, WARNING)
     * @param string $message The message to log
     * @param array $context Additional context data
     * @param bool $writeToFile Whether to write to the log file
     */
    private function log(string $level, string $message, array $context = [], bool $writeToFile = false): void
    {
        $logEntry = $this->formatLogEntry($level, $message, $context);
        
        // Write to file only for ERROR and WARNING
        if ($writeToFile) {
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        }

        // Store for browser console in debug mode
        if ($this->isDebug) {
            self::$browserLogs[] = [
                'level' => $level,
                'entry' => $logEntry
            ];
        }
    }

    /**
     * Format a log entry.
     *
     * @param string $level The log level
     * @param string $message The message to log
     * @param array $context Additional context data
     * @return string The formatted log entry
     */
    private function formatLogEntry(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context, JSON_PRETTY_PRINT) : '';

        if ($this->isDebug) {
            return "[$timestamp] $level: $message\n$contextString\n\n";
        }
        return "[$timestamp] $level: $message\n";
    }

    /**
     * Output stored logs to the browser console if the client is a browser.
     */
    public static function outputBrowserLogs(): void
    {
        if (!env('DEBUG', false) || !self::isBrowserRequest()) {
            return;
        }

        foreach (self::$browserLogs as $log) {
            $consoleMethod = self::getConsoleMethod($log['level']);
            $style = self::getConsoleStyle($log['level']);
            $logEntry = str_replace("\n", "\\n", addslashes($log['entry']));
            echo "<script>console.{$consoleMethod}('%c{$logEntry}', '{$style}');</script>";
        }

        self::$browserLogs = [];
    }

    /**
     * Check if the request comes from a browser based on User-Agent.
     *
     * @return bool
     */
    private static function isBrowserRequest(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browserAgents = ['Mozilla', 'Gecko', 'AppleWebKit', 'Chrome', 'Safari', 'Firefox', 'Edge', 'Opera'];
        
        foreach ($browserAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the JavaScript console method for a log level.
     *
     * @param string $level The log level
     * @return string The console method (log, error, warn)
     */
    private static function getConsoleMethod(string $level): string
    {
        switch (strtoupper($level)) {
            case 'ERROR':
                return 'error';
            case 'WARNING':
                return 'warn';
            default:
                return 'log';
        }
    }

    /**
     * Get the CSS style for console output based on log level.
     *
     * @param string $level The log level
     * @return string The CSS style
     */
    private static function getConsoleStyle(string $level): string
    {
        switch (strtoupper($level)) {
            case 'ERROR':
                return 'color: red; font-weight: bold;';
            case 'WARNING':
                return 'color: orange; font-weight: bold;';
            default:
                return 'color: blue;';
        }
    }
}