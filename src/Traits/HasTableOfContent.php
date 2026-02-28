<?php

namespace PrashantMalla\AutoToc\Traits;

use PrashantMalla\AutoToc\Models\TableOfContent;
use Illuminate\Support\Str;
use RuntimeException;

trait HasTableOfContent
{
    /**
     * Validate that the model defines the source field for TOC.
     * Fail-fast if not defined or empty.
     */
    protected static function validateTocSourceField(): void
    {
        if (! property_exists(static::class, 'tocSourceField') || empty(static::$tocSourceField)) {
            throw new RuntimeException(
                sprintf(
                    'Model [%s] must define protected static string $tocSourceField to use HasTableOfContent trait.',
                    static::class
                )
            );
        }
    }

    /**
     * Boot method for trait.
     */
    protected static function bootHasTableOfContent(): void
    {
        // Fail fast on trait usage
        static::validateTocSourceField();

        // Hook to generate TOC automatically on saving
        static::saved(function ($model) {
            $field = $model->getTocField();

            if (! empty($model->$field)) {
                $generated = $model->generateTableOfContents($model->$field);

                // Silently update source field with injected heading IDs (skip events to avoid loop)
                $model->newQuery()
                    ->where($model->getKeyName(), $model->getKey())
                    ->update([$field => $generated['content']]);

                // Save or update polymorphic TOC
                $model->toc()->updateOrCreate([], [
                    'content' => $generated['toc'] ?? [],
                ]);
            }
        });
    }

    /**
     * Polymorphic relationship to TableOfContent.
     */
    public function toc()
    {
        return $this->morphOne(TableOfContent::class, 'tocable');
    }

    /**
     * Generate Table of Contents array and inject IDs into HTML.
     *
     * @param  string  $htmlContent
     * @param  array|null  $headingLevels  Which heading tags to include (e.g. ['h2','h3','h4','h5','h6'])
     * @return array{content: string, toc: array}
     */
    protected function generateTableOfContents(string $htmlContent, ?array $headingLevels = null): array
    {
        $headingLevels = $headingLevels ?? $this->getTocHeadingLevels();

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . $htmlContent,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $toc = [];
        $usedIds = [];

        foreach ($dom->getElementsByTagName('*') as $node) {
            $tag = strtolower($node->nodeName);

            if (in_array($tag, $headingLevels, true)) {
                $text = trim($node->textContent);
                $id = $node->getAttribute('id') ?: Str::slug($text);

                // Ensure unique ID
                $uniqueId = $id;
                $counter = 1;

                while (in_array($uniqueId, $usedIds, true)) {
                    $uniqueId = $id . '-' . $counter++;
                }

                $usedIds[] = $uniqueId;
                $node->setAttribute('id', $uniqueId);

                $toc[] = [
                    'title'  => $text,
                    'anchor' => $uniqueId,
                    'level'  => (int) substr($tag, 1),
                ];
            }
        }

        libxml_clear_errors();

        // Strip the XML encoding declaration that was injected
        $output = $dom->saveHTML();
        $output = preg_replace('/^<\?xml encoding="utf-8" \?>\s*/i', '', $output);

        return [
            'content' => $output,
            'toc'     => $toc,
        ];
    }

    /**
     * Return the field to generate TOC from.
     * Each model using this trait must define $tocSourceField.
     */
    protected function getTocField(): string
    {
        if (! property_exists(static::class, 'tocSourceField') || empty(static::$tocSourceField)) {
            throw new RuntimeException(
                sprintf('Model [%s] must define protected static string $tocSourceField.', get_class($this))
            );
        }

        return static::$tocSourceField;
    }

    /**
     * Return the heading levels to extract for the TOC.
     *
     * Override $tocHeadingLevels on the model or override this method
     * to customise which headings are included.
     *
     * Falls back to the config value, which defaults to h2–h6.
     */
    protected function getTocHeadingLevels(): array
    {
        if (property_exists(static::class, 'tocHeadingLevels') && ! empty(static::$tocHeadingLevels)) {
            return static::$tocHeadingLevels;
        }

        return config('auto-toc.heading_levels', ['h2', 'h3', 'h4', 'h5', 'h6']);
    }
}
