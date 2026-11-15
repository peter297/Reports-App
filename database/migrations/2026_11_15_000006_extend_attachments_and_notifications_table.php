<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enrich the legacy attachments table so it serves as report_attachments.
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreignId('uploaded_by')->nullable()->after('report_id')->constrained('users')->nullOnDelete();
            $table->string('original_name')->nullable()->after('file_path');
            $table->string('mime_type')->nullable()->after('original_name');
            $table->unsignedBigInteger('size_bytes')->nullable()->after('mime_type');
        });

        // Standard Laravel notifications table for database channel delivery.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropColumn(['original_name', 'mime_type', 'size_bytes']);
        });

        Schema::dropIfExists('notifications');
    }
};
