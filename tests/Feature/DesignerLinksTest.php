<?php

use App\Models\Category;
use App\Models\Design;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(RoleAndPermissionSeeder::class);

    $this->designer = User::factory()->create(['is_active' => true]);
    $this->designer->assignRole('designer');
    $this->designer->designerProfile()->create(['full_name' => $this->designer->name, 'approval_status' => 'approved']);

    $category = Category::create(['name' => 'ملابس', 'slug' => 'clothes']);
    $this->product = Product::create(['category_id' => $category->id, 'name' => 'هودي', 'code' => 'HOODIE-PREMIUM', 'is_active' => true]);
});

/** Every plain GET page of the designer area (no URL parameters), by route name. */
function designerPages(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true)
            && str_starts_with((string) $route->getName(), 'designer.')
            && ! str_contains($route->uri(), '{'))
        ->map(fn ($route) => $route->getName())
        ->values()
        ->all();
}

/** The same-site paths a page links to: <a href>, <form action> and <link href> are all looked at. */
function internalTargets(string $html): array
{
    preg_match_all('/<(?:a|form)\b[^>]*\b(?:href|action)="([^"]+)"/i', $html, $matches);
    $host = parse_url(config('app.url'), PHP_URL_HOST);
    $paths = [];

    foreach ($matches[1] as $url) {
        $url = html_entity_decode($url);
        if (preg_match('/^(#|javascript:|mailto:|tel:|data:)/i', $url)) {
            continue;
        }
        $parts = parse_url($url);
        if (isset($parts['host']) && $parts['host'] !== $host && $parts['host'] !== '127.0.0.1' && $parts['host'] !== 'localhost') {
            continue; // another site
        }
        $path = $parts['path'] ?? '/';
        if (str_starts_with($path, '/front/') || str_starts_with($path, '/storage/')) {
            continue; // static files
        }
        $paths[] = $path;
    }

    return array_values(array_unique($paths));
}

test('every designer page opens', function () {
    foreach (designerPages() as $name) {
        $status = $this->actingAs($this->designer)->get(route($name))->status();
        expect($status)->toBe(200, "{$name} answered {$status}");
    }
});

test('every link and form on the designer pages points at a route that exists', function () {
    $design = Design::create([
        'designer_id' => $this->designer->id, 'product_id' => $this->product->id, 'title' => 'تصميم', 'description' => 'x',
        'base_price' => 10, 'selling_price' => 25, 'designer_profit' => 15, 'status' => 'draft',
        'selected_options' => ['display_category' => 'adults', 'allowed_size_ids' => ['M'], 'allowed_color_ids' => ['black']],
    ]);
    Notification::create(['user_id' => $this->designer->id, 'type' => 'design_review', 'title' => 'a', 'message' => 'b', 'link' => route('designer.designs.index')]);

    $pages = collect(designerPages())->map(fn ($name) => route($name, [], false))
        ->push(route('designer.designs.show', $design, false), route('design-studio', ['edit' => $design->id], false), route('design-studio', [], false))
        ->unique()->values();

    $broken = [];
    $router = app('router');

    foreach ($pages as $page) {
        $response = $this->actingAs($this->designer)->get($page);
        if ($response->status() !== 200) {
            $broken[] = "{$page} itself answered {$response->status()}";

            continue;
        }

        foreach (internalTargets($response->getContent()) as $target) {
            // A link must lead to a route that exists for a GET (forms may be POST/PATCH/DELETE, checked by name below).
            $request = Illuminate\Http\Request::create($target, 'GET');
            try {
                $router->getRoutes()->match($request);
            } catch (Symfony\Component\HttpKernel\Exception\NotFoundHttpException|Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $exception) {
                if ($exception instanceof Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    continue; // exists, but for another verb (a form action)
                }
                $broken[] = "{$page} -> {$target} (no such route)";

                continue;
            }

            // And a link a designer can click must open for them.
            $status = $this->actingAs($this->designer)->get($target)->status();
            if (! in_array($status, [200, 302], true)) {
                $broken[] = "{$page} -> {$target} answered {$status}";
            }
        }
    }

    expect($broken)->toBe([]);
});

test('the designer pages are closed to visitors, customers and print shops', function () {
    $customer = User::factory()->create(['is_active' => true]);
    $customer->assignRole('customer');
    $shop = User::factory()->create(['is_active' => true]);
    $shop->assignRole('print_provider');

    foreach (designerPages() as $name) {
        auth()->logout();
        $this->get(route($name))->assertRedirect(route('login'));
        $this->actingAs($customer)->get(route($name))->assertForbidden();
        $this->actingAs($shop)->get(route($name))->assertForbidden();
    }
});

test('a designer cannot open another designer\'s design page, files or studio copy', function () {
    $other = User::factory()->create(['is_active' => true]);
    $other->assignRole('designer');
    $other->designerProfile()->create(['full_name' => 'x', 'approval_status' => 'approved']);
    $design = Design::create(['designer_id' => $other->id, 'product_id' => $this->product->id, 'title' => 'لغيري', 'description' => 'x', 'base_price' => 10, 'selling_price' => 25, 'designer_profit' => 15, 'status' => 'draft']);

    $this->actingAs($this->designer)->get(route('designer.designs.show', $design))->assertNotFound();
    $this->actingAs($this->designer)->get(route('design-studio', ['edit' => $design->id]))->assertNotFound();
    $this->actingAs($this->designer)->get(route('designer.designs.file', [$design, 'x']))->assertNotFound();
});

test('the sidebar of every designer page lists the same destinations', function () {
    $sets = collect(designerPages())->map(function ($name) {
        $html = $this->actingAs($this->designer)->get(route($name))->getContent();
        preg_match('/<aside.*?<\/aside>/s', $html, $aside);

        return collect(internalTargets($aside[0] ?? ''))->sort()->values()->all();
    })->filter()->unique(fn ($set) => json_encode($set));

    expect($sets)->toHaveCount(1);
});
