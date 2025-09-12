<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications');
            $table->foreignId('approval_flow_id')->constrained('approval_flows');
            $table->foreignId('approver_id')->constrained('users');
            $table->integer('step_number');
            $table->enum('step_type', ['review', 'approve']); // 確認 or 承認
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
            
            $table->unique(['application_id', 'step_number']);
            $table->index(['approver_id', 'status']);
            $table->index(['application_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};