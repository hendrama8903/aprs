<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penomoran dokumen per jenis & tahun (PC-INT, PC-EXT).
        Schema::create('doc_counters', function (Blueprint $table) {
            $table->string('counter_key', 30)->primary();
            $table->integer('last_no')->default(0);
        });

        // Header dokumen Approval for Price Confirmation.
        Schema::create('aprs', function (Blueprint $table) {
            $table->id('apr_id');
            $table->string('doc_no', 50)->unique();
            $table->date('doc_date');
            $table->foreignId('supplier_id')->references('supplier_id')->on('suppliers')->restrictOnDelete();
            $table->string('part_category', 50);
            $table->string('part_status', 50);
            $table->string('model', 50);
            $table->string('background', 255);
            $table->date('effective_from');
            $table->date('effective_to');
            // Periode transaksi PO untuk qty pembelian & statistik harga.
            $table->date('purchase_from');
            $table->date('purchase_to');
            $table->decimal('rate_usd_current', 18, 4)->default(0);
            $table->decimal('rate_usd_new', 18, 4)->default(0);
            $table->decimal('rate_jpy_current', 18, 4)->default(0);
            $table->decimal('rate_jpy_new', 18, 4)->default(0);
            $table->enum('conclusion', ['ACCEPTABLE', 'NOT_ACCEPTABLE'])->default('ACCEPTABLE');
            $table->string('conclusion_note', 500)->nullable();
            // Syarat yang dicetak di surat Price Confirmation ke supplier (satu baris = satu poin).
            $table->text('letter_conditions')->nullable();
            $table->enum('status', ['DRAFT', 'IN_APPROVAL', 'REJECTED', 'APPROVED', 'ISSUED'])->default('DRAFT')->index();
            $table->integer('current_step_no')->nullable();
            $table->integer('cycle')->default(0);
            $table->foreignId('prepared_by_user_id')->references('user_id')->on('user_accounts')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('ext_doc_no', 50)->nullable()->unique();
            $table->timestamp('issued_at')->nullable();
            $table->string('issued_by', 50)->nullable();
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('apr_details', function (Blueprint $table) {
            $table->id('apr_detail_id');
            $table->foreignId('apr_id')->references('apr_id')->on('aprs')->cascadeOnDelete();
            $table->integer('line_no');
            $table->foreignId('item_id')->nullable()->references('item_id')->on('items')->nullOnDelete();
            $table->string('item_code', 20);
            $table->string('item_name', 150);
            $table->string('unit', 20)->nullable();
            $table->string('currency', 3);
            $table->decimal('qty', 18, 4)->default(0);
            $table->decimal('current_price', 18, 4);
            $table->decimal('new_price', 18, 4);
            $table->string('remark', 255)->nullable();
            // Snapshot statistik PO periode pembelian saat item ditambahkan / dihitung ulang.
            $table->integer('po_count')->default(0);
            $table->decimal('po_avg_price', 18, 4)->nullable();
            $table->decimal('po_min_price', 18, 4)->nullable();
            $table->decimal('po_max_price', 18, 4)->nullable();
            $table->decimal('po_last_price', 18, 4)->nullable();
            $table->date('po_last_date')->nullable();

            $table->unique(['apr_id', 'item_code']);
        });

        // Jejak submit / approve / reject / issue. cycle bertambah setiap submit ulang setelah reject.
        Schema::create('apr_approvals', function (Blueprint $table) {
            $table->id('apr_approval_id');
            $table->foreignId('apr_id')->references('apr_id')->on('aprs')->cascadeOnDelete();
            $table->integer('cycle');
            $table->integer('step_no')->nullable();
            $table->string('step_name', 50);
            $table->enum('action', ['SUBMIT', 'APPROVE', 'REJECT', 'ISSUE']);
            $table->foreignId('user_id')->nullable()->references('user_id')->on('user_accounts')->nullOnDelete();
            $table->string('user_name', 100);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['apr_id', 'cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apr_approvals');
        Schema::dropIfExists('apr_details');
        Schema::dropIfExists('aprs');
        Schema::dropIfExists('doc_counters');
    }
};
