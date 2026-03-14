<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User; // Import the User model

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if the column already exists
        if (!Schema::hasColumn('users', 'slug')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('email');
            });

            // Generate slugs for existing users
            $users = User::all();
            foreach ($users as $user) {
                // Create a unique slug by combining name and ID to ensure uniqueness
                $slug = Str::slug($user->name) . '-' . $user->id;
                $user->update(['slug' => $slug]);
            }

            // Make the slug column unique after populating
            Schema::table('users', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']); // Drop the unique index first
            $table->dropColumn('slug');
        });
    }
};