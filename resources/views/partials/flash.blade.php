{{-- Session success/error/warning are shown globally as toasts
     (see partials/toast.blade.php). Only validation errors remain here,
     which make sense to show next to the form. --}}
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
