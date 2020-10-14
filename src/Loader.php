<?php
namespace LCloss\View;

class Loader
{
    const DEFAULT_VIEW_PATH = '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
    const DEFAULT_EXTENSION = '.tpl.php';

    protected $path = "";
    protected $extension = "";
    protected $template = "";
    protected $doc = "";
    protected $data = [];

    public function __construct( $path = self::DEFAULT_VIEW_PATH, $ext = self::DEFAULT_EXTENSION )
    {
        $this->setPath( $path );
        $this->setExtension( $ext );
    }

    /**
     * Set default folder for views.
     * If not used, default folder is resources/views
     * @param string $folder Folder where views are
     * @return void
     */
    public function setPath( $folder ): void 
    {
        $this->path = $folder;
    }

    public function setTemplate( $template ): void
    {
        $this->template = $template;
    }

    /**
     * Set default template extension for view.
     * If not used, default extension is .tpl.php
     * @param string $extension Extension for views
     * @return void
     */
    public function setExtension( $extension ): void 
    {
        $this->extension = $extension;
    }

    public function setKey( $key, $value ) 
    {
        $this->data[ $key ] = $value;
    }

    public function setData( $data ) 
    {
        if ( is_array( $data ) ) {
            foreach( $data as $key => $value ) {
                $this->setKey( $key, $value );
            }
        }
    }

    public function set( $content )
    {
        $this->doc = $content;
    }

    public function get() 
    {
        return $this->doc;
    }

    public function replace( $search, $replace_to )
    {
        $this->set( str_replace( $search, $replace_to, $this->get() ));
    }

    public function pregReplace( $pattern, $replace_to )
    {
        $this->set( preg_replace( $pattern, $replace_to, $this->get() ));
    }

    /**
     * Get default folder.
     * @return string Folder 
     */
    public function path(): string 
    {
        return $this->path;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function key( $key )
    {
        if ( $this->keyExists( $key )) {
            return $this->data[ $key ];
        } else {
            return NULL;
        }
    }

    public function extract( $pattern )
    {
        preg_match_all( $pattern, $this->get(), $matches );
        return $matches;
    }

    public function load( $template, $data = [] )
    {
        $this->setTemplate( $template );
        $this->setData( $data );

        return $this->parse();
    }

    public function parse($return_doc = true)
    {
        if ( '' == $this->template ) {
            return '';
        }

        $template = str_replace('.', DIRECTORY_SEPARATOR, $this->template);
        $filename = $this->path() . $template . $this->extension;

        if ( !file_exists( $filename ) ) {
            // return '';
            throw new \Exception( sprintf('%s file was not found.', $filename));
        }

        if ( count( $this->data ) > 0 ) {
            extract( $this->data );
        }

        ob_start();
        include $filename;
        $this->set( ob_get_clean() );

        if ( $return_doc ) {
            return $this->get();
        }
    }

    public function keyExists( $key ): bool
    {
        return array_key_exists( $key, $this->data() );
    }
}