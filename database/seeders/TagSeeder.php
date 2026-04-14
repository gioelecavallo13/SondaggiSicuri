<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        foreach (require database_path('data/default_tags.php') as [$nome, $slug]) {
            Tag::query()->firstOrCreate(
                ['slug' => $slug],
                ['nome' => $nome]
            );
        }
    }
}
