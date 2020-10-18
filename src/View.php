<?php declare(strict_types=1);

namespace LCloss\View;

use LCloss\Env\Environment;
use LCloss\View\Loader;
use Exception;

class View {
    const BREAK_LINE = ( PHP_OS == 'Linux' ? "\n" : "\r\n" );
    const VALID_WORD = '\w\.\_\-';
    
    protected $loader = NULL;

    private $_sections = [];
    private $_for = [];
    private $_for_count = 0;
    private $_if = [];
    private $_if_count = 0;

    public function __construct( $path = NULL, $ext = NULL ) 
    {
        $this->loader = new Loader();

        // Path : may be in 'point' format: resources.views
        if ( !is_null( $path ) ) {
            $this->setPath( $path );
        }

        // Extension
        if ( !is_null( $ext ) ) {
            $this->setExtension( $ext );
        }
    }

    public function fromEnv( $path, $env_file = '.env' )
    {
        $env = Environment::getInstance( $env_file, $path );
        $this->setBase( $env->base_dir );
        $this->setPath( $env->view['path'] );
        $this->setExtension( $env->view['extension'] );
    }

    public function setBase( $folder )
    {
        if ( substr( $folder, -1 ) != DIRECTORY_SEPARATOR ) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        $this->loader->setBase( $folder );
    }
    public function setPath( $path )
    {
        $this->loader->setPath( $path );
    }

    public function setExtension( $ext )
    {
        $this->loader->setExtension( $ext );
    }
    
    // Setters
    public function setKey( $key, $value ) {
        $this->loader->setKey( $key, $value );
    }

    /**
     * Set keys for template
     * @param array $data Keys to use in view
     * @return void
     */
    public function setData( $data ): void {
        $this->loader->setData( $data );
        // foreach( $data as $key => $value ) {
        //     $this->setKey( $key, $value );
        // }
    }

    /**
     * Set the document content.
     * This method can be used when you do not want load from a view file.
     * @param string $doc Document content
     * @return void
     */
    public function setDoc( $doc ): void {
        $this->loader->set( $doc );
        // $this->_doc = $doc;
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

    private function setNewFor( $var, $sub, $content ): String {
        $c = $this->addForCount();
        $key = 'for_' . $c;
        $this->setFor( $key, $var, $sub, $content );

        return $key;
    }

    private function addForCount(): int {
        $this->_for_count++;
        return $this->_for_count;
    }

    private function setFor( $key, $var, $sub, $content ): void {
        $this->_for[$key] = [
            'var'   => $var,
            'sub'   => $sub,
            'content'   => $content
        ];
    }

    private function setFors( $fors ) {
        foreach( $fors as $key => $data ) {
            $this->setFor( $key, $data['var'], $data['sub'], $data['content'] );
        }
    }

    private function setNewIf( $cond, $then, $else ): String {
        $c = $this->addIfCount();
        $key = 'if_' . $c;
        $this->setIf( $key, $cond, $then, $else );

        return $key;
    }

    private function addIfCount(): int {
        $this->_if_count++;
        return $this->_if_count;
    }

    private function setIf( $key, $cond, $then, $else ): void {
        $this->_if[$key] = [
            'cond'   => $cond,
            'then'   => $then,
            'else'   => $else
        ];
    }

    private function setIfs( $ifs ) {
        foreach( $ifs as $key => $data ) {
            $this->setIf( $key, $data['cond'], $data['then'], $data['else'] );
        }
    }

    // Return view
    /**
     * Process the view and return result.
     * @param optional string $template View file to load.
     * @param optional array $data Keys for the View
     * @return string Document parsed
     */
    public function view( $template = '', $data = [], $final = true ): string {
        // Set data
        $this->setData( $data );

        // Get template
        if ( $template != '' ) {
            $this->loader->setTemplate( $template );
            $this->loader->parse(false);
        }

        return $this->parse($final);
    }

    public function parse($final = false): String {
        // Process includes and keys
        $this->process();

        if ( $final ) {
            $this->cleanup();
        }
        
        // Return updated doc
        return $this->loader->get();
    }

    /**
     * Parse the document
     * @return void
     */
    public function process() {
        // Extract all sections from template
        $this->_extractSections();

        // Extends template
        $this->_mergeExtends();

        // Includes templates
        $this->_includeIncludes();

        // Replace missed sections. Others was replaced on _mergeExtends method.
        $this->_replaceSections();

        // From here, we have all files/contents togheter.

        // Now, replace all raw and lonely keys
        $this->_replaceKeys();

        // Then, we need to handle with @for and @if
        // We will extract every single block (without other inside) of each @for and each @if, until the $doc does not have any block.
        $this->_extractIfAndFor();

        // Replace if and for founded
        $this->_replaceIfAndFor();
    }

    // Sections
    /**
     * Extract all sections from template and store them on $_sections private field.
     * @return void
     */
    private function _extractSections(): void {
        $sections_pattern = '/@section\([\s]*([' . self::VALID_WORD . ']*)(?:' . self::BREAK_LINE . ')?[\s]*\)(.*?)@endsection(?:' . self::BREAK_LINE . ')?/s';
        $matches = $this->loader->extract( $sections_pattern );

        // Just set the sections on internal array
        foreach( $matches[0] as $i => $found ) {
            $this->setSection($matches[1][$i], $matches[2][$i]);
            $this->loader->replace( $found, '' );
        }
    }

    /**
     * Merge this view with the extended view
     * @return void
     */
    private function _mergeExtends(): void {
        $extends_pattern = '/@extends\([\s]*([' . self::VALID_WORD . ']*)[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
        $matches = $this->loader->extract( $extends_pattern );

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                // Clear @extends key
                $this->loader->replace( $found, '' );
                $this_content = $this->loader->get();

                // Create a new view from extended layout
                $extended = $this->createNew();
                $extended_doc = $extended->view($matches[1][$i], [], false);

                // Replace current content with the extended view
                $this->setDoc( $extended_doc );
                $this->_replaceYield('content', $this_content);

                // Replace sections on current extended doc
                $this->_replaceSections();
            }
        }
    }
    
    /**
     * Create a new view based on this view.
     * @return View
     */
    public function createNew(): View {
        $view = new View();
        $view->setBase( $this->loader->base() );
        $view->setPath( $this->loader->path() );
        $view->setExtension( $this->loader->extension() );
        $view->setData( $this->loader->data() );
        $view->setIfs( $this->_if );
        $view->setFors( $this->_for );
        return $view;
    }

    /**
     * Replace @yield key with a content
     * @param String $key Yield key to replace.
     * @param String $content Content to replace yield
     * @return void
     */
    private function _replaceYield( String $key, String $content ): void {
        // $yield_pattern = '/@yield\([\s]*' . $key . '[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
        $yield_pattern = '/@yield\([\s]*' . $key . '[\s]*\)/s';
        $this->loader->pregReplace( $yield_pattern, $content );
    }

    /**
     * Replace all sections stored on respective keys
     * @return void
     */
    private function _replaceSections(): void {
        // Handle sections
        foreach( $this->_sections as $section => $content ) {
            $this->_replaceYield($section, $content);
        }
    }

    /**
     * Include all @includes
     * @return void
     */
    private function _includeIncludes(): void {
        // $includes_pattern = '/@include\([\s]*([' . self::VALID_WORD . ']*)[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
        $includes_pattern = '/@include\([\s]*([' . self::VALID_WORD . ']*)[\s]*\)/s';
        $matches = $this->loader->extract( $includes_pattern );

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                // Get include content
                $include = $this->createNew();
                $include_doc = $include->view($matches[1][$i], [], false);
                
                // Replace in the doc
                $this->loader->replace( $found, $include_doc );
            }
        }
    }

    /**
     * Replace all keys that is not inside a @for or @if, in other words, replace
     * 'lonely' keys as {{ $key }}
     * @return void
     */
    private function _replaceKeys(): void {
        // Raw keys have priority over other keys
        $this->_replaceRawKeys();
        
        // Then, replace other lonely keys
        $this->_replaceLonelyKeys();
    }

    /** 
     * Lookup on the view for raw keys.
     * These keys has priority over others
     * @return void
     */
    private function _replaceRawKeys(): void {
        $keys_pattern = '/\!\$([' . self::VALID_WORD . ']*)/s';
        $matches = $this->loader->extract( $keys_pattern );
        // preg_match_all( $keys_pattern, $this->_doc, $matches );

        foreach($matches[0] as $i => $found) {
            $key = $matches[1][$i];
            // Only replaces keys found on the data
            if ( $this->loader->keyExists( $key ) ) {
                if ( !is_array( $this->loader->key($key) )) {
                    $this->loader->replace( $found, $this->loader->key($key) );
                }
            }
        }
    }

    /**
     * Extract all keys from @if conditions.
     * Also, replace each occurrence (var.prop) with (var_prop).
     * @return array Set of if variables
     */
    private function _getIfKeys(): array {
        $keys_pattern = '/\$([' . self::VALID_WORD . ']*)/s';
        $matches = $this->loader->extract( $keys_pattern );

        $if_vars = [];
        foreach( $matches[0] as $i => $found ) {
            $key = str_replace( '.', '_', $matches[1][$i]);

            if ( $this->loader->keyExists( $matches[1][$i] )) {
                $if_vars[$key] = [
                    'original'  => $matches[1][$i],
                    'content'   => $this->loader->key($matches[1][$i]),
                ];
            } else {
                $if_vars[$key] = [
                    'original'  => $matches[1][$i],
                    'content'   => NULL,
                ];
            }
        }

        return $if_vars;
    }

    /**
     * Lookup on the view for lonely keys.
     * These keys can be replaced if we already have on our data
     * @return void
     */
    private function _replaceLonelyKeys(): void {
        $keys_pattern = '/{{[\s]*\$([' . self::VALID_WORD . ']*)[\s]*}}/s';
        $matches = $this->loader->extract( $keys_pattern );

        foreach($matches[0] as $i => $found) {
            $key = $matches[1][$i];
            // Only replaces keys found on the data
            if ( $this->loader->keyExists( $key ) ) {
                if ( !is_array( $this->loader->key($key) )) {
                    $this->loader->replace( $found, $this->loader->key($key) );
                }
            }
        }
    }

    /**
     * Extract ifs and fors, and store in theirs variables $_if and $_for.
     * Replaces each occurrence with other key, so by this, replace all occurrences 
     * (even if they are chainned).
     * @return void
     */
    private function _extractIfAndFor(): void {
        $has_any = true;
        while( true == $has_any ) {
            $has_any_for = $this->_extractFor();
            $has_any_if = $this->_extractIf();

            $has_any = ( $has_any_for || $has_any_if );
        }
    }

    /**
     * Extract all fors and store them into $_for property.
     * @return bool if there any replacement
     */
    private function _extractFor(): bool {
        $for_pattern = '/@for\([\s]*\$([' . self::VALID_WORD . ']*)[\s]*as[\s]*\$([' . self::VALID_WORD . ']*)[\s]*\)((?:((?!@if)+(?!@for)+(?!@end)).)+)@endfor/s';
        $matches = $this->loader->extract( $for_pattern );

        $has_any = false;

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                $has_any = true;
                $key = $this->setNewFor( $matches[1][$i], $matches[2][$i], $matches[3][$i] );
                $this->loader->replace( $found, '{% for $' . $key . ' %}' );
            }
        }

        return $has_any;
    }

    /**
     * Extract all ifs and store them into $_if property.
     * @return bool if there any replacement
     */
    private function _extractIf(): bool {
        $if_pattern = '/@if\([\s]*([^:]+)[\s]*\)\:((?:(?!@if)+(?!@for)+(?!@else)+(?!@end).)*?)(?:@else((?:(?!@if)+(?!@for)+(?!@else)+(?!@end).)*?))?@endif/s';
        $matches = $this->loader->extract( $if_pattern );

        $has_any = false;

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                $has_any = true;
                $key = $this->setNewIf( $matches[1][$i], $matches[2][$i], $matches[3][$i] );
                $this->loader->replace( $found, '{% if $' . $key . ' %}' );
            }
        }

        return $has_any;
    }

    /**
     * Replace all $_for and $_if with their respective value.
     * Process chainned @for and @if
     * @return void
     */
    private function _replaceIfAndFor(): void {
        $has_any = true;
        while( $has_any ) {
            $has_any_for = $this->_replaceFor();
            $has_any_if = $this->_replaceIf();
            $this->_replaceKeys();

            $has_any = ( $has_any_for || $has_any_if );
        }
    }

    /**
     * Replace all $_for with their respective value
     * @return bool if happens any replacement
     */
    private function _replaceFor(): bool {
        $for_pattern = '/{% for \$([' . self::VALID_WORD . ']+) %}/s';
        $matches = $this->loader->extract( $for_pattern );

        $has_any = false;

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                $key = $matches[1][$i];
                $data = $this->_for[$key];

                $occurs = $this->loader->key( $data['var'] );

                $for_content = "";

                foreach( $occurs as $item ) {
                    $for_template = $this->createNew();
                    $content = $data['content'];
                    $for_template->setDoc( $data['content'] );

                    if ( is_array( $item ) ) {
                        foreach( $item as $item_key => $item_value ) {
                            $for_template->setKey( $data['sub'] . '.' . $item_key, $item_value);
                        }
                    } else {
                        $for_template->setKey( $data['sub'], $item );
                    }
                    $content = $for_template->parse();
                    $for_content .= $content;
                }

                $this->loader->replace( $found, $for_content );
                $has_any = true;
            }
        }

        return $has_any;
    }

    /**
     * Replace all $_if with their respective value
     * @return bool if happens any replacement
     */
    private function _replaceIf(): bool {
        $if_pattern = '/{% if \$([' . self::VALID_WORD . ']+) %}/s';
        $matches = $this->loader->extract( $if_pattern );

        $has_any = false;

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                $key = $matches[1][$i];

                if ( array_key_exists( $key, $this->_if )) {
                    $data = $this->_if[$key];

                    $cond = $data['cond'];
                    $if_template = $this->createNew();
                    $if_template->setDoc( $cond );
    
                    // Extract all if variables and create theirs variables with prefix 'xif'
                    $if_keys = $if_template->_getIfKeys();
                    $if_variables = [];
                    foreach( $if_keys as $key => $if_data ) {
                        $cond = str_replace( '$' . $if_data['original'], '$xif_' . $key, $cond );
                        $if_variables[$key] = $if_data['content'];
                    }
                    extract( $if_variables, EXTR_PREFIX_ALL, 'xif');
                    // Now, evaluate condition:
                    $cond = '$res = ( ' . $cond . ');';
                    eval($cond);
    
                    // And decide what block wins:
                    if ( $res ) {
                        $if_content = $data['then'];
                    } else {
                        $if_content = $data['else'];
                    }
    
                    $this->loader->replace( $found, $if_content );
                    $has_any = true;
                }
            }
        }

        return $has_any;
    }

    /**
     * Cleanup template from missed keys.
     * @return void
     */
    public function cleanup(): void {
        $this->_clearSections();
        $this->_clearKeys();
        $this->_clearIfsAndFors();
    }

    /**
     * Cleanup template from missed @yield
     * @return void
     */
    private function _clearSections(): void {
        $yield_pattern = '/@yield\([\s]*[^)]*[\s]*\)(?:' . self::BREAK_LINE . ')?/s';
        $matches = $this->loader->extract( $yield_pattern );
        foreach( $matches[0] as $i => $found ) {
            $this->loader->replace( $found, '' );
        }
    }

    /**
     * Cleanup template from missed $keys
     * @return void
     */
    private function _clearKeys(): void {
        $keys_pattern = '/{{[\s]*\$[' . self::VALID_WORD . ']*[\s]*}}/s';
        $this->loader->pregReplace( $keys_pattern, '' );
    }

    /**
     * Cleanup template from missed ifs and fors
     * @return void
     */
    private function _clearIfsAndFors(): void {
        $has_any = true;
        while( $has_any ) {
            $has_any_for = $this->_clearFors();
            $has_any_if = $this->_clearIfs();
            $has_any = ( $has_any_for || $has_any_if );
        }
    }

    /**
     * Cleanup template from missed @for
     * @return bool if there were any replacement
     */
    private function _clearFors(): bool {
        $for_pattern = '/@for\([\s]*\$([' . self::VALID_WORD . ']*)[\s]*as[\s]*\$([' . self::VALID_WORD . ']*)[\s]*\)((?:((?!@if)+(?!@for)+(?!@end)).)+)@endfor/s';
        $matches = $this->loader->extract( $for_pattern );

        $has_any = false;
        foreach( $matches[0] as $i => $found) {
            if ( "" != $found ) {
                $this->loader->replace( $found, '' );
                $has_any = true;
            }
        }

        return $has_any;
    }

    /**
     * Cleanup template from missed @if
     * @return bool if there were any replacement
     */
    private function _clearIfs(): bool {
        $if_pattern = '/@if\([\s]*([^:]+)[\s]*\)\:((?:(?!@if)+(?!@for)+(?!@else)+(?!@end).)+)(?:@else((?:(?!@if)+(?!@for)+(?!@else)+(?!@end).)+))?@endif/s';
        $matches = $this->loader->extract( $if_pattern );

        $has_any = false;

        foreach( $matches[0] as $i => $found ) {
            if ( "" != $found ) {
                $this->loader->replace( $found, '' );
                $has_any = true;
            }
        }

        return $has_any;
    }
}