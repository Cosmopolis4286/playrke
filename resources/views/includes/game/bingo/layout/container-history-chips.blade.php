<style>
    /*Seccion Carrusel*/
    #container-history-chips {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
    }

    .cont-arrow-history-chips {
        height: 100%;
        padding-top: 2.5px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        color: rgb(83, 0, 151);
        font-size: 1.6em;
        transition: 3ms ease;
    }

    .cont-arrow-history-chips:hover {
        transform: scale(1.10);
    }

    .arrow {
        display: none;
    }

    #left-arrow {
        margin-right: 5px;
    }

    #right-arrow {
        margin-left: 5px;
    }

    #container-all-chips {
        position: relative;
        background: rgb(17, 0, 48);
        border-radius: 15px;
        height: 45px;
        padding: 5px;
        gap: 4px;
        display: flex;
        width: auto;
    }

    .image-history {
        width: 3.8vmax;
        height: 3.8vmax;
    }

    /*Seccion Carrusel*/
</style>

<div id="container-history-chips" class=" mt-1 mb-2" style="display: none">
    <div class="cont-arrow-history-chips" data-move="1">
        <i id="left-arrow" class="arrow fa-regular fa-circle-left"></i>
    </div>
    <div id="container-all-chips"></div>
    <div class="cont-arrow-history-chips" data-move="2">
        <i id="right-arrow" class="arrow fa-regular fa-circle-right"></i>
    </div>
</div>
