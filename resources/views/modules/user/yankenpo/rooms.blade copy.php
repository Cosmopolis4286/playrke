@extends('layouts.app_rke')
<title>Binsgo - PlayRKE</title>

@section('content')
@endsection
@section('scripts')
    <script src="{{ asset('js/phaser.min.js') }}"></script>
    {{-- <script src="{{ asset('js/phaser_index.js') }}" type="module"></script> --}}
    <script>
        var config = {
            type: Phaser.AUTO,
            width: 800,
            height: 500,
            physics: {
                default: 'arcade',
                arcade: {
                    gravity: {
                        y: 300
                    },
                    debug: false
                }
            },
            scene: {
                preload: preload,
                create: create,
                update: update
            }
        };

        var game = new Phaser.Game(config);

        function preload() {
            this.load.image('sky', 'img/phaser/sky.png');
            this.load.image('ground', 'img/phaser/platform.png');
            this.load.image('star', 'img/phaser/star.png');
            this.load.image('bomb', 'img/phaser/bomb.png');
            this.load.spritesheet('dude', 'img/phaser/dude.png', {
                frameWidth: 32,
                frameHeight: 48
            });
        };

        function create() {
            this.add.image(400, 250, 'sky');
            platforms = this.physics.add.staticGroup();
            platforms.create(400, 468, 'ground').setScale(2).refreshBody();
            platforms.create(600, 300, 'ground').setScale();
            platforms.create(750, 120, 'ground').setScale();

            player = this.physics.add.sprite(100, 450, 'dude');
            player.setCollideWorldBounds(true);
            player.setBounce(0.2);

            this.anims.create({
                key: 'left',
                frame: this.anims.generateFrameNumbers('dude', {
                    start: 0,
                    end: 3
                }),
                frameRete: 10,
                repeat: -1
            });
            this.anims.create({
                key: 'turn',
                frame: [{
                    key: 'dude',
                    frame: 4
                }],
                frameRete: 20,
            });

            this.anims.create({
                key: 'right',
                frame: this.anims.generateFrameNumbers('dude', {
                    start: 5,
                    end: 8
                }),
                frameRete: 10,
                repeat: -1
            });

            player.body.setGravityY(300);

            this.physics.add.collider(player, platforms);

        }



        function update() {

        }
    </script>
@endsection
