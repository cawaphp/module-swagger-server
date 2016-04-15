<?php

/*
 * This file is part of the Сáша framework.
 *
 * (c) tchiotludo <http://github.com/tchiotludo>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types = 1);

namespace Cawa\SwaggerServer\Auth;

use Cawa\App\HttpApp;
use Cawa\Net\Ip;

class Basic extends AbstractAuth
{
    /**
     * @var array
     */
    private $services = [];

    /**
     * @var string
     */
    private $user;

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return array|bool
     */
    private function getUserPassword()
    {
        $user = HttpApp::request()->getServer('PHP_AUTH_USER');
        $password = HttpApp::request()->getServer('PHP_AUTH_PW');

        if ($user && $password) {
            return [$user, $password];
        }

        // Get current user password
        $header = null;
        if (HttpApp::request()->getServer('HTTP_AUTHORIZATION')) {
            $header = HttpApp::request()->getServer('HTTP_AUTHORIZATION');
        } elseif (HttpApp::request()->getServer('REDIRECT_HTTP_AUTHORIZATION')) {
            $header = HttpApp::request()->getServer('REDIRECT_HTTP_AUTHORIZATION');
        } elseif (HttpApp::request()->getServer('REDIRECT_REDIRECT_HTTP_AUTHORIZATION')) {
            $header = HttpApp::request()->getServer('REDIRECT_REDIRECT_HTTP_AUTHORIZATION');
        }

        if (is_null($header)) {
            return false;
        }

        $explode = explode(':', base64_decode(substr($header, 6)));
        if (sizeof($explode) != 2) {
            return false;
        }

        return $explode;
    }

    /**
     * @return bool
     */
    private function getAuth() : bool
    {
        // Auth is already done
        if (sizeof($this->services) > 0) {
            return true;
        }

        $auth = $this->getUserPassword();

        if (!$auth) {
            return false;
        }

        list($user, $password) = $auth;

        $usersList = $this->module->users;

        if (!isset($usersList[$user])) {
            return false;
        }

        // password check
        if (md5(strtolower($user) . $password) != $usersList[$user]['password']) {
            return false;
        }

        // ip check
        if (isset($usersList[$user]['ip']) && sizeof($usersList[$user]['ip']) > 0) {
            $ipSuccess = false;
            $currentIp = Ip::get();

            foreach ($usersList[$user]['ip'] as $currentRestriction) {
                $isRange = (stripos($currentRestriction, '/') !== false);
                if (($isRange && Ip::isInRange($currentRestriction, $currentIp)) ||
                    ($currentIp == $currentRestriction)
                ) {
                    $ipSuccess = true;
                    break;
                }
            }

            if (!$ipSuccess) {
                return false;
            }
        }

        $this->services = $usersList[$user]['services'];
        $this->user = $user;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed(string $service) : bool
    {
        if (!$this->getAuth()) {
            return false;
        }

        foreach ($this->services as $serviceMatch => $methodValue) {
            if (preg_match('`' . trim($serviceMatch) . '`i', $service)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function promptAuth() : bool
    {
        if ($this->getUserPassword() === false && !$this->getAuth()) {
            HttpApp::response()->addHeader('WWW-Authenticate', 'Basic realm="SwaggerApi"');
            HttpApp::response()->setStatus(401);

            return true;
        }

        return false;
    }
}
