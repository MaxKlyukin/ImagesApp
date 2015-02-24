<?php

namespace App\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageHelper
{


    function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isValid(UploadedFile $file)
    {
        return
            in_array($file->getMimeType(), $this->app['config.allowedMimeTypes'])
            && in_array($file->getClientOriginalExtension(), $this->app['config.allowedExtensions']);
    }

    public function save(UploadedFile $file)
    {
        $imageName = $this->getName($file);

        return $file->move($this->app['config.imagesUploadDir'], $imageName);
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    private function getName(UploadedFile $file)
    {
        return sprintf(
            "%s.%s",
            sha1(openssl_random_pseudo_bytes(20)),
            $file->getClientOriginalExtension()
        );
    }

    public function remove($name)
    {
        if (file_exists($this->app['config.imagesUploadDir'] . "/" . $name)) {
            unlink($this->app['config.imagesUploadDir'] . "/" . $name);
        }
    }
}