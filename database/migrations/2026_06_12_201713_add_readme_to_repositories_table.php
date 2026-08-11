<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('repositories', function ($table) {
        $table->longText('readme')->nullable();
    });
}

public function down(): void
{
    Schema::table('repositories', function ($table) {
        $table->dropColumn('readme');
    });
}
};
