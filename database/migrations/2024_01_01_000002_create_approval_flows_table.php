<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('application_type', ['expense', 'leave', 'purchase', 'other']);
            $table->json('conditions')->nullable(); // 条件設定 (金額範囲など)
            $table->integer('step_count')->default(2); // 承認ステップ数
            $table->json('flow_config'); // フロー設定 (各ステップの設定)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('application_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};