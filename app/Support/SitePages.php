<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Admin-editable text for the public info pages (terms, privacy, returns, shipping, FAQ).
 *
 * The text is stored in PlatformSettings ('pages' group) in a tiny markup:
 *   # title · ## section title · ### sub title · - bullet · 1. numbered item
 *   **bold** · [link text](url) · {commission} style tokens replaced with live values.
 * When nothing is stored, the pages keep showing their built-in default content.
 */
class SitePages
{
    public const PAGES = [
        'terms' => ['الشروط والأحكام', 'pages.terms'],
        'privacy' => ['سياسة الخصوصية', 'pages.privacy'],
        'returns' => ['سياسة الاسترجاع', 'pages.returns'],
        'shipping' => ['الشحن والتوصيل', 'pages.shipping'],
        'faq' => ['الأسئلة الشائعة', 'pages.faq'],
    ];

    public const TOKENS = [
        'commission' => 'نسبة عمولة المنصة',
        'min_withdrawal' => 'الحد الأدنى للسحب',
        'shipping_cost' => 'رسوم الشحن',
        'return_days' => 'مهلة الاسترجاع (أيام)',
        'email' => 'بريد الدعم',
        'phone' => 'رقم التواصل',
    ];

    private static bool $forceDefault = false;

    /** The admin's saved text for a page, or '' when the built-in default should be shown. */
    public static function custom(string $key): string
    {
        if (self::$forceDefault) {
            return '';
        }

        return trim((string) PlatformSettings::get('pages', $key, ''));
    }

    public static function tokenValues(): array
    {
        return [
            'commission' => PlatformSettings::get('fees', 'platform_commission', 12).'%',
            'min_withdrawal' => PlatformSettings::get('fees', 'minimum_withdrawal', 100).' ₪',
            'shipping_cost' => number_format((float) PlatformSettings::get('fees', 'shipping_cost', 5), 2).' ₪',
            'return_days' => (string) PlatformSettings::get('site', 'return_days', 7),
            'email' => (string) PlatformSettings::get('general', 'admin_email', ''),
            'phone' => (string) PlatformSettings::get('general', 'contact_phone', ''),
        ];
    }

    /** Text shown in the admin editor: the saved text, or the default converted back to markup. */
    public static function editorText(string $key): string
    {
        return self::custom($key) ?: self::defaultText($key);
    }

    public static function defaultText(string $key): string
    {
        if (! isset(self::PAGES[$key])) {
            return '';
        }

        self::$forceDefault = true;
        try {
            $html = view(self::PAGES[$key][1])->render();
        } catch (\Throwable) {
            return '';
        } finally {
            self::$forceDefault = false;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $article = $doc->getElementsByTagName('article')->item(0);
        if (! $article) {
            return '';
        }

        $lines = [];
        foreach ($article->getElementsByTagName('section') as $section) {
            if ($key === 'faq' && ! str_contains($section->getAttribute('class'), 'about-faq')) {
                continue;
            }
            // The table of contents relies on section ids that the editable text does not keep.
            $heading = $section->getElementsByTagName('h2')->item(0);
            if ($heading && trim($heading->textContent) === 'المحتويات') {
                continue;
            }
            self::sectionToLines($section, $lines);
        }

        return trim(implode("\n", $lines));
    }

    private static function sectionToLines(DOMElement $section, array &$lines): void
    {
        foreach ($section->childNodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $tag = $node->tagName;
            if ($tag === 'details') {
                $lines[] = '## '.self::inline($node->getElementsByTagName('summary')->item(0));
                foreach ($node->childNodes as $child) {
                    if ($child instanceof DOMElement && $child->tagName === 'p') {
                        $lines[] = self::inline($child);
                    }
                }
                $lines[] = '';
            } elseif (in_array($tag, ['h1', 'h2', 'h3'], true)) {
                $lines[] = ['h1' => '# ', 'h2' => '## ', 'h3' => '### '][$tag].self::inline($node);
            } elseif ($tag === 'p') {
                $lines[] = self::inline($node);
                $lines[] = '';
            } elseif ($tag === 'ul' || $tag === 'ol') {
                $number = 1;
                foreach ($node->childNodes as $li) {
                    if (! $li instanceof DOMElement || $li->tagName !== 'li') {
                        continue;
                    }
                    if ($li->getElementsByTagName('h3')->length) {
                        // Step cards (title + description) become a sub title and a paragraph.
                        foreach ($li->childNodes as $part) {
                            if ($part instanceof DOMElement) {
                                $lines[] = ($part->tagName === 'h3' ? '### ' : '').self::inline($part);
                            }
                        }
                    } else {
                        $lines[] = ($tag === 'ol' ? ($number++).'. ' : '- ').self::inline($li);
                    }
                }
                $lines[] = '';
            } elseif ($tag === 'div') {
                $links = [];
                foreach ($node->getElementsByTagName('a') as $anchor) {
                    $links[] = self::inline($anchor);
                }
                if ($links) {
                    $lines[] = implode(' · ', $links);
                    $lines[] = '';
                }
            }
        }
    }

    private static function inline(?DOMNode $node): string
    {
        if (! $node) {
            return '';
        }

        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $text = self::inline($child);
                if (in_array($child->tagName, ['strong', 'b'], true)) {
                    $out .= '**'.trim($text).'** ';
                } elseif ($child->tagName === 'a') {
                    $out .= '['.trim($text).']('.self::relative($child->getAttribute('href')).')';
                } else {
                    $out .= $text;
                }
            } else {
                $out .= preg_replace('/\s+/u', ' ', $child->textContent);
            }
        }

        return trim(preg_replace('/\s+/u', ' ', $out));
    }

    /** Turns this site's absolute links back into relative ones so the saved text survives a domain change. */
    private static function relative(string $href): string
    {
        $parts = parse_url($href);
        $host = $parts['host'] ?? null;
        if (! $host) {
            return $href;
        }
        if (! in_array($host, [parse_url(url('/'), PHP_URL_HOST), request()->getHost()], true)) {
            return $href;
        }

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return ($parts['path'] ?? '/').$query.$fragment;
    }

    // ---------------------------------------------------------------- rendering

    /** Renders stored markup into the same .about-block sections the built-in pages use. */
    public static function render(string $text): string
    {
        $html = '<section class="about-block">';
        $list = null;

        $closeList = function () use (&$html, &$list) {
            if ($list) {
                $html .= '</'.$list.'>';
                $list = null;
            }
        };

        foreach (explode("\n", self::prepare($text)) as $line) {
            $line = trim($line);
            if ($line === '') {
                $closeList();
            } elseif (str_starts_with($line, '### ')) {
                $closeList();
                $html .= '<h3>'.self::inlineHtml(substr($line, 4)).'</h3>';
            } elseif (str_starts_with($line, '## ')) {
                $closeList();
                $html .= '</section><section class="about-block"><h2>'.self::inlineHtml(substr($line, 3)).'</h2>';
            } elseif (str_starts_with($line, '# ')) {
                $closeList();
                $html .= '<h1>'.self::inlineHtml(substr($line, 2)).'</h1>';
            } elseif (preg_match('/^[-*] (.+)$/u', $line, $m)) {
                if ($list !== 'ul') {
                    $closeList();
                    $html .= '<ul class="about-list">';
                    $list = 'ul';
                }
                $html .= '<li>'.self::inlineHtml($m[1]).'</li>';
            } elseif (preg_match('/^\d+[.)] (.+)$/u', $line, $m)) {
                if ($list !== 'ol') {
                    $closeList();
                    $html .= '<ol class="about-list">';
                    $list = 'ol';
                }
                $html .= '<li>'.self::inlineHtml($m[1]).'</li>';
            } else {
                $closeList();
                $html .= '<p>'.self::inlineHtml($line).'</p>';
            }
        }
        $closeList();

        return $html.'</section>';
    }

    /** FAQ markup ("## question" followed by answer lines) rendered as the page's <details> list. */
    public static function renderFaq(string $text): string
    {
        $items = [];
        $current = null;
        foreach (explode("\n", self::prepare($text)) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '## ')) {
                $current = count($items);
                $items[$current] = ['q' => substr($line, 3), 'a' => []];
            } elseif ($line !== '' && $current !== null) {
                $items[$current]['a'][] = $line;
            }
        }

        $html = '';
        foreach ($items as $item) {
            $html .= '<details><summary>'.self::inlineHtml($item['q']).'</summary>';
            foreach ($item['a'] as $answer) {
                $html .= '<p>'.self::inlineHtml($answer).'</p>';
            }
            $html .= '</details>';
        }

        return $html;
    }

    private static function prepare(string $text): string
    {
        $values = self::tokenValues();

        return preg_replace_callback(
            '/\{(\w+)\}/',
            fn ($m) => $values[$m[1]] ?? $m[0],
            str_replace(["\r\n", "\r"], "\n", $text)
        );
    }

    private static function inlineHtml(string $text): string
    {
        $text = e($text);
        $text = preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text);

        return preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
            $url = html_entity_decode($m[2]);
            if (! preg_match('#^(/|\#|https?://|mailto:|tel:)#i', $url)) {
                return $m[0];
            }

            return '<a class="about-link" href="'.e($url).'">'.$m[1].'</a>';
        }, $text);
    }
}
