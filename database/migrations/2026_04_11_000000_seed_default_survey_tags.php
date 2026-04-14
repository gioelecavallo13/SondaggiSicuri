<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->tagTuples() as [$nome, $slug]) {
            Tag::query()->firstOrCreate(
                ['slug' => $slug],
                ['nome' => $nome]
            );
        }
    }

    public function down(): void
    {
        $slugs = array_map(
            static fn (array $row): string => $row[1],
            $this->tagTuples()
        );
        Tag::query()->whereIn('slug', $slugs)->delete();
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function tagTuples(): array
    {
        return require database_path('data/default_tags.php');
    }
};
