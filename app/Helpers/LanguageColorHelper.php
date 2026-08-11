<?php

namespace App\Helpers;

class LanguageColorHelper
{
    /**
     * Language color mapping for consistent colors across the application.
     */
    private static array $languageColors = [
        'PHP' => 'var(--lang-php)',
        'JavaScript' => 'var(--lang-javascript)',
        'TypeScript' => 'var(--lang-typescript)',
        'Python' => 'var(--lang-python)',
        'Java' => 'var(--lang-java)',
        'C#' => 'var(--lang-csharp)',
        'C++' => 'var(--lang-cpp)',
        'C' => 'var(--lang-c)',
        'Go' => 'var(--lang-go)',
        'Rust' => 'var(--lang-rust)',
        'Swift' => 'var(--lang-swift)',
        'Kotlin' => 'var(--lang-kotlin)',
        'Ruby' => 'var(--lang-ruby)',
        'HTML' => 'var(--lang-html)',
        'CSS' => 'var(--lang-css)',
        'SCSS' => 'var(--lang-scss)',
        'Vue' => 'var(--lang-vue)',
        'React' => 'var(--lang-react)',
        'Angular' => 'var(--lang-angular)',
        'Dart' => 'var(--lang-dart)',
        'Flutter' => 'var(--lang-flutter)',
        'Shell' => 'var(--lang-shell)',
        'Dockerfile' => 'var(--lang-dockerfile)',
        'YAML' => 'var(--lang-yaml)',
        'JSON' => 'var(--lang-json)',
        'XML' => 'var(--lang-xml)',
        'Markdown' => 'var(--lang-markdown)',
    ];

    /**
     * Get the color for a specific language.
     */
    public static function getLanguageColor(string $language): string
    {
        return self::$languageColors[$language] ?? 'var(--lang-default)';
    }

    /**
     * Get the CSS variable name for a language.
     */
    public static function getLanguageCssVar(string $language): string
    {
        $color = self::getLanguageColor($language);
        return str_replace(['var(', ')'], '', $color);
    }

    /**
     * Get all supported language colors.
     */
    public static function getAllLanguageColors(): array
    {
        return self::$languageColors;
    }

    /**
     * Check if a language has a defined color.
     */
    public static function hasLanguageColor(string $language): bool
    {
        return isset(self::$languageColors[$language]);
    }

    /**
     * Generate a language badge HTML with consistent styling.
     */
    public static function getLanguageBadge(string $language, bool $showDot = true): string
    {
        $color = self::getLanguageColor($language);
        $dot = $showDot ? '<span class="w-2 h-2 rounded-full" style="background: ' . $color . ';"></span>' : '';
        
        return '<span class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--border-color)] bg-[var(--bg-muted)] px-2.5 py-1 text-xs font-medium text-[var(--text-secondary)]">'
             . $dot . htmlspecialchars($language) . '</span>';
    }

    /**
     * Generate language progress bar HTML.
     */
    public static function getLanguageProgressBar(string $language, int $count, int $total, bool $showPercentage = true): string
    {
        $color = self::getLanguageColor($language);
        $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
        $displayPercentage = $showPercentage ? " · {$percentage}%" : '';
        
        return '
        <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
                <span class="font-medium text-[var(--text-primary)]">' . htmlspecialchars($language) . '</span>
                <span class="text-[var(--text-muted)]">' . $count . ' repos' . $displayPercentage . '</span>
            </div>
            <div class="h-2 bg-[var(--bg-muted)] rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700" 
                     style="width: ' . $percentage . '%; background: ' . $color . ';"></div>
            </div>
        </div>';
    }
}