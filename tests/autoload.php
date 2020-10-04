<?php
define('ROOT_FOLDER', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);

spl_autoload_register(function ($class) {
    // First take a look on 'packages' folder:
    $package_path = "";
    $path_to = explode( '\\', $class );
    $parts = count( $path_to );
    $vendor = "";
    $class_name = "";


    if ( $parts > 1 ) {
        $is_first = true;
        foreach ( $path_to as $i => $path ) {
            if ( !$is_first ) {
                $package_path .= DIRECTORY_SEPARATOR;
            } else {
                $vendor = $path;
            }
            if ( $i <> ( $parts - 1) ) {
                $package_path .= strtolower( $path );
            } else {
                $class_name = $path;
            }
            $is_first = false;
        }

        $class_path = ROOT_FOLDER . "src" . DIRECTORY_SEPARATOR . $class_name . ".php";

        if ( file_exists( $class_path ) ) {
            require_once( $class_path );
        }
    }
});

?>