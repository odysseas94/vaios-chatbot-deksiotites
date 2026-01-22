<?php

namespace app\middlewares;

use flight\Engine;

class AdminAuthMiddleware
{
    protected Engine $app;

    public function __construct(Engine $app)
    {
        $this->app = $app;
    }

    /**
     * Check if user is authenticated with basic auth
     */
    public function before()
    {
        // Get admin credentials from config
        $config = require __DIR__ . '/../config/config.php';
        $adminUsername = $config['admin']['username'] ?? 'admin';
        $adminPassword = $config['admin']['password'] ?? 'admin';

        // Check for basic auth headers
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        
        if (!$authHeader && isset($_SERVER['PHP_AUTH_USER'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'] ?? '';
        } elseif ($authHeader && preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
            list($username, $password) = explode(':', base64_decode($matches[1]), 2) + [null, null];
        } else {
            $this->requireAuth();
            return false;
        }

        // Verify credentials
        if ($username !== $adminUsername || $password !== $adminPassword) {
            $this->requireAuth();
            return false;
        }

        return true;
    }

    /**
     * Send 401 Unauthorized response
     */
    private function requireAuth()
    {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin Area"');
        echo '<h1>401 Unauthorized</h1><p>Απαιτείται έγκριση για πρόσβαση σε αυτή τη σελίδα.</p>';
        exit;
    }
}
