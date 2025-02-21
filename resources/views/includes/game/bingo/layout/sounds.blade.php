@php
    $audioFiles = [
        'next' => 'sounds/bingo/next.mp3',
        'select' => 'sounds/bingo/select.mp3',
        'winner' => 'sounds/bingo/winner.mp3',
    ];
@endphp

@foreach ($audioFiles as $id => $file)
    <audio id="{{ $id }}">
        <source src="{{ asset($file) }}" type="audio/mp3">
    </audio>
@endforeach
