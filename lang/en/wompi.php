<?php
return [
    'settings' => [
        'title' => 'Wompi',
        'description' => 'Wompi payment method configuration for Colombia',
        'public_key' => 'Public Key',
        'private_key' => 'Private Key',
        'webhook_secret' => 'Webhook Secret (Optional)',
        'public_key_placeholder' => 'pub_test_xxxxxx or pub_prod_xxxxxx',
        'private_key_placeholder' => 'prv_test_xxxxxx or prv_prod_xxxxxx',
        'webhook_secret_placeholder' => 'Secret to validate webhooks',
        'webhook_secret_help' => 'Optional: Used to validate the authenticity of Wompi webhooks',
        'webhook_url' => 'Webhook URL',
        'webhook_instruction' => 'Configure this URL in your Wompi dashboard to receive payment notifications',
        'note_1' => 'To use Wompi, you need to register at wompi.co and get your API keys',
        'note_2' => 'In sandbox mode, use test keys. In production mode, use real keys',
        'note_3' => 'Configure the webhook in your Wompi panel to receive automatic payment confirmations',
    ],
];

