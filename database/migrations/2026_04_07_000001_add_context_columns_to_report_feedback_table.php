<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_feedback', function (Blueprint $table) {
            $table->string('summary_type')->nullable()->after('feedback');
            $table->string('category')->nullable()->after('summary_type');
            $table->smallInteger('item_index')->unsigned()->nullable()->after('category');
            $table->text('item_text')->nullable()->after('item_index');
            $table->json('proposed_summary')->nullable()->after('item_text');
            $table->string('resolution')->default('pending')->after('proposed_summary');
        });
    }

    public function down(): void
    {
        Schema::table('report_feedback', function (Blueprint $table) {
            $table->dropColumn([
                'summary_type',
                'category',
                'item_index',
                'item_text',
                'proposed_summary',
                'resolution',
            ]);
        });
    }
};
