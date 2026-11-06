@php($studio = $publicRenderData->extensionData('studio'))
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $studio->title }} · {{ $studio->name }}</title>
    <meta name="description" content="{{ $studio->summary }}">
    <link rel="stylesheet" href="{{ asset('css/starter.css') }}">
</head>
<body>
<a class="skip" href="#main">{{ __('starter.skip') }}</a>
<header><a class="brand" href="/">{{ $studio->name }}<span aria-hidden="true">✳</span></a><nav aria-label="Main"><a href="/work/field-notes">{{ __('starter.work') }}</a><a href="/contact">{{ __('starter.contact') }}</a><a href="/admin">{{ __('starter.admin') }}</a></nav></header>
<main id="main">
    <p class="eyebrow">{{ $studio->kind === 'case-study' ? __('starter.case_study') : __('starter.eyebrow') }}</p>
    <h1>{{ $studio->title }}</h1><p class="intro">{{ $studio->summary }}</p>
    @if($studio->kind === 'case-study')<dl><dt>{{ __('starter.client') }}</dt><dd>{{ $studio->client }}</dd><dt>{{ __('starter.year') }}</dt><dd>{{ $studio->year }}</dd></dl>@endif
    @if($studio->kind === 'contact')
        <form action="{{ route('contact.send') }}" method="post">
            @csrf
            @if(session('status')) <p role="status">{{ session('status') }}</p> @endif
            @foreach(['name', 'email', 'message'] as $field)
                <label for="{{ $field }}">{{ __('starter.'.$field) }}</label>
                @if($field === 'message')
                    <textarea id="message" name="message" required minlength="10" maxlength="5000">{{ old('message') }}</textarea>
                @else
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" required maxlength="{{ $field === 'email' ? 254 : 100 }}" value="{{ old($field) }}">
                @endif
                @error($field) <p role="alert">{{ $message }}</p> @enderror
            @endforeach
            <button type="submit">{{ __('starter.send') }} <span aria-hidden="true">↗</span></button>
        </form>
    @else
        <a class="project" href="{{ $studio->kind === 'case-study' ? '/contact' : '/work/field-notes' }}">
            <span class="project-art" aria-hidden="true"><span>FIELD<br>NOTES<span class="dot">●</span></span></span>
            <span class="project-label">{{ $studio->kind === 'case-study' ? __('starter.contact') : __('starter.case_title') }} <span aria-hidden="true">↗</span></span>
        </a>
    @endif
</main>
<footer><span>{{ $studio->name }}</span><span>{{ __('starter.footer') }}</span></footer>
</body></html>
