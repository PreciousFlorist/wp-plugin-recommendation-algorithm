<?php
// Check if the file is being accessed directly
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function form_backup_db()
{
?>
    <div class="postbox">

        <h2 class="hndle"><span>Backup Database</span></h2>
        <div class="inside">
            <form method="post" action="">
                <?php wp_nonce_field('backup_db_action', 'backup_db_nonce_field'); ?>
                <input type="submit" name="backup_db_tables" class="button button-primary" value="Download All Tables" />
            </form>
        </div>
    </div>

<?php
}
