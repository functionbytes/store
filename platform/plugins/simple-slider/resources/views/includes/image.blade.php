@php
    $slider->loadMissing('metadata');
    $tabletImage = $slider->getMetaData('tablet_image', true) ?: $slider->image;
    $mobileImage = $slider->getMetaData('mobile_image', true) ?: $tabletImage;
    $attributes = $attributes ?? [];
    $lazy = Arr::get($attributes, 'loading') !== 'lazy' ? false : true;
@endphp

@if ($style == 'style-2')asas
    <style>
        @media (max-width: 767px) {
            .slider-bg-{{ $slider->id }} {
                background-image: url('{{ RvMedia::getImageUrl($mobileImage) }}');
            }
        }

        @media (min-width: 768px) and (max-width: 1199px) {
            .slider-bg-{{ $slider->id }} {
                background-image: url('{{ RvMedia::getImageUrl($tabletImage) }}');
            }
        }

        @media (min-width: 1200px) {
            .slider-bg-{{ $slider->id }} {
                background-image: url('{{ RvMedia::getImageUrl($slider->image) }}');
            }
        }
    </style>

    <div class="slider-bg slider-bg-{{ $slider->id }}"></div>
@else
    <picture>
        <source srcset="{{ RvMedia::getImageUrl($slider->image, null, false, RvMedia::getDefaultImage()) }}" media="(min-width: 1200px)" />
        <source srcset="{{ RvMedia::getImageUrl($tabletImage, null, false, RvMedia::getDefaultImage()) }}" media="(min-width: 768px)" />
        <source srcset="{{ RvMedia::getImageUrl($mobileImage, null, false, RvMedia::getDefaultImage()) }}" media="(max-width: 767px)" />
        {{ RvMedia::image($slider->image, attributes: $attributes, lazy: $lazy) }}
    </picture>
@endif
