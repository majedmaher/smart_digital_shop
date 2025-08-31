<?php
// app/Services/ZohoBooksService.php
namespace App\Services;

use App\Enum\PaymentCurrencyEnum;
use App\Models\ZohoToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ZohoBooksService
{
    protected string $apiBase;
    protected string $accountsUrl;
    protected string $orgId;
    protected $taxCache = [];

    public function __construct()
    {
        $this->apiBase     = rtrim(config('services.zoho.api_base'), '/') . '/books/v3';
        $this->accountsUrl = rtrim(config('services.zoho.accounts_url'), '/');
        $this->orgId       = (string) config('services.zoho.org_id');
    }

    /** تأمين توكن صالح (تجديد إذا لزم) */
    protected function getAccessToken(): string
    {
        $token = ZohoToken::first();
        abort_unless($token?->access_token, 500, 'Zoho not connected');

        if (now()->addMinute()->greaterThan($token->expires_at) && $token->refresh_token) {
            $resp = Http::asForm()->post($this->accountsUrl . '/oauth/v2/token', [
                'grant_type'    => 'refresh_token',
                'client_id'     => config('services.zoho.client_id'),
                'client_secret' => config('services.zoho.client_secret'),
                'refresh_token' => $token->refresh_token,
            ])->json();

            if (!isset($resp['access_token'])) {
                abort(500, 'Unable to refresh Zoho token');
            }

            $token->update([
                'access_token' => $resp['access_token'],
                'expires_at'   => now()->addSeconds($resp['expires_in'] ?? 3300),
                'scope'        => $resp['scope'] ?? $token->scope,
            ]);
        }

        return $token->access_token;
    }

    /** استدعاء عام مع JSON headers واضحة */
    protected function call(string $method, string $path, array $query = [], array $json = [])
    {
        $access = $this->getAccessToken();
        $url    = $this->apiBase . '/' . ltrim($path, '/');

        $http = Http::withHeaders([
            'Authorization' => 'Zoho-oauthtoken ' . $access,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json; charset=UTF-8',
        ])->withQueryParameters(['organization_id' => $this->orgId] + $query);

        // Logging مُفيد للتتبع
        if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
            $res = $http->{$method}($url, $json);
        } else {
            $res = $http->{$method}($url);
        }

        $data = $res->json();

        if ($res->failed()) {
            // حاول نطلع رسالة مفهومة
            $body = $res->body();
            throw new \Exception("Zoho API Error: " . $body);
        }

        return $data;
    }

    /** احصل على المنظمات (اختياري) */
    public function listOrganizations(): array
    {
        return $this->call('get', 'organizations');
    }

    /** ========== Sanitizers ========== */

    /** تنظيف اسم Contact */
    protected function sanitizeZohoContactName(?string $name): string
    {
        $name = trim((string) $name);
        // اسم contact: نسمح بحروف (بما فيها العربية)، أرقام، فراغ، و -_.() فقط
        $name = preg_replace('/[^\p{L}\p{N}\s\-\_\.\(\)]+/u', '', $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        $name = trim($name);

        if ($name === '' || mb_strlen($name) < 2) {
            $name = 'Customer-' . substr(uniqid('', true), -8);
        }

        // حد آمن للطول (100)
        if (mb_strlen($name) > 100) {
            $name = mb_substr($name, 0, 100);
        }

        return $name;
    }

    /** تنظيف اسم Item */
    protected function sanitizeZohoItemName(?string $name): string
    {
        $name = trim((string) $name);
        // نسمح بحروف (بما فيها العربية)، أرقام، فراغ، و -_ .() & + فقط
        $name = preg_replace('/[^\p{L}\p{N}\s\-\_\.\(\)\&\+]+/u', '', $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        $name = trim($name);

        if ($name === '' || mb_strlen($name) < 2) {
            $name = 'Item-' . substr(uniqid('', true), -8);
        }

        // حد آمن للطول (100)
        if (mb_strlen($name) > 100) {
            $name = mb_substr($name, 0, 100);
        }

        return $name;
    }

    /** تنظيف SKU */
    protected function sanitizeSku(?string $sku): ?string
    {
        if ($sku === null) return null;
        $sku = trim((string) $sku);
        // نسمح بحروف/أرقام و -_. فقط
        $sku = preg_replace('/[^A-Za-z0-9\-\_\.]+/', '-', $sku);
        $sku = trim($sku, '-_.');

        if ($sku === '') return null;

        if (strlen($sku) > 50) {
            $sku = substr($sku, 0, 50);
        }

        return $sku;
    }

    /** ========== Contacts ========== */

    /** جلب أو إنشاء Contact بالـ email إن وجد */
    // public function getOrCreateContact(array $customer): array
    // {
    //     // تنظيف الاسم
    //     $baseName = $this->sanitizeZohoContactName(Arr::get($customer, 'name'));
    //     if (empty($baseName)) {
    //         $baseName = 'Customer';
    //     }

    //     // هنا نضمن uniqueness بإضافة ID
    //     $contactName = $baseName . '-' . substr(uniqid('', true), -6);
    //     $companyName = 'Customer-' . substr(uniqid('', true), -8);

    //     $payload = [
    //         'contact_name' => $contactName,
    //         'company_name' => $companyName,
    //         'email'        => $email,
    //         'phone'        => Arr::get($customer, 'phone') ?: null,
    //     ];

    //     $res = $this->call('post', 'contacts', [], $payload);

    //     if (!empty($res['code'])) {
    //         throw new \Exception('Failed to create contact: ' . json_encode($res));
    //     }

    //     if (!empty($res['contact'])) return $res['contact'];

    //     throw new \Exception('Failed to create contact: ' . json_encode($res));
    // }

    /** جلب أو إنشاء Contact بالـ email إن وجد */
    public function getOrCreateContact(array $customer): array
    {
        $email = Arr::get($customer, 'email');
        $name = $this->sanitizeZohoContactName(Arr::get($customer, 'name') ?? 'Customer');

        // 1. البحث بالبريد الإلكتروني أولًا (الأهم)
        if ($email) {
            try {
                $found = $this->call('get', 'contacts', ['email' => $email]);
                if (!empty($found['contacts'][0])) {
                    return $found['contacts'][0];
                }
            } catch (\Exception $e) {
                // لو البحث فشل، نكمل لإنشاء عميل جديد
            }
        }

        // 2. إذا ما وجد، نبحث بالاسم (استثنائيًا)
        try {
            $foundByName = $this->call('get', 'contacts', ['search_text' => $name]);
            foreach ($foundByName['contacts'] ?? [] as $contact) {
                // إذا كان له نفس البريد أو نفس الاسم، نستخدمه
                if ($contact['email'] === $email || !$email) {
                    return $contact;
                }
            }
        } catch (\Exception $e) {
            // تجاهل خطأ البحث
        }

        // 3. إذا ما وجد، ننشئ عميل جديد
        // نضيف لاحقة فريدة للتأكد من التفرد
        $uniqueSuffix = substr(md5(uniqid('', true) . $email), -6);
        $contactName = $name . '-' . $uniqueSuffix;

        $payload = [
            'contact_name' => $contactName,
            'company_name' => 'Customer-' . $uniqueSuffix,
            'email'        => $email,
            'phone'        => Arr::get($customer, 'phone') ?: null,
        ];

        $res = $this->call('post', 'contacts', [], $payload);

        if (!empty($res['contact'])) {
            return $res['contact'];
        }

        throw new \Exception('Failed to create contact: ' . json_encode($res));
    }

    /** ========== Items ========== */

    /** جلب أو إنشاء Item بالـ SKU أو الاسم */
    public function getOrCreateItem(array $product): array
    {
        $sku     = $this->sanitizeSku($product['sku'] ?? null);
        $name    = $this->sanitizeZohoItemName($product['name']);
        $price   = (float) $product['price'];
        $taxRate = (float) ($product['tax_rate'] ?? 0);

        // 1. ابحث بالـ SKU أولًا
        if ($sku) {
            $found = $this->call('get', 'items', ['search_text' => $sku]);
            foreach ($found['items'] ?? [] as $item) {
                if (($item['sku'] ?? '') === $sku) {
                    return $item;
                }
            }
        }

        // 2. إذا ما وجد بالـ SKU، ابحث بالاسم
        if ($name) {
            $found = $this->call('get', 'items', ['search_text' => $name]);
            foreach ($found['items'] ?? [] as $item) {
                // إذا كان الاسم مطابقًا (وإن لم يكن له SKU)
                if (($item['name'] ?? '') === $name) {
                    return $item;
                }
            }
        }

        // جلب أو إنشاء الضريبة
        $taxId = $this->ensureTaxExistsByRate($taxRate);

        // إنشاء المنتج مع tax_id
        $payload = [
            'name' => $name,
            'rate' => $price,
        ];
        if ($sku) $payload['sku'] = $sku;
        if ($taxId) $payload['tax_id'] = $taxId;

        try {
            $res = $this->call('post', 'items', [], $payload);
            if (!empty($res['item'])) return $res['item'];
        } catch (\Exception $e) {
            // إذا فشل بسبب تكرار الاسم
            if (str_contains($e->getMessage(), '1001') || str_contains($e->getMessage(), 'موجود بالفعل')) {
                // نعيد البحث بعد الفشل
                $found = $this->call('get', 'items', ['search_text' => $name]);
                foreach ($found['items'] ?? [] as $item) {
                    if ($item['name'] === $name) {
                        return $item;
                    }
                }
            }
            throw $e;
        }

        throw new \Exception('فشل إنشاء الصنف: ' . json_encode($res));
    }

    /** ========== Invoices & Payments ========== */
    public function createInvoice(array $contact, array $lineItems, array $meta = []): array
    {
        $payload = [
            'customer_id'   => $contact['contact_id'],
            'date'          => now()->format('Y-m-d'),
            'currency_code' => PaymentCurrencyEnum::DEFAULT_CURRENCY->value,
            'line_items'    => array_map(function ($li) {
                $row = [
                    'item_id'  => $li['item_id'],
                    'rate'     => (float) $li['rate'],
                    'quantity' => (float) $li['quantity'],
                ];

                if (!empty($li['tax_id'])) {
                    $row['tax_id'] = $li['tax_id'];
                }

                return $row; // 👈 هذا كان ناقص
            }, $lineItems),
        ];

        // ✅ احفظ الكوبون في الملاحظات (أو بدّلها إلى custom_fields لو عامل حقل مخصص)
        if (!empty($meta['coupon'])) {
            $payload['notes'] = trim(($meta['notes'] ?? 'Thanks for your business.') . "\nCoupon: " . $meta['coupon']);
        }

        $res = $this->call('post', 'invoices', [], $payload);
        if (!empty($res['invoice'])) return $res['invoice'];

        throw new \Exception('Failed to create invoice');
    }



    /** تسجيل دفعة للفاتورة */
    public function recordPayment(string $invoiceId, float $amount, string $mode = 'others', array $meta = []): array
    {
        $payload = [
            'customer_id'      => $meta['customer_id'],
            'date'             => now()->format('Y-m-d'),
            'amount'           => (float) $amount,
            'payment_mode'     => $mode, // others / banktransfer / cash ...
            'reference_number' => $meta['reference'] ?? null,
            'invoices'         => [
                ['invoice_id' => $invoiceId, 'amount_applied' => (float) $amount]
            ],
            'description'      => $meta['description'] ?? null,
        ];


        $res = $this->call('post', 'customerpayments', [], $payload);
        if (!empty($res['payment'])) return $res['payment'];

        throw new \Exception('Failed to record payment');
    }

    public function getTaxIdByRate(float $rate): ?string
    {
        if (isset($this->taxCache[$rate])) {
            return $this->taxCache[$rate];
        }

        $taxes = $this->call('get', 'settings/taxes')['taxes'] ?? [];

        // أولًا: حاول إيجاد ضريبة مطابقة مباشرة
        foreach ($taxes as $tax) {
            $effectiveRate = $this->calculateEffectiveTaxRate($tax);
            if (abs($effectiveRate - $rate) < 0.01) {
                return $this->taxCache[$rate] = $tax['tax_id'];
            }
        }

        // ثانيًا: إذا ما وجد، جرب تقريب النسبة
        $roundedRate = round($rate, 2);
        foreach ($taxes as $tax) {
            $effectiveRate = $this->calculateEffectiveTaxRate($tax);
            if (abs($effectiveRate - $roundedRate) < 0.01) {
                return $this->taxCache[$rate] = $tax['tax_id'];
            }
        }

        return null;
    }

    protected function calculateEffectiveTaxRate(array $tax): float
    {
        if (!empty($tax['tax_components'])) {
            return collect($tax['tax_components'])->sum('tax_percentage');
        }

        return (float) ($tax['tax_percentage'] ?? 0);
    }

    // ==== TAX HELPERS: fetch / create / ensure ====

    protected array $cachedZohoTaxes = []; // cache per runtime

    /**
     * جلب الضرائب من Zoho (ويخزنها مؤقتًا)
     */
    protected function fetchTaxesFromZoho(bool $force = false): array
    {
        if (!$force && !empty($this->cachedZohoTaxes)) {
            return $this->cachedZohoTaxes;
        }

        try {
            $res = $this->call('get', 'settings/taxes');
        } catch (\Throwable $e) {
            // Log::error('Failed to fetch taxes from Zoho', ['error' => $e->getMessage()]);
            return [];
        }

        $taxes = $res['taxes'] ?? [];

        // normalize: make sure each tax has tax_id and an effective rate
        $this->cachedZohoTaxes = array_map(function ($tax) {
            $tax['effective_rate'] = $this->calculateEffectiveTaxRate($tax);
            return $tax;
        }, $taxes);

        // Log::info('Zoho taxes fetched', ['count' => count($this->cachedZohoTaxes)]);
        return $this->cachedZohoTaxes;
    }

    /**
     * Creates a tax in Zoho (returns tax_id or null)
     */
    public function createTaxInZoho(float $rate, ?string $name = null): ?string
    {
        $name = $name ?: 'VAT ' . rtrim((string) $rate, '.0') . '%';

        $payload = [
            'tax_name'       => $name,
            'tax_percentage' => number_format($rate, 2, '.', ''),
            // 'is_compound' => false, // optional
        ];

        try {
            $res = $this->call('post', 'settings/taxes', [], $payload);
            // Log::info('Create Tax Response', ['response' => $res]);
        } catch (\Throwable $e) {
            // Log::error('Zoho createTax failed', ['rate' => $rate, 'error' => $e->getMessage()]);
            return null;
        }

        // try multiple possible shapes
        if (!empty($res['tax']['tax_id'])) return $res['tax']['tax_id'];
        if (!empty($res['tax_id'])) return $res['tax_id'];
        if (!empty($res['taxes'][0]['tax_id'])) return $res['taxes'][0]['tax_id'];

        // Log::error('createTaxInZoho: unexpected response', ['resp' => $res]);
        return null;
    }

    /**
     * Ensure a tax exists in Zoho for the given percentage.
     * - If found returns tax_id
     * - Else tries to create it and return created tax_id
     */
    public function ensureTaxExistsByRate(float $rate): ?string
    {
        if (!$rate) return null;

        // in-memory cache
        if (isset($this->taxCache[$rate])) {
            return $this->taxCache[$rate];
        }

        // 1) fetch taxes and try match
        $taxes = $this->fetchTaxesFromZoho();
        foreach ($taxes as $tax) {
            $effective = (float) ($tax['effective_rate'] ?? $tax['tax_percentage'] ?? 0);
            if (abs($effective - $rate) < 0.01) {
                $this->taxCache[$rate] = $tax['tax_id'];
                return $tax['tax_id'];
            }
        }

        // 2) not found → try to create
        // Log::info("Tax for rate {$rate}% not found in Zoho, creating...");
        $createdTaxId = $this->createTaxInZoho($rate);
        if ($createdTaxId) {
            // refresh cache
            $this->fetchTaxesFromZoho(true);
            $this->taxCache[$rate] = $createdTaxId;
            // Log::info("Created Zoho tax for rate {$rate}%", ['tax_id' => $createdTaxId]);
            return $createdTaxId;
        }

        // Log::warning("Unable to ensure Zoho tax for rate {$rate}%");
        return null;
    }

    // public function testFetchTaxes(): void
    // {
    //     try {
    //         $taxes = $this->fetchTaxesFromZoho(true);
    //         \Log::info('Fetched Taxes', ['count' => count($taxes), 'taxes' => $taxes]);
    //         dd($taxes);
    //     } catch (\Exception $e) {
    //         \Log::error('Failed to fetch taxes', ['error' => $e->getMessage()]);
    //         dd($e->getMessage());
    //     }
    // }
}
