<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ContentPage;
use Illuminate\Contracts\View\View;

class LegalPageController extends Controller
{
    public function privacyPolicy(): View
    {
        return $this->render('privacy_policy', 'privacypolicy.privacy', 'Privacy Policy');
    }

    public function termsConditions(): View
    {
        return $this->render('terms_conditions', 'termsconditions.termsConditions', 'Terms & Conditions');
    }

    private function render(string $type, string $view, string $fallbackTitle): View
    {
        $page = ContentPage::query()
            ->where('type', $type)
            ->where('status', 'active')
            ->first();

        $data = (object) [
            'title' => $page?->title ?? $fallbackTitle,
            'description' => $page?->content
                ?? '<p>'.e($fallbackTitle).' content is not available yet.</p>',
        ];

        return view($view, compact('data'));
    }
}
