<?php
/**
Template name: Visualizing Les Miserables Microsite
*/

get_header();

// Preparing things for embedding
function lm_getResourceViewURL($resourceName) {
    $lm_resourcesPath = 'https://lesmiserables.mla.hcommons.org/app/themes/lesmiserables-wordpress-theme' . '/resources/?resource=';
    return $lm_resourcesPath . $resourceName;
}

function lm_getGexfViewURL($graphName) {
    $lm_gexfPath =      'https://lesmiserables.mla.hcommons.org/app/themes/lesmiserables-wordpress-theme' . '/gexf/?graph=';
    return $lm_gexfPath . $graphName;
}

function lm_getResourcePDFURL($resourceName) {
    $lm_resourcesPath = 'https://lesmiserables.mla.hcommons.org/app/themes/lesmiserables-wordpress-theme' . '/img/library/';
    return $lm_resourcesPath . $resourceName . '.pdf';
}

/*
 *  /characters
 *  /characters-introduction
 *  /characters-data
 *  /characters-graphs
 *  /paris
 *  /paris-introduction
 *  /paris-maps
 *  /about
 */

if ( $charsPost = get_page_by_path('characters') ) {
    $charsPost_content = apply_filters('the_content', $charsPost->post_content);
}

if ( $charsPostIntro = get_page_by_path('characters-introduction') ) {
    $charsPostIntro_content = apply_filters('the_content', $charsPostIntro->post_content);
}

if ( $charsPostData = get_page_by_path('characters-data') ) {
    $charsPostData_content = apply_filters('the_content', $charsPostData->post_content);
}

if ( $charsPostGraphs = get_page_by_path('characters-graphs') ) {
    $charsPostGraphs_content = apply_filters('the_content', $charsPostGraphs->post_content);
}

if ( $parisPost = get_page_by_path('paris') ) {
    $parisPost_content = apply_filters('the_content', $parisPost->post_content);
}

if ( $parisPostIntro = get_page_by_path('paris-intro') ) {
    $parisPostIntro_content = apply_filters('the_content', $parisPostIntro->post_content);
}

if ( $parisPostMaps = get_page_by_path('paris-maps') ) {
    $parisPostMaps_content = apply_filters('the_content', $parisPostMaps->post_content);
}

if ( $aboutPost = get_page_by_path('about') ) {
    $aboutPost_content = apply_filters('the_content', $aboutPost->post_content);
}

?>

<hr class="lm_separator darker">

<main id="lm_accordion" class="lm_accordion">

    <ul>
        <li class="su lm_main section-3" id="section-3" data-section-id="3">

            <a href="#" class="lm_accordion-section-title">
                About the Project
                <span class="lm_accordion-arrow"></span>
                <span class="helper-message"></span>
            </a>

            <div class="lm_accordion-section-content">
                <div class="pulled-content">
                    <?php echo $aboutPost_content; ?>
                </div>
            </div>
        </li>

        <li class="su lm_main section-1" id="section-1" data-section-id="1">

            <a href="#" class="lm_accordion-section-title">
                Characters of <span class="lm_book-name">Les Misérables</span>
                <span class="lm_accordion-arrow"></span>
                <span class="helper-message"></span>
            </a>

            <div class="lm_accordion-section-content">

                <div class="pulled-content">
                    <?php echo $charsPost_content; ?>
                </div>

                <p class="dot">&bull;</p>

                <div class="tabs lm_chars-tabs">
                    <ul class="switches">
                        <li class="switch"><a class="lm_button inv" href="#ctab1" data-target="1">introduction</a></li>
                        <li class="switch"><a class="lm_button inv" href="#ctab2" data-target="2">character data</a></li>
                        <li class="switch"><a class="lm_button inv" href="#ctab3" data-target="3">character graphs</a></li>
                        <li class="switch"><a class="lm_button inv" href="#ctab4" data-target="4">appendix</a></li>
                    </ul>

                    <div class="tabcontent" id="ctab1">

                        <div class="pulled-content reading-typography">
                            <?php echo $charsPostIntro_content; ?>
                        </div>

                    </div>

                    <div class="tabcontent tables-tab" id="ctab2">

                        <h2 class="h-center">Character Data</h2>

                        <p class="dot">&middot;</p>

                        <div class="pulled-content">
                            <?php echo $charsPostData_content; ?>
                        </div>

                        <p class="dot">&middot;</p>

                        <h2 class="h-center table-title chapters">
                            Chapter Table

                            <span class="jump-to">
                                <a href="#lm_table-characters" class="jump-to-link">Go to character table</a>
                            </span>
                        </h2>

                        <table id="lm_table-chapters" class="tablesorter lm_table lm_table-chapters" style="">
                            <thead>
                            <tr valign=bottom>
                                <th align="left">Chapter</th>
                                <th align="left">Number of Characters Appearing in Chapter</th>
                                <th align="left">Number of Encounters</th>
                                <th align="left">Character Encounters and Appearances in Chapter</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr valign=top id="chapter_1_1_1" data-chapterid="1.1.1">
                                <td data-sort="1.1.1">1.1.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_NP"><span title="Napoleon, Emperor of France">NP</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>)</td>
                            <tr valign=top id="chapter_1_1_2" data-chapterid="1.1.2">
                                <td data-sort="1.1.2">1.1.2<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_HD"><span title="Hospital director in Digne">HD</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>); (<a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>)</td>
                            <tr valign=top id="chapter_1_1_3" data-chapterid="1.1.3">
                                <td data-sort="1.1.3">1.1.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MS"><span title="Mayor of Senez">MS</span></a>)</td>
                            <tr valign=top id="chapter_1_1_4" data-chapterid="1.1.4">
                                <td data-sort="1.1.4">1.1.4<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>6</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_CL"><span title="Countess de Lô, distant relative of Myriel">CL</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_GE"><span title="Géborand, retired merchant of Digne">GE</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MC"><span title="Marquis de Champtercier, ultra-royalist in Digne">MC</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_CD"><span title="Man condemned to death">CD</span></a>)</td>
                            <tr valign=top id="chapter_1_1_5" data-chapterid="1.1.5">
                                <td data-sort="1.1.5">1.1.5<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>)</td>
                            <tr valign=top id="chapter_1_1_6" data-chapterid="1.1.6">
                                <td data-sort="1.1.6">1.1.6<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>, <a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_CA"><span title="Curé in Digne">CA</span></a>)</td>
                            <tr valign=top id="chapter_1_1_7" data-chapterid="1.1.7">
                                <td data-sort="1.1.7">1.1.7<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>6</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MH"><span title="Mayor of Chastelar">MH</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_CB"><span title="Curé in the mountains near Digne">CB</span></a>); (<a href="#character_CV"><span title="Cravatte, mountain bandit">CV</span></a>, <a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>)</td>
                            <tr valign=top id="chapter_1_1_8" data-chapterid="1.1.8">
                                <td data-sort="1.1.8">1.1.8<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_SN"><span title="Senator, Count ***, in Digne">SN</span></a>, <a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>)</td>
                            <tr valign=top id="chapter_1_1_9" data-chapterid="1.1.9">
                                <td data-sort="1.1.9">1.1.9<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_VB"><span title="Mme Boischevron, friend and correspondent of Mlle Baptistine">VB</span></a>)</td>
                            <tr valign=top id="chapter_1_1_10" data-chapterid="1.1.10">
                                <td data-sort="1.1.10">1.1.10<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_GG"><span title="G--, a Convenionist">GG</span></a>, <a href="#character_SB"><span title="Shepherd boy, serves G-- the conventionist">SB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_GG"><span title="G--, a Convenionist">GG</span></a>)</td>
                            <tr valign=top id="chapter_1_1_11" data-chapterid="1.1.11">
                                <td data-sort="1.1.11">1.1.11<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>)</td>
                            <tr valign=top id="chapter_1_1_12" data-chapterid="1.1.12">
                                <td data-sort="1.1.12">1.1.12<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>)</td>
                            <tr valign=top id="chapter_1_1_13" data-chapterid="1.1.13">
                                <td data-sort="1.1.13">1.1.13<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>)</td>
                            <tr valign=top id="chapter_1_1_14" data-chapterid="1.1.14">
                                <td data-sort="1.1.14">1.1.14<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_SN"><span title="Senator, Count ***, in Digne">SN</span></a>)</td>
                            <tr valign=top id="chapter_1_2_1" data-chapterid="1.2.1">
                                <td data-sort="1.2.1">1.2.1<span class="chapter-lookup mizicon"></span></td>
                                <td>10</td>
                                <td>13</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_GD"><span title="Gendarme in Digne">GD</span></a>); (<a href="#character_JL"><span title="Labarre, innkeeper in Digne">JL</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JL"><span title="Labarre, innkeeper in Digne">JL</span></a>, <a href="#character_KB"><span title="Kitchen boy at Labarre's inn">KB</span></a>); (<a href="#character_JL"><span title="Labarre, innkeeper in Digne">JL</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_KT"><span title="Tavern keeper in Digne">KT</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FM"><span title="Fisherman in Digne">FM</span></a>); (<a href="#character_FM"><span title="Fisherman in Digne">FM</span></a>, <a href="#character_JL"><span title="Labarre, innkeeper in Digne">JL</span></a>); (<a href="#character_FM"><span title="Fisherman in Digne">FM</span></a>, <a href="#character_KT"><span title="Tavern keeper in Digne">KT</span></a>); (<a href="#character_KT"><span title="Tavern keeper in Digne">KT</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JD"><span title="Jailer in the prison of Digne">JD</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PD"><span title="Peasant in Digne, whom Valjean asks for food">PD</span></a>); (<a href="#character_PD"><span title="Peasant in Digne, whom Valjean asks for food">PD</span></a>, <a href="#character_PE"><span title="Peasant's wife">PE</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MR"><span title="Marquise de R-, inhabitant of Digne">MR</span></a>)</td>
                            <tr valign=top id="chapter_1_2_2" data-chapterid="1.2.2">
                                <td data-sort="1.2.2">1.2.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>)</td>
                            <tr valign=top id="chapter_1_2_3" data-chapterid="1.2.3">
                                <td data-sort="1.2.3">1.2.3<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>6</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>)</td>
                            <tr valign=top id="chapter_1_2_4" data-chapterid="1.2.4">
                                <td data-sort="1.2.4">1.2.4<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>10</td>
                                <td>(<a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_VB"><span title="Mme Boischevron, friend and correspondent of Mlle Baptistine">VB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>)</td>
                            <tr valign=top id="chapter_1_2_5" data-chapterid="1.2.5">
                                <td data-sort="1.2.5">1.2.5<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_MB"><span title="Mlle Baptistine, sister of Myriel">MB</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_2_6" data-chapterid="1.2.6">
                                <td data-sort="1.2.6">1.2.6<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>6</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JM"><span title="Jeanne, sister of Valjean">JM</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MD"><span title="Marie-Claude, neighbor of the Valjeans in Faverolles">MD</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_IS"><span title="Isabeau, baker in Faverolles">IS</span></a>); (<a href="#character_PR"><span title="Prison guard">PR</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JM"><span title="Jeanne, sister of Valjean">JM</span></a>, <a href="#character_JN"><span title="Youngest son of Valjean's sister">JN</span></a>); (<a href="#character_JN"><span title="Youngest son of Valjean's sister">JN</span></a>, <a href="#character_KD"><span title="Door keeper at a Paris bindery">KD</span></a>)</td>
                            <tr valign=top id="chapter_1_2_7" data-chapterid="1.2.7">
                                <td data-sort="1.2.7">1.2.7<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_2_9" data-chapterid="1.2.9">
                                <td data-sort="1.2.9">1.2.9<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_DO"><span title="Distillery foreman in Grasse">DO</span></a>)</td>
                            <tr valign=top id="chapter_1_2_10" data-chapterid="1.2.10">
                                <td data-sort="1.2.10">1.2.10<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_2_11" data-chapterid="1.2.11">
                                <td data-sort="1.2.11">1.2.11<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_2_12" data-chapterid="1.2.12">
                                <td data-sort="1.2.12">1.2.12<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_ME"><span title="Mme Magloire, housekeeper to Myriel">ME</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TR"><span title="Three gendarmes, arrested Valjean">TR</span></a>, <a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>); (<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_2_13" data-chapterid="1.2.13">
                                <td data-sort="1.2.13">1.2.13<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PG"><span title="Petit Gervais, a chimney sweep">PG</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PH"><span title="A priest on the road from Digne">PH</span></a>)</td>
                            <tr valign=top id="chapter_1_3_2" data-chapterid="1.3.2">
                                <td data-sort="1.3.2">1.3.2<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>0</td>
                                <td>(<a href="#character_FT"><span title="Tholomyès, Parisian student, lover of Fantine">FT</span></a>); (<a href="#character_LI"><span title="Listolier, Parisian student, lover of Dahlia">LI</span></a>); (<a href="#character_FA"><span title="Fameuil, Parisian student, lover of  Zéphine">FA</span></a>); (<a href="#character_BL"><span title="Blachevelle, Parisian student, lover of Favourite">BL</span></a>); (<a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>); (<a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>); (<a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_3_3" data-chapterid="1.3.3">
                                <td data-sort="1.3.3">1.3.3<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>28</td>
                                <td>(<a href="#character_FT"><span title="Tholomyès, Parisian student, lover of Fantine">FT</span></a>, <a href="#character_LI"><span title="Listolier, Parisian student, lover of Dahlia">LI</span></a>, <a href="#character_FA"><span title="Fameuil, Parisian student, lover of  Zéphine">FA</span></a>, <a href="#character_BL"><span title="Blachevelle, Parisian student, lover of Favourite">BL</span></a>, <a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>, <a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>, <a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_3_4" data-chapterid="1.3.4">
                                <td data-sort="1.3.4">1.3.4<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>28</td>
                                <td>(<a href="#character_FT"><span title="Tholomyès, Parisian student, lover of Fantine">FT</span></a>, <a href="#character_LI"><span title="Listolier, Parisian student, lover of Dahlia">LI</span></a>, <a href="#character_FA"><span title="Fameuil, Parisian student, lover of  Zéphine">FA</span></a>, <a href="#character_BL"><span title="Blachevelle, Parisian student, lover of Favourite">BL</span></a>, <a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>, <a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>, <a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_3_6" data-chapterid="1.3.6">
                                <td data-sort="1.3.6">1.3.6<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>28</td>
                                <td>(<a href="#character_FA"><span title="Fameuil, Parisian student, lover of  Zéphine">FA</span></a>, <a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>, <a href="#character_FT"><span title="Tholomyès, Parisian student, lover of Fantine">FT</span></a>, <a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_LI"><span title="Listolier, Parisian student, lover of Dahlia">LI</span></a>, <a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>, <a href="#character_BL"><span title="Blachevelle, Parisian student, lover of Favourite">BL</span></a>)</td>
                            <tr valign=top id="chapter_1_3_7" data-chapterid="1.3.7">
                                <td data-sort="1.3.7">1.3.7<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>10</td>
                                <td>(<a href="#character_FT"><span title="Tholomyès, Parisian student, lover of Fantine">FT</span></a>, <a href="#character_BL"><span title="Blachevelle, Parisian student, lover of Favourite">BL</span></a>, <a href="#character_FA"><span title="Fameuil, Parisian student, lover of  Zéphine">FA</span></a>, <a href="#character_LI"><span title="Listolier, Parisian student, lover of Dahlia">LI</span></a>, <a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>)</td>
                            <tr valign=top id="chapter_1_3_8" data-chapterid="1.3.8">
                                <td data-sort="1.3.8">1.3.8<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>28</td>
                                <td>(<a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>, <a href="#character_BL"><span title="Blachevelle, Parisian student, lover of Favourite">BL</span></a>, <a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>, <a href="#character_FT"><span title="Tholomyès, Parisian student, lover of Fantine">FT</span></a>, <a href="#character_FA"><span title="Fameuil, Parisian student, lover of  Zéphine">FA</span></a>, <a href="#character_LI"><span title="Listolier, Parisian student, lover of Dahlia">LI</span></a>, <a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_3_9" data-chapterid="1.3.9">
                                <td data-sort="1.3.9">1.3.9<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>16</td>
                                <td>(<a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>, <a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>, <a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_WB"><span title="Waiter at Bombarda">WB</span></a>, <a href="#character_FV"><span title="Favourite, mistress of Blachevelle">FV</span></a>, <a href="#character_DA"><span title="Dahlia, mistress of Listolier">DA</span></a>, <a href="#character_ZE"><span title="Zephine, mistress of Fameuil">ZE</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_4_1" data-chapterid="1.4.1">
                                <td data-sort="1.4.1">1.4.1<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>15</td>
                                <td>(<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_NT"><span title="Neighbor of Thénardiers">NT</span></a>)</td>
                            <tr valign=top id="chapter_1_4_2" data-chapterid="1.4.2">
                                <td data-sort="1.4.2">1.4.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>0</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>)</td>
                            <tr valign=top id="chapter_1_4_3" data-chapterid="1.4.3">
                                <td data-sort="1.4.3">1.4.3<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>6</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_1_5_1" data-chapterid="1.5.1">
                                <td data-sort="1.5.1">1.5.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_5_2" data-chapterid="1.5.2">
                                <td data-sort="1.5.2">1.5.2<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_5_3" data-chapterid="1.5.3">
                                <td data-sort="1.5.3">1.5.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_5_4" data-chapterid="1.5.4">
                                <td data-sort="1.5.4">1.5.4<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_MY"><span title="M. Myriel, Bishop of Digne">MY</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_DM"><span title="Dowager in M-sur-M">DM</span></a>)</td>
                            <tr valign=top id="chapter_1_5_5" data-chapterid="1.5.5">
                                <td data-sort="1.5.5">1.5.5<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>0</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_5_6" data-chapterid="1.5.6">
                                <td data-sort="1.5.6">1.5.6<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_1_5_7" data-chapterid="1.5.7">
                                <td data-sort="1.5.7">1.5.7<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>0</td>
                                <td>(<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_5_8" data-chapterid="1.5.8">
                                <td data-sort="1.5.8">1.5.8<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>7</td>
                                <td>(<a href="#character_VI"><span title="Mme Victurnien, snoop in M-sur-M">VI</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_SF"><span title="Supervisor in M. Madeleine's factory">SF</span></a>)</td>
                            <tr valign=top id="chapter_1_5_9" data-chapterid="1.5.9">
                                <td data-sort="1.5.9">1.5.9<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_SF"><span title="Supervisor in M. Madeleine's factory">SF</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_FD"><span title="Secondhand dealer who sold furniture to Fantine">FD</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_FL"><span title="Fantine's landlord">FL</span></a>); (<a href="#character_MT"><span title="Marguerite, friend of Fantine in M-sur-M">MT</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_VI"><span title="Mme Victurnien, snoop in M-sur-M">VI</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_5_10" data-chapterid="1.5.10">
                                <td data-sort="1.5.10">1.5.10<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_MT"><span title="Marguerite, friend of Fantine in M-sur-M">MT</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_FB"><span title="Barber to whom Fantine sells her hair">FB</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_ID"><span title="Itinerant dentist">ID</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_MT"><span title="Marguerite, friend of Fantine in M-sur-M">MT</span></a>)</td>
                            <tr valign=top id="chapter_1_5_12" data-chapterid="1.5.12">
                                <td data-sort="1.5.12">1.5.12<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_BM"><span title="M. Bamatabois, idler of M-sur-M">BM</span></a>); (<a href="#character_BM"><span title="M. Bamatabois, idler of M-sur-M">BM</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_BM"><span title="M. Bamatabois, idler of M-sur-M">BM</span></a>)</td>
                            <tr valign=top id="chapter_1_5_13" data-chapterid="1.5.13">
                                <td data-sort="1.5.13">1.5.13<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>7</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_SG"><span title="Police sergeant in M-sur-M">SG</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_6_1" data-chapterid="1.6.1">
                                <td data-sort="1.6.1">1.6.1<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_DS"><span title="Doctor in M-sur-M hospital">DS</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>)</td>
                            <tr valign=top id="chapter_1_6_2" data-chapterid="1.6.2">
                                <td data-sort="1.6.2">1.6.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>)</td>
                            <tr valign=top id="chapter_1_7_1" data-chapterid="1.7.1">
                                <td data-sort="1.7.1">1.7.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_SP"><span title="Sister Perpétue, nun at infirmary in M-sur-M">SP</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_7_2" data-chapterid="1.7.2">
                                <td data-sort="1.7.2">1.7.2<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SC"><span title="M. Scaufflaire, keeper of horses and coaches in M-sur-M">SC</span></a>); (<a href="#character_SC"><span title="M. Scaufflaire, keeper of horses and coaches in M-sur-M">SC</span></a>, <a href="#character_SD"><span title="M. Scaufflaire's wife">SD</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_PO"><span title="Portress of JV in M-sur-M">PO</span></a>, <a href="#character_CI"><span title="Cashier at M.Madeleine's manufactory">CI</span></a>)</td>
                            <tr valign=top id="chapter_1_7_3" data-chapterid="1.7.3">
                                <td data-sort="1.7.3">1.7.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_7_4" data-chapterid="1.7.4">
                                <td data-sort="1.7.4">1.7.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PO"><span title="Portress of JV in M-sur-M">PO</span></a>)</td>
                            <tr valign=top id="chapter_1_7_5" data-chapterid="1.7.5">
                                <td data-sort="1.7.5">1.7.5<span class="chapter-lookup mizicon"></span></td>
                                <td>11</td>
                                <td>10</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CE"><span title="Coachman of the mail to Arras">CE</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BH"><span title="Stable boy in Hesdin">BH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BW"><span title="Master Bourgaillard, wheelright">BW</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_WH"><span title="Old woman in Hesdin">WH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_WI"><span title="Old woman's son">WI</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_IK"><span title="Innkeeper's wife at Saint Pol">IK</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SE"><span title="Servant girl in Saint Pol">SE</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TE"><span title="German teamster">TE</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_RM"><span title="Road mender">RM</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PT"><span title="Postillion, accompanying Valjean to Arras">PT</span></a>)</td>
                            <tr valign=top id="chapter_1_7_6" data-chapterid="1.7.6">
                                <td data-sort="1.7.6">1.7.6<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>6</td>
                                <td>(<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_DS"><span title="Doctor in M-sur-M hospital">DS</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>); (<a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>, <a href="#character_SM"><span title="Servant at the hospital in M-sur-M">SM</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_SM"><span title="Servant at the hospital in M-sur-M">SM</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_DS"><span title="Doctor in M-sur-M hospital">DS</span></a>); (<a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>, <a href="#character_DS"><span title="Doctor in M-sur-M hospital">DS</span></a>)</td>
                            <tr valign=top id="chapter_1_7_7" data-chapterid="1.7.7">
                                <td data-sort="1.7.7">1.7.7<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_LR"><span title="Landlady at an Arras hotel">LR</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_RA"><span title="Resident of Arras">RA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BC"><span title="Booking clerk in Arras's court">BC</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_AA"><span title="Lawyer in Arras's court">AA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BI"><span title="Bailiff in Arras's court">BI</span></a>)</td>
                            <tr valign=top id="chapter_1_7_8" data-chapterid="1.7.8">
                                <td data-sort="1.7.8">1.7.8<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_BI"><span title="Bailiff in Arras's court">BI</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BI"><span title="Bailiff in Arras's court">BI</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_7_9" data-chapterid="1.7.9">
                                <td data-sort="1.7.9">1.7.9<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>16</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BM"><span title="M. Bamatabois, idler of M-sur-M">BM</span></a>, <a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>, <a href="#character_CK"><span title="Counsel for the defense in Champmathieu's trial">CK</span></a>, <a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>)</td>
                            <tr valign=top id="chapter_1_7_10" data-chapterid="1.7.10">
                                <td data-sort="1.7.10">1.7.10<span class="chapter-lookup mizicon"></span></td>
                                <td>9</td>
                                <td>35</td>
                                <td>(<a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>); (<a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>, <a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>); (<a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>, <a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>); (<a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_BI"><span title="Bailiff in Arras's court">BI</span></a>); (<a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_BR"><span title="Brevet, convict in the galleys with Valjean">BR</span></a>, <a href="#character_CN"><span title="Chenildieu, convict in the galleys with Valjean">CN</span></a>, <a href="#character_CC"><span title="Cochepaille, convict in the galleys with Valjean">CC</span></a>, <a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BR"><span title="Brevet, convict in the galleys with Valjean">BR</span></a>, <a href="#character_CN"><span title="Chenildieu, convict in the galleys with Valjean">CN</span></a>, <a href="#character_CC"><span title="Cochepaille, convict in the galleys with Valjean">CC</span></a>, <a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>, <a href="#character_BM"><span title="M. Bamatabois, idler of M-sur-M">BM</span></a>)</td>
                            <tr valign=top id="chapter_1_7_11" data-chapterid="1.7.11">
                                <td data-sort="1.7.11">1.7.11<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>21</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BR"><span title="Brevet, convict in the galleys with Valjean">BR</span></a>, <a href="#character_CN"><span title="Chenildieu, convict in the galleys with Valjean">CN</span></a>, <a href="#character_CC"><span title="Cochepaille, convict in the galleys with Valjean">CC</span></a>, <a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>, <a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>)</td>
                            <tr valign=top id="chapter_1_8_1" data-chapterid="1.8.1">
                                <td data-sort="1.8.1">1.8.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_1_8_2" data-chapterid="1.8.2">
                                <td data-sort="1.8.2">1.8.2<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>6</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_DS"><span title="Doctor in M-sur-M hospital">DS</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_1_8_3" data-chapterid="1.8.3">
                                <td data-sort="1.8.3">1.8.3<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>7</td>
                                <td>(<a href="#character_CH"><span title="Champmathieu, accused thief mistaken for Valjean">CH</span></a>, <a href="#character_JU"><span title="Judge at the Arras court">JU</span></a>, <a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>); (<a href="#character_PA"><span title="Prosecuting attorney in Champmathieu trial">PA</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_8_4" data-chapterid="1.8.4">
                                <td data-sort="1.8.4">1.8.4<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>5</td>
                                <td>(<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_1_8_5" data-chapterid="1.8.5">
                                <td data-sort="1.8.5">1.8.5<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>5</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_PO"><span title="Portress of JV in M-sur-M">PO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_PO"><span title="Portress of JV in M-sur-M">PO</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_SS"><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_FN"><span title="Fantine, mistress of Tholomyès">FN</span></a>)</td>
                            <tr valign=top id="chapter_2_1_19" data-chapterid="2.1.19">
                                <td data-sort="2.1.19">2.1.19<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>)</td>
                            <tr valign=top id="chapter_2_2_1" data-chapterid="2.2.1">
                                <td data-sort="2.2.1">2.2.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_2_2" data-chapterid="2.2.2">
                                <td data-sort="2.2.2">2.2.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>); (<a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_2_3" data-chapterid="2.2.3">
                                <td data-sort="2.2.3">2.2.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BS"><span title="Boatswain on the Orion saved by Valjean">BS</span></a>)</td>
                            <tr valign=top id="chapter_2_3_1" data-chapterid="2.3.1">
                                <td data-sort="2.3.1">2.3.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>6</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_2_3_2" data-chapterid="2.3.2">
                                <td data-sort="2.3.2">2.3.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_3_3" data-chapterid="2.3.3">
                                <td data-sort="2.3.3">2.3.3<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_PZ"><span title="Peddler in Thénardier's inn">PZ</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_PZ"><span title="Peddler in Thénardier's inn">PZ</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_3_4" data-chapterid="2.3.4">
                                <td data-sort="2.3.4">2.3.4<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>2</td>
                                <td>(<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_3_5" data-chapterid="2.3.5">
                                <td data-sort="2.3.5">2.3.5<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_3_6" data-chapterid="2.3.6">
                                <td data-sort="2.3.6">2.3.6<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_LP"><span title="Louis-Philippe d'Orléans, King of France">LP</span></a>, <a href="#character_DH"><span title="Duc d'Havré, accompanies Louis-Philippe">DH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CF"><span title="Montfermeuil coachman">CF</span></a>); (<a href="#character_CF"><span title="Montfermeuil coachman">CF</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_3_7" data-chapterid="2.3.7">
                                <td data-sort="2.3.7">2.3.7<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_3_8" data-chapterid="2.3.8">
                                <td data-sort="2.3.8">2.3.8<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>28</td>
                                <td>(<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_3_9" data-chapterid="2.3.9">
                                <td data-sort="2.3.9">2.3.9<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>7</td>
                                <td>(<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_3_10" data-chapterid="2.3.10">
                                <td data-sort="2.3.10">2.3.10<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>3</td>
                                <td>(<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_3_11" data-chapterid="2.3.11">
                                <td data-sort="2.3.11">2.3.11<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_4_2" data-chapterid="2.4.2">
                                <td data-sort="2.4.2">2.4.2<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_4_3" data-chapterid="2.4.3">
                                <td data-sort="2.4.3">2.4.3<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_LL"><span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_4_4" data-chapterid="2.4.4">
                                <td data-sort="2.4.4">2.4.4<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_LL"><span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_LL"><span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span></a>)</td>
                            <tr valign=top id="chapter_2_4_5" data-chapterid="2.4.5">
                                <td data-sort="2.4.5">2.4.5<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_LL"><span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span></a>)</td>
                            <tr valign=top id="chapter_2_5_1" data-chapterid="2.5.1">
                                <td data-sort="2.5.1">2.5.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_2_5_2" data-chapterid="2.5.2">
                                <td data-sort="2.5.2">2.5.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_KA"><span title="Toll keeper at Austerlitz bridge">KA</span></a>)</td>
                            <tr valign=top id="chapter_2_5_3" data-chapterid="2.5.3">
                                <td data-sort="2.5.3">2.5.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_5_4" data-chapterid="2.5.4">
                                <td data-sort="2.5.4">2.5.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_5_5" data-chapterid="2.5.5">
                                <td data-sort="2.5.5">2.5.5<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SO"><span title="Soldiers pursuing Valjean, led by Javert">SO</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_SO"><span title="Soldiers pursuing Valjean, led by Javert">SO</span></a>)</td>
                            <tr valign=top id="chapter_2_5_6" data-chapterid="2.5.6">
                                <td data-sort="2.5.6">2.5.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_5_7" data-chapterid="2.5.7">
                                <td data-sort="2.5.7">2.5.7<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_5_8" data-chapterid="2.5.8">
                                <td data-sort="2.5.8">2.5.8<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_2_5_9" data-chapterid="2.5.9">
                                <td data-sort="2.5.9">2.5.9<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>)</td>
                            <tr valign=top id="chapter_2_5_10" data-chapterid="2.5.10">
                                <td data-sort="2.5.10">2.5.10<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>7</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_LL"><span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_SO"><span title="Soldiers pursuing Valjean, led by Javert">SO</span></a>)</td>
                            <tr valign=top id="chapter_2_6_7" data-chapterid="2.6.7">
                                <td data-sort="2.6.7">2.6.7<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>)</td>
                            <tr valign=top id="chapter_2_8_1" data-chapterid="2.8.1">
                                <td data-sort="2.8.1">2.8.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>); (<a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>)</td>
                            <tr valign=top id="chapter_2_8_2" data-chapterid="2.8.2">
                                <td data-sort="2.8.2">2.8.2<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>)</td>
                            <tr valign=top id="chapter_2_8_3" data-chapterid="2.8.3">
                                <td data-sort="2.8.3">2.8.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>)</td>
                            <tr valign=top id="chapter_2_8_4" data-chapterid="2.8.4">
                                <td data-sort="2.8.4">2.8.4<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_8_5" data-chapterid="2.8.5">
                                <td data-sort="2.8.5">2.8.5<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_GR"><span title="Gribier, new gravedigger at Vaugirard cemetery">GR</span></a>)</td>
                            <tr valign=top id="chapter_2_8_6" data-chapterid="2.8.6">
                                <td data-sort="2.8.6">2.8.6<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_2_8_7" data-chapterid="2.8.7">
                                <td data-sort="2.8.7">2.8.7<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_GR"><span title="Gribier, new gravedigger at Vaugirard cemetery">GR</span></a>); (<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_GR"><span title="Gribier, new gravedigger at Vaugirard cemetery">GR</span></a>)</td>
                            <tr valign=top id="chapter_2_8_8" data-chapterid="2.8.8">
                                <td data-sort="2.8.8">2.8.8<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MI"><span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>)</td>
                            <tr valign=top id="chapter_2_8_9" data-chapterid="2.8.9">
                                <td data-sort="2.8.9">2.8.9<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_FF"><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_3_1_13" data-chapterid="3.1.13">
                                <td data-sort="3.1.13">3.1.13<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>6</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_BU"><span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_2_1" data-chapterid="3.2.1">
                                <td data-sort="3.2.1">3.2.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_2_2" data-chapterid="3.2.2">
                                <td data-sort="3.2.2">3.2.2<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_2_3" data-chapterid="3.2.3">
                                <td data-sort="3.2.3">3.2.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_2_4" data-chapterid="3.2.4">
                                <td data-sort="3.2.4">3.2.4<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_2_5" data-chapterid="3.2.5">
                                <td data-sort="3.2.5">3.2.5<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_2_6" data-chapterid="3.2.6">
                                <td data-sort="3.2.6">3.2.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>)</td>
                            <tr valign=top id="chapter_3_2_7" data-chapterid="3.2.7">
                                <td data-sort="3.2.7">3.2.7<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_2_8" data-chapterid="3.2.8">
                                <td data-sort="3.2.8">3.2.8<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>5</td>
                                <td>(<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_MP"><span title="Mme Pontmercy, younger daughter of Gillenormand">MP</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_MV"><span title="Mlle Vaubois, friend of Mlle Gillenormand">MV</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_3_1" data-chapterid="3.3.1">
                                <td data-sort="3.3.1">3.3.1<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>5</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_BT"><span title="Baroness de T-, friend of M. Gillenormand">BT</span></a>); (<a href="#character_BT"><span title="Baroness de T-, friend of M. Gillenormand">BT</span></a>, <a href="#character_LA"><span title="Count Lamothe, member of Baroness de T-'s salon">LA</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_3_2" data-chapterid="3.3.2">
                                <td data-sort="3.3.2">3.3.2<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>11</td>
                                <td>(<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_WP"><span title="Woman servant to Colonel Pontmercy">WP</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_AM"><span title="Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf">AM</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_NP"><span title="Napoleon, Emperor of France">NP</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_MP"><span title="Mme Pontmercy, younger daughter of Gillenormand">MP</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_AM"><span title="Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf">AM</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>)</td>
                            <tr valign=top id="chapter_3_3_3" data-chapterid="3.3.3">
                                <td data-sort="3.3.3">3.3.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_BT"><span title="Baroness de T-, friend of M. Gillenormand">BT</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_3_4" data-chapterid="3.3.4">
                                <td data-sort="3.3.4">3.3.4<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>8</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_WP"><span title="Woman servant to Colonel Pontmercy">WP</span></a>); (<a href="#character_GP"><span title="Colonel George Pontmercy, Marius's father">GP</span></a>, <a href="#character_AM"><span title="Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf">AM</span></a>, <a href="#character_DV"><span title="Doctor in Vernon">DV</span></a>, <a href="#character_WP"><span title="Woman servant to Colonel Pontmercy">WP</span></a>)</td>
                            <tr valign=top id="chapter_3_3_5" data-chapterid="3.3.5">
                                <td data-sort="3.3.5">3.3.5<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>)</td>
                            <tr valign=top id="chapter_3_3_6" data-chapterid="3.3.6">
                                <td data-sort="3.3.6">3.3.6<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_3_7" data-chapterid="3.3.7">
                                <td data-sort="3.3.7">3.3.7<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_3_8" data-chapterid="3.3.8">
                                <td data-sort="3.3.8">3.3.8<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>)</td>
                            <tr valign=top id="chapter_3_4_1" data-chapterid="3.4.1">
                                <td data-sort="3.4.1">3.4.1<span class="chapter-lookup mizicon"></span></td>
                                <td>9</td>
                                <td>0</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>); (<a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>); (<a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>); (<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>); (<a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>)</td>
                            <tr valign=top id="chapter_3_4_2" data-chapterid="3.4.2">
                                <td data-sort="3.4.2">3.4.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>)</td>
                            <tr valign=top id="chapter_3_4_3" data-chapterid="3.4.3">
                                <td data-sort="3.4.3">3.4.3<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>)</td>
                            <tr valign=top id="chapter_3_4_4" data-chapterid="3.4.4">
                                <td data-sort="3.4.4">3.4.4<span class="chapter-lookup mizicon"></span></td>
                                <td>9</td>
                                <td>6</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>); (<a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>); (<a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>)</td>
                            <tr valign=top id="chapter_3_4_5" data-chapterid="3.4.5">
                                <td data-sort="3.4.5">3.4.5<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>19</td>
                                <td>(<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_3_4_6" data-chapterid="3.4.6">
                                <td data-sort="3.4.6">3.4.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_5_1" data-chapterid="3.5.1">
                                <td data-sort="3.5.1">3.5.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_5_2" data-chapterid="3.5.2">
                                <td data-sort="3.5.2">3.5.2<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_5_3" data-chapterid="3.5.3">
                                <td data-sort="3.5.3">3.5.3<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_3_5_4" data-chapterid="3.5.4">
                                <td data-sort="3.5.4">3.5.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_PL"><span title="Mother Plutarch, housekeeper of M. Mabeuf">PL</span></a>)</td>
                            <tr valign=top id="chapter_3_5_5" data-chapterid="3.5.5">
                                <td data-sort="3.5.5">3.5.5<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_SR"><span title="Servant to Marius at Garbeau tenement">SR</span></a>)</td>
                            <tr valign=top id="chapter_3_5_6" data-chapterid="3.5.6">
                                <td data-sort="3.5.6">3.5.6<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>5</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>)</td>
                            <tr valign=top id="chapter_3_6_1" data-chapterid="3.6.1">
                                <td data-sort="3.6.1">3.6.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_3_6_2" data-chapterid="3.6.2">
                                <td data-sort="3.6.2">3.6.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_3_6_3" data-chapterid="3.6.3">
                                <td data-sort="3.6.3">3.6.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_3_6_4" data-chapterid="3.6.4">
                                <td data-sort="3.6.4">3.6.4<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_3_6_5" data-chapterid="3.6.5">
                                <td data-sort="3.6.5">3.6.5<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>5</td>
                                <td>(<a href="#character_BU"><span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_BU"><span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_3_6_6" data-chapterid="3.6.6">
                                <td data-sort="3.6.6">3.6.6<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>)</td>
                            <tr valign=top id="chapter_3_6_7" data-chapterid="3.6.7">
                                <td data-sort="3.6.7">3.6.7<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_3_6_8" data-chapterid="3.6.8">
                                <td data-sort="3.6.8">3.6.8<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_3_6_9" data-chapterid="3.6.9">
                                <td data-sort="3.6.9">3.6.9<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>8</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_PS"><span title="Porter, rue de l'Ouest">PS</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_PS"><span title="Porter, rue de l'Ouest">PS</span></a>)</td>
                            <tr valign=top id="chapter_3_7_3" data-chapterid="3.7.3">
                                <td data-sort="3.7.3">3.7.3<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>1</td>
                                <td>(<a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>); (<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>); (<a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>)</td>
                            <tr valign=top id="chapter_3_7_4" data-chapterid="3.7.4">
                                <td data-sort="3.7.4">3.7.4<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>1</td>
                                <td>(<a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>); (<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>); (<a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>); (<a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>); (<a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>); (<a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>)</td>
                            <tr valign=top id="chapter_3_8_1" data-chapterid="3.8.1">
                                <td data-sort="3.8.1">3.8.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_2" data-chapterid="3.8.2">
                                <td data-sort="3.8.2">3.8.2<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_3" data-chapterid="3.8.3">
                                <td data-sort="3.8.3">3.8.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_3_8_4" data-chapterid="3.8.4">
                                <td data-sort="3.8.4">3.8.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_5" data-chapterid="3.8.5">
                                <td data-sort="3.8.5">3.8.5<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_6" data-chapterid="3.8.6">
                                <td data-sort="3.8.6">3.8.6<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>)</td>
                            <tr valign=top id="chapter_3_8_7" data-chapterid="3.8.7">
                                <td data-sort="3.8.7">3.8.7<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>)</td>
                            <tr valign=top id="chapter_3_8_8" data-chapterid="3.8.8">
                                <td data-sort="3.8.8">3.8.8<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>6</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_3_8_9" data-chapterid="3.8.9">
                                <td data-sort="3.8.9">3.8.9<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>15</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_3_8_10" data-chapterid="3.8.10">
                                <td data-sort="3.8.10">3.8.10<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>5</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CG"><span title="Coachman by Gorbeau tenement">CG</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>)</td>
                            <tr valign=top id="chapter_3_8_11" data-chapterid="3.8.11">
                                <td data-sort="3.8.11">3.8.11<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>)</td>
                            <tr valign=top id="chapter_3_8_12" data-chapterid="3.8.12">
                                <td data-sort="3.8.12">3.8.12<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>)</td>
                            <tr valign=top id="chapter_3_8_13" data-chapterid="3.8.13">
                                <td data-sort="3.8.13">3.8.13<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>)</td>
                            <tr valign=top id="chapter_3_8_14" data-chapterid="3.8.14">
                                <td data-sort="3.8.14">3.8.14<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_15" data-chapterid="3.8.15">
                                <td data-sort="3.8.15">3.8.15<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>)</td>
                            <tr valign=top id="chapter_3_8_16" data-chapterid="3.8.16">
                                <td data-sort="3.8.16">3.8.16<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>10</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>)</td>
                            <tr valign=top id="chapter_3_8_17" data-chapterid="3.8.17">
                                <td data-sort="3.8.17">3.8.17<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>)</td>
                            <tr valign=top id="chapter_3_8_18" data-chapterid="3.8.18">
                                <td data-sort="3.8.18">3.8.18<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_3_8_19" data-chapterid="3.8.19">
                                <td data-sort="3.8.19">3.8.19<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>10</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>, <a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>)</td>
                            <tr valign=top id="chapter_3_8_20" data-chapterid="3.8.20">
                                <td data-sort="3.8.20">3.8.20<span class="chapter-lookup mizicon"></span></td>
                                <td>12</td>
                                <td>50</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_21" data-chapterid="3.8.21">
                                <td data-sort="3.8.21">3.8.21<span class="chapter-lookup mizicon"></span></td>
                                <td>10</td>
                                <td>62</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_PN"><span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_DU"><span title="Deux-millards, aka Demi-liard, a criminal">DU</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_3_8_22" data-chapterid="3.8.22">
                                <td data-sort="3.8.22">3.8.22<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_BU"><span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_1_6" data-chapterid="4.1.6">
                                <td data-sort="4.1.6">4.1.6<span class="chapter-lookup mizicon"></span></td>
                                <td>10</td>
                                <td>31</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_CP"><span title="Man playing cards with Grantaire">CP</span></a>)</td>
                            <tr valign=top id="chapter_4_2_1" data-chapterid="4.2.1">
                                <td data-sort="4.2.1">4.2.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_BU"><span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span></a>); (<a href="#character_BU"><span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_2_2" data-chapterid="4.2.2">
                                <td data-sort="4.2.2">4.2.2<span class="chapter-lookup mizicon"></span></td>
                                <td>12</td>
                                <td>12</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>); (<a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_GF"><span title="Guard, La Force prison">GF</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>); (<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_BF"><span title="Babet's girlfriend">BF</span></a>); (<a href="#character_BF"><span title="Babet's girlfriend">BF</span></a>, <a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>); (<a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>); (<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>)</td>
                            <tr valign=top id="chapter_4_2_3" data-chapterid="4.2.3">
                                <td data-sort="4.2.3">4.2.3<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_2_4" data-chapterid="4.2.4">
                                <td data-sort="4.2.4">4.2.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_3_1" data-chapterid="4.3.1">
                                <td data-sort="4.3.1">4.3.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>)</td>
                            <tr valign=top id="chapter_4_3_2" data-chapterid="4.3.2">
                                <td data-sort="4.3.2">4.3.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>)</td>
                            <tr valign=top id="chapter_4_3_4" data-chapterid="4.3.4">
                                <td data-sort="4.3.4">4.3.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_4_3_5" data-chapterid="4.3.5">
                                <td data-sort="4.3.5">4.3.5<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_4_3_6" data-chapterid="4.3.6">
                                <td data-sort="4.3.6">4.3.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_3_7" data-chapterid="4.3.7">
                                <td data-sort="4.3.7">4.3.7<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_PS"><span title="Porter, rue de l'Ouest">PS</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_3_8" data-chapterid="4.3.8">
                                <td data-sort="4.3.8">4.3.8<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_4_1" data-chapterid="4.4.1">
                                <td data-sort="4.4.1">4.4.1<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_4_2" data-chapterid="4.4.2">
                                <td data-sort="4.4.2">4.4.2<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_PL"><span title="Mother Plutarch, housekeeper of M. Mabeuf">PL</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_PL"><span title="Mother Plutarch, housekeeper of M. Mabeuf">PL</span></a>)</td>
                            <tr valign=top id="chapter_4_5_1" data-chapterid="4.5.1">
                                <td data-sort="4.5.1">4.5.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_5_2" data-chapterid="4.5.2">
                                <td data-sort="4.5.2">4.5.2<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_4_5_3" data-chapterid="4.5.3">
                                <td data-sort="4.5.3">4.5.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_5_4" data-chapterid="4.5.4">
                                <td data-sort="4.5.4">4.5.4<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_5_5" data-chapterid="4.5.5">
                                <td data-sort="4.5.5">4.5.5<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_5_6" data-chapterid="4.5.6">
                                <td data-sort="4.5.6">4.5.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_6_1" data-chapterid="4.6.1">
                                <td data-sort="4.6.1">4.6.1<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>13</td>
                                <td>(<a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>, <a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_TM"><span title="Madame Thénardier, wife of Thénardier">TM</span></a>); (<a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>, <a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>); (<a href="#character_MN"><span title="Magnon, servant of Gillenormand">MN</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>)</td>
                            <tr valign=top id="chapter_4_6_2" data-chapterid="4.6.2">
                                <td data-sort="4.6.2">4.6.2<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>17</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_BG"><span title="Barber encountered by Gavroche">BG</span></a>); (<a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>, <a href="#character_BG"><span title="Barber encountered by Gavroche">BG</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_GL"><span title="Poor girl, helped by Gavroche">GL</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_BK"><span title="Baker, visited by Gavroche">BK</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>); (<a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_6_3" data-chapterid="4.6.3">
                                <td data-sort="4.6.3">4.6.3<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>40</td>
                                <td>(<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>)</td>
                            <tr valign=top id="chapter_4_8_1" data-chapterid="4.8.1">
                                <td data-sort="4.8.1">4.8.1<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_8_2" data-chapterid="4.8.2">
                                <td data-sort="4.8.2">4.8.2<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_8_3" data-chapterid="4.8.3">
                                <td data-sort="4.8.3">4.8.3<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>7</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_8_4" data-chapterid="4.8.4">
                                <td data-sort="4.8.4">4.8.4<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>22</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_BB"><span title="Babet, member, Patron-Minette">BB</span></a>, <a href="#character_BJ"><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></a>, <a href="#character_GU"><span title="Gueulemer, member of Patron-Minette">GU</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>, <a href="#character_MO"><span title="Montparnasse, member of Patron-Minette">MO</span></a>)</td>
                            <tr valign=top id="chapter_4_8_6" data-chapterid="4.8.6">
                                <td data-sort="4.8.6">4.8.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_4_8_7" data-chapterid="4.8.7">
                                <td data-sort="4.8.7">4.8.7<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>)</td>
                            <tr valign=top id="chapter_4_9_1" data-chapterid="4.9.1">
                                <td data-sort="4.9.1">4.9.1<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_9_2" data-chapterid="4.9.2">
                                <td data-sort="4.9.2">4.9.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_9_3" data-chapterid="4.9.3">
                                <td data-sort="4.9.3">4.9.3<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>4</td>
                                <td>(<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_PL"><span title="Mother Plutarch, housekeeper of M. Mabeuf">PL</span></a>); (<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_MU"><span title="Minister of agriculture">MU</span></a>); (<a href="#character_MU"><span title="Minister of agriculture">MU</span></a>, <a href="#character_MW"><span title="Minister of agriculture's wife">MW</span></a>); (<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_GN"><span title="Gardener, encountered by Mabeuf">GN</span></a>)</td>
                            <tr valign=top id="chapter_4_11_1" data-chapterid="4.11.1">
                                <td data-sort="4.11.1">4.11.1<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_GS"><span title="Secondhand dealer from whom Gavroche 'borrows' a pistol">GS</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_11_2" data-chapterid="4.11.2">
                                <td data-sort="4.11.2">4.11.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>5</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_RP"><span title="Ragpicker">RP</span></a>, <a href="#character_TC"><span title="Three concierges, met by Gvaroche">TC</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_RP"><span title="Ragpicker">RP</span></a>, <a href="#character_TC"><span title="Three concierges, met by Gvaroche">TC</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_RP"><span title="Ragpicker">RP</span></a>)</td>
                            <tr valign=top id="chapter_4_11_3" data-chapterid="4.11.3">
                                <td data-sort="4.11.3">4.11.3<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_BG"><span title="Barber encountered by Gavroche">BG</span></a>, <a href="#character_OS"><span title="Old soldier at barbershop">OS</span></a>); (<a href="#character_BG"><span title="Barber encountered by Gavroche">BG</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_11_4" data-chapterid="4.11.4">
                                <td data-sort="4.11.4">4.11.4<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>20</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>); (<a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>)</td>
                            <tr valign=top id="chapter_4_11_5" data-chapterid="4.11.5">
                                <td data-sort="4.11.5">4.11.5<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_11_6" data-chapterid="4.11.6">
                                <td data-sort="4.11.6">4.11.6<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>8</td>
                                <td>(<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_CW"><span title="Concierge, rue de la Verrerie">CW</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_12_1" data-chapterid="4.12.1">
                                <td data-sort="4.12.1">4.12.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_HL"><span title="Mme Hucheloup, keeper of Corinth Inn">HL</span></a>); (<a href="#character_HL"><span title="Mme Hucheloup, keeper of Corinth Inn">HL</span></a>, <a href="#character_ML"><span title="Matelotte, a servant at the Corinth Inn">ML</span></a>, <a href="#character_GB"><span title="Gibolette, servant in Corinth Inn">GB</span></a>)</td>
                            <tr valign=top id="chapter_4_12_2" data-chapterid="4.12.2">
                                <td data-sort="4.12.2">4.12.2<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>15</td>
                                <td>(<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_ML"><span title="Matelotte, a servant at the Corinth Inn">ML</span></a>, <a href="#character_GB"><span title="Gibolette, servant in Corinth Inn">GB</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>); (<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_NA"><span title="Navet, friend of Gavroche">NA</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_HL"><span title="Mme Hucheloup, keeper of Corinth Inn">HL</span></a>); (<a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_ML"><span title="Matelotte, a servant at the Corinth Inn">ML</span></a>, <a href="#character_GB"><span title="Gibolette, servant in Corinth Inn">GB</span></a>); (<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>)</td>
                            <tr valign=top id="chapter_4_12_3" data-chapterid="4.12.3">
                                <td data-sort="4.12.3">4.12.3<span class="chapter-lookup mizicon"></span></td>
                                <td>11</td>
                                <td>14</td>
                                <td>(<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_ML"><span title="Matelotte, a servant at the Corinth Inn">ML</span></a>, <a href="#character_GB"><span title="Gibolette, servant in Corinth Inn">GB</span></a>); (<a href="#character_HL"><span title="Mme Hucheloup, keeper of Corinth Inn">HL</span></a>); (<a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_ML"><span title="Matelotte, a servant at the Corinth Inn">ML</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>)</td>
                            <tr valign=top id="chapter_4_12_4" data-chapterid="4.12.4">
                                <td data-sort="4.12.4">4.12.4<span class="chapter-lookup mizicon"></span></td>
                                <td>11</td>
                                <td>13</td>
                                <td>(<a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_HL"><span title="Mme Hucheloup, keeper of Corinth Inn">HL</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_HL"><span title="Mme Hucheloup, keeper of Corinth Inn">HL</span></a>, <a href="#character_ML"><span title="Matelotte, a servant at the Corinth Inn">ML</span></a>, <a href="#character_GB"><span title="Gibolette, servant in Corinth Inn">GB</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_DN"><span title="Dandy near the barricade">DN</span></a>)</td>
                            <tr valign=top id="chapter_4_12_5" data-chapterid="4.12.5">
                                <td data-sort="4.12.5">4.12.5<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_4_12_6" data-chapterid="4.12.6">
                                <td data-sort="4.12.6">4.12.6<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>28</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>)</td>
                            <tr valign=top id="chapter_4_12_7" data-chapterid="4.12.7">
                                <td data-sort="4.12.7">4.12.7<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>6</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_4_12_8" data-chapterid="4.12.8">
                                <td data-sort="4.12.8">4.12.8<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>6</td>
                                <td>(<a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>, <a href="#character_PC"><span title="Porter shot at the barricade by Le Cabuc">PC</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_QU"><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_13_1" data-chapterid="4.13.1">
                                <td data-sort="4.13.1">4.13.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_13_3" data-chapterid="4.13.3">
                                <td data-sort="4.13.3">4.13.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_14_1" data-chapterid="4.14.1">
                                <td data-sort="4.14.1">4.14.1<span class="chapter-lookup mizicon"></span></td>
                                <td>10</td>
                                <td>48</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>)</td>
                            <tr valign=top id="chapter_4_14_2" data-chapterid="4.14.2">
                                <td data-sort="4.14.2">4.14.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_MM"><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_4_14_3" data-chapterid="4.14.3">
                                <td data-sort="4.14.3">4.14.3<span class="chapter-lookup mizicon"></span></td>
                                <td>11</td>
                                <td>33</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>); (<a href="#character_GV"><span title="Government troops">GV</span></a>, <a href="#character_BA"><span title="Bahorel, member, Friends of the ABC">BA</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_14_4" data-chapterid="4.14.4">
                                <td data-sort="4.14.4">4.14.4<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>11</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_4_14_5" data-chapterid="4.14.5">
                                <td data-sort="4.14.5">4.14.5<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>16</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_JP"><span title="Prouvaire, member Friends of the ABC">JP</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_4_14_6" data-chapterid="4.14.6">
                                <td data-sort="4.14.6">4.14.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>)</td>
                            <tr valign=top id="chapter_4_14_7" data-chapterid="4.14.7">
                                <td data-sort="4.14.7">4.14.7<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>3</td>
                                <td>(<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_15_1" data-chapterid="4.15.1">
                                <td data-sort="4.15.1">4.15.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>5</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TS"><span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></a>)</td>
                            <tr valign=top id="chapter_4_15_2" data-chapterid="4.15.2">
                                <td data-sort="4.15.2">4.15.2<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_4_15_3" data-chapterid="4.15.3">
                                <td data-sort="4.15.3">4.15.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_4_15_4" data-chapterid="4.15.4">
                                <td data-sort="4.15.4">4.15.4<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_DR"><span title="Drunk Auvergnat coachman">DR</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_SI"><span title="Sergeant at the Imprimérie Royale post">SI</span></a>)</td>
                            <tr valign=top id="chapter_5_1_2" data-chapterid="5.1.2">
                                <td data-sort="5.1.2">5.1.2<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>22</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>, <a href="#character_IW"><span title="Insurgent workers">IW</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>)</td>
                            <tr valign=top id="chapter_5_1_3" data-chapterid="5.1.3">
                                <td data-sort="5.1.3">5.1.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_WA"><span title="Anonymous worker at barricade">WA</span></a>)</td>
                            <tr valign=top id="chapter_5_1_4" data-chapterid="5.1.4">
                                <td data-sort="5.1.4">5.1.4<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>17</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_IW"><span title="Insurgent workers">IW</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_IW"><span title="Insurgent workers">IW</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_IW"><span title="Insurgent workers">IW</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_IW"><span title="Insurgent workers">IW</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_5_1_5" data-chapterid="5.1.5">
                                <td data-sort="5.1.5">5.1.5<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_5_1_6" data-chapterid="5.1.6">
                                <td data-sort="5.1.6">5.1.6<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_1_7" data-chapterid="5.1.7">
                                <td data-sort="5.1.7">5.1.7<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>13</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>)</td>
                            <tr valign=top id="chapter_5_1_8" data-chapterid="5.1.8">
                                <td data-sort="5.1.8">5.1.8<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>8</td>
                                <td>(<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_GV"><span title="Government troops">GV</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>)</td>
                            <tr valign=top id="chapter_5_1_9" data-chapterid="5.1.9">
                                <td data-sort="5.1.9">5.1.9<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>9</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>)</td>
                            <tr valign=top id="chapter_5_1_10" data-chapterid="5.1.10">
                                <td data-sort="5.1.10">5.1.10<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_5_1_11" data-chapterid="5.1.11">
                                <td data-sort="5.1.11">5.1.11<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>6</td>
                                <td>(<a href="#character_GV"><span title="Government troops">GV</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>)</td>
                            <tr valign=top id="chapter_5_1_12" data-chapterid="5.1.12">
                                <td data-sort="5.1.12">5.1.12<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>1</td>
                                <td>(<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_5_1_13" data-chapterid="5.1.13">
                                <td data-sort="5.1.13">5.1.13<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>)</td>
                            <tr valign=top id="chapter_5_1_14" data-chapterid="5.1.14">
                                <td data-sort="5.1.14">5.1.14<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>); (<a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>)</td>
                            <tr valign=top id="chapter_5_1_15" data-chapterid="5.1.15">
                                <td data-sort="5.1.15">5.1.15<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>); (<a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>)</td>
                            <tr valign=top id="chapter_5_1_16" data-chapterid="5.1.16">
                                <td data-sort="5.1.16">5.1.16<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>7</td>
                                <td>(<a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>, <a href="#character_BX"><span title="Bourgeois man in Luxemburg gardens">BX</span></a>, <a href="#character_BY"><span title="Bourgeois man's son">BY</span></a>); (<a href="#character_XA"><span title="Older Child, son of Thénardier, raised by Magnon">XA</span></a>, <a href="#character_XB"><span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></a>)</td>
                            <tr valign=top id="chapter_5_1_17" data-chapterid="5.1.17">
                                <td data-sort="5.1.17">5.1.17<span class="chapter-lookup mizicon"></span></td>
                                <td>9</td>
                                <td>16</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_GA"><span title="Gavroche, son of the Thénardiers">GA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>); (<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>)</td>
                            <tr valign=top id="chapter_5_1_18" data-chapterid="5.1.18">
                                <td data-sort="5.1.18">5.1.18<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>18</td>
                                <td>(<a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_5_1_19" data-chapterid="5.1.19">
                                <td data-sort="5.1.19">5.1.19<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_EP"><span title="Eponine, daughter of the Thénardiers">EP</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_1_21" data-chapterid="5.1.21">
                                <td data-sort="5.1.21">5.1.21<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>22</td>
                                <td>(<a href="#character_GV"><span title="Government troops">GV</span></a>); (<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CR"><span title="Courfeyrac, member, Friends of the ABC">CR</span></a>, <a href="#character_BO"><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></a>, <a href="#character_FE"><span title="Feuilly, member, Friends of the ABC">FE</span></a>, <a href="#character_CM"><span title="Combeferre, member, Friends of the ABC">CM</span></a>, <a href="#character_JO"><span title="Joly, member Friends of the ABC">JO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>)</td>
                            <tr valign=top id="chapter_5_1_22" data-chapterid="5.1.22">
                                <td data-sort="5.1.22">5.1.22<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>)</td>
                            <tr valign=top id="chapter_5_1_23" data-chapterid="5.1.23">
                                <td data-sort="5.1.23">5.1.23<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_EN"><span title="Enjolras, chief of Friends of the ABC">EN</span></a>, <a href="#character_GT"><span title="Grantaire, Friends of the ABC skeptic">GT</span></a>, <a href="#character_GV"><span title="Government troops">GV</span></a>)</td>
                            <tr valign=top id="chapter_5_1_24" data-chapterid="5.1.24">
                                <td data-sort="5.1.24">5.1.24<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_3_1" data-chapterid="5.3.1">
                                <td data-sort="5.3.1">5.3.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_SW"><span title="Sewermen">SW</span></a>)</td>
                            <tr valign=top id="chapter_5_3_2" data-chapterid="5.3.2">
                                <td data-sort="5.3.2">5.3.2<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_SW"><span title="Sewermen">SW</span></a>)</td>
                            <tr valign=top id="chapter_5_3_3" data-chapterid="5.3.3">
                                <td data-sort="5.3.3">5.3.3<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_CJ"><span title="Coachman assisting Javert">CJ</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_CJ"><span title="Coachman assisting Javert">CJ</span></a>)</td>
                            <tr valign=top id="chapter_5_3_4" data-chapterid="5.3.4">
                                <td data-sort="5.3.4">5.3.4<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_3_5" data-chapterid="5.3.5">
                                <td data-sort="5.3.5">5.3.5<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_3_6" data-chapterid="5.3.6">
                                <td data-sort="5.3.6">5.3.6<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_3_7" data-chapterid="5.3.7">
                                <td data-sort="5.3.7">5.3.7<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_3_8" data-chapterid="5.3.8">
                                <td data-sort="5.3.8">5.3.8<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_3_9" data-chapterid="5.3.9">
                                <td data-sort="5.3.9">5.3.9<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_CJ"><span title="Coachman assisting Javert">CJ</span></a>)</td>
                            <tr valign=top id="chapter_5_3_10" data-chapterid="5.3.10">
                                <td data-sort="5.3.10">5.3.10<span class="chapter-lookup mizicon"></span></td>
                                <td>8</td>
                                <td>11</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_CX"><span title="Concierge at Gillenormand">CX</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CJ"><span title="Coachman assisting Javert">CJ</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_CX"><span title="Concierge at Gillenormand">CX</span></a>); (<a href="#character_CX"><span title="Concierge at Gillenormand">CX</span></a>, <a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>); (<a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>, <a href="#character_NI"><span title="Nicolette, maid to Gillenormand">NI</span></a>); (<a href="#character_NI"><span title="Nicolette, maid to Gillenormand">NI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_CJ"><span title="Coachman assisting Javert">CJ</span></a>)</td>
                            <tr valign=top id="chapter_5_3_11" data-chapterid="5.3.11">
                                <td data-sort="5.3.11">5.3.11<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_CJ"><span title="Coachman assisting Javert">CJ</span></a>, <a href="#character_JA"><span title="Javert, police officer">JA</span></a>); (<a href="#character_JA"><span title="Javert, police officer">JA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_3_12" data-chapterid="5.3.12">
                                <td data-sort="5.3.12">5.3.12<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>16</td>
                                <td>(<a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>, <a href="#character_CX"><span title="Concierge at Gillenormand">CX</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_DG"><span title="Doctor at Gillenormand">DG</span></a>); (<a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>, <a href="#character_NI"><span title="Nicolette, maid to Gillenormand">NI</span></a>); (<a href="#character_DG"><span title="Doctor at Gillenormand">DG</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_DG"><span title="Doctor at Gillenormand">DG</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_4_1" data-chapterid="5.4.1">
                                <td data-sort="5.4.1">5.4.1<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JA"><span title="Javert, police officer">JA</span></a>)</td>
                            <tr valign=top id="chapter_5_5_1" data-chapterid="5.5.1">
                                <td data-sort="5.5.1">5.5.1<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>); (<a href="#character_BZ"><span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_5_2" data-chapterid="5.5.2">
                                <td data-sort="5.5.2">5.5.2<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>2</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CX"><span title="Concierge at Gillenormand">CX</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_5_5_3" data-chapterid="5.5.3">
                                <td data-sort="5.5.3">5.5.3<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_5_5_4" data-chapterid="5.5.4">
                                <td data-sort="5.5.4">5.5.4<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>15</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CX"><span title="Concierge at Gillenormand">CX</span></a>); (<a href="#character_NI"><span title="Nicolette, maid to Gillenormand">NI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>)</td>
                            <tr valign=top id="chapter_5_5_5" data-chapterid="5.5.5">
                                <td data-sort="5.5.5">5.5.5<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_5_6" data-chapterid="5.5.6">
                                <td data-sort="5.5.6">5.5.6<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>7</td>
                                <td>(<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>)</td>
                            <tr valign=top id="chapter_5_5_7" data-chapterid="5.5.7">
                                <td data-sort="5.5.7">5.5.7<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_5_8" data-chapterid="5.5.8">
                                <td data-sort="5.5.8">5.5.8<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>3</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_6_1" data-chapterid="5.6.1">
                                <td data-sort="5.6.1">5.6.1<span class="chapter-lookup mizicon"></span></td>
                                <td>6</td>
                                <td>8</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>); (<a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>, <a href="#character_AZ"><span title="Azelma, daughter of the Thénardiers">AZ</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_6_2" data-chapterid="5.6.2">
                                <td data-sort="5.6.2">5.6.2<span class="chapter-lookup mizicon"></span></td>
                                <td>7</td>
                                <td>16</td>
                                <td>(<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_MG"><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_TG"><span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></a>); (<a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_GI"><span title="M. Gillenormand, Marius's grandfather">GI</span></a>)</td>
                            <tr valign=top id="chapter_5_6_3" data-chapterid="5.6.3">
                                <td data-sort="5.6.3">5.6.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_6_4" data-chapterid="5.6.4">
                                <td data-sort="5.6.4">5.6.4<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_7_1" data-chapterid="5.7.1">
                                <td data-sort="5.7.1">5.7.1<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>6</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_7_2" data-chapterid="5.7.2">
                                <td data-sort="5.7.2">5.7.2<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>)</td>
                            <tr valign=top id="chapter_5_8_1" data-chapterid="5.8.1">
                                <td data-sort="5.8.1">5.8.1<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_5_8_2" data-chapterid="5.8.2">
                                <td data-sort="5.8.2">5.8.2<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_5_8_3" data-chapterid="5.8.3">
                                <td data-sort="5.8.3">5.8.3<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>4</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_BQ"><span title="Basque, manservant to Gillenormand">BQ</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>); (<a href="#character_NI"><span title="Nicolette, maid to Gillenormand">NI</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_8_4" data-chapterid="5.8.4">
                                <td data-sort="5.8.4">5.8.4<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_9_1" data-chapterid="5.9.1">
                                <td data-sort="5.9.1">5.9.1<span class="chapter-lookup mizicon"></span></td>
                                <td>2</td>
                                <td>1</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_5_9_2" data-chapterid="5.9.2">
                                <td data-sort="5.9.2">5.9.2<span class="chapter-lookup mizicon"></span></td>
                                <td>4</td>
                                <td>4</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CY"><span title="Concierge at rue de l'Homme Armé">CY</span></a>); (<a href="#character_JV"><span title="Jean Valjean">JV</span></a>); (<a href="#character_CY"><span title="Concierge at rue de l'Homme Armé">CY</span></a>, <a href="#character_CZ"><span title="Husband of Concierge at rue de l'Homme Armé">CZ</span></a>); (<a href="#character_CY"><span title="Concierge at rue de l'Homme Armé">CY</span></a>, <a href="#character_DJ"><span title="Doctor to Valjean">DJ</span></a>); (<a href="#character_DJ"><span title="Doctor to Valjean">DJ</span></a>, <a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_9_3" data-chapterid="5.9.3">
                                <td data-sort="5.9.3">5.9.3<span class="chapter-lookup mizicon"></span></td>
                                <td>1</td>
                                <td>0</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>)</td>
                            <tr valign=top id="chapter_5_9_4" data-chapterid="5.9.4">
                                <td data-sort="5.9.4">5.9.4<span class="chapter-lookup mizicon"></span></td>
                                <td>3</td>
                                <td>2</td>
                                <td>(<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_TH"><span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>)</td>
                            <tr valign=top id="chapter_5_9_5" data-chapterid="5.9.5">
                                <td data-sort="5.9.5">5.9.5<span class="chapter-lookup mizicon"></span></td>
                                <td>5</td>
                                <td>5</td>
                                <td>(<a href="#character_JV"><span title="Jean Valjean">JV</span></a>, <a href="#character_CO"><span title="Cosette, daughter of Fantine">CO</span></a>, <a href="#character_MA"><span title="Marius">MA</span></a>); (<a href="#character_MA"><span title="Marius">MA</span></a>, <a href="#character_DJ"><span title="Doctor to Valjean">DJ</span></a>); (<a href="#character_DJ"><span title="Doctor to Valjean">DJ</span></a>, <a href="#character_CY"><span title="Concierge at rue de l'Homme Armé">CY</span></a>)</td>
                            </tbody>
                        </table>

                        <p class="dot">&middot;</p>

                        <h2 class="h-center table-title characters">
                            Character Table

                            <span class="jump-to">
                                <a href="#lm_table-chapters" class="jump-to-link">Go to chapter table</a>
                            </span>
                        </h2>

                        <table id="lm_table-characters" class="tablesorter lm_table lm_table-characters" style="">
                            <thead>
                            <tr valign=bottom>
                                <th align="left">Code</th>
                                <th align="left">Description</th>
                                <th align="left">Name</th>
                                <th align="left">Number of Chapters in which Character Appears</th>
                                <th align="left">Chapters Where Encounters Occur</th>
                                <th align="left">Number of Characters Encountered</th>
                                <th align="left">Characters Encountered</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr valign=top id="character_AA" data-character="AA">
                                <td>AA</td>
                                <td>Lawyer in Arras's court</td>
                                <td>Arras lawyer</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_7">1.7.7</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_AM" data-character="AM">
                                <td>AM</td>
                                <td>Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf</td>
                                <td>Abbé Mabeuf</td>
                                <td>2</td>
                                <td><a href="#chapter_3_3_2">3.3.2</a>, <a href="#chapter_3_3_4">3.3.4</a></td>
                                <td>3</td>
                                <td><span title="Doctor in Vernon">DV</span>, <span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="Woman servant to Colonel Pontmercy">WP</span></td>
                            <tr valign=top id="character_AZ" data-character="AZ">
                                <td>AZ</td>
                                <td>Azelma, daughter of the Thénardiers</td>
                                <td>Azelma</td>
                                <td>15</td>
                                <td><a href="#chapter_1_4_1">1.4.1</a>, <a href="#chapter_1_4_3">1.4.3</a>, <a href="#chapter_2_3_1">2.3.1</a>, <a href="#chapter_2_3_4">2.3.4</a>, <a href="#chapter_2_3_8">2.3.8</a>, <a href="#chapter_3_1_13">3.1.13</a>, <a href="#chapter_3_8_7">3.8.7</a>, <a href="#chapter_3_8_9">3.8.9</a>, <a href="#chapter_3_8_10">3.8.10</a>, <a href="#chapter_3_8_12">3.8.12</a>, <a href="#chapter_3_8_16">3.8.16</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_5_6_1">5.6.1</a></td>
                                <td>7</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_BA" data-character="BA">
                                <td>BA</td>
                                <td>Bahorel, member, Friends of the ABC</td>
                                <td>Bahorel</td>
                                <td>12</td>
                                <td><a href="#chapter_3_4_3">3.4.3</a>, <a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_3_4_5">3.4.5</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_4">4.12.4</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_3">4.14.3</a></td>
                                <td>14</td>
                                <td><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Government troops">GV</span>, <span title="Mme Hucheloup, keeper of Corinth Inn">HL</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_BB" data-character="BB">
                                <td>BB</td>
                                <td>Babet, member, Patron-Minette</td>
                                <td>Babet</td>
                                <td>7</td>
                                <td><a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_2_2">4.2.2</a>, <a href="#chapter_4_6_3">4.6.3</a>, <a href="#chapter_4_8_4">4.8.4</a></td>
                                <td>14</td>
                                <td><span title="Babet's girlfriend">BF</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Magnon, servant of Gillenormand">MN</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_BC" data-character="BC">
                                <td>BC</td>
                                <td>Booking clerk in Arras's court</td>
                                <td>Booking clerk</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_7">1.7.7</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_BF" data-character="BF">
                                <td>BF</td>
                                <td>Babet's girlfriend</td>
                                <td>Babet's girlfriend</td>
                                <td>1</td>
                                <td><a href="#chapter_4_2_2">4.2.2</a></td>
                                <td>2</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Magnon, servant of Gillenormand">MN</span></td>
                            <tr valign=top id="character_BG" data-character="BG">
                                <td>BG</td>
                                <td>Barber encountered by Gavroche</td>
                                <td>Paris barber</td>
                                <td>2</td>
                                <td><a href="#chapter_4_6_2">4.6.2</a>, <a href="#chapter_4_11_3">4.11.3</a></td>
                                <td>4</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Old soldier at barbershop">OS</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_BH" data-character="BH">
                                <td>BH</td>
                                <td>Stable boy in Hesdin</td>
                                <td>Stable boy</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_BI" data-character="BI">
                                <td>BI</td>
                                <td>Bailiff in Arras's court</td>
                                <td>Bailiff</td>
                                <td>3</td>
                                <td><a href="#chapter_1_7_7">1.7.7</a>, <a href="#chapter_1_7_8">1.7.8</a>, <a href="#chapter_1_7_10">1.7.10</a></td>
                                <td>2</td>
                                <td><span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_BJ" data-character="BJ">
                                <td>BJ</td>
                                <td>Brujon, criminal, associate of Patron-Minette</td>
                                <td>Brujon</td>
                                <td>7</td>
                                <td><a href="#chapter_3_8_13">3.8.13</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_2_2">4.2.2</a>, <a href="#chapter_4_6_3">4.6.3</a>, <a href="#chapter_4_8_4">4.8.4</a></td>
                                <td>15</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Guard, La Force prison">GF</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_BK" data-character="BK">
                                <td>BK</td>
                                <td>Baker, visited by Gavroche</td>
                                <td>Paris baker</td>
                                <td>1</td>
                                <td><a href="#chapter_4_6_2">4.6.2</a></td>
                                <td>1</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span></td>
                            <tr valign=top id="character_BL" data-character="BL">
                                <td>BL</td>
                                <td>Blachevelle, Parisian student, lover of Favourite</td>
                                <td>Blachevelle</td>
                                <td>6</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_7">1.3.7</a>, <a href="#chapter_1_3_8">1.3.8</a></td>
                                <td>7</td>
                                <td><span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_BM" data-character="BM">
                                <td>BM</td>
                                <td>M. Bamatabois, idler of M-sur-M</td>
                                <td>Bamatabois</td>
                                <td>3</td>
                                <td><a href="#chapter_1_5_12">1.5.12</a>, <a href="#chapter_1_7_9">1.7.9</a>, <a href="#chapter_1_7_10">1.7.10</a></td>
                                <td>9</td>
                                <td><span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Counsel for the defense in Champmathieu's trial">CK</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_BO" data-character="BO">
                                <td>BO</td>
                                <td>Bossuet (Lesgle), member, Friends of the ABC</td>
                                <td>Bossuet</td>
                                <td>21</td>
                                <td><a href="#chapter_3_4_2">3.4.2</a>, <a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_3_4_5">3.4.5</a>, <a href="#chapter_3_8_15">3.8.15</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_7">5.1.7</a>, <a href="#chapter_5_1_9">5.1.9</a>, <a href="#chapter_5_1_11">5.1.11</a>, <a href="#chapter_5_1_12">5.1.12</a>, <a href="#chapter_5_1_14">5.1.14</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_21">5.1.21</a></td>
                                <td>18</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gibolette, servant in Corinth Inn">GB</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Government troops">GV</span>, <span title="Insurgent workers">IW</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Navet, friend of Gavroche">NA</span></td>
                            <tr valign=top id="character_BQ" data-character="BQ">
                                <td>BQ</td>
                                <td>Basque, manservant to Gillenormand</td>
                                <td>Basque</td>
                                <td>7</td>
                                <td><a href="#chapter_4_8_7">4.8.7</a>, <a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_12">5.3.12</a>, <a href="#chapter_5_6_2">5.6.2</a>, <a href="#chapter_5_7_1">5.7.1</a>, <a href="#chapter_5_8_1">5.8.1</a>, <a href="#chapter_5_8_3">5.8.3</a></td>
                                <td>7</td>
                                <td><span title="Concierge at Gillenormand">CX</span>, <span title="Doctor at Gillenormand">DG</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span>, <span title="Nicolette, maid to Gillenormand">NI</span></td>
                            <tr valign=top id="character_BR" data-character="BR">
                                <td>BR</td>
                                <td>Brevet, convict in the galleys with Valjean</td>
                                <td>Brevet</td>
                                <td>2</td>
                                <td><a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a></td>
                                <td>7</td>
                                <td><span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_BS" data-character="BS">
                                <td>BS</td>
                                <td>Boatswain on the Orion saved by Valjean</td>
                                <td>Boatswain</td>
                                <td>1</td>
                                <td><a href="#chapter_2_2_3">2.2.3</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_BT" data-character="BT">
                                <td>BT</td>
                                <td>Baroness de T-, friend of M. Gillenormand</td>
                                <td>Baroness de T</td>
                                <td>2</td>
                                <td><a href="#chapter_3_3_1">3.3.1</a>, <a href="#chapter_3_3_3">3.3.3</a></td>
                                <td>3</td>
                                <td><span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Count Lamothe, member of Baroness de T-'s salon">LA</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_BU" data-character="BU">
                                <td>BU</td>
                                <td>Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement</td>
                                <td>Mme Burgon</td>
                                <td>4</td>
                                <td><a href="#chapter_3_6_5">3.6.5</a>, <a href="#chapter_3_8_22">3.8.22</a>, <a href="#chapter_4_2_1">4.2.1</a></td>
                                <td>4</td>
                                <td><span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Javert, police officer">JA</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_BW" data-character="BW">
                                <td>BW</td>
                                <td>Master Bourgaillard, wheelright</td>
                                <td>Wheelright</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_BX" data-character="BX">
                                <td>BX</td>
                                <td>Bourgeois man in Luxemburg gardens</td>
                                <td>Bourgeois man</td>
                                <td>1</td>
                                <td><a href="#chapter_5_1_16">5.1.16</a></td>
                                <td>3</td>
                                <td><span title="Bourgeois man's son">BY</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_BY" data-character="BY">
                                <td>BY</td>
                                <td>Bourgeois man's son</td>
                                <td>Bourgeois son</td>
                                <td>1</td>
                                <td><a href="#chapter_5_1_16">5.1.16</a></td>
                                <td>3</td>
                                <td><span title="Bourgeois man in Luxemburg gardens">BX</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_BZ" data-character="BZ">
                                <td>BZ</td>
                                <td>Boulatruelle, former convict and road mender in Montfermeil</td>
                                <td>Boulatruelle</td>
                                <td>5</td>
                                <td><a href="#chapter_2_2_2">2.2.2</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_5_5_1">5.5.1</a></td>
                                <td>5</td>
                                <td><span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Jean Valjean">JV</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></td>
                            <tr valign=top id="character_CA" data-character="CA">
                                <td>CA</td>
                                <td>Curé in Digne</td>
                                <td>Curé, Digne</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_6">1.1.6</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_CB" data-character="CB">
                                <td>CB</td>
                                <td>Curé in the mountains near Digne</td>
                                <td>Mountain curé</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_7">1.1.7</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_CC" data-character="CC">
                                <td>CC</td>
                                <td>Cochepaille, convict in the galleys with Valjean</td>
                                <td>Cochepaille</td>
                                <td>2</td>
                                <td><a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a></td>
                                <td>7</td>
                                <td><span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_CD" data-character="CD">
                                <td>CD</td>
                                <td>Man condemned to death</td>
                                <td>Condemned to death</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_4">1.1.4</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_CE" data-character="CE">
                                <td>CE</td>
                                <td>Coachman of the mail to Arras</td>
                                <td>Arras coachman</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_CF" data-character="CF">
                                <td>CF</td>
                                <td>Montfermeuil coachman</td>
                                <td>Montfermeuil coachman</td>
                                <td>1</td>
                                <td><a href="#chapter_2_3_6">2.3.6</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_CG" data-character="CG">
                                <td>CG</td>
                                <td>Coachman by Gorbeau tenement</td>
                                <td>Parisian coachman</td>
                                <td>1</td>
                                <td><a href="#chapter_3_8_10">3.8.10</a></td>
                                <td>1</td>
                                <td><span title="Marius">MA</span></td>
                            <tr valign=top id="character_CH" data-character="CH">
                                <td>CH</td>
                                <td>Champmathieu, accused thief mistaken for Valjean</td>
                                <td>Champmathieu</td>
                                <td>5</td>
                                <td><a href="#chapter_1_7_9">1.7.9</a>, <a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a>, <a href="#chapter_1_8_3">1.8.3</a></td>
                                <td>8</td>
                                <td><span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Counsel for the defense in Champmathieu's trial">CK</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_CI" data-character="CI">
                                <td>CI</td>
                                <td>Cashier at M.Madeleine's manufactory</td>
                                <td>Cashier</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_2">1.7.2</a></td>
                                <td>1</td>
                                <td><span title="Portress of JV in M-sur-M">PO</span></td>
                            <tr valign=top id="character_CJ" data-character="CJ">
                                <td>CJ</td>
                                <td>Coachman assisting Javert</td>
                                <td>Javert's coachman</td>
                                <td>4</td>
                                <td><a href="#chapter_5_3_3">5.3.3</a>, <a href="#chapter_5_3_9">5.3.9</a>, <a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_11">5.3.11</a></td>
                                <td>3</td>
                                <td><span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_CK" data-character="CK">
                                <td>CK</td>
                                <td>Counsel for the defense in Champmathieu's trial</td>
                                <td>Defense counsel</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_9">1.7.9</a></td>
                                <td>5</td>
                                <td><span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_CL" data-character="CL">
                                <td>CL</td>
                                <td>Countess de Lô, distant relative of Myriel</td>
                                <td>Countess de Lô</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_4">1.1.4</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_CM" data-character="CM">
                                <td>CM</td>
                                <td>Combeferre, member, Friends of the ABC</td>
                                <td>Combeferre</td>
                                <td>21</td>
                                <td><a href="#chapter_3_4_3">3.4.3</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_11_5">4.11.5</a>, <a href="#chapter_4_11_6">4.11.6</a>, <a href="#chapter_4_12_4">4.12.4</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_12_8">4.12.8</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_4">5.1.4</a>, <a href="#chapter_5_1_7">5.1.7</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_9">5.1.9</a>, <a href="#chapter_5_1_12">5.1.12</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_21">5.1.21</a></td>
                                <td>14</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Government troops">GV</span>, <span title="Insurgent workers">IW</span>, <span title="Javert, police officer">JA</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_CN" data-character="CN">
                                <td>CN</td>
                                <td>Chenildieu, convict in the galleys with Valjean</td>
                                <td>Chenildieu</td>
                                <td>2</td>
                                <td><a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a></td>
                                <td>7</td>
                                <td><span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_CO" data-character="CO">
                                <td>CO</td>
                                <td>Cosette, daughter of Fantine</td>
                                <td>Cosette</td>
                                <td>80</td>
                                <td><a href="#chapter_1_4_1">1.4.1</a>, <a href="#chapter_1_4_3">1.4.3</a>, <a href="#chapter_1_5_8">1.5.8</a>, <a href="#chapter_2_3_1">2.3.1</a>, <a href="#chapter_2_3_3">2.3.3</a>, <a href="#chapter_2_3_4">2.3.4</a>, <a href="#chapter_2_3_5">2.3.5</a>, <a href="#chapter_2_3_6">2.3.6</a>, <a href="#chapter_2_3_7">2.3.7</a>, <a href="#chapter_2_3_8">2.3.8</a>, <a href="#chapter_2_3_9">2.3.9</a>, <a href="#chapter_2_3_10">2.3.10</a>, <a href="#chapter_2_3_11">2.3.11</a>, <a href="#chapter_2_4_2">2.4.2</a>, <a href="#chapter_2_4_3">2.4.3</a>, <a href="#chapter_2_4_4">2.4.4</a>, <a href="#chapter_2_4_5">2.4.5</a>, <a href="#chapter_2_5_1">2.5.1</a>, <a href="#chapter_2_5_2">2.5.2</a>, <a href="#chapter_2_5_3">2.5.3</a>, <a href="#chapter_2_5_4">2.5.4</a>, <a href="#chapter_2_5_5">2.5.5</a>, <a href="#chapter_2_5_6">2.5.6</a>, <a href="#chapter_2_5_7">2.5.7</a>, <a href="#chapter_2_5_8">2.5.8</a>, <a href="#chapter_2_5_9">2.5.9</a>, <a href="#chapter_2_8_1">2.8.1</a>, <a href="#chapter_2_8_4">2.8.4</a>, <a href="#chapter_2_8_8">2.8.8</a>, <a href="#chapter_2_8_9">2.8.9</a>, <a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_3_4_5">3.4.5</a>, <a href="#chapter_3_6_1">3.6.1</a>, <a href="#chapter_3_6_2">3.6.2</a>, <a href="#chapter_3_6_3">3.6.3</a>, <a href="#chapter_3_6_4">3.6.4</a>, <a href="#chapter_3_6_5">3.6.5</a>, <a href="#chapter_3_6_6">3.6.6</a>, <a href="#chapter_3_6_7">3.6.7</a>, <a href="#chapter_3_6_8">3.6.8</a>, <a href="#chapter_3_6_9">3.6.9</a>, <a href="#chapter_3_8_8">3.8.8</a>, <a href="#chapter_3_8_9">3.8.9</a>, <a href="#chapter_3_8_10">3.8.10</a>, <a href="#chapter_4_3_1">4.3.1</a>, <a href="#chapter_4_3_2">4.3.2</a>, <a href="#chapter_4_3_4">4.3.4</a>, <a href="#chapter_4_3_5">4.3.5</a>, <a href="#chapter_4_3_6">4.3.6</a>, <a href="#chapter_4_3_7">4.3.7</a>, <a href="#chapter_4_3_8">4.3.8</a>, <a href="#chapter_4_4_1">4.4.1</a>, <a href="#chapter_4_5_1">4.5.1</a>, <a href="#chapter_4_5_2">4.5.2</a>, <a href="#chapter_4_5_3">4.5.3</a>, <a href="#chapter_4_5_6">4.5.6</a>, <a href="#chapter_4_8_1">4.8.1</a>, <a href="#chapter_4_8_2">4.8.2</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_8_6">4.8.6</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_14_7">4.14.7</a>, <a href="#chapter_4_15_1">4.15.1</a>, <a href="#chapter_5_5_4">5.5.4</a>, <a href="#chapter_5_5_6">5.5.6</a>, <a href="#chapter_5_5_7">5.5.7</a>, <a href="#chapter_5_5_8">5.5.8</a>, <a href="#chapter_5_6_1">5.6.1</a>, <a href="#chapter_5_6_2">5.6.2</a>, <a href="#chapter_5_7_1">5.7.1</a>, <a href="#chapter_5_8_1">5.8.1</a>, <a href="#chapter_5_8_2">5.8.2</a>, <a href="#chapter_5_8_3">5.8.3</a>, <a href="#chapter_5_9_1">5.9.1</a>, <a href="#chapter_5_9_4">5.9.4</a>, <a href="#chapter_5_9_5">5.9.5</a></td>
                                <td>25</td>
                                <td><span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Concierge at Gillenormand">CX</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Javert, police officer">JA</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span>, <span title="Marius">MA</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span>, <span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span>, <span title="Peddler in Thénardier's inn">PZ</span>, <span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span>, <span title="Toussaint, servant of Valjean at Rue Plumet">TS</span>, <span title="Mme Victurnien, snoop in M-sur-M">VI</span></td>
                            <tr valign=top id="character_CP" data-character="CP">
                                <td>CP</td>
                                <td>Man playing cards with Grantaire</td>
                                <td>Card player</td>
                                <td>1</td>
                                <td><a href="#chapter_4_1_6">4.1.6</a></td>
                                <td>1</td>
                                <td><span title="Grantaire, Friends of the ABC skeptic">GT</span></td>
                            <tr valign=top id="character_CR" data-character="CR">
                                <td>CR</td>
                                <td>Courfeyrac, member, Friends of the ABC</td>
                                <td>Courfeyrac</td>
                                <td>40</td>
                                <td><a href="#chapter_3_4_2">3.4.2</a>, <a href="#chapter_3_4_3">3.4.3</a>, <a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_3_4_5">3.4.5</a>, <a href="#chapter_3_4_6">3.4.6</a>, <a href="#chapter_3_5_5">3.5.5</a>, <a href="#chapter_3_6_4">3.6.4</a>, <a href="#chapter_3_6_5">3.6.5</a>, <a href="#chapter_3_6_6">3.6.6</a>, <a href="#chapter_3_8_15">3.8.15</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_2_1">4.2.1</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_9_2">4.9.2</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_11_5">4.11.5</a>, <a href="#chapter_4_11_6">4.11.6</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_4">4.12.4</a>, <a href="#chapter_4_12_5">4.12.5</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_12_8">4.12.8</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_2">4.14.2</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_4_14_7">4.14.7</a>, <a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_4">5.1.4</a>, <a href="#chapter_5_1_7">5.1.7</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_11">5.1.11</a>, <a href="#chapter_5_1_13">5.1.13</a>, <a href="#chapter_5_1_14">5.1.14</a>, <a href="#chapter_5_1_15">5.1.15</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_21">5.1.21</a></td>
                                <td>19</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Concierge, rue de la Verrerie">CW</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Government troops">GV</span>, <span title="Mme Hucheloup, keeper of Corinth Inn">HL</span>, <span title="Insurgent workers">IW</span>, <span title="Javert, police officer">JA</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_CV" data-character="CV">
                                <td>CV</td>
                                <td>Cravatte, mountain bandit</td>
                                <td>Cravatte</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_7">1.1.7</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_CW" data-character="CW">
                                <td>CW</td>
                                <td>Concierge, rue de la Verrerie</td>
                                <td>Concierge, Verrerie</td>
                                <td>1</td>
                                <td><a href="#chapter_4_11_6">4.11.6</a></td>
                                <td>1</td>
                                <td><span title="Courfeyrac, member, Friends of the ABC">CR</span></td>
                            <tr valign=top id="character_CX" data-character="CX">
                                <td>CX</td>
                                <td>Concierge at Gillenormand</td>
                                <td>Concierge, Gillenormand</td>
                                <td>4</td>
                                <td><a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_12">5.3.12</a>, <a href="#chapter_5_5_2">5.5.2</a>, <a href="#chapter_5_5_4">5.5.4</a></td>
                                <td>7</td>
                                <td><span title="Basque, manservant to Gillenormand">BQ</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Doctor at Gillenormand">DG</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></td>
                            <tr valign=top id="character_CY" data-character="CY">
                                <td>CY</td>
                                <td>Concierge at rue de l'Homme Armé</td>
                                <td>Concierge, l'Homme Armé</td>
                                <td>2</td>
                                <td><a href="#chapter_5_9_2">5.9.2</a>, <a href="#chapter_5_9_5">5.9.5</a></td>
                                <td>3</td>
                                <td><span title="Husband of Concierge at rue de l'Homme Armé">CZ</span>, <span title="Doctor to Valjean">DJ</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_CZ" data-character="CZ">
                                <td>CZ</td>
                                <td>Husband of Concierge at rue de l'Homme Armé</td>
                                <td>Concierge's husband</td>
                                <td>1</td>
                                <td><a href="#chapter_5_9_2">5.9.2</a></td>
                                <td>1</td>
                                <td><span title="Concierge at rue de l'Homme Armé">CY</span></td>
                            <tr valign=top id="character_DA" data-character="DA">
                                <td>DA</td>
                                <td>Dahlia, mistress of Listolier</td>
                                <td>Dahlia</td>
                                <td>6</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_8">1.3.8</a>, <a href="#chapter_1_3_9">1.3.9</a></td>
                                <td>8</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Waiter at Bombarda">WB</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_DG" data-character="DG">
                                <td>DG</td>
                                <td>Doctor at Gillenormand</td>
                                <td>Doctor, Gillenormand</td>
                                <td>1</td>
                                <td><a href="#chapter_5_3_12">5.3.12</a></td>
                                <td>5</td>
                                <td><span title="Basque, manservant to Gillenormand">BQ</span>, <span title="Concierge at Gillenormand">CX</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Marius">MA</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></td>
                            <tr valign=top id="character_DH" data-character="DH">
                                <td>DH</td>
                                <td>Duc d'Havré, accompanies Louis-Philippe</td>
                                <td>Duc d'Havré</td>
                                <td>1</td>
                                <td><a href="#chapter_2_3_6">2.3.6</a></td>
                                <td>2</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="Louis-Philippe d'Orléans, King of France">LP</span></td>
                            <tr valign=top id="character_DJ" data-character="DJ">
                                <td>DJ</td>
                                <td>Doctor to Valjean</td>
                                <td>Doctor, Valjean</td>
                                <td>2</td>
                                <td><a href="#chapter_5_9_2">5.9.2</a>, <a href="#chapter_5_9_5">5.9.5</a></td>
                                <td>3</td>
                                <td><span title="Concierge at rue de l'Homme Armé">CY</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_DM" data-character="DM">
                                <td>DM</td>
                                <td>Dowager in M-sur-M</td>
                                <td>Dowager M-s-M</td>
                                <td>1</td>
                                <td><a href="#chapter_1_5_4">1.5.4</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_DN" data-character="DN">
                                <td>DN</td>
                                <td>Dandy near the barricade</td>
                                <td>Dandy</td>
                                <td>1</td>
                                <td><a href="#chapter_4_12_4">4.12.4</a></td>
                                <td>1</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span></td>
                            <tr valign=top id="character_DO" data-character="DO">
                                <td>DO</td>
                                <td>Distillery foreman in Grasse</td>
                                <td>Foreman, Grasse</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_9">1.2.9</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_DR" data-character="DR">
                                <td>DR</td>
                                <td>Drunk Auvergnat coachman</td>
                                <td>Drunk coachman</td>
                                <td>1</td>
                                <td><a href="#chapter_4_15_4">4.15.4</a></td>
                                <td>1</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span></td>
                            <tr valign=top id="character_DS" data-character="DS">
                                <td>DS</td>
                                <td>Doctor in M-sur-M hospital</td>
                                <td>Doctor M-s-M</td>
                                <td>3</td>
                                <td><a href="#chapter_1_6_1">1.6.1</a>, <a href="#chapter_1_7_6">1.7.6</a>, <a href="#chapter_1_8_2">1.8.2</a></td>
                                <td>3</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Jean Valjean">JV</span>, <span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></td>
                            <tr valign=top id="character_DU" data-character="DU">
                                <td>DU</td>
                                <td>Deux-millards, aka Demi-liard, a criminal</td>
                                <td>Deuxmilliards</td>
                                <td>5</td>
                                <td><a href="#chapter_3_8_13">3.8.13</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a></td>
                                <td>11</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_DV" data-character="DV">
                                <td>DV</td>
                                <td>Doctor in Vernon</td>
                                <td>Doctor, Vernon</td>
                                <td>1</td>
                                <td><a href="#chapter_3_3_4">3.3.4</a></td>
                                <td>3</td>
                                <td><span title="Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf">AM</span>, <span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="Woman servant to Colonel Pontmercy">WP</span></td>
                            <tr valign=top id="character_EN" data-character="EN">
                                <td>EN</td>
                                <td>Enjolras, chief of Friends of the ABC</td>
                                <td>Enjolras</td>
                                <td>37</td>
                                <td><a href="#chapter_3_4_3">3.4.3</a>, <a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_3_4_5">3.4.5</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_11_5">4.11.5</a>, <a href="#chapter_4_11_6">4.11.6</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_4">4.12.4</a>, <a href="#chapter_4_12_5">4.12.5</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_12_7">4.12.7</a>, <a href="#chapter_4_12_8">4.12.8</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_2">4.14.2</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_3">5.1.3</a>, <a href="#chapter_5_1_4">5.1.4</a>, <a href="#chapter_5_1_6">5.1.6</a>, <a href="#chapter_5_1_7">5.1.7</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_9">5.1.9</a>, <a href="#chapter_5_1_11">5.1.11</a>, <a href="#chapter_5_1_13">5.1.13</a>, <a href="#chapter_5_1_14">5.1.14</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_19">5.1.19</a>, <a href="#chapter_5_1_21">5.1.21</a>, <a href="#chapter_5_1_22">5.1.22</a>, <a href="#chapter_5_1_23">5.1.23</a></td>
                                <td>20</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gibolette, servant in Corinth Inn">GB</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Government troops">GV</span>, <span title="Insurgent workers">IW</span>, <span title="Javert, police officer">JA</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Anonymous worker at barricade">WA</span></td>
                            <tr valign=top id="character_EP" data-character="EP">
                                <td>EP</td>
                                <td>Eponine, daughter of the Thénardiers</td>
                                <td>Eponine</td>
                                <td>29</td>
                                <td><a href="#chapter_1_4_1">1.4.1</a>, <a href="#chapter_1_4_3">1.4.3</a>, <a href="#chapter_2_3_1">2.3.1</a>, <a href="#chapter_2_3_4">2.3.4</a>, <a href="#chapter_2_3_8">2.3.8</a>, <a href="#chapter_3_1_13">3.1.13</a>, <a href="#chapter_3_8_3">3.8.3</a>, <a href="#chapter_3_8_4">3.8.4</a>, <a href="#chapter_3_8_7">3.8.7</a>, <a href="#chapter_3_8_8">3.8.8</a>, <a href="#chapter_3_8_9">3.8.9</a>, <a href="#chapter_3_8_11">3.8.11</a>, <a href="#chapter_3_8_12">3.8.12</a>, <a href="#chapter_3_8_16">3.8.16</a>, <a href="#chapter_4_2_2">4.2.2</a>, <a href="#chapter_4_2_3">4.2.3</a>, <a href="#chapter_4_2_4">4.2.4</a>, <a href="#chapter_4_6_1">4.6.1</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_8_4">4.8.4</a>, <a href="#chapter_4_9_1">4.9.1</a>, <a href="#chapter_4_9_2">4.9.2</a>, <a href="#chapter_4_11_6">4.11.6</a>, <a href="#chapter_4_12_8">4.12.8</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_4_14_6">4.14.6</a>, <a href="#chapter_4_14_7">4.14.7</a>, <a href="#chapter_5_1_19">5.1.19</a></td>
                                <td>16</td>
                                <td><span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Magnon, servant of Gillenormand">MN</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_FA" data-character="FA">
                                <td>FA</td>
                                <td>Fameuil, Parisian student, lover of  Zéphine</td>
                                <td>Fameuil</td>
                                <td>6</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_7">1.3.7</a>, <a href="#chapter_1_3_8">1.3.8</a></td>
                                <td>7</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_FB" data-character="FB">
                                <td>FB</td>
                                <td>Barber to whom Fantine sells her hair</td>
                                <td>Barber</td>
                                <td>1</td>
                                <td><a href="#chapter_1_5_10">1.5.10</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_FD" data-character="FD">
                                <td>FD</td>
                                <td>Secondhand dealer who sold furniture to Fantine</td>
                                <td>Furniture seller</td>
                                <td>1</td>
                                <td><a href="#chapter_1_5_9">1.5.9</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_FE" data-character="FE">
                                <td>FE</td>
                                <td>Feuilly, member, Friends of the ABC</td>
                                <td>Feuilly</td>
                                <td>10</td>
                                <td><a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_21">5.1.21</a></td>
                                <td>15</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gibolette, servant in Corinth Inn">GB</span>, <span title="Government troops">GV</span>, <span title="Insurgent workers">IW</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Marius">MA</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_FF" data-character="FF">
                                <td>FF</td>
                                <td>Fauchelevent, failed notary turned carter in M-sur-M</td>
                                <td>Fauchelevent</td>
                                <td>12</td>
                                <td><a href="#chapter_1_5_6">1.5.6</a>, <a href="#chapter_2_5_8">2.5.8</a>, <a href="#chapter_2_5_9">2.5.9</a>, <a href="#chapter_2_8_1">2.8.1</a>, <a href="#chapter_2_8_2">2.8.2</a>, <a href="#chapter_2_8_3">2.8.3</a>, <a href="#chapter_2_8_4">2.8.4</a>, <a href="#chapter_2_8_5">2.8.5</a>, <a href="#chapter_2_8_7">2.8.7</a>, <a href="#chapter_2_8_8">2.8.8</a>, <a href="#chapter_2_8_9">2.8.9</a></td>
                                <td>5</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Gribier, new gravedigger at Vaugirard cemetery">GR</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span></td>
                            <tr valign=top id="character_FL" data-character="FL">
                                <td>FL</td>
                                <td>Fantine's landlord</td>
                                <td>Landlord</td>
                                <td>1</td>
                                <td><a href="#chapter_1_5_9">1.5.9</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_FM" data-character="FM">
                                <td>FM</td>
                                <td>Fisherman in Digne</td>
                                <td>Fisherman, Digne</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>3</td>
                                <td><span title="Labarre, innkeeper in Digne">JL</span>, <span title="Jean Valjean">JV</span>, <span title="Tavern keeper in Digne">KT</span></td>
                            <tr valign=top id="character_FN" data-character="FN">
                                <td>FN</td>
                                <td>Fantine, mistress of Tholomyès</td>
                                <td>Fantine</td>
                                <td>21</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_8">1.3.8</a>, <a href="#chapter_1_3_9">1.3.9</a>, <a href="#chapter_1_4_1">1.4.1</a>, <a href="#chapter_1_5_8">1.5.8</a>, <a href="#chapter_1_5_9">1.5.9</a>, <a href="#chapter_1_5_10">1.5.10</a>, <a href="#chapter_1_5_12">1.5.12</a>, <a href="#chapter_1_5_13">1.5.13</a>, <a href="#chapter_1_6_1">1.6.1</a>, <a href="#chapter_1_7_1">1.7.1</a>, <a href="#chapter_1_7_6">1.7.6</a>, <a href="#chapter_1_8_1">1.8.1</a>, <a href="#chapter_1_8_2">1.8.2</a>, <a href="#chapter_1_8_3">1.8.3</a>, <a href="#chapter_1_8_4">1.8.4</a></td>
                                <td>25</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Dahlia, mistress of Listolier">DA</span>, <span title="Doctor in M-sur-M hospital">DS</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Barber to whom Fantine sells her hair">FB</span>, <span title="Secondhand dealer who sold furniture to Fantine">FD</span>, <span title="Fantine's landlord">FL</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Itinerant dentist">ID</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Marguerite, friend of Fantine in M-sur-M">MT</span>, <span title="Neighbor of Thénardiers">NT</span>, <span title="Supervisor in M. Madeleine's factory">SF</span>, <span title="Servant at the hospital in M-sur-M">SM</span>, <span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span>, <span title="Mme Victurnien, snoop in M-sur-M">VI</span>, <span title="Waiter at Bombarda">WB</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_FT" data-character="FT">
                                <td>FT</td>
                                <td>Tholomyès, Parisian student, lover of Fantine</td>
                                <td>Tholomyès</td>
                                <td>6</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_7">1.3.7</a>, <a href="#chapter_1_3_8">1.3.8</a></td>
                                <td>7</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_FV" data-character="FV">
                                <td>FV</td>
                                <td>Favourite, mistress of Blachevelle</td>
                                <td>Favourite</td>
                                <td>7</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_7">1.3.7</a>, <a href="#chapter_1_3_8">1.3.8</a>, <a href="#chapter_1_3_9">1.3.9</a></td>
                                <td>8</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Waiter at Bombarda">WB</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_GA" data-character="GA">
                                <td>GA</td>
                                <td>Gavroche, son of the Thénardiers</td>
                                <td>Gavroche</td>
                                <td>27</td>
                                <td><a href="#chapter_2_3_1">2.3.1</a>, <a href="#chapter_3_8_22">3.8.22</a>, <a href="#chapter_4_4_2">4.4.2</a>, <a href="#chapter_4_6_2">4.6.2</a>, <a href="#chapter_4_6_3">4.6.3</a>, <a href="#chapter_4_11_1">4.11.1</a>, <a href="#chapter_4_11_2">4.11.2</a>, <a href="#chapter_4_11_3">4.11.3</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_4">4.12.4</a>, <a href="#chapter_4_12_7">4.12.7</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_4_14_7">4.14.7</a>, <a href="#chapter_4_15_2">4.15.2</a>, <a href="#chapter_4_15_4">4.15.4</a>, <a href="#chapter_5_1_7">5.1.7</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_11">5.1.11</a>, <a href="#chapter_5_1_15">5.1.15</a>, <a href="#chapter_5_1_17">5.1.17</a></td>
                                <td>33</td>
                                <td><span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Babet, member, Patron-Minette">BB</span>, <span title="Barber encountered by Gavroche">BG</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Baker, visited by Gavroche">BK</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Dandy near the barricade">DN</span>, <span title="Drunk Auvergnat coachman">DR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Poor girl, helped by Gavroche">GL</span>, <span title="Secondhand dealer from whom Gavroche 'borrows' a pistol">GS</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Government troops">GV</span>, <span title="Javert, police officer">JA</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Ragpicker">RP</span>, <span title="Sergeant at the Imprimérie Royale post">SI</span>, <span title="Three concierges, met by Gvaroche">TC</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_GB" data-character="GB">
                                <td>GB</td>
                                <td>Gibolette, servant in Corinth Inn</td>
                                <td>Gibolette</td>
                                <td>4</td>
                                <td><a href="#chapter_4_12_1">4.12.1</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_4">4.12.4</a></td>
                                <td>6</td>
                                <td><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Mme Hucheloup, keeper of Corinth Inn">HL</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span></td>
                            <tr valign=top id="character_GD" data-character="GD">
                                <td>GD</td>
                                <td>Gendarme in Digne</td>
                                <td>Gendarme, Digne</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_GE" data-character="GE">
                                <td>GE</td>
                                <td>Géborand, retired merchant of Digne</td>
                                <td>Géborand</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_4">1.1.4</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_GF" data-character="GF">
                                <td>GF</td>
                                <td>Guard, La Force prison</td>
                                <td>Guard, La Force</td>
                                <td>1</td>
                                <td><a href="#chapter_4_2_2">4.2.2</a></td>
                                <td>1</td>
                                <td><span title="Brujon, criminal, associate of Patron-Minette">BJ</span></td>
                            <tr valign=top id="character_GG" data-character="GG">
                                <td>GG</td>
                                <td>G--, a Convenionist</td>
                                <td>Conventionist</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_10">1.1.10</a></td>
                                <td>2</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span>, <span title="Shepherd boy, serves G-- the conventionist">SB</span></td>
                            <tr valign=top id="character_GI" data-character="GI">
                                <td>GI</td>
                                <td>M. Gillenormand, Marius's grandfather</td>
                                <td>M. Gillenormand</td>
                                <td>25</td>
                                <td><a href="#chapter_3_2_6">3.2.6</a>, <a href="#chapter_3_2_8">3.2.8</a>, <a href="#chapter_3_3_1">3.3.1</a>, <a href="#chapter_3_3_2">3.3.2</a>, <a href="#chapter_3_3_4">3.3.4</a>, <a href="#chapter_3_3_5">3.3.5</a>, <a href="#chapter_3_3_7">3.3.7</a>, <a href="#chapter_3_3_8">3.3.8</a>, <a href="#chapter_3_5_6">3.5.6</a>, <a href="#chapter_4_6_1">4.6.1</a>, <a href="#chapter_4_8_7">4.8.7</a>, <a href="#chapter_5_3_12">5.3.12</a>, <a href="#chapter_5_5_2">5.5.2</a>, <a href="#chapter_5_5_3">5.5.3</a>, <a href="#chapter_5_5_4">5.5.4</a>, <a href="#chapter_5_5_6">5.5.6</a>, <a href="#chapter_5_6_1">5.6.1</a>, <a href="#chapter_5_6_2">5.6.2</a></td>
                                <td>12</td>
                                <td><span title="Basque, manservant to Gillenormand">BQ</span>, <span title="Baroness de T-, friend of M. Gillenormand">BT</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Doctor at Gillenormand">DG</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span>, <span title="Magnon, servant of Gillenormand">MN</span>, <span title="Nicolette, maid to Gillenormand">NI</span>, <span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_GL" data-character="GL">
                                <td>GL</td>
                                <td>Poor girl, helped by Gavroche</td>
                                <td>Poor girl</td>
                                <td>1</td>
                                <td><a href="#chapter_4_6_2">4.6.2</a></td>
                                <td>1</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span></td>
                            <tr valign=top id="character_GN" data-character="GN">
                                <td>GN</td>
                                <td>Gardener, encountered by Mabeuf</td>
                                <td>Gardener</td>
                                <td>1</td>
                                <td><a href="#chapter_4_9_3">4.9.3</a></td>
                                <td>1</td>
                                <td><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_GP" data-character="GP">
                                <td>GP</td>
                                <td>Colonel George Pontmercy, Marius's father</td>
                                <td>Colonel Pontmercy</td>
                                <td>3</td>
                                <td><a href="#chapter_2_1_19">2.1.19</a>, <a href="#chapter_3_3_2">3.3.2</a>, <a href="#chapter_3_3_4">3.3.4</a></td>
                                <td>8</td>
                                <td><span title="Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf">AM</span>, <span title="Doctor in Vernon">DV</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Mme Pontmercy, younger daughter of Gillenormand">MP</span>, <span title="Napoleon, Emperor of France">NP</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Woman servant to Colonel Pontmercy">WP</span></td>
                            <tr valign=top id="character_GR" data-character="GR">
                                <td>GR</td>
                                <td>Gribier, new gravedigger at Vaugirard cemetery</td>
                                <td>Gribier</td>
                                <td>2</td>
                                <td><a href="#chapter_2_8_5">2.8.5</a>, <a href="#chapter_2_8_7">2.8.7</a></td>
                                <td>1</td>
                                <td><span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span></td>
                            <tr valign=top id="character_GS" data-character="GS">
                                <td>GS</td>
                                <td>Secondhand dealer from whom Gavroche 'borrows' a pistol</td>
                                <td>Secondhand dealer</td>
                                <td>1</td>
                                <td><a href="#chapter_4_11_1">4.11.1</a></td>
                                <td>1</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span></td>
                            <tr valign=top id="character_GT" data-character="GT">
                                <td>GT</td>
                                <td>Grantaire, Friends of the ABC skeptic</td>
                                <td>Grantaire</td>
                                <td>6</td>
                                <td><a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_5_1_23">5.1.23</a></td>
                                <td>9</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Man playing cards with Grantaire">CP</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Government troops">GV</span>, <span title="Mme Hucheloup, keeper of Corinth Inn">HL</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span></td>
                            <tr valign=top id="character_GU" data-character="GU">
                                <td>GU</td>
                                <td>Gueulemer, member of Patron-Minette</td>
                                <td>Gueulemer</td>
                                <td>7</td>
                                <td><a href="#chapter_3_7_3">3.7.3</a>, <a href="#chapter_3_7_4">3.7.4</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_2_2">4.2.2</a>, <a href="#chapter_4_6_3">4.6.3</a>, <a href="#chapter_4_8_4">4.8.4</a></td>
                                <td>12</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_GV" data-character="GV">
                                <td>GV</td>
                                <td>Government troops</td>
                                <td>Government troops</td>
                                <td>12</td>
                                <td><a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_5_1_7">5.1.7</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_9">5.1.9</a>, <a href="#chapter_5_1_11">5.1.11</a>, <a href="#chapter_5_1_14">5.1.14</a>, <a href="#chapter_5_1_15">5.1.15</a>, <a href="#chapter_5_1_22">5.1.22</a>, <a href="#chapter_5_1_23">5.1.23</a></td>
                                <td>13</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_HD" data-character="HD">
                                <td>HD</td>
                                <td>Hospital director in Digne</td>
                                <td>Hospital director</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_2">1.1.2</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_HL" data-character="HL">
                                <td>HL</td>
                                <td>Mme Hucheloup, keeper of Corinth Inn</td>
                                <td>Mme Hucheloup</td>
                                <td>4</td>
                                <td><a href="#chapter_4_12_1">4.12.1</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_4">4.12.4</a></td>
                                <td>5</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Gibolette, servant in Corinth Inn">GB</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span></td>
                            <tr valign=top id="character_ID" data-character="ID">
                                <td>ID</td>
                                <td>Itinerant dentist</td>
                                <td>Itinerant dentist</td>
                                <td>1</td>
                                <td><a href="#chapter_1_5_10">1.5.10</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_IK" data-character="IK">
                                <td>IK</td>
                                <td>Innkeeper's wife at Saint Pol</td>
                                <td>Innkeeper's wife</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_IS" data-character="IS">
                                <td>IS</td>
                                <td>Isabeau, baker in Faverolles</td>
                                <td>Isabeau</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_6">1.2.6</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_IW" data-character="IW">
                                <td>IW</td>
                                <td>Insurgent workers</td>
                                <td>Insurgent workers</td>
                                <td>2</td>
                                <td><a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_4">5.1.4</a></td>
                                <td>7</td>
                                <td><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_JA" data-character="JA">
                                <td>JA</td>
                                <td>Javert, police officer</td>
                                <td>Javert</td>
                                <td>33</td>
                                <td><a href="#chapter_1_5_6">1.5.6</a>, <a href="#chapter_1_5_12">1.5.12</a>, <a href="#chapter_1_5_13">1.5.13</a>, <a href="#chapter_1_6_2">1.6.2</a>, <a href="#chapter_1_8_2">1.8.2</a>, <a href="#chapter_1_8_3">1.8.3</a>, <a href="#chapter_1_8_4">1.8.4</a>, <a href="#chapter_1_8_5">1.8.5</a>, <a href="#chapter_2_4_5">2.4.5</a>, <a href="#chapter_2_5_1">2.5.1</a>, <a href="#chapter_2_5_5">2.5.5</a>, <a href="#chapter_2_5_10">2.5.10</a>, <a href="#chapter_3_8_14">3.8.14</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_2_1">4.2.1</a>, <a href="#chapter_4_11_6">4.11.6</a>, <a href="#chapter_4_12_7">4.12.7</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_4_15_1">4.15.1</a>, <a href="#chapter_5_1_6">5.1.6</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_19">5.1.19</a>, <a href="#chapter_5_3_3">5.3.3</a>, <a href="#chapter_5_3_9">5.3.9</a>, <a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_11">5.3.11</a></td>
                                <td>29</td>
                                <td><span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span>, <span title="Coachman assisting Javert">CJ</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Concierge at Gillenormand">CX</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Jean Valjean">JV</span>, <span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span>, <span title="Marius">MA</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Portress of JV in M-sur-M">PO</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Police sergeant in M-sur-M">SG</span>, <span title="Soldiers pursuing Valjean, led by Javert">SO</span>, <span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span>, <span title="Toussaint, servant of Valjean at Rue Plumet">TS</span></td>
                            <tr valign=top id="character_JD" data-character="JD">
                                <td>JD</td>
                                <td>Jailer in the prison of Digne</td>
                                <td>Jailer, Digne</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_JL" data-character="JL">
                                <td>JL</td>
                                <td>Labarre, innkeeper in Digne</td>
                                <td>Labarre</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>3</td>
                                <td><span title="Fisherman in Digne">FM</span>, <span title="Jean Valjean">JV</span>, <span title="Kitchen boy at Labarre's inn">KB</span></td>
                            <tr valign=top id="character_JM" data-character="JM">
                                <td>JM</td>
                                <td>Jeanne, sister of Valjean</td>
                                <td>Valjean's sister</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_6">1.2.6</a></td>
                                <td>2</td>
                                <td><span title="Youngest son of Valjean's sister">JN</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_JN" data-character="JN">
                                <td>JN</td>
                                <td>Youngest son of Valjean's sister</td>
                                <td>Sister's son</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_6">1.2.6</a></td>
                                <td>2</td>
                                <td><span title="Jeanne, sister of Valjean">JM</span>, <span title="Door keeper at a Paris bindery">KD</span></td>
                            <tr valign=top id="character_JO" data-character="JO">
                                <td>JO</td>
                                <td>Joly, member Friends of the ABC</td>
                                <td>Joly</td>
                                <td>11</td>
                                <td><a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_14_1">4.14.1</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_5_1_2">5.1.2</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_21">5.1.21</a></td>
                                <td>15</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gibolette, servant in Corinth Inn">GB</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Government troops">GV</span>, <span title="Insurgent workers">IW</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Marius">MA</span>, <span title="Matelotte, a servant at the Corinth Inn">ML</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_JP" data-character="JP">
                                <td>JP</td>
                                <td>Prouvaire, member Friends of the ABC</td>
                                <td>Prouvaire</td>
                                <td>10</td>
                                <td><a href="#chapter_3_6_6">3.6.6</a>, <a href="#chapter_4_1_6">4.1.6</a>, <a href="#chapter_4_11_4">4.11.4</a>, <a href="#chapter_4_12_6">4.12.6</a>, <a href="#chapter_4_12_8">4.12.8</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_4_14_5">4.14.5</a></td>
                                <td>11</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Government troops">GV</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_JU" data-character="JU">
                                <td>JU</td>
                                <td>Judge at the Arras court</td>
                                <td>Judge</td>
                                <td>5</td>
                                <td><a href="#chapter_1_7_8">1.7.8</a>, <a href="#chapter_1_7_9">1.7.9</a>, <a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a>, <a href="#chapter_1_8_3">1.8.3</a></td>
                                <td>9</td>
                                <td><span title="Bailiff in Arras's court">BI</span>, <span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Counsel for the defense in Champmathieu's trial">CK</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Jean Valjean">JV</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span></td>
                            <tr valign=top id="character_JV" data-character="JV">
                                <td>JV</td>
                                <td>Jean Valjean</td>
                                <td>Jean Valjean</td>
                                <td>132</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a>, <a href="#chapter_1_2_3">1.2.3</a>, <a href="#chapter_1_2_4">1.2.4</a>, <a href="#chapter_1_2_5">1.2.5</a>, <a href="#chapter_1_2_6">1.2.6</a>, <a href="#chapter_1_2_9">1.2.9</a>, <a href="#chapter_1_2_12">1.2.12</a>, <a href="#chapter_1_2_13">1.2.13</a>, <a href="#chapter_1_5_4">1.5.4</a>, <a href="#chapter_1_5_6">1.5.6</a>, <a href="#chapter_1_5_13">1.5.13</a>, <a href="#chapter_1_6_1">1.6.1</a>, <a href="#chapter_1_6_2">1.6.2</a>, <a href="#chapter_1_7_1">1.7.1</a>, <a href="#chapter_1_7_2">1.7.2</a>, <a href="#chapter_1_7_4">1.7.4</a>, <a href="#chapter_1_7_5">1.7.5</a>, <a href="#chapter_1_7_7">1.7.7</a>, <a href="#chapter_1_7_8">1.7.8</a>, <a href="#chapter_1_7_9">1.7.9</a>, <a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a>, <a href="#chapter_1_8_1">1.8.1</a>, <a href="#chapter_1_8_2">1.8.2</a>, <a href="#chapter_1_8_3">1.8.3</a>, <a href="#chapter_1_8_4">1.8.4</a>, <a href="#chapter_1_8_5">1.8.5</a>, <a href="#chapter_2_2_2">2.2.2</a>, <a href="#chapter_2_2_3">2.2.3</a>, <a href="#chapter_2_3_5">2.3.5</a>, <a href="#chapter_2_3_6">2.3.6</a>, <a href="#chapter_2_3_7">2.3.7</a>, <a href="#chapter_2_3_8">2.3.8</a>, <a href="#chapter_2_3_9">2.3.9</a>, <a href="#chapter_2_3_10">2.3.10</a>, <a href="#chapter_2_3_11">2.3.11</a>, <a href="#chapter_2_4_2">2.4.2</a>, <a href="#chapter_2_4_3">2.4.3</a>, <a href="#chapter_2_4_4">2.4.4</a>, <a href="#chapter_2_4_5">2.4.5</a>, <a href="#chapter_2_5_1">2.5.1</a>, <a href="#chapter_2_5_2">2.5.2</a>, <a href="#chapter_2_5_3">2.5.3</a>, <a href="#chapter_2_5_4">2.5.4</a>, <a href="#chapter_2_5_5">2.5.5</a>, <a href="#chapter_2_5_6">2.5.6</a>, <a href="#chapter_2_5_7">2.5.7</a>, <a href="#chapter_2_5_8">2.5.8</a>, <a href="#chapter_2_5_9">2.5.9</a>, <a href="#chapter_2_5_10">2.5.10</a>, <a href="#chapter_2_8_1">2.8.1</a>, <a href="#chapter_2_8_4">2.8.4</a>, <a href="#chapter_2_8_7">2.8.7</a>, <a href="#chapter_2_8_8">2.8.8</a>, <a href="#chapter_2_8_9">2.8.9</a>, <a href="#chapter_3_6_1">3.6.1</a>, <a href="#chapter_3_6_2">3.6.2</a>, <a href="#chapter_3_6_4">3.6.4</a>, <a href="#chapter_3_6_5">3.6.5</a>, <a href="#chapter_3_6_6">3.6.6</a>, <a href="#chapter_3_6_7">3.6.7</a>, <a href="#chapter_3_6_8">3.6.8</a>, <a href="#chapter_3_6_9">3.6.9</a>, <a href="#chapter_3_8_8">3.8.8</a>, <a href="#chapter_3_8_9">3.8.9</a>, <a href="#chapter_3_8_18">3.8.18</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_4_3_1">4.3.1</a>, <a href="#chapter_4_3_2">4.3.2</a>, <a href="#chapter_4_3_4">4.3.4</a>, <a href="#chapter_4_3_5">4.3.5</a>, <a href="#chapter_4_3_7">4.3.7</a>, <a href="#chapter_4_3_8">4.3.8</a>, <a href="#chapter_4_4_1">4.4.1</a>, <a href="#chapter_4_4_2">4.4.2</a>, <a href="#chapter_4_5_2">4.5.2</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_9_1">4.9.1</a>, <a href="#chapter_4_15_1">4.15.1</a>, <a href="#chapter_4_15_2">4.15.2</a>, <a href="#chapter_5_1_4">5.1.4</a>, <a href="#chapter_5_1_6">5.1.6</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_9">5.1.9</a>, <a href="#chapter_5_1_11">5.1.11</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_19">5.1.19</a>, <a href="#chapter_5_1_24">5.1.24</a>, <a href="#chapter_5_3_1">5.3.1</a>, <a href="#chapter_5_3_2">5.3.2</a>, <a href="#chapter_5_3_4">5.3.4</a>, <a href="#chapter_5_3_5">5.3.5</a>, <a href="#chapter_5_3_6">5.3.6</a>, <a href="#chapter_5_3_7">5.3.7</a>, <a href="#chapter_5_3_8">5.3.8</a>, <a href="#chapter_5_3_9">5.3.9</a>, <a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_11">5.3.11</a>, <a href="#chapter_5_5_1">5.5.1</a>, <a href="#chapter_5_5_2">5.5.2</a>, <a href="#chapter_5_5_4">5.5.4</a>, <a href="#chapter_5_5_6">5.5.6</a>, <a href="#chapter_5_5_7">5.5.7</a>, <a href="#chapter_5_5_8">5.5.8</a>, <a href="#chapter_5_6_1">5.6.1</a>, <a href="#chapter_5_6_2">5.6.2</a>, <a href="#chapter_5_7_1">5.7.1</a>, <a href="#chapter_5_8_1">5.8.1</a>, <a href="#chapter_5_8_2">5.8.2</a>, <a href="#chapter_5_8_3">5.8.3</a>, <a href="#chapter_5_9_2">5.9.2</a>, <a href="#chapter_5_9_5">5.9.5</a></td>
                                <td>87</td>
                                <td><span title="Lawyer in Arras's court">AA</span>, <span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Babet, member, Patron-Minette">BB</span>, <span title="Booking clerk in Arras's court">BC</span>, <span title="Stable boy in Hesdin">BH</span>, <span title="Bailiff in Arras's court">BI</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Basque, manservant to Gillenormand">BQ</span>, <span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Boatswain on the Orion saved by Valjean">BS</span>, <span title="Master Bourgaillard, wheelright">BW</span>, <span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Coachman of the mail to Arras">CE</span>, <span title="Montfermeuil coachman">CF</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Coachman assisting Javert">CJ</span>, <span title="Counsel for the defense in Champmathieu's trial">CK</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Concierge at Gillenormand">CX</span>, <span title="Concierge at rue de l'Homme Armé">CY</span>, <span title="Duc d'Havré, accompanies Louis-Philippe">DH</span>, <span title="Doctor to Valjean">DJ</span>, <span title="Dowager in M-sur-M">DM</span>, <span title="Distillery foreman in Grasse">DO</span>, <span title="Doctor in M-sur-M hospital">DS</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span>, <span title="Fisherman in Digne">FM</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gendarme in Digne">GD</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Government troops">GV</span>, <span title="Innkeeper's wife at Saint Pol">IK</span>, <span title="Isabeau, baker in Faverolles">IS</span>, <span title="Javert, police officer">JA</span>, <span title="Jailer in the prison of Digne">JD</span>, <span title="Labarre, innkeeper in Digne">JL</span>, <span title="Jeanne, sister of Valjean">JM</span>, <span title="Judge at the Arras court">JU</span>, <span title="Toll keeper at Austerlitz bridge">KA</span>, <span title="Tavern keeper in Digne">KT</span>, <span title="Landlady at Gorbeau House (during JVJ's stay)">LL</span>, <span title="Louis-Philippe d'Orléans, King of France">LP</span>, <span title="Landlady at an Arras hotel">LR</span>, <span title="Marius">MA</span>, <span title="Mlle Baptistine, sister of Myriel">MB</span>, <span title="Marie-Claude, neighbor of the Valjeans in Faverolles">MD</span>, <span title="Mme Magloire, housekeeper to Myriel">ME</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span>, <span title="Mother Innocent, prioress of Convent of Petit Picpus">MI</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Marquise de R-, inhabitant of Digne">MR</span>, <span title="M. Myriel, Bishop of Digne">MY</span>, <span title="Nicolette, maid to Gillenormand">NI</span>, <span title="Prosecuting attorney in Champmathieu trial">PA</span>, <span title="Peasant in Digne, whom Valjean asks for food">PD</span>, <span title="Petit Gervais, a chimney sweep">PG</span>, <span title="A priest on the road from Digne">PH</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Portress of JV in M-sur-M">PO</span>, <span title="Prison guard">PR</span>, <span title="Porter, rue de l'Ouest">PS</span>, <span title="Postillion, accompanying Valjean to Arras">PT</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Resident of Arras">RA</span>, <span title="Road mender">RM</span>, <span title="M. Scaufflaire, keeper of horses and coaches in M-sur-M">SC</span>, <span title="Servant girl in Saint Pol">SE</span>, <span title="Soldiers pursuing Valjean, led by Javert">SO</span>, <span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span>, <span title="Sewermen">SW</span>, <span title="German teamster">TE</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span>, <span title="Three gendarmes, arrested Valjean">TR</span>, <span title="Toussaint, servant of Valjean at Rue Plumet">TS</span>, <span title="Old woman in Hesdin">WH</span>, <span title="Old woman's son">WI</span></td>
                            <tr valign=top id="character_KA" data-character="KA">
                                <td>KA</td>
                                <td>Toll keeper at Austerlitz bridge</td>
                                <td>Toll keeper</td>
                                <td>1</td>
                                <td><a href="#chapter_2_5_2">2.5.2</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_KB" data-character="KB">
                                <td>KB</td>
                                <td>Kitchen boy at Labarre's inn</td>
                                <td>Kitchen boy</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>1</td>
                                <td><span title="Labarre, innkeeper in Digne">JL</span></td>
                            <tr valign=top id="character_KD" data-character="KD">
                                <td>KD</td>
                                <td>Door keeper at a Paris bindery</td>
                                <td>Door keeper</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_6">1.2.6</a></td>
                                <td>1</td>
                                <td><span title="Youngest son of Valjean's sister">JN</span></td>
                            <tr valign=top id="character_KT" data-character="KT">
                                <td>KT</td>
                                <td>Tavern keeper in Digne</td>
                                <td>Tavern keeper</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>2</td>
                                <td><span title="Fisherman in Digne">FM</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_LA" data-character="LA">
                                <td>LA</td>
                                <td>Count Lamothe, member of Baroness de T-'s salon</td>
                                <td>Count Lamothe</td>
                                <td>1</td>
                                <td><a href="#chapter_3_3_1">3.3.1</a></td>
                                <td>1</td>
                                <td><span title="Baroness de T-, friend of M. Gillenormand">BT</span></td>
                            <tr valign=top id="character_LI" data-character="LI">
                                <td>LI</td>
                                <td>Listolier, Parisian student, lover of Dahlia</td>
                                <td>Listolier</td>
                                <td>6</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_7">1.3.7</a>, <a href="#chapter_1_3_8">1.3.8</a></td>
                                <td>7</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_LL" data-character="LL">
                                <td>LL</td>
                                <td>Landlady at Gorbeau House (during JVJ's stay)</td>
                                <td>Landlady, Gorbeau</td>
                                <td>4</td>
                                <td><a href="#chapter_2_4_4">2.4.4</a>, <a href="#chapter_2_4_5">2.4.5</a>, <a href="#chapter_2_5_10">2.5.10</a></td>
                                <td>3</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_LP" data-character="LP">
                                <td>LP</td>
                                <td>Louis-Philippe d'Orléans, King of France</td>
                                <td>Louis-Philippe</td>
                                <td>1</td>
                                <td><a href="#chapter_2_3_6">2.3.6</a></td>
                                <td>2</td>
                                <td><span title="Duc d'Havré, accompanies Louis-Philippe">DH</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_LR" data-character="LR">
                                <td>LR</td>
                                <td>Landlady at an Arras hotel</td>
                                <td>Arras hotel keeper</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_7">1.7.7</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_MA" data-character="MA">
                                <td>MA</td>
                                <td>Marius</td>
                                <td>Marius</td>
                                <td>104</td>
                                <td><a href="#chapter_3_2_8">3.2.8</a>, <a href="#chapter_3_3_1">3.3.1</a>, <a href="#chapter_3_3_2">3.3.2</a>, <a href="#chapter_3_3_3">3.3.3</a>, <a href="#chapter_3_3_4">3.3.4</a>, <a href="#chapter_3_3_5">3.3.5</a>, <a href="#chapter_3_3_7">3.3.7</a>, <a href="#chapter_3_3_8">3.3.8</a>, <a href="#chapter_3_4_2">3.4.2</a>, <a href="#chapter_3_4_3">3.4.3</a>, <a href="#chapter_3_4_4">3.4.4</a>, <a href="#chapter_3_4_5">3.4.5</a>, <a href="#chapter_3_4_6">3.4.6</a>, <a href="#chapter_3_5_5">3.5.5</a>, <a href="#chapter_3_6_1">3.6.1</a>, <a href="#chapter_3_6_2">3.6.2</a>, <a href="#chapter_3_6_3">3.6.3</a>, <a href="#chapter_3_6_4">3.6.4</a>, <a href="#chapter_3_6_5">3.6.5</a>, <a href="#chapter_3_6_6">3.6.6</a>, <a href="#chapter_3_6_7">3.6.7</a>, <a href="#chapter_3_6_8">3.6.8</a>, <a href="#chapter_3_6_9">3.6.9</a>, <a href="#chapter_3_8_3">3.8.3</a>, <a href="#chapter_3_8_4">3.8.4</a>, <a href="#chapter_3_8_10">3.8.10</a>, <a href="#chapter_3_8_11">3.8.11</a>, <a href="#chapter_3_8_13">3.8.13</a>, <a href="#chapter_3_8_14">3.8.14</a>, <a href="#chapter_3_8_15">3.8.15</a>, <a href="#chapter_3_8_16">3.8.16</a>, <a href="#chapter_4_2_1">4.2.1</a>, <a href="#chapter_4_2_3">4.2.3</a>, <a href="#chapter_4_2_4">4.2.4</a>, <a href="#chapter_4_3_6">4.3.6</a>, <a href="#chapter_4_3_7">4.3.7</a>, <a href="#chapter_4_5_6">4.5.6</a>, <a href="#chapter_4_8_1">4.8.1</a>, <a href="#chapter_4_8_2">4.8.2</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_8_4">4.8.4</a>, <a href="#chapter_4_8_6">4.8.6</a>, <a href="#chapter_4_8_7">4.8.7</a>, <a href="#chapter_4_9_2">4.9.2</a>, <a href="#chapter_4_14_3">4.14.3</a>, <a href="#chapter_4_14_4">4.14.4</a>, <a href="#chapter_4_14_5">4.14.5</a>, <a href="#chapter_4_14_6">4.14.6</a>, <a href="#chapter_4_14_7">4.14.7</a>, <a href="#chapter_5_1_4">5.1.4</a>, <a href="#chapter_5_1_8">5.1.8</a>, <a href="#chapter_5_1_17">5.1.17</a>, <a href="#chapter_5_1_18">5.1.18</a>, <a href="#chapter_5_1_19">5.1.19</a>, <a href="#chapter_5_1_21">5.1.21</a>, <a href="#chapter_5_1_22">5.1.22</a>, <a href="#chapter_5_1_24">5.1.24</a>, <a href="#chapter_5_3_1">5.3.1</a>, <a href="#chapter_5_3_2">5.3.2</a>, <a href="#chapter_5_3_4">5.3.4</a>, <a href="#chapter_5_3_5">5.3.5</a>, <a href="#chapter_5_3_6">5.3.6</a>, <a href="#chapter_5_3_7">5.3.7</a>, <a href="#chapter_5_3_8">5.3.8</a>, <a href="#chapter_5_3_9">5.3.9</a>, <a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_12">5.3.12</a>, <a href="#chapter_5_5_2">5.5.2</a>, <a href="#chapter_5_5_3">5.5.3</a>, <a href="#chapter_5_5_4">5.5.4</a>, <a href="#chapter_5_5_6">5.5.6</a>, <a href="#chapter_5_5_7">5.5.7</a>, <a href="#chapter_5_5_8">5.5.8</a>, <a href="#chapter_5_6_1">5.6.1</a>, <a href="#chapter_5_6_2">5.6.2</a>, <a href="#chapter_5_7_1">5.7.1</a>, <a href="#chapter_5_8_3">5.8.3</a>, <a href="#chapter_5_9_1">5.9.1</a>, <a href="#chapter_5_9_4">5.9.4</a>, <a href="#chapter_5_9_5">5.9.5</a></td>
                                <td>35</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Basque, manservant to Gillenormand">BQ</span>, <span title="Baroness de T-, friend of M. Gillenormand">BT</span>, <span title="Mme Burgon (aka Bougon), new concierge at the Gorbeau tenement">BU</span>, <span title="Coachman by Gorbeau tenement">CG</span>, <span title="Coachman assisting Javert">CJ</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Concierge at Gillenormand">CX</span>, <span title="Doctor at Gillenormand">DG</span>, <span title="Doctor to Valjean">DJ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="Government troops">GV</span>, <span title="Insurgent workers">IW</span>, <span title="Javert, police officer">JA</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Prouvaire, member Friends of the ABC">JP</span>, <span title="Jean Valjean">JV</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span>, <span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Porter, rue de l'Ouest">PS</span>, <span title="Servant to Marius at Garbeau tenement">SR</span>, <span title="Sewermen">SW</span>, <span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Woman servant to Colonel Pontmercy">WP</span></td>
                            <tr valign=top id="character_MB" data-character="MB">
                                <td>MB</td>
                                <td>Mlle Baptistine, sister of Myriel</td>
                                <td>Mlle Baptistine</td>
                                <td>10</td>
                                <td><a href="#chapter_1_1_1">1.1.1</a>, <a href="#chapter_1_1_2">1.1.2</a>, <a href="#chapter_1_1_4">1.1.4</a>, <a href="#chapter_1_1_5">1.1.5</a>, <a href="#chapter_1_1_7">1.1.7</a>, <a href="#chapter_1_1_9">1.1.9</a>, <a href="#chapter_1_2_2">1.2.2</a>, <a href="#chapter_1_2_3">1.2.3</a>, <a href="#chapter_1_2_4">1.2.4</a>, <a href="#chapter_1_2_5">1.2.5</a></td>
                                <td>4</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="Mme Magloire, housekeeper to Myriel">ME</span>, <span title="M. Myriel, Bishop of Digne">MY</span>, <span title="Mme Boischevron, friend and correspondent of Mlle Baptistine">VB</span></td>
                            <tr valign=top id="character_MC" data-character="MC">
                                <td>MC</td>
                                <td>Marquis de Champtercier, ultra-royalist in Digne</td>
                                <td>Champtercier</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_4">1.1.4</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_MD" data-character="MD">
                                <td>MD</td>
                                <td>Marie-Claude, neighbor of the Valjeans in Faverolles</td>
                                <td>Marie-Claude</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_6">1.2.6</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_ME" data-character="ME">
                                <td>ME</td>
                                <td>Mme Magloire, housekeeper to Myriel</td>
                                <td>Mme Magloire</td>
                                <td>10</td>
                                <td><a href="#chapter_1_1_1">1.1.1</a>, <a href="#chapter_1_1_2">1.1.2</a>, <a href="#chapter_1_1_4">1.1.4</a>, <a href="#chapter_1_1_5">1.1.5</a>, <a href="#chapter_1_1_6">1.1.6</a>, <a href="#chapter_1_1_7">1.1.7</a>, <a href="#chapter_1_2_2">1.2.2</a>, <a href="#chapter_1_2_3">1.2.3</a>, <a href="#chapter_1_2_4">1.2.4</a>, <a href="#chapter_1_2_12">1.2.12</a></td>
                                <td>3</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="Mlle Baptistine, sister of Myriel">MB</span>, <span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_MG" data-character="MG">
                                <td>MG</td>
                                <td>Mlle Gillenormand, unmarried daughter of Gillenormand</td>
                                <td>Mlle Gillenormand</td>
                                <td>13</td>
                                <td><a href="#chapter_3_2_8">3.2.8</a>, <a href="#chapter_3_3_1">3.3.1</a>, <a href="#chapter_3_3_5">3.3.5</a>, <a href="#chapter_3_3_7">3.3.7</a>, <a href="#chapter_3_3_8">3.3.8</a>, <a href="#chapter_3_5_6">3.5.6</a>, <a href="#chapter_4_8_7">4.8.7</a>, <a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_12">5.3.12</a>, <a href="#chapter_5_5_4">5.5.4</a>, <a href="#chapter_5_6_2">5.6.2</a></td>
                                <td>10</td>
                                <td><span title="Basque, manservant to Gillenormand">BQ</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Concierge at Gillenormand">CX</span>, <span title="Doctor at Gillenormand">DG</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Mlle Vaubois, friend of Mlle Gillenormand">MV</span>, <span title="Nicolette, maid to Gillenormand">NI</span>, <span title="Lieutenant Théodule Gillenormand, grandnephew of Gillenormand">TG</span></td>
                            <tr valign=top id="character_MH" data-character="MH">
                                <td>MH</td>
                                <td>Mayor of Chastelar</td>
                                <td>Chastelar mayor</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_7">1.1.7</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_MI" data-character="MI">
                                <td>MI</td>
                                <td>Mother Innocent, prioress of Convent of Petit Picpus</td>
                                <td>Mother Innocent</td>
                                <td>5</td>
                                <td><a href="#chapter_2_8_2">2.8.2</a>, <a href="#chapter_2_8_3">2.8.3</a>, <a href="#chapter_2_8_8">2.8.8</a></td>
                                <td>3</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Fauchelevent, failed notary turned carter in M-sur-M">FF</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_ML" data-character="ML">
                                <td>ML</td>
                                <td>Matelotte, a servant at the Corinth Inn</td>
                                <td>Matelotte</td>
                                <td>4</td>
                                <td><a href="#chapter_4_12_1">4.12.1</a>, <a href="#chapter_4_12_2">4.12.2</a>, <a href="#chapter_4_12_3">4.12.3</a>, <a href="#chapter_4_12_4">4.12.4</a></td>
                                <td>7</td>
                                <td><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gibolette, servant in Corinth Inn">GB</span>, <span title="Grantaire, Friends of the ABC skeptic">GT</span>, <span title="Mme Hucheloup, keeper of Corinth Inn">HL</span>, <span title="Joly, member Friends of the ABC">JO</span></td>
                            <tr valign=top id="character_MM" data-character="MM">
                                <td>MM</td>
                                <td>M. Mabeuf, warden of St. Sulpice, bibliophile</td>
                                <td>M. Mabeuf</td>
                                <td>11</td>
                                <td><a href="#chapter_3_3_2">3.3.2</a>, <a href="#chapter_3_3_5">3.3.5</a>, <a href="#chapter_3_5_4">3.5.4</a>, <a href="#chapter_3_5_5">3.5.5</a>, <a href="#chapter_4_2_3">4.2.3</a>, <a href="#chapter_4_4_2">4.4.2</a>, <a href="#chapter_4_9_3">4.9.3</a>, <a href="#chapter_4_11_5">4.11.5</a>, <a href="#chapter_4_14_1">4.14.1</a></td>
                                <td>15</td>
                                <td><span title="Bahorel, member, Friends of the ABC">BA</span>, <span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span>, <span title="Combeferre, member, Friends of the ABC">CM</span>, <span title="Courfeyrac, member, Friends of the ABC">CR</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Feuilly, member, Friends of the ABC">FE</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gardener, encountered by Mabeuf">GN</span>, <span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="Government troops">GV</span>, <span title="Joly, member Friends of the ABC">JO</span>, <span title="Marius">MA</span>, <span title="Minister of agriculture">MU</span>, <span title="Mother Plutarch, housekeeper of M. Mabeuf">PL</span></td>
                            <tr valign=top id="character_MN" data-character="MN">
                                <td>MN</td>
                                <td>Magnon, servant of Gillenormand</td>
                                <td>Magnon</td>
                                <td>3</td>
                                <td><a href="#chapter_3_2_6">3.2.6</a>, <a href="#chapter_4_2_2">4.2.2</a>, <a href="#chapter_4_6_1">4.6.1</a></td>
                                <td>7</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Babet's girlfriend">BF</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_MO" data-character="MO">
                                <td>MO</td>
                                <td>Montparnasse, member of Patron-Minette</td>
                                <td>Montparnasse</td>
                                <td>7</td>
                                <td><a href="#chapter_4_4_2">4.4.2</a>, <a href="#chapter_4_6_2">4.6.2</a>, <a href="#chapter_4_6_3">4.6.3</a>, <a href="#chapter_4_8_4">4.8.4</a></td>
                                <td>8</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Jean Valjean">JV</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span></td>
                            <tr valign=top id="character_MP" data-character="MP">
                                <td>MP</td>
                                <td>Mme Pontmercy, younger daughter of Gillenormand</td>
                                <td>Mme Pontmercy</td>
                                <td>2</td>
                                <td><a href="#chapter_3_3_2">3.3.2</a></td>
                                <td>1</td>
                                <td><span title="Colonel George Pontmercy, Marius's father">GP</span></td>
                            <tr valign=top id="character_MR" data-character="MR">
                                <td>MR</td>
                                <td>Marquise de R-, inhabitant of Digne</td>
                                <td>Marquise de R</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_MS" data-character="MS">
                                <td>MS</td>
                                <td>Mayor of Senez</td>
                                <td>Mayor of Senez</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_3">1.1.3</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_MT" data-character="MT">
                                <td>MT</td>
                                <td>Marguerite, friend of Fantine in M-sur-M</td>
                                <td>Marguerite</td>
                                <td>2</td>
                                <td><a href="#chapter_1_5_9">1.5.9</a>, <a href="#chapter_1_5_10">1.5.10</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_MU" data-character="MU">
                                <td>MU</td>
                                <td>Minister of agriculture</td>
                                <td>Minister of agriculture</td>
                                <td>1</td>
                                <td><a href="#chapter_4_9_3">4.9.3</a></td>
                                <td>2</td>
                                <td><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span>, <span title="Minister of agriculture's wife">MW</span></td>
                            <tr valign=top id="character_MV" data-character="MV">
                                <td>MV</td>
                                <td>Mlle Vaubois, friend of Mlle Gillenormand</td>
                                <td>Mlle Vaubois</td>
                                <td>1</td>
                                <td><a href="#chapter_3_2_8">3.2.8</a></td>
                                <td>1</td>
                                <td><span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></td>
                            <tr valign=top id="character_MW" data-character="MW">
                                <td>MW</td>
                                <td>Minister of agriculture's wife</td>
                                <td>Minister's wife</td>
                                <td>1</td>
                                <td><a href="#chapter_4_9_3">4.9.3</a></td>
                                <td>1</td>
                                <td><span title="Minister of agriculture">MU</span></td>
                            <tr valign=top id="character_MY" data-character="MY">
                                <td>MY</td>
                                <td>M. Myriel, Bishop of Digne</td>
                                <td>Bishop Myriel</td>
                                <td>19</td>
                                <td><a href="#chapter_1_1_1">1.1.1</a>, <a href="#chapter_1_1_2">1.1.2</a>, <a href="#chapter_1_1_3">1.1.3</a>, <a href="#chapter_1_1_4">1.1.4</a>, <a href="#chapter_1_1_5">1.1.5</a>, <a href="#chapter_1_1_6">1.1.6</a>, <a href="#chapter_1_1_7">1.1.7</a>, <a href="#chapter_1_1_8">1.1.8</a>, <a href="#chapter_1_1_10">1.1.10</a>, <a href="#chapter_1_1_14">1.1.14</a>, <a href="#chapter_1_2_2">1.2.2</a>, <a href="#chapter_1_2_3">1.2.3</a>, <a href="#chapter_1_2_4">1.2.4</a>, <a href="#chapter_1_2_5">1.2.5</a>, <a href="#chapter_1_2_12">1.2.12</a></td>
                                <td>17</td>
                                <td><span title="Curé in Digne">CA</span>, <span title="Curé in the mountains near Digne">CB</span>, <span title="Man condemned to death">CD</span>, <span title="Countess de Lô, distant relative of Myriel">CL</span>, <span title="Cravatte, mountain bandit">CV</span>, <span title="Géborand, retired merchant of Digne">GE</span>, <span title="G--, a Convenionist">GG</span>, <span title="Hospital director in Digne">HD</span>, <span title="Jean Valjean">JV</span>, <span title="Mlle Baptistine, sister of Myriel">MB</span>, <span title="Marquis de Champtercier, ultra-royalist in Digne">MC</span>, <span title="Mme Magloire, housekeeper to Myriel">ME</span>, <span title="Mayor of Chastelar">MH</span>, <span title="Mayor of Senez">MS</span>, <span title="Napoleon, Emperor of France">NP</span>, <span title="Senator, Count ***, in Digne">SN</span>, <span title="Three gendarmes, arrested Valjean">TR</span></td>
                            <tr valign=top id="character_NA" data-character="NA">
                                <td>NA</td>
                                <td>Navet, friend of Gavroche</td>
                                <td>Navet</td>
                                <td>1</td>
                                <td><a href="#chapter_4_12_2">4.12.2</a></td>
                                <td>1</td>
                                <td><span title="Bossuet (Lesgle), member, Friends of the ABC">BO</span></td>
                            <tr valign=top id="character_NI" data-character="NI">
                                <td>NI</td>
                                <td>Nicolette, maid to Gillenormand</td>
                                <td>Nicolette</td>
                                <td>4</td>
                                <td><a href="#chapter_5_3_10">5.3.10</a>, <a href="#chapter_5_3_12">5.3.12</a>, <a href="#chapter_5_5_4">5.5.4</a>, <a href="#chapter_5_8_3">5.8.3</a></td>
                                <td>4</td>
                                <td><span title="Basque, manservant to Gillenormand">BQ</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Jean Valjean">JV</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></td>
                            <tr valign=top id="character_NP" data-character="NP">
                                <td>NP</td>
                                <td>Napoleon, Emperor of France</td>
                                <td>Napoleon</td>
                                <td>2</td>
                                <td><a href="#chapter_1_1_1">1.1.1</a>, <a href="#chapter_3_3_2">3.3.2</a></td>
                                <td>2</td>
                                <td><span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_NT" data-character="NT">
                                <td>NT</td>
                                <td>Neighbor of Thénardiers</td>
                                <td>Thénardiers' neighbor</td>
                                <td>1</td>
                                <td><a href="#chapter_1_4_1">1.4.1</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_OS" data-character="OS">
                                <td>OS</td>
                                <td>Old soldier at barbershop</td>
                                <td>Old soldier</td>
                                <td>1</td>
                                <td><a href="#chapter_4_11_3">4.11.3</a></td>
                                <td>1</td>
                                <td><span title="Barber encountered by Gavroche">BG</span></td>
                            <tr valign=top id="character_PA" data-character="PA">
                                <td>PA</td>
                                <td>Prosecuting attorney in Champmathieu trial</td>
                                <td>Prosecuting attorney</td>
                                <td>4</td>
                                <td><a href="#chapter_1_7_9">1.7.9</a>, <a href="#chapter_1_7_10">1.7.10</a>, <a href="#chapter_1_7_11">1.7.11</a>, <a href="#chapter_1_8_3">1.8.3</a></td>
                                <td>9</td>
                                <td><span title="M. Bamatabois, idler of M-sur-M">BM</span>, <span title="Brevet, convict in the galleys with Valjean">BR</span>, <span title="Cochepaille, convict in the galleys with Valjean">CC</span>, <span title="Champmathieu, accused thief mistaken for Valjean">CH</span>, <span title="Counsel for the defense in Champmathieu's trial">CK</span>, <span title="Chenildieu, convict in the galleys with Valjean">CN</span>, <span title="Javert, police officer">JA</span>, <span title="Judge at the Arras court">JU</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_PC" data-character="PC">
                                <td>PC</td>
                                <td>Porter shot at the barricade by Le Cabuc</td>
                                <td>Porter, barricade</td>
                                <td>1</td>
                                <td><a href="#chapter_4_12_8">4.12.8</a></td>
                                <td>1</td>
                                <td><span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span></td>
                            <tr valign=top id="character_PD" data-character="PD">
                                <td>PD</td>
                                <td>Peasant in Digne, whom Valjean asks for food</td>
                                <td>Digne peasant</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>2</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="Peasant's wife">PE</span></td>
                            <tr valign=top id="character_PE" data-character="PE">
                                <td>PE</td>
                                <td>Peasant's wife</td>
                                <td>Peasant's wife</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_1">1.2.1</a></td>
                                <td>1</td>
                                <td><span title="Peasant in Digne, whom Valjean asks for food">PD</span></td>
                            <tr valign=top id="character_PG" data-character="PG">
                                <td>PG</td>
                                <td>Petit Gervais, a chimney sweep</td>
                                <td>Petit Gervais</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_13">1.2.13</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_PH" data-character="PH">
                                <td>PH</td>
                                <td>A priest on the road from Digne</td>
                                <td>Priest, Digne</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_13">1.2.13</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_PL" data-character="PL">
                                <td>PL</td>
                                <td>Mother Plutarch, housekeeper of M. Mabeuf</td>
                                <td>Mother Plutarch</td>
                                <td>3</td>
                                <td><a href="#chapter_3_5_4">3.5.4</a>, <a href="#chapter_4_4_2">4.4.2</a>, <a href="#chapter_4_9_3">4.9.3</a></td>
                                <td>1</td>
                                <td><span title="M. Mabeuf, warden of St. Sulpice, bibliophile">MM</span></td>
                            <tr valign=top id="character_PN" data-character="PN">
                                <td>PN</td>
                                <td>Panchaud, a criminal aka as Printanier, Bigrenaille</td>
                                <td>Panchaud</td>
                                <td>5</td>
                                <td><a href="#chapter_3_8_10">3.8.10</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a></td>
                                <td>10</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_PO" data-character="PO">
                                <td>PO</td>
                                <td>Portress of JV in M-sur-M</td>
                                <td>Portress, M-s-M</td>
                                <td>3</td>
                                <td><a href="#chapter_1_7_2">1.7.2</a>, <a href="#chapter_1_7_4">1.7.4</a>, <a href="#chapter_1_8_5">1.8.5</a></td>
                                <td>3</td>
                                <td><span title="Cashier at M.Madeleine's manufactory">CI</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_PR" data-character="PR">
                                <td>PR</td>
                                <td>Prison guard</td>
                                <td>Prison guard</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_6">1.2.6</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_PS" data-character="PS">
                                <td>PS</td>
                                <td>Porter, rue de l'Ouest</td>
                                <td>Porter, r. de l'Ouest</td>
                                <td>2</td>
                                <td><a href="#chapter_3_6_9">3.6.9</a>, <a href="#chapter_4_3_7">4.3.7</a></td>
                                <td>2</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_PT" data-character="PT">
                                <td>PT</td>
                                <td>Postillion, accompanying Valjean to Arras</td>
                                <td>Postillion</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_PZ" data-character="PZ">
                                <td>PZ</td>
                                <td>Peddler in Thénardier's inn</td>
                                <td>Peddler</td>
                                <td>1</td>
                                <td><a href="#chapter_2_3_3">2.3.3</a></td>
                                <td>2</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_QU" data-character="QU">
                                <td>QU</td>
                                <td>Claquesous, member of Patron-Minette,aka Le Cabuc</td>
                                <td>Claquesous</td>
                                <td>7</td>
                                <td><a href="#chapter_3_7_3">3.7.3</a>, <a href="#chapter_3_7_4">3.7.4</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_8_4">4.8.4</a>, <a href="#chapter_4_12_8">4.12.8</a></td>
                                <td>13</td>
                                <td><span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Enjolras, chief of Friends of the ABC">EN</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Porter shot at the barricade by Le Cabuc">PC</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_RA" data-character="RA">
                                <td>RA</td>
                                <td>Resident of Arras</td>
                                <td>Arras resident</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_7">1.7.7</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_RM" data-character="RM">
                                <td>RM</td>
                                <td>Road mender</td>
                                <td>Road mender</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_RP" data-character="RP">
                                <td>RP</td>
                                <td>Ragpicker</td>
                                <td>Ragpicker</td>
                                <td>1</td>
                                <td><a href="#chapter_4_11_2">4.11.2</a></td>
                                <td>2</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Three concierges, met by Gvaroche">TC</span></td>
                            <tr valign=top id="character_SB" data-character="SB">
                                <td>SB</td>
                                <td>Shepherd boy, serves G-- the conventionist</td>
                                <td>Shepherd boy</td>
                                <td>1</td>
                                <td><a href="#chapter_1_1_10">1.1.10</a></td>
                                <td>1</td>
                                <td><span title="G--, a Convenionist">GG</span></td>
                            <tr valign=top id="character_SC" data-character="SC">
                                <td>SC</td>
                                <td>M. Scaufflaire, keeper of horses and coaches in M-sur-M</td>
                                <td>M. Scaufflaire</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_2">1.7.2</a></td>
                                <td>2</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="M. Scaufflaire's wife">SD</span></td>
                            <tr valign=top id="character_SD" data-character="SD">
                                <td>SD</td>
                                <td>M. Scaufflaire's wife</td>
                                <td>Scaufflaire's wife</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_2">1.7.2</a></td>
                                <td>1</td>
                                <td><span title="M. Scaufflaire, keeper of horses and coaches in M-sur-M">SC</span></td>
                            <tr valign=top id="character_SE" data-character="SE">
                                <td>SE</td>
                                <td>Servant girl in Saint Pol</td>
                                <td>Saint Pol Servant girl</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_SF" data-character="SF">
                                <td>SF</td>
                                <td>Supervisor in M. Madeleine's factory</td>
                                <td>Factory supervisor</td>
                                <td>2</td>
                                <td><a href="#chapter_1_5_8">1.5.8</a>, <a href="#chapter_1_5_9">1.5.9</a></td>
                                <td>1</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span></td>
                            <tr valign=top id="character_SG" data-character="SG">
                                <td>SG</td>
                                <td>Police sergeant in M-sur-M</td>
                                <td>Police sergeant M-s-M</td>
                                <td>1</td>
                                <td><a href="#chapter_1_5_13">1.5.13</a></td>
                                <td>1</td>
                                <td><span title="Javert, police officer">JA</span></td>
                            <tr valign=top id="character_SI" data-character="SI">
                                <td>SI</td>
                                <td>Sergeant at the Imprimérie Royale post</td>
                                <td>Sergeant, Imprimérie</td>
                                <td>1</td>
                                <td><a href="#chapter_4_15_4">4.15.4</a></td>
                                <td>1</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span></td>
                            <tr valign=top id="character_SM" data-character="SM">
                                <td>SM</td>
                                <td>Servant at the hospital in M-sur-M</td>
                                <td>Hospital servant</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_6">1.7.6</a></td>
                                <td>2</td>
                                <td><span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></td>
                            <tr valign=top id="character_SN" data-character="SN">
                                <td>SN</td>
                                <td>Senator, Count ***, in Digne</td>
                                <td>Senator</td>
                                <td>2</td>
                                <td><a href="#chapter_1_1_8">1.1.8</a>, <a href="#chapter_1_1_14">1.1.14</a></td>
                                <td>1</td>
                                <td><span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_SO" data-character="SO">
                                <td>SO</td>
                                <td>Soldiers pursuing Valjean, led by Javert</td>
                                <td>Soldiers</td>
                                <td>2</td>
                                <td><a href="#chapter_2_5_5">2.5.5</a>, <a href="#chapter_2_5_10">2.5.10</a></td>
                                <td>2</td>
                                <td><span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_SP" data-character="SP">
                                <td>SP</td>
                                <td>Sister Perpétue, nun at infirmary in M-sur-M</td>
                                <td>Sister Perpétue</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_1">1.7.1</a></td>
                                <td>1</td>
                                <td><span title="Sister Simplice, nun at infirmary in M-sur-M">SS</span></td>
                            <tr valign=top id="character_SR" data-character="SR">
                                <td>SR</td>
                                <td>Servant to Marius at Garbeau tenement</td>
                                <td>Marius's servant</td>
                                <td>1</td>
                                <td><a href="#chapter_3_5_5">3.5.5</a></td>
                                <td>1</td>
                                <td><span title="Marius">MA</span></td>
                            <tr valign=top id="character_SS" data-character="SS">
                                <td>SS</td>
                                <td>Sister Simplice, nun at infirmary in M-sur-M</td>
                                <td>Sister Simplice</td>
                                <td>4</td>
                                <td><a href="#chapter_1_7_1">1.7.1</a>, <a href="#chapter_1_7_6">1.7.6</a>, <a href="#chapter_1_8_1">1.8.1</a>, <a href="#chapter_1_8_5">1.8.5</a></td>
                                <td>6</td>
                                <td><span title="Doctor in M-sur-M hospital">DS</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Servant at the hospital in M-sur-M">SM</span>, <span title="Sister Perpétue, nun at infirmary in M-sur-M">SP</span></td>
                            <tr valign=top id="character_SW" data-character="SW">
                                <td>SW</td>
                                <td>Sewermen</td>
                                <td>Sewermen</td>
                                <td>2</td>
                                <td><a href="#chapter_5_3_1">5.3.1</a>, <a href="#chapter_5_3_2">5.3.2</a></td>
                                <td>2</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_TC" data-character="TC">
                                <td>TC</td>
                                <td>Three concierges, met by Gvaroche</td>
                                <td>Three concierges</td>
                                <td>1</td>
                                <td><a href="#chapter_4_11_2">4.11.2</a></td>
                                <td>2</td>
                                <td><span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Ragpicker">RP</span></td>
                            <tr valign=top id="character_TE" data-character="TE">
                                <td>TE</td>
                                <td>German teamster</td>
                                <td>German teamster</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_TG" data-character="TG">
                                <td>TG</td>
                                <td>Lieutenant Théodule Gillenormand, grandnephew of Gillenormand</td>
                                <td>Théodule</td>
                                <td>6</td>
                                <td><a href="#chapter_3_2_8">3.2.8</a>, <a href="#chapter_3_3_7">3.3.7</a>, <a href="#chapter_3_5_6">3.5.6</a>, <a href="#chapter_4_5_1">4.5.1</a></td>
                                <td>4</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Marius">MA</span>, <span title="Mlle Gillenormand, unmarried daughter of Gillenormand">MG</span></td>
                            <tr valign=top id="character_TH" data-character="TH">
                                <td>TH</td>
                                <td>Thénardier, innkeeper in Montfermeil, aka Jondrette</td>
                                <td>Thénardier</td>
                                <td>34</td>
                                <td><a href="#chapter_1_4_1">1.4.1</a>, <a href="#chapter_1_4_3">1.4.3</a>, <a href="#chapter_1_5_8">1.5.8</a>, <a href="#chapter_1_6_1">1.6.1</a>, <a href="#chapter_2_1_19">2.1.19</a>, <a href="#chapter_2_2_2">2.2.2</a>, <a href="#chapter_2_3_2">2.3.2</a>, <a href="#chapter_2_3_8">2.3.8</a>, <a href="#chapter_2_3_9">2.3.9</a>, <a href="#chapter_2_3_10">2.3.10</a>, <a href="#chapter_2_5_10">2.5.10</a>, <a href="#chapter_3_1_13">3.1.13</a>, <a href="#chapter_3_8_6">3.8.6</a>, <a href="#chapter_3_8_7">3.8.7</a>, <a href="#chapter_3_8_8">3.8.8</a>, <a href="#chapter_3_8_9">3.8.9</a>, <a href="#chapter_3_8_10">3.8.10</a>, <a href="#chapter_3_8_12">3.8.12</a>, <a href="#chapter_3_8_15">3.8.15</a>, <a href="#chapter_3_8_16">3.8.16</a>, <a href="#chapter_3_8_17">3.8.17</a>, <a href="#chapter_3_8_18">3.8.18</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_6_1">4.6.1</a>, <a href="#chapter_4_6_3">4.6.3</a>, <a href="#chapter_4_8_4">4.8.4</a>, <a href="#chapter_5_3_3">5.3.3</a>, <a href="#chapter_5_3_8">5.3.8</a>, <a href="#chapter_5_6_1">5.6.1</a>, <a href="#chapter_5_9_4">5.9.4</a></td>
                                <td>19</td>
                                <td><span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Boulatruelle, former convict and road mender in Montfermeil">BZ</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Marius">MA</span>, <span title="Montparnasse, member of Patron-Minette">MO</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span>, <span title="Mme Victurnien, snoop in M-sur-M">VI</span></td>
                            <tr valign=top id="character_TM" data-character="TM">
                                <td>TM</td>
                                <td>Madame Thénardier, wife of Thénardier</td>
                                <td>Mme Thénardier</td>
                                <td>26</td>
                                <td><a href="#chapter_1_4_1">1.4.1</a>, <a href="#chapter_1_4_3">1.4.3</a>, <a href="#chapter_1_5_8">1.5.8</a>, <a href="#chapter_1_6_1">1.6.1</a>, <a href="#chapter_2_3_2">2.3.2</a>, <a href="#chapter_2_3_3">2.3.3</a>, <a href="#chapter_2_3_4">2.3.4</a>, <a href="#chapter_2_3_8">2.3.8</a>, <a href="#chapter_2_3_9">2.3.9</a>, <a href="#chapter_2_3_10">2.3.10</a>, <a href="#chapter_2_5_10">2.5.10</a>, <a href="#chapter_3_1_13">3.1.13</a>, <a href="#chapter_3_8_6">3.8.6</a>, <a href="#chapter_3_8_7">3.8.7</a>, <a href="#chapter_3_8_8">3.8.8</a>, <a href="#chapter_3_8_9">3.8.9</a>, <a href="#chapter_3_8_12">3.8.12</a>, <a href="#chapter_3_8_16">3.8.16</a>, <a href="#chapter_3_8_17">3.8.17</a>, <a href="#chapter_3_8_18">3.8.18</a>, <a href="#chapter_3_8_19">3.8.19</a>, <a href="#chapter_3_8_20">3.8.20</a>, <a href="#chapter_3_8_21">3.8.21</a>, <a href="#chapter_4_6_1">4.6.1</a></td>
                                <td>16</td>
                                <td><span title="Azelma, daughter of the Thénardiers">AZ</span>, <span title="Babet, member, Patron-Minette">BB</span>, <span title="Brujon, criminal, associate of Patron-Minette">BJ</span>, <span title="Cosette, daughter of Fantine">CO</span>, <span title="Deux-millards, aka Demi-liard, a criminal">DU</span>, <span title="Eponine, daughter of the Thénardiers">EP</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Gueulemer, member of Patron-Minette">GU</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span>, <span title="Magnon, servant of Gillenormand">MN</span>, <span title="Panchaud, a criminal aka as Printanier, Bigrenaille">PN</span>, <span title="Peddler in Thénardier's inn">PZ</span>, <span title="Claquesous, member of Patron-Minette,aka Le Cabuc">QU</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Mme Victurnien, snoop in M-sur-M">VI</span></td>
                            <tr valign=top id="character_TR" data-character="TR">
                                <td>TR</td>
                                <td>Three gendarmes, arrested Valjean</td>
                                <td>Three gendarmes</td>
                                <td>1</td>
                                <td><a href="#chapter_1_2_12">1.2.12</a></td>
                                <td>2</td>
                                <td><span title="Jean Valjean">JV</span>, <span title="M. Myriel, Bishop of Digne">MY</span></td>
                            <tr valign=top id="character_TS" data-character="TS">
                                <td>TS</td>
                                <td>Toussaint, servant of Valjean at Rue Plumet</td>
                                <td>Toussaint</td>
                                <td>6</td>
                                <td><a href="#chapter_4_3_1">4.3.1</a>, <a href="#chapter_4_3_2">4.3.2</a>, <a href="#chapter_4_3_5">4.3.5</a>, <a href="#chapter_4_5_3">4.5.3</a>, <a href="#chapter_4_8_3">4.8.3</a>, <a href="#chapter_4_15_1">4.15.1</a></td>
                                <td>3</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Javert, police officer">JA</span>, <span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_VB" data-character="VB">
                                <td>VB</td>
                                <td>Mme Boischevron, friend and correspondent of Mlle Baptistine</td>
                                <td>Mme Boischevron</td>
                                <td>2</td>
                                <td><a href="#chapter_1_1_9">1.1.9</a>, <a href="#chapter_1_2_4">1.2.4</a></td>
                                <td>1</td>
                                <td><span title="Mlle Baptistine, sister of Myriel">MB</span></td>
                            <tr valign=top id="character_VI" data-character="VI">
                                <td>VI</td>
                                <td>Mme Victurnien, snoop in M-sur-M</td>
                                <td>Mme Victurnien</td>
                                <td>2</td>
                                <td><a href="#chapter_1_5_8">1.5.8</a>, <a href="#chapter_1_5_9">1.5.9</a></td>
                                <td>4</td>
                                <td><span title="Cosette, daughter of Fantine">CO</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Thénardier, innkeeper in Montfermeil, aka Jondrette">TH</span>, <span title="Madame Thénardier, wife of Thénardier">TM</span></td>
                            <tr valign=top id="character_WA" data-character="WA">
                                <td>WA</td>
                                <td>Anonymous worker at barricade</td>
                                <td>Anonymous worker</td>
                                <td>1</td>
                                <td><a href="#chapter_5_1_3">5.1.3</a></td>
                                <td>1</td>
                                <td><span title="Enjolras, chief of Friends of the ABC">EN</span></td>
                            <tr valign=top id="character_WB" data-character="WB">
                                <td>WB</td>
                                <td>Waiter at Bombarda</td>
                                <td>Waiter</td>
                                <td>1</td>
                                <td><a href="#chapter_1_3_9">1.3.9</a></td>
                                <td>4</td>
                                <td><span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Zephine, mistress of Fameuil">ZE</span></td>
                            <tr valign=top id="character_WH" data-character="WH">
                                <td>WH</td>
                                <td>Old woman in Hesdin</td>
                                <td>Old woman</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_WI" data-character="WI">
                                <td>WI</td>
                                <td>Old woman's son</td>
                                <td>Old woman's son</td>
                                <td>1</td>
                                <td><a href="#chapter_1_7_5">1.7.5</a></td>
                                <td>1</td>
                                <td><span title="Jean Valjean">JV</span></td>
                            <tr valign=top id="character_WP" data-character="WP">
                                <td>WP</td>
                                <td>Woman servant to Colonel Pontmercy</td>
                                <td>Pontmercy's servant</td>
                                <td>2</td>
                                <td><a href="#chapter_3_3_2">3.3.2</a>, <a href="#chapter_3_3_4">3.3.4</a></td>
                                <td>4</td>
                                <td><span title="Abbé Mabeuf, curé in Vernon, brother of M. Mabeuf">AM</span>, <span title="Doctor in Vernon">DV</span>, <span title="Colonel George Pontmercy, Marius's father">GP</span>, <span title="Marius">MA</span></td>
                            <tr valign=top id="character_XA" data-character="XA">
                                <td>XA</td>
                                <td>Older Child, son of Thénardier, raised by Magnon</td>
                                <td>Older child</td>
                                <td>3</td>
                                <td><a href="#chapter_4_6_1">4.6.1</a>, <a href="#chapter_4_6_2">4.6.2</a>, <a href="#chapter_5_1_16">5.1.16</a></td>
                                <td>7</td>
                                <td><span title="Barber encountered by Gavroche">BG</span>, <span title="Bourgeois man in Luxemburg gardens">BX</span>, <span title="Bourgeois man's son">BY</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Magnon, servant of Gillenormand">MN</span>, <span title="Younger Child, son of Thénardier, raised by Magnon">XB</span></td>
                            <tr valign=top id="character_XB" data-character="XB">
                                <td>XB</td>
                                <td>Younger Child, son of Thénardier, raised by Magnon</td>
                                <td>Younger child</td>
                                <td>3</td>
                                <td><a href="#chapter_4_6_1">4.6.1</a>, <a href="#chapter_4_6_2">4.6.2</a>, <a href="#chapter_5_1_16">5.1.16</a></td>
                                <td>7</td>
                                <td><span title="Barber encountered by Gavroche">BG</span>, <span title="Bourgeois man in Luxemburg gardens">BX</span>, <span title="Bourgeois man's son">BY</span>, <span title="Gavroche, son of the Thénardiers">GA</span>, <span title="M. Gillenormand, Marius's grandfather">GI</span>, <span title="Magnon, servant of Gillenormand">MN</span>, <span title="Older Child, son of Thénardier, raised by Magnon">XA</span></td>
                            <tr valign=top id="character_ZE" data-character="ZE">
                                <td>ZE</td>
                                <td>Zephine, mistress of Fameuil</td>
                                <td>Zephine</td>
                                <td>6</td>
                                <td><a href="#chapter_1_3_3">1.3.3</a>, <a href="#chapter_1_3_4">1.3.4</a>, <a href="#chapter_1_3_6">1.3.6</a>, <a href="#chapter_1_3_8">1.3.8</a>, <a href="#chapter_1_3_9">1.3.9</a></td>
                                <td>8</td>
                                <td><span title="Blachevelle, Parisian student, lover of Favourite">BL</span>, <span title="Dahlia, mistress of Listolier">DA</span>, <span title="Fameuil, Parisian student, lover of  Zéphine">FA</span>, <span title="Fantine, mistress of Tholomyès">FN</span>, <span title="Tholomyès, Parisian student, lover of Fantine">FT</span>, <span title="Favourite, mistress of Blachevelle">FV</span>, <span title="Listolier, Parisian student, lover of Dahlia">LI</span>, <span title="Waiter at Bombarda">WB</span></td>
                            </tbody>
                        </table>
                        
                    </div>

                    <div class="tabcontent" id="ctab3">

                        <h2 class="h-center">Character Graphs</h2>

                        <p class="dot">&middot;</p>

                        <div class="pulled-content">
                            <?php echo $charsPostGraphs_content; ?>
                        </div>

                        <p class="dot">&middot;</p>

                        <ul class="lm_assets-list lm_characters-list">
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('whole-novel'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/all-parts.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Whole Novel</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('whole-novel'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('all-parts'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part1'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/part1.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Part I</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part1'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('part1'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part2'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/part2.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Part II</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part2'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('part2'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part3'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/part3.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Part III</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part3'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('part3'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part4'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/part4.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Part IV</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part4'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('part4'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part5'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/part5.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Part V</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getGexfViewURL('part5'); ?>" data-featherlight-type="iframe" data-featherlight-variant="graph" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('part5'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                        </ul>

                    </div>

                    <div class="tabcontent" id="ctab4">

                        <h2 class="h-center">Appendix</h2>

                        <ul class="lm_assets-list lm_appendix-list">
                            <li>
                                <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/jean.txt" target="_blank" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/data.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Original Knuth's Data</h3>

                                    <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/jean.txt" target="_blank" class="lm_button reg pdf asset-opts">download</a>
                                </div>
                            </li>
                            <li>
                                <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/jean-complete.txt" target="_blank" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/data.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Revised Data</h3>

                                    <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/jean-complete.txt" target="_blank" class="lm_button reg pdf asset-opts">download</a>
                                </div>
                            </li>
                            <li>
                                <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/chapter-table.html" target="_blank" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/table-2.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Chapter HTML Table</h3>

                                    <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/chapter-table.html" target="_blank" class="lm_button reg pdf asset-opts">download</a>
                                </div>
                            </li>
                            <li>
                                <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/character-table.html" target="_blank" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/table-2.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Character HTML Table</h3>

                                    <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/docs/character-table.html" target="_blank" class="lm_button reg pdf asset-opts">download</a>
                                </div>
                            </li>
                            <li>
                                <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/img/newman-girvan-old-graph.png" target="_blank" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/old-graph-ng.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Newman-Girvan Graph</h3>

                                    <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/img/newman-girvan-old-graph.png" target="_blank" class="lm_button reg pdf asset-opts">download</a>
                                </div>
                            </li>
                            <li>
                                <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/img/visone-old-graph.png" target="_blank" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/old-graph-visone.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Graph by Visone</h3>

                                    <a href="<?php echo esc_url( get_template_directory_uri() ); ?>/img/visone-old-graph.png" target="_blank" class="lm_button reg pdf asset-opts">download</a>
                                </div>
                            </li>
                        </ul>

                    </div>
                </div>

            </div>
        </li>

        <li class="su lm_main section-2" id="section-2" data-section-id="2">

            <a href="#" class="lm_accordion-section-title">
                Paris of <span class="lm_book-name">Les Misérables</span>
                <span class="lm_accordion-arrow"></span>
                <span class="helper-message"></span>
            </a>

            <div class="lm_accordion-section-content">

                <div class="pulled-content">
                    <?php echo $parisPost_content; ?>
                </div>

                <p class="dot">&bull;</p>

                <div class="tabs lm_maps-tabs">
                    <ul class="switches">
                        <li class="switch"><a class="lm_button inv" href="#mtab1" data-target="1">introduction</a></li>
                        <li class="switch"><a class="lm_button inv" href="#mtab2" data-target="2">maps</a></li>
                    </ul>

                    <div class="tabcontent" id="mtab1">
                        <div class="pulled-content reading-typography">
                            <?php echo $parisPostIntro_content; ?>
                        </div>
                    </div>

                    <div class="tabcontent" id="mtab2">

                        <h2 class="h-center">Maps</h2>

                        <p class="dot">&middot;</p>

                        <div class="pulled-content">
                            <?php echo $parisPostMaps_content; ?>
                        </div>

                        <p class="dot">&middot;</p>

                        <ul class="lm_assets-list lm_paris-images-list">
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getResourceViewURL('paris-a'); ?>" data-featherlight-type="iframe" data-featherlight-variant="map" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/paris-a.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Sites</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getResourceViewURL('paris-a'); ?>" data-featherlight-type="iframe" data-featherlight-variant="map" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('paris-a'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li>
                                <a href="#" data-featherlight="<?php echo lm_getResourceViewURL('paris-c'); ?>" data-featherlight-type="iframe" data-featherlight-variant="map" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/paris-c.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Itinerary I: <br>Gavroche, Valjean, Javert</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getResourceViewURL('paris-c'); ?>" data-featherlight-type="iframe" data-featherlight-variant="map" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('paris-c'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                            <li class="full-width">
                                <a href="#" data-featherlight="<?php echo lm_getResourceViewURL('paris-b'); ?>" data-featherlight-type="iframe" data-featherlight-variant="map" class="lm_lightbox lm_img-wrapper">
                                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/paris-b.png" alt="">
                                </a>

                                <div class="lm_asset-actions">
                                    <h3>Itinerary II: <br>Valjean and Cosette, Marius</h3>

                                    <a href="#" data-featherlight="<?php echo lm_getResourceViewURL('paris-b'); ?>" data-featherlight-type="iframe" data-featherlight-variant="map" class="lm_button reg view asset-opts">view online</a>
                                    <a href="<?php echo lm_getResourcePDFURL('paris-b'); ?>" target="_blank" class="lm_button reg pdf asset-opts">download pdf</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </li>
    </ul>

</main>

<?php get_footer(); ?>