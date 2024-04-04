<?php
    if ( $footerPost = get_page_by_path('footer') ) {
        $footerPost_content = apply_filters('the_content', $footerPost->post_content);
    }
?>

        <footer class="su lm_footer">
            <div class="content">

                <a class="footer-logo nu-wcas-logo" href="http://weinberg.northwestern.edu" target="_blank">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/nu-wcas.png" alt="MMLC logo">
                </a>

                <a class="footer-logo mmlc-logo" href="http://mmlc.northwestern.edu" target="_blank">
                    <img src="<?php echo get_template_directory_uri(); ?>/img/mmlc.png" alt="Multimedia Learning Center Logo">
                </a>
            </div>
        </footer>

        <?php wp_footer(); ?>

        <script>
            LM.init(true);

            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-67421300-1', 'auto');
            ga('send', 'pageview');
        </script>
    </body>
</html>