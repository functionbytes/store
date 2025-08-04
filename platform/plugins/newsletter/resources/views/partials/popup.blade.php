@php
    $title = setting('newsletter_popup_title', trans('plugins/newsletter::newsletter.popup.title'));
    $subtitle = setting('newsletter_popup_subtitle', trans('plugins/newsletter::newsletter.popup.subtitle'));
    $description = setting('newsletter_popup_description', trans('plugins/newsletter::newsletter.popup.description'));
    $image = theme_option('newsletter_popup_image');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/core/plugins/newsletter/css/newsletter.css') }}?v=1.2.8">

<div @class(['modal-dialog', 'modal-lg' => $image])>
    <div @class(['modal-content border-0', 'd-flex flex-md-col flex-lg-row' => $image])>
        @if ($image)
            <div class="d-none d-md-block col-6 newsletter-popup-bg">
                {!! RvMedia::image($image, $title, attributes: ['loading' => 'eager']) !!}
            </div>
        @endif

        <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>

        <div class="newsletter-popup-content">
            <div class="modal-header flex-column align-items-start border-0 p-0">
                @if ($subtitle)
                    <span class="modal-subtitle">{!! BaseHelper::clean($subtitle) !!}</span>
                @endif

                @if ($title)
                    <h5 class="modal-title" id="newsletterPopupModalLabel">{!! BaseHelper::clean($title) !!}</h5>
                @endif

                @if ($description)
                    <p class="modal-text text-muted">{!! BaseHelper::clean($description) !!}</p>
                @endif
            </div>
            <div class="modal-body p-0">
                {!! $newsletterForm->setFormOption('class', 'bb-newsletter-popup-form')->renderForm() !!}
                <div class="newsletter-success-message" style="display: none;"></div>
                <div class="newsletter-error-message" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
