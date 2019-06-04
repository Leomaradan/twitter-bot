<?php

class Logger
{
    const Error = 0;
    const Info = 1;
    const Debug = 2;

    public static function levelName($level)
    {
        switch ($level) {
            case Logger::Error:
                return 'Error';
            case Logger::Info:
                return 'Info';
            case Logger::Debug:
                return 'Debug';
        }
    }

    public function __construct()
    {
        function logMessage($message, $level = Logger::Info)
        {
            $log = date("Y-m-d H:i:s") . ' ' . Logger::levelName($level) . ' : ' . $message . PHP_EOL;
            if ($_ENV['verbose']) {
                echo $log;
            } else {
                if ($level <= Logger::Info) {
                    if ($_ENV['verbose']) {
                        echo $log;
                    }
                }
            }
        }

        function logError($message)
        {
            logMessage($message, Logger::Error);
        }
        function logInfo($message)
        {
            logMessage($message, Logger::Info);
        }
        function logDebug($message)
        {
            logMessage($message, Logger::Debug);
        }
    }
}
