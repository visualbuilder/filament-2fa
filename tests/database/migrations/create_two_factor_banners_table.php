<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->down();
        Schema::create('two_factor_banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('content');
            $table->string('render_location');
            $table->json('auth_guards')->nullable();
            $table->json('scope')->nullable();
            $table->boolean('can_be_closed_by_user')->default(false);
            $table->boolean('can_truncate_message')->default(false);
            $table->boolean('is_active')->default(true);
            $table->dateTime('active_since')->nullable();
            $table->string('text_color');
            $table->string('icon')->nullable();
            $table->string('icon_color')->nullable();
            $table->string('background_type')->nullable();
            $table->string('start_color')->nullable();
            $table->string('end_color')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_banners');
    }
};
