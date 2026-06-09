@if(session('success'))
    <div class="mb-4">
        <x-alert variant="success">{{ session('success') }}</x-alert>
    </div>
@endif

@if(session('error') || session('danger'))
    <div class="mb-4">
        <x-alert variant="error">{{ session('error') ?? session('danger') }}</x-alert>
    </div>
@endif

@if(session('warning'))
    <div class="mb-4">
        <x-alert variant="warning">{{ session('warning') }}</x-alert>
    </div>
@endif

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
