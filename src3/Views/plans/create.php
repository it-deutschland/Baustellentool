<?php

$projectData = is_array($project ?? null) ? $project : [];
$projectId = isset($projectData['id']) ? (string) $projectData['id'] : '';
$action = '/projects/' . rawurlencode($projectId) . '/plans';
$submitLabel = 'Plan anlegen';

require VIEWS_PATH . '/plans/_form.php';
