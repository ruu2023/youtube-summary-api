<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CategoryMatcherService;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryMatcherServiceTest extends TestCase
{
    public function test_it_can_match_category_by_title()
    {
        $service = new CategoryMatcherService();

        $category = new Category();
        $category->id = 1;
        $category->keywords = ['Apex', 'FPS'];

        $categories = new Collection([$category]);

        $result = $service->match('Today we play Apex Legends', 'description', $categories);

        $this->assertEquals(1, $result);
    }

    public function test_it_can_match_category_by_description()
    {
        $service = new CategoryMatcherService();

        $category = new Category();
        $category->id = 2;
        $category->keywords = ['Minecraft'];

        $categories = new Collection([$category]);

        $result = $service->match('Lets Play', 'Playing Minecraft with friends', $categories);

        $this->assertEquals(2, $result);
    }

    public function test_it_returns_null_if_no_match()
    {
        $service = new CategoryMatcherService();

        $category = new Category();
        $category->id = 3;
        $category->keywords = ['Cooking'];

        $categories = new Collection([$category]);

        $result = $service->match('Gaming Video', 'Just playing games', $categories);

        $this->assertNull($result);
    }

    public function test_it_matches_case_insensitive()
    {
        $service = new CategoryMatcherService();

        $category = new Category();
        $category->id = 4;
        $category->keywords = ['ZELDA'];

        $categories = new Collection([$category]);

        $result = $service->match('Playing zelda is fun', '', $categories);

        $this->assertEquals(4, $result);
    }
}
