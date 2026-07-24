<?php
require 'auth.php';
require 'config.php';
require 'stripe_config.php';
require_once __DIR__ . '/stripe-php/init.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$societe_id = intval($_SESSION['societe_id'] ?? 0);

if ($societe_id <= 0) {
    die("Société invalide");
}

$plan = $_GET['plan'] ?? '';

$plans = [
    'basic' => [
        'nom' => 'Basic',
        'prix' => 2900
    ],
    'pro' => [
        'nom' => 'Pro',
        'prix' => 5900
    ],
    'premium' => [
        'nom' => 'Premium',
        'prix' => 9900
    ]
];

if (!isset($plans[$plan])) {
    die("Plan invalide");
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',

        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Abonnement Medigo - ' . $plans[$plan]['nom'],
                ],
                'unit_amount' => $plans[$plan]['prix'],
            ],
            'quantity' => 1,
        ]],

        'metadata' => [
            'societe_id' => $societe_id,
            'plan' => $plan
        ],

        'success_url' => SITE_URL . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => SITE_URL . '/payment_cancel.php',
    ]);

    header("Location: " . $session->url);
    exit;

} catch (Exception $e) {
    die("Erreur Stripe : " . $e->getMessage());
}