# Orchid Laraberg

A field that drops the Gutenberg block editor into an Orchid Platform screen. Wraps [van-ons/laraberg](https://github.com/VanOns/laraberg) with an Orchid `Field`, a paste handler that strips Word/Docs cruft so pasting doesn't freeze the browser, and an opt-in bridge to a Laravel media-upload endpoint.

> Requires PHP 8.1+, Orchid Platform ^14.15. License: MIT (this wrapper).
> Laraberg itself is GPL-3.0-or-later — read its license before shipping commercial software.

## Table of contents

- [Installation](#installation)
- [Usage in an Orchid screen](#usage-in-an-orchid-screen)
- [Options](#options)
- [Media uploads](#media-uploads)
- [Paste cleanup](#paste-cleanup)
- [Custom blocks](#custom-blocks)
- [Credits](#credits)

## Installation

```bash
composer require protrafficgroup/orchid-laraberg
```

The service provider auto-discovers and publishes Laraberg + wrapper assets on first boot. If something prevents auto-publishing (custom container, console-only runs), do it explicitly:

```bash
php artisan vendor:publish --provider="VanOns\Laraberg\LarabergServiceProvider" --tag=public
```

To customise the default options array:

```bash
php artisan vendor:publish --tag=laraberg-config
```

## Usage in an Orchid screen

```php
use Orchid\Support\Facades\Layout;
use ProTrafficGroup\OrchidLaraberg\Laraberg;

public function layout(): iterable
{
    return [
        Layout::rows([
            Laraberg::make('content')->title('Body'),
        ]),
    ];
}
```

The submit handler receives the raw Gutenberg HTML in `$request->input('content')` — store it directly. To render on the public site, use the `RendersContent` trait from the upstream Laraberg:

```php
use Illuminate\Database\Eloquent\Model;
use VanOns\Laraberg\Traits\RendersContent;

class Post extends Model
{
    use RendersContent;
}

// in a Blade view:
// {!! $post->render() !!}
```

By default the trait reads the `content` column. Override via `protected $contentColumn = 'body';` or pass the name into `$post->render('body')`.

## Options

`Laraberg::make()->setOptions([...])` forwards options into `Laraberg.init` on the client. Anything supported by Laraberg's `EditorSettings` works — height, sizes, colours, gradients, disabled core blocks, and so on.

```php
Laraberg::make('content')
    ->setOptions([
        'height'             => '600px',
        'maxWidth'           => 800,
        'disabledCoreBlocks' => ['core/code', 'core/freeform'],
        'colors'             => [
            ['name' => 'Brand red', 'slug' => 'brand-red', 'color' => '#c0392b'],
        ],
    ]);
```

Use `mergeOptions([...])` to add to an existing set without replacing it. The default option set lives in `config/orchid-laraberg.php` and is read on every `Laraberg::make()` call.

## Media uploads

Set the special `_mediaUploadEndpoint` option to a route URL — the field wires that URL into Gutenberg's `mediaUpload` callback, so Image / Gallery / Cover / Video blocks send selected files there.

```php
Laraberg::make('content')
    ->setOptions([
        '_mediaUploadEndpoint' => route('platform.laraberg.media'),
    ]);
```

The frontend posts `multipart/form-data` with `files[]`, plus a CSRF header (`X-CSRF-TOKEN` from `<meta name="csrf-token">` if present, otherwise `X-XSRF-TOKEN` derived from the cookie — one of them will be accepted by Laravel's `VerifyCsrfToken` middleware).

Your endpoint must return JSON in this shape:

```json
[
    {
        "id": 42,
        "url": "https://example.test/storage/2026/05/17/abc.png",
        "mime": "image/png",
        "title": "logo.png",
        "alt": "logo.png",
        "caption": ""
    }
]
```

### Reference implementation

Using Orchid's built-in `Attachment` (deduplicates by hash, ships with the platform):

```php
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Orchid\Attachment\File;

class LarabergMediaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'file|max:20480',
        ]);

        $payload = [];

        foreach ($request->file('files') as $uploaded) {
            $attachment = (new File($uploaded))->load();

            $payload[] = [
                'id'      => $attachment->id,
                'url'     => $attachment->url(),
                'mime'    => $attachment->mime,
                'title'   => $attachment->original_name,
                'alt'     => $attachment->original_name,
                'caption' => '',
            ];
        }

        return response()->json($payload);
    }
}
```

Route — register it **inside `routes/platform.php`** so the `platform` middleware adds auth + CSRF + the dashboard prefix:

```php
Route::post('/laraberg/media', LarabergMediaController::class)
    ->name('platform.laraberg.media');
```

### Operational notes

- Set `APP_URL` to the URL the editor is opened on (`http://example.test:81`). `Storage::disk('public')->url(...)` builds absolute URLs from it; if `APP_URL` is wrong, every uploaded image 404s.
- `php artisan storage:link` must have been run; otherwise the `/storage/...` URL has no symlink to serve.
- Default `nginx` `client_max_body_size` is `1m`. Raise it for larger uploads. PHP `upload_max_filesize` and `post_max_size` apply too — the real ceiling is the minimum of nginx / php / `files.*|max:` validator.

## Paste cleanup

When pasting from Word, Google Docs, or a browser page, Gutenberg's `pasteHandler` parses the HTML through a multi-pass DOMParser pipeline and matches it against every registered block. Rich HTML from word processors can trigger multi-second freezes.

The field auto-registers a `blocks.pasteHandler` filter that strips, before Gutenberg sees the HTML:

- Word-only attributes — `style`, `class`, `lang`, `mso-*`
- Conditional comments — `<!--[if mso]>...<![endif]-->`
- Namespaced tags — `<o:p>`, `<w:*>`, `<m:*>`, `<st1:*>`
- Bare `<font>` / `<span>` wrappers

For most Word / Docs paste flows this turns multi-second freezes into instant pastes. No configuration needed.

Plain-text paste (`Ctrl+Shift+V` / `⌘+Shift+V`) bypasses the filter entirely and is always fast.

## Custom blocks

Anything that works against `van-ons/laraberg` v2.0.x works here unchanged — this wrapper does not touch block registration. See the upstream [custom block docs](https://github.com/VanOns/laraberg#custom-blocks).

The bundled WordPress packages are exposed via the global `Laraberg` object:

- `Laraberg.wordpress.blockEditor`
- `Laraberg.wordpress.blocks`
- `Laraberg.wordpress.components`
- `Laraberg.wordpress.data`
- `Laraberg.wordpress.element`
- `Laraberg.wordpress.hooks`
- `Laraberg.wordpress.serverSideRender`

## Credits

This package is a thin Orchid-flavored wrapper around [van-ons/laraberg](https://github.com/VanOns/laraberg) (GPL-3.0-or-later). All of the heavy lifting — bundling Gutenberg, rendering blocks, exposing WordPress packages — happens in that project.
