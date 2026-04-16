<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doc_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->json('content'); // { sections: [...] }
            $table->timestamps();
        });

        // Seed PSY-19 template
        $userId = DB::table('users')->value('id') ?? 1;

        DB::table('doc_templates')->insert([
            'created_by'  => $userId,
            'name'        => 'Modulo prestazione professionale psicologica (PSY-19)',
            'description' => 'Modulo standard per il consenso informato e il trattamento dati per pazienti maggiorenni.',
            'is_system'   => true,
            'content'     => json_encode([
                'sections' => [
                    // ── 1. Dati identificativi ────────────────────────────────
                    [
                        'id'       => 's1',
                        'title'    => 'Dati identificativi',
                        'optional' => false,
                        'items'    => [
                            ['id' => 's1i1', 'type' => 'field',     'label' => 'Paziente (nome e cognome)',       'placeholder' => '',                        'multiline' => false, 'optional' => false],
                            ['id' => 's1i2', 'type' => 'field',     'label' => 'Nato/a a, il',                   'placeholder' => 'città — data di nascita',  'multiline' => false, 'optional' => false],
                            ['id' => 's1i3', 'type' => 'field',     'label' => 'Residente a, in via',            'placeholder' => '',                        'multiline' => false, 'optional' => false],
                            ['id' => 's1i4', 'type' => 'field',     'label' => 'Codice fiscale',                 'placeholder' => '',                        'multiline' => false, 'optional' => false],
                            ['id' => 's1i5', 'type' => 'field',     'label' => 'Codice SDI / PEC',               'placeholder' => 'Per soggetti privati: 0000000', 'multiline' => false, 'optional' => true],
                            ['id' => 's1i6', 'type' => 'field',     'label' => 'Professionista',                 'placeholder' => 'Nome, recapiti, indirizzo, email, ecc.', 'multiline' => true, 'optional' => false],
                        ],
                    ],

                    // ── 2. Consenso informato ────────────────────────────────
                    [
                        'id'       => 's2',
                        'title'    => 'Consenso informato',
                        'optional' => false,
                        'intro'    => 'È informato/a sui seguenti punti in relazione al consenso informato:',
                        'items'    => [
                            ['id' => 's2i1', 'type' => 'paragraph', 'content'  => 'lo psicologo è strettamente tenuto ad attenersi al Codice Deontologico degli Psicologi Italiani;', 'optional' => true],
                            ['id' => 's2i2', 'type' => 'field',     'label'    => 'la prestazione offerta riguarda', 'placeholder' => 'consulenza psicologica, psicoterapia, valutazione psicodiagnostica, ecc.', 'multiline' => false, 'optional' => true],
                            ['id' => 's2i3', 'type' => 'field',     'label'    => 'la prestazione è da considerarsi (ordinaria/complessa) in quanto', 'placeholder' => 'motivazione…', 'multiline' => true, 'optional' => true],
                            ['id' => 's2i4', 'type' => 'paragraph', 'content'  => 'la prestazione è finalizzata ad attività professionali di promozione e tutela della salute e del benessere di persone, gruppi, organismi sociali e comunità (L. n.56/1989, GDPR art.13 lett. C, D.Lgs. 101/2018);', 'optional' => true],
                            ['id' => 's2i5', 'type' => 'field',     'label'    => 'per il conseguimento dell\'obiettivo saranno utilizzati i seguenti strumenti', 'placeholder' => 'colloquio psicologico clinico, test psicodiagnostici, scale di valutazione, ecc.', 'multiline' => true, 'optional' => true],
                            ['id' => 's2i6', 'type' => 'field',     'label'    => 'la durata globale dell\'intervento', 'placeholder' => 'n. ___ sedute / non definibile a priori', 'multiline' => false, 'optional' => true],
                            ['id' => 's2i7', 'type' => 'paragraph', 'content'  => 'in qualsiasi momento è possibile interrompere il rapporto comunicando al professionista la propria volontà di interruzione;', 'optional' => true],
                            ['id' => 's2i8', 'type' => 'paragraph', 'content'  => 'il/la professionista può valutare ed eventualmente proporre l\'interruzione del rapporto quando constata che non vi sia alcun beneficio dall\'intervento e non è ragionevolmente prevedibile che ve ne saranno dal proseguimento dello stesso (art.27 C.D.);', 'optional' => true],
                            ['id' => 's2i9', 'type' => 'paragraph', 'content'  => 'cliente e professionista sono tenuti alla scrupolosa osservanza delle date e degli orari degli appuntamenti; in caso di sopravvenuta impossibilità la parte impossibilitata è tenuta a darne notizia all\'altra in tempi congrui.', 'optional' => true],
                        ],
                    ],

                    // ── 3. Preventivo ────────────────────────────────────────
                    [
                        'id'       => 's3',
                        'title'    => 'Preventivo',
                        'optional' => true,
                        'items'    => [
                            ['id' => 's3i1', 'type' => 'field',     'label'    => 'Compenso per prestazione', 'placeholder' => '€ ___ (+ ENPAP 2%) – esente IVA ex art.10 n.18 D.P.R. 633/1972', 'multiline' => false, 'optional' => false],
                            ['id' => 's3i2', 'type' => 'field',     'label'    => 'Termini di pagamento',     'placeholder' => '€ ___ al termine di ogni prestazione / fatturazione mensile / …', 'multiline' => false, 'optional' => false],
                            ['id' => 's3i3', 'type' => 'paragraph', 'content'  => 'Il compenso non può essere condizionato all\'esito o ai risultati dell\'intervento professionale. In caso di prestazione sanitaria la spesa è detraibile solo se il pagamento avviene con modalità tracciabile.', 'optional' => true],
                            ['id' => 's3i4', 'type' => 'field',     'label'    => 'Assicurazione RC professionale – polizza n.', 'placeholder' => 'nome compagnia, numero polizza', 'multiline' => false, 'optional' => true],
                        ],
                    ],

                    // ── 4. Trattamento dati personali (GDPR) ─────────────────
                    [
                        'id'       => 's4',
                        'title'    => 'Trattamento dati personali (GDPR)',
                        'optional' => true,
                        'items'    => [
                            ['id' => 's4i1', 'type' => 'paragraph', 'content' => 'Il Regolamento UE 2016/679 (GDPR) e il D.Lgs. 101/2018 prevedono la protezione dei dati personali secondo i principi di correttezza, liceità, trasparenza e tutela della riservatezza.', 'optional' => false],
                            ['id' => 's4i2', 'type' => 'paragraph', 'content' => 'Il professionista, Titolare del trattamento, raccoglie: a) dati anagrafici, di contatto e di pagamento; b) dati relativi allo stato di salute fisico/mentale, raccolti nell\'ambito dell\'incarico conferito.', 'optional' => true],
                            ['id' => 's4i3', 'type' => 'paragraph', 'content' => 'I dati sono trattati con modalità cartacee ed elettroniche, con adeguate misure di sicurezza. Conservazione: dati anagrafici/pagamento 10 anni; dati sanitari minimo 5 anni (art.17 C.D.).', 'optional' => true],
                            ['id' => 's4i4', 'type' => 'paragraph', 'content' => 'I dati sanitari sono resi noti solo all\'interessato e, previo consenso scritto, a terzi (art.12 C.D.). Potranno essere condivisi, per le sole informazioni strettamente necessarie, in supervisioni, intervisioni e riunioni d\'équipe (art.15 C.D.).', 'optional' => true],
                            ['id' => 's4i5', 'type' => 'paragraph', 'content' => 'Le spese sanitarie saranno trasmesse all\'Agenzia delle Entrate tramite Sistema Tessera Sanitaria per la dichiarazione precompilata. È possibile opporsi all\'invio indicando il proprio dissenso nel campo apposito in calce.', 'optional' => true],
                            ['id' => 's4i6', 'type' => 'paragraph', 'content' => 'L\'interessato potrà esercitare i diritti di accesso, rettifica, cancellazione, limitazione e portabilità dei dati (artt. 15-22 GDPR), rivolgendosi al Titolare del trattamento (riscontro entro 30 giorni). È altresì possibile proporre reclamo al Garante per la protezione dei dati personali.', 'optional' => true],
                        ],
                    ],

                    // ── 5. Firme e consensi ──────────────────────────────────
                    [
                        'id'       => 's5',
                        'title'    => 'Firme e consensi',
                        'optional' => false,
                        'items'    => [
                            [
                                'id'        => 's5i1',
                                'type'      => 'checkbox_group',
                                'label'     => 'Consenso alla prestazione professionale',
                                'optional'  => false,
                                'checkboxes'=> [
                                    ['id' => 's5i1c1', 'label' => 'FORNISCE IL CONSENSO alla prestazione e al preventivo suindicati', 'default_checked' => false],
                                ],
                            ],
                            [
                                'id'        => 's5i2',
                                'type'      => 'checkbox_group',
                                'label'     => 'Consenso al trattamento dei dati personali',
                                'optional'  => false,
                                'checkboxes'=> [
                                    ['id' => 's5i2c1', 'label' => 'FORNISCE IL CONSENSO al trattamento e alla comunicazione dei propri dati personali per le finalità indicate nella presente informativa', 'default_checked' => false],
                                ],
                            ],
                            [
                                'id'        => 's5i3',
                                'type'      => 'checkbox_group',
                                'label'     => 'Sistema Tessera Sanitaria',
                                'optional'  => true,
                                'checkboxes'=> [
                                    ['id' => 's5i3c1', 'label' => 'NON FORNISCE IL CONSENSO all\'invio dei dati tramite Sistema Tessera Sanitaria all\'Agenzia delle Entrate', 'default_checked' => false],
                                ],
                            ],
                            ['id' => 's5i4', 'type' => 'field', 'label' => 'Luogo e data',                    'placeholder' => '', 'multiline' => false, 'optional' => false],
                            ['id' => 's5i5', 'type' => 'field', 'label' => 'Firma del paziente/cliente',      'placeholder' => '', 'multiline' => false, 'optional' => false],
                            ['id' => 's5i6', 'type' => 'field', 'label' => 'Timbro e firma del Professionista', 'placeholder' => '', 'multiline' => false, 'optional' => false],
                        ],
                    ],
                ],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doc_templates');
    }
};
