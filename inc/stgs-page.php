<?php
$log = (isset($_GET['ze_scope']) && $_GET['ze_scope'] == 'log') ? true : false;
$wel_actv = 'nav-tab-active';
$log_actv = '';
if ($log) {
    $wel_actv = '';
    $log_actv = 'nav-tab-active';
    if (isset($_GET['action']) && $_GET['action'] == 'clear') {
        update_option('ze_hxr_errors', array());
    }
    $ajax_errors = get_option('ze_hxr_errors', array());
}else{
require(ABSPATH . 'wp-admin/options-head.php');
}
?>
<div class="wrap">
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=zero-errors') ?>" class=" nav-tab <?php echo $wel_actv ?> "><?php _e('Welcome','0e') ?></a>
        <a href="<?php echo admin_url('admin.php?page=zero-errors&ze_scope=log') ?>" class="nav-tab <?php echo $log_actv ?> "><?php _e('Ajax Log','0e') ?></a>
    </h2>

    <br/>
    <!-- <h2><?php _e('0e Plugin Base', '0e'); ?></h2> -->

    <div class="ze-stgs-page">
        <div class="content alignleft">

        <!--<h2 class='error-neg ze-h2-notice'><span>Congrats!</span> You have 0 Recorded Errors! </h2> -->
        <!--<h2 class='error-pos ze-h2-notice'><span>Oh No!</span> You have Some Errors to deal with! </h2>-->

            <div id="0e-help-content">
                <?php if (!$log): ?>


                    <form id="ze-plugin-base-form" action="options.php" method="POST">

                        <?php settings_fields('ze_setting') ?>
                        <?php do_settings_sections('0e-plugin-base') ?>

                        <input class="button-primary" type="submit" value="<?php _e("Save", '0e'); ?>" />
                    </form> <!-- end of #0etemplate-form -->


                <?php else: ?>
                    <h3><?php _e('Ajax Errors','0e') ?></h3>
                    <?php _e('<p><b>Note:</b> Parse Errors are not subject to custom PHP error handleling. <br>
                        Only the latest 10 PHP Ajax errors will be stored to keep the footprint on storage space at a minimal.</p>','0e') ?>
                    
                    <p><a class="button" href="<?php echo admin_url('admin.php?page=zero-errors&ze_scope=log&action=clear') ?>"><?php _e('Clear','0e') ?></a> <a class="button-primary"><?php _e('Help','0e') ?></a></p>
                    <?php if (!empty($ajax_errors)): ?>
                        <div class="ze-hxr_errs">
                            <ul>
                                <?php 
								$ajax_errors = array_reverse($ajax_errors,1);
								foreach ($ajax_errors as $time=>$jxerr): ?> 
                                    <li><?php echo $jxerr.' | '.date_i18n(_x('Y-m-d G:i:s', 'timezone date format'), $time).' |';  ?></li>
                                <?php endforeach ?>
                            </ul>
                        <?php else: ?>
                            <div class="ze-no_errs">
                               <?php _e('No Errors Recorded','0e') ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <footer class='ze-footer'>
                    <a href="https://wordpress.org/support/plugin/0-errors/" target="_blank"><?php _e('Plugin Help','0e') ?></a>
                </footer>

            </div>
            <div class="sidebar alignright">
                <h2><?php _e('About the plugin','0e') ?></h2>
                <p><?php echo sprintf(__('This plugin is maintained by %s , a Senior  WordPress Developer.','0e'),'<a href="https://wordpress.org/plugins/0-errors/" target="_blank">Mucunguzi Ayebare</a>') ?></p>
                <p><?php echo sprintf(__('Rate it at %s','0e'),'<a href="https://wordpress.org/plugins/0-errors/" target="_blank">WordPress.org</a>') ?> </a> </p>
            </div>
        </div>

    </div>