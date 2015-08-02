<h4><?php echo $first_name . " " . $last_name . " has cancelled his/her PTO."; ?></h4>
<b>PTO period:</b> <?php echo $sdate . ' ' . $stime . ' - ' . $edate . ' ' . $etime; ?><br/><br/>
Click <a href="<?php echo get_edit_post_link($_GET["id"]); ?>">HERE</a> for more details.