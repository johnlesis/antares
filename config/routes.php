<?php

use App\Controllers\PatientController;

return [
    ['GET',  '/config-test',      PatientController::class, 'list',   200],
    ['GET',  '/config-test/{id}', PatientController::class, 'show',   200],
    ['POST', '/config-test',      PatientController::class, 'create', 201],
];