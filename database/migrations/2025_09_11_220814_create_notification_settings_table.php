<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // application_submitted, approval_requested, approved, rejected, etc.
            $table->json('channels'); // ['email', 'slack', 'database']
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable(); // channel-specific configuration
            $table->timestamps();
            
            $table->unique(['user_id', 'event_type']);
            $table->index(['user_id', 'is_enabled']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_settings');
    }
};