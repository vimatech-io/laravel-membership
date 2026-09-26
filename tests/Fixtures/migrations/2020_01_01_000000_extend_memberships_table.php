<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('seat')->nullable();
        });

        Schema::create('membership_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained('memberships');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_notes');

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('seat');
        });
    }
};
