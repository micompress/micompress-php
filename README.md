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

Errors throw `MiCompress\CompressException`; `getErrorCode()` returns e.g. `UNSUPPORTED`, `LOGIN_REQUIRED`, `NETWORK`.

## Command line

```bash
vendor/bin/micompress photo.jpg slides.pdf --preset=extreme --out=compressed/
```

## How it works

Each file is uploaded over HTTPS to micompress.com, compressed there, and the result is downloaded back.
No account is needed within the anonymous size limits; pass `['token' => '<jwt>']` for higher limits.
Uploaded files are deleted from the server automatically — see the [privacy policy](https://micompress.com/privacy).

Supported formats include JPG, PNG, WebP, GIF, AVIF, HEIC, TIFF, SVG, RAW, PDF, MP4, MOV, MP3, WAV,
DOCX, XLSX, PPTX, EPUB, ZIP, 7Z and more.

## License

MIT © [MiCompress](https://micompress.com)
