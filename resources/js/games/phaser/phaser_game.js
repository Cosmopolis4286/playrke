export class Game extends Phaser.Scene {

    constructor() {
        super({ key: 'game' });
    }

    preload() {
        this.load.image('sky', 'img/phaser/sky.png');
       
    }

    create() {
        this.add.image(400, 250, 'sky');
        //platforms.create(400, 568, 'ground').setScale(2);
        //platforms.create(600, 400, 'ground').setScale();
    }
}