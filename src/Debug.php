<?php

namespace LCloss\View;

class Debug {
    const DEBUG_ALL = 1;
    const DEBUG_INFO = 2;
    const DEBUG_WARNING = 4;
    const DEBUG_CRITICAL = 8;
    const DEBUG_NONE = 15;

    const BL = ( PHP_OS == 'Linux' ? "\n" : "\r\n" );

    // Hold the class instance
    private static $instance = NULL;

    private $_level = 15;
    private $_singleton = NULL;
    private $_this = NULL;

    private function __construct() 
    {
    }

    public static function getInstance( $level = self::DEBUG_NONE )
    {
        if ( null == self::$instance ) {
            self::$instance = new Debug();
        }

        self::$instance->setLevel( $level );

        return self::$instance;
    }

    public function setLevel( $level ) {
        $this->_level = $level;
    }

    public function getCallingScript() {
        $debug = debug_backtrace();
        return $debug[2]['file'];
    }

    public function getCallingFunction() {
        $debug = debug_backtrace();
        return $debug[2]['function'];
    }

    public function printDebugMessage( $msg ) {
        echo "<code><pre>" . $msg . "</pre></code>" . self::BL;
    }

    public function printWhere() {
        if ( $this->_level <= self::DEBUG_INFO ) {
            $method = $this->getCallingFunction();
            $this->printDebugMessage( $method );
        }
    }

    public function printInfo( $msg ) {
        if ( $this->_level <= self::DEBUG_INFO ) {
            $file = $this->getCallingScript();
            $method = $this->getCallingFunction();
            $msg = "[INFO] " . $file . "(". $method . '): ' . $msg;
            $this->printDebugMessage( $msg );
        }
    }
}