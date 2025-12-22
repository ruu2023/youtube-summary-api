<?php

namespace App\Services;

use App\Models\Category;

class CategoryMatcherService
{
    /**
     * Match category based on title and description
     *
     * @param string $title
     * @param string|null $description
     * @param \Illuminate\Database\Eloquent\Collection $categories
     * @return int|null
     */
    public function match(string $title, ?string $description, $categories): ?int
    {
        $text = $title . ' ' . ($description ?? '');
        
        foreach ($categories as $category) {
            $keywords = $category->keywords;
            
            if (empty($keywords) || !is_array($keywords)) {
                continue;
            }

            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return $category->id;
                }
            }
        }

        return null;
    }
}
