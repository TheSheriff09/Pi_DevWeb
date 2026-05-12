<?php

namespace App\Controller;

use App\Service\CurrencyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CurrencyController extends AbstractController
{
    private $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    #[Route('/api/currency/convert', name: 'api_currency_convert', methods: ['GET', 'POST'])]
    public function convert(Request $request): JsonResponse
    {
        $amount = (float) $request->get('amount');
        $from = strtoupper($request->get('from', 'USD'));
        $to = strtoupper($request->get('to', 'EUR'));

        if ($amount <= 0) {
            return new JsonResponse(['error' => 'Amount must be greater than zero'], 400);
        }

        $result = $this->currencyService->convert($amount, $from, $to);

        if (isset($result['error'])) {
            return new JsonResponse($result, 500);
        }

        return new JsonResponse($result);
    }

    #[Route('/api/currency/rates', name: 'api_currency_rates', methods: ['GET'])]
    public function rates(Request $request): JsonResponse
    {
        $base = strtoupper($request->get('base', 'USD'));
        $result = $this->currencyService->getLatestRates($base);

        if (isset($result['error'])) {
            return new JsonResponse($result, 500);
        }

        return new JsonResponse($result);
    }
}
