<?php

namespace App\Service;

/**
 * TranslationService
 *
 * Uses the MyMemory free translation API — no API key required.
 * Docs: https://mymemory.translated.net/doc/spec.php
 *
 * Limits (anonymous):  5 000 words / day
 * Limits (with email): 10 000 words / day  →  set MYMEMORY_EMAIL in .env
 *
 * Long texts are automatically split into chunks ≤ 500 chars and
 * reassembled so the 500-byte per-request limit is never exceeded.
 */
class TranslationService
{
    private const API_URL = 'https://api.mymemory.translated.net/get';
    private const CHUNK   = 450; // safe margin below 500-byte limit

    /** Optional registered e-mail for higher daily quota */
    private ?string $email;

    public function __construct(?string $myMemoryEmail = null)
    {
        $this->email = $myMemoryEmail ?: null;
    }

    // ──────────────────────────────────────────────────────────────────
    //  Public
    // ──────────────────────────────────────────────────────────────────

    /**
     * Translate $text from $sourceLang to $targetLang.
     * Returns the translated string, or throws \RuntimeException on failure.
     *
     * @param string $text        UTF-8 source text
     * @param string $targetLang  ISO code e.g. "fr", "ar", "es"
     * @param string $sourceLang  ISO code, default "en" (auto-detect if "auto")
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Split into chunks so we never exceed the 500-byte limit
        $chunks = $this->splitText($text);
        $parts  = [];

        foreach ($chunks as $chunk) {
            $parts[] = $this->callApi($chunk, $sourceLang, $targetLang);
        }

        return implode(' ', $parts);
    }

    /**
     * Returns the list of supported languages with their flag emojis.
     * This is used by the frontend language picker.
     */
    public static function supportedLanguages(): array
    {
        return [
            'fr' => ['name' => 'French',     'flag' => '🇫🇷'],
            'ar' => ['name' => 'Arabic',      'flag' => '🇸🇦'],
            'es' => ['name' => 'Spanish',     'flag' => '🇪🇸'],
            'de' => ['name' => 'German',      'flag' => '🇩🇪'],
            'it' => ['name' => 'Italian',     'flag' => '🇮🇹'],
            'pt' => ['name' => 'Portuguese',  'flag' => '🇧🇷'],
            'ru' => ['name' => 'Russian',     'flag' => '🇷🇺'],
            'zh' => ['name' => 'Chinese',     'flag' => '🇨🇳'],
            'ja' => ['name' => 'Japanese',    'flag' => '🇯🇵'],
            'ko' => ['name' => 'Korean',      'flag' => '🇰🇷'],
            'tr' => ['name' => 'Turkish',     'flag' => '🇹🇷'],
            'nl' => ['name' => 'Dutch',       'flag' => '🇳🇱'],
            'pl' => ['name' => 'Polish',      'flag' => '🇵🇱'],
            'sv' => ['name' => 'Swedish',     'flag' => '🇸🇪'],
        ];
    }

    // ──────────────────────────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────────────────────────

    private function callApi(string $text, string $from, string $to): string
    {
        $params = [
            'q'        => $text,
            'langpair' => $from . '|' . $to,
        ];
        if ($this->email) {
            $params['de'] = $this->email;
        }

        $url = self::API_URL . '?' . http_build_query($params);

        $ctx  = stream_context_create(['http' => [
            'timeout'       => 8,
            'ignore_errors' => true,
            'header'        => "User-Agent: Symfony-TranslationService/1.0\r\n",
        ]]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            throw new \RuntimeException('Translation API unreachable.');
        }

        $data = json_decode($body, true);

        if (
            !isset($data['responseStatus']) ||
            (int) $data['responseStatus'] !== 200
        ) {
            $msg = $data['responseDetails'] ?? 'Unknown error';
            throw new \RuntimeException('Translation API error: ' . $msg);
        }

        return $data['responseData']['translatedText'] ?? $text;
    }

    /**
     * Split text into sentences / words so each chunk ≤ CHUNK chars.
     */
    private function splitText(string $text): array
    {
        if (mb_strlen($text) <= self::CHUNK) {
            return [$text];
        }

        $chunks   = [];
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [$text];
        $current  = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($current) + mb_strlen($sentence) + 1 <= self::CHUNK) {
                $current .= ($current !== '' ? ' ' : '') . $sentence;
            } else {
                if ($current !== '') {
                    $chunks[] = $current;
                }
                // If a single sentence is too long, hard-split by words
                if (mb_strlen($sentence) > self::CHUNK) {
                    $words   = explode(' ', $sentence);
                    $current = '';
                    foreach ($words as $word) {
                        if (mb_strlen($current) + mb_strlen($word) + 1 <= self::CHUNK) {
                            $current .= ($current !== '' ? ' ' : '') . $word;
                        } else {
                            if ($current !== '') {
                                $chunks[] = $current;
                            }
                            $current = $word;
                        }
                    }
                } else {
                    $current = $sentence;
                }
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
