<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ChatSession
{
    protected string $uniqueId;
    protected int $maxMessages;
    protected int $ttl; // مدة التخزين بالكاش بالثواني
    protected string $cacheKey;

    public function __construct(string $uniqueId, int $maxMessages = 20, int $ttl = 3600)
    {
        $this->uniqueId = $uniqueId;
        $this->maxMessages = $maxMessages;
        $this->ttl = $ttl;
        $this->cacheKey = "chat_history:unique_id:{$this->uniqueId}";
    }

    /**
     * استرجاع كل الرسائل السابقة.
     *
     * @return array
     */
    public function getMessages(): array
    {
        return Cache::get($this->cacheKey, []);
    }

    /**
     * إضافة رسالة جديدة (role: user|assistant)
     *
     * @param string $role
     * @param string $content
     */
    public function addMessage(string $role, string $content): void
    {
        $messages = $this->getMessages();
        $messages[] = [
            'role' => $role,
            'content' => $content
        ];

        // احتفظ فقط بآخر $maxMessages رسالة
        $messages = array_slice($messages, -$this->maxMessages);

        Cache::put($this->cacheKey, $messages, $this->ttl);
    }

    /**
     * مسح المحادثة بالكامل (مثلاً عند انتهاء الجلسة)
     */
    public function clear(): void
    {
        Cache::forget($this->cacheKey);
    }
}
