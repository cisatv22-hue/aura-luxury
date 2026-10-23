<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Notifications\ConfirmNewsletterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
            'consent' => ['accepted'],
        ]);
        $email = mb_strtolower(trim($data['email']));
        $subscriber = Subscriber::query()->firstOrNew(['email' => $email]);
        $subscriber->unsubscribed_at = null;
        $subscriber->confirmed_at = null;
        $subscriber->save();

        $confirmationUrl = URL::temporarySignedRoute('newsletter.confirm', now()->addDays(3), ['subscriber' => $subscriber->id]);
        $unsubscribeUrl = URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $subscriber->id]);
        Notification::route('mail', $email)->notify(new ConfirmNewsletterSubscription($confirmationUrl, $unsubscribeUrl));

        return back()->with('status', 'newsletter-confirmation-sent');
    }

    public function confirm(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $subscriber->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);

        return redirect('/')->with('status', 'newsletter-confirmed');
    }

    public function unsubscribe(Request $request, Subscriber $subscriber): RedirectResponse
    {
        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect('/')->with('status', 'newsletter-unsubscribed');
    }
}
