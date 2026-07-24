<?php

require_once __DIR__ . '/tcpdf/tcpdf.php';

function genererFacture(
    $numero,
    $societe,
    $responsable,
    $adresse,
    $siret,
    $plan,
    $montant
) {

    $dossier = __DIR__ . '/factures';

    if (!is_dir($dossier)) {
        mkdir($dossier, 0775, true);
    }

    $fichier = 'FAC-' . date('Y') . '-' . str_pad($numero, 6, '0', STR_PAD_LEFT) . '.pdf';

    $chemin = $dossier . '/' . $fichier;

    $pdf = new TCPDF();

    $pdf->SetCreator('Medigo Synology');
    $pdf->SetAuthor('Medigo Synology');
    $pdf->SetTitle('Facture ' . $fichier);

    $pdf->AddPage();

    $html = '

    <h1>MEDIGO SYNOLOGY</h1>

    <h3>Facture N° ' . $fichier . '</h3>

    <hr>

    <b>Éditeur :</b><br>
    Medigo Synology<br>
    32 rue Auguste Renoir<br>
    33600 Pessac<br><br>

    <b>Client :</b><br>
    ' . htmlspecialchars($societe) . '<br>
    Responsable : ' . htmlspecialchars($responsable) . '<br>
    Adresse : ' . htmlspecialchars($adresse) . '<br>
    SIRET : ' . htmlspecialchars($siret) . '<br><br>

    <hr>

    <table border="1" cellpadding="6">
        <tr>
            <th width="70%">Description</th>
            <th width="30%">Montant</th>
        </tr>
        <tr>
            <td>
            Licence SaaS Medigo Synology<br>
            Accès plateforme MinicarTrans<br>
            Plan ' . strtoupper($plan) . '
            </td>
            <td align="right">
            ' . number_format($montant, 2, ',', ' ') . ' €
            </td>
        </tr>
    </table>

    <br><br>

    <b>Total TTC :</b>
    ' . number_format($montant, 2, ',', ' ') . ' €

    <br><br>

    Date : ' . date('d/m/Y') . '

    <br><br>

    Paiement validé via Stripe.

    ';

    $pdf->writeHTML($html);

    $pdf->Output($chemin, 'F');

    return 'factures/' . $fichier;
}