<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9161dbb779a62d8efa2e2da26bcb9a39
{
    public static $prefixLengthsPsr4 = array (
        'b' => 
        array (
            'bashkarev\\email\\' => 16,
        ),
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'bashkarev\\email\\' => 
        array (
            0 => __DIR__ . '/..' . '/bashkarev/email/src',
        ),
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/..' . '/composer/installers/src/Composer/Installers',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9161dbb779a62d8efa2e2da26bcb9a39::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9161dbb779a62d8efa2e2da26bcb9a39::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}