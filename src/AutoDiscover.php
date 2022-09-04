<?php

/*
 * This file is part of Chrome PHP.
 *
 * (c) Soufiane Ghzal <sghzal@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HeadlessChromium;

class AutoDiscover
{
    /**
     * @var callable(): string
     */
    private $osFamily;

    /**
     * @param (callable(): string)|null $osFamily
     */
    public function __construct(?callable $osFamily = null)
    {
        $this->osFamily = $osFamily ?? function (): string {
            return \PHP_OS_FAMILY;
        };
    }

    public function guessChromeBinaryPath(): string
    {
        if (\array_key_exists('CHROME_PATH', $_SERVER)) {
            return $_SERVER['CHROME_PATH'];
        }

        switch (($this->osFamily)()) {
            case 'Darwin':
                return '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
            case 'Windows':
                return self::getFromRegistry() ?? '%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe';
            default:
                return null === self::shellExec('command -v google-chrome') ? 'chrome' : 'google-chrome';
        }
    }

    private static function getFromRegistry(): ?string
    {
        $registryKey = self::shellExec(
            'reg query "HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe" /ve'
        );

        if (null === $registryKey) {
            return null;
        }

        \preg_match('/.:(?!.*:).*/', $registryKey, $matches);

        return $matches[0] ?? null;
    }

    private static function shellExec(string $command): ?string
    {
        try {
            $result = @\shell_exec($command);

            return \is_string($result) ? $result : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
