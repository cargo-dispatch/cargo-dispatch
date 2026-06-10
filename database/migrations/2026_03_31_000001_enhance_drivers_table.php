<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {

            // ── Onboarding & status ──────────────────────────────────────────
            $table->enum('status', [
                'invited',
                'pending_review',
                'active',
                'rejected',
                'suspended',
                'inactive',
            ])->default('invited')->after('email');

            $table->enum('onboarding_status', [
                'invited',
                'profile_incomplete',
                'docs_submitted',
                'under_review',
                'approved',
                'rejected',
            ])->default('invited')->after('status');

            $table->timestamp('invited_at')->nullable()->after('onboarding_status');
            $table->unsignedBigInteger('invited_by')->nullable()->after('invited_at'); // admin user_id
            $table->timestamp('approved_at')->nullable()->after('invited_by');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('approved_by');

            // ── Personal info ───────────────────────────────────────────────
            $table->date('date_of_birth')->nullable()->after('rejection_reason');
            $table->string('ssn_last4', 4)->nullable()->after('date_of_birth');   // last 4 of SSN for compliance
            $table->string('address')->nullable()->after('ssn_last4');
            $table->string('city')->nullable()->after('address');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip', 10)->nullable()->after('state');
            $table->string('profile_photo')->nullable()->after('zip');

            // ── CDL (Commercial Driver License) ──────────────────────────────
            $table->string('cdl_number')->nullable()->after('profile_photo');
            $table->string('cdl_state', 2)->nullable()->after('cdl_number');
            $table->enum('cdl_class', ['A', 'B', 'C'])->nullable()->after('cdl_state');
            $table->date('cdl_expiry_date')->nullable()->after('cdl_class');
            $table->json('cdl_endorsements')->nullable()->after('cdl_expiry_date'); // ['H','N','T','X']
            $table->string('cdl_restriction')->nullable()->after('cdl_endorsements');

            // ── Medical & drug test ─────────────────────────────────────────
            $table->date('medical_card_expiry')->nullable()->after('cdl_restriction');
            $table->date('drug_test_date')->nullable()->after('medical_card_expiry');
            $table->enum('drug_test_status', ['pending','passed','failed'])->nullable()->after('drug_test_date');
            $table->date('mvr_date')->nullable()->after('drug_test_status'); // Motor Vehicle Record

            // ── Experience & preference ──────────────────────────────────────
            $table->unsignedSmallInteger('years_experience')->nullable()->after('mvr_date');
            $table->unsignedBigInteger('preferred_truck_type_id')->nullable()->after('years_experience');
            $table->json('equipment_types')->nullable()->after('preferred_truck_type_id'); // ['Dry Van','Flatbed']

            // ── Pay ──────────────────────────────────────────────────────────
            $table->enum('pay_type', ['per_mile','per_load','percentage','hourly'])->nullable()->after('equipment_types');
            $table->decimal('pay_rate', 8, 4)->nullable()->after('pay_type');

            // Indexes for common filter/sort queries
            $table->index('status');
            $table->index('onboarding_status');
            $table->index('current_duty_status');
            $table->index('cdl_expiry_date');
            $table->index('medical_card_expiry');
            $table->index('preferred_truck_type_id');

            // Foreign keys
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('preferred_truck_type_id')->references('id')->on('vehicle_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['onboarding_status']);
            $table->dropIndex(['current_duty_status']);
            $table->dropIndex(['cdl_expiry_date']);
            $table->dropIndex(['medical_card_expiry']);
            $table->dropForeign(['invited_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['preferred_truck_type_id']);
            $table->dropColumn([
                'status', 'onboarding_status', 'invited_at', 'invited_by',
                'approved_at', 'approved_by', 'rejection_reason',
                'date_of_birth', 'ssn_last4', 'address', 'city', 'state', 'zip', 'profile_photo',
                'cdl_number', 'cdl_state', 'cdl_class', 'cdl_expiry_date', 'cdl_endorsements', 'cdl_restriction',
                'medical_card_expiry', 'drug_test_date', 'drug_test_status', 'mvr_date',
                'years_experience', 'preferred_truck_type_id', 'equipment_types',
                'pay_type', 'pay_rate',
            ]);
        });
    }
};
