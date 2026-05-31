<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Reads the role manuals from docs/handleidingen and renders them to HTML so
 * the teacher can read and print them from within the app.
 */
class Manuals
{
    /**
     * Friendly tab labels and display order per manual slug. Unknown slugs are
     * appended alphabetically with their filename as label.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'README' => 'Overzicht',
        'docent' => 'Docent',
        'technicus' => 'Technicus',
        'leidinggevende' => 'Leidinggevende',
        'klant' => 'Klant',
    ];

    /**
     * All available manuals as a list of {slug, label}.
     *
     * @return Collection<int, array{slug: string, label: string}>
     */
    public function all(): Collection
    {
        $order = array_keys(self::LABELS);

        return collect(glob($this->directory().'/*.md'))
            ->map(fn (string $path): string => basename($path, '.md'))
            ->sortBy(fn (string $slug): string => sprintf('%02d-%s', array_search($slug, $order, true) === false ? 99 : array_search($slug, $order, true), $slug))
            ->map(fn (string $slug): array => ['slug' => $slug, 'label' => $this->label($slug)])
            ->values();
    }

    /**
     * Whether a manual with this slug exists (and the slug is safe).
     */
    public function exists(string $slug): bool
    {
        return $this->path($slug) !== null;
    }

    /**
     * The friendly label for a slug.
     */
    public function label(string $slug): string
    {
        return self::LABELS[$slug] ?? Str::headline($slug);
    }

    /**
     * The manual rendered to HTML, or null when it does not exist.
     */
    public function html(string $slug): ?string
    {
        $path = $this->path($slug);

        if ($path === null) {
            return null;
        }

        return Str::markdown(
            (string) file_get_contents($path),
            ['html_input' => 'escape', 'allow_unsafe_links' => false],
        );
    }

    /**
     * Resolve a slug to a file path, guarding against path traversal.
     */
    private function path(string $slug): ?string
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            return null;
        }

        $path = $this->directory().'/'.$slug.'.md';

        return is_file($path) ? $path : null;
    }

    private function directory(): string
    {
        return base_path('docs/handleidingen');
    }
}
