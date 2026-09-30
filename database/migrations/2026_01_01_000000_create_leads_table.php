<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable(); // freelance platforms often hide it
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // new, contacted, follow_up, won, lost
            $table->string('source')->default('landing_page'); // landing_page, manual, outbound
            $table->string('platform')->default('website'); // website, linkedin, upwork, fiverr, referral, email, other
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
