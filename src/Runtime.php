<?php
namespace Environ;

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
     * Note that versions older than PHP 7 did not support true 64-bit on
     * Windows. As a result, this method will always return false on older
     * versions of PHP on Windows.
     *
     * @return bool True if the environment is 64-bit, otherwise false.
     */
    public static function is64Bit()
    {
        return PHP_INT_SIZE === 8;
    }

    /**
     * Gets the path to the current interpreter executable.
     *
     * @return string The path to the current interpreter executable.
     */
    public static function path()
    {
        return PHP_BINARY;
    }

    /**
     * Gets the version number of the runtime as a string.
     *
     * Note that the versioning scheme can vary between different runtimes. You
     * may need to do additional processing on the returned string to use it for
     * non-display purposes.
     *
     * @return string The runtime version string.
     */
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

    /**
     * Checks if the runtime is the official PHP group interpreter.
     *
     * @return bool True if the runtime is a canonical interpreter, otherwise false.
     */
    public static function isCanonical()
    {
        return !self::isHHVM() && !self::isJPHP();
    }

    /**
     * Checks if the runtime is HHVM.
     *
     * @return bool True if the runtime is HHVM, otherwise false.
     */
    public static function isHHVM()
    {
        return defined('HHVM_VERSION');
    }

    /**
     * Checks if the runtime is JPHP.
     *
     * @return bool True if the runtime is JPHP, otherwise false.
     */
    public static function isJPHP()
    {
        // Not a perfectly accurate way of checking. This may need to be changed.
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
