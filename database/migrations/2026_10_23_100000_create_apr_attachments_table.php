<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dokumen pendukung APR (quotation supplier, perbandingan harga, dsb.). File disimpan di disk "local"
        // (storage/app/private/apr-attachments/{apr_id}/...), di sini hanya metadata.
        Schema::create('apr_attachments', function (Blueprint $table) {
            $table->id('apr_attachment_id');
            $table->foreignId('apr_id')->references('apr_id')->on('aprs')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->string('stored_path', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apr_attachments');
    }
};
