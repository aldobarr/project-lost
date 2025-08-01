<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void {
		Schema::table('cards', function (Blueprint $table) {
			$table->boolean('is_errata')->default(false);
			$table->text('errata_description')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void {
		Schema::table('cards', function (Blueprint $table) {
			$table->dropColumn(['is_errata', 'errata_description']);
		});
	}
};
