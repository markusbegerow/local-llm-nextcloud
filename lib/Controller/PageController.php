<?php
declare(strict_types=1);

namespace OCA\LocalLlm\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        Util::addScript('local-llm-nextcloud', 'local-llm-nextcloud-main');
        Util::addStyle('local-llm-nextcloud', 'style');

        return new TemplateResponse('local-llm-nextcloud', 'main');
    }
}
