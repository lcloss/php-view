<?php declare(strict_types=1);

namespace LCloss\View;

use PHPUnit\Framework\TestCase;
use LCloss\Route\Request;

// require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'View.php');
// require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Loader.php');
// require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'view.php');


final class ViewTest extends TestCase
{
    const BREAK_LINE = ( PHP_OS == 'Linux' ? "\n" : "\r\n" );
    
    public function testCanLoadATemplate(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $this->assertEquals(
            '<h1>Hello !</h1>', $view->view('view')
        );        
    }
    /**
     * @depends testCanLoadATemplate
     */
    public function testCannotLoadATemplate(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('".\tests\notfound.tpl.php" file not found.', $view->view('notfound')
        );        
    }
    public function testCanProcessDocument(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );

        $doc = '<h1>Hi {{ $target }}</h1>';
        $data = [
            'target' => 'Everyone'
        ];
        $view->setDoc($doc);
        $this->assertEquals(
            '<h1>Hi Everyone</h1>', $view->view('', $data)
        );
    }
    /**
     * @depends testCanLoadATemplate
     */
    public function testCanReplaceAKey(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $data = ['user' => 'Mon ami'];
        $this->assertEquals(
            '<h1>Hello Mon ami!</h1>', $view->view('view', $data)
        );
    }
    /**
     * @depends testCanLoadATemplate
     */
    public function testCanExtend(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $this->assertEquals(
            "<div><p>Content</p></div>", $view->view('extends')
        );

        $doc = '@extends(layout)<span>Another content</span>';
        $view->setDoc($doc);
        $this->assertEquals(
            "<div><span>Another content</span></div>", $view->view('')
        );
    }
    public function testCanProcessSections(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $this->assertEquals(
            self::BREAK_LINE . '<header>This is Header</header>' . self::BREAK_LINE  . self::BREAK_LINE . '<p>This is Body</p>' . self::BREAK_LINE . self::BREAK_LINE . '<footer>This is Footer</footer>' . self::BREAK_LINE, $view->view('sections')
        );
    }
    public function testCanProcessIf(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );

        $doc = '<p>@if( count( $colors ) > 1 ):There are many colors.@endif' . self::BREAK_LINE . 'Thank you!</p>';
        $data = [
            'colors' => ['red', 'green', 'blue']
        ];
        $view->setDoc($doc);
        $this->assertEquals(
            '<p>There are many colors.' . self::BREAK_LINE . 'Thank you!</p>', $view->view('', $data)
        );

        $doc = '<p>@if( $count > 1 ):There are many colors' . self::BREAK_LINE . '.@endifThank you!</p>';
        $data = [
            'count'     => -10
        ];

        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $view->setDoc($doc);
        $this->assertEquals(
            '<p>Thank you!</p>', $view->view('', $data)
        );

        $doc = '<p>@if( $user != "" ):Hi {{ $user }}!@else Please, log on.@endif</p>';
        $data = [
            'user' => 'Logged User'
        ];

        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $view->setDoc($doc);
        $this->assertEquals(
            '<p>Hi Logged User!</p>', $view->view('', $data)
        );
        $doc = '<p>@if( $user != "" ):Hi {{ $user }}!@else Please, log on.@endif</p>';

        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $view->setDoc($doc);
        $this->assertEquals(
            '<p> Please, log on.</p>', $view->view('')
        );
    }
    public function testCanProcessFor(): void
    {
        $view = new View( NULL, 'tpl.php' );
        $view->setBase( __DIR__ );
        $doc = '<p>The colors are: @for( $colors as $color ){{ $color }} @endfor</p>';
        $data = [
            'colors' => [
                'red', 'green', 'blue'
            ]
        ];
        $view->setDoc($doc);
        $this->assertEquals(
            '<p>The colors are: red green blue </p>', $view->view('', $data)
        );

        $doc = '<p>The colors are: @for( $colors as $color ){{ $color.name }} @endfor</p>';
        $data = [
            'colors' => [
                ['name' => 'red'],
                ['name' => 'green'],
                ['name' => 'blue'],
            ]
        ];

        $view->setDoc($doc);
        $this->assertEquals(
            '<p>The colors are: red green blue </p>', $view->view('', $data)
        );
    }
}