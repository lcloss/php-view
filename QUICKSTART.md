# Package Quick Start

## Initialize 

```php
use LCloss\View\View;

$view = new View();
echo $view->view('index');
```

The command above will load `index.tpl.php` from folder `resources\view` and will return imediatelly.

To use keys:
```php
$data = [
    'user' => 'User name',
    'last_login' => '2020-10-02'
];
$view->view('index', $data);
```

The keys marked as `{{ $user }}` and `{{ $login }}` in the template will be replaced with respective values.

## Set up

You can customize some features, as:

### Views folder
The default folder for template views is `resources\view` folder.

You can change this by:
```php
$view->setDefaultFolder( 'views/' );
```

### Template extension
The default template extension is `.tpl.php`. 

You can change this by:
```php
$view->setDefaultTemplateExtension( '.view.php' );
```
## Features

### extends
You can use `@extend(<template name>)` to extends the current template into a new one.

For example:
`main.tpl.php`:
```html
<html>
    <head>
        <title>System</title>
    </head>
    <body>
    @yield(content)
    </body>
</html>
```
And:
`page.tpl.php`:
```html
@extend(main)
<h1>Hello, World!</h1>
```

When template was processed, all content of `page.tpl.php` will be inserted into `@yield(content)` place, on `main.tpl.php` as `page.tpl.php` extends it.

### keys
You can put keys on your template as here:

```html
<p>Welcome back, {{ $user }}!</p>
```
When you call `view` method with `data` parameter, if you set `user` key in `data` this key will be replaced.
```php
$data = [
    'user' => 'User name'
];
$view->view('index', $data);
```
Will produce.
```html
<p>Welcome back, User name!</p>
```

### Raw keys
Sometime you want to replace first a key, to be evaluated later.
Imagine you can have same structure for two different data.

For example:
`template.tpl.php`
```html
<table>
    <tr>
        @for( columns as column )
        <th>{{ $column.name }}</th>
        @endfor
    </tr>
    @for( rows as row )
    <tr>
        <td>{{ $row.age }}</td>
    </tr>
    @endfor
</table>
```
On your file, you have:
`main.php`
```php
$columns = [
    ['name' => 'age']
];
$rows = [
    ['age' => 30],
    ['age' => 42],
    ['age' => 25],
    ['age' => 27],
];
$data = [
    'columns' => $columns,
    'rows' => $rows
];
$view->view('template', $data);
```
The code above will produce:
```html
<table>
    <tr>
        <th>age</th>
    </tr>
    <tr>
        <td>30</td>
    </tr>
    <tr>
        <td>42</td>
    </tr>
    <tr>
        <td>25</td>
    </tr>
    <tr>
        <td>27</td>
    </tr>
</table>
```
Then, you have other data:
`main.php`
```php
$columns = [
    ['name' => 'birthdate']
];
$rows = [
    ['birthdate' => '27-09-1950'],
    ['birthdate' => '17-02-1949'],
    ['birthdate' => '01-11-1908'],
];
$data = [
    'columns' => $columns,
    'rows' => $rows
];
$view->view('template', $data);
```
You need to modify your code to match the new data:
```html
    @for( rows as row )
    <tr>
        <td>{{ $row.birthdate }}</td>
    </tr>
    @endfor
```
But, you can use **raw keys** to do that!

Look to this code:
```html
<table>
    <tr>
        @for( columns as column )
        <th>{{ $column.name }}</th>
        @endfor
    </tr>
    @for( rows as row )
    <tr>
        @for( columns as column )
        <td>{{ $row.!$column.name }}</td>
        @endfor
    </tr>
    @endfor
</table>
```

The template system will replace !$column.name by respective value (in this case `birthdate`) before replacing other keys.

So, with same template you can use on both cases:
```html
<table>
    <tr>
        <th>birthdate</th>
    </tr>
    <tr>
        <td>27-09-1950</td>
    </tr>
    <tr>
        <td>17-02-1949</td>
    </tr>
    <tr>
        <td>01-11-1908</td>
    </tr>
</table>
```

### If
You can easly use If's on your template:
```html
@if( count( $rows ) > 0 ):
    <table>
    @for( rows as row )
    <tr>
        <td>{{ $row.name }}</td>
        <td>{{ $row.age }}</td>
    </tr>
    @endfor
    </table>
@else
    <p>None was found.</p>
@endif
```

[Back to README](README.md)
