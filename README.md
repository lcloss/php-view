# php-view
A PHP Package to handle Views and templates.
This is part of [Slender Micro Framework](https://github.com/lcloss/slender), but can be used separatelly in any project.

## Intend
This package handles views.
Its purpose is to make easly to create views and do the most common stuff with it.
It was inspired in Blade template system of Laravel Framework.

## Install
I recommend you to install this package through [Composer](https://packagist.org/packages/lcloss/view).

## Quick Start

You can use a view simple like this:
```php
use LCloss\View\View;

$view = new View('resources.view', 'tpl.php');
$view->setBase(__DIR__ . '\\..\\..\\' );

echo $view->view('index');
```

The main syntax:

`@extends(view)`, `@section()` and `@yield`
---
This will extend the `view` and include the `sections` inside a `yield` notation.
If there is no `section`, the main content will be included on `content` yield. 
For example:
`app.tpl.php`
```
<html>
    <head>
    </head>
    <body>
    @yield(content)
    </body>
</html>
```

`index.tpl.php`
```
@extends(app)
This is the main content
```

Or, you can also have:
`index.tpl.php`
```
@extends(app)
@section(content)
This is the main content
@endsection
```

It will be helpfull to include styles and scripts, like:
`app.tpl.php`
```
<html>
    <head>
    @yield(styles)
    </head>
    <body>
    @yield(content)
    @yield(scripts)
    </body>
</html>
```

`index.tpl.php`
```
@extends(app)
@section(styles)
<style>
.any-format {
    font-size: 0.8em;
    color: gray;
}
</style>
@endsection
@section(content)
This is the main content
@endsection
@section(scripts)
<script>
alert('Say hello!');
</script>
@endsection
```

`@route()`
---
You can easly create routes.
```
<nav>
    <li><a href="@route('index')">Home</a></li>
    <li><a href="@route('blog')">Blog</a></li>
    <li><a href="@route('blog.show', 1)">How to install Slender Micro Framework</a></li>
    <li><a href="@route('blog.create')">New article</a></li>
</nav>
```

`{{ $variables }}`
---
You can easly insert variables in views:
`resources\views\user.tpl.php`
```
<h1>Introduction</h1>
<p>Hello {{ $user }}! You are welcome!</p>
```
And in a controller:
`app\controller\UserController.php`
```
$data = [
    'user' => 'Luciano Closs'
];

$view = new View('resources.views', 'tpl.php');
$view->setBase( env('base_dir') );

echo $view->view('user', $data);
```

## Features

Release 0.0.1
---
- @if
- @for
- @route
- @assets
- {{ $key }}

Release info:

Release 0.0.2
---
- Parse view to PHP (expand current functionalities)
- Fix some keys replacement (when key is next ')' without space)


## ToDo
There are a lot of to do!
The next steps are:
- Create tests
- Create docs
- Make more compatible with Blade
- Introduce cache system
