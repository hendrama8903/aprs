<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu supplier bisa punya beberapa alamat surat (kantor pusat, plant, ...), masing-masing dengan penerima sendiri.
        Schema::create('supplier_addresses', function (Blueprint $table) {
            $table->id('address_id');
            $table->foreignId('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();
            $table->string('label', 50);
            $table->string('address', 255);
            $table->string('attention_name', 100)->nullable();
            $table->string('attention_title', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('created_by', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->string('updated_by', 50)->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->index(['supplier_id', 'is_active']);
        });

        // Snapshot alamat surat di dokumen APR (diambil saat disimpan), supaya surat yang sudah
        // terbit tidak berubah walaupun master supplier diedit / alamatnya dihapus.
        Schema::table('aprs', function (Blueprint $table) {
            $table->foreignId('supplier_address_id')->nullable()->after('supplier_id')
                ->references('address_id')->on('supplier_addresses')->nullOnDelete();
            $table->string('letter_address_label', 50)->nullable()->after('supplier_address_id');
            $table->string('letter_address', 255)->nullable()->after('letter_address_label');
            $table->string('letter_attention_name', 100)->nullable()->after('letter_address');
            $table->string('letter_attention_title', 100)->nullable()->after('letter_attention_name');
        });

        // Alamat lama (satu per supplier) menjadi alamat default.
        $now = now();
        foreach (DB::table('suppliers')->get() as $s) {
            if ($s->address === null && $s->attention_name === null && $s->email === null) {
                continue;
            }
            $addressId = DB::table('supplier_addresses')->insertGetId([
                'supplier_id' => $s->supplier_id,
                'label' => 'Main',
                'address' => $s->address ?? '-',
                'attention_name' => $s->attention_name,
                'attention_title' => $s->attention_title,
                'email' => $s->email,
                'is_default' => true,
                'is_active' => true,
                'created_by' => 'migration',
                'created_at' => $now,
            ]);
            DB::table('aprs')->where('supplier_id', $s->supplier_id)->update([
                'supplier_address_id' => $addressId,
                'letter_address_label' => 'Main',
                'letter_address' => $s->address,
                'letter_attention_name' => $s->attention_name,
                'letter_attention_title' => $s->attention_title,
            ]);
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['address', 'attention_name', 'attention_title', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('supplier_name');
            $table->string('attention_name', 100)->nullable()->after('address');
            $table->string('attention_title', 100)->nullable()->after('attention_name');
            $table->string('email', 100)->nullable()->after('attention_title');
        });

        foreach (DB::table('supplier_addresses')->where('is_default', true)->get() as $a) {
            DB::table('suppliers')->where('supplier_id', $a->supplier_id)->update([
                'address' => $a->address,
                'attention_name' => $a->attention_name,
                'attention_title' => $a->attention_title,
                'email' => $a->email,
            ]);
        }

        Schema::table('aprs', function (Blueprint $table) {
            $table->dropForeign(['supplier_address_id']);
            $table->dropColumn(['supplier_address_id', 'letter_address_label', 'letter_address', 'letter_attention_name', 'letter_attention_title']);
        });

        Schema::dropIfExists('supplier_addresses');
    }
};
