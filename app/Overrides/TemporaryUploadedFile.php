<?php

namespace Livewire\Features\SupportFileUploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

/**
 * Drop-in replacement for Livewire's TemporaryUploadedFile.
 *
 * Fixes: tmpfile() returns false on Windows + PHP 8.4 + artisan serve,
 * crashing with "stream_get_meta_data(): Argument #1 must be of type
 * resource, false given".
 *
 * Fix: use the actual storage path of the uploaded file instead of
 * creating a throwaway tmpfile() handle. The parent UploadedFile
 * constructor only needs a valid path string.
 *
 * Loaded via Composer exclude-from-classmap + classmap autoloading,
 * so this replaces the vendor class without modifying vendor files.
 */
class TemporaryUploadedFile extends UploadedFile
{
    protected $disk;

    protected $storage;

    protected $path;

    public function __construct($path, $disk)
    {
        $this->disk = $disk;
        $this->storage = Storage::disk($this->disk);
        $this->path = FileUploadConfiguration::path($path, false);

        // Use the actual storage path instead of tmpfile().
        // The parent UploadedFile just needs a valid file path string.
        $storagePath = $this->storage->path($this->path);

        // Ensure parent directory exists (first upload on fresh install)
        $dir = dirname($storagePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // If the file doesn't exist yet on disk (initial construction before
        // the upload is written), create a placeholder so UploadedFile doesn't
        // error. The real file content is managed by Livewire's upload process.
        if (! file_exists($storagePath)) {
            touch($storagePath);
        }

        parent::__construct($storagePath, $this->path);

        if (app()->runningUnitTests()) {
            @touch($this->path(), now()->timestamp);
        }
    }

    public function getPath(): string
    {
        return $this->storage->path(FileUploadConfiguration::directory());
    }

    public function isValid(): bool
    {
        return true;
    }

    public function getSize(): int
    {
        if (app()->runningUnitTests() && str($this->getFilename())->contains('-size=')) {
            return (int) str($this->getFilename())->between('-size=', '.')->__toString();
        }

        return (int) $this->storage->size($this->path);
    }

    public function getMimeType(): string
    {
        if (app()->runningUnitTests() && str($this->getFilename())->contains('-mimeType=')) {
            $escapedMimeType = str($this->getFilename())->between('-mimeType=', '-');

            return (string) $escapedMimeType->replace('_', '/');
        }

        $mimeType = $this->storage->mimeType($this->path);

        if (in_array($mimeType, ['application/octet-stream', 'inode/x-empty', 'application/x-empty'])) {
            $detector = new FinfoMimeTypeDetector;

            $mimeType = $detector->detectMimeTypeFromPath($this->path) ?: 'text/plain';
        }

        return $mimeType;
    }

    public function getFilename(): string
    {
        return $this->getName($this->path);
    }

    public function getRealPath(): string
    {
        return $this->storage->path($this->path);
    }

    public function getPathname(): string
    {
        return $this->getRealPath();
    }

    public function getClientOriginalName(): string
    {
        return $this->extractOriginalNameFromFilePath($this->path);
    }

    public function dimensions()
    {
        // Use the real file path directly instead of tmpfile()
        return @getimagesize($this->getRealPath());
    }

    public function temporaryUrl()
    {
        if (! $this->isPreviewable()) {
            throw new FileNotPreviewableException($this);
        }

        if ((FileUploadConfiguration::isUsingS3() or FileUploadConfiguration::isUsingGCS()) && ! app()->runningUnitTests()) {
            return $this->storage->temporaryUrl(
                $this->path,
                now()->addDay()->endOfHour(),
                ['ResponseContentDisposition' => 'attachment; filename="'.urlencode($this->getClientOriginalName()).'"']
            );
        }

        if (method_exists($this->storage->getAdapter(), 'getTemporaryUrl')) {
            return $this->storage->temporaryUrl($this->path, now()->addDay());
        }

        return URL::temporarySignedRoute(
            'livewire.preview-file', now()->addMinutes(30)->endOfHour(), ['filename' => $this->getFilename()]
        );
    }

    public function isPreviewable()
    {
        $supportedPreviewTypes = config('livewire.temporary_file_upload.preview_mimes', [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ]);

        return in_array($this->guessExtension(), $supportedPreviewTypes);
    }

    public function readStream()
    {
        return $this->storage->readStream($this->path);
    }

    public function exists()
    {
        return $this->storage->exists($this->path);
    }

    public function get()
    {
        return $this->storage->get($this->path);
    }

    public function delete()
    {
        return $this->storage->delete($this->path);
    }

    public function storeAs($path, $name = null, $options = [])
    {
        $options = $this->parseOptions($options);

        $disk = Arr::pull($options, 'disk') ?: $this->disk;

        $newPath = trim($path.'/'.$name, '/');

        Storage::disk($disk)->put(
            $newPath, $this->storage->readStream($this->path), $options
        );

        return $newPath;
    }

    public static function generateHashNameWithOriginalNameEmbedded($file)
    {
        $hash = str()->random(30);
        $meta = str('-meta'.base64_encode($file->getClientOriginalName()).'-')->replace('/', '_');
        $extension = '.'.$file->getClientOriginalExtension();

        return $hash.$meta.$extension;
    }

    public function hashName($path = null)
    {
        if (app()->runningUnitTests() && str($this->getFilename())->contains('-hash=')) {
            return str($this->getFilename())->between('-hash=', '-mimeType')->value();
        }

        return parent::hashName($path);
    }

    public function extractOriginalNameFromFilePath($path)
    {
        return base64_decode(head(explode('-', last(explode('-meta', str($path)->replace('_', '/'))))));
    }

    public static function createFromLivewire($filePath)
    {
        return new static($filePath, FileUploadConfiguration::disk());
    }

    public static function canUnserialize($subject)
    {
        if (is_string($subject)) {
            return (string) str($subject)->startsWith(['livewire-file:', 'livewire-files:']);
        }

        if (is_array($subject)) {
            return collect($subject)->contains(function ($value) {
                return static::canUnserialize($value);
            });
        }

        return false;
    }

    public static function unserializeFromLivewireRequest($subject)
    {
        if (is_string($subject)) {
            if (str($subject)->startsWith('livewire-file:')) {
                return static::createFromLivewire(str($subject)->after('livewire-file:'));
            }

            if (str($subject)->startsWith('livewire-files:')) {
                $paths = json_decode(str($subject)->after('livewire-files:'), true);

                return collect($paths)->map(function ($path) {
                    return static::createFromLivewire($path);
                })->toArray();
            }
        }

        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $subject[$key] = static::unserializeFromLivewireRequest($value);
            }
        }

        return $subject;
    }

    public function serializeForLivewireResponse()
    {
        return 'livewire-file:'.$this->getFilename();
    }

    public static function serializeMultipleForLivewireResponse($files)
    {
        return 'livewire-files:'.json_encode(collect($files)->map->getFilename());
    }
}
