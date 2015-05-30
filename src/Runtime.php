<?php
namespace Envir;

/**
 * Provides information about the current PHP runtime.
 */
abstract class Runtime
{
    /**
     * Checks if the environment is running in a 64-bit environment.
     *
     * Note that this checks for support for 64-bit operations in the PHP
     * runtime, not the system architecture. To check if the system is 64-bit,
     * {@see Platform::is64Bit()}.
     *
     * Note that versions older than PHP7 did not support true 64-bit on
     * Windows. As a result, this method will always return false on older
     * versions of PHP on Windows.
     *
     * @return bool True if the environment is 64-bit, otherwise false.
     */
    public static function is64Bit()
    {
        return PHP_INT_SIZE === 8;
    }

    public function path()
    {
        return PHP_BINDIR;
    }

    public static function version()
    {
        if (self::isJPHP()) {
            $info = new \php\lang\JavaClass('php.runtime.Information');
            return $info->getDeclaredFields()['CORE_VERSION']->get();
        } elseif (self::isHHVM()) {
            return HHVM_VERSION;
        }

        return PHP_VERSION;
    }

    public static function isCanonical()
    {
        return !self::isHHVM() && !self::isJPHP();
    }

    public static function isHHVM()
    {
        return defined('HHVM_VERSION');
    }

    public static function isJPHP()
    {
        return class_exists('php\lang\JavaClass', false);
    }

    /**
     * Checks if the execution environment is running as a server module.
     *
     * @return bool True if the environment is running as a server module, otherwise false.
     */
    public static function isServerModule()
    {
        return PHP_SAPI !== 'cli';
    }
}
