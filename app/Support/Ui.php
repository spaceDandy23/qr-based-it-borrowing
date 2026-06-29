<?php

namespace App\Support;

class Ui
{
    private const AVATAR_COLORS = ['#7a0d14', '#3c7a4a', '#8a5a12', '#5b4a8a', '#a3131c', '#4a4540'];

    private const CATEGORY_PATHS = [
        'Laptop' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M2 20h20"/>',
        'Desktop' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'Monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
        'Projector' => '<rect x="2" y="7" width="20" height="10" rx="2"/><circle cx="9" cy="12" r="3"/><path d="M17 10v4"/>',
        'Camera' => '<path d="M3 7h4l2-2h6l2 2h4v12H3z"/><circle cx="12" cy="13" r="3.5"/>',
        'Networking' => '<rect x="3" y="9" width="18" height="6" rx="1"/><path d="M7 9V6M12 9V6M17 9V6M7 18v-3M12 18v-3M17 18v-3"/>',
        'Printer' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/>',
        'Peripheral' => '<rect x="5" y="2" width="14" height="20" rx="7"/><path d="M12 6v5"/>',
        'Tablet' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/>',
        'Audio' => '<path d="M11 5 6 9H2v6h4l5 4zM15 9a4 4 0 0 1 0 6M18 6a8 8 0 0 1 0 12"/>',
    ];

    public static function avatarColor(string $name): string
    {
        $sum = array_sum(array_map('ord', str_split($name)));

        return self::AVATAR_COLORS[$sum % count(self::AVATAR_COLORS)];
    }

    public static function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->take(2)
            ->join('');
    }

    public static function category(string $category, int $size = 40, string $stroke = '#8d7e77'): string
    {
        $path = self::CATEGORY_PATHS[$category] ?? self::CATEGORY_PATHS['Peripheral'];

        return sprintf(
            '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="%2$s" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">%3$s</svg>',
            $size,
            $stroke,
            $path
        );
    }
}
