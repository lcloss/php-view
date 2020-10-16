<?php
namespace LCloss\View;

final class Environment
{
    private static $instance = NULL;
    protected $data = [];

    private function __construct() {}

    public static function load( $env_file )
    {
        if ( is_null(self::$instance) ) {
            self::$instance = new Environment();
        }

        if ( count( self::$instance->data ) == 0 ) {
            if ( !file_exists( $env_file ) ) {
                throw new \Exception(sprintf('%s file is not found.', $env_file));
            }
            $data = parse_ini_file( $env_file, true );

            if ( !array_key_exists( 'view', $data) ) {
                throw new \Exception(sprintf('Section [view] was not found on %s env file.', $env_file));
            }

            self::$instance->data = $data['view'];
        }

        return self::$instance;
    }

    public function get( $key )
    {
        if ( !array_key_exists( $key, $this->data )) {
            return NULL;
        }
        return $this->data[$key];
    }
}