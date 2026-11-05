<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a back-office account may open, and which member of the team it is.
 *
 * The role defaults to the owner because the accounts this finds were all
 * created before roles existed · every new one writes its role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            $table->string('role', 20)->default('owner')->after('password');
            $table->foreignId('practitioner_id')->nullable()->after('role');
            $table->dateTime('invited_at')->nullable()->after('practitioner_id');
            $table->dateTime('invitation_failed_at')->nullable()->after('invited_at');
            $table->dateTime('suspended_at')->nullable()->after('invitation_failed_at');

            $table->unique('practitioner_id', 'admins_practitioner_uq');
            $table->foreign('practitioner_id', 'admins_practitioner_fk')
                ->references('id')
                ->on('falcon_booking_practitioners')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_chk CHECK (role IN ('owner', 'practitioner'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admins DROP CONSTRAINT admins_role_chk');

        Schema::table('admins', function (Blueprint $table): void {
            $table->dropForeign('admins_practitioner_fk');
            $table->dropUnique('admins_practitioner_uq');
            $table->dropColumn(['role', 'practitioner_id', 'invited_at', 'invitation_failed_at', 'suspended_at']);
        });
    }
};
