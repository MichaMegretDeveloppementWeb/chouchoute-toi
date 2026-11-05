<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The links that invite a member of the team, apart from the forgotten
 * passwords' · they live seven days, and one never stands for the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_invitation_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_invitation_tokens');
    }
};
