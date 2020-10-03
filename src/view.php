<?php declare(strict_types=1);

namespace LCloss\View;
use Exception;

class View {
    const DEFAULT_VIEW_PATH = '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    const DEFAULT_EXTENSION = '.tpl.php';
    
    private $_view_path = "";
    private $_tpl_extension = "";
    private $_doc = "";
    private $_sections = [];
    private $_data = [];

    public function __construct() {
        $this->setDefaultFolder( self::DEFAULT_VIEW_PATH );
        $this->setDefaultTemplateExtension( self::DEFAULT_EXTENSION );
    }

    // Setters
    public function setDefaultFolder( $folder ) {
        $this->_view_path = $folder;
    }

    public function setDefaultTemplateExtension( $extension ) {
        $this->_tpl_extension = $extension;
    }

    public function setData( $data ) {
        $this->_data = $data;
    }

    public function setDoc( $doc ) {
        $this->_doc = $doc;
    }

    private function setSection($section, $content) {
        $this->_sections[$section] = $content;
    }

    // Getters
    public function getDefaultFolder() {
        return $this->_view_path;
    }

    public function getDoc() {
        return $this->_doc;
    }

    public function getData() {
        return $this->_data;
    }
    
    // Return view
    public function view( $template = '', $data = [] ) {
        // Set data
        $this->setData( $data );

        // Get template
        if ( $template != '' ) {
            $this->_load_template( $template );
        }

        // Process includes and keys
        $this->process();

        // Return updated doc
        return $this->getDoc();
    }

    public function process() {
        // Sections template
        $this->_sections();

        // Extends template
        $this->_extends();

        // Includes templates
        $this->_includes();

        // Replace all keys
        $this->_replace_raw_keys();

        $this->_replace_keys();

        $this->_if();

        $this->_clear_for();
        
        // Clear remainer keys
        $this->_clear_keys();
    }

    // Load template
    private function _load_template( $template ) {
        $template = str_replace('.', DIRECTORY_SEPARATOR, $template);
        $filename = $this->getDefaultFolder() . $template . $this->_tpl_extension;
        if ( !file_exists($filename) ) {
            throw new Exception(
                sprintf('"%s" file not found.', $filename)
            );
        } else {
            $this->setDoc( file_get_contents( $filename ) );
        }
    }

    // Replace keys
    private function _replace_keys() {
        $data = $this->getData();
        
        foreach( $data as $key => $value ) {
            if ( !is_array( $value ) ) {
                $key_pattern = '/{{[\s]*\$' . $key . '[\s]*}}/s';
                $this->_doc = preg_replace( $key_pattern, $value, $this->_doc );
            } else {
                $this->_for( $key, $value );
            }
        }
    }

    private function _replace_raw_keys() {
        $keys_pattern = '/\!\$([\w\.]*)/s';
        preg_match_all( $keys_pattern, $this->_doc, $matches );

        foreach($matches[0] as $i => $found) {
            if ( array_key_exists( $matches[1][$i], $this->_data )) {
                $this->_doc = str_replace( $found, $this->_data[$matches[1][$i]], $this->_doc );
            }
        }
    }

    private function _clear_keys() {
        $keys_pattern = '/{{[\s]*\$[\w]*[\s]*}}/s';
        $this->_doc = preg_replace( $keys_pattern, '', $this->_doc );
    }

    // Extends template
    private function _extends() {
        $extends_pattern = '/@extends\([\s]*([\w\.]*)[\s]*\)(?:\r\n)?/s';
        preg_match_all( $extends_pattern, $this->_doc, $matches );

        foreach( $matches[0] as $i => $found ) {
            if ( $found != "" ) {
                $this->_doc = str_replace($found, '', $this->_doc);
                $content = $this->getDoc();

                // Extend template through View
                $view = new View();
                $view->setDefaultFolder( $this->getDefaultFolder() );

                $doc = $view->view($matches[1][$i]);
                $this->setDoc( $doc );
                $this->_yield('content', $content);

                // Handle sections
                foreach( $this->_sections as $section => $content ) {
                    $this->_yield($section, $content);
                }
            }
        }
    }

    // Sections
    private function _sections() {
        $sections_pattern = '/@section\([\s]*([\w\.]*)(?:\r\n)?[\s]*\)(.*?)@endsection(?:\r\n)?/s';
        preg_match_all( $sections_pattern, $this->_doc, $matches );

        // Just set the sections on internal array
        foreach( $matches[0] as $i => $found ) {
            $this->setSection($matches[1][$i], $matches[2][$i]);
            $this->_doc = str_replace($found, '', $this->_doc);
        }
    }

    private function _yield( $key, $content ) {
        $yield_pattern = '/@yield\([\s]*' . $key . '[\s]*\)(?:\r\n)?/s';
        $this->setDoc( preg_replace( $yield_pattern, $content, $this->_doc ) );
    }

    // Includes
    private function _includes() {
        $includes_pattern = '/@include\([\s]*([\w\.]*)[\s]*\)(?:\r\n)?/s';
        preg_match_all( $includes_pattern, $this->_doc, $matches );

        foreach( $matches[0] as $i => $found ) {
            if ( $found != "" ) {
                // Get include content
                $view = new View();
                $view->setDefaultFolder( $this->getDefaultFolder() );
                $doc = $view->get($matches[1][$i], $this->getData());

                // Replace in the doc
                $this->_doc = str_replace($found, $doc, $this->_doc);
            }
        }
    }

    public function get( $template, $data = []) {
        // Set data
        $this->setData( $data );

        // Get template
        $this->_load_template( $template );

        // Return updated doc
        return $this->getDoc();
    }

    // For
    private function _for( $key, $occurs ) {
        $for_pattern = '/@for\([\s]*' . $key . '[\s]+as[\s]([\w]*)[\s]*\)(.*?)@endfor/s';
        preg_match_all( $for_pattern, $this->_doc, $matches );

        // Just set the fors on internal array
        $for = new View();
        $for->setDefaultFolder( $this->getDefaultFolder() );

        // Loop through for founds
        foreach( $matches[0] as $i => $found ) {
            $data = [];
            $for_content = "";

            // Foreach of the keys
            foreach( $occurs as $content ) {
                $for->setDoc( $matches[2][$i] );

                // If key is associative array...
                if ( is_array( $content ) ) {
                    foreach( $content as $cnt_key => $cnt_value ) {
                        $data[$matches[1][$i] . "." . $cnt_key] = $cnt_value;
                    }
                } else {
                    // If key is simple array
                    $data[$matches[1][$i]] = $content;
                }
                $all_data = array_merge( $this->_data, $data );
                $for->setData( $all_data );
                $for->process();
                $for_content .= $for->getDoc();
            }
            $this->_doc = str_replace($found, $for_content, $this->_doc);
        }
    }

    private function _clear_for() {
        $for_pattern = '/@for\([\s]*[\w]*[\s]+as[\s]([\w]*)[\s]*\)(.*?)@endfor/s';
        preg_match_all( $for_pattern, $this->_doc, $matches );

        // Loop through for founds
        foreach( $matches[0] as $i => $found ) {
            $this->_doc = str_replace($found, '', $this->_doc);
        }
    }

    // If
    private function _if() {
        $if_pattern = '/@if\([\s]*([^:]*)[\s]*\)\:(.*?)(?:@else(.*?))?@endif/s';
        
        preg_match_all( $if_pattern, $this->_doc, $matches );

        // Raw keys
        // --------
        $key_pattern = '/\!\$([\w\.]*)/s';
        foreach( $matches[0] as $i => $found ) {
            // Evaluate each condition
            // First, replace keys:
            preg_match_all( $key_pattern, $matches[1][$i], $key_matches );
            $cond = $found;

            foreach( $key_matches[0] as $j => $key_found ) {
                if ( array_key_exists( $key_matches[1][$j], $this->_data )) {
                    $cond = str_replace( '$' . $key_matches[1][$j], $this->_data[$key_matches[1][$j]], $cond );
                }
            }
            $this->_doc = str_replace( $found, $cond, $this->_doc );
        }

        // Other keys
        // ----------
        $key_pattern = '/\$([\w\.]*)/s';
        foreach( $matches[0] as $i => $found ) {
            // Evaluate each condition
            // First, replace keys:
            preg_match_all( $key_pattern, $matches[1][$i], $key_matches );
            $cond = $matches[1][$i];

            foreach( $key_matches[0] as $j => $key_found ) {
                if ( array_key_exists( $key_matches[1][$j], $this->_data )) {
                    $arr = [
                        $key_matches[1][$j] => $this->_data[$key_matches[1][$j]]
                    ];
                    extract($arr, EXTR_PREFIX_ALL, 'if');
                    // printMessage('Key: ' . $key_matches[1][$j]);
                    // printMessage($if_database);

                    // $cond = str_replace( '$' . $key_matches[1][$j], "'" . $this->_data[$key_matches[1][$j]] . "'", $cond );
                    $cond = str_replace( '$' . $key_matches[1][$j], '$if_' . $key_matches[1][$j], $cond );
                    // printMessage($cond);
                } else {
                    $cond = str_replace( '$' . $key_matches[1][$j], "''", $cond );
                }
            }
            $evaluation_cond = '$res = ' . $cond . ';';
            eval($evaluation_cond);
            if ($res) {
                $this->_doc = str_replace( $found, $matches[2][$i], $this->_doc );
            } else {
                $this->_doc = str_replace( $found, $matches[3][$i], $this->_doc );
            }
        }
    }
}