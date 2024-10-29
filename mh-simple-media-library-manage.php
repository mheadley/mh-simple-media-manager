<?php /*
Plugin Name: Media Locations
Author: Michael Headley
Author URI: https://mheadley.com
Plugin URI: https://mheadley.com/developing/wordpress-plugins/wp-simple-media-locations-management-with-real-folders/
Description:  Easily assign locations, filter media by location and manage them via a category tree structure under Media Locations.  This plugin  adds a dropdown select to all media upload forms and a filter to attachments browsers. The selected location is the folder in which the file will be uploaded. Modifying location position/structure will <strong>NOT</strong> move images after intial upload.  Please see plugin site for details.
Version: 1.2.0
License:  GPL v3

*/


class MH_Media_Locations 
{
    protected static $instance = NULL;
    public $post_type;
    public $taxonomies;
    public $currentUploadBase;
    public $currentUploadLocation;

    /**
     * Used for regular plugin work.
     *
     * @wp-hook plugins_loaded
     * @return  void
     */
    public function plugin_setup()
    {
        // add taxonmies
        add_action( 'init', array( $this, 'mh_register_media_locations' ) );
        // Taxonomies filter
        add_action( 'init', array( $this, 'mh_media_locations_setup' ) );

        add_action( 'add_attachment', array( $this, 'mh_register_media_locations_auto_tax' ), 10, 2 );

        add_filter('wp_handle_upload_prefilter', array( $this, 'mh_register_media_locations_upload_before_filter' ), 10 );	
        
        add_filter('wp_handle_upload',array( $this, 'mh_register_media_locations_upload_remove_filter' ), 10, 2 );
        
       
        add_action( 'post-upload-ui', array($this, 'mh_register_media_locations_enqueue_media_upload'), 10, 1);  
        add_action( 'wp_enqueue_media', array($this, 'mh_register_media_locations_enqueue_media_managed'), 10, 1 ); 
    }



    /**
     * Constructor, init the functions inside WP
     *
     * @since   1.0.0
     * @return  voidloca
     */
    public function __construct($post_type) {
        $this->post_type = $post_type;
        add_action(
            'plugins_loaded',
            array ( $this, 'plugin_setup')
        );
    }

    /**
     * Handler for the action 'init'. Instantiates this class.
     *
     * @since   1.0.0
     * @access  public
     * @return  $instance
     */
    public function get_object() 
    {
        NULL === self::$instance and self::$instance = new self;
        return self::$instance;
    }

    /**
     * Setup Taxonomies
     * Creates 'attachment_tag' and 'attachment_location' taxonomies.
     * Enhance via filter `mh_simple_media_location_taxonomies`
     * 
     * @uses    register_taxonomy, apply_filters
     * @since   1.0.0
     * @return  void
     */
    public function mh_register_media_locations() 
    {
        $mh_simple_media_location_taxonomies = array();


        $labels = array(
            'name'              => _x( 'Media Tags', 'taxonomy general name', 'mh_simple_media_manage' ),
            'singular_name'     => _x( 'Media Tag', 'taxonomy singular name', 'mh_simple_media_manage' ),
            'search_items'      => __( 'Search Media Tags', 'mh_simple_media_manage' ),
            'all_items'         => __( 'All Media Tags', 'mh_simple_media_manage' ),
            'parent_item'       => __( 'Parent Media Tag', 'mh_simple_media_manage' ),
            'parent_item_colon' => __( 'Parent Media Tag:', 'mh_simple_media_manage' ),
            'edit_item'         => __( 'Edit Media Tag', 'mh_simple_media_manage' ), 
            'update_item'       => __( 'Update Media Tag', 'mh_simple_media_manage' ),
            'add_new_item'      => __( 'Add New Media Tag', 'mh_simple_media_manage' ),
            'new_item_name'     => __( 'New Media Tag Name', 'mh_simple_media_manage' ),
            'menu_name'         => __( 'Media Tags', 'mh_simple_media_manage' ),
        );

        $args = array(
            'hierarchical' => FALSE,
            'labels'       => $labels,
            'show_ui'      => TRUE,
            'show_admin_column' => FALSE,
            'query_var'    => TRUE,
            'rewrite'      => TRUE,
            'show_in_rest' => TRUE
        );

        $mh_simple_media_location_taxonomies[] = array(
            'taxonomy'  => 'attachment_tag',
            'post_type' => 'attachment',
            'args'      => $args
        );

        
  // Locations
        $labels = array(
            'name'              => _x( 'Media Locations', 'taxonomy general name', 'mh_simple_media_manage' ),
            'singular_name'     => _x( 'Media Location', 'taxonomy singular name', 'mh_simple_media_manage' ),
            'search_items'      => __( 'Search Media Locations', 'mh_simple_media_manage' ),
            'all_items'         => __( 'All Media Locations', 'mh_simple_media_manage' ),
            'parent_item'       => __( 'Parent Media Location', 'mh_simple_media_manage' ),
            'parent_item_colon' => __( 'Parent Media Location:', 'mh_simple_media_manage' ),
            'edit_item'         => __( 'Edit Media Location', 'mh_simple_media_manage' ), 
            'update_item'       => __( 'Update Media Location', 'mh_simple_media_manage' ),
            'add_new_item'      => __( 'Add New Media Location', 'mh_simple_media_manage' ),
            'new_item_name'     => __( 'New Media Location Name', 'mh_simple_media_manage' ),
            'menu_name'         => __( 'Media Locations', 'mh_simple_media_manage' ),
        );

        $args = array(
            'hierarchical' => TRUE,
            'labels'       => $labels,
            'show_ui'      => TRUE,
            'query_var'    => TRUE,
			'rewrite'      => TRUE,
			'public' 	   => FALSE,
			'show_in_rest' => TRUE,
            'show_in_menu' => TRUE,
            'meta_box_cb' => FALSE,
            'show_admin_column' => FALSE,

        );

        $mh_simple_media_location_taxonomies[] = array(
            'taxonomy'  => 'attachment_location',
            'post_type' => 'attachment',
            'args'      => $args
        );
        $mh_simple_media_location_taxonomies = apply_filters( 'fb_mh_simple_media_location_taxonomies', $mh_simple_media_location_taxonomies );
        foreach ( $mh_simple_media_location_taxonomies as $attachment_taxonomy ) {
            register_taxonomy(
                $attachment_taxonomy['taxonomy'],
                $attachment_taxonomy['post_type'],
                $attachment_taxonomy['args']
            );
        }
    }

    public function mh_media_locations_setup()
    {
        add_action('post-html-upload-ui', array( $this, 'mh_media_locations_get_select_mvc' ), 20 );
        add_action('post-plupload-upload-ui', array( $this, 'mh_media_locations_get_select_mvc' ), 20 );
        add_action( current_filter(), array( $this, 'mh_media_locations_setup_vars' ), 20 );
        add_action( 'restrict_manage_posts', array( $this, 'mh_media_locations_get_select' ) );
    }

    public function mh_media_locations_setup_vars()
    {
        $this->currentUploadBase = wp_get_upload_dir()["subdir"];
        $this->post_type = 'attachment';
        $this->taxonomies = get_object_taxonomies( $this->post_type );
    }


    public function mh_media_locations_get_select()
    {
        $walker = new MHSML_walker;
        foreach ( $this->taxonomies as $tax )
        {
            if(is_taxonomy_hierarchical( $tax )){
                wp_dropdown_categories( array(
                    'taxonomy'        => $tax
                    ,'hide_if_empty'   => false
                    //,'parent' => false
                    ,'show_option_all' => "Any Location"
                    ,'hide_empty'      => false
                    ,'hierarchical'    => is_taxonomy_hierarchical( $tax )
                    ,'show_count'      => false
                    ,'orderby'         => 'name'
                    ,'selected'        => '0' !== get_query_var( $tax )
                        ? get_query_var( $tax )
                        : false
                    ,'name'            => $tax
                    ,'id'              => $tax
                    ,'walker'          => $walker
                    ///,'echo'            => false
                ) );
            }
        }
    }

    public function mh_media_locations_get_select_mvc()
    {
        $walker = new MHSML_walkerMVC;
        foreach ( $this->taxonomies as $tax )
        {
            if(is_taxonomy_hierarchical( $tax )){
                wp_dropdown_categories( array(
                    'taxonomy'        => $tax
                    ,'hide_if_empty'   => false
                    //,'parent' => false
                    ,'show_option_all' => "Root Location"
                    ,'hide_empty'      => false
                    ,'hierarchical'    => is_taxonomy_hierarchical( $tax )
                    ,'show_count'      => false
                    ,'orderby'         => 'name'
                    ,'selected'        => '0' !== get_query_var( $tax )
                        ? get_query_var( $tax )
                        : false
                    ,'name'            => $tax
                    ,'id'              => $tax
                    ,'walker'          => $walker
                    ///,'echo'            => false
                ) );
            }
        }
    }

    
    public function mh_register_media_locations_auto_tax($id) {
        $post = get_post($id);
        if($post->post_type != 'attachment')
        return false;

        if( isset($_REQUEST['mediaLocation']) ) {
            wp_set_post_terms( $id, array($_REQUEST['mediaLocation']),  'attachment_location');

        }   
    }
        

    public function mh_register_media_locations_upload_dir($path){	
        if(!empty($path['error'])) { return $path; } //error; do nothing.
        if( isset($_REQUEST['mediaLocation']) ) {
            $customdir = "/" . $this->mh_register_media_locations_upload_location_build($_REQUEST['mediaLocation']); 
        } 
        else{
            return $path;
         }
        $path['path'] 	 = str_replace($path['subdir'], '', $path['path']); //remove default subdir (year/month)
        $path['url']	 = str_replace($path['subdir'], '', $path['url']);		
        $path['subdir']  = $customdir;
        $path['path'] 	.= $customdir; 
        $path['url'] 	.= $customdir;	
        return $path;
    }
    public function mh_register_media_locations_upload_location_build($id){
        if($id < 1){return "";}
        $all_tax_terms = get_terms( 'attachment_location', array( 'hide_empty' => false ));
        $term = array_values(get_terms( 'attachment_location', array( 'hide_empty' => false, 'include'=> array($id) )))[0];
        return $term && $term->parent > 0 ? $this->getParentSlug($term->parent, $all_tax_terms) . $this->mh_register_media_locations_term_slug_parse($term->slug) : $this->mh_register_media_locations_term_slug_parse($term->slug);
    }
    public function mh_register_media_locations_term_slug_parse($term){
        $termArray = explode("_", $term);
        return $termArray[count($termArray) - 1];
    }
   
    //* @param array $file Reference to a single element of $_FILES. Call the function once for each uploaded file.
    public function mh_register_media_locations_upload_before_filter($file){	

        if( isset($_REQUEST['mediaLocation']) ) {
            //$this->$currentUploadLocation = $_REQUEST['mediaLocation'];
            add_filter('upload_dir',  array( $this, 'mh_register_media_locations_upload_dir'));
        }
        return $file;
    }

    function mh_register_media_locations_upload_remove_filter($file){	
        remove_filter('upload_dir',  array( $this, 'mh_register_media_locations_upload_dir'));
        return $file;
    }


    function mh_register_media_locations_enqueue_media_upload() { 
        wp_register_script('mh-media-location-upload', plugins_url('mh-simple-media-library-upload.js' , __FILE__ ), array('media-editor', 'media-views'));

        wp_enqueue_script('mh-media-location-upload');
    }
        
      
    function mh_register_media_locations_enqueue_media_managed() { 
        wp_register_script('mh-media-location-managed', plugins_url('mh-simple-media-library-manage.js' , __FILE__ ), array( 'media-editor', 'media-views' ) );

       wp_enqueue_style( 'mh-media-location-managed-styles',  plugins_url('mh-simple-media-library-manage.css', __FILE__  ),array() , get_plugin_data( __FILE__ )['Version'], 'all' );
       
        wp_localize_script( 'mh-media-location-managed', 'MediaLibraryTaxonomyFilterData', array(
            'terms'     => get_terms( 'attachment_location', array( 'hide_empty' => false ) ),
        ) );

        wp_enqueue_script('mh-media-location-managed');

        add_action( 'admin_footer', function(){
            ?>
            <style>
            .media-modal-content .media-frame select.attachment-filters {
                max-width: -webkit-calc(33% - 12px);
                max-width: calc(33% - 12px);
            }
            body.block-editor-page .media-modal-content .media-frame select.attachment-filters:nth-of-type(2){
                max-width: 40%;
            }
            </style>
            <?php
        });
    }
    function getParentSlug($id, $termsArray){
        $term;
        foreach($termsArray as $termItem){   
            if($termItem->term_id === $id){
                $term = $termItem;
            }
          }
          if($term){
            if($term->parent){
                return $this->getParentSlug($term->parent, $termsArray) . $this->mh_register_media_locations_term_slug_parse($term->slug) . '/';
              }
              return $this->mh_register_media_locations_term_slug_parse($term->slug) . '/';
          }

    }

} 

class MHSML_walker extends Walker_CategoryDropdown
{
    var $tree_type = 'category';
    var $db_fields = array(
         'parent' => 'parent'
        ,'id'     => 'term_id'
    );
    public $tax_name;


    function getParentLabel($id, $termsArray){
        $term;
        foreach($termsArray as $termItem){   
            if($termItem->term_id === $id){
                $term = $termItem;
            }
        }
        if($term){
            if($term->parent){
                return $this->getParentLabel($term->parent, $termsArray) . $term->name . '/';
            }
            return $term->name . '/';
        }

    }
    function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 )
    {
        
        $all_tax_terms = get_terms( 'attachment_location', array( 'hide_empty' => false ));
        $pad = $term->parent > 0 ? $this->getParentLabel($term->parent, $all_tax_terms) : '';

        $output .= sprintf(
             '<option class="level-%s" value="%s" %s>%s%s</option>'
            ,$depth
            ,$term->slug
            ,selected(
                 $args['selected']
                ,$term->slug
                ,false
             )
            ,"{$pad}{$term->name}"
            ,$args['show_count']
                ? "&nbsp;&nbsp;({$term->count})"
                : ''
        );
    }



}


class MHSML_walkerMVC extends Walker_CategoryDropdown
{
    var $tree_type = 'category';
    var $db_fields = array(
         'parent' => 'parent'
        ,'id'     => 'term_id'
    );
    public $tax_name;
   
    function getParentLabel($id, $termsArray){
        $term;
        foreach($termsArray as $termItem){   
            if($termItem->term_id === $id){
                $term = $termItem;
            }
          }
          if($term){
            if($term->parent > 0){
                return  $this->getParentLabel($term->parent, $termsArray)  . $term->name  .  '/';
              }
            return  $term->name .   '/';
          }

    }
    function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 )
    {
        
        $all_tax_terms = get_terms( 'attachment_location', array( 'hide_empty' => false ));
        $pad = $term->parent > 0 ? $this->getParentLabel($term->parent, $all_tax_terms) : '';

        $output .= sprintf(
             '<option class="level-%s" value="%s" %s>%s%s</option>'
            ,$depth
            ,$term->term_id
            ,selected(
                 $args['selected']
                ,$term->term_id
                ,false
             )
            ,"{$pad}{$term->name}"
            ,$args['show_count']
                ? "&nbsp;&nbsp;({$term->count})"
                : ''
        );
    }



}

$mh_location_metabox = new MH_Media_Locations('media_managing');


