<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slack_webhook_url')->nullable()->after('email');
            $table->json('notification_preferences')->nullable()->after('slack_webhook_url');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['slack_webhook_url', 'notification_preferences']);
        });
    }
};