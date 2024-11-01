<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita03de9933a4084a585202cc3e18f4297
{
    public static $prefixLengthsPsr4 = array (
        'L' => 
        array (
            'League\\Pipeline\\' => 16,
        ),
        'J' => 
        array (
            'JsonSchema\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'League\\Pipeline\\' => 
        array (
            0 => __DIR__ . '/..' . '/league/pipeline/src',
        ),
        'JsonSchema\\' => 
        array (
            0 => __DIR__ . '/..' . '/justinrainbow/json-schema/src/JsonSchema',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita03de9933a4084a585202cc3e18f4297::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita03de9933a4084a585202cc3e18f4297::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita03de9933a4084a585202cc3e18f4297::$classMap;

        }, null, ClassLoader::class);
    }
}
