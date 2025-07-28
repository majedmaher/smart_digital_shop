<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\BaseController;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class FaqController extends Controller
{
    public function store(FaqRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] - auth()->id();
            $faq = Faq::create();
            return BaseController::sendResponse($faq, __('messages.store_successfully', ['item' => __('messages.faq')]));
        } catch (\Throwable $th) {
            return BaseController::sendError(__('messages.store_failed', ['item' => __('messages.faq')]), [$th->getMessage()], 500);
        }
    }

    public function ask(Request $request): JsonResponse
    {
        $question = $request->input('question');
        $locale = app()->getLocale(); // 'ar' أو 'en'

        // البحث عن سؤال مشابه
        $existingFaq = Faq::where("question->{$locale}", 'LIKE', "%$question%")->first();
        if ($existingFaq) {
            return response()->json([
                'answer' => $existingFaq->getTranslation('answer', $locale),
                'from_cache' => true,
            ]);
            // return BaseController::sendResponse($existingFaq->getTranslation('answer', $locale),__('resp'))
        }

        // تحميل التصنيفات مع روابطها
        $sub_categories = SubCategory::get()->map(function ($cat) use ($locale) {
            return [
                'name' => $cat->getTranslation('name', $locale),
                'slug' => $cat->getTranslation('slug', $locale),
                'url' => route('getSubCategory', $cat->getTranslation('slug', $locale)),
            ];
        });

        // تحميل منتجات عشوائية (اختياري)
        // $products = Product::inRandomOrder()->limit(5)->get()->map(function ($p) use ($locale) {
        $products = Product::get()->map(function ($p) use ($locale) {
            return [
                'title' => $p->getTranslation('title', $locale),
                'slug' => $p->getTranslation('slug', $locale),
                'url' => route('getProduct', $p->getTranslation('slug', $locale)),
            ];
        });

        // تحميل أسئلة وأجوبة سابقة بنفس اللغة
        $faqs = Faq::all()->map(function ($faq) use ($locale) {
            return [
                'q' => $faq->getTranslation('question', $locale),
                'a' => $faq->getTranslation('answer', $locale),
            ];
        });

        // بناء system message ذكي
        if ($locale === 'ar') {
            $systemMessage = "أنت مساعد ذكي لموقع إلكتروني يبيع أكواد رقمية إسمه إنجوي جيمز.\n";
            $systemMessage .= "أجب عن استفسارات المستخدم بلغة سهلة باللهجة المكتوبة إن أمكن.\n";
            $systemMessage .= "لا تجب على الأسئلة المتعلقة بالخصومات أو الطلبات أو المعلومات الشخصية.\n";
            $systemMessage .= "طرق الدفع: بطاقة ائتمان عبر Paymob فقط.\n";
            $systemMessage .= "إليك بعض الأسئلة الشائعة التي يمكن أن تستفيد منها في الرد:\n";
        } else {
            $systemMessage = "You are a smart assistant for an online store that sells digital codes his name is Enjoy Games.\n";
            $systemMessage .= "Answer user questions in simple and natural language if possible.\n";
            $systemMessage .= "Do not respond to questions about discounts, orders, or personal info.\n";
            $systemMessage .= "Payment methods: Credit card via Paymob only.\n";
            $systemMessage .= "Here are some FAQ entries to help you answer:\n";
        }

        foreach ($faqs as $faq) {
            $systemMessage .= "Q: {$faq['q']}\nA: {$faq['a']}\n";
        }

        // إضافة التصنيفات والمنتجات للرابط
        $systemMessage .= $locale === 'ar' ? "\nالتصنيفات:\n" : "\nCategories:\n";
        foreach ($sub_categories as $c) {
            $systemMessage .= "- {$c['name']}: {$c['url']}\n";
        }
        // إضافة التصنيفات والمنتجات للرابط
        $systemMessage .= $locale === 'ar' ? "\المنتجات:\n" : "\products:\n";
        foreach ($products as $p) {
            $systemMessage .= "- {$p['title']}: {$p['url']}\n";
        }

        // إرسال الرسائل
        $messages = [
            ['role' => 'system', 'content' => $systemMessage],
            ['role' => 'user', 'content' => $question],
        ];

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => $messages,
        ]);

        $aiAnswer = trim($response->choices[0]->message->content);

        // حفظ السؤال الجديد
        Faq::create([
            'question' => [$locale => $question],
            'answer' => [$locale => $aiAnswer],
        ]);

        return response()->json([
            'answer' => $aiAnswer,
            'from_cache' => false,
        ]);
    }
}
