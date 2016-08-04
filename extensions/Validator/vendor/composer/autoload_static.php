<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7bdfe5ea12f268940a7860eeb1e3286a
{
    public static $files = array (
        'd1715cacc3c23b16a030645514266a76' => __DIR__ . '/..' . '/data-values/interfaces/Interfaces.php',
        '7cb394c3af2b1ae832979b0368e0da62' => __DIR__ . '/..' . '/data-values/data-values/DataValues.php',
        '0dd9431cbbfa9ed9cb9d565d7129dbaf' => __DIR__ . '/..' . '/data-values/validators/Validators.php',
        '90559502573a0d473dc66fde5c0ff7e2' => __DIR__ . '/..' . '/data-values/common/Common.php',
        'af3cc937b8a54e5b4209c82d6cfe8889' => __DIR__ . '/..' . '/param-processor/param-processor/DefaultConfig.php',
        'c3ae67574219cc56cab6c30ef8877b85' => __DIR__ . '/../..' . '/Validator.php',
    );

    public static $prefixLengthsPsr4 = array (
        'V' => 
        array (
            'ValueValidators\\' => 16,
            'ValueParsers\\' => 13,
            'ValueFormatters\\' => 16,
        ),
        'P' => 
        array (
            'ParamProcessor\\' => 15,
        ),
        'D' => 
        array (
            'DataValues\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ValueValidators\\' => 
        array (
            0 => __DIR__ . '/..' . '/data-values/interfaces/src/ValueValidators',
            1 => __DIR__ . '/..' . '/data-values/validators/src',
        ),
        'ValueParsers\\' => 
        array (
            0 => __DIR__ . '/..' . '/data-values/interfaces/src/ValueParsers',
            1 => __DIR__ . '/..' . '/data-values/common/src/ValueParsers',
        ),
        'ValueFormatters\\' => 
        array (
            0 => __DIR__ . '/..' . '/data-values/interfaces/src/ValueFormatters',
            1 => __DIR__ . '/..' . '/data-values/common/src/ValueFormatters',
        ),
        'ParamProcessor\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/ParamProcessor',
            1 => __DIR__ . '/..' . '/param-processor/param-processor/src',
        ),
        'DataValues\\' => 
        array (
            0 => __DIR__ . '/..' . '/data-values/common/src/DataValues',
        ),
    );

    public static $prefixesPsr0 = array (
        'D' => 
        array (
            'DataValues\\' => 
            array (
                0 => __DIR__ . '/..' . '/data-values/data-values/src',
            ),
        ),
    );

    public static $classMap = array (
        'Comparable' => __DIR__ . '/..' . '/data-values/data-values/src/interfaces/Comparable.php',
        'Copyable' => __DIR__ . '/..' . '/data-values/data-values/src/interfaces/Copyable.php',
        'DataValues\\Tests\\DataValueTest' => __DIR__ . '/..' . '/data-values/data-values/tests/phpunit/DataValueTest.php',
        'Hashable' => __DIR__ . '/..' . '/data-values/data-values/src/interfaces/Hashable.php',
        'Immutable' => __DIR__ . '/..' . '/data-values/data-values/src/interfaces/Immutable.php',
        'ParamProcessor\\Tests\\Definitions\\NumericParamTest' => __DIR__ . '/..' . '/param-processor/param-processor/tests/phpunit/Definitions/NumericParamTest.php',
        'ParamProcessor\\Tests\\Definitions\\ParamDefinitionTest' => __DIR__ . '/..' . '/param-processor/param-processor/tests/phpunit/Definitions/ParamDefinitionTest.php',
        'ParserHook' => __DIR__ . '/../..' . '/src/legacy/ParserHook.php',
        'ParserHookCaller' => __DIR__ . '/../..' . '/src/legacy/ParserHook.php',
        'ValueFormatters\\Test\\ValueFormatterTestBase' => __DIR__ . '/..' . '/data-values/interfaces/tests/ValueFormatters/ValueFormatterTestBase.php',
        'ValueParsers\\Normalizers\\Test\\NullStringNormalizerTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/Normalizers/NullStringNormalizerTest.php',
        'ValueParsers\\Test\\BoolParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/BoolParserTest.php',
        'ValueParsers\\Test\\DispatchingValueParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/DispatchingValueParserTest.php',
        'ValueParsers\\Test\\FloatParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/FloatParserTest.php',
        'ValueParsers\\Test\\IntParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/IntParserTest.php',
        'ValueParsers\\Test\\NullParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/NullParserTest.php',
        'ValueParsers\\Test\\StringParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/StringParserTest.php',
        'ValueParsers\\Test\\StringValueParserTest' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/StringValueParserTest.php',
        'ValueParsers\\Test\\ValueParserTestBase' => __DIR__ . '/..' . '/data-values/common/tests/ValueParsers/ValueParserTestBase.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7bdfe5ea12f268940a7860eeb1e3286a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7bdfe5ea12f268940a7860eeb1e3286a::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit7bdfe5ea12f268940a7860eeb1e3286a::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit7bdfe5ea12f268940a7860eeb1e3286a::$classMap;

        }, null, ClassLoader::class);
    }
}
