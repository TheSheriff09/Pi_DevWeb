<?php

namespace App\Service;

/**
 * BadWordsFilter
 *
 * Word list source (970+ words, MIT-like, public domain):
 *   https://github.com/coffee-and-fun/google-profanity-words
 *   Raw URL: https://raw.githubusercontent.com/coffee-and-fun/google-profanity-words/main/data/en.txt
 *
 * The list is downloaded once and cached locally for 24 hours so the app
 * never makes a live HTTP call on every request.
 *
 * Obfuscation detection covers:
 *   • Leet-speak substitutions  (@ → a, 3 → e, 1 → i/l, 0 → o, $ → s …)
 *   • Symbol / punctuation insertion  (f*ck, f.u.c.k, f-u-c-k)
 *   • Repeated character padding     (fuuuuck, shhhhit)
 *   • Mixed-separator spacing        (f u c k)
 */
class BadWordsFilter
{
    /** Remote list – MIT-licensed, community-maintained (970+ words). */
    private const WORD_LIST_URL =
        'https://raw.githubusercontent.com/coffee-and-fun/google-profanity-words/main/data/en.txt';

    // ═══════════════════════════════════════════════════════════════════
    //  ✏️  ADD YOUR CUSTOM BANNED WORDS HERE
    //  One word or phrase per entry. Case-insensitive. Obfuscation is
    //  detected automatically (leet-speak, symbols, spaces, etc.)
    //  Example: 'badword', 'bad phrase', 'spam keyword'
    // ═══════════════════════════════════════════════════════════════════
    private array $customWords = [
        // 'yourword',
        // 'your phrase here',
        'maher'
    ];
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Words that ALWAYS get blocked no matter what — even if the remote
     * list fails. These cover threats & violence not in the profanity list.
     */
    private array $alwaysBlocked = [
        // Violence / threats
        'kill', 'murder', 'stab', 'shoot', 'bomb', 'attack',
        'assassinate', 'slaughter', 'massacre', 'execute',
        'i will kill', 'i ll kill', 'gonna kill', 'want to kill',
        'i will hurt', 'i will shoot', 'i will stab',
        'blow up', 'blow yourself up',
        // Self-harm
        'kill yourself', 'kys', 'go kill yourself', 'end your life',
        'commit suicide', 'go die', 'you should die',
        // Hate / harassment
        'i hate you', 'die already', 'hope you die',
        // Terrorism
        'terrorist', 'jihad', 'suicide bomber',
    ];

    /**
     * Words to NEVER block — they appear inside innocent common words.
     * e.g. "hell" would block "hello", "ass" blocks "class/pass/mass".
     * Add any false-positive word here.
     */
    private array $whitelist = [
        'hell',   // hello, shell, shellfish …
        'cum',    // curriculum, accumulate …
        'ho',     // honor, hotel, show …
        'bum',    // bump, album, number …
        'poo',    // pool, poor, spoon …
        'crap',   // # leave if you want; remove if too strict
        'damn',   // # leave if you want
    ];

    /** Local cache file – refreshed every 24 h. */
    private string $cacheFile;

    /** Loaded word list (lazy). */
    private ?array $words = null;

    /**
     * Leet-speak / common symbol substitutions.
     * Mapping: obfuscated char → plain char(s)
     */
    private const LEET_MAP = [
        '@'  => 'a',
        '4'  => 'a',
        '8'  => 'b',
        '('  => 'c',
        '{'  => 'c',
        '['  => 'c',
        '<'  => 'c',
        '3'  => 'e',
        '€'  => 'e',
        '6'  => 'g',
        '#'  => 'h',
        '!'  => 'i',
        '1'  => 'i',
        '|'  => 'i',
        '0'  => 'o',
        'ø'  => 'o',
        '$'  => 's',
        '5'  => 's',
        '§'  => 's',
        '+'  => 't',
        '7'  => 't',
        'ü'  => 'u',
        'ú'  => 'u',
        'v'  => 'v',   // keep for double-u case below
        '2'  => 'z',
    ];

    public function __construct(string $kernelCacheDir)
    {
        $this->cacheFile = $kernelCacheDir . '/bad_words_cache.txt';
    }

    // ─────────────────────────────────────────────────────────────
    //  Public API
    // ─────────────────────────────────────────────────────────────

    /**
     * Returns the first violated word/phrase found, or null if clean.
     */
    public function findViolation(string $text): ?string
    {
        $words = $this->getWords();

        // Build all normalized variants of the input
        $variants       = $this->buildVariants($text);
        // Also keep the original lowercased text for phrase/boundary checks
        $lowerOriginal  = mb_strtolower($text);

        foreach ($words as $bad) {
            // Skip words explicitly whitelisted (common false-positives)
            if (in_array(mb_strtolower($bad), $this->whitelist, true)) {
                continue;
            }

            $normalBad = $this->normalize($bad);
            if ($normalBad === '') {
                continue;
            }

            // ── Strategy 1: word-boundary check on the ORIGINAL text ──────
            // Catches "kill" in "I will kill you" but NOT "hell" in "hello"
            $escaped = preg_quote($bad, '/');
            if (preg_match('/\b' . $escaped . '\b/iu', $lowerOriginal)) {
                return $bad;
            }

            // ── Strategy 2: normalized substring check ────────────────────
            // Catches obfuscated variants like k!ll, k1ll, f*ck, etc.
            // Only apply if the bad word is long enough (≥4 chars) to avoid
            // short-word false positives after stripping separators.
            if (mb_strlen($normalBad) >= 4) {
                foreach ($variants as $variant) {
                    if (str_contains($variant, $normalBad)) {
                        return $bad;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Returns true when the text contains no forbidden words.
     */
    public function isClean(string $text): bool
    {
        return $this->findViolation($text) === null;
    }

    // ─────────────────────────────────────────────────────────────
    //  Normalization helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Build multiple normalised representations of $text so obfuscation
     * is caught regardless of the technique used.
     */
    private function buildVariants(string $text): array
    {
        $lower = mb_strtolower($text);

        return array_unique([
            // 1. Plain lowercase
            $this->normalize($lower),

            // 2. After stripping ALL non-alpha chars (catches f*u*c*k, f.u.c.k)
            $this->normalize(preg_replace('/[^a-z0-9]/u', '', $lower)),

            // 3. After collapsing repeated chars (fuuuuck → fuck)
            $this->normalize($this->collapseRepeats($lower)),

            // 4. Leet-speak decoded, then stripped
            $this->normalize($this->decodeLeet($lower)),

            // 5. Leet-decoded + repeat-collapsed + stripped
            $this->normalize($this->collapseRepeats($this->decodeLeet($lower))),
        ]);
    }

    /**
     * Apply leet-speak character substitutions.
     */
    private function decodeLeet(string $text): string
    {
        return strtr($text, self::LEET_MAP);
    }

    /**
     * Collapse runs of the same character: fuuuuck → fuck
     */
    private function collapseRepeats(string $text): string
    {
        return (string) preg_replace('/(.)\1+/u', '$1', $text);
    }

    /**
     * Strip everything except lowercase letters and digits,
     * then collapse repeated chars one more time.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        // Remove separators/symbols between letters (f u c k → fuck, f-u-c-k → fuck)
        $text = (string) preg_replace('/[^a-z0-9]/u', '', $text);
        // Final repeat collapse
        $text = (string) preg_replace('/(.)\1{2,}/u', '$1$1', $text);
        return $text;
    }

    // ─────────────────────────────────────────────────────────────
    //  Word-list loading & caching
    // ─────────────────────────────────────────────────────────────

    /**
     * Return the word list, loading from cache or remote as needed.
     */
    private function getWords(): array
    {
        if ($this->words !== null) {
            return $this->words;
        }

        // Use cache if fresh (< 24 h)
        if (file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile)) < 86400) {
            $this->words = $this->parseWordList(file_get_contents($this->cacheFile));
        } elseif ($remote = $this->fetchRemote(self::WORD_LIST_URL)) {
            // Fetch succeeded — save and use it
            file_put_contents($this->cacheFile, $remote);
            $this->words = $this->parseWordList($remote);
        } elseif (file_exists($this->cacheFile)) {
            // Network failed but stale cache exists — use it
            $this->words = $this->parseWordList(file_get_contents($this->cacheFile));
        } else {
            $this->words = [];
        }

        // Always merge your custom words AND the always-blocked list
        $custom  = array_map('mb_strtolower', array_filter($this->customWords));
        $always  = array_map('mb_strtolower', $this->alwaysBlocked);
        $this->words = array_unique(array_merge($this->words, $custom, $always));

        return $this->words;
    }

    /**
     * Parse the raw text file: one word/phrase per line.
     */
    private function parseWordList(string $raw): array
    {
        $lines = explode("\n", str_replace("\r", '', $raw));
        $words = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $words[] = mb_strtolower($line);
            }
        }
        return array_unique($words);
    }

    /**
     * Fetch a URL using PHP streams (no Guzzle/cURL required).
     */
    private function fetchRemote(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout'       => 5,
                'ignore_errors' => true,
                'header'        => "User-Agent: Symfony-BadWordsFilter/1.0\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        return ($body !== false && strlen($body) > 100) ? $body : null;
    }
}
