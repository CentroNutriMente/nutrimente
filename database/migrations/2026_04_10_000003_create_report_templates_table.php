<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // nome del template, es. "Scheda Primo Colloquio"
            $table->string('description')->nullable();     // descrizione breve
            $table->string('header_title');                // titolo che appare in cima al referto
            $table->string('header_subtitle')->nullable(); // sottotitolo opzionale
            $table->string('header_logo')->nullable();     // path immagine logo
            $table->json('sections');                      // array di sezioni configurabili
            $table->boolean('show_patient_fields')->default(true); // mostra nome/data/numero in header
            $table->boolean('show_professional_footer')->default(true); // mostra firma professionista
            $table->text('footer_note')->nullable();       // nota libera nel footer
            $table->boolean('is_default')->default(false); // template preferito
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_templates');
    }
};
