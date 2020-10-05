<?php
define('ROOT_FOLDER', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
define('THIS_NAMESPACE', 'LCloss\\View\\');

if (!function_exists('startsWith')) {
    function startsWith( $target, $context ): bool {
        if ( strlen( $context ) > strlen( $target ) ) {
            return false;
        }
        if ( substr( $target, 0, strlen( $context ) ) == $context ) {
            return true;
        } else {
            return false;
        }
    }
}

spl_autoload_register(function ($class) {

    if ( startsWith( $class, THIS_NAMESPACE )) {
        $path_to = explode ( '\\', $class );
        $i = count( $path_to ) - 1;
        $class_name = $path_to[$i] . '.php';
        require_once ( ROOT_FOLDER . 'src' . DIRECTORY_SEPARATOR . $class_name);
    }

});

?>