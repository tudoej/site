<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie6" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9 ]>    <html class="ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html <?php language_attributes(); ?>> <!--<![endif]-->
    <head>
        <meta charset="<?php bloginfo('charset'); ?>" />

        <title><?php wp_title(''); ?></title>

        <link rel="profile" href="http://gmpg.org/xfn/11" />
        <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <?php wp_head(); ?>

        <link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('stylesheet_url'); ?>" />

        <script>
            (function (i, s, o, g, r, a, m) {
                i['GoogleAnalyticsObject'] = r;
                i[r] = i[r] || function () {
                    (i[r].q = i[r].q || []).push(arguments)
                }, i[r].l = 1 * new Date();
                a = s.createElement(o),
                        m = s.getElementsByTagName(o)[0];
                a.async = 1;
                a.src = g;
                m.parentNode.insertBefore(a, m)
            })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');

            ga('create', 'UA-79361685-1', 'auto');
            ga('send', 'pageview');

        </script>
    </head>

    <body <?php body_class(); ?> itemscope itemtype="http://schema.org/WebPage">

        <?php appthemes_before(); ?>

        <?php appthemes_before_header(); ?>
        <?php get_header(app_template_base()); ?>
        <?php appthemes_after_header(); ?>

        <div id="content" class="container">
            <?php do_action('va_content_container_top'); ?>
            <div id="content-mid" class="row rounded">
                <div id="content-inner" class="rounded">

                    <?php load_template(app_template_path()); ?>

                    <div class="clear"></div>
                </div> <!-- /content-inner -->
            </div> <!-- /content-mid -->
        </div> <!-- /content -->

        <?php appthemes_before_footer(); ?>
        <?php get_footer(app_template_base()); ?>
        <?php appthemes_after_footer(); ?>

        <?php appthemes_after(); ?>

        <?php wp_footer(); ?>

    </body>
</html>
