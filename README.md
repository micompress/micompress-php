# MiCompress for PHP

Compress **images, PDFs, video, audio, documents & archives** from PHP, powered by
[micompress.com](https://micompress.com/?utm_source=packagist&utm_medium=library&utm_campaign=extend).

```bash
composer require micompress/micompress
```

## Usage

```php
$client = new MiCompress\Client();

// Writes photo-min.jpg next to the original
$result = $client->compressFile('photo.jpg');
echo $result['savedPercent'] . "% smaller\n";

// Or get the bytes yourself
$result = $client->compress('report.pdf', ['preset' => 'extreme']);
file_put_contents('report-small.pdf', $result['bytes']);
```

Presets: `lossless`, `recommended` (default on the server), `extreme`. Images also accept `maxWidth`.

Errors throw `MiCompress\CompressException`; `getErrorCode()` returns e.g. `UNSUPPORTED`, `LOGIN_REQUIRED`,
`INVALID_API_KEY`, `API_QUOTA_REACHED`, `USAGE_LIMIT_REACHED`, `NETWORK`.

## Command line

```bash
vendor/bin/micompress photo.jpg slides.pdf --preset=extreme --out=compressed/
```

## API key

Small files work without an account. For bigger files and a monthly allowance, use your personal API key
(`mc_…`), shown on your [account page](https://micompress.com/account) — creating an account is free.

```php
$client = new MiCompress\Client(['apiKey' => getenv('MICOMPRESS_API_KEY')]);
```

```bash
export MICOMPRESS_API_KEY=mc_your_key_here
vendor/bin/micompress big-video.mp4
vendor/bin/micompress big-video.mp4 --api-key=mc_your_key_here   # the flag wins over the env var
```

| Plan | Compressions per month | Notes |
|------|------------------------|-------|
| No key (anonymous) | Anonymous limits | Small files only |
| Free account | 50 via the API | Files up to 100 MB |
| Plus ($9.99/mo) | 1,000 | Shared with website usage |
| Pro ($19.99/mo) | 5,000 | Shared with website usage |

When the allowance is used up, `getErrorCode()` returns `API_QUOTA_REACHED` (free) or `USAGE_LIMIT_REACHED`
(paid) — see [micompress.com/plans](https://micompress.com/plans). A wrong key gives `INVALID_API_KEY`.
The older `token` option (JWT login token) still works. Keep the key secret: anyone who has it can spend your quota.

## How it works

Each file is uploaded over HTTPS to micompress.com, compressed there, and the result is downloaded back.
No account is needed within the anonymous size limits; pass `['apiKey' => 'mc_…']` for higher limits.
Uploaded files are deleted from the server automatically — see the [privacy policy](https://micompress.com/privacy).

Supported formats include JPG, PNG, WebP, GIF, AVIF, HEIC, TIFF, SVG, RAW, PDF, MP4, MOV, MP3, WAV,
DOCX, XLSX, PPTX, EPUB, ZIP, 7Z and more.

## License

MIT © [MiCompress](https://micompress.com)
