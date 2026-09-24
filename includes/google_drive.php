<?php
/**
 * طبقة تكامل Google Drive (OAuth 2.0) - كل شركة تربط حساب Google Drive
 * الخاص بها بشكل مستقل، باستخدام تطبيق OAuth واحد يُعدّه مالك النظام من
 * صفحة superadmin/settings.php (Google Client ID / Secret).
 *
 * لا نعتمد على أي مكتبة SDK خارجية؛ كل الاتصال بـ Google يتم عبر cURL مباشرة.
 */

require_once __DIR__ . '/functions.php';

const GOOGLE_OAUTH_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';
const GOOGLE_DRIVE_FILES_URL = 'https://www.googleapis.com/drive/v3/files';
const GOOGLE_DRIVE_UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';
const GOOGLE_DRIVE_SCOPE = 'https://www.googleapis.com/auth/drive.file https://www.googleapis.com/auth/userinfo.email';

function googleDriveRedirectUri(): string
{
    return (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL . '/modules/settings/google_callback.php';
}

function googleDriveIsConfigured(): bool
{
    return getSystemSetting('google_client_id') && getSystemSetting('google_client_secret');
}

/** تنفيذ طلب HTTP عبر cURL وإرجاع المصفوفة الناتجة من فك ترميز JSON، أو null عند الفشل */
function googleDriveHttp(string $method, string $url, array $data = [], ?string $bearerToken = null, ?array $rawBody = null): ?array
{
    $ch = curl_init($url);
    $headers = [];

    if ($rawBody !== null) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rawBody));
        $headers[] = 'Content-Type: application/json';
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method === 'GET' && !empty($data)) {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
    }

    if ($bearerToken) {
        $headers[] = 'Authorization: Bearer ' . $bearerToken;
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Google Drive HTTP error: ' . $curlError);
        return null;
    }

    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        error_log('Google Drive API error (' . $httpCode . '): ' . $response);
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

/** رابط تفويض Google OAuth لبدء عملية الربط */
function googleDriveAuthUrl(string $state): string
{
    $params = [
        'client_id' => getSystemSetting('google_client_id', ''),
        'redirect_uri' => googleDriveRedirectUri(),
        'response_type' => 'code',
        'scope' => GOOGLE_DRIVE_SCOPE,
        'access_type' => 'offline',
        'prompt' => 'consent',
        'state' => $state,
    ];
    return GOOGLE_OAUTH_AUTH_URL . '?' . http_build_query($params);
}

/** تبادل كود التفويض (من الاستدعاء الراجع) بتوكن وصول وتحديث */
function googleDriveExchangeCode(string $code): ?array
{
    return googleDriveHttp('POST', GOOGLE_OAUTH_TOKEN_URL, [
        'code' => $code,
        'client_id' => getSystemSetting('google_client_id', ''),
        'client_secret' => getSystemSetting('google_client_secret', ''),
        'redirect_uri' => googleDriveRedirectUri(),
        'grant_type' => 'authorization_code',
    ]);
}

/** تحديث توكن الوصول باستخدام توكن التحديث المخزَّن */
function googleDriveRefreshAccessToken(string $refreshToken): ?array
{
    return googleDriveHttp('POST', GOOGLE_OAUTH_TOKEN_URL, [
        'refresh_token' => $refreshToken,
        'client_id' => getSystemSetting('google_client_id', ''),
        'client_secret' => getSystemSetting('google_client_secret', ''),
        'grant_type' => 'refresh_token',
    ]);
}

function googleDriveUserEmail(string $accessToken): ?string
{
    $data = googleDriveHttp('GET', GOOGLE_USERINFO_URL, [], $accessToken);
    return $data['email'] ?? null;
}

/** جلب سجل ربط Drive الخاص بالشركة إن وُجد */
function googleDriveConnection(int $companyId): ?array
{
    return dbFetchOne('SELECT * FROM company_google_drive WHERE company_id = ?', 'i', [$companyId]);
}

/**
 * ضمان الحصول على توكن وصول صالح لشركة معيّنة، مع تحديثه تلقائياً إن انتهت
 * صلاحيته. يرجع null إن لم تكن الشركة مرتبطة بحساب Google أصلاً.
 */
function googleDriveEnsureValidToken(int $companyId): ?string
{
    $conn = googleDriveConnection($companyId);
    if (!$conn) {
        return null;
    }

    if (strtotime($conn['token_expires_at']) > time() + 60) {
        return $conn['access_token'];
    }

    $refreshed = googleDriveRefreshAccessToken($conn['refresh_token']);
    if (!$refreshed || empty($refreshed['access_token'])) {
        return null;
    }

    $expiresAt = date('Y-m-d H:i:s', time() + (int) ($refreshed['expires_in'] ?? 3600));
    dbExecute(
        'UPDATE company_google_drive SET access_token = ?, token_expires_at = ? WHERE company_id = ?',
        'ssi',
        [$refreshed['access_token'], $expiresAt, $companyId]
    );

    return $refreshed['access_token'];
}

/** إنشاء (أو إعادة استخدام) مجلد مخصص للشركة داخل Google Drive الخاصة بها */
function googleDriveEnsureFolder(int $companyId, string $accessToken): ?string
{
    $conn = googleDriveConnection($companyId);
    if (!empty($conn['folder_id'])) {
        return $conn['folder_id'];
    }

    $company = dbFetchOne('SELECT name FROM companies WHERE id = ?', 'i', [$companyId]);
    $folderName = 'بناء - ' . ($company['name'] ?? ('شركة #' . $companyId));

    $result = googleDriveHttp('POST', GOOGLE_DRIVE_FILES_URL, [], $accessToken, [
        'name' => $folderName,
        'mimeType' => 'application/vnd.google-apps.folder',
    ]);

    if (!$result || empty($result['id'])) {
        return null;
    }

    dbExecute('UPDATE company_google_drive SET folder_id = ? WHERE company_id = ?', 'si', [$result['id'], $companyId]);
    return $result['id'];
}

/**
 * رفع ملف محلي إلى Google Drive الخاصة بالشركة المرتبطة.
 * يرجع مصفوفة تحتوي id/webViewLink للملف المرفوع، أو null عند الفشل أو عدم الربط.
 */
function googleDriveUploadFile(int $companyId, string $localFilePath, string $filename, string $mimeType): ?array
{
    if (!file_exists($localFilePath)) {
        return null;
    }

    $accessToken = googleDriveEnsureValidToken($companyId);
    if (!$accessToken) {
        return null;
    }

    $folderId = googleDriveEnsureFolder($companyId, $accessToken);

    $metadata = ['name' => $filename];
    if ($folderId) {
        $metadata['parents'] = [$folderId];
    }

    $boundary = '-------314159265358979323846';
    $body = "--{$boundary}\r\n"
        . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
        . json_encode($metadata) . "\r\n"
        . "--{$boundary}\r\n"
        . "Content-Type: {$mimeType}\r\n\r\n"
        . file_get_contents($localFilePath) . "\r\n"
        . "--{$boundary}--";

    $ch = curl_init(GOOGLE_DRIVE_UPLOAD_URL . '?uploadType=multipart&fields=id,webViewLink');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: multipart/related; boundary=' . $boundary,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        error_log('Google Drive upload error (' . $httpCode . '): ' . $response);
        return null;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : null;
}

function googleDriveDisconnect(int $companyId): void
{
    dbExecute('DELETE FROM company_google_drive WHERE company_id = ?', 'i', [$companyId]);
}
