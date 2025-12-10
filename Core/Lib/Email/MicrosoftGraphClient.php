<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib\Email;

use FacturaScripts\Core\Tools;

/**
 * Helper to interact with Microsoft Graph delegated authentication.
 */
class MicrosoftGraphClient
{
    private const GRAPH_AUTH_URL = 'https://login.microsoftonline.com/';
    private const GRAPH_TOKEN_PATH = '/oauth2/v2.0/token';
    private const GRAPH_AUTH_PATH = '/oauth2/v2.0/authorize';
    private const GRAPH_API_URL = 'https://graph.microsoft.com/v1.0';
    private const TOKEN_MODE_AUTHORIZATION_CODE = 'authorization_code';
    private const TOKEN_MODE_PASSWORD = 'password';

    private string $tenantId;
    private string $clientId;
    private string $clientSecret;
    private string $scopes;
    private string $tokenMode;
    private string $username;
    private string $password;
    private ?string $lastError = null;

    public function __construct()
    {
        $this->tenantId = (string)Tools::settings('email', 'msgraph_tenant_id', 'common');
        $this->clientId = (string)Tools::settings('email', 'msgraph_client_id', '');
        $this->clientSecret = (string)Tools::settings('email', 'msgraph_client_secret', '');
        $this->scopes = trim((string)Tools::settings('email', 'msgraph_scopes', 'offline_access https://graph.microsoft.com/Mail.Send'));
        $this->tokenMode = strtolower((string)Tools::settings('email', 'msgraph_token_mode', self::TOKEN_MODE_AUTHORIZATION_CODE));
        if (!in_array($this->tokenMode, [self::TOKEN_MODE_AUTHORIZATION_CODE, self::TOKEN_MODE_PASSWORD], true)) {
            $this->tokenMode = self::TOKEN_MODE_AUTHORIZATION_CODE;
        }
        $this->username = (string)Tools::settings('email', 'msgraph_username', '');
        $this->password = (string)Tools::settings('email', 'msgraph_password', '');
    }

    public function getRedirectUri(): string
    {
        $configured = (string)Tools::settings('email', 'msgraph_redirect_uri', '');
        if (!empty($configured)) {
            return $configured;
        }

        return Tools::siteUrl() . '/index.php?page=ConfigEmail&action=msgraph-callback';
    }

    public function hasClientConfiguration(): bool
    {
        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->tenantId)) {
            return false;
        }

        if ($this->usesPasswordMode()) {
            return $this->hasPasswordCredentials();
        }

        return true;
    }

    public function hasRefreshToken(): bool
    {
        return '' !== $this->getStoredRefreshToken();
    }

    public function isReadyToSend(): bool
    {
        if (false === $this->hasClientConfiguration()) {
            return false;
        }

        if ($this->usesPasswordMode()) {
            return true;
        }

        return $this->hasRefreshToken();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getAuthorizationUrl(string $state): ?string
    {
        if (!$this->usesAuthorizationCodeMode()) {
            $this->lastError = 'authorization-disabled';
            return null;
        }

        if (false === $this->hasClientConfiguration()) {
            $this->lastError = 'missing-configuration';
            return null;
        }

        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->getRedirectUri(),
            'response_mode' => 'query',
            'scope' => $this->scopes,
            'state' => $state,
            'prompt' => 'select_account'
        ];

        return self::GRAPH_AUTH_URL . rawurlencode($this->tenantId) . self::GRAPH_AUTH_PATH
            . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeCode(string $code): bool
    {
        if (!$this->usesAuthorizationCodeMode()) {
            $this->lastError = 'authorization-disabled';
            return false;
        }

        $params = [
            'client_id' => $this->clientId,
            'scope' => $this->scopes,
            'code' => $code,
            'redirect_uri' => $this->getRedirectUri(),
            'grant_type' => 'authorization_code',
            'client_secret' => $this->clientSecret
        ];

        return $this->requestToken($params);
    }

    public function refreshAccessToken(): bool
    {
        if ($this->usesPasswordMode()) {
            return $this->requestPasswordToken();
        }

        $refreshToken = $this->getStoredRefreshToken();
        if (empty($refreshToken)) {
            $this->lastError = 'missing-refresh-token';
            return false;
        }

        $params = [
            'client_id' => $this->clientId,
            'scope' => $this->scopes,
            'refresh_token' => $refreshToken,
            'redirect_uri' => $this->getRedirectUri(),
            'grant_type' => 'refresh_token',
            'client_secret' => $this->clientSecret
        ];

        return $this->requestToken($params);
    }

    public function validateConnection(): bool
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        $response = $this->graphRequest('GET', '/me?$select=id,mail', $token);
        if ($response['status'] >= 200 && $response['status'] < 300) {
            return true;
        }

        if (401 === $response['status'] && $this->refreshAccessToken()) {
            $token = $this->getStoredAccessToken();
            if (!empty($token)) {
                $retry = $this->graphRequest('GET', '/me?$select=id,mail', $token);
                if ($retry['status'] >= 200 && $retry['status'] < 300) {
                    return true;
                }
                $response = $retry;
            }
        }

        $this->lastError = $this->extractErrorMessage($response['body'] ?? '', $response['error'] ?? '');
        return false;
    }

    public function sendMail(array $message, bool $saveToSentItems): bool
    {
        $token = $this->getAccessToken();
        if (empty($token)) {
            return false;
        }

        $payload = json_encode([
            'message' => $message,
            'saveToSentItems' => $saveToSentItems
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response = $this->graphRequest('POST', '/me/sendMail', $token, $payload, [
            'Content-Type: application/json'
        ]);

        if (401 === $response['status'] && $this->refreshAccessToken()) {
            $token = $this->getStoredAccessToken();
            if (!empty($token)) {
                $response = $this->graphRequest('POST', '/me/sendMail', $token, $payload, [
                    'Content-Type: application/json'
                ]);
            }
        }

        if ($response['status'] === 202 || $response['status'] === 200) {
            return true;
        }

        $this->lastError = $this->extractErrorMessage($response['body'] ?? '', $response['error'] ?? '');
        return false;
    }

    private function getStoredRefreshToken(): string
    {
        return (string)Tools::settings('email', 'msgraph_refresh_token', '');
    }

    private function getStoredAccessToken(): string
    {
        return (string)Tools::settings('email', 'msgraph_access_token', '');
    }

    private function getAccessToken(): ?string
    {
        $accessToken = $this->getStoredAccessToken();
        $expiresAt = (int)Tools::settings('email', 'msgraph_token_expires', '0');
        if (!empty($accessToken) && $expiresAt > (time() + 60)) {
            return $accessToken;
        }

        if (false === $this->refreshAccessToken()) {
            return null;
        }

        return $this->getStoredAccessToken();
    }

    private function requestPasswordToken(): bool
    {
        if (!$this->hasPasswordCredentials()) {
            $this->lastError = 'missing-password-credentials';
            return false;
        }

        $params = [
            'client_id' => $this->clientId,
            'scope' => $this->scopes,
            'grant_type' => 'password',
            'client_secret' => $this->clientSecret,
            'username' => $this->username,
            'password' => $this->password
        ];

        return $this->requestToken($params);
    }

    private function requestToken(array $params): bool
    {
        if (false === $this->hasClientConfiguration()) {
            $this->lastError = 'missing-configuration';
            return false;
        }

        $response = $this->executeRequest(
            'POST',
            self::GRAPH_AUTH_URL . rawurlencode($this->tenantId) . self::GRAPH_TOKEN_PATH,
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query($params, '', '&', PHP_QUERY_RFC3986)
        );

        if (null !== $response['error']) {
            $this->lastError = $response['error'];
            return false;
        }

        $data = json_decode($response['body'], true);
        if ($response['status'] < 200 || $response['status'] >= 300 || !is_array($data)) {
            $this->lastError = $this->extractErrorMessage($response['body'], '');
            return false;
        }

        $this->storeTokenData($data);
        $this->lastError = null;
        return true;
    }

    private function storeTokenData(array $data): void
    {
        if (!empty($data['access_token'])) {
            Tools::settingsSet('email', 'msgraph_access_token', $data['access_token']);
        }

        if (!empty($data['refresh_token'])) {
            Tools::settingsSet('email', 'msgraph_refresh_token', $data['refresh_token']);
        }

        if (!empty($data['expires_in'])) {
            $expires = time() + (int)$data['expires_in'] - 60;
            Tools::settingsSet('email', 'msgraph_token_expires', (string)$expires);
        }

        Tools::settingsSave();
        Tools::settingsClear();
    }

    private function graphRequest(string $method, string $endpoint, string $token, ?string $body = null, array $headers = []): array
    {
        $headers[] = 'Authorization: Bearer ' . $token;
        $headers[] = 'Accept: application/json';

        return $this->executeRequest($method, self::GRAPH_API_URL . $endpoint, $headers, $body);
    }

    private function executeRequest(string $method, string $url, array $headers, ?string $body = null): array
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        if (null !== $body) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($curl);
        $error = curl_errno($curl) ? curl_error($curl) : null;
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [
            'status' => $status,
            'body' => false === $result ? '' : (string)$result,
            'error' => $error
        ];
    }

    private function extractErrorMessage(?string $body, string $default): string
    {
        if (!empty($default)) {
            return $default;
        }

        if (empty($body)) {
            return 'unknown-error';
        }

        $data = json_decode($body, true);
        if (is_array($data)) {
            if (isset($data['error_description'])) {
                return (string)$data['error_description'];
            }

            if (isset($data['error']['message'])) {
                return (string)$data['error']['message'];
            }

            if (isset($data['message'])) {
                return (string)$data['message'];
            }
        }

        return (string)$body;
    }

    private function usesAuthorizationCodeMode(): bool
    {
        return $this->tokenMode === self::TOKEN_MODE_AUTHORIZATION_CODE;
    }

    private function usesPasswordMode(): bool
    {
        return $this->tokenMode === self::TOKEN_MODE_PASSWORD;
    }

    private function hasPasswordCredentials(): bool
    {
        return '' !== $this->username && '' !== $this->password;
    }
}
