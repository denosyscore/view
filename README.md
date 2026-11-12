# denosyscore/view

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/view`. Open issues and pull requests there.

View engine, directives, forms, and value objects

## Installation

```bash
composer require denosyscore/view
```

## Quick start

Compile templates with layouts, sections, conditionals and escaped output.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\View\ViewEngine;

$templates = sys_get_temp_dir() . '/my-app-views';
@mkdir($templates, 0700, true);
file_put_contents("$templates/layout.php", '<main>@yield("content")</main>');
file_put_contents("$templates/welcome.php", <<<'TPL'
@extends("layout")
@section("content")Hello, {{ $name }}!@if($admin) (admin)@endif@endsection
TPL);

$view = new ViewEngine([$templates]);
$view->enableCache(sys_get_temp_dir() . '/my-app-view-cache');

// {{ }} escapes output; {!! !!} prints it raw.
echo $view->render('welcome', ['name' => '<Ada>', 'admin' => true]), "\n";
// <main>Hello, &lt;Ada&gt;! (admin)</main>
```

## Included Modules

- src/View/*
- src/Form/*
- src/ValueObjects/*

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
