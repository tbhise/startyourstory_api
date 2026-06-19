<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reusable social-media caption for a blog post.
     *
     * Stores the common caption used when sharing the blog across WhatsApp,
     * LinkedIn and Twitter/X. The blog URL is NOT stored — it is generated
     * dynamically from the slug at copy time. Nullable + guarded so it is safe
     * on every environment and existing blogs continue working unchanged.
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            if (!Schema::hasColumn('blogs', 'social_caption')) {
                $table->text('social_caption')->nullable()->after('meta_description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            if (Schema::hasColumn('blogs', 'social_caption')) {
                $table->dropColumn('social_caption');
            }
        });
    }
};
