<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // email, slack, database
            $table->string('event_type'); // application_submitted, approval_requested, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // related model data
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'type', 'status']);
            $table->index(['user_id', 'read_at']);
            $table->index(['event_type', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};