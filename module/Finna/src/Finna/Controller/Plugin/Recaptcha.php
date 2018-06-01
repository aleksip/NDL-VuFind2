<?php
/**
 * VuFind Recaptcha controller plugin
 *
 * PHP version 7
 *
 * Copyright (C) The National Library of Finland 2017-2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Plugin
 * @author   Joni Nevalainen <joni.nevalainen@gofore.com>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace Finna\Controller\Plugin;

use Zend\I18n\Translator\TranslatorInterface;
use Zend\Session\SessionManager;

/**
 * Recaptcha controller plugin.
 *
 * @category VuFind
 * @package  Plugin
 * @author   Joni Nevalainen <joni.nevalainen@gofore.com>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/
 */
class Recaptcha extends \VuFind\Controller\Plugin\Recaptcha
{
    /**
     * Bypassed authentication methods
     *
     * @var array
     */
    protected $bypassCaptcha = [];

    /**
     * Authentication manager
     *
     * @var ServiceManager
     */
    protected $authManager;

    /**
     * Session manager
     *
     * @var SessionManager
     */
    protected $sessionManager;

    /**
     * Minimum interval between consecutive actions (seconds)
     *
     * @var int
     */
    protected $actionInterval;

    /**
     * Translator
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Recaptcha constructor.
     *
     * @param \ZendService\ReCaptcha\ReCaptcha $r              ReCaptcha object
     * @param \VuFind\Config                   $config         Configuration
     * @param \VuFind\Auth\Manager             $authManager    Authentication Manager
     * @param SessionManager                   $sessionManager Session Manager
     * @param TranslatorInterface              $translator     Translator
     *
     * @return Recaptcha
     */
    public function __construct($r, $config, \VuFind\Auth\Manager $authManager,
        SessionManager $sessionManager, TranslatorInterface $translator
    ) {
        parent::__construct($r, $config);
        $this->authManager = $authManager;
        $this->sessionManager = $sessionManager;
        $this->translator = $translator;
        $this->actionInterval = !empty($config->Captcha->actionInterval)
            ? $config->Captcha->actionInterval : 60;
        if (!empty($config->Captcha->bypassCaptcha)) {
            $trimLowercase = function ($str) {
                return strtolower(trim($str));
            };

            $bypassCaptcha = $config->Captcha->bypassCaptcha->toArray();
            foreach ($bypassCaptcha as $domain => $authMethods) {
                $this->bypassCaptcha[$domain] = array_map(
                    $trimLowercase,
                    explode(',', $authMethods)
                );
            }
        }
    }

    /**
     * Return whether a specific form is set for Captcha in the config. Takes into
     * account authentication methods which should be bypassed.
     *
     * @param string|bool $domain The specific config term are we checking; ie. "sms"
     *
     * @return bool
     */
    public function active($domain = false)
    {
        if (!$domain || empty($this->bypassCaptcha[$domain])) {
            return parent::active($domain);
        }

        $user = $this->authManager->isLoggedIn();

        $bypassCaptcha = $user && in_array(
            strtolower($user->finna_auth_method),
            $this->bypassCaptcha[$domain]
        );
        return $bypassCaptcha ? false : parent::active($domain);
    }

    /**
     * Normally, this would pull the captcha field from POST and check them for
     * accuracy, but we're not actually using the captcha, so only check that other
     * conditions are valid.
     *
     * @return bool
     */
    public function validate()
    {
        if (!$this->active()) {
            return true;
        }

        // Session checks
        $storage = new \Zend\Session\Container(
            'SessionState', $this->sessionManager
        );
        $timestamp = isset($storage->lastProtectedActionTime)
            ? $storage->lastProtectedActionTime : $storage->sessionStartTime;
        $passed = time() - $timestamp >= $this->actionInterval;
        if ($passed) {
            $storage->lastProtectedActionTime = time();
        }

        if (!$passed && $this->errorMode != 'none') {
            $error = str_replace(
                '%%interval%%',
                $this->actionInterval,
                $this->translator->translate('protected_action_interval_not_passed')
            );

            if ($this->errorMode == 'flash') {
                $this->getController()->flashMessenger()->addErrorMessage($error);
            } else {
                throw new \Exception($error);
            }
        }
        return $passed;
    }
}
