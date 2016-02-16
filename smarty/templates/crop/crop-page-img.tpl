<div class='top-container'>
    <h1 class='head'><a href='ps-crop.php?page=wall'>Публикатор мыслей</a></h1>
    <div class='navigation'>
        <a href="ps-crop.php?page=wall">
            Стена
        </a>
        <a href="ps-crop.php?page=img" class='active'>
            Опубликовать
        </a>
    </div>

    <div id="carrier">
        {* ЛЕВАЯ ПАНЕЛЬ *}
        <div class="container">

            <div class="top-buttons">
                <label for="choose-file" class="choose-file-label">
                    Загрузить фото
                </label>
                <input type="file" id="choose-file" accept="image/*" />
            </div>

            <div class="progress">
                <img src="/ps-content/images/icons/page_loading.gif" alt="loading">
            </div>

            <div class="info_box warn">
                Error message
            </div>

            <div class="crop-editor">
                {*<div class="crop-holder"></div>*}
            </div>

            <div class="crop-text">
                <div class="crop-textarea-holder">
                    <textarea ml='160'></textarea>
                </div>
            </div>

            <div class="bottom-buttons">
                <button>Опубликовать</button>
            </div>

        </div>

        {* ПРАВАЯ ПАНЕЛЬ *}
        <div class="crop-menu">
            <div class="rotate"></div>

            <div id="PresetFilters">
                <a href="#vintage">Vintage</a>
                <a href="#lomo">Lomo</a>
                <a href="#clarity">Clarity</a>
                <a href="#sinCity">Sin City</a>
                <a href="#sunrise">Sunrise</a>
                <a href="#crossProcess">Cross Process</a>
                <a href="#orangePeel">Orange Peel</a>
                <a href="#love">Love</a>
                <a href="#grungy">Grungy</a>
                <a href="#jarques">Jarques</a>
                <a href="#pinhole">Pinhole</a>
                <a href="#oldBoot">Old Boot</a>
                <a href="#glowingSun">Glowing Sun</a>
                <a href="#hazyDays">Hazy Days</a>
                <a href="#herMajesty">Her Majesty</a>
                <a href="#nostalgia">Nostalgia</a>
                <a href="#hemingway">Hemingway</a>
                <a href="#concentrate">Concentrate</a>
            </div>

            <div class="clearall"></div>

            <div class="crop-preview"></div>

            <div class="crop-grid">
                <div class="crop-preview"></div>
            </div>

        </div>

        <div class="clearall"></div>

    </div>
</div>