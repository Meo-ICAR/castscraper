<?php

namespace Database\Seeders;

use App\Models\ScrapingKeyword;
use Illuminate\Database\Seeder;

class ScrapingKeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keywords = [
            ['tecnico', 'troupe', 10, 'Identifica set professionale'],
            ['tecnico', 'maestranze', 10, 'Termine istituzionale bandi'],
            ['tecnico', 'personale tecnico', 10, 'Esclude esplicitamente attori'],
            ['tecnico', 'crew', 9, 'Usato per produzioni internazionali'],
            ['tecnico', 'reparti', 8, 'Riferito a reparti cinema (sceno/costumi/ecc)'],
            ['tecnico', 'caporeparto', 10, 'Posizione di responsabilità'],
            ['tecnico', 'maestranze locali', 10, 'Target specifico per residenti'],
            ['logistica', 'fornitori', 9, 'Target per Service B2B'],
            ['logistica', 'service', 9, 'Rental o servizi strutturati'],
            ['logistica', 'catering', 10, 'Servizio pasti sul set'],
            ['logistica', 'logistica', 8, 'Trasporti e permessi'],
            ['logistica', 'noleggio', 9, 'Rental house (luci/camera/mezzi)'],
            ['logistica', 'trasporti', 8, 'Driver e mezzi tecnici'],
            ['logistica', 'area sosta', 7, 'Intercetta ordinanze comunali'],
            ['ruoli', 'location manager', 10, 'Ruolo chiave per territorio'],
            ['ruoli', 'ispettore di produzione', 10, 'Logistica di alto livello'],
            ['ruoli', 'runner di produzione', 9, 'Ingresso nel settore'],
            ['ruoli', 'scenografo', 9, 'Reparto creativo tecnico'],
            ['ruoli', 'fonico', 9, 'Reparto audio'],
            ['ruoli', 'elettricista', 9, 'Reparto luci'],
            ['istituzionale', 'production alert', 10, 'Alert ufficiale Film Commission'],
            ['istituzionale', 'manifestazione di interesse', 10, 'Linguaggio bandi pubblici'],
            ['istituzionale', 'bando', 8, 'Finanziamento regionale'],
            ['istituzionale', 'convenzione', 7, 'Accordi con fornitori locali'],
            ['geografico', 'residenza', 7, 'Vincolo geografico per assunzione'],
            ['geografico', 'domicilio', 7, 'Vincolo geografico per diaria'],
        ];

        foreach ($keywords as $kw) {
            ScrapingKeyword::updateOrCreate(
                ['keyword' => $kw[1]],
                [
                    'category' => $kw[0],
                    'priority' => $kw[2],
                    'technical_notes' => $kw[3],
                    'active' => true,
                ]
            );
        }
    }
}
