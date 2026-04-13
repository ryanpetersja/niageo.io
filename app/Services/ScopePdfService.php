<?php

namespace App\Services;

use App\Models\BrandingSetting;
use App\Models\Scope;
use Barryvdh\DomPDF\Facade\Pdf;

class ScopePdfService
{
    public function generate(Scope $scope): \Barryvdh\DomPDF\PDF
    {
        $scope->load(['client', 'items', 'invoice.lineItems', 'invoice.client']);
        $branding = BrandingSetting::getSettings();

        return Pdf::loadView('pdf.scope', [
            'scope' => $scope,
            'branding' => $branding,
        ])->setPaper('a4');
    }

    public function download(Scope $scope): \Symfony\Component\HttpFoundation\Response
    {
        return $this->generate($scope)->download($scope->scope_number . '.pdf');
    }

    public function stream(Scope $scope): \Symfony\Component\HttpFoundation\Response
    {
        return $this->generate($scope)->stream($scope->scope_number . '.pdf');
    }
}
