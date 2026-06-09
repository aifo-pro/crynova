{{-- Session success/error/warning показываются глобально как всплывающие тосты
     (см. partials/toast.blade.php). Здесь остаются только ошибки валидации,
     которые логично видеть рядом с формой. --}}
@if($errors->any())
    <div class="mb-4">
        <x-alert variant="error">
            @if($errors->count() === 1)
                {{ $errors->first() }}
            @else
                <ul class="list-disc space-y-1 pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </x-alert>
    </div>
@endif
