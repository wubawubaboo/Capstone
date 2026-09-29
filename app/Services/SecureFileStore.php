<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

/**
 * Stores sensitive uploads (resident ID photos, selfies, incident report
 * attachments) encrypted with APP_KEY on the private disk, and decrypts them
 * only when an authorized controller action serves them.
 *
 * Encrypted files are named "<random>.<original extension>.enc", so the
 * content type can be recovered without decrypting. Files stored before
 * encryption was introduced have no ".enc" suffix and are served as-is until
 * `php artisan files:encrypt-sensitive` converts them.
 */
class SecureFileStore
{
    private const SUFFIX = '.enc';

    public function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $path = trim($directory, '/') . '/' . Str::random(40) . ".{$extension}" . self::SUFFIX;

        Storage::put($path, Crypt::encryptString($file->get()));

        return $path;
    }

    /** Encrypts a file already on the disk in place, returning its new path. */
    public function encryptExisting(string $path): string
    {
        if ($this->isEncrypted($path)) {
            return $path;
        }

        $encryptedPath = $path . self::SUFFIX;
        Storage::put($encryptedPath, Crypt::encryptString(Storage::get($path)));

        return $encryptedPath;
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $path !== '' && Storage::exists($path);
    }

    public function contents(string $path): string
    {
        $raw = Storage::get($path);

        return $this->isEncrypted($path) ? Crypt::decryptString($raw) : $raw;
    }

    /** An inline response for the decrypted file, never cached by the browser. */
    public function response(string $path): Response
    {
        return response($this->contents($path), 200, [
            'Content-Type' => $this->mimeType($path),
            'Content-Disposition' => 'inline; filename="' . basename($this->plainName($path)) . '"',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function isEncrypted(string $path): bool
    {
        return str_ends_with($path, self::SUFFIX);
    }

    private function plainName(string $path): string
    {
        return $this->isEncrypted($path) ? substr($path, 0, -strlen(self::SUFFIX)) : $path;
    }

    private function mimeType(string $path): string
    {
        $extension = strtolower(pathinfo($this->plainName($path), PATHINFO_EXTENSION));

        return MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? 'application/octet-stream';
    }
}
