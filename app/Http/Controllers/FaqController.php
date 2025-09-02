<?php

namespace App\Http\Controllers;

use App\Enum\PaymentCurrencyEnum;
use App\Http\Controllers\API\BaseController;
use App\Http\Requests\AssistantAIRequest;
use App\Http\Requests\FaqRequest;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Models\SubCategory;
use App\Services\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class FaqController extends Controller
{
    public function getAdminFAQS(): JsonResponse
    {
        try {
            $faq = Faq::latest()->get();
            return BaseController::sendResponse($faq, __('messages.store_successfully', ['item' => __('messages.faq')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.faq')]), [], 500);
        }
    }
    public function store(FaqRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $faq = Faq::create($data);
            return BaseController::sendResponse($faq, __('messages.store_successfully', ['item' => __('messages.faq')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.faq')]), [], 500);
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $faq = Faq::find($id);
            if (!$faq || !isset($faq)) {
                return BaseController::sendError(__('messages.item_not_found', ['item' => __('messages.faq')]), [], 404);
            }
            $faq->delete();
            return BaseController::sendResponse($faq, __('messages.delete_successfully', ['item' => __('messages.faq')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.delete_failed', ['item' => __('messages.faq')]), [], 500);
        }
    }

    public function ask(AssistantAIRequest $request): JsonResponse
    {
        $question = trim((string) $request->question);
        $locale   = app()->getLocale(); // 'ar' أو 'en'
        $baseUrl  = 'https://enjoy-games.vercel.app';
        $uniqueId = $request->unique_id;
        $currency = strtoupper($request->header('Currency', PaymentCurrencyEnum::DEFAULT_CURRENCY->value));

        $emptyLinks = [
            'orders' => [],
            'categories' => [],
            'sub_categories' => [],
            'products' => [],
            'support' => [],
        ];

        if ($question === '') {
            return response()->json([
                'answer' => $locale === 'ar' ? 'يرجى إدخال السؤال أولاً.' : 'Please enter a question first.',
                'from_cache' => false,
                'links' => $emptyLinks,
            ], 422);
        }

        // 1) أسئلة خارج نطاق الموقع
        if ($this->isOutOfScope($question, $locale)) {
            $msg = $locale === 'ar'
                ? 'أنا مساعد لمتجر إنجوي جيمز فقط. اسألني عن المنتجات، التصنيفات، الدفع أو الطلبات.'
                : 'I’m an assistant for Enjoy Games only. Ask me about products, categories, payment, or orders.';
            return response()->json([
                'answer' => $msg,
                'from_cache' => false,
                'links' => $emptyLinks,
            ]);
        }

        // 2) تحيات
        if ($this->isGreeting($question, $locale)) {
            $greeting = $locale === 'ar' ? 'مرحبا! كيف يمكنني مساعدتك اليوم؟' : 'Hello! How can I help you today?';
            return response()->json([
                'answer' => $greeting,
                'from_cache' => false,
                'links' => $emptyLinks,
            ]);
        }

        // 3) أسئلة الطلبات
        if ($this->isOrderRelated($question, $locale)) {
            return response()->json([
                'answer' => $locale === 'ar'
                    ? 'يمكنك مشاهدة جميع طلباتك عبر الرابط التالي'
                    : 'You can view all your orders at the following link',
                'from_cache' => false,
                'links' => array_merge($emptyLinks, ['orders' => ["{$baseUrl}/orders"]]),
            ]);
        }

        // ===== 3.5) سؤال عن المطور =====
        $developerKeywords = $locale === 'ar'
            ? ['المطور', 'من صمم الموقع', 'من برمج الموقع', 'صممه']
            : ['who developed', 'who made the site', 'who coded', 'developer'];

        $lowerQuestion = mb_strtolower($question);

        foreach ($developerKeywords as $kw) {
            if (Str::contains($lowerQuestion, mb_strtolower($kw))) {
                $answer = $locale === 'ar'
                    ? "الموقع صممه ماجد زيارة، وهو مطور ويب متخصص في إطار لارافيل. يمكنك زيارة حسابه على GitHub هنا: https://github.com/majedmaher"
                    : "The website was designed by Majed Ziyara, a web developer specialized in Laravel framework. You can visit his GitHub here: https://github.com/majedmaher";

                return response()->json([
                    'answer' => $answer,
                    'from_cache' => false,
                    'links' => $emptyLinks,
                ]);
            }
        }


        // ===== 4) التحقق من طلب كل الألعاب =====
        if ($this->isAllProductsRequest($question, $locale)) {
            $products = Product::all()->load(['subCategory.category']);
            $productLinks = $products->map(fn($p) => [
                'title_ar' => $p->getTranslation('title', 'ar'),
                'title_en' => $p->getTranslation('title', 'en'),
                'price' => currencyConverter($p->price, $currency),
                'url' => $p->subCategory && $p->subCategory->category
                    ? "{$baseUrl}/categories/{$p->subCategory->category->slug}/{$p->subCategory->slug}/product/{$p->slug}"
                    : null,
            ])->filter(fn($x) => $x['url'] !== null)->values()->all();

            return response()->json([
                'answer' => $locale === 'ar' ? 'هذه جميع الألعاب المتوفرة لدينا:' : 'Here are all available games:',
                'from_cache' => false,
                'links' => array_merge($emptyLinks, ['products' => $productLinks]),
            ]);
        }

        // 4) جلسة المحادثة
        $chatSession = new ChatSession($uniqueId);
        $previousMessages = $chatSession->getMessages();

        // 5) البحث عن تطابقات للروابط
        [$catLinks, $subcatLinks, $productLinks] = $this->findMatchingLinks($question, $locale, $baseUrl, $currency);

        // 6) تجهيز system message
        $systemMessage = $this->buildSystemMessage($locale, $catLinks, $subcatLinks, $productLinks);

        $messages = array_merge($previousMessages, [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $question],
        ]);

        try {
            // 7) استدعاء GPT
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'temperature' => 0.3,
                'max_tokens' => 300,
            ]);

            $aiAnswer = trim($response->choices[0]->message->content ?? '');

            // 8) التعرف على طلب "كل الألعاب" عبر GPT
            $isAllProducts = Str::contains(mb_strtolower($aiAnswer), $locale === 'ar' ? 'كل الألعاب' : 'all games');

            if ($isAllProducts) {
                $products = Product::all()->load(['subCategory.category']);
                $productLinks = $products->map(fn($p) => [
                    'title_ar' => $p->getTranslation('title', 'ar'),
                    'title_en' => $p->getTranslation('title', 'en'),
                    'price' => currencyConverter($p->price, $currency),
                    'url' => $p->subCategory && $p->subCategory->category
                        ? "{$baseUrl}/categories/{$p->subCategory->category->slug}/{$p->subCategory->slug}/product/{$p->slug}"
                        : null,
                ])->filter(fn($x) => $x['url'] !== null)->values()->all();

                $aiAnswer = $locale === 'ar' ? 'هذه جميع الألعاب المتوفرة لدينا:' : 'Here are all available games:';

                return response()->json([
                    'answer' => $aiAnswer,
                    'from_cache' => false,
                    'links' => array_merge($emptyLinks, ['products' => $productLinks]),
                ]);
            }

            // 9) حفظ الجلسة وFAQ
            $chatSession->addMessage('user', $question);
            $chatSession->addMessage('assistant', $aiAnswer);
            if ($aiAnswer) {
                Faq::create([
                    'question' => [$locale => $question],
                    'answer'   => [$locale => $aiAnswer],
                    'is_ai_generated' => true,
                ]);
            }

            // 10) فلتر الدعم الفني
            $supportKeywords = ['support', 'ticket', 'contact', 'دعم', 'تواصل', 'اتصل'];
            foreach ($supportKeywords as $kw) {
                if (Str::contains(mb_strtolower($aiAnswer), mb_strtolower($kw))) {
                    return response()->json([
                        'answer' => $locale === 'ar'
                            ? 'يبدو أنك بحاجة إلى مساعدة من الدعم الفني. يمكنك فتح تذكرة عبر الرابط التالي'
                            : 'It looks like you need help from support. You can open a ticket via the following link',
                        'from_cache' => false,
                        'links' => array_merge($emptyLinks, ['support' => ["{$baseUrl}/en/tickets"]]),
                    ]);
                }
            }

            return response()->json([
                'answer' => $aiAnswer ?: ($locale === 'ar' ? 'تم العثور على النتائج التالية:' : 'Here are the results found:'),
                'from_cache' => false,
                'links' => [
                    'orders' => [],
                    'categories' => $catLinks,
                    'sub_categories' => $subcatLinks,
                    'products' => $productLinks,
                    'support' => [],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'answer' => $locale === 'ar' ? 'تعذر إكمال الطلب الآن. جرب لاحقًا.' : 'Unable to complete the request now. Please try again later.',
                'from_cache' => false,
                'links' => $emptyLinks,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ===== Helper Functions =====

    protected function isOutOfScope(string $q, string $locale): bool
    {
        $blockList = [
            'سياسة',
            'أخبار',
            'طقس',
            'رياضة',
            'سياسي',
            'رئيس',
            'طب',
            'برمجة',
            'unrelated',
            'politics',
            'weather',
            'news',
            'sports',
            'health',
            'medical'
        ];

        $lq = mb_strtolower($q);
        foreach ($blockList as $w) {
            if (Str::contains($lq, mb_strtolower($w))) {
                return true;
            }
        }
        return false;
    }

    protected function isGreeting(string $q, string $locale): bool
    {
        $greetings = $locale === 'ar' ? ['مرحبا', 'أهلا', 'سلام', 'هلا'] : ['hi', 'hello', 'hey', 'greetings'];
        $lq = mb_strtolower($q);

        foreach ($greetings as $g) {
            if (Str::contains($lq, mb_strtolower($g))) {
                return true;
            }
        }
        return false;
    }

    protected function isOrderRelated(string $question, string $locale): bool
    {
        $question = mb_strtolower($question);
        $keywordsAr = ['طلب', 'طلباتي', 'الطلب', 'طلبية', 'طلب رقم'];
        $keywordsEn = ['order', 'orders', 'my order', 'order status'];

        foreach (($locale === 'ar' ? $keywordsAr : $keywordsEn) as $word) {
            if (Str::contains($question, $word)) return true;
        }
        return false;
    }

    protected function findMatchingLinks(string $question, string $locale, string $baseUrl, string $currency): array
    {
        $lookupKey = "chat_lookup:{$locale}:" . md5($question);

        return Cache::remember($lookupKey, 600, function () use ($question, $locale, $baseUrl, $currency) {
            $questionClean = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $question);
            $words = array_filter(explode(' ', mb_strtolower($questionClean)));

            // البحث عن التصنيفات
            $categories = Category::query()
                ->select('id', 'name', 'slug')
                ->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->orWhere('name->ar', 'LIKE', "%{$w}%")
                            ->orWhere('name->en', 'LIKE', "%{$w}%");
                    }
                })
                ->limit(5)
                ->get();

            // البحث عن التصنيفات الفرعية
            $subcats = SubCategory::query()
                ->select('id', 'name', 'slug', 'category_id')
                ->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->orWhere('name->ar', 'LIKE', "%{$w}%")
                            ->orWhere('name->en', 'LIKE', "%{$w}%");
                    }
                })
                ->limit(5)
                ->get()
                ->load('category:id,slug');

            // البحث عن المنتجات
            $products = Product::query()
                ->select('id', 'title', 'slug', 'price', 'sub_category_id')
                ->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->orWhere('title->ar', 'LIKE', "%{$w}%")
                            ->orWhere('title->en', 'LIKE', "%{$w}%");
                    }
                })
                ->limit(5)
                ->get()
                ->load(['subCategory:id,slug,category_id', 'subCategory.category:id,slug']);

            // بناء روابط التصنيفات
            $catLinks = $categories->map(fn($c) => [
                'name_ar' => $c->getTranslation('name', 'ar'),
                'name_en' => $c->getTranslation('name', 'en'),
                'url' => "{$baseUrl}/categories/{$c->slug}"
            ])->values()->all();

            // بناء روابط التصنيفات الفرعية
            $subcatLinks = $subcats->map(fn($sc) => [
                'name_ar' => $sc->getTranslation('name', 'ar'),
                'name_en' => $sc->getTranslation('name', 'en'),
                'url' => $sc->category ? "{$baseUrl}/categories/{$sc->category->slug}/{$sc->slug}" : null
            ])->filter(fn($x) => $x['url'] !== null)->values()->all();

            // بناء روابط المنتجات
            $productLinks = $products->map(fn($p) => [
                'title_ar' => $p->getTranslation('title', 'ar'),
                'title_en' => $p->getTranslation('title', 'en'),
                'price' => currencyConverter($p->price, $currency),
                'url' => $p->subCategory && $p->subCategory->category
                    ? "{$baseUrl}/categories/{$p->subCategory->category->slug}/{$p->subCategory->slug}/product/{$p->slug}"
                    : null
            ])->filter(fn($x) => $x['url'] !== null)->values()->all();

            return [$catLinks, $subcatLinks, $productLinks];
        });
    }

    protected function buildSystemMessage(string $locale, array $catLinks, array $subcatLinks, array $productLinks): string
    {
        $msg = $locale === 'ar'
            ? "أنت مساعد لمتجر إنجوي جيمز.\n"
            : "You are an assistant for Enjoy Games store.\n";

        $msg .= $locale === 'ar'
            ? "أجب بنفس لغة المستخدم فقط عن الأسئلة المتعلقة بالمتجر (منتجات، تصنيفات، الطلب، الدفع).\n"
            : "Reply only about store-related questions (products, categories, orders, payment).\n";

        // تحديد طرق الدفع المتوفرة
        $msg .= $locale === 'ar'
            ? "طرق الدفع المتاحة لدينا فقط هي: المحفظة وباي موب.\n"
            : "The only available payment methods are: Wallet and Paymob.\n";

        if (!empty($catLinks)) {
            $msg .= "\n" . ($locale === 'ar' ? 'تصنيفات محتملة:' : 'Possible categories:') . "\n";
            foreach ($catLinks as $c) {
                $msg .= "- " . ($locale === 'ar' ? $c['name_ar'] : $c['name_en']) . ": {$c['url']}\n";
            }
        }

        if (!empty($subcatLinks)) {
            $msg .= "\n" . ($locale === 'ar' ? 'تصنيفات فرعية محتملة:' : 'Possible subcategories:') . "\n";
            foreach ($subcatLinks as $sc) {
                $msg .= "- " . ($locale === 'ar' ? $sc['name_ar'] : $sc['name_en']) . ": {$sc['url']}\n";
            }
        }

        if (!empty($productLinks)) {
            $msg .= "\n" . ($locale === 'ar' ? 'منتجات محتملة:' : 'Possible products:') . "\n";
            foreach ($productLinks as $p) {
                $msg .= "- " . ($locale === 'ar' ? $p['title_ar'] : $p['title_en']) . ": {$p['url']}\n";
            }
        }

        return $msg;
    }

    protected function isAllProductsRequest(string $question, string $locale): bool
    {
        $question = mb_strtolower($question);

        $keywordsAr = ['كل الألعاب', 'جميع الألعاب', 'الالعاب المتوفرة', 'اعرض كل الألعاب', 'أريد كل الألعاب'];
        $keywordsEn = ['all games', 'all products', 'show all games', 'list all products', 'available games'];

        foreach (($locale === 'ar' ? $keywordsAr : $keywordsEn) as $word) {
            if (str_contains($question, $word)) return true;
        }

        return false;
    }
}
