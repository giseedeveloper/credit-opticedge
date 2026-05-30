<?php

$srcPath = __DIR__.'/../app/Http/Controllers/Api/KycApiController.php';
$lines = explode("\n", file_get_contents($srcPath));

function sliceLines(array $lines, int $start, int $end): string
{
    return implode("\n", array_slice($lines, $start - 1, $end - $start + 1));
}

$support = sliceLines($lines, 1359, 2019);
$catalog = sliceLines($lines, 49, 158)."\n\n".sliceLines($lines, 1338, 1349);
$wizard = sliceLines($lines, 164, 964);
$agent = sliceLines($lines, 970, 1336);

$dir = __DIR__.'/../app/Http/Controllers/Api/Concerns';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

file_put_contents($dir.'/ManagesKycSupport.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\SelcomPaymentRequest;
use App\Models\SystemDocument;
use App\Models\Verification;
use App\Services\DeviceIdentifierScanService;
use App\Services\IMEITrackingService;
use App\Services\KycAccessoryOfferService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait ManagesKycSupport
{
PHP
    .$support."\n}\n");

file_put_contents($dir.'/ManagesKycCatalog.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Services\CustomerLoanProvisioningService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use App\Services\KycStageFlowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ManagesKycCatalog
{
    use ApiResponse;

PHP
    .$catalog."\n}\n");

file_put_contents($dir.'/ManagesKycWizard.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Http\Requests\Api\Kyc\HandoverChecklistRequest;
use App\Http\Requests\Api\Kyc\KycPaymentRequest;
use App\Http\Requests\Api\Kyc\Step1DeviceRequest;
use App\Http\Requests\Api\Kyc\Step2IdentityRequest;
use App\Http\Requests\Api\Kyc\Step3ContactRequest;
use App\Http\Requests\Api\Kyc\Step4IncomeRequest;
use App\Http\Requests\Api\Kyc\Step5NokRequest;
use App\Http\Requests\Api\Kyc\Step6ConsentRequest;
use App\Http\Requests\Api\Kyc\Step7SubmitRequest;
use App\Jobs\ProcessFaceMatchJob;
use App\Models\Customer;
use App\Models\Verification;
use App\Services\ApplicationAutoCheckService;
use App\Services\CustomerLoanProvisioningService;
use App\Services\DeviceIdentifierScanService;
use App\Services\IMEITrackingService;
use App\Services\KycAccessoryOfferService;
use App\Services\KycDeviceCatalogService;
use App\Services\KycPhoneService;
use App\Services\KycStageFlowService;
use App\Services\SelcomCheckoutService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

trait ManagesKycWizard
{
    use ApiResponse;

PHP
    .$wizard."\n}\n");

file_put_contents($dir.'/ManagesKycAgentPortal.php', <<<'PHP'
<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Verification;
use App\Services\CustomerLoanProvisioningService;
use App\Services\KycStageFlowService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ManagesKycAgentPortal
{
    use ApiResponse;

PHP
    .$agent."\n}\n");

echo "Traits written to {$dir}\n";
