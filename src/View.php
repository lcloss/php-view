<?php declare(strict_types=1);

namespace LCloss\View;
use Exception;

class View {
    const DEFAULT_VIEW_PATH = '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    const DEFAULT_EXTENSION = '.tpl.php';
    const BREAK_LINE = ( PHP_OS == 'Linux' ? '\n' : '\r\n' );
    
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
    /**
     * Set default folder for views.
     * If not used, default folder is resources/views
     * @param string $folder Folder where views are
     * @return void
     */
    public function setDefaultFolder( $folder ): void {
        $this->_view_path = $folder;
    }

    /**
     * Set default template extension for view.
     * If not used, default extension is .tpl.php
     * @param string $extension Extension for views
     * @return void
     */
    public function setDefaultTemplateExtension( $extension ): void {
        $this->_tpl_extension = $extension;
    }

    /**
     * Set keys for template
     * @param array $data Keys to use in view
     * @return void
     */
    public function setData( $data ): void {
        $this->_data = $data;
    }

    /**
     * Set the document content.
     * This method can be used when you do not want load from a view file.
     * @param string $doc Document content
     * @return void
     */
    public function setDoc( $doc ): void {
        $this->_doc = $doc;
    }

    /**
     * Set section content.
     * @param string $section Section name
     * @param string $content Content of section
     * @return void
     */
    private function setSection($section, $content): void {
        $this->_sections[$section] = $content;
    }

    // Getters
    /**
     * Get default folder.
     * @return string Folder 
     */
    public function getDefaultFolder(): string {
        return $this->_view_path;
    }

    /**
     * Get document
     * @return string Document
     */
    public function getDoc(): string {
        return $this->_doc;
    }

    /**
     * Get template keys
     * @return array Keys
     */
    public function getData(): array {
        return $this->_data;
    }
    
    // Return view
    /**
     * Process the view and return result.
     * @param optional string $template View file to load.
     * @param optional array $data Keys for the View
     * @return string Document parsed
     */
    public function view( $template = '', $data = [] ): string {
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

    /**
     * Parse the document
     * @return void
     */
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

    /**
     * Load a template view and return it
     * @param string $template Template View file
     * @param optional array $data Template keys
     * @return string
     */
    public function get( $template, $data = []): string {
        // Set data
        $this->setData( $data );

        // Get template
        $this->_load_template( $template );

        // Return updated doc
        return $this->getDoc();
    }

    // Load template
    private function _load_template( $template ): void {
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
    private function _replace_keys(): void {
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

    private function _replace_raw_keys(): void {
        $keys_pattern = '/\!\$([\w\.]*)/s';
        preg_match_all( $keys_pattern, $this->_doc, $matches );

        foreach($matches[0] as $i => $found) {
            if ( array_key_exists( $matches[1][$i], $this->_data )) {
                $this->_doc = str_replace( $found, $this->_data[$matches[1][$i]], $this->_doc );
            }
        }
    }

    private function _clear_keys(): void {
        $keys_pattern = '/{{[\s]*\$[\w]*[\s]*}}/s';
        $this->_doc = preg_replace( $keys_pattern, '', $this->_doc );
    }

    // Extends template
    private function _extends(): void {
        $extends_pattern = '/@extends\([\s]*([\w\.]*)[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
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
    private function _sections(): void {
        $sections_pattern = '/@section\([\s]*([\w\.]*)(?:' . self::BREAK_LINE . ')?[\s]*\)(.*?)@endsection(?:' . self::BREAK_LINE . ')?/s';
        preg_match_all( $sections_pattern, $this->_doc, $matches );

        // Just set the sections on internal array
        foreach( $matches[0] as $i => $found ) {
            $this->setSection($matches[1][$i], $matches[2][$i]);
            $this->_doc = str_replace($found, '', $this->_doc);
        }
    }

    private function _yield( $key, $content ): void {
        $yield_pattern = '/@yield\([\s]*' . $key . '[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
        $this->setDoc( preg_replace( $yield_pattern, $content, $this->_doc ) );
    }

    // Includes
    private function _includes(): void {
        $includes_pattern = '/@include\([\s]*([\w\.]*)[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
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

    // For
    private function _for( $key, $occurs ): void {
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

    private function _clear_for(): void {
        $for_pattern = '/@for\([\s]*[\w]*[\s]+as[\s]([\w]*)[\s]*\)(.*?)@endfor/s';
        preg_match_all( $for_pattern, $this->_doc, $matches );

        // Loop through for founds
        foreach( $matches[0] as $i => $found ) {
            $this->_doc = str_replace($found, '', $this->_doc);
        }
    }

    // If
    private function _if(): void {
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