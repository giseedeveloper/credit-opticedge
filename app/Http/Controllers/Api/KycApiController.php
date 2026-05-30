<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ManagesKycAgentPortal;
use App\Http\Controllers\Api\Concerns\ManagesKycCatalog;
use App\Http\Controllers\Api\Concerns\ManagesKycSupport;
use App\Http\Controllers\Api\Concerns\ManagesKycWizard;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

/**
 * @group KYC — Agent Mobile App
 *
 * Step-by-step endpoints for the field agent mobile application.
 * Logic is split across concerns for maintainability.
 */
class KycApiController extends Controller
{
    use ApiResponse;
    use ManagesKycAgentPortal;
    use ManagesKycCatalog;
    use ManagesKycSupport;
    use ManagesKycWizard;
}
