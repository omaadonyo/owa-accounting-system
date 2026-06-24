<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::select('SELECT id, user_id, (SELECT name FROM users WHERE users.id = subscriptions.user_id) as user_name, business_id, status FROM subscriptions');
foreach ($rows as $r) {
    echo "#{$r->id}: user_id={$r->user_id} user={$r->user_name} business_id={$r->business_id} status={$r->status}\n";
}
