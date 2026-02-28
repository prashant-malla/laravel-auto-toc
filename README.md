# Laravel Auto TOC

Automatically generate a Table of Contents (TOC) from rich text editor HTML content in Laravel.

The package parses HTML headings (h2–h6 by default), injects unique `id` attributes, and stores a structured TOC via a polymorphic relationship — perfect for blogs, tours, documentation, and any CKEditor / TinyMCE / Trix content.

---

## Installation

```bash
composer require prashant-malla/laravel-auto-toc
```

The service provider is auto-discovered. If you need to register it manually:

```php
// config/app.php → providers
PrashantMalla\AutoToc\AutoTocServiceProvider::class,
```

### Publish & run migrations

```bash
php artisan vendor:publish --tag=auto-toc-migrations
php artisan migrate
```

### Publish config (optional)

```bash
php artisan vendor:publish --tag=auto-toc-config
```

---

## Usage

### 1. Use the trait on any Eloquent model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PrashantMalla\AutoToc\Traits\HasTableOfContent;

class Blog extends Model
{
    use HasTableOfContent;

    /**
     * The HTML field that contains the rich text content.
     */
    protected static string $tocSourceField = 'content';
}
```

That's it! Every time a `Blog` is saved the package will:

1. Parse the `content` field for headings.
2. Inject unique `id` attributes into each heading tag.
3. Update the `content` field with the modified HTML.
4. Store / update a polymorphic `TableOfContent` record with the structured TOC.

### 2. Access the TOC

```php
$blog = Blog::with('toc')->find(1);

// Structured array of headings
$toc = $blog->toc->content;

// Each entry looks like:
// [
//     'title'  => 'Getting Started',
//     'anchor' => 'getting-started',
//     'level'  => 2,
// ]
```

### 3. Render the TOC in a Blade view

#### Flat list (simple)

```blade
@if($blog->toc && count($blog->toc->content))
<nav class="toc">
    <h2>Table of Contents</h2>
    <ul>
        @foreach($blog->toc->content as $item)
            <li class="toc-level-{{ $item['level'] }}">
                <a href="#{{ $item['anchor'] }}">{{ $item['title'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
@endif
```

Style the indentation with CSS using the level classes:

```css
.toc-level-2 { margin-left: 0; }
.toc-level-3 { margin-left: 1rem; }
.toc-level-4 { margin-left: 2rem; }
.toc-level-5 { margin-left: 3rem; }
.toc-level-6 { margin-left: 4rem; }
```

#### Nested list (hierarchical)

For a properly nested `<ul>` structure that mirrors the heading hierarchy:

```blade
@if($blog->toc && count($blog->toc->content))
<nav class="toc">
    <h2>Table of Contents</h2>
    @php $prevLevel = 0; @endphp

    @foreach($blog->toc->content as $item)
        @if($item['level'] > $prevLevel)
            {{-- Open new nested <ul> for each level deeper --}}
            @for($i = 0; $i < $item['level'] - $prevLevel; $i++)
                <ul>
            @endfor
        @elseif($item['level'] < $prevLevel)
            {{-- Close </li> and </ul> for each level back up --}}
            @for($i = 0; $i < $prevLevel - $item['level']; $i++)
                </li></ul>
            @endfor
            </li>
        @else
            </li>
        @endif

        <li><a href="#{{ $item['anchor'] }}">{{ $item['title'] }}</a>

        @php $prevLevel = $item['level']; @endphp
    @endforeach

    {{-- Close any remaining open tags --}}
    @for($i = 0; $i < $prevLevel - ($blog->toc->content[0]['level'] ?? 0); $i++)
        </li></ul>
    @endfor
    </li></ul>
</nav>
@endif
```

#### Example output

Given this HTML content:

```html
<h2>Getting Started</h2>
<p>Introduction text...</p>
<h3>Prerequisites</h3>
<p>You need PHP 8.1...</p>
<h3>Installation</h3>
<p>Run composer...</p>
<h2>Configuration</h2>
<p>Config details...</p>
<h3>Environment</h3>
<p>Set your .env...</p>
```

The stored TOC array will be:

```php
[
    ['title' => 'Getting Started', 'anchor' => 'getting-started', 'level' => 2],
    ['title' => 'Prerequisites',   'anchor' => 'prerequisites',   'level' => 3],
    ['title' => 'Installation',    'anchor' => 'installation',     'level' => 3],
    ['title' => 'Configuration',   'anchor' => 'configuration',    'level' => 2],
    ['title' => 'Environment',     'anchor' => 'environment',      'level' => 3],
]
```

And the nested Blade template renders:

```html
<nav class="toc">
    <h2>Table of Contents</h2>
    <ul>
        <li><a href="#getting-started">Getting Started</a>
            <ul>
                <li><a href="#prerequisites">Prerequisites</a></li>
                <li><a href="#installation">Installation</a></li>
            </ul>
        </li>
        <li><a href="#configuration">Configuration</a>
            <ul>
                <li><a href="#environment">Environment</a></li>
            </ul>
        </li>
    </ul>
</nav>
```

---

## Customising Heading Levels

### Per-model

```php
class Blog extends Model
{
    use HasTableOfContent;

    protected static string $tocSourceField = 'content';

    /** Only extract h2 and h3 headings */
    protected static array $tocHeadingLevels = ['h2', 'h3'];
}
```

### Globally via config

```php
// config/auto-toc.php
return [
    'heading_levels' => ['h2', 'h3', 'h4', 'h5', 'h6'],
];
```

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
