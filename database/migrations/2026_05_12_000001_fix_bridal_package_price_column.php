<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bridal_packages', 'package_pprice') && ! Schema::hasColumn('bridal_packages', 'package_price')) {
            Schema::table('bridal_packages', function (Blueprint $table) {
                $table->renameColumn('package_pprice', 'package_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bridal_packages', 'package_price') && ! Schema::hasColumn('bridal_packages', 'package_pprice')) {
            Schema::table('bridal_packages', function (Blueprint $table) {
                $table->renameColumn('package_price', 'package_pprice');
            });
        }
    }
};
