<?php
/**
 * This class creates a folder object. The $type variable defines,
 * if it is a:
 * 
 * RML_TYPE_FOLDER
 * RML_TYPE_COLLECTION
 * RML_TYPE_GALLERY
 * 
 * @author MatthiasWeb
 * @since 1.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class RML_Folder {
    
    /**
     * =================
     * == @atInit
     * This Parameters are loaded through the constructor
     * automatically without any parsing.
     */
    
    /**
     * Autogenerated folder id
     * 
     * @atInit
     */
    public $id;
    
    /**
     * The parents folder ID. If it is root, then the id will
     * be -1 or the constant RML_TYPE_ROOT
     * 
     * @atInit
     */
    public $parent;
    
    /**
     * The name for the folder
     * 
     * @atInit
     */
    public $name;
    
    /**
     * The size of the folder. Means the amount of files in this
     * folder
     * 
     * @atInit
     */
    private $cnt;
    
    /**
     * The order value. It always changes if the user rearranges
     * the folders.
     * 
     * @atInit
     */
    public $order;
    
    /**
     * Defines the RML_TYPE_...
     * 
     * @atInit
     */
    public $type;
    
    /**
     * The slug of this folder for URLs, use getter.
     * 
     * @atInit
     */
    private $slug;
    
    /**
     * The absolute path to this folder, use getter.
     * 
     * @atInit
     */
    private $absolutePath;
    
    /**
     * The full row of the SQL query.
     * 
     * @filter RML/Tree/SQLStatement/SELECT
     * @atInit
     */
    private $row;
    
    /**
     * This Parameters are parsed or are coming from another source.
     */
    
     /**
     * An array of childrens RML_Folder object. It is filled automatically
     * from the Structure class.
     * 
     * @see RML_Structure
     */
    public $children;
    
    /**
     * An array of restrictions for this folder.
     * 
     * @see this::_construct
     * @see RML_Permissions
     */
    public $restrictions = array();
    public $restrictionsCount = 0;
    
    /**
     * C'tor
     */
    public function __construct($id, $parent, $name, $slug, $absolute, $order, $type, $cnt, $row) {
        // @atInit
        $this->id = $id;
        $this->parent = $parent;
        $this->name = $name;
        $this->cnt = $cnt >= 0 ? $cnt : 0;
        $this->order = $order;
        $this->type = $type;
        $this->children = array();
        $this->slug = $slug;
        $this->absolutePath = $absolute;
        $this->row = $row;
        
        // Parse the restrictions
        if (isset($row->restrictions) && is_string($row->restrictions) && strlen($row->restrictions) > 0) {
            $this->restrictions = explode(',', $row->restrictions);
            $this->restrictionsCount = count($this->restrictions);
        }
    }
    
    /**
     * Insert an amount of post ID's (attachments) to this folder.
     * 
     * @param $ids Array of post ids
     * @param $supress_validation Supress the permission validation
     * @return true or Array with errors
     */
    public function insert($ids, $supress_validation = false) {
        $errors = array();
        if (is_array($ids) && $this->type != 1) {
            $validIds = array();
            foreach ($ids as $value) {
                // If it is a gallery, there are only images allowed
                if ($this->type == 2 && !wp_attachment_is_image($value)) {
                    $errors[] = __('You can only move images to a gallery.', RML_TD);
                    continue;
                    
                }else{
                    // Check if other fails are counted
                    if ($supress_validation === false) {
        	            $validation = apply_filters("RML/Validate/Insert", array(), $value, $this);
        	            if (count($validation) > 0) {
        	                $errors = array_merge($errors, $validation);
        	                return $errors;
        	            }
                    }
                    
                    $validIds[] = $value;
                }
            }
            
            // Get the folder IDs of the attachments
            $foldersToUpdate = wp_attachment_folder($validIds);
            
            // Update the folder
            foreach ($validIds as $value) {
                update_post_meta($value, "_rml_folder", $this->id);
            }
            $foldersToUpdate[] = $this->id;
            wp_rml_update_count($foldersToUpdate);
        }else{
            $errors[] = __('Something went wrong.', RML_TD);
        }
        
        return count($errors) > 0 ? array_unique($errors) : true;
    }
    
    /**
     * Fetch all attachment ids currently in this folder.
     * 
     * @return array of post ids
     */
    public function read($order = null, $orderby = null) {
        return self::sFetchFileIds($this->id, $order, $orderby);
    }
    
    /**
     * Returns a santitized title for the folder. If the slug is empty
     * or forced to, it will be updated in the database, too.
     * 
     * @param force Forces to regenerate the slug
     * @return string slug
     */
    public function slug($force = false) {
        if ($this->slug == "" || $force) {
            $this->slug = sanitize_title($this->name, "", "folder");
            
            // Update in database
            //error_log("Update slug " . $this->slug);
            global $wpdb;
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET slug=%s WHERE id = %d", $this->slug, $this->id));
        }
        
        return $this->slug;
    }
    
    /**
     * Creates a absolute path. If the absolute path is empty
     * or forced to, it will be updated in the database, too.
     * 
     * @param force Forces to regenerate the absolute path
     * @return string path
     */
    public function absolutePath($force = false) {
        if ($this->absolutePath == "" || $force) {
            $return = array($this->slug());
            $folder = $this;
            while (true) {
                $f = RML_Structure::getInstance()->getFolderByID($folder->parent);
                if ($f !== null) {
                    $folder = $f;
                    $return[] = $folder->slug();
                }else{
                    break;
                }
            }
            $this->absolutePath = implode("/", array_reverse($return));
            
            // Update in database
            //error_log("Update absolute " . $this->absolutePath);
            global $wpdb;
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET absolute=%s WHERE id = %d", $this->absolutePath, $this->id));
        }
        return $this->absolutePath;
    }
    
    /**
     * Creates a absolute path without slugging' the names.
     * 
     * @return string path
     */
    public function absolutePathNormalized($implode = "/") {
        $return = array($this->name);
        $folder = $this;
        while (true) {
            $f = RML_Structure::getInstance()->getFolderByID($folder->parent);
            if ($f !== null) {
                $folder = $f;
                $return[] = $folder->name;
            }else{
                break;
            }
        }
        return implode($implode, array_reverse($return));
    }
    
    /**
     * Checks, if this folder has a children with the name.
     *  
     * @param $slug String Slug or Name of folder
     * @param $isSlug boolean Set it to false, if the slug is not santizied (@see $this->slug())
     * @return boolean true/false
     */
    public function hasChildSlug($slug, $isSlug = true) {
        if (!$isSlug) {
            $slug = sanitize_title($slug, "", "folder");
        }
        
        foreach ($this->children as $value) {
            if ($value->slug() == $slug) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * It iterates all chrildrens of this folder recursivly and
     * updates the absolute path.
     * 
     * @recursive through all children folders
     */
    public function updateThisAndChildrensAbsolutePath() {
        // Update childrens
        if (is_array($this->children) && count($this->children)) {
            foreach ($this->children as $key => $value) {
                $value->updateThisAndChildrensAbsolutePath();
            }
        }
        
        // Update this absolute path
        $this->absolutePath(true);
    }
    
    /**
     * Changes the parent folder of this folder. This function should
     * only be called through the AJAX function wp_ajax_bulk_sort.
     * 
     * The action RML/Structure/Rebuild will update the absolute path of the whole
     * structure in the database. Please call this after setParent()!
     * 
     * @return boolean true = Parent could be changed
     */
    public function setParent($id, $ord = 99, $force = false) {
        if ($force || RML_Structure::getInstance()->isAllowedTo($id, $this->type)) {
            $oldParent = $this->parent;
            
            $this->parent = $id;
            
            global $wpdb;
            
            // Save in database
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET parent=%d, ord=%d WHERE id = %d", $id, $ord, $this->id));
            
            // Reset
            $this->absolutePath = "";
            
            // Update children in parents
            // Update will be processed in action RML/Structure/Rebuild
            
            do_action('RML/Folder/Moved', $this, $id, $ord, $force);
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Renames a folder and then checks, if there is no duplicate folder in the
     * parent folder.
     * 
     * @param $name String New name of the folder
     * @param $supress_validation Supress the permission validation
     * @return true or Array with errors
     */
    public function setName($name, $supress_validation = false) {
        if (strpbrk($name, "\\/?%*:|\"<>") === FALSE && $this->id > 0 && strlen(trim($name)) > 0) {
            if (RML_Structure::getInstance()->hasChildSlug($this->parent, $name, false)) {
                return array(__("There is already a folder with this name.", RML_TD));
            }
            
            if ($supress_validation === false) {
	            $validation = apply_filters("RML/Validate/Rename", array(), $name, $this);
	            if (count($validation) > 0) {
	                return $validation;
	            }
            }
            
            global $wpdb;
            
            // Reset
            $this->name = $name;
            $this->slug(true);
            $this->updateThisAndChildrensAbsolutePath();
            
            // Save in Database
            $table_name = RML_Core::getInstance()->getTableName();
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET name=%s WHERE id = %d", $name, $this->id));
            
            do_action('RML/Folder/Renamed', $name, $this);
            return true;
        }else{
            return array(__("Please use a valid folder name.", RML_TD));
        }
    }
    
    /**
     * Set restrictions for this folder. See the class RML_Permissions for more infos.
     * 
     * @param $restrictions Array with restrictions
     * @return boolean
     */
    public function setRestrictions($restrictions = array()) {
        global $wpdb;
        
        // Reset
        $this->restrictions = $restrictions;
        $this->restrictionsCount = count($this->restrictions);
        
        // Save in Database
        $table_name = RML_Core::getInstance()->getTableName();
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET restrictions=%s WHERE id = %d", implode(",", $restrictions), $this->id));
    }

    /**
     * Check if folder is a RML_TYPE_...
     * 
     * @param $folder_type (@see ./real-media-library.php for Constant-Types)
     * @return boolean
     */
    public function is($folder_type) {
        return $this->type == $folder_type;
    }
    
    /**
     * Checks, if this folder has a special restriction.
     * 
     * @param $restriction The restriction to check
     * @return boolean
     * @see this::$restrictions
     */
    public function isRestrictFor($restriction) {
        return in_array($restriction, $this->restrictions) || in_array($restriction . ">", $this->restrictions);
    }
    
    public function getType() {
        return $this->type;
    }
    
    /**
     * Gets the count of the files in this folder.
     * 
     * @return int
     */
    public function getCnt($forceReload = false) {
        if ($this->cnt === null || $forceReload) {
            $query = new RML_WP_Query_Count(
                apply_filters('RML/Folder/QueryCountArgs', array(
                	'post_status' => 'inherit',
                	'post_type' => 'attachment',
                	'rml_folder' => $this->id
                ))
            );
            if (isset($query->posts[0])) {
                $this->cnt = $query->posts[0];
            }else{
                $this->cnt = 0;
            }
        }
        return $this->cnt;
    }
    
    /**
     * Returns childrens of this folder.
     * 
     * @return array of RML_Folder
     */
    public function getChildrens() {
        return $this->children;
    }
    
    /**
     *
     * Get the full row of the SQL query.
     * 
     * @param $field The field name
     * @return Any object or false
     * @filter RML/Tree/SQLStatement/SELECT
     */
    public function getRowData($field = null) {
        if (is_object($this->row)) {
            if ($field == null) {
                return $this->row;
            }else{
                return $this->row->$field;
            }
        }else{
            return false;
        }
    }
    
    /**
     * ================================
     *          STATIC!
     * ================================
     */
    public static function sFetchFileIds($id, $order = null, $orderby = null) {
        $args = array(
        	'post_status' => 'inherit',
        	'post_type' => 'attachment',
        	'posts_per_page' => -1,
        	/*'meta_query' => array( array( 'key' => '_rml_folder', 'value' => $id, 'compare' => '=' )),*/
	        'rml_folder' => $id,
	        'fields' => 'ids'
        );
        
        // Set orders
        if ($order !== null) {
            $args["order"] = $order;
        }
        if ($orderby !== null) {
            $args["orderby"] = $orderby;
        }
        
        $args = apply_filters('RML/Folder/QueryArgs', $args);
        $query = new WP_Query($args);
        $posts = $query->get_posts();
        $posts = apply_filters('RML/Folder/QueryResult', $posts);
        return $posts;
    }
    
    /**
     * ================================
     *          DEPRECATED!
     * Because I changed the names of the methods to be conform
     * with the permissions.
     * ================================
     */
    /**
     * @deprecated this::insert
     */
    public function moveItemsHere($ids) {
        $this->insert($ids);
    }
    
    /**
     * @deprecated this::read
     */
    public function fetchFileIds($order = null, $orderby = null) {
        return self::sFetchFileIds($this->id, $order, $orderby);
    }
}

?>