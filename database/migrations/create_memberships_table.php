<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('membership.tables.memberships', 'memberships'), function (Blueprint $table) {
            $table->id();

            $table->string('member_type');
            $table->unsignedBigInteger('member_id');

            $table->string('membershipable_type');
            $table->unsignedBigInteger('membershipable_id');

            $table->string('role');

            $table->timestamp('joined_at')->nullable();

            $table->string('invited_by_type')->nullable();
            $table->unsignedBigInteger('invited_by_id')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            if (config('membership.soft_deletes', false)) {
                $table->softDeletes();
            }

            $table->index(['member_type', 'member_id']);
            $table->index(['membershipable_type', 'membershipable_id']);
            $table->index(['membershipable_type', 'membershipable_id', 'role'], 'memberships_membershipable_role_index');

            $table->unique([
                'member_type',
                'member_id',
                'membershipable_type',
                'membershipable_id',
            ], 'memberships_unique_member_membershipable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('membership.tables.memberships', 'memberships'));
    }
};
