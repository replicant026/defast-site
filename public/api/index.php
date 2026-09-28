<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/session.php';

date_default_timezone_set('America/Sao_Paulo');

$projectRoot = dirname(__DIR__);
$repositoryRoot = dirname($projectRoot);

loadEnvFile($repositoryRoot . DIRECTORY_SEPARATOR . '.env');
loadEnvFile($projectRoot . DIRECTORY_SEPARATOR . '.env');

$config = buildConfig($projectRoot);
$plansFilePath = $projectRoot . DIRECTORY_SEPARATOR . 'plans.json';
if (!is_file($plansFilePath)) {
    $plansFilePath = $repositoryRoot . DIRECTORY_SEPARATOR . 'plans.json';
}
$plansContext = loadPlansContext($plansFilePath, $config);

setCorsHeaders($config);
setApiSecurityHeaders();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$path = normalizePath((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));

try {
    if ($method === 'GET' && $path === '/api/health') {
        jsonResponse(['ok' => true, 'service' => 'defast-compra-php']);
    }

    if ($method === 'GET' && $path === '/api/auth/csrf') {
        handleAuthCsrf();
    }

    if ($method === 'POST' && $path === '/api/auth/register') {
        handleAuthRegister($config, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/auth/login') {
        handleAuthLogin($config, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/auth/password') {
        handleAuthPasswordUpdate($config, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/auth/logout') {
        handleAuthLogout();
    }

    if ($method === 'GET' && $path === '/api/auth/me') {
        handleAuthMe();
    }

    if ($method === 'GET' && $path === '/api/license/status') {
        handleLicenseStatus($config, $plansContext);
    }

    if ($method === 'GET' && $path === '/api/customer-area/overview') {
        handleCustomerAreaOverview($config, $plansContext);
    }

    if ($method === 'POST' && $path === '/api/customer-area/subscription/cancel') {
        handleCustomerAreaCancelSubscription($config);
    }

    if ($method === 'POST' && $path === '/api/customer-area/subscription/change-plan') {
        handleCustomerAreaChangePlan($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/customer-area/subscription/payment-method') {
        handleCustomerAreaUpdatePaymentMethod($config, readJsonBody());
    }

    if ($method === 'GET' && $path === '/api/plan') {
        handleGetPlan($plansContext);
    }

    if ($method === 'GET' && $path === '/api/plans') {
        handleGetPlans($plansContext);
    }

    if ($method === 'POST' && $path === '/api/asaas/webhook') {
        handleWebhook($config);
    }

    if ($method === 'POST' && $path === '/api/payment/pix') {
        handleRecurringPix($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/payment/boleto') {
        handleRecurringBoleto($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/payment/credit-card') {
        handleRecurringCreditCard($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/onetime/pix') {
        handleOnetimePix($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/onetime/boleto') {
        handleOnetimeBoleto($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/onetime/credit-card') {
        handleOnetimeCreditCard($config, $plansContext, readJsonBody());
    }

    // --- Mercado Pago ---
    if ($method === 'GET' && $path === '/api/mp/public-key') {
        handleMpPublicKey($config);
    }

    if ($method === 'POST' && $path === '/api/mp/subscription') {
        handleMpSubscription($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/mp/payment') {
        handleMpPayment($config, $plansContext, readJsonBody());
    }

    if ($method === 'POST' && $path === '/api/mp/webhook') {
        handleMpWebhook($config, $plansContext);
    }

    jsonResponse(['error' => 'Rota nao encontrada.'], 404);
} catch (ApiException $error) {
    $payload = ['error' => $error->getMessage()];
    if (shouldExposeApiErrorDetails($config)) {
        $payload['details'] = $error->details;
    }

    jsonResponse($payload, $error->status);
} catch (Throwable $error) {
    error_log('[api/index.php] ' . $error->getMessage());
    jsonResponse(['error' => 'Erro inesperado. Tente novamente mais tarde.'], 500);
}

function handleGetPlan(array $plansContext): void
{
    $annual = findPlanById($plansContext['activePlans'], 'annual');
    if ($annual === null) {
        $annual = $plansContext['activePlans'][0] ?? null;
    }

    if ($annual === null) {
        throw new ApiException('Nenhum plano ativo encontrado.', 500);
    }

    jsonResponse([
        'name' => $annual['name'],
        'description' => $annual['description'],
        'value' => $annual['price'],
        'cycle' => $annual['cycle'],
        'dueDateLimitDays' => $plansContext['dueDateLimitDays'],
    ]);
}

function handleGetPlans(array $plansContext): void
{
    jsonResponse($plansContext['activePlans']);
}

function handleAuthRegister(array $config, array $body): void
{
    ensureSupabaseAuthConfigured($config);
    enforceRateLimit('auth_register', 6, 300);

    initSecureSession();
    enforceCsrfToken();

    $email = strtolower(cleanText($body['email'] ?? ''));
    $name = cleanText($body['name'] ?? '');
    $phone = onlyDigits($body['phone'] ?? '');
    $password = validateAccountPassword($body['password'] ?? '');

    if (!isValidEmail($email)) {
        throw new ApiException('Informe um e-mail valido.', 400);
    }

    if ($name === '' || strlen($name) < 3) {
        $name = ucfirst(explode('@', $email)[0] ?? 'Cliente');
        if ($name === '' || strlen($name) < 3) {
            $name = 'Cliente DeFast';
        }
    }

    if ($phone === '' || strlen($phone) < 10) {
        throw new ApiException('Informe um telefone com DDD valido.', 400);
    }

    $user = registerOrValidateSupabaseUser($config, $email, $password, $name, $phone);
    $userId = cleanText($user['id'] ?? '');
    if ($userId === '') {
        throw new ApiException('Conta criada sem ID de usuario no Supabase.', 502);
    }

    session_regenerate_id(true);
    $_SESSION['pending_signup'] = [
        'userId' => $userId,
        'email' => $email,
        'name' => $name,
        'phone' => $phone,
        'createdAt' => time(),
    ];

    // --- Ativar trial gratuito automaticamente ---
    $freeTrialDays = max(1, (int) ($config['free_trial_days'] ?? 30));
    try {
        ensureSupabaseServiceConfigured($config);
        upsertUserLicense($config, [
            'user_id'             => $userId,
            'stripe_customer_id'  => '',
            'subscription_end'    => isoTimestampFromDate('+' . $freeTrialDays . ' days'),
            'is_active'           => true,
            'plan_id'             => 'trial',
            'plan_interval'       => 'TRIAL',
        ]);
    } catch (Throwable $trialError) {
        error_log('[handleAuthRegister] Falha ao ativar trial: ' . $trialError->getMessage());
    }
    // --- Fim trial ---

    $csrfToken = rotateCsrfToken();

    jsonResponse([
        'registered' => true,
        'trialActivated' => true,
        'trialDays' => $freeTrialDays,
        'customer' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
        ],
        'csrfToken' => $csrfToken,
    ]);
}

function handleAuthLogin(array $config, array $body): void
{
    ensureSupabaseAuthConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('auth_login', 8, 300);
    initSecureSession();
    enforceCsrfToken();

    $email = strtolower(cleanText($body['email'] ?? ''));
    $password = (string) ($body['password'] ?? '');

    if (!isValidEmail($email)) {
        throw new ApiException('Informe um e-mail valido.', 400);
    }

    if ($password === '') {
        throw new ApiException('Informe sua senha.', 400);
    }

    $signIn = supabasePasswordSignIn($config, $email, $password);
    if (!$signIn['ok']) {
        throw new ApiException('Credenciais invalidas.', 401, supabaseErrorDetails($signIn['data']));
    }

    $user = $signIn['data']['user'] ?? null;
    if (!is_array($user)) {
        throw new ApiException('Resposta de autenticacao invalida.', 502);
    }

    $userId = cleanText($user['id'] ?? '');
    if ($userId === '') {
        throw new ApiException('Resposta de autenticacao sem identificador de usuario.', 502);
    }

    $userMetadata = is_array($user['user_metadata'] ?? null) ? $user['user_metadata'] : [];
    $name = cleanText($userMetadata['full_name'] ?? '');
    if ($name === '') {
        $name = cleanText(explode('@', $email)[0] ?? 'Cliente');
    }
    if ($name === '') {
        $name = 'Cliente';
    }

    $cpfCnpj = onlyDigits($userMetadata['cpf_cnpj'] ?? '');
    $phone = onlyDigits($userMetadata['phone'] ?? '');
    $license = findUserLicenseByUserId($config, $userId);
    $asaasCustomerId = cleanText($license['stripe_customer_id'] ?? '');

    session_regenerate_id(true);
    $csrfToken = rotateCsrfToken();

    $_SESSION['auth_customer'] = [
        'userId' => $userId,
        'asaasCustomerId' => $asaasCustomerId,
        'name' => $name,
        'email' => $email,
        'cpfCnpj' => $cpfCnpj,
        'phone' => $phone,
        'authenticatedAt' => time(),
    ];

    unset($_SESSION['pending_signup']);

    jsonResponse([
        'authenticated' => true,
        'customer' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
        ],
        'csrfToken' => $csrfToken,
    ]);
}

function handleAuthPasswordUpdate(array $config, array $body): void
{
    ensureSupabaseAuthConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('auth_password_update', 5, 300);

    $auth = requireAuthenticatedCustomer();
    enforceCsrfToken();

    $currentPassword = (string) ($body['currentPassword'] ?? '');
    $newPassword = validateAccountPassword($body['newPassword'] ?? '');

    if ($currentPassword === '') {
        throw new ApiException('Informe sua senha atual.', 400);
    }

    if (hash_equals($currentPassword, $newPassword)) {
        throw new ApiException('A nova senha deve ser diferente da senha atual.', 400);
    }

    $verifyCredentials = supabasePasswordSignIn($config, $auth['email'], $currentPassword);
    if (!$verifyCredentials['ok']) {
        throw new ApiException('Senha atual invalida.', 401, supabaseErrorDetails($verifyCredentials['data']));
    }

    $updatePassword = supabaseRequest(
        $config,
        'PUT',
        '/auth/v1/admin/users/' . rawurlencode($auth['userId']),
        ['password' => $newPassword],
        [],
        'service'
    );

    if (!$updatePassword['ok']) {
        throw new ApiException(
            'Nao foi possivel atualizar sua senha no Supabase.',
            normalizeErrorStatus((int) ($updatePassword['status'] ?? 500)),
            supabaseErrorDetails($updatePassword['data'])
        );
    }

    jsonResponse([
        'updated' => true,
        'message' => 'Senha atualizada com sucesso.',
        'csrfToken' => rotateCsrfToken(),
    ]);
}

function handleAuthCsrf(): void
{
    initSecureSession();
    jsonResponse([
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function handleAuthMe(): void
{
    initSecureSession();
    $auth = authenticatedCustomer();

    if ($auth === null) {
        throw new ApiException('Nao autenticado.', 401);
    }

    jsonResponse([
        'authenticated' => true,
        'customer' => [
            'id' => $auth['userId'],
            'name' => $auth['name'],
            'email' => $auth['email'],
            'phone' => $auth['phone'],
        ],
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function handleAuthLogout(): void
{
    initSecureSession();
    enforceCsrfToken();

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();

    jsonResponse(['authenticated' => false]);
}

function handleCustomerAreaOverview(array $config, array $plansContext): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('customer_area_overview', 60, 60);

    $auth = requireAuthenticatedCustomer();
    $asaasCustomerId = resolveAuthenticatedAsaasCustomerId($config, $auth);
    $subscription = $asaasCustomerId !== ''
        ? findCustomerPrimarySubscription($config, $asaasCustomerId)
        : null;
    $payments = $asaasCustomerId !== ''
        ? listCustomerPayments($config, $asaasCustomerId, 20)
        : [];

    if ($subscription !== null && $asaasCustomerId !== '') {
        try {
            syncUserLicenseFromSubscription(
                $config,
                $auth['userId'],
                $asaasCustomerId,
                $subscription,
                []
            );
        } catch (Throwable $syncError) {
            error_log('[customer-area] Falha ao sincronizar licenca no Supabase: ' . $syncError->getMessage());
        }
    }

    jsonResponse([
        'customer' => [
            'id' => $auth['userId'],
            'name' => $auth['name'],
            'email' => $auth['email'],
        ],
        'subscription' => $subscription !== null
            ? normalizeCustomerAreaSubscriptionResponse($config, $plansContext, $subscription)
            : null,
        'payments' => array_map('normalizeCustomerAreaPaymentResponse', $payments),
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function handleLicenseStatus(array $config, array $plansContext): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('license_status', 60, 60);

    $auth = requireAuthenticatedCustomer();
    $asaasCustomerId = resolveAuthenticatedAsaasCustomerId($config, $auth);

    $subscription = null;
    $payments = [];

    if ($asaasCustomerId !== '') {
        $subscription = findCustomerPrimarySubscription($config, $asaasCustomerId);
        $payments = listCustomerPayments($config, $asaasCustomerId, 20);
    }

    if ($subscription !== null && $asaasCustomerId !== '') {
        try {
            syncUserLicenseFromSubscription(
                $config,
                $auth['userId'],
                $asaasCustomerId,
                $subscription,
                []
            );
        } catch (Throwable $syncError) {
            error_log('[license-status] Falha ao sincronizar licenca no Supabase: ' . $syncError->getMessage());
        }
    }

    $license = findUserLicenseByUserId($config, $auth['userId']);
    $access = resolveLicenseAccessStatus($license, $subscription);
    $trial = buildTrialStatusPayload($plansContext, $subscription);

    $subscriptionPayload = $subscription !== null
        ? normalizeCustomerAreaSubscriptionResponse($config, $plansContext, $subscription)
        : null;

    $subscriptionId = cleanText($subscription['id'] ?? '');
    $currentCharge = pickSubscriptionCurrentCharge($payments, $subscriptionId);
    $currentChargePayload = $currentCharge !== null
        ? normalizeCustomerAreaPaymentResponse($currentCharge)
        : null;

    $pixQrCode = resolveCurrentChargePixQrCode($config, $currentCharge);

    $licensePayload = [
        'isActive' => (bool) ($license['is_active'] ?? false),
        'subscriptionEnd' => cleanText($license['subscription_end'] ?? ''),
        'planId' => cleanText($license['plan_id'] ?? ''),
        'planInterval' => strtoupper(cleanText($license['plan_interval'] ?? '')),
        'asaasCustomerId' => cleanText($license['stripe_customer_id'] ?? ''),
    ];

    jsonResponse([
        'customer' => [
            'id' => $auth['userId'],
            'name' => $auth['name'],
            'email' => $auth['email'],
            'asaasCustomerId' => $asaasCustomerId,
        ],
        'access' => $access,
        'trial' => $trial,
        'license' => $licensePayload,
        'subscription' => $subscriptionPayload,
        'payments' => array_map('normalizeCustomerAreaPaymentResponse', $payments),
        'billing' => [
            'currentCharge' => $currentChargePayload,
            'pixQrCode' => $pixQrCode,
            'bankSlipUrl' => cleanText($currentCharge['bankSlipUrl'] ?? ''),
        ],
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function resolveLicenseAccessStatus(?array $license, ?array $subscription): array
{
    $nowTs = time();

    $licenseIsActive = false;
    $licenseExpiresAt = '';
    if (is_array($license)) {
        $licenseExpiresAt = cleanText($license['subscription_end'] ?? '');
        $licenseFlag = (bool) ($license['is_active'] ?? false);
        $licenseExpiryTs = 0;

        if ($licenseExpiresAt !== '') {
            try {
                $licenseExpiryTs = (new DateTimeImmutable($licenseExpiresAt, new DateTimeZone('America/Sao_Paulo')))->getTimestamp();
            } catch (Throwable) {
                $licenseExpiryTs = 0;
            }
        }

        $licenseIsActive = $licenseFlag && ($licenseExpiryTs === 0 || $licenseExpiryTs >= $nowTs);
    }

    $subscriptionStatus = strtoupper(cleanText($subscription['status'] ?? 'INACTIVE'));
    $subscriptionNextDueDate = cleanText($subscription['nextDueDate'] ?? '');
    $subscriptionIsActive = false;
    if (is_array($subscription) && subscriptionIsActive($subscriptionStatus)) {
        if ($subscriptionNextDueDate === '') {
            $subscriptionIsActive = true;
        } else {
            $subscriptionIsActive = customerAreaDateToTimestamp($subscriptionNextDueDate) >= $nowTs;
        }
    }

    $hasAccess = $licenseIsActive || $subscriptionIsActive;
    $source = $licenseIsActive
        ? 'license'
        : ($subscriptionIsActive ? 'subscription' : 'none');

    return [
        'hasAccess' => $hasAccess,
        'source' => $source,
        'licenseActive' => $licenseIsActive,
        'subscriptionActive' => $subscriptionIsActive,
        'licenseExpiresAt' => $licenseExpiresAt,
        'subscriptionStatus' => $subscriptionStatus,
        'subscriptionNextDueDate' => $subscriptionNextDueDate,
    ];
}

function buildTrialStatusPayload(array $plansContext, ?array $subscription): array
{
    $defaultPayload = [
        'enabled' => false,
        'trialDays' => 0,
        'inTrial' => false,
        'startsAt' => null,
        'endsAt' => null,
        'daysLeft' => 0,
    ];

    if (!is_array($subscription)) {
        return $defaultPayload;
    }

    $trialDays = resolveSubscriptionTrialDays($plansContext, $subscription);
    if ($trialDays <= 0) {
        return $defaultPayload;
    }

    $timezone = new DateTimeZone('America/Sao_Paulo');
    $trialStart = null;
    $trialEnd = null;

    $dateCreated = cleanText($subscription['dateCreated'] ?? '');
    if ($dateCreated !== '') {
        try {
            $trialStart = new DateTimeImmutable($dateCreated, $timezone);
            $trialEnd = $trialStart->modify('+' . $trialDays . ' day');
        } catch (Throwable) {
            $trialStart = null;
            $trialEnd = null;
        }
    }

    if ($trialEnd === null) {
        $nextDueDate = cleanText($subscription['nextDueDate'] ?? '');
        if ($nextDueDate !== '') {
            try {
                $trialEnd = new DateTimeImmutable($nextDueDate, $timezone);
                $trialStart = $trialEnd->modify('-' . $trialDays . ' day');
            } catch (Throwable) {
                $trialStart = null;
                $trialEnd = null;
            }
        }
    }

    if ($trialEnd === null || $trialStart === null) {
        return [
            'enabled' => true,
            'trialDays' => $trialDays,
            'inTrial' => false,
            'startsAt' => null,
            'endsAt' => null,
            'daysLeft' => 0,
        ];
    }

    $now = new DateTimeImmutable('now', $timezone);
    $secondsLeft = $trialEnd->getTimestamp() - $now->getTimestamp();
    $inTrial = $secondsLeft > 0;

    return [
        'enabled' => true,
        'trialDays' => $trialDays,
        'inTrial' => $inTrial,
        'startsAt' => $trialStart->format(DATE_ATOM),
        'endsAt' => $trialEnd->format(DATE_ATOM),
        'daysLeft' => $inTrial ? max(1, (int) ceil($secondsLeft / 86400)) : 0,
    ];
}

function resolveSubscriptionTrialDays(array $plansContext, array $subscription): int
{
    $cycle = strtoupper(cleanText($subscription['cycle'] ?? ''));
    $value = round((float) ($subscription['value'] ?? 0), 2);

    $planSnapshot = resolveSubscriptionPlanSnapshot($plansContext['activePlans'], $cycle, $value, $subscription);
    $planId = cleanText($planSnapshot['id'] ?? '');

    if ($planId !== '') {
        $plan = findPlanById($plansContext['activePlans'], $planId);
        if (is_array($plan)) {
            return max(0, (int) ($plan['trialDays'] ?? 0));
        }
    }

    foreach ($plansContext['activePlans'] as $plan) {
        if (!is_array($plan)) {
            continue;
        }

        $planCycle = strtoupper(cleanText($plan['cycle'] ?? ''));
        $planPrice = round((float) ($plan['price'] ?? 0), 2);
        if ($planCycle === $cycle && abs($planPrice - $value) < 0.01) {
            return max(0, (int) ($plan['trialDays'] ?? 0));
        }
    }

    return 0;
}

function pickSubscriptionCurrentCharge(array $payments, string $subscriptionId): ?array
{
    $filtered = [];
    foreach ($payments as $payment) {
        if (!is_array($payment)) {
            continue;
        }

        $paymentSubscriptionId = cleanText($payment['subscription'] ?? '');
        if ($subscriptionId !== '' && $paymentSubscriptionId !== $subscriptionId) {
            continue;
        }

        $filtered[] = $payment;
    }

    if (count($filtered) === 0) {
        return null;
    }

    usort($filtered, static function (array $a, array $b): int {
        $aPriority = paymentChargePriority($a);
        $bPriority = paymentChargePriority($b);
        if ($aPriority !== $bPriority) {
            return $aPriority <=> $bPriority;
        }

        $aDue = customerAreaDateToTimestamp(cleanText($a['dueDate'] ?? ''));
        $bDue = customerAreaDateToTimestamp(cleanText($b['dueDate'] ?? ''));
        if ($aDue !== $bDue) {
            return $aDue <=> $bDue;
        }

        $aCreated = customerAreaDateToTimestamp(cleanText($a['dateCreated'] ?? ''));
        $bCreated = customerAreaDateToTimestamp(cleanText($b['dateCreated'] ?? ''));
        return $bCreated <=> $aCreated;
    });

    return $filtered[0];
}

function paymentChargePriority(array $payment): int
{
    $status = strtoupper(cleanText($payment['status'] ?? ''));

    if (in_array($status, ['PENDING', 'OVERDUE'], true)) {
        return 0;
    }

    if (in_array($status, ['CONFIRMED', 'RECEIVED', 'RECEIVED_IN_CASH'], true)) {
        return 1;
    }

    return 2;
}

function resolveCurrentChargePixQrCode(array $config, ?array $currentCharge): ?array
{
    if (!is_array($currentCharge)) {
        return null;
    }

    $billingType = strtoupper(cleanText($currentCharge['billingType'] ?? ''));
    if ($billingType !== 'PIX') {
        return null;
    }

    $paymentStatus = strtoupper(cleanText($currentCharge['status'] ?? ''));
    if (!in_array($paymentStatus, ['PENDING', 'OVERDUE'], true)) {
        return null;
    }

    $paymentId = cleanText($currentCharge['id'] ?? '');
    if ($paymentId === '') {
        return null;
    }

    $pixQrCodeResponse = asaasRequest(
        $config,
        'GET',
        '/v3/payments/' . rawurlencode($paymentId) . '/pixQrCode'
    );

    if (!$pixQrCodeResponse['ok']) {
        return null;
    }

    return [
        'encodedImage' => cleanText($pixQrCodeResponse['data']['encodedImage'] ?? ''),
        'payload' => cleanText($pixQrCodeResponse['data']['payload'] ?? ''),
        'expirationDate' => cleanText($pixQrCodeResponse['data']['expirationDate'] ?? ''),
    ];
}

function handleCustomerAreaCancelSubscription(array $config): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('customer_area_cancel_subscription', 6, 300);

    $auth = requireAuthenticatedCustomer();
    enforceCsrfToken();

    $asaasCustomerId = resolveAuthenticatedAsaasCustomerId($config, $auth);
    if ($asaasCustomerId === '') {
        throw new ApiException('Nenhuma assinatura ativa encontrada para cancelar.', 404);
    }

    $subscription = findCustomerPrimarySubscription($config, $asaasCustomerId);
    if ($subscription === null) {
        throw new ApiException('Nenhuma assinatura ativa encontrada para cancelar.', 404);
    }

    $subscriptionId = cleanText($subscription['id'] ?? '');
    if ($subscriptionId === '') {
        throw new ApiException('Assinatura ativa sem identificador valido.', 502);
    }

    $response = asaasRequest($config, 'DELETE', '/v3/subscriptions/' . rawurlencode($subscriptionId));
    if (!$response['ok']) {
        throwAsaasException('Nao foi possivel cancelar a assinatura no Asaas.', $response);
    }

    try {
        markUserLicenseInactive($config, $auth['userId'], $asaasCustomerId);
    } catch (Throwable $syncError) {
        error_log('[customer-area] Falha ao marcar licenca como inativa no Supabase: ' . $syncError->getMessage());
    }

    jsonResponse([
        'cancelled' => true,
        'subscriptionId' => $subscriptionId,
        'message' => 'Assinatura cancelada com sucesso.',
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function handleCustomerAreaChangePlan(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('customer_area_change_plan', 8, 300);

    $auth = requireAuthenticatedCustomer();
    enforceCsrfToken();
    $asaasCustomerId = resolveAuthenticatedAsaasCustomerId($config, $auth);
    if ($asaasCustomerId === '') {
        throw new ApiException('Nenhuma assinatura ativa encontrada para alteracao.', 404);
    }

    $planId = strtolower(cleanText($body['planId'] ?? ''));
    if ($planId === '') {
        throw new ApiException('Informe o plano desejado para alteracao.', 400);
    }

    $plan = findPlanById($plansContext['activePlans'], $planId);
    if ($plan === null || ($plan['active'] ?? true) === false) {
        throw new ApiException('Plano informado nao esta disponivel.', 404);
    }

    if (isOnetimeCycle((string) ($plan['cycle'] ?? ''))) {
        throw new ApiException('Nao e possivel migrar assinatura recorrente para plano ONETIME por esta rota.', 400);
    }

    $subscription = findCustomerPrimarySubscription($config, $asaasCustomerId);
    if ($subscription === null) {
        throw new ApiException('Nenhuma assinatura ativa encontrada para alteracao.', 404);
    }

    $subscriptionId = cleanText($subscription['id'] ?? '');
    if ($subscriptionId === '') {
        throw new ApiException('Assinatura ativa sem identificador valido.', 502);
    }

    $description = cleanText($plan['description'] ?? '');
    if ($description === '') {
        $description = cleanText($plan['name'] ?? 'Assinatura DeFast');
    }

    $payload = [
        'cycle' => normalizeRecurringCycle((string) ($plan['cycle'] ?? 'YEARLY')),
        'value' => (float) ($plan['price'] ?? 0),
        'description' => substr($description, 0, 500),
        'updatePendingPayments' => true,
        'status' => 'ACTIVE',
    ];

    $response = asaasRequest(
        $config,
        'PUT',
        '/v3/subscriptions/' . rawurlencode($subscriptionId),
        $payload
    );

    if (!$response['ok']) {
        throwAsaasException('Nao foi possivel alterar o plano da assinatura.', $response);
    }

    $updatedSubscription = is_array($response['data']) ? $response['data'] : $subscription;
    try {
        syncUserLicenseFromSubscription($config, $auth['userId'], $asaasCustomerId, $updatedSubscription, $plan);
    } catch (Throwable $syncError) {
        error_log('[customer-area] Falha ao sincronizar licenca apos troca de plano: ' . $syncError->getMessage());
    }

    jsonResponse([
        'updated' => true,
        'message' => 'Plano atualizado com sucesso.',
        'subscription' => normalizeCustomerAreaSubscriptionResponse($config, $plansContext, $updatedSubscription),
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function handleCustomerAreaUpdatePaymentMethod(array $config, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceHttpsForCardCapture();
    enforceRateLimit('customer_area_update_payment_method', 5, 300);

    $auth = requireAuthenticatedCustomer();
    enforceCsrfToken();
    $asaasCustomerId = resolveAuthenticatedAsaasCustomerId($config, $auth);
    if ($asaasCustomerId === '') {
        throw new ApiException('Nenhuma assinatura ativa encontrada para atualizar o cartao.', 404);
    }

    $subscription = findCustomerPrimarySubscription($config, $asaasCustomerId);
    if ($subscription === null) {
        throw new ApiException('Nenhuma assinatura ativa encontrada para atualizar o cartao.', 404);
    }

    $subscriptionId = cleanText($subscription['id'] ?? '');
    if ($subscriptionId === '') {
        throw new ApiException('Assinatura ativa sem identificador valido.', 502);
    }

    $billingType = strtoupper(cleanText($subscription['billingType'] ?? ''));
    if ($billingType !== 'CREDIT_CARD') {
        throw new ApiException('Atualizacao de cartao disponivel apenas para assinaturas com cobranca em cartao.', 400);
    }

    $validated = validateCustomerAreaPaymentMethodInput($body, $auth);
    $payload = [
        'creditCard' => [
            'holderName' => $validated['holderName'],
            'number' => $validated['cardNumber'],
            'expiryMonth' => $validated['expiryMonth'],
            'expiryYear' => $validated['expiryYear'],
            'ccv' => $validated['ccv'],
        ],
        'creditCardHolderInfo' => [
            'name' => $validated['holderName'],
            'email' => $validated['email'],
            'cpfCnpj' => $validated['cpfCnpj'],
            'postalCode' => $validated['postalCode'],
            'addressNumber' => $validated['addressNumber'],
            'phone' => $validated['phone'],
        ],
        'remoteIp' => getClientIp(),
    ];

    if ($validated['mobilePhone'] !== '') {
        $payload['creditCardHolderInfo']['mobilePhone'] = $validated['mobilePhone'];
    }

    $response = asaasRequest(
        $config,
        'PUT',
        '/v3/subscriptions/' . rawurlencode($subscriptionId) . '/creditCard',
        $payload
    );

    if (!$response['ok']) {
        throwAsaasException('Nao foi possivel atualizar o cartao da assinatura.', $response);
    }

    try {
        syncUserLicenseFromSubscription($config, $auth['userId'], $asaasCustomerId, $subscription, []);
    } catch (Throwable $syncError) {
        error_log('[customer-area] Falha ao sincronizar licenca apos atualizar cartao: ' . $syncError->getMessage());
    }

    $last4 = substr($validated['cardNumber'], -4);
    jsonResponse([
        'updated' => true,
        'message' => 'Cartao atualizado com sucesso.',
        'paymentMethod' => [
            'type' => 'CREDIT_CARD',
            'last4' => $last4,
            'summary' => 'Cartao terminando em ' . $last4,
        ],
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function requireAuthenticatedCustomer(): array
{
    initSecureSession();
    $auth = authenticatedCustomer();
    if ($auth === null) {
        throw new ApiException('Nao autenticado.', 401);
    }

    return $auth;
}

function findCustomerPrimarySubscription(array $config, string $customerId): ?array
{
    $response = asaasRequest(
        $config,
        'GET',
        '/v3/subscriptions',
        null,
        [
            'customer' => $customerId,
            'limit' => '100',
        ]
    );

    if (!$response['ok']) {
        throwAsaasException('Nao foi possivel consultar assinaturas do cliente.', $response);
    }

    $subscriptions = asaasListData($response['data']);
    if (count($subscriptions) === 0) {
        return null;
    }

    $active = [];
    foreach ($subscriptions as $subscription) {
        $status = strtoupper(cleanText($subscription['status'] ?? ''));
        $deleted = (bool) ($subscription['deleted'] ?? false);
        if (!$deleted && $status === 'ACTIVE') {
            $active[] = $subscription;
        }
    }

    if (count($active) === 0) {
        return null;
    }

    usort($active, static function (array $a, array $b): int {
        $aTs = customerAreaDateToTimestamp(cleanText($a['nextDueDate'] ?? ''));
        $bTs = customerAreaDateToTimestamp(cleanText($b['nextDueDate'] ?? ''));

        if ($aTs === $bTs) {
            $aCreated = customerAreaDateToTimestamp(cleanText($a['dateCreated'] ?? ''));
            $bCreated = customerAreaDateToTimestamp(cleanText($b['dateCreated'] ?? ''));
            return $aCreated <=> $bCreated;
        }

        return $aTs <=> $bTs;
    });

    return $active[0];
}

function listCustomerPayments(array $config, string $customerId, int $limit): array
{
    $limit = max(1, min(100, $limit));
    $response = asaasRequest(
        $config,
        'GET',
        '/v3/payments',
        null,
        [
            'customer' => $customerId,
            'limit' => (string) $limit,
            'offset' => '0',
        ]
    );

    if (!$response['ok']) {
        throwAsaasException('Nao foi possivel consultar o historico de cobrancas.', $response);
    }

    return asaasListData($response['data']);
}

function asaasListData(array $data): array
{
    $items = $data['data'] ?? [];
    if (!is_array($items)) {
        return [];
    }

    $normalized = [];
    foreach ($items as $item) {
        if (is_array($item)) {
            $normalized[] = $item;
        }
    }

    return $normalized;
}

function normalizeCustomerAreaSubscriptionResponse(array $config, array $plansContext, array $subscription): array
{
    $subscriptionId = cleanText($subscription['id'] ?? '');
    $cycle = strtoupper(cleanText($subscription['cycle'] ?? ''));
    $value = (float) ($subscription['value'] ?? 0);
    $status = strtoupper(cleanText($subscription['status'] ?? 'INACTIVE'));
    $billingType = strtoupper(cleanText($subscription['billingType'] ?? 'UNDEFINED'));

    $plan = resolveSubscriptionPlanSnapshot($plansContext['activePlans'], $cycle, $value, $subscription);
    $paymentMethod = resolveSubscriptionPaymentMethod($config, $subscriptionId);

    return [
        'id' => $subscriptionId,
        'status' => $status,
        'billingType' => $billingType,
        'cycle' => $cycle,
        'value' => round($value, 2),
        'nextDueDate' => cleanText($subscription['nextDueDate'] ?? ''),
        'description' => cleanText($subscription['description'] ?? ''),
        'plan' => $plan,
        'paymentMethod' => $paymentMethod,
        'canCancel' => $status === 'ACTIVE',
        'canChangePlan' => $status === 'ACTIVE' && !isOnetimeCycle($cycle),
    ];
}

function normalizeCustomerAreaPaymentResponse(array $payment): array
{
    return [
        'id' => cleanText($payment['id'] ?? ''),
        'status' => strtoupper(cleanText($payment['status'] ?? 'PENDING')),
        'billingType' => strtoupper(cleanText($payment['billingType'] ?? 'UNDEFINED')),
        'value' => round((float) ($payment['value'] ?? 0), 2),
        'dueDate' => cleanText($payment['dueDate'] ?? ''),
        'paymentDate' => cleanText($payment['paymentDate'] ?? ''),
        'description' => cleanText($payment['description'] ?? ''),
        'invoiceUrl' => cleanText($payment['invoiceUrl'] ?? ''),
        'bankSlipUrl' => cleanText($payment['bankSlipUrl'] ?? ''),
        'subscriptionId' => cleanText($payment['subscription'] ?? ''),
    ];
}

function resolveSubscriptionPlanSnapshot(array $activePlans, string $cycle, float $value, array $subscription): array
{
    $bestByCycleAndPrice = null;
    $bestByCycle = null;

    foreach ($activePlans as $plan) {
        if (!is_array($plan)) {
            continue;
        }

        $planCycle = strtoupper(cleanText($plan['cycle'] ?? ''));
        if ($planCycle === '' || isOnetimeCycle($planCycle)) {
            continue;
        }

        if ($planCycle === $cycle) {
            $bestByCycle ??= $plan;

            $planPrice = (float) ($plan['price'] ?? 0);
            if (abs($planPrice - $value) < 0.01) {
                $bestByCycleAndPrice = $plan;
                break;
            }
        }
    }

    $picked = $bestByCycleAndPrice ?? $bestByCycle;
    if (is_array($picked)) {
        return [
            'id' => cleanText($picked['id'] ?? ''),
            'name' => cleanText($picked['name'] ?? 'Assinatura'),
            'cycle' => strtoupper(cleanText($picked['cycle'] ?? $cycle)),
            'price' => round((float) ($picked['price'] ?? $value), 2),
        ];
    }

    $fallbackName = cleanText($subscription['description'] ?? '');
    if ($fallbackName === '') {
        $fallbackName = 'Assinatura DeFast';
    }

    return [
        'id' => '',
        'name' => $fallbackName,
        'cycle' => $cycle,
        'price' => round($value, 2),
    ];
}

function resolveSubscriptionPaymentMethod(array $config, string $subscriptionId): ?array
{
    if ($subscriptionId === '') {
        return null;
    }

    $response = asaasRequest(
        $config,
        'GET',
        '/v3/subscriptions/' . rawurlencode($subscriptionId) . '/payments',
        null,
        ['limit' => '12']
    );

    if (!$response['ok']) {
        return null;
    }

    $payments = asaasListData($response['data']);
    if (count($payments) === 0) {
        return null;
    }

    $selected = $payments[0];
    foreach ($payments as $payment) {
        $billingType = strtoupper(cleanText($payment['billingType'] ?? ''));
        if ($billingType === 'CREDIT_CARD') {
            $selected = $payment;
            break;
        }
    }

    $billingType = strtoupper(cleanText($selected['billingType'] ?? 'UNDEFINED'));
    if ($billingType === 'CREDIT_CARD') {
        $brand = strtoupper(cleanText($selected['creditCard']['creditCardBrand'] ?? 'CREDIT_CARD'));
        $last4 = onlyDigits($selected['creditCard']['creditCardNumber'] ?? '');
        if ($last4 !== '' && strlen($last4) > 4) {
            $last4 = substr($last4, -4);
        }

        return [
            'type' => 'CREDIT_CARD',
            'brand' => $brand,
            'last4' => $last4,
            'summary' => $last4 !== ''
                ? sprintf('%s terminando em %s', $brand, $last4)
                : sprintf('%s cadastrado', $brand),
            'extra' => '',
        ];
    }

    if ($billingType === 'PIX') {
        return [
            'type' => 'PIX',
            'brand' => 'PIX',
            'last4' => '',
            'summary' => 'Cobrancas via Pix',
            'extra' => '',
        ];
    }

    if ($billingType === 'BOLETO') {
        return [
            'type' => 'BOLETO',
            'brand' => 'BOLETO',
            'last4' => '',
            'summary' => 'Cobrancas via boleto',
            'extra' => '',
        ];
    }

    return [
        'type' => $billingType !== '' ? $billingType : 'UNDEFINED',
        'brand' => $billingType !== '' ? $billingType : 'PAGAMENTO',
        'last4' => '',
        'summary' => 'Metodo de pagamento ativo',
        'extra' => '',
    ];
}

function validateCustomerAreaPaymentMethodInput(array $body, array $auth): array
{
    $holderName = cleanText($body['holderName'] ?? $auth['name'] ?? '');
    $payload = $body;
    $payload['holderName'] = $holderName;

    $validatedCard = validateCreditCardInput($payload);
    $cpfCnpj = onlyDigits($auth['cpfCnpj'] ?? '');
    $email = strtolower(cleanText($auth['email'] ?? ''));

    if (!isValidEmail($email)) {
        throw new ApiException('Email da sessao e invalido para atualizar o cartao.', 400);
    }

    if (!isValidCpfCnpj($cpfCnpj)) {
        throw new ApiException('CPF/CNPJ da sessao e invalido para atualizar o cartao.', 400);
    }

    return [
        'holderName' => $holderName,
        'cardNumber' => $validatedCard['cardNumber'],
        'expiryMonth' => $validatedCard['expiryMonth'],
        'expiryYear' => $validatedCard['expiryYear'],
        'ccv' => $validatedCard['ccv'],
        'postalCode' => $validatedCard['postalCode'],
        'addressNumber' => $validatedCard['addressNumber'],
        'phone' => $validatedCard['phone'],
        'mobilePhone' => $validatedCard['mobilePhone'],
        'email' => $email,
        'cpfCnpj' => $cpfCnpj,
    ];
}

function customerAreaDateToTimestamp(string $date): int
{
    if ($date === '') {
        return PHP_INT_MAX;
    }

    try {
        return (new DateTimeImmutable($date, new DateTimeZone('America/Sao_Paulo')))->getTimestamp();
    } catch (Throwable) {
        return PHP_INT_MAX;
    }
}

function enforceCsrfToken(): void
{
    $providedToken = cleanText(getHeaderValue('X-CSRF-Token') ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? null;

    if (!is_string($sessionToken) || $sessionToken === '') {
        throw new ApiException('CSRF token ausente na sessao.', 403);
    }

    if ($providedToken === '' || !hash_equals($sessionToken, $providedToken)) {
        throw new ApiException('CSRF token invalido.', 403);
    }
}

function authenticatedCustomer(): ?array
{
    $auth = $_SESSION['auth_customer'] ?? null;
    if (!is_array($auth)) {
        return null;
    }

    $userId = cleanText($auth['userId'] ?? '');
    $asaasCustomerId = cleanText($auth['asaasCustomerId'] ?? '');
    $email = strtolower(cleanText($auth['email'] ?? ''));
    $name = cleanText($auth['name'] ?? 'Cliente');
    $cpfCnpj = onlyDigits($auth['cpfCnpj'] ?? '');
    $phone = onlyDigits($auth['phone'] ?? '');

    if ($userId === '' || $email === '') {
        return null;
    }

    return [
        'userId' => $userId,
        'asaasCustomerId' => $asaasCustomerId,
        'email' => $email,
        'name' => $name,
        'cpfCnpj' => $cpfCnpj,
        'phone' => $phone,
    ];
}

/**
 * Handler de webhook via API PHP.
 *
 * Handler canonico em producao: Supabase Edge Function
 * `supabase/functions/asaas-webhook-sync`.
 * Mantenha este endpoint apenas como fallback local/migracao e nao configure
 * os dois handlers ao mesmo tempo no painel do Asaas.
 */
function handleWebhook(array $config): void
{
    ensureSupabaseServiceConfigured($config);
    $payload = readJsonBody();
    $webhookToken = getHeaderValue('asaas-access-token') ?? getHeaderValue('access_token');

    if ($config['asaas_webhook_token'] === '') {
        throw new ApiException('ASAAS_WEBHOOK_TOKEN nao configurado no servidor.', 500);
    }

    if ($webhookToken === null || !hash_equals($config['asaas_webhook_token'], $webhookToken)) {
        throw new ApiException('Unauthorized', 401);
    }

    $eventName = cleanText($payload['event'] ?? '');
    $paymentId = cleanText($payload['payment']['id'] ?? '');
    syncUserLicenseFromWebhook($config, $eventName, $payload);
    error_log(sprintf('[ASAAS WEBHOOK] event=%s paymentId=%s', $eventName, $paymentId));
    jsonResponse(['received' => true]);
}

function handleRecurringPix(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('payment_pix', 12, 300);
    $idempotency = beginIdempotency('payment_pix', $body, strtolower(trim($body['email'] ?? '')));

    try {
        $customer = validateBaseCustomerInput($body, true);
        $supabaseUserId = requireCheckoutSupabaseUserIdForEmail($config, $customer['email']);
        $plan = resolvePlan($plansContext['activePlans'], (string) ($body['planId'] ?? ''), 'annual');

        if (isOnetimeCycle($plan['cycle'])) {
            throw new ApiException('Plano ONETIME usa a rota /api/onetime/pix.', 400);
        }

        $customerId = findOrCreateCustomer($config, $customer);
        syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);
        $trialDays = max(0, (int) ($plan['trialDays'] ?? 0));
        $nextDueDate = nextDueDateStr($trialDays > 0 ? $trialDays : 1);

        $subscriptionResponse = asaasRequest($config, 'POST', '/v3/subscriptions', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $plan['price'],
            'nextDueDate' => $nextDueDate,
            'cycle' => normalizeRecurringCycle($plan['cycle']),
            'description' => (string) $plan['description'],
            'externalReference' => sprintf('%s_pix_%d', (string) $plan['id'], time()),
            'dueDateLimitDays' => $plansContext['dueDateLimitDays'],
        ]);

        if (!$subscriptionResponse['ok']) {
            throwAsaasException('Nao foi possivel criar a assinatura.', $subscriptionResponse);
        }

        try {
            syncUserLicenseFromSubscription($config, $supabaseUserId, $customerId, $subscriptionResponse['data'] ?? [], $plan);
        } catch (Throwable $syncError) {
            error_log('[checkout] Falha ao sincronizar assinatura PIX no Supabase: ' . $syncError->getMessage());
        }

        $subscriptionId = cleanText($subscriptionResponse['data']['id'] ?? '');
        if ($subscriptionId === '') {
            throw new ApiException('Assinatura criada sem ID no retorno do Asaas.', 502);
        }

        $payment = getSubscriptionFirstPayment($config, $subscriptionId);

        $pixQrCodeResponse = asaasRequest(
            $config,
            'GET',
            '/v3/payments/' . rawurlencode($payment['id']) . '/pixQrCode'
        );

        if (!$pixQrCodeResponse['ok']) {
            throwAsaasException('Nao foi possivel obter o QR code PIX.', $pixQrCodeResponse);
        }

        $payload = [
            'type' => 'pix',
            'pixEncodedImage' => $pixQrCodeResponse['data']['encodedImage'] ?? null,
            'pixPayload' => $pixQrCodeResponse['data']['payload'] ?? null,
            'expirationDate' => $pixQrCodeResponse['data']['expirationDate'] ?? null,
            'dueDate' => $payment['dueDate'] ?? null,
            'value' => $payment['value'] ?? null,
            'subscriptionId' => $subscriptionId,
        ];

        commitIdempotency($idempotency, $payload, 201);
        jsonResponse($payload, 201);
    } catch (Throwable $error) {
        releaseIdempotency($idempotency);
        throw $error;
    }
}

function handleRecurringBoleto(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('payment_boleto', 12, 300);
    $idempotency = beginIdempotency('payment_boleto', $body, strtolower(trim($body['email'] ?? '')));

    try {
        $customer = validateBaseCustomerInput($body, true);
        $supabaseUserId = requireCheckoutSupabaseUserIdForEmail($config, $customer['email']);
        $plan = resolvePlan($plansContext['activePlans'], (string) ($body['planId'] ?? ''), 'annual');

        if (isOnetimeCycle($plan['cycle'])) {
            throw new ApiException('Plano ONETIME usa a rota /api/onetime/boleto.', 400);
        }

        $customerId = findOrCreateCustomer($config, $customer);
        syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);
        $trialDays = max(0, (int) ($plan['trialDays'] ?? 0));
        $nextDueDate = nextDueDateStr($trialDays > 0 ? $trialDays : 1);

        $subscriptionResponse = asaasRequest($config, 'POST', '/v3/subscriptions', [
            'customer' => $customerId,
            'billingType' => 'BOLETO',
            'value' => (float) $plan['price'],
            'nextDueDate' => $nextDueDate,
            'cycle' => normalizeRecurringCycle($plan['cycle']),
            'description' => (string) $plan['description'],
            'externalReference' => sprintf('%s_boleto_%d', (string) $plan['id'], time()),
            'dueDateLimitDays' => $plansContext['dueDateLimitDays'],
        ]);

        if (!$subscriptionResponse['ok']) {
            throwAsaasException('Nao foi possivel criar a assinatura.', $subscriptionResponse);
        }

        try {
            syncUserLicenseFromSubscription($config, $supabaseUserId, $customerId, $subscriptionResponse['data'] ?? [], $plan);
        } catch (Throwable $syncError) {
            error_log('[checkout] Falha ao sincronizar assinatura boleto no Supabase: ' . $syncError->getMessage());
        }

        $subscriptionId = cleanText($subscriptionResponse['data']['id'] ?? '');
        if ($subscriptionId === '') {
            throw new ApiException('Assinatura criada sem ID no retorno do Asaas.', 502);
        }

        $payment = getSubscriptionFirstPayment($config, $subscriptionId);

        $payload = [
            'type' => 'boleto',
            'bankSlipUrl' => $payment['bankSlipUrl'] ?? null,
            'barCode' => $payment['nossoNumero'] ?? null,
            'dueDate' => $payment['dueDate'] ?? null,
            'value' => $payment['value'] ?? null,
            'subscriptionId' => $subscriptionId,
        ];

        commitIdempotency($idempotency, $payload, 201);
        jsonResponse($payload, 201);
    } catch (Throwable $error) {
        releaseIdempotency($idempotency);
        throw $error;
    }
}

function handleRecurringCreditCard(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('payment_credit_card', 10, 300);
    $idempotency = beginIdempotency('payment_credit_card', $body, strtolower(trim($body['email'] ?? '')));

    try {
        $customer = validateBaseCustomerInput($body, true);
        $supabaseUserId = requireCheckoutSupabaseUserIdForEmail($config, $customer['email']);
        $plan = resolvePlan($plansContext['activePlans'], (string) ($body['planId'] ?? ''), 'annual');

        if (isOnetimeCycle($plan['cycle'])) {
            throw new ApiException('Plano ONETIME usa a rota /api/onetime/credit-card.', 400);
        }

        if ($config['card_processing_mode'] === 'HOSTED_LINK') {
            $customerId = findOrCreateCustomer($config, $customer);
            syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);

            $checkoutUrl = createHostedCardPaymentLink($config, $customer, $plan, 'RECURRENT');
            $payload = [
                'type' => 'credit_card_redirect',
                'checkoutUrl' => $checkoutUrl,
                'message' => 'Redirecione o cliente para concluir o pagamento em ambiente seguro da Asaas.',
            ];

            commitIdempotency($idempotency, $payload, 201);
            jsonResponse($payload, 201);
            return;
        }

        enforceHttpsForCardCapture();

        $card = validateCreditCardInput($body);
        $customerId = findOrCreateCustomer($config, $customer);
        syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);
        $trialDays = max(0, (int) ($plan['trialDays'] ?? 0));
        $nextDueDate = nextDueDateStr($trialDays > 0 ? $trialDays : 1);

        $asaasPayload = [
            'customer' => $customerId,
            'billingType' => 'CREDIT_CARD',
            'value' => (float) $plan['price'],
            'nextDueDate' => $nextDueDate,
            'cycle' => normalizeRecurringCycle($plan['cycle']),
            'description' => (string) $plan['description'],
            'externalReference' => sprintf('%s_cc_%d', (string) $plan['id'], time()),
            'creditCard' => [
                'holderName' => $card['holderName'],
                'number' => $card['cardNumber'],
                'expiryMonth' => $card['expiryMonth'],
                'expiryYear' => $card['expiryYear'],
                'ccv' => $card['ccv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'cpfCnpj' => $customer['cpfCnpj'],
                'postalCode' => $card['postalCode'],
                'addressNumber' => $card['addressNumber'],
                'phone' => $card['phone'],
            ],
            'remoteIp' => getClientIp(),
        ];

        if ($card['mobilePhone'] !== '') {
            $asaasPayload['creditCardHolderInfo']['mobilePhone'] = $card['mobilePhone'];
        }

        $subscriptionResponse = asaasRequest($config, 'POST', '/v3/subscriptions/', $asaasPayload);
        if (!$subscriptionResponse['ok']) {
            $firstError = firstAsaasErrorMessage($subscriptionResponse['data']);
            throw new ApiException(
                $firstError !== '' ? $firstError : 'Pagamento com cartao nao autorizado.',
                normalizeErrorStatus($subscriptionResponse['status']),
                asaasErrorList($subscriptionResponse['data'])
            );
        }

        try {
            syncUserLicenseFromSubscription($config, $supabaseUserId, $customerId, $subscriptionResponse['data'] ?? [], $plan);
        } catch (Throwable $syncError) {
            error_log('[checkout] Falha ao sincronizar assinatura cartao no Supabase: ' . $syncError->getMessage());
        }

        $payload = [
            'type' => 'credit_card',
            'status' => $subscriptionResponse['data']['status'] ?? null,
            'subscriptionId' => $subscriptionResponse['data']['id'] ?? null,
            'message' => 'Assinatura ativada com sucesso!',
        ];

        commitIdempotency($idempotency, $payload, 201);
        jsonResponse($payload, 201);
    } catch (Throwable $error) {
        releaseIdempotency($idempotency);
        throw $error;
    }
}

function handleOnetimePix(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('onetime_pix', 12, 300);
    $idempotency = beginIdempotency('onetime_pix', $body, strtolower(trim($body['email'] ?? '')));

    try {
        $customer = validateBaseCustomerInput($body, true);
        $supabaseUserId = requireCheckoutSupabaseUserIdForEmail($config, $customer['email']);
        $plan = resolvePlan($plansContext['activePlans'], (string) ($body['planId'] ?? ''), 'onetime');

        if (!isOnetimeCycle($plan['cycle'])) {
            throw new ApiException('A rota /api/onetime/pix aceita apenas planos ONETIME.', 400);
        }

        $customerId = findOrCreateCustomer($config, $customer);
        syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);
        $dueDate = nextDueDateStr(0);

        $paymentResponse = asaasRequest($config, 'POST', '/v3/payments', [
            'customer' => $customerId,
            'billingType' => 'PIX',
            'value' => (float) $plan['price'],
            'dueDate' => $dueDate,
            'description' => (string) $plan['description'],
            'externalReference' => sprintf('%s_onetime_pix_%d', (string) $plan['id'], time()),
        ]);

        if (!$paymentResponse['ok']) {
            throwAsaasException('Nao foi possivel criar o pagamento PIX.', $paymentResponse);
        }

        $paymentId = cleanText($paymentResponse['data']['id'] ?? '');
        if ($paymentId === '') {
            throw new ApiException('Pagamento criado sem ID no retorno do Asaas.', 502);
        }

        $pixQrCodeResponse = asaasRequest(
            $config,
            'GET',
            '/v3/payments/' . rawurlencode($paymentId) . '/pixQrCode'
        );

        if (!$pixQrCodeResponse['ok']) {
            throwAsaasException('Nao foi possivel obter o QR code PIX.', $pixQrCodeResponse);
        }

        $checkoutExpiresAt = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))
            ->modify('+15 minutes')
            ->format(DATE_ATOM);

        $payload = [
            'type' => 'pix',
            'pixEncodedImage' => $pixQrCodeResponse['data']['encodedImage'] ?? null,
            'pixPayload' => $pixQrCodeResponse['data']['payload'] ?? null,
            'expirationDate' => $pixQrCodeResponse['data']['expirationDate'] ?? null,
            'checkoutExpiresAt' => $checkoutExpiresAt,
            'dueDate' => $paymentResponse['data']['dueDate'] ?? null,
            'value' => $paymentResponse['data']['value'] ?? null,
            'paymentId' => $paymentId,
        ];

        commitIdempotency($idempotency, $payload, 201);
        jsonResponse($payload, 201);
    } catch (Throwable $error) {
        releaseIdempotency($idempotency);
        throw $error;
    }
}

function handleOnetimeBoleto(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('onetime_boleto', 12, 300);
    $idempotency = beginIdempotency('onetime_boleto', $body, strtolower(trim($body['email'] ?? '')));

    try {
        $customer = validateBaseCustomerInput($body, true);
        $supabaseUserId = requireCheckoutSupabaseUserIdForEmail($config, $customer['email']);
        $plan = resolvePlan($plansContext['activePlans'], (string) ($body['planId'] ?? ''), 'onetime');

        if (!isOnetimeCycle($plan['cycle'])) {
            throw new ApiException('A rota /api/onetime/boleto aceita apenas planos ONETIME.', 400);
        }

        $customerId = findOrCreateCustomer($config, $customer);
        syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);
        $dueDate = nextDueDateStr(max(1, $plansContext['dueDateLimitDays']));

        $paymentResponse = asaasRequest($config, 'POST', '/v3/payments', [
            'customer' => $customerId,
            'billingType' => 'BOLETO',
            'value' => (float) $plan['price'],
            'dueDate' => $dueDate,
            'description' => (string) $plan['description'],
            'externalReference' => sprintf('%s_onetime_boleto_%d', (string) $plan['id'], time()),
        ]);

        if (!$paymentResponse['ok']) {
            throwAsaasException('Nao foi possivel criar o boleto.', $paymentResponse);
        }

        $payload = [
            'type' => 'boleto',
            'bankSlipUrl' => $paymentResponse['data']['bankSlipUrl'] ?? null,
            'barCode' => $paymentResponse['data']['nossoNumero'] ?? null,
            'dueDate' => $paymentResponse['data']['dueDate'] ?? null,
            'value' => $paymentResponse['data']['value'] ?? null,
            'paymentId' => $paymentResponse['data']['id'] ?? null,
        ];

        commitIdempotency($idempotency, $payload, 201);
        jsonResponse($payload, 201);
    } catch (Throwable $error) {
        releaseIdempotency($idempotency);
        throw $error;
    }
}

function handleOnetimeCreditCard(array $config, array $plansContext, array $body): void
{
    ensureApiKeyConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('onetime_credit_card', 10, 300);
    $idempotency = beginIdempotency('onetime_credit_card', $body, strtolower(trim($body['email'] ?? '')));

    try {
        $customer = validateBaseCustomerInput($body, true);
        $supabaseUserId = requireCheckoutSupabaseUserIdForEmail($config, $customer['email']);
        $plan = resolvePlan($plansContext['activePlans'], (string) ($body['planId'] ?? ''), 'onetime');

        if (!isOnetimeCycle($plan['cycle'])) {
            throw new ApiException('A rota /api/onetime/credit-card aceita apenas planos ONETIME.', 400);
        }

        if ($config['card_processing_mode'] === 'HOSTED_LINK') {
            $customerId = findOrCreateCustomer($config, $customer);
            syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);

            $checkoutUrl = createHostedCardPaymentLink($config, $customer, $plan, 'DETACHED');
            $payload = [
                'type' => 'credit_card_redirect',
                'checkoutUrl' => $checkoutUrl,
                'message' => 'Redirecione o cliente para concluir o pagamento em ambiente seguro da Asaas.',
            ];

            commitIdempotency($idempotency, $payload, 201);
            jsonResponse($payload, 201);
            return;
        }

        enforceHttpsForCardCapture();

        $card = validateCreditCardInput($body);
        $customerId = findOrCreateCustomer($config, $customer);
        syncCheckoutCustomerContext($config, $supabaseUserId, $customer, $customerId, $plan);
        $dueDate = nextDueDateStr(max(1, (int) ($plan['trialDays'] ?? 0)));

        $asaasPayload = [
            'customer' => $customerId,
            'billingType' => 'CREDIT_CARD',
            'value' => (float) $plan['price'],
            'dueDate' => $dueDate,
            'description' => (string) $plan['description'],
            'externalReference' => sprintf('%s_onetime_cc_%d', (string) $plan['id'], time()),
            'creditCard' => [
                'holderName' => $card['holderName'],
                'number' => $card['cardNumber'],
                'expiryMonth' => $card['expiryMonth'],
                'expiryYear' => $card['expiryYear'],
                'ccv' => $card['ccv'],
            ],
            'creditCardHolderInfo' => [
                'name' => $customer['name'],
                'email' => $customer['email'],
                'cpfCnpj' => $customer['cpfCnpj'],
                'postalCode' => $card['postalCode'],
                'addressNumber' => $card['addressNumber'],
                'phone' => $card['phone'],
            ],
            'remoteIp' => getClientIp(),
        ];

        if ($card['mobilePhone'] !== '') {
            $asaasPayload['creditCardHolderInfo']['mobilePhone'] = $card['mobilePhone'];
        }

        $paymentResponse = asaasRequest($config, 'POST', '/v3/payments/', $asaasPayload);
        if (!$paymentResponse['ok']) {
            $firstError = firstAsaasErrorMessage($paymentResponse['data']);
            throw new ApiException(
                $firstError !== '' ? $firstError : 'Pagamento com cartao nao autorizado.',
                normalizeErrorStatus($paymentResponse['status']),
                asaasErrorList($paymentResponse['data'])
            );
        }

        $payload = [
            'type' => 'credit_card',
            'status' => $paymentResponse['data']['status'] ?? null,
            'paymentId' => $paymentResponse['data']['id'] ?? null,
            'invoiceUrl' => $paymentResponse['data']['invoiceUrl'] ?? null,
            'message' => 'Pagamento aprovado com sucesso!',
        ];

        commitIdempotency($idempotency, $payload, 201);
        jsonResponse($payload, 201);
    } catch (Throwable $error) {
        releaseIdempotency($idempotency);
        throw $error;
    }
}

function createHostedCardPaymentLink(array $config, array $customer, array $plan, string $chargeType): string
{
    $chargeType = strtoupper($chargeType);
    if ($config['card_processing_mode'] !== 'HOSTED_LINK') {
        throw new ApiException('Pagamento com cartao direto esta desabilitado por seguranca.', 403);
    }

    $payload = [
        'name' => substr($plan['name'] !== '' ? (string) $plan['name'] : 'Pagamento DeFast', 0, 40),
        'description' => trim((string) $plan['description'] . ' - Cliente: ' . ($customer['name'] ?? 'Cliente')),
        'value' => (float) $plan['price'],
        'billingType' => 'CREDIT_CARD',
        'chargeType' => $chargeType,
        'notificationEnabled' => false,
        'externalReference' => sprintf('%s_cc_%s_%d', (string) $plan['id'], substr((string) ($customer['cpfCnpj'] ?? 'doc'), 0, 6), time()),
        'callback' => [
            'successUrl' => (string) $config['checkout_success_url'],
            'autoRedirect' => true,
        ],
    ];

    if ($chargeType === 'RECURRENT') {
        $payload['subscriptionCycle'] = normalizeRecurringCycle((string) $plan['cycle']);
    }

    $linkResponse = asaasRequest($config, 'POST', '/v3/paymentLinks', $payload);
    if (!$linkResponse['ok']) {
        throwAsaasException('Nao foi possivel gerar o checkout seguro com cartao.', $linkResponse);
    }

    $checkoutUrl = cleanText($linkResponse['data']['url'] ?? '');
    if ($checkoutUrl === '') {
        throw new ApiException('Checkout de cartao criado sem URL de redirecionamento.', 502);
    }

    if (!str_starts_with($checkoutUrl, 'https://')) {
        throw new ApiException('URL de checkout invalida retornada pelo Asaas.', 502);
    }

    return $checkoutUrl;
}

function findOrCreateCustomer(array $config, array $customer): string
{
    $payload = [
        'name' => $customer['name'],
        'email' => $customer['email'],
        'cpfCnpj' => $customer['cpfCnpj'],
        'notificationDisabled' => false,
    ];

    if ($customer['phone'] !== '') {
        $payload['mobilePhone'] = $customer['phone'];
    }

    $createResponse = asaasRequest($config, 'POST', '/v3/customers', $payload);
    if ($createResponse['ok']) {
        $customerId = cleanText($createResponse['data']['id'] ?? '');
        if ($customerId === '') {
            throw new ApiException('Cliente criado sem ID no retorno do Asaas.', 502);
        }
        return $customerId;
    }

    if (!isDuplicateCustomerError($createResponse['data'])) {
        throwAsaasException('Nao foi possivel criar o cliente no Asaas.', $createResponse);
    }

    $searchResponse = asaasRequest(
        $config,
        'GET',
        '/v3/customers',
        null,
        ['cpfCnpj' => $customer['cpfCnpj'], 'limit' => '1']
    );

    if (!$searchResponse['ok']) {
        throwAsaasException('Cliente duplicado e nao foi possivel consultar o cadastro existente.', $searchResponse);
    }

    $existing = $searchResponse['data']['data'][0]['id'] ?? '';
    $existingCustomerId = cleanText($existing);

    if ($existingCustomerId === '') {
        throw new ApiException(
            'Cliente duplicado, mas nao encontrado na busca por CPF/CNPJ.',
            400,
            [['description' => 'Cliente duplicado sem correspondencia na consulta.']]
        );
    }

    return $existingCustomerId;
}

function getSubscriptionFirstPayment(array $config, string $subscriptionId): array
{
    $response = asaasRequest(
        $config,
        'GET',
        '/v3/subscriptions/' . rawurlencode($subscriptionId) . '/payments',
        null,
        ['limit' => '1']
    );

    if (!$response['ok']) {
        throwAsaasException('Nao foi possivel listar as cobrancas da assinatura.', $response);
    }

    $payments = $response['data']['data'] ?? [];
    if (!is_array($payments) || count($payments) === 0 || !is_array($payments[0])) {
        throw new ApiException('Assinatura sem cobrancas retornadas pelo Asaas.', 502);
    }

    return $payments[0];
}

function resolvePlan(array $activePlans, string $requestedPlanId, string $defaultPlanId): array
{
    $requestedPlanId = cleanText($requestedPlanId);

    if ($requestedPlanId !== '') {
        $requested = findPlanById($activePlans, $requestedPlanId);
        if ($requested !== null) {
            return $requested;
        }
    }

    $defaultPlan = findPlanById($activePlans, $defaultPlanId);
    if ($defaultPlan !== null) {
        return $defaultPlan;
    }

    if (count($activePlans) > 0) {
        return $activePlans[0];
    }

    throw new ApiException('Nenhum plano ativo disponivel.', 500);
}

function findPlanById(array $plans, string $planId): ?array
{
    $targetId = strtolower(trim($planId));
    foreach ($plans as $plan) {
        if (!is_array($plan)) {
            continue;
        }

        $currentId = strtolower(trim((string) ($plan['id'] ?? '')));
        if ($currentId === $targetId) {
            return $plan;
        }
    }

    return null;
}

function loadPlansContext(string $plansFilePath, array $config): array
{
    $dueDateLimitDays = normalizePositiveInt($config['asaas_due_date_limit_days'], 3);
    $activePlans = [];

    if (is_file($plansFilePath)) {
        $raw = file_get_contents($plansFilePath);
        $decoded = $raw !== false ? json_decode($raw, true) : null;

        if (is_array($decoded)) {
            if (isset($decoded['billing']['dueDateLimitDays'])) {
                $dueDateLimitDays = normalizePositiveInt($decoded['billing']['dueDateLimitDays'], $dueDateLimitDays);
            }

            $plans = $decoded['plans'] ?? [];
            if (is_array($plans)) {
                foreach ($plans as $plan) {
                    if (!is_array($plan)) {
                        continue;
                    }

                    if (($plan['active'] ?? true) === false) {
                        continue;
                    }

                    $activePlans[] = normalizePlan($plan);
                }
            }
        }
    }

    if (count($activePlans) === 0) {
        $activePlans[] = [
            'id' => 'annual',
            'name' => $config['annual_plan_name'],
            'description' => $config['annual_plan_description'],
            'price' => (float) $config['annual_plan_price'],
            'cycle' => 'YEARLY',
            'active' => true,
            'trialDays' => 1,
            'features' => [],
            'display' => [
                'badge' => 'Assinatura',
                'label' => 'Anual',
                'sublabel' => 'Renova automaticamente',
                'discountBadge' => null,
            ],
        ];
    }

    return [
        'activePlans' => $activePlans,
        'dueDateLimitDays' => $dueDateLimitDays,
    ];
}

function normalizePlan(array $plan): array
{
    $id = cleanText($plan['id'] ?? '');
    if ($id === '') {
        $id = 'plan_' . substr(md5(json_encode($plan)), 0, 8);
    }

    $name = cleanText($plan['name'] ?? 'Plano');
    if ($name === '') {
        $name = 'Plano';
    }

    $description = cleanText($plan['description'] ?? '');
    $price = (float) ($plan['price'] ?? 0);
    if (!is_finite($price) || $price < 0) {
        $price = 0;
    }

    $cycle = strtoupper(cleanText($plan['cycle'] ?? 'YEARLY'));
    if ($cycle === '') {
        $cycle = 'YEARLY';
    }

    $trialDays = (int) ($plan['trialDays'] ?? 0);
    if ($trialDays < 0) {
        $trialDays = 0;
    }

    $features = $plan['features'] ?? [];
    if (!is_array($features)) {
        $features = [];
    }

    $display = $plan['display'] ?? [];
    if (!is_array($display)) {
        $display = [];
    }

    return [
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'price' => round($price, 2),
        'cycle' => $cycle,
        'active' => ($plan['active'] ?? true) !== false,
        'trialDays' => $trialDays,
        'features' => array_values(array_map('strval', $features)),
        'display' => [
            'badge' => cleanText($display['badge'] ?? ''),
            'label' => cleanText($display['label'] ?? ''),
            'sublabel' => cleanText($display['sublabel'] ?? ''),
            'discountBadge' => isset($display['discountBadge']) ? cleanText((string) $display['discountBadge']) : null,
        ],
    ];
}

function validateBaseCustomerInput(array $body, bool $cpfRequired): array
{
    $name = cleanText($body['name'] ?? '');
    $email = cleanText($body['email'] ?? '');
    $cpfCnpj = onlyDigits($body['cpfCnpj'] ?? '');
    $phone = onlyDigits($body['phone'] ?? '');

    if ($name === '' || strlen($name) < 3) {
        throw new ApiException('Informe um nome valido.', 400);
    }

    if (!isValidEmail($email)) {
        throw new ApiException('Informe um e-mail valido.', 400);
    }

    if ($cpfRequired && $cpfCnpj === '') {
        throw new ApiException('CPF ou CNPJ e obrigatorio.', 400);
    }

    if ($cpfCnpj !== '' && !isValidCpfCnpj($cpfCnpj)) {
        throw new ApiException('CPF ou CNPJ invalido.', 400);
    }

    return [
        'name' => $name,
        'email' => $email,
        'cpfCnpj' => $cpfCnpj,
        'phone' => $phone,
    ];
}

function validateCreditCardInput(array $body): array
{
    $postalCode = onlyDigits($body['postalCode'] ?? '');
    $addressNumber = cleanText($body['addressNumber'] ?? '');
    $holderName = cleanText($body['holderName'] ?? '');
    $cardNumber = onlyDigits($body['cardNumber'] ?? '');
    $expiryMonth = onlyDigits($body['expiryMonth'] ?? '');
    $expiryYear = onlyDigits($body['expiryYear'] ?? '');
    $ccv = onlyDigits($body['ccv'] ?? '');

    $phone = onlyDigits($body['phone'] ?? '');
    $mobilePhone = onlyDigits($body['mobilePhone'] ?? '');

    if ($phone === '' && $mobilePhone !== '') {
        $phone = $mobilePhone;
    }

    if ($postalCode === '' || strlen($postalCode) < 8) {
        throw new ApiException('Informe um CEP valido.', 400);
    }

    if ($addressNumber === '') {
        throw new ApiException('Informe o numero do endereco.', 400);
    }

    if ($holderName === '') {
        throw new ApiException('Informe o nome no cartao.', 400);
    }

    if ($cardNumber === '' || strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
        throw new ApiException('Informe um numero de cartao valido.', 400);
    }

    if (!isValidCardNumber($cardNumber)) {
        throw new ApiException('Numero de cartao invalido.', 400);
    }

    if ($expiryMonth === '' || strlen($expiryMonth) > 2) {
        throw new ApiException('Informe o mes de validade do cartao.', 400);
    }

    $expiryMonth = str_pad($expiryMonth, 2, '0', STR_PAD_LEFT);
    $monthInt = (int) $expiryMonth;
    if ($monthInt < 1 || $monthInt > 12) {
        throw new ApiException('Mes de validade do cartao invalido.', 400);
    }

    if ($expiryYear === '' || (strlen($expiryYear) !== 2 && strlen($expiryYear) !== 4)) {
        throw new ApiException('Informe o ano de validade do cartao.', 400);
    }

    if (strlen($expiryYear) === 2) {
        $expiryYear = '20' . $expiryYear;
    }

    $currentYear = (int) date('Y');
    $currentMonth = (int) date('m');
    if ((int) $expiryYear < $currentYear || ((int) $expiryYear === $currentYear && $monthInt < $currentMonth)) {
        throw new ApiException('Cartao expirado.', 400);
    }

    if ($ccv === '' || strlen($ccv) < 3 || strlen($ccv) > 4) {
        throw new ApiException('Informe um CVV valido.', 400);
    }

    if ($phone === '' || strlen($phone) < 10) {
        throw new ApiException('Informe um telefone com DDD para pagamento no cartao.', 400);
    }

    return [
        'postalCode' => $postalCode,
        'addressNumber' => $addressNumber,
        'holderName' => $holderName,
        'cardNumber' => $cardNumber,
        'expiryMonth' => $expiryMonth,
        'expiryYear' => $expiryYear,
        'ccv' => $ccv,
        'phone' => $phone,
        'mobilePhone' => $mobilePhone,
    ];
}

function isValidCardNumber(string $cardNumber): bool
{
    $sum = 0;
    $isEven = false;

    for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
        $digit = (int) $cardNumber[$i];
        if ($isEven) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $isEven = !$isEven;
    }

    return ($sum % 10) === 0;
}

function asaasRequest(
    array $config,
    string $method,
    string $path,
    ?array $body = null,
    array $query = []
): array {
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 500,
            'data' => ['errors' => [['description' => 'Extensao cURL nao esta habilitada no PHP.']]],
        ];
    }

    $url = rtrim($config['asaas_api_base'], '/') . $path;
    if (count($query) > 0) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'ok' => false,
            'status' => 500,
            'data' => ['errors' => [['description' => 'Falha ao inicializar cURL.']]],
        ];
    }

    $headers = [
        'accept: application/json',
        'access_token: ' . $config['asaas_api_key'],
        'user-agent: defast-compra/1.0',
    ];

    $method = strtoupper($method);
    $payloadJson = null;

    if ($body !== null) {
        $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            $payloadJson = '{}';
        }
        $headers[] = 'content-type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $config['asaas_timeout_seconds'],
    ]);

    if ($payloadJson !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    }

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($rawResponse === false) {
        return [
            'ok' => false,
            'status' => 503,
            'data' => ['errors' => [['description' => $curlError !== '' ? $curlError : 'Falha de conexao com o Asaas.']]],
        ];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        $decoded = [
            'errors' => [[
                'description' => cleanText(substr((string) $rawResponse, 0, 500)) !== ''
                    ? cleanText(substr((string) $rawResponse, 0, 500))
                    : 'Resposta invalida retornada pelo Asaas.',
            ]],
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $decoded,
    ];
}

function validateAccountPassword(mixed $password): string
{
    $value = (string) $password;
    if (strlen($value) < 6) {
        throw new ApiException('A senha deve ter no minimo 6 caracteres.', 400);
    }

    return $value;
}

function ensureSupabaseAuthConfigured(array $config): void
{
    if (cleanText($config['supabase_url'] ?? '') === '' || cleanText($config['supabase_anon_key'] ?? '') === '') {
        throw new ApiException('SUPABASE_URL e SUPABASE_PUBLISHABLE_KEY/SUPABASE_ANON_KEY devem estar configurados.', 500);
    }
}

function ensureSupabaseServiceConfigured(array $config): void
{
    if (cleanText($config['supabase_url'] ?? '') === '' || resolveSupabaseServiceRoleKey($config) === '') {
        throw new ApiException('SUPABASE_URL e SUPABASE_SERVICE_ROLE_KEY devem estar configurados.', 500);
    }
}

function resolveSupabaseServiceRoleKey(array $config): string
{
    $direct = cleanText($config['supabase_service_role_key'] ?? '');
    if ($direct !== '') {
        return $direct;
    }

    $keyFile = cleanText($config['supabase_service_role_key_file'] ?? '');
    if ($keyFile === '') {
        return '';
    }

    $isWindowsAbsolute = preg_match('/^[A-Za-z]:[\\\\\/]/', $keyFile) === 1;
    $isUnixAbsolute = str_starts_with($keyFile, '/');
    $resolvedPath = $keyFile;
    if (!$isWindowsAbsolute && !$isUnixAbsolute) {
        $resolvedPath = rtrim((string) ($config['project_root'] ?? ''), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $keyFile;
    }

    if (!is_file($resolvedPath)) {
        return '';
    }

    $contents = file_get_contents($resolvedPath);
    return $contents !== false ? cleanText($contents) : '';
}

function shouldExposeApiErrorDetails(array $config): bool
{
    $value = strtolower(cleanText($config['api_expose_error_details'] ?? '0'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function supabaseRequest(
    array $config,
    string $method,
    string $path,
    ?array $body = null,
    array $query = [],
    string $authMode = 'service',
    array $extraHeaders = []
): array {
    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'status' => 500,
            'data' => ['message' => 'Extensao cURL nao esta habilitada no PHP.'],
        ];
    }

    $baseUrl = rtrim((string) ($config['supabase_url'] ?? ''), '/');
    if ($baseUrl === '') {
        return [
            'ok' => false,
            'status' => 500,
            'data' => ['message' => 'SUPABASE_URL nao configurado.'],
        ];
    }

    $apiKey = $authMode === 'anon'
        ? cleanText($config['supabase_anon_key'] ?? '')
        : resolveSupabaseServiceRoleKey($config);
    if ($apiKey === '') {
        return [
            'ok' => false,
            'status' => 500,
            'data' => ['message' => $authMode === 'anon'
                ? 'SUPABASE_PUBLISHABLE_KEY/SUPABASE_ANON_KEY nao configurado.'
                : 'SUPABASE_SERVICE_ROLE_KEY nao configurado.'],
        ];
    }

    $url = $baseUrl . $path;
    if (count($query) > 0) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'ok' => false,
            'status' => 500,
            'data' => ['message' => 'Falha ao inicializar cURL.'],
        ];
    }

    $headers = [
        'accept: application/json',
        'apikey: ' . $apiKey,
        'authorization: Bearer ' . $apiKey,
        'user-agent: defast-compra/1.0',
    ];

    foreach ($extraHeaders as $header) {
        if (is_string($header) && trim($header) !== '') {
            $headers[] = $header;
        }
    }

    $method = strtoupper($method);
    $payloadJson = null;
    if ($body !== null) {
        $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            $payloadJson = '{}';
        }
        $headers[] = 'content-type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => max(10, (int) ($config['asaas_timeout_seconds'] ?? 60)),
    ]);

    if ($payloadJson !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    }

    $rawResponse = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($rawResponse === false) {
        return [
            'ok' => false,
            'status' => 503,
            'data' => ['message' => $curlError !== '' ? $curlError : 'Falha de conexao com o Supabase.'],
        ];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        $trimmed = cleanText(substr((string) $rawResponse, 0, 500));
        $decoded = [
            'message' => $trimmed !== '' ? $trimmed : 'Resposta invalida retornada pelo Supabase.',
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $decoded,
    ];
}

function supabaseErrorMessage(array $data): string
{
    $candidates = [
        cleanText($data['msg'] ?? ''),
        cleanText($data['message'] ?? ''),
        cleanText($data['error_description'] ?? ''),
        cleanText($data['error'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function supabaseErrorDetails(array $data): array
{
    $message = supabaseErrorMessage($data);
    if ($message === '') {
        return [];
    }

    return [[
        'code' => cleanText($data['error_code'] ?? $data['code'] ?? ''),
        'description' => $message,
    ]];
}

function isSupabaseUserAlreadyRegistered(array $data): bool
{
    $message = strtolower(supabaseErrorMessage($data));
    $code = strtolower(cleanText($data['error_code'] ?? $data['code'] ?? ''));

    return str_contains($message, 'already registered')
        || str_contains($message, 'already exists')
        || str_contains($code, 'email_exists')
        || str_contains($code, 'user_already_exists');
}

function supabasePasswordSignIn(array $config, string $email, string $password): array
{
    return supabaseRequest(
        $config,
        'POST',
        '/auth/v1/token',
        [
            'email' => $email,
            'password' => $password,
        ],
        ['grant_type' => 'password'],
        'anon'
    );
}

function registerOrValidateSupabaseUser(
    array $config,
    string $email,
    string $password,
    string $name,
    string $phone
): array {
    $createResponse = supabaseRequest(
        $config,
        'POST',
        '/auth/v1/signup',
        [
            'email' => $email,
            'password' => $password,
            'data' => [
                'full_name' => $name,
                'phone' => $phone,
            ],
        ],
        [],
        'anon'
    );

    if ($createResponse['ok'] && isSupabaseUserAlreadyRegistered($createResponse['data'])) {
        throw new ApiException('Este e-mail ja existe. Use outro e-mail ou faca login.', 409);
    }

    if ($createResponse['ok']) {
        $createdUser = $createResponse['data']['user'] ?? $createResponse['data'];
        return is_array($createdUser) ? $createdUser : [];
    }

    if (!isSupabaseUserAlreadyRegistered($createResponse['data'])) {
        throw new ApiException(
            'Nao foi possivel criar a conta no Supabase.',
            normalizeErrorStatus((int) ($createResponse['status'] ?? 500)),
            supabaseErrorDetails($createResponse['data'])
        );
    }

    throw new ApiException('Este e-mail ja existe. Use outro e-mail ou faca login.', 409);
}

function syncSupabaseUserMetadata(array $config, string $userId, array $customer, string $asaasCustomerId): void
{
    $metadata = [
        'full_name' => cleanText($customer['name'] ?? ''),
        'phone' => onlyDigits($customer['phone'] ?? ''),
        'cpf_cnpj' => onlyDigits($customer['cpfCnpj'] ?? ''),
    ];

    if ($asaasCustomerId !== '') {
        $metadata['asaas_customer_id'] = $asaasCustomerId;
    }

    $update = supabaseRequest(
        $config,
        'PUT',
        '/auth/v1/admin/users/' . rawurlencode($userId),
        [
            'user_metadata' => $metadata,
            'app_metadata' => [
                'billing_provider' => 'asaas',
                'source' => 'defast-compra',
            ],
        ],
        [],
        'service'
    );

    if (!$update['ok']) {
        throw new ApiException(
            'Nao foi possivel sincronizar metadados do usuario no Supabase.',
            normalizeErrorStatus((int) ($update['status'] ?? 500)),
            supabaseErrorDetails($update['data'])
        );
    }
}

function findUserLicenseByUserId(array $config, string $userId): ?array
{
    if ($userId === '') {
        return null;
    }

    $response = supabaseRequest(
        $config,
        'GET',
        '/rest/v1/user_licenses',
        null,
        [
            'select' => 'user_id,stripe_customer_id,subscription_end,is_active,plan_id,plan_interval',
            'user_id' => 'eq.' . $userId,
            'limit' => '1',
        ],
        'service'
    );

    if (!$response['ok']) {
        throw new ApiException(
            'Nao foi possivel consultar a licenca no Supabase.',
            normalizeErrorStatus((int) ($response['status'] ?? 500)),
            supabaseErrorDetails($response['data'])
        );
    }

    $rows = $response['data'];
    if (!is_array($rows) || count($rows) === 0 || !is_array($rows[0])) {
        return null;
    }

    return $rows[0];
}

function findUserLicenseByAsaasCustomerId(array $config, string $asaasCustomerId): ?array
{
    if ($asaasCustomerId === '') {
        return null;
    }

    $response = supabaseRequest(
        $config,
        'GET',
        '/rest/v1/user_licenses',
        null,
        [
            'select' => 'user_id,stripe_customer_id,subscription_end,is_active,plan_id,plan_interval',
            'stripe_customer_id' => 'eq.' . $asaasCustomerId,
            'limit' => '1',
        ],
        'service'
    );

    if (!$response['ok']) {
        throw new ApiException(
            'Nao foi possivel consultar a licenca pelo cliente Asaas.',
            normalizeErrorStatus((int) ($response['status'] ?? 500)),
            supabaseErrorDetails($response['data'])
        );
    }

    $rows = $response['data'];
    if (!is_array($rows) || count($rows) === 0 || !is_array($rows[0])) {
        return null;
    }

    return $rows[0];
}

function upsertUserLicense(array $config, array $row): void
{
    $payload = [
        'user_id' => cleanText($row['user_id'] ?? ''),
        'stripe_customer_id' => cleanText($row['stripe_customer_id'] ?? ''),
        'subscription_end' => cleanText($row['subscription_end'] ?? ''),
        'is_active' => (bool) ($row['is_active'] ?? false),
        'plan_id' => cleanText($row['plan_id'] ?? ''),
        'plan_interval' => cleanText($row['plan_interval'] ?? ''),
    ];

    if ($payload['user_id'] === '' || $payload['subscription_end'] === '') {
        throw new ApiException('Dados de licenca insuficientes para sincronizar no Supabase.', 500);
    }

    $response = supabaseRequest(
        $config,
        'POST',
        '/rest/v1/user_licenses',
        $payload,
        ['on_conflict' => 'user_id'],
        'service',
        ['Prefer: resolution=merge-duplicates,return=representation']
    );

    if (!$response['ok']) {
        throw new ApiException(
            'Nao foi possivel atualizar a licenca do usuario no Supabase.',
            normalizeErrorStatus((int) ($response['status'] ?? 500)),
            supabaseErrorDetails($response['data'])
        );
    }
}

function isoTimestampFromDate(string $value): string
{
    try {
        $date = new DateTimeImmutable($value, new DateTimeZone('America/Sao_Paulo'));
    } catch (Throwable) {
        $date = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    }

    return $date->format(DATE_ATOM);
}

function subscriptionIsActive(string $status): bool
{
    return in_array(strtoupper($status), ['ACTIVE', 'RECEIVED', 'CONFIRMED', 'PENDING'], true);
}

function syncUserLicenseFromSubscription(
    array $config,
    string $userId,
    string $asaasCustomerId,
    array $subscription,
    array $plan
): void {
    $status = strtoupper(cleanText($subscription['status'] ?? 'INACTIVE'));
    $nextDueDate = cleanText($subscription['nextDueDate'] ?? '');
    if ($nextDueDate === '') {
        $nextDueDate = nextDueDateStr(1);
    }

    $planId = cleanText($plan['id'] ?? '');
    if ($planId === '') {
        $externalReference = cleanText($subscription['externalReference'] ?? '');
        if ($externalReference !== '') {
            $parts = explode('_', $externalReference);
            $planId = cleanText($parts[0] ?? '');
        }
    }

    $planInterval = strtoupper(cleanText($subscription['cycle'] ?? cleanText($plan['cycle'] ?? '')));
    upsertUserLicense($config, [
        'user_id' => $userId,
        'stripe_customer_id' => $asaasCustomerId,
        'subscription_end' => isoTimestampFromDate($nextDueDate),
        'is_active' => subscriptionIsActive($status),
        'plan_id' => $planId,
        'plan_interval' => $planInterval,
    ]);
}

function markUserLicenseInactive(array $config, string $userId, string $asaasCustomerId): void
{
    $existing = findUserLicenseByUserId($config, $userId);
    upsertUserLicense($config, [
        'user_id' => $userId,
        'stripe_customer_id' => $asaasCustomerId !== ''
            ? $asaasCustomerId
            : cleanText($existing['stripe_customer_id'] ?? ''),
        'subscription_end' => isoTimestampFromDate('now'),
        'is_active' => false,
        'plan_id' => cleanText($existing['plan_id'] ?? ''),
        'plan_interval' => cleanText($existing['plan_interval'] ?? ''),
    ]);
}

function resolveAuthenticatedAsaasCustomerId(array $config, array $auth): string
{
    $fromSession = cleanText($auth['asaasCustomerId'] ?? '');
    if ($fromSession !== '') {
        return $fromSession;
    }

    $license = findUserLicenseByUserId($config, cleanText($auth['userId'] ?? ''));
    $fromLicense = cleanText($license['stripe_customer_id'] ?? '');
    if ($fromLicense !== '') {
        $_SESSION['auth_customer']['asaasCustomerId'] = $fromLicense;
    }

    return $fromLicense;
}

function requireCheckoutSupabaseUserIdForEmail(array $config, string $email): string
{
    initSecureSession();

    $normalizedEmail = strtolower(cleanText($email));
    if ($normalizedEmail === '') {
        throw new ApiException('E-mail invalido para vincular a conta.', 400);
    }

    $pending = $_SESSION['pending_signup'] ?? null;
    if (is_array($pending)) {
        $pendingCreatedAt = (int) ($pending['createdAt'] ?? 0);
        $pendingTtlSeconds = max(60, (int) ($config['pending_signup_ttl_seconds'] ?? 900));

        if ($pendingCreatedAt > 0 && (time() - $pendingCreatedAt) > $pendingTtlSeconds) {
            unset($_SESSION['pending_signup']);
            throw new ApiException('Sessao de cadastro expirada. Crie sua conta novamente para continuar.', 401);
        }

        $pendingUserId = cleanText($pending['userId'] ?? '');
        $pendingEmail = strtolower(cleanText($pending['email'] ?? ''));
        if ($pendingUserId !== '' && $pendingEmail === $normalizedEmail) {
            return $pendingUserId;
        }
    }

    $auth = authenticatedCustomer();
    if (is_array($auth) && strtolower(cleanText($auth['email'] ?? '')) === $normalizedEmail) {
        return cleanText($auth['userId'] ?? '');
    }

    throw new ApiException('Conta nao registrada nesta sessao. Volte e crie sua conta antes de pagar.', 401);
}

function syncCheckoutCustomerContext(
    array $config,
    string $userId,
    array $customer,
    string $asaasCustomerId,
    array $plan = []
): void
{
    syncSupabaseUserMetadata($config, $userId, $customer, $asaasCustomerId);

    $existing = findUserLicenseByUserId($config, $userId);
    $nextPlanId = cleanText($plan['id'] ?? '');
    $nextPlanInterval = strtoupper(cleanText($plan['cycle'] ?? ''));

    upsertUserLicense($config, [
        'user_id' => $userId,
        'stripe_customer_id' => $asaasCustomerId,
        'subscription_end' => cleanText($existing['subscription_end'] ?? isoTimestampFromDate('now')),
        'is_active' => (bool) ($existing['is_active'] ?? false),
        'plan_id' => $nextPlanId !== '' ? $nextPlanId : cleanText($existing['plan_id'] ?? ''),
        'plan_interval' => $nextPlanInterval !== '' ? $nextPlanInterval : cleanText($existing['plan_interval'] ?? ''),
    ]);
}

function syncUserLicenseFromWebhook(array $config, string $eventName, array $payload): void
{
    try {
        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $subscriptionNode = is_array($payload['subscription'] ?? null) ? $payload['subscription'] : [];

        $subscriptionId = cleanText($subscriptionNode['id'] ?? $payment['subscription'] ?? '');
        $asaasCustomerId = cleanText($subscriptionNode['customer'] ?? $payment['customer'] ?? '');

        if ($asaasCustomerId === '') {
            return;
        }

        $license = findUserLicenseByAsaasCustomerId($config, $asaasCustomerId);
        if ($license === null) {
            return;
        }

        $userId = cleanText($license['user_id'] ?? '');
        if ($userId === '') {
            return;
        }

        $eventUpper = strtoupper($eventName);
        $paymentStatus = strtoupper(cleanText($payment['status'] ?? ''));
        $externalReference = cleanText($payment['externalReference'] ?? $subscriptionNode['externalReference'] ?? '');
        $isOnetimeLicense = strtoupper(cleanText($license['plan_interval'] ?? '')) === 'ONETIME';
        $isOnetimeReference = str_contains(strtolower($externalReference), '_onetime_');
        $isOnetimeFlow = $isOnetimeLicense || $isOnetimeReference;

        if ($subscriptionId === '') {
            if ($isOnetimeFlow) {
                $isConfirmedPayment = in_array($paymentStatus, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'], true);
                $withinPixCheckoutWindow = isOnetimePixPaymentWithinCheckoutWindow($externalReference);
                $planId = resolveOnetimePlanIdFromReference($externalReference, cleanText($license['plan_id'] ?? ''));

                if ($isConfirmedPayment && $withinPixCheckoutWindow) {
                    upsertUserLicense($config, [
                        'user_id' => $userId,
                        'stripe_customer_id' => $asaasCustomerId,
                        'subscription_end' => isoTimestampFromDate('+' . max(1, (int) ($config['onetime_access_days'] ?? 30)) . ' days'),
                        'is_active' => true,
                        'plan_id' => $planId,
                        'plan_interval' => 'ONETIME',
                    ]);
                    return;
                }

                if ($isConfirmedPayment && !$withinPixCheckoutWindow) {
                    upsertUserLicense($config, [
                        'user_id' => $userId,
                        'stripe_customer_id' => $asaasCustomerId,
                        'subscription_end' => isoTimestampFromDate('now'),
                        'is_active' => false,
                        'plan_id' => $planId,
                        'plan_interval' => 'ONETIME',
                    ]);
                    return;
                }

                if (
                    str_contains($eventUpper, 'CANCEL')
                    || str_contains($eventUpper, 'DELETE')
                    || str_contains($eventUpper, 'REFUND')
                    || str_contains($eventUpper, 'CHARGEBACK')
                ) {
                    upsertUserLicense($config, [
                        'user_id' => $userId,
                        'stripe_customer_id' => $asaasCustomerId,
                        'subscription_end' => isoTimestampFromDate('now'),
                        'is_active' => false,
                        'plan_id' => $planId,
                        'plan_interval' => 'ONETIME',
                    ]);
                }

                return;
            }

            if (str_contains($eventUpper, 'CANCEL')) {
                markUserLicenseInactive($config, $userId, $asaasCustomerId);
            }
            return;
        }

        $subscriptionResponse = asaasRequest(
            $config,
            'GET',
            '/v3/subscriptions/' . rawurlencode($subscriptionId)
        );

        if (!$subscriptionResponse['ok']) {
            if (str_contains($eventUpper, 'CANCEL') || str_contains($eventUpper, 'DELETE')) {
                markUserLicenseInactive($config, $userId, $asaasCustomerId);
            }
            error_log('[ASAAS WEBHOOK] Falha ao consultar assinatura para sync no Supabase.');
            return;
        }

        if (!is_array($subscriptionResponse['data'])) {
            return;
        }

        syncUserLicenseFromSubscription(
            $config,
            $userId,
            $asaasCustomerId,
            $subscriptionResponse['data'],
            ['id' => cleanText($license['plan_id'] ?? '')]
        );
    } catch (Throwable $error) {
        error_log('[ASAAS WEBHOOK] Erro ao sincronizar assinatura no Supabase: ' . $error->getMessage());
    }
}

function resolveOnetimePlanIdFromReference(string $externalReference, string $fallbackPlanId): string
{
    $externalReference = cleanText($externalReference);
    if ($externalReference === '') {
        return $fallbackPlanId;
    }

    $parts = explode('_', $externalReference);
    $planId = cleanText($parts[0] ?? '');
    return $planId !== '' ? $planId : $fallbackPlanId;
}

function isOnetimePixPaymentWithinCheckoutWindow(string $externalReference): bool
{
    $externalReference = strtolower(cleanText($externalReference));
    if ($externalReference === '') {
        return true;
    }

    if (!str_contains($externalReference, '_onetime_pix_')) {
        return true;
    }

    $parts = explode('_', $externalReference);
    $lastPart = cleanText($parts[count($parts) - 1] ?? '');
    if ($lastPart === '' || preg_match('/^\d{9,}$/', $lastPart) !== 1) {
        return false;
    }

    $issuedAt = (int) $lastPart;
    if ($issuedAt <= 0) {
        return false;
    }

    return (time() - $issuedAt) <= 900;
}

function throwAsaasException(string $message, array $response): never
{
    throw new ApiException(
        $message,
        normalizeErrorStatus((int) ($response['status'] ?? 400)),
        asaasErrorList($response['data'] ?? [])
    );
}

function asaasErrorList(array $data): array
{
    $errors = $data['errors'] ?? [];
    if (!is_array($errors)) {
        return [];
    }

    $normalized = [];
    foreach ($errors as $error) {
        if (!is_array($error)) {
            continue;
        }
        $normalized[] = [
            'code' => cleanText($error['code'] ?? ''),
            'description' => cleanText($error['description'] ?? ''),
        ];
    }

    return $normalized;
}

function firstAsaasErrorMessage(array $data): string
{
    $errors = asaasErrorList($data);
    if (count($errors) === 0) {
        return '';
    }

    $first = $errors[0];
    return $first['description'] !== '' ? $first['description'] : ($first['code'] ?? '');
}

function isDuplicateCustomerError(array $data): bool
{
    foreach (asaasErrorList($data) as $error) {
        $code = strtolower((string) ($error['code'] ?? ''));
        $description = strtolower((string) ($error['description'] ?? ''));

        if (str_contains($code, 'cpfcnpjalreadyinuse')) {
            return true;
        }

        if (str_contains($description, 'already in use') || str_contains($description, 'ja cadastrado')) {
            return true;
        }
    }

    return false;
}

function normalizeErrorStatus(int $status): int
{
    if ($status >= 400 && $status <= 599) {
        return $status;
    }
    return 400;
}

function ensureApiKeyConfigured(array $config): void
{
    if (cleanText($config['asaas_api_key']) === '') {
        throw new ApiException('ASAAS_API_KEY nao configurada.', 500);
    }
}

function normalizeRecurringCycle(string $cycle): string
{
    $cycle = strtoupper(trim($cycle));
    $allowed = [
        'WEEKLY',
        'BIWEEKLY',
        'MONTHLY',
        'BIMONTHLY',
        'QUARTERLY',
        'SEMIANNUALLY',
        'YEARLY',
    ];

    if (in_array($cycle, $allowed, true)) {
        return $cycle;
    }

    return 'YEARLY';
}

function isOnetimeCycle(string $cycle): bool
{
    return strtoupper(trim($cycle)) === 'ONETIME';
}

function nextDueDateStr(int $days): string
{
    $days = max(0, $days);
    $date = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
    if ($days > 0) {
        $date = $date->modify('+' . $days . ' day');
    }
    return $date->format('Y-m-d');
}

function resolveMaxJsonBodyBytes(): int
{
    return normalizePositiveInt(envValue('MAX_JSON_BODY_BYTES', '1048576'), 1048576);
}

function readJsonBody(): array
{
    $maxBytes = resolveMaxJsonBodyBytes();
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && $contentLength > $maxBytes) {
        throw new ApiException('Payload JSON excede o limite permitido.', 413);
    }

    $raw = file_get_contents('php://input', false, null, 0, $maxBytes + 1);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    if (strlen($raw) > $maxBytes) {
        throw new ApiException('Payload JSON excede o limite permitido.', 413);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new ApiException('JSON invalido no corpo da requisicao.', 400);
    }

    return $decoded;
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function buildConfig(string $projectRoot): array
{
    $publicBaseUrl = envValue('PUBLIC_BASE_URL', detectPublicBaseUrl());
    $publicBaseUrl = rtrim((string) $publicBaseUrl, '/');

    return [
        'project_root' => $projectRoot,
        'asaas_env' => strtolower((string) envValue('ASAAS_ENV', 'sandbox')),
        'asaas_api_key' => (string) envValue('ASAAS_API_KEY', ''),
        'public_base_url' => $publicBaseUrl,
        'annual_plan_name' => (string) envValue('ANNUAL_PLAN_NAME', 'Plano Anual DeFast'),
        'annual_plan_description' => (string) envValue('ANNUAL_PLAN_DESCRIPTION', 'Assinatura anual com acesso completo ao DeFast.'),
        'annual_plan_price' => normalizePositiveFloat(envValue('ANNUAL_PLAN_PRICE', '997'), 997),
        'asaas_due_date_limit_days' => normalizePositiveInt(envValue('ASAAS_DUE_DATE_LIMIT_DAYS', '3'), 3),
        'onetime_access_days' => normalizePositiveInt(envValue('ONETIME_ACCESS_DAYS', '30'), 30),
        'asaas_webhook_token' => (string) envValue('ASAAS_WEBHOOK_TOKEN', ''),
        'asaas_timeout_seconds' => normalizePositiveInt(envValue('ASAAS_TIMEOUT_SECONDS', '60'), 60),
        'card_processing_mode' => strtoupper((string) envValue('CARD_PROCESSING_MODE', 'INLINE_DIRECT')),
        'checkout_success_url' => (string) envValue('CHECKOUT_SUCCESS_URL', $publicBaseUrl . '/checkout?status=success'),
        'checkout_cancel_url' => (string) envValue('CHECKOUT_CANCEL_URL', $publicBaseUrl . '/checkout?status=cancel'),
        'checkout_expired_url' => (string) envValue('CHECKOUT_EXPIRED_URL', $publicBaseUrl . '/checkout?status=expired'),
        'supabase_url' => rtrim((string) envValue('SUPABASE_URL', ''), '/'),
        'supabase_anon_key' => (string) envValue('SUPABASE_PUBLISHABLE_KEY', envValue('SUPABASE_ANON_KEY', '')),
        'supabase_service_role_key' => (string) envValue('SUPABASE_SERVICE_ROLE_KEY', ''),
        'supabase_service_role_key_file' => (string) envValue('SUPABASE_SERVICE_ROLE_KEY_FILE', ''),
        'pending_signup_ttl_seconds' => normalizePositiveInt(envValue('PENDING_SIGNUP_TTL_SECONDS', '900'), 900),
        'api_expose_error_details' => (string) envValue('API_EXPOSE_ERROR_DETAILS', '0'),
        'free_trial_days' => normalizePositiveInt(envValue('FREE_TRIAL_DAYS', '30'), 30),
        'asaas_api_base' => strtolower((string) envValue('ASAAS_ENV', 'sandbox')) === 'production'
            ? 'https://api.asaas.com'
            : 'https://api-sandbox.asaas.com',
        'mp_access_token' => (string) envValue('MP_ACCESS_TOKEN', ''),
        'mp_public_key' => (string) envValue('MP_PUBLIC_KEY', ''),
        'mp_webhook_secret' => (string) envValue('MP_WEBHOOK_SECRET', ''),
    ];
}

function detectPublicBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function setCorsHeaders(array $config): void
{
    $origin = cleanText($_SERVER['HTTP_ORIGIN'] ?? '');
    $allowedOrigin = originFromUrl((string) $config['public_base_url']);

    if ($origin !== '' && $allowedOrigin !== '' && strcasecmp($origin, $allowedOrigin) === 0) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Headers: Content-Type, Idempotency-Key, X-Idempotency-Key, X-CSRF-Token, asaas-access-token, access_token, x-signature, x-request-id');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Max-Age: 86400');
}

function setApiSecurityHeaders(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

function enforceRateLimit(string $scope, int $maxRequests, int $windowSeconds): void
{
    $maxRequests = max(1, $maxRequests);
    $windowSeconds = max(1, $windowSeconds);

    $ip = getClientIp();
    $now = time();
    $windowStart = $now - $windowSeconds;

    $rateLimitDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'defast_rate_limit';
    if (!is_dir($rateLimitDir) && !@mkdir($rateLimitDir, 0700, true) && !is_dir($rateLimitDir)) {
        error_log('[rate-limit] Falha ao criar diretorio de rate limit: ' . $rateLimitDir);
        return;
    }

    $rateLimitFile = $rateLimitDir . DIRECTORY_SEPARATOR . hash('sha256', $scope . '|' . $ip) . '.json';
    $handle = fopen($rateLimitFile, 'c+');
    if ($handle === false) {
        error_log('[rate-limit] Falha ao abrir arquivo de rate limit: ' . $rateLimitFile);
        return;
    }

    try {
        if (!flock($handle, LOCK_EX)) {
            error_log('[rate-limit] Falha ao obter lock no arquivo de rate limit: ' . $rateLimitFile);
            return;
        }

        $raw = stream_get_contents($handle);
        $history = is_string($raw) ? json_decode($raw, true) : [];
        if (!is_array($history)) {
            $history = [];
        }

        $recent = [];
        foreach ($history as $timestamp) {
            $ts = (int) $timestamp;
            if ($ts >= $windowStart && $ts <= $now) {
                $recent[] = $ts;
            }
        }

        if (count($recent) >= $maxRequests) {
            throw new ApiException('Muitas tentativas. Aguarde alguns minutos e tente novamente.', 429);
        }

        $recent[] = $now;

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($recent, JSON_UNESCAPED_SLASHES));
        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);

        if (mt_rand(1, 50) === 1 && isset($rateLimitDir)) {
            $cutoff = $now - ($windowSeconds * 2);
            foreach (glob($rateLimitDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $staleFile) {
                if (is_file($staleFile) && filemtime($staleFile) < $cutoff) {
                    @unlink($staleFile);
                }
            }
        }
    }
}

function beginIdempotency(string $scope, array $requestBody, string $userContext = ''): array
{
    $key = cleanText(getHeaderValue('Idempotency-Key') ?? getHeaderValue('X-Idempotency-Key') ?? '');
    if ($key === '') {
        throw new ApiException('Cabecalho Idempotency-Key obrigatorio.', 400);
    }

    if (strlen($key) < 8 || strlen($key) > 150) {
        throw new ApiException('Cabecalho Idempotency-Key invalido.', 400);
    }

    $requestJson = json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $requestHash = hash('sha256', $requestJson !== false ? $requestJson : '{}');

    $idempotencyDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'defast_idempotency';
    if (!is_dir($idempotencyDir) && !@mkdir($idempotencyDir, 0700, true) && !is_dir($idempotencyDir)) {
        throw new ApiException('Falha ao inicializar idempotencia.', 500);
    }

    $filePath = $idempotencyDir . DIRECTORY_SEPARATOR . hash('sha256', $scope . '|' . $userContext . '|' . $key) . '.json';
    $handle = fopen($filePath, 'c+');
    if ($handle === false) {
        throw new ApiException('Falha ao inicializar idempotencia.', 500);
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        throw new ApiException('Falha ao inicializar idempotencia.', 500);
    }

    $raw = stream_get_contents($handle);
    $cached = is_string($raw) ? json_decode($raw, true) : null;
    $now = time();
    $ttlSeconds = 86400;

    if (is_array($cached) && isset($cached['createdAt'], $cached['requestHash'], $cached['status'], $cached['payload'])) {
        $createdAt = (int) $cached['createdAt'];
        if (($now - $createdAt) <= $ttlSeconds) {
            if ((string) $cached['requestHash'] !== $requestHash) {
                releaseIdempotency(['handle' => $handle]);
                throw new ApiException('Idempotency-Key reutilizado com payload diferente.', 409);
            }

            $payload = is_array($cached['payload']) ? $cached['payload'] : [];
            $status = (int) $cached['status'];
            if ($status < 200 || $status > 599) {
                $status = 200;
            }

            releaseIdempotency(['handle' => $handle]);
            jsonResponse($payload, $status);
        }
    }

    return [
        'handle' => $handle,
        'requestHash' => $requestHash,
        'createdAt' => $now,
    ];
}

function commitIdempotency(array $context, array $payload, int $status): void
{
    $handle = $context['handle'] ?? null;
    if (!is_resource($handle)) {
        return;
    }

    $record = [
        'requestHash' => (string) ($context['requestHash'] ?? ''),
        'createdAt' => (int) ($context['createdAt'] ?? time()),
        'status' => $status,
        'payload' => $payload,
    ];

    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fflush($handle);

    releaseIdempotency($context);
}

function releaseIdempotency(array $context): void
{
    $handle = $context['handle'] ?? null;
    if (!is_resource($handle)) {
        return;
    }

    flock($handle, LOCK_UN);
    fclose($handle);
}

function originFromUrl(string $url): string
{
    $parts = parse_url($url);
    if (!is_array($parts) || !isset($parts['scheme']) || !isset($parts['host'])) {
        return '';
    }

    $origin = $parts['scheme'] . '://' . $parts['host'];
    if (isset($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }
    return $origin;
}

function getHeaderValue(string $headerName): ?string
{
    $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
    if (isset($_SERVER[$normalized])) {
        return cleanText((string) $_SERVER[$normalized]);
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $name => $value) {
                if (strcasecmp((string) $name, $headerName) === 0) {
                    return cleanText((string) $value);
                }
            }
        }
    }

    return null;
}

function enforceHttpsForCardCapture(): void
{
    if (isSecureRequest()) {
        return;
    }

    $host = strtolower(cleanText($_SERVER['HTTP_HOST'] ?? ''));
    if (str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1')) {
        return;
    }

    throw new ApiException(
        'Pagamento com cartao exige SSL/HTTPS no seu dominio. Ative HTTPS para continuar.',
        400
    );
}

function getClientIp(): string
{
    $candidates = [$_SERVER['REMOTE_ADDR'] ?? ''];
    if (shouldTrustProxyHeaders()) {
        array_unshift(
            $candidates,
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''
        );
    }

    foreach ($candidates as $candidate) {
        if (!is_string($candidate) || trim($candidate) === '') {
            continue;
        }

        $first = trim(explode(',', $candidate)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP) !== false) {
            return $first;
        }
    }

    return '127.0.0.1';
}

function normalizePath(string $path): string
{
    $path = preg_replace('#/+#', '/', $path);
    if (!is_string($path) || $path === '') {
        return '/';
    }

    if ($path !== '/' && str_ends_with($path, '/')) {
        return rtrim($path, '/');
    }

    return $path;
}

function loadEnvFile(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '') {
            continue;
        }

        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

function envValue(string $key, string $fallback): string
{
    $fromGetEnv = getenv($key);
    if ($fromGetEnv !== false && $fromGetEnv !== '') {
        return (string) $fromGetEnv;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    return $fallback;
}

function cleanText(mixed $value): string
{
    return trim((string) $value);
}

function onlyDigits(mixed $value): string
{
    return preg_replace('/\D+/', '', (string) $value) ?? '';
}

function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function normalizePositiveInt(mixed $value, int $fallback): int
{
    $intValue = filter_var($value, FILTER_VALIDATE_INT);
    if ($intValue === false || $intValue < 1) {
        return $fallback;
    }
    return $intValue;
}

function normalizePositiveFloat(mixed $value, float $fallback): float
{
    if (is_string($value)) {
        $value = str_replace(',', '.', $value);
    }

    $floatValue = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($floatValue === false || !is_finite((float) $floatValue) || (float) $floatValue <= 0) {
        return $fallback;
    }

    return (float) $floatValue;
}

function isValidCpfCnpj(string $value): bool
{
    $digits = onlyDigits($value);
    if (strlen($digits) === 11) {
        return isValidCpf($digits);
    }

    if (strlen($digits) === 14) {
        return isValidCnpj($digits);
    }

    return false;
}

function isValidCpf(string $cpf): bool
{
    if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
        return false;
    }

    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
        $sum += ((int) $cpf[$i]) * (10 - $i);
    }

    $rest = ($sum * 10) % 11;
    if ($rest === 10) {
        $rest = 0;
    }

    if ($rest !== (int) $cpf[9]) {
        return false;
    }

    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $sum += ((int) $cpf[$i]) * (11 - $i);
    }

    $rest = ($sum * 10) % 11;
    if ($rest === 10) {
        $rest = 0;
    }

    return $rest === (int) $cpf[10];
}

function isValidCnpj(string $cnpj): bool
{
    if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
        return false;
    }

    $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    $digit1 = cnpjDigit($cnpj, $weights1);
    $digit2 = cnpjDigit($cnpj, $weights2);

    return $digit1 === (int) $cnpj[12] && $digit2 === (int) $cnpj[13];
}

function cnpjDigit(string $cnpj, array $weights): int
{
    $sum = 0;
    foreach ($weights as $index => $weight) {
        $sum += ((int) $cnpj[$index]) * $weight;
    }

    $rest = $sum % 11;
    return $rest < 2 ? 0 : 11 - $rest;
}

// ============================================================
// Mercado Pago — helpers e handlers
// ============================================================

function ensureMpConfigured(array $config): void
{
    if (cleanText($config['mp_access_token'] ?? '') === '') {
        throw new ApiException('MP_ACCESS_TOKEN nao configurado.', 500);
    }
}

function mpRequest(
    array $config,
    string $method,
    string $path,
    ?array $body = null,
    array $query = []
): array {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 500, 'data' => ['message' => 'Extensao cURL nao habilitada.']];
    }

    $url = 'https://api.mercadopago.com' . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'status' => 500, 'data' => ['message' => 'Falha ao inicializar cURL.']];
    }

    $headers = [
        'Authorization: Bearer ' . $config['mp_access_token'],
        'Content-Type: application/json',
        'User-Agent: defast-site/1.0',
        'X-Idempotency-Key: ' . bin2hex(random_bytes(16)),
    ];

    $payloadJson = null;
    if ($body !== null) {
        $payloadJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === false) {
            $payloadJson = '{}';
        }
    }

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => strtoupper($method),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 60,
    ];
    curl_setopt_array($ch, $opts);

    if ($payloadJson !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    }

    $fn          = 'curl_exec';
    $rawResponse = $fn($ch);
    $curlError   = curl_error($ch);
    $status      = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($rawResponse === false) {
        return ['ok' => false, 'status' => 503, 'data' => ['message' => $curlError ?: 'Falha de conexao com o Mercado Pago.']];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        $decoded = ['message' => 'Resposta invalida do Mercado Pago.'];
    }

    return [
        'ok'     => $status >= 200 && $status < 300,
        'status' => $status,
        'data'   => $decoded,
    ];
}

function handleMpPublicKey(array $config): void
{
    enforceRateLimit('mp_public_key', 60, 60);
    $publicKey = cleanText($config['mp_public_key'] ?? '');
    if ($publicKey === '') {
        throw new ApiException('MP_PUBLIC_KEY nao configurado.', 500);
    }
    jsonResponse(['publicKey' => $publicKey]);
}

function handleMpSubscription(array $config, array $plansContext, array $body): void
{
    ensureMpConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('mp_subscription', 10, 300);
    initSecureSession();
    $auth = requireAuthenticatedCustomer();
    enforceCsrfToken();

    $planId = strtolower(cleanText($body['planId'] ?? ''));
    $plan   = findPlanById($plansContext['activePlans'], $planId);

    if ($plan === null || ($plan['active'] ?? true) === false) {
        throw new ApiException('Plano nao encontrado ou inativo.', 404);
    }

    if (isOnetimeCycle((string) ($plan['cycle'] ?? ''))) {
        throw new ApiException('Use /api/mp/payment para planos de pagamento unico.', 400);
    }

    $frequencyType = strtoupper($plan['cycle'] ?? 'MONTHLY') === 'YEARLY' ? 'years' : 'months';
    $backUrl       = rtrim($config['public_base_url'], '/') . '/checkout?status=mp_success&plan=' . rawurlencode($plan['id']);

    $response = mpRequest($config, 'POST', '/preapproval', [
        'reason'             => cleanText($plan['name'] ?? 'Assinatura DeFast'),
        'external_reference' => $auth['userId'],
        'payer_email'        => $auth['email'],
        'auto_recurring'     => [
            'frequency'          => 1,
            'frequency_type'     => $frequencyType,
            'transaction_amount' => (float) $plan['price'],
            'currency_id'        => 'BRL',
        ],
        'back_url' => $backUrl,
        'status'   => 'pending',
    ]);

    if (!$response['ok']) {
        $msg = cleanText($response['data']['message'] ?? 'Nao foi possivel criar a assinatura no Mercado Pago.');
        throw new ApiException($msg, 502);
    }

    $preapprovalId = cleanText($response['data']['id'] ?? '');
    $initPoint     = cleanText($response['data']['init_point'] ?? '');

    if ($initPoint === '') {
        throw new ApiException('Mercado Pago nao retornou URL de autorizacao.', 502);
    }

    try {
        upsertUserLicense($config, [
            'user_id'            => $auth['userId'],
            'stripe_customer_id' => $preapprovalId,
            'plan_id'            => $plan['id'],
            'plan_interval'      => strtoupper($plan['cycle'] ?? 'MONTHLY'),
            'is_active'          => false,
        ]);
    } catch (Throwable $e) {
        error_log('[mp/subscription] Falha ao registrar preapproval no Supabase: ' . $e->getMessage());
    }

    jsonResponse([
        'initPoint'     => $initPoint,
        'preapprovalId' => $preapprovalId,
        'csrfToken'     => ensureCsrfToken(),
    ]);
}

function handleMpPayment(array $config, array $plansContext, array $body): void
{
    ensureMpConfigured($config);
    ensureSupabaseServiceConfigured($config);
    enforceRateLimit('mp_payment', 10, 300);
    initSecureSession();
    $auth = requireAuthenticatedCustomer();
    enforceCsrfToken();

    $planId   = strtolower(cleanText($body['planId'] ?? ''));
    $plan     = findPlanById($plansContext['activePlans'], $planId);
    $formData = $body['formData'] ?? null;

    if ($plan === null || ($plan['active'] ?? true) === false) {
        throw new ApiException('Plano nao encontrado ou inativo.', 404);
    }

    if (!is_array($formData) || empty($formData)) {
        throw new ApiException('Dados de pagamento ausentes.', 400);
    }

    $formData['external_reference'] = $auth['userId'] . '|' . $plan['id'];
    $formData['metadata'] = [
        'user_id'    => $auth['userId'],
        'plan_id'    => $plan['id'],
        'user_email' => $auth['email'],
    ];

    $response = mpRequest($config, 'POST', '/v1/payments', $formData);

    if (!$response['ok']) {
        $errorMsg = cleanText(
            $response['data']['message']
                ?? $response['data']['error']
                ?? 'Erro ao processar pagamento.'
        );
        throw new ApiException($errorMsg, 422);
    }

    $status    = cleanText($response['data']['status'] ?? '');
    $paymentId = cleanText((string) ($response['data']['id'] ?? ''));

    if ($status === 'approved') {
        $onetimeDays = max(1, (int) ($config['onetime_access_days'] ?? 30));
        try {
            upsertUserLicense($config, [
                'user_id'            => $auth['userId'],
                'stripe_customer_id' => $paymentId,
                'plan_id'            => $plan['id'],
                'plan_interval'      => 'ONETIME',
                'is_active'          => true,
                'subscription_end'   => isoTimestampFromDate('+' . $onetimeDays . ' days'),
            ]);
        } catch (Throwable $e) {
            error_log('[mp/payment] Falha ao ativar licenca no Supabase: ' . $e->getMessage());
        }
    }

    jsonResponse([
        'status'    => $status,
        'paymentId' => $paymentId,
        'approved'  => $status === 'approved',
        'message'   => $status === 'approved'
            ? 'Pagamento aprovado! Seu acesso foi ativado.'
            : 'Pagamento em processamento. Voce receberá confirmacao em breve.',
        'csrfToken' => ensureCsrfToken(),
    ]);
}

function handleMpWebhook(array $config, array $plansContext): void
{
    ensureMpConfigured($config);
    ensureSupabaseServiceConfigured($config);

    $payload = readJsonBody();
    $type    = cleanText($payload['type'] ?? $payload['action'] ?? '');
    $dataId  = cleanText((string) ($payload['data']['id'] ?? ''));

    error_log(sprintf('[MP WEBHOOK] type=%s id=%s', $type, $dataId));

    if ($dataId === '') {
        jsonResponse(['received' => true]);
        return;
    }

    if (str_contains($type, 'payment')) {
        $payment = mpRequest($config, 'GET', '/v1/payments/' . rawurlencode($dataId));

        if ($payment['ok'] && is_array($payment['data'])) {
            $paymentData = $payment['data'];
            $status      = cleanText($paymentData['status'] ?? '');
            $externalRef = cleanText($paymentData['external_reference'] ?? '');
            $parts       = explode('|', $externalRef);
            $userId      = cleanText($parts[0] ?? '');
            $planId      = cleanText($parts[1] ?? '');

            if ($userId !== '' && $status === 'approved') {
                $onetimeDays = max(1, (int) ($config['onetime_access_days'] ?? 30));
                try {
                    upsertUserLicense($config, [
                        'user_id'            => $userId,
                        'stripe_customer_id' => $dataId,
                        'plan_id'            => $planId ?: 'onetime',
                        'plan_interval'      => 'ONETIME',
                        'is_active'          => true,
                        'subscription_end'   => isoTimestampFromDate('+' . $onetimeDays . ' days'),
                    ]);
                } catch (Throwable $e) {
                    error_log('[MP WEBHOOK] Falha ao ativar licenca por pagamento: ' . $e->getMessage());
                }
            }
        }
    } elseif (str_contains($type, 'preapproval') || str_contains($type, 'subscription')) {
        $preapproval = mpRequest($config, 'GET', '/preapproval/' . rawurlencode($dataId));

        if ($preapproval['ok'] && is_array($preapproval['data'])) {
            $data          = $preapproval['data'];
            $status        = cleanText($data['status'] ?? '');
            $userId        = cleanText($data['external_reference'] ?? '');
            $autoRecurring = is_array($data['auto_recurring'] ?? null) ? $data['auto_recurring'] : [];
            $freqType      = strtolower(cleanText($autoRecurring['frequency_type'] ?? 'months'));
            $interval      = $freqType === 'years' ? 'YEARLY' : 'MONTHLY';
            $daysToAdd     = $interval === 'YEARLY' ? 365 : 30;

            if ($userId !== '') {
                if (in_array($status, ['authorized', 'active'], true)) {
                    try {
                        upsertUserLicense($config, [
                            'user_id'            => $userId,
                            'stripe_customer_id' => $dataId,
                            'plan_id'            => $interval === 'YEARLY' ? 'annual' : 'monthly',
                            'plan_interval'      => $interval,
                            'is_active'          => true,
                            'subscription_end'   => isoTimestampFromDate('+' . $daysToAdd . ' days'),
                        ]);
                    } catch (Throwable $e) {
                        error_log('[MP WEBHOOK] Falha ao ativar assinatura no Supabase: ' . $e->getMessage());
                    }
                } elseif (in_array($status, ['cancelled', 'expired', 'paused'], true)) {
                    try {
                        markUserLicenseInactive($config, $userId, $dataId);
                    } catch (Throwable $e) {
                        error_log('[MP WEBHOOK] Falha ao desativar assinatura no Supabase: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    jsonResponse(['received' => true]);
}

final class ApiException extends RuntimeException
{
    public int $status;
    public array $details;

    public function __construct(string $message, int $status = 400, array $details = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->details = $details;
    }
}
