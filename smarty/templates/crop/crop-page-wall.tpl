<div class='top-container'>
    <h1 class='head'><a href='ps-crop.php?page=wall'>Публикатор мыслей</a></h1>
    <div class='navigation'>
        <a href="ps-crop.php?page=wall" class='active'>
            Стена
        </a>
        <a href="ps-crop.php?page=img">
            Опубликовать
        </a>
    </div>

    <div class='wall'>
        {CropWallGenerator::generate()}
    </div>

    <div style="width: 1000px; height: 740px" class="holder binds-show">

        <img usemap="#mosaicmap" alt="" class="mosaic" src="/autogen/mosaic/1.jpg">
        <map id="mosaicmap">
            <area data-id="1" coords="320, 148, 360, 185" shape="rect" nohref="nohref">
            <area data-id="1" coords="400, 296, 440, 333" shape="rect" nohref="nohref">
            <area data-id="1" coords="520, 370, 560, 407" shape="rect" nohref="nohref">
            <area data-id="1" coords="40, 444, 80, 481" shape="rect" nohref="nohref">
            <area data-id="1" coords="640, 555, 680, 592" shape="rect" nohref="nohref">
        </map>
    </div>
</div></div>