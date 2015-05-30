<?php
namespace Environment;

/**
 * Provides information about the current system platform.
 */
abstract class Platform
{
    /**
     * @var int Indicates an unrecognized operating system.
     */
    const UNKNOWN = 0;

    /**
     * @var int Indicates a Unix-based or Unix-like operating system.
     */
    const UNIX = 0b0001;

    /**
     * @var int Indicates a Microsoft Windows operating system.
     */
    const WINDOWS = 0b0010;

    /**
     * @var int Indicates a Linux operating system or Linux distribution.
     */
    const LINUX = 0b0101;

    /**
     * @var int Indicates a Darwin-based system, such as Mac OS X.
     */
    const DARWIN = 0b1001;

    /**
     * @var int Indicates a FreeBSD operating system.
     */
    const FREEBSD = 0b10001;

    /**
     * @var int Hewlett-Packard UNIX systems.
     */
    const HP_UX = 0b100001;

    /**
     * @var int IBM AIX (Advanced Interactive eXecutive) systems.
     */
    const AIX = 0b1000001;

    /**
     * @var int Indicates a Sun Solaris/SunOS or OpenSolaris operating system.
     */
    const SOLARIS = 0b10000001;

    /**
     * Gets the number of logical processors on the system.
     *
     * @return int The number of processors.
     */
    public static function getCpuCount()
    {
        // Most UNIX systems
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            return substr_count($cpuinfo, 'processor');
        }

        // Use WMI on Windows
        if (self::isOS(self::WINDOWS)) {
            if (@exec('wmic cpu get NumberOfCores', $output) !== false) {
                return intval($output[1]);
            }
        }

        // Use sysctl as a last resort on other systems, like old BSD releases
        $ncpu = @exec('sysctl -n hw.ncpu');
        if ($ncpu !== false) {
            return intval($ncpu);
        }

        return 1;
    }

    /**
     * Gets the CPU architecture.
     *
     * @return string The CPU architecture name.
     */
    public static function getArch()
    {
        return php_uname('m');
    }

    /**
     * Gets the name of the local computer.
     *
     * @return string The NetBIOS name or hostname of the local computer.
     */
    public static function getMachineName()
    {
        return php_uname('n');
    }

    /**
     * Gets the operating system the environment is currently running on.
     *
     * @return int Bitmask representing the operating system constant.
     *
     * This method has not been tested on all platforms, so it may not detect
     * untested platforms correctly.
     */
    public static function getOS()
    {
        // what does PHP say we are on?
        $uname = strtolower(php_uname('s'));

        // generic linux or linux distro (we don't care which)
        if ($uname === 'linux' || file_exists('/proc/version')) {
            return self::LINUX;
        }

        // freebsd system
        if ($uname === 'freebsd') {
            return self::FREEBSD;
        }

        // some solaris return as 'SunOS'
        if ($uname === 'sunos' || $uname === 'solaris') {
            return self::SOLARIS;
        }

        // darwin-based; no way to tell for sure if it is OS X
        if ($uname === 'darwin') {
            return self::DARWIN;
        }

        // HP-UX system
        if ($uname === 'hp-ux') {
            return self::HP_UX;
        }

        // IBM AIX
        if ($uname === 'aix') {
            return self::AIX;
        }

        // some unknown unix-based system
        if ($uname === 'unix') {
            return self::UNIX;
        }

        // some name of win-something-or-other
        if (substr($uname, 0, 3) === 'win') {
            return self::WINDOWS;
        }

        // we really have no idea
        return self::UNKNOWN;
    }

    /**
     * Checks if the system is a given platform.
     *
     * Supports derivative operating systems. For example, if the platform is Linux, checking for Unix
     * will also return true.
     *
     * @param int $platform The platform to check.
     *
     * @return bool True if the system is the given OS or a derivative.
     */
    public static function isOS($os)
    {
        return (self::getOS() & $os) === $os;
    }

    /**
     * Gets the platform release; typically a version string.
     *
     * This method tries really hard to return an alphanumeric string, maybe
     * even in SemVer format, but there are no guarantees.
     *
     * @see https://msdn.microsoft.com/library/windows/desktop/ms724832.aspx for
     * a guide on what Windows release versions can indicate.
     *
     * @return string
     */
    public static function release()
    {
        // OS X has its own way of doing things.
        if (self::isOS(self::DARWIN)) {
            return self::getOSXRelease();
        }

        // Solaris and SunOS store release info in a file, like a good UNIX.
        if (self::isOS(self::SOLARIS)) {
            return file_get_contents('/etc/release');
        }

        // For Windows, Linux and other UNIXes, just return the uname release.
        return php_uname('r');
    }

    /**
     * Gets the platform version; typically includes build information.
     *
     * Note that the version returned varies widely depending on platform
     * specifics. You will need to determine the platform first before the
     * version can be used in any sort of meaningful way.
     *
     * @return string A string that probably includes some version information.
     */
    public static function version()
    {
        return php_uname('v');
    }

    /**
     * Gets the Linux distribution information.
     *
     * @return array An associative array of all possible information about the
     *               distribution that could be determined.
     */
    public static function linuxDistribution()
    {
        $info = [];

        // Nothing to do if we're not on Linux.
        if (!self::isOS(self::LINUX)) {
            return $info;
        }

        // Check for the uncommon LSB release file for release info. Only
        // Ubuntu and Linux Mint actually use this I think.
        if (is_file('/etc/lsb-release')) {
            $release = self::parseReleaseFile('/etc/lsb-release');

            // LSB release files are actually very tidy!
            $info['name'] = strtolower($release['DISTRIB_ID']);
            $info['release'] = $release['DISTRIB_RELEASE'];
            $info['codename'] = $release['DISTRIB_CODENAME'];
            $info['pretty_name'] = $release['DISTRIB_DESCRIPTION'];
        }

        // If we're on a modern distro, we can use the "os-release" file that is
        // being standardized (thankfully) to find distro info. More info at
        // http://www.freedesktop.org/software/systemd/man/os-release.html
        // and http://0pointer.de/blog/projects/os-release.
        elseif (is_file('/etc/os-release')) {
            $release = self::parseReleaseFile('/etc/os-release');

            $info['name'] = $release['ID'];
            if (isset($release['VERSION_ID'])) {
                $info['release'] = $release['VERSION_ID'];
            }
            if (isset($release['PRETTY_NAME'])) {
                $info['pretty_name'] = $release['PRETTY_NAME'];
            }
        }

        // We couldn't find any generic information, so now look for specific
        // distros by release files, issue info, anything. Add code below for
        // identifying unrecognized or old distros.

        // SuSE; usually SLES
        elseif (is_file('/etc/SuSE-release')) {
            $release = self::parseReleaseFile('/etc/SuSE-release');

            $info['name'] = 'suse';

            if (isset($release['VERSION'])) {
                $info['release'] = $release['VERSION'].'.'.$release['PATCHLEVEL'];
            }
        }

        // Fedora
        elseif (is_file('/etc/fedora-release')) {
            $info['name'] = 'fedora';

            $release = file_get_contents('/etc/fedora-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Mandrake Linux
        elseif (is_file('/etc/mandrake-release')) {
            $info['name'] = 'mandrake';

            $release = file_get_contents('/etc/mandrake-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // CentOS and derivatives
        elseif (is_file('/etc/centos-release')) {
            $info['name'] = 'centos';

            $release = file_get_contents('/etc/centos-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Gentoo Linux
        elseif (is_file('/etc/gentoo-release')) {
            $info['name'] = 'gentoo';
        }

        // Slackware Linux
        elseif (is_file('/etc/slackware-version')) {
            $info['name'] = 'slackware';

            $release = file_get_contents('/etc/slackware-version');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Redhat and derivatives
        elseif (is_file('/etc/redhat-release')) {
            $info['name'] = 'redhat';

            $release = file_get_contents('/etc/redhat-release');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // Check for Debian last to avoid false positives for the many Debian forks
        elseif (is_file('/etc/debian_version')) {
            $info['name'] = 'debian';

            $release = file_get_contents('/etc/debian_version');
            if (preg_match('/[0-9\.]+/', $release, $matches) === 1) {
                $info['release'] = $matches[0];
            }
        }

        // If we haven't found anything yet, see if there is any meaningful info
        // in /etc/issue, which is somewhat common, but doesn't strictly contain
        // distro info.
        elseif (is_file('/etc/issue')) {
            $issueMessage = file_get_contents('/etc/issue');

            // If the message doesn't match the expression, it probably isn't
            // a distro description anyway.
            if (preg_match('/([^0-9\r\n]+)\s+(?:release\s+)?(\d+(\.\d+)*)/i', $issueMessage, $matches) === 1) {
                $info['pretty_name'] = $matches[0];
                $info['name'] = strtolower($matches[1]);
                $info['release'] = $matches[2];
            }
        }

        return $info;
    }

    /**
     * Parses a Linux distribution release file and returns the data it contains.
     *
     * @param string $filename The path to the release file.
     *
     * @return array A hash of keys and values.
     */
    private static function parseReleaseFile($filename)
    {
        $contents = file_get_contents($filename);

        // Parse all of the variables in the release file.
        if (preg_match_all('/(\w+)\s*=\s*["\']?([^"\'\r\n]*)["\']?[\r\n]*/', $contents, $matches, PREG_SET_ORDER) === false) {
            throw new \Exception();
        }

        $vars = [];
        foreach ($matches as $match) {
            $vars[strtoupper($match[1])] = $match[2];
        }

        return $vars;
    }

    /**
     * Gets the Mac OS X version.
     *
     * @return string
     */
    private static function getOSXRelease()
    {
        $systemVersion = new \DOMDocument();
        $systemVersion->loadXML('/System/Library/CoreServices/SystemVersion.plist');

        $plistNodes = $systemVersion->documentElement->childNodes->item(0)->childNodes;

        for ($i = 0; $i < $plistNodes->length; $i++) {
            if ($plistNodes->item($i)->nodeValue == 'ProductVersion') {
                return $plistNodes->item($i + 1)->nodeValue;
            }
        }

        return '';
    }
}
