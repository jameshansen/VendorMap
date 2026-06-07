<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Approval workflow: pending -> approved | rejected.
            $table->string('status')->default('pending')->after('user_id');
            // Free-text note the applicant supplies to help an admin verify them.
            $table->text('application_note')->nullable()->after('notes');
            // Profile details captured at sign-up.
            $table->text('address')->nullable()->after('phone');
            $table->string('website')->nullable()->after('address');
            // Social handles keyed by platform (facebook, instagram, x, tiktok, youtube).
            $table->json('socials')->nullable()->after('website');
            // Internal admin-only notes (not shown to the vendor).
            $table->text('admin_notes')->nullable()->after('application_note');
            // When the account was approved (for display / auditing).
            $table->dateTime('approved_at')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'application_note', 'address', 'website',
                'socials', 'admin_notes', 'approved_at',
            ]);
        });
    }
};
