<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class SdkController extends Controller
{
    /** Source directory of the bundled PHP SDK. */
    private function sdkPath(): string
    {
        return resource_path('sdk/crynova-php-sdk');
    }

    /** The SDK landing / getting-started page. */
    public function page()
    {
        return view('public.sdk');
    }

    /**
     * Build and stream the SDK as a zip archive on the fly.
     *
     * Generated from the source in resources/sdk so the download always
     * matches the committed code — nothing binary is stored in the repo.
     */
    public function download(): StreamedResponse
    {
        $root = $this->sdkPath();
        abort_unless(is_dir($root) && class_exists(\ZipArchive::class), 404);

        $tmp = tempnam(sys_get_temp_dir(), 'crynova-sdk');

        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }
            $local = 'crynova-php-sdk/' . ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($root))), '/');
            $zip->addFile($file->getPathname(), $local);
        }

        $zip->close();

        return response()->streamDownload(function () use ($tmp) {
            readfile($tmp);
            @unlink($tmp);
        }, 'crynova-php-sdk.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
