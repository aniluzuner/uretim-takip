<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
  public function up(): void{
    Schema::table('production_steps', function (Blueprint $table) {
      $table->float('time', 8, 2)->change();
    });
  }

  public function down(): void{
    Schema::table('production_steps', function (Blueprint $table) {
      $table->integer('time')->change();
    });
  }
};
