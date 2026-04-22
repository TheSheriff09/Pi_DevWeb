<?php

namespace App\Service;

use App\Entity\BannedWord;
use Doctrine\ORM\EntityManagerInterface;

/**
 * BadWordsFilter
 *
 * Word list source (970+ words, MIT-like, public domain):
 *   https://github.com/coffee-and-fun/google-profanity-words
 *   Raw URL: https://raw.githubusercontent.com/coffee-and-fun/google-profanity-words/main/data/en.txt
 *
 * The list is downloaded once and cached locally for 24 hours so the app
 * never makes a live HTTP call on every request.
 */
class BadWordsFilter
{
    /** Remote list – MIT-licensed, community-maintained (970+ words). */
    private const WORD_LIST_URL =
        'https://raw.githubusercontent.com/coffee-and-fun/google-profanity-words/main/data/en.txt';

    // Custom words now fetched dynamcially from the database table 'banned_words'.

    private array $alwaysBlocked = [
        'kill', 'murder', 'stab', 'shoot', 'bomb', 'attack',
        'assassinate', 'slaughter', 'massacre', 'execute',
        'i will kill', 'i ll kill', 'gonna kill', 'want to kill',
        'i will hurt', 'i will shoot', 'i will stab',
        'blow up', 'blow yourself up',
        'kill yourself', 'kys', 'go kill yourself', 'end your life',
        'commit suicide', 'go die', 'you should die',
        'i hate you', 'die already', 'hope you die',
        'terrorist', 'jihad', 'suicide bomber',
    ];

    private array $whitelist = [
        'hell', 'cum', 'ho', 'bum', 'poo', 'crap', 'damn',
    ];

    private string $cacheFile;
    private ?array $words = null;
    private $em;

    private const LEET_MAP = [
        '@'  => 'a', '4'  => 'a', '8'  => 'b', '('  => 'c', '{'  => 'c', '['  => 'c', '<'  => 'c',
        '3'  => 'e', '€'  => 'e', '6'  => 'g', '#'  => 'h', '!'  => 'i', '1'  => 'i', '|'  => 'i',
        '0'  => 'o', 'ø'  => 'o', '$'  => 's', '5'  => 's', '§'  => 's', '+'  => 't', '7'  => 't',
        'ü'  => 'u', 'ú'  => 'u', '2'  => 'z',
    ];

    public function __construct(string $kernelCacheDir, EntityManagerInterface $em)
    {
        $this->cacheFile = $kernelCacheDir . '/bad_words_cache.txt';
        $this->em = $em;
    }

    public function findViolation(string $text): ?string
    {
        $words = $this->getWords();
        $variants = $this->buildVariants($text);
        $lowerOriginal = mb_strtolower($text);

        foreach ($words as $bad) {
            if (in_array(mb_strtolower($bad), $this->whitelist, true)) continue;

            $normalBad = $this->normalize($bad);
            if ($normalBad === '') continue;

            $escaped = preg_quote($bad, '/');
            if (preg_match('/\b' . $escaped . '\b/iu', $lowerOriginal)) return $bad;

            if (mb_strlen($normalBad) >= 4) {
                foreach ($variants as $variant) {
                    if (str_contains($variant, $normalBad)) return $bad;
                }
            }
        }
        return null;
    }

    private function buildVariants(string $text): array
    {
        $lower = mb_strtolower($text);
        return array_unique([
            $this->normalize($lower),
            $this->normalize(preg_replace('/[^a-z0-9]/u', '', $lower)),
            $this->normalize($this->collapseRepeats($lower)),
            $this->normalize($this->decodeLeet($lower)),
            $this->normalize($this->collapseRepeats($this->decodeLeet($lower))),
        ]);
    }

    private function decodeLeet(string $text): string { return strtr($text, self::LEET_MAP); }
    private function collapseRepeats(string $text): string { return (string) preg_replace('/(.)\1+/u', '$1', $text); }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = (string) preg_replace('/[^a-z0-9]/u', '', $text);
        $text = (string) preg_replace('/(.)\1{2,}/u', '$1$1', $text);
        return $text;
    }

    private function getWords(): array
    {
        if ($this->words !== null) return $this->words;

        if (file_exists($this->cacheFile) && (time() - filemtime($this->cacheFile)) < 86400) {
            $this->words = $this->parseWordList(file_get_contents($this->cacheFile));
        } elseif ($remote = $this->fetchRemote(self::WORD_LIST_URL)) {
            file_put_contents($this->cacheFile, $remote);
            $this->words = $this->parseWordList($remote);
        } elseif (file_exists($this->cacheFile)) {
            $this->words = $this->parseWordList(file_get_contents($this->cacheFile));
        } else {
            $this->words = [];
        }

        $dynamicWords = $this->em->getRepository(BannedWord::class)->findAll();
        $custom = [];
        foreach ($dynamicWords as $bw) {
            $custom[] = mb_strtolower($bw->getWord());
        }

        $always = array_map('mb_strtolower', $this->alwaysBlocked);
        $this->words = array_unique(array_merge($this->words, $custom, $always));
        return $this->words;
    }

    private function parseWordList(string $raw): array
    {
        $lines = explode("\n", str_replace("\r", '', $raw));
        $words = [];
        foreach ($lines as $line) {
            if (($trimmed = trim($line)) !== '') $words[] = mb_strtolower($trimmed);
        }
        return array_unique($words);
    }

    private function fetchRemote(string $url): ?string
    {
        $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true, 'header' => "User-Agent: Symfony-BadWordsFilter/1.0\r\n"]]);
        $body = @file_get_contents($url, false, $context);
        return ($body !== false && strlen($body) > 100) ? $body : null;
    }
}
