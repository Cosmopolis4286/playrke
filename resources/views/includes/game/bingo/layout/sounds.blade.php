@php
    $audioFiles = [
        'next' => 'storage/sounds/bingo/next.mp3',
        'select' => 'storage/sounds/bingo/select.mp3',
        'winner' => 'storage/sounds/bingo/winner.mp3',
    ];
@endphp

@foreach ($audioFiles as $id => $file)
    <audio id="{{ $id }}">
        <source src="{{ asset($file) }}" type="audio/mp3">
    </audio>
@endforeach
