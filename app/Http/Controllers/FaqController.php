<?php

namespace App\Http\Controllers;

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

        if ($question === '') {
            return BaseController::sendError(__('messages.empty_question'), [], 422);
        }

        // 1) فلترة مبكرة: أسئلة خارج نطاق الموقع
        if ($this->isOutOfScope($question, $locale)) {
            $msg = $locale === 'ar'
                ? 'أنا مساعد لمتجر إنجوي جيمز فقط. اسألني عن المنتجات، التصنيفات، الدفع أو الطلبات.'
                : 'I’m an assistant for Enjoy Games only. Ask me about products, categories, payment, or orders.';
            return BaseController::sendResponse($msg, '');
        }

        // 2) تحيات بسيطة
        if ($this->isGreeting($question, $locale)) {
            $greeting = $locale === 'ar' ? 'مرحبا! كيف يمكنني مساعدتك اليوم؟' : 'Hello! How can I help you today?';
            return BaseController::sendResponse($greeting, '');
        }

        // 2) التحقق من سؤال عن الطلبات
        if ($this->isOrderRelated($question, $locale)) {
            return response()->json([
                'answer' => $locale === 'ar'
                    ? 'يمكنك مشاهدة جميع طلباتك عبر الرابط التالي'
                    : 'You can view all your orders at the following link',
                'from_cache' => false,
                'links' => [
                    'orders'        => ['https://enjoy-games.vercel.app/orders'],
                    'categories'    => [],
                    'sub_categories' => [],
                    'products'      => [],
                ],
            ]);
        }

        // 3) جلسة المحادثة (الذاكرة)
        $chatSession = new ChatSession($uniqueId);
        $previousMessages = $chatSession->getMessages();

        // 4) البحث عن تطابقات المنتجات/التصنيفات
        [$catLinks, $subcatLinks, $productLinks] = $this->findMatchingLinks($question, $locale, $baseUrl);

        // 5) تجهيز System Message
        $systemMessage = $this->buildSystemMessage($locale, $catLinks, $subcatLinks, $productLinks);

        // 6) تجهيز الرسائل للـGPT
        $messages = array_merge($previousMessages, [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $question],
        ]);

        // 7) استدعاء GPT
        $model = 'gpt-4o-mini';   // اقتصادي افتراضيًا
        $maxTokens = 300;
        $temperature = 0.3;

        try {
            $response = OpenAI::chat()->create([
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => $temperature,
                'max_tokens'  => $maxTokens,
            ]);

            $aiAnswer = trim($response->choices[0]->message->content ?? '');

            // إعادة المحاولة بموديل أقوى إذا كان الجواب فاضي أو قصير جدًا
            if (!$aiAnswer || mb_strlen($aiAnswer) < 5) {
                $fallback = OpenAI::chat()->create([
                    'model'       => 'gpt-5',
                    'messages'    => $messages,
                    'temperature' => 0.2,
                    'max_tokens'  => $maxTokens,
                ]);
                $aiAnswer = trim($fallback->choices[0]->message->content ?? '');
            }

            // 8) حفظ الرسائل في الجلسة
            $chatSession->addMessage('user', $question);
            $chatSession->addMessage('assistant', $aiAnswer);

            // 9) حفظ الـFAQ إذا كان هناك جواب صالح
            if ($aiAnswer) {
                Faq::create([
                    'question' => [$locale => $question],
                    'answer'   => [$locale => $aiAnswer],
                    'is_ai_generated' => true,
                ]);
            }

            if (!$aiAnswer && empty($catLinks) && empty($subcatLinks) && empty($productLinks)) {
                return response()->json([
                    'answer' => $locale === 'ar'
                        ? 'يبدو أن سؤالك يحتاج تدخل بشري، يمكنك التواصل مع الدعم الفني عبر الرابط التالي'
                        : 'It looks like your question needs human assistance, please contact support via the following link',
                    'from_cache' => false,
                    'links' => [
                        'support' => ['https://enjoy-games.vercel.app/en/tickets']
                    ]
                ]);
            }

            // فلتر خاص: إذا الجواب من AI يبدو أنه طلب دعم → نتجاهله ونرجع رابط التذاكر
            $supportKeywords = ['support', 'ticket', 'contact', 'دعم',  'تواصل', 'اتصل'];
            foreach ($supportKeywords as $kw) {
                if (Str::contains(mb_strtolower($aiAnswer), mb_strtolower($kw))) {
                    return response()->json([
                        'answer' => $locale === 'ar'
                            ? 'يبدو أنك بحاجة إلى مساعدة من الدعم الفني. يمكنك فتح تذكرة عبر الرابط التالي'
                            : 'It looks like you need help from support. You can open a ticket via the following link',
                        'from_cache' => false,
                        'links' => [
                            'support' => ['https://enjoy-games.vercel.app/en/tickets']
                        ]
                    ]);
                }
            }


            return response()->json([
                'answer' => $aiAnswer,
                'from_cache' => false,
                'links' => [
                    'categories'     => $catLinks,
                    'sub_categories' => $subcatLinks,
                    'products'       => $productLinks,
                ],
            ]);
        } catch (\Throwable $e) {
            $fallbackMsg = $locale === 'ar'
                ? 'تعذر إكمال الطلب الآن. جرب لاحقًا.'
                : 'Unable to complete the request now. Please try again later.';
            return BaseController::sendError($fallbackMsg, [$e->getMessage()], 500);
        }
    }

    // ================= Helper Methods =================

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
        $lq = Str::lower($q);
        foreach ($blockList as $w) {
            if (Str::contains($lq, Str::lower($w))) {
                return true;
            }
        }
        return false;
    }

    protected function isGreeting(string $q, string $locale): bool
    {
        $greetings = $locale === 'ar' ? ['مرحبا', 'أهلا', 'سلام'] : ['hi', 'hello', 'hey'];
        $lq = Str::lower($q);
        foreach ($greetings as $g) {
            if (Str::contains($lq, Str::lower($g))) {
                return true;
            }
        }
        return false;
    }

    protected function findMatchingLinks(string $question, string $locale, string $baseUrl): array
    {
        $lookupKey = "chat_lookup:{$locale}:" . md5($question);

        return Cache::remember($lookupKey, 600, function () use ($question, $baseUrl) {
            $questionLower = mb_strtolower($question);

            // ======== Categories ========
            $categories = Category::query()
                ->select('id', 'name', 'slug')
                ->where('name->ar', 'LIKE', "%{$questionLower}%")
                ->orWhere('name->en', 'LIKE', "%{$questionLower}%")
                ->limit(5)->get();

            // ======== SubCategories ========
            $subcats = SubCategory::query()
                ->select('id', 'name', 'slug', 'category_id')
                ->where('name->ar', 'LIKE', "%{$questionLower}%")
                ->orWhere('name->en', 'LIKE', "%{$questionLower}%")
                ->limit(5)
                ->get()
                ->load('category:id,slug');

            // ======== Products ========
            $products = Product::query()
                ->select('id', 'title', 'slug', 'sub_category_id')
                ->where('title->ar', 'LIKE', "%{$questionLower}%")
                ->orWhere('title->en', 'LIKE', "%{$questionLower}%")
                ->limit(5)
                ->get()
                ->load(['subCategory:id,slug,category_id', 'subCategory.category:id,slug']);

            // ======== Mapping Links ========
            $catLinks = $categories->map(fn($c) => [
                'name_ar' => $c->getTranslation('name', 'ar'),
                'name_en' => $c->getTranslation('name', 'en'),
                'url'     => "{$baseUrl}/categories/{$c->slug}"
            ])->values()->all();

            $subcatLinks = $subcats->map(fn($sc) => [
                'name_ar' => $sc->getTranslation('name', 'ar'),
                'name_en' => $sc->getTranslation('name', 'en'),
                'url'     => $sc->category ? "{$baseUrl}/categories/{$sc->category->slug}/{$sc->slug}" : null
            ])->filter(fn($x) => $x['url'] !== null)->values()->all();

            $productLinks = $products->map(function ($p) use ($baseUrl) {
                $sc = $p->subCategory;
                $catSlug = $sc?->category?->slug;
                $subSlug = $sc?->slug;
                return [
                    'title_ar' => $p->getTranslation('title', 'ar'),
                    'title_en' => $p->getTranslation('title', 'en'),
                    'url'      => ($catSlug && $subSlug)
                        ? "{$baseUrl}/categories/{$catSlug}/{$subSlug}/product/{$p->slug}"
                        : null,
                ];
            })->filter(fn($x) => $x['url'] !== null)->values()->all();

            return [$catLinks, $subcatLinks, $productLinks];
        });
    }


    protected function buildSystemMessage(string $locale, array $catLinks, array $subcatLinks, array $productLinks): string
    {
        $msg = $locale === 'ar' ? "أنت مساعد لمتجر إنجوي جيمز.\n"
            : "You are an assistant for Enjoy Games store.\n";

        $msg .= $locale === 'ar'
            ? "أجب بنفس لغة المستخدم فقط عن الأسئلة المتعلقة بالمتجر (منتجات، تصنيفات، الطلب، الدفع).\n"
            : "Reply only about store-related questions (products, categories, orders, payment).\n";

        if (!empty($catLinks)) {
            $msg .= "\n" . ($locale === 'ar' ? 'تصنيفات محتملة:' : 'Possible categories:') . "\n";
            foreach ($catLinks as $c) $msg .= "- " . ($locale === 'ar' ? $c['name_ar'] : $c['name_en']) . ": {$c['url']}\n";
        }
        if (!empty($subcatLinks)) {
            $msg .= "\n" . ($locale === 'ar' ? 'تصنيفات فرعية محتملة:' : 'Possible subcategories:') . "\n";
            foreach ($subcatLinks as $sc) $msg .= "- " . ($locale === 'ar' ? $sc['name_ar'] : $sc['name_en']) . ": {$sc['url']}\n";
        }
        if (!empty($productLinks)) {
            $msg .= "\n" . ($locale === 'ar' ? 'منتجات محتملة:' : 'Possible products:') . "\n";
            foreach ($productLinks as $p) $msg .= "- " . ($locale === 'ar' ? $p['title_ar'] : $p['title_en']) . ": {$p['url']}\n";
        }

        return $msg;
    }

    protected function isOrderRelated(string $question, string $locale): bool
    {
        $question = mb_strtolower($question);

        $keywordsAr = ['طلب', 'طلباتي', 'الطلب', 'طلبية', 'طلب رقم'];
        $keywordsEn = ['order', 'orders', 'my order', 'order status'];

        foreach (($locale === 'ar' ? $keywordsAr : $keywordsEn) as $word) {
            if (str_contains($question, $word)) {
                return true;
            }
        }

        return false;
    }
}
