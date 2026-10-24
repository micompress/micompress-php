<?php

namespace MiCompress;

/**
 * File extension -> MiCompress category. Mirrors the other MiCompress clients;
 * the authoritative list lives on the server.
 */
final class Categories
{
    public const EXT_CATEGORY = [
        // image
        'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'webp' => 'image', 'gif' => 'image',
        'bmp' => 'image', 'tiff' => 'image', 'tif' => 'image', 'avif' => 'image', 'svg' => 'image',
        'heic' => 'image', 'heif' => 'image', 'ico' => 'image', 'psd' => 'image', 'dng' => 'image',
        'raw' => 'image', 'cr2' => 'image', 'arw' => 'image', 'nef' => 'image',
        // video
        'mp4' => 'video', 'mov' => 'video', 'avi' => 'video', 'mkv' => 'video', 'webm' => 'video',
        'mpeg' => 'video', 'mpg' => 'video', 'm4v' => 'video', 'wmv' => 'video', 'flv' => 'video', '3gp' => 'video',
        // audio
        'mp3' => 'audio', 'wav' => 'audio', 'flac' => 'audio', 'ogg' => 'audio', 'm4a' => 'audio',
        'aac' => 'audio', 'wma' => 'audio', 'opus' => 'audio',
        // pdf (dedicated endpoint)
        'pdf' => 'pdf',
        // document
        'csv' => 'document', 'json' => 'document', 'xml' => 'document', 'md' => 'document',
        'odt' => 'document', 'ods' => 'document', 'docx' => 'document', 'doc' => 'document',
        'ppt' => 'document', 'pptx' => 'document', 'xls' => 'document', 'xlsx' => 'document',
        'epub' => 'document', 'html' => 'document', 'htm' => 'document', 'hwp' => 'document',
        'txt' => 'document', 'rtf' => 'document',
        // archive
        'zip' => 'archive', '7z' => 'archive', 'tar' => 'archive', 'gz' => 'archive', 'tgz' => 'archive', 'rar' => 'archive',
    ];

    public const CATEGORY_PATH = [
        'image' => 'image-compressors',
        'video' => 'video-compressors',
        'audio' => 'audio-compressors',
        'document' => 'document-compressors',
        'pdf' => 'pdf-compressors',
        'archive' => 'archive-compressors',
    ];

    public static function extOf(string $filename): string
    {
        return strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function categoryFor(string $filename): ?string
    {
        return self::EXT_CATEGORY[self::extOf($filename)] ?? null;
    }
}
