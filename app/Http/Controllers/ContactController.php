<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\SendContactMessageAction;
use App\Data\ContactData;
use App\Http\Requests\ContactRequest;
use Illuminate\Http\RedirectResponse;

final class ContactController extends Controller
{
    public function __invoke(ContactRequest $request, SendContactMessageAction $send): RedirectResponse
    {
        $send->handle(ContactData::from($request->validated()));

        return redirect('/contact')->with('status', __('starter.contact_sent'));
    }
}
