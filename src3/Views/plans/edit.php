<?php

$planData = is_array($plan ?? null) ? $plan : [];
$planId = isset($planData['id']) ? (string) $planData['id'] : '';
$action = '/plans/' . rawurlencode($planId);
$submitLabel = 'Plan aktualisieren';

require VIEWS_PATH . '/plans/_form.php';
