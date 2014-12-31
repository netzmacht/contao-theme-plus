<?php

/**
 * This file is part of bit3/contao-theme-plus.
 *
 * (c) Tristan Lins <tristan.lins@bit3.de>
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    bit3/contao-theme-plus
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @copyright  bit3 UG <https://bit3.de>
 * @link       https://github.com/bit3/contao-theme-plus
 * @license    http://opensource.org/licenses/LGPL-3.0 LGPL-3.0+
 * @filesource
 */

namespace Bit3\Contao\ThemePlus;

/**
 * Class ThemePlusEnvironment
 */
class ThemePlusEnvironment
{
    /**
     * Singleton
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return ThemePlusEnvironment
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new ThemePlusEnvironment();

            if (TL_MODE == 'FE') {
                // request the BE_USER_AUTH login status
                $cookieName = 'BE_USER_AUTH';
                $ip         = \Environment::get('ip');
                $hash       = \Input::cookie($cookieName);

                // Check the cookie hash
                if ($hash == sha1(session_id() . (!$GLOBALS['TL_CONFIG']['disableIpCheck'] ? $ip : '') . $cookieName)) {
                    $session = \Database::getInstance()
                        ->prepare("SELECT * FROM tl_session WHERE hash=? AND name=?")
                        ->execute($hash, $cookieName);

                    // Try to find the session in the database
                    if ($session->next()) {
                        $time = time();

                        // Validate the session
                        if ($session->sessionID == session_id()
                            && ($GLOBALS['TL_CONFIG']['disableIpCheck'] || $session->ip == $ip)
                            && $session->hash == $hash
                            && ($session->tstamp + $GLOBALS['TL_CONFIG']['sessionTimeout']) >= $time
                        ) {
                            $userId = $session->pid;
                            $user   = \UserModel::findByPk($userId);

                            if ($user) {
                                if (\Input::get('theme_plus_compile_assets')) {
                                    static::setDesignerMode(false);
                                    static::$instance->preCompileModeEnabled = true;
                                } elseif ($user->themePlusDesignerMode) {
                                    static::setDesignerMode(true);
                                }
                            }
                        }
                    }
                }
            }
        }
        return static::$instance;
    }

    /**
     * If is in live mode.
     *
     * @var bool
     */
    private $liveModeEnabled = true;

    /**
     * If is in pre-compile mode.
     *
     * @var bool
     */
    private $preCompileModeEnabled = false;

    /**
     * Singleton constructor.
     */
    protected function __construct()
    {
    }

    /**
     * Get productive mode status.
     */
    public static function isLiveMode()
    {
        return static::getInstance()->liveModeEnabled
            ? true
            : false;
    }

    /**
     * Set productive mode.
     */
    public static function setLiveMode($liveMode = true)
    {
        static::getInstance()->liveModeEnabled = $liveMode;
    }

    /**
     * Get productive mode status.
     */
    public static function isDesignerMode()
    {
        return static::getInstance()->liveModeEnabled
            ? false
            : true;
    }

    /**
     * Set designer mode.
     */
    public static function setDesignerMode($designerMode = true)
    {
        static::getInstance()->liveModeEnabled = !$designerMode;
    }

    /**
     * Determine if the pre-compile mode is enabled.
     *
     * @bool
     */
    public static function isInPreCompileMode()
    {
        return static::getInstance()->preCompileModeEnabled;
    }
}
