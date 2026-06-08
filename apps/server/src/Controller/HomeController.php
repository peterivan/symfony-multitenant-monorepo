<?php

namespace App\Controller;

use App\Tenant\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function __invoke(TenantContext $tenantContext, RequestStack $requestStack): Response
    {
        $tenantSlug = $tenantContext->getTenantSlug() ?? 'none';
        $host = $requestStack->getCurrentRequest()?->getHost() ?? 'unknown';

        return new Response(sprintf(
            "host: %s\ntenant: %s\n",
            $host,
            $tenantSlug,
        ));
    }
}
