<?php
/* @var $EM_Event EM_Event */

$count_cats = count($EM_Event->get_categories()->categories) > 0;
global $post;

if( $count_cats > 0 ){              
    ?>
    <ul class="event-categories">
        <?php foreach($EM_Event->get_categories() as $EM_Category): ?>
            <li><?php echo $EM_Category->output("#_CATEGORYLINK"); ?></li>
        <?php endforeach; ?>
    </ul>
    <?php    
}else{
    global $post;
    $vacation_pid = get_post_meta($post->ID, '_vacation_pid', true);   
    if ($vacation_pid != "") {
        $categories = get_the_category( $vacation_pid );
        if (!empty($categories)) {        
            foreach ($categories as $category)    
                $cat[] = $category->cat_name;        
            echo implode(", ", $cat);
        } else {
            echo get_option ( 'dbem_no_categories_message' );    
        }
        
        
    } else {
        echo get_option ( 'dbem_no_categories_message' );    
    }
    
}