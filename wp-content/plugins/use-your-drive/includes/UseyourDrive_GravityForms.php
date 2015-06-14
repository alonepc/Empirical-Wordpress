<?php
if (class_exists("GFForms")) {
  GFForms::include_addon_framework();

  class GFUseyourDriveAddOn extends GFAddOn {

    protected $_version = "1.0";
    protected $_min_gravityforms_version = "1.9";
    protected $_slug = "useyourdriveaddon";
    protected $_path = "use-your-drive/includes/UseyourDrive_GravityForms.php";
    protected $_full_path = __FILE__;
    protected $_title = "Gravity Forms Use-your-Drive Add-On";
    protected $_short_title = "Use-your-Drive Add-On";

    public function init() {
      parent::init();

      if (isset($this->_min_gravityforms_version) && !$this->is_gravityforms_supported($this->_min_gravityforms_version)) {
        return;
      }

      /* Add a Use-your-Drive button to the advanced to the field editor */
      add_filter('gform_add_field_buttons', array($this, "useyourdrive_field"));
      add_filter('admin_enqueue_scripts', array($this, "useyourdrive_extra_scripts"));

      /* Now we execute some javascript technicalitites for the field to load correctly */
      add_action("gform_editor_js", array($this, "gform_editor_js"));
      add_filter('gform_field_input', array($this, "useyourdrive_input"), 10, 5);

      /* Add a custom setting to the field */
      add_action("gform_field_standard_settings", array($this, "useyourdrive_settings"), 10, 2);

      /* Adds title to the custom field */
      add_filter('gform_field_type_title', array($this, 'useyourdrive_title'));

      /* Filter to add the tooltip for the field */
      add_filter('gform_tooltips', array($this, 'add_useyourdrive_tooltips'));

      /* Save some data for this field */
      add_action('gform_pre_submission', array($this, 'useyourdrive_pre_submission_handler'));

      /* Display values in a proper way */
      add_filter('gform_entry_field_value', array($this, 'useyourdrive_entry_field_value'), 10, 4);
      add_filter('gform_entries_field_value', array($this, 'useyourdrive_entries_field_value'), 10, 4);
      add_filter('gform_merge_tag_filter', array($this, 'useyourdrive_merge_tag_filter'), 10, 5);
    }

    public function useyourdrive_extra_scripts() {
      if (RGForms::is_gravity_page()) {
        add_thickbox();
      }
    }

    public function useyourdrive_field($field_groups) {
      foreach ($field_groups as &$group) {
        if ($group["name"] == "advanced_fields") {
          $group["fields"][] = array(
              'class' => 'button',
              'value' => 'Use-your-Drive',
              'date-type' => 'useyourdrive',
              'onclick' => "StartAddField('useyourdrive');"
          );
          break;
        }
      }
      return $field_groups;
    }

    public function gform_editor_js() {
      ?>
      <script type='text/javascript'>
        jQuery(document).ready(function ($) {
          /* Which settings field should be visible for our custom field*/
          fieldSettings["useyourdrive"] = ".label_setting, .description_setting, .admin_label_setting, .error_message_setting, .css_class_setting, .visibility_setting, .label_placement_setting, .useyourdrive_setting"; //this will show all the fields of the Paragraph Text field minus a couple that I didn't want to appear.

          /* binding to the load field settings event to initialize */
          $(document).bind("gform_load_field_settings", function (event, field, form) {
            if (field["UseyourdriveShortcode"] !== undefined) {
              jQuery("#field_useyourdrive").val(field["UseyourdriveShortcode"]);
            } else {
              /* Default value */
              var defaultvalue = '[useyourdrive showext="0" search="0"  upload="1" simpleupload="1" uploadrole="all" delete="1" deleterole="all" userfolders="auto"]';
              jQuery("#field_useyourdrive").val(defaultvalue);
            }
          });

          /* Shortcode Generator Popup */
          $('.UseyourDrive-GF-shortcodegenerator').click(function () {
            var shortcode = jQuery("#field_useyourdrive").val();
            shortcode = shortcode.replace('[useyourdrive ', '').replace('"]', '');
            var query = encodeURIComponent(shortcode).split('%3D%22').join('=').split('%22%20').join('&');
            tb_show("Build Shortcode for Form", ajaxurl + '?action=useyourdrive-getpopup&' + query + '&type=gravityforms&TB_iframe=true&height=600&width=800');
          });

        });
      </script>
      <?php
    }

    public function useyourdrive_input($input, $field, $value, $lead_id, $form_id) {
      if ($field->type == "useyourdrive") {
        if (!$this->is_form_editor()) {
          $return = do_shortcode($field->UseyourdriveShortcode);
          return $return;
        } else {
          $style = 'background: #176cff url(' . USEYOURDRIVE_ROOTPATH . '/css/images/shortcode_image.png) no-repeat center center; height: 150px;  width: 99%;  border: 1px solid #aaa;  outline: 0;  cursor: pointer;';
          return '<div style="' . $style . '"></div>';
        }
      }
      return $input;
    }

    public function useyourdrive_settings($position, $form_id) {
      if ($position == 1430) {
        ?>
        <li class="useyourdrive_setting field_setting">
          <label for="field_useyourdrive">Use-your-Drive Shortcode <?php echo gform_tooltip("form_field_useyourdrive"); ?></label>
          <a href="#" class='button-primary UseyourDrive-GF-shortcodegenerator '><?php echo __('Build your Use-your-Drive shortcode ', 'useyourdrive'); ?></a>
          <input type="text" id="field_useyourdrive" class="fieldwidth-3" size="35" disabled="disabled"/>
          <br/><small>Missing a Use-your-Drive Gravity Form feature? Please let me <a href="https://florisdeleeuwnl.zendesk.com/hc/en-us/requests/new" target="_blank">know</a>!</small>
        </li>
        <?php
      }
    }

    function useyourdrive_title($type) {
      if ($type === 'useyourdrive') {
        return __('Use-your-Drive Upload', 'useyourdrive');
      }
    }

    public function add_useyourdrive_tooltips($tooltips) {
      $tooltips["form_field_useyourdrive"] = "<h6>Use-your-Drive Shortcode</h6>" . __('Build here your Use-your-Drive shortcode', 'useyourdrive');
      return $tooltips;
    }

    public function useyourdrive_pre_submission_handler($form) {
      /* Get information uploaded files from hidden input */
      $filesinput = rgpost('fileupload-filelist');
      $uploadedfiles = json_decode($filesinput);

      if (($uploadedfiles !== NULL) && (count($uploadedfiles) > 0)) {
        /* Fill our custom field with the details of our upload session */
        foreach ($form['fields'] as &$field) {
          if ($field->type === 'useyourdrive') {
            $_POST["input_{$field->id}"] = $filesinput;
            break;
          }
        }
      }

      return $form;
    }

    public function useyourdrive_entry_field_value($value, $field, $lead, $form) {
      if ($field->type !== "useyourdrive") {
        return $value;
      }

      return $this->renderUploadedFiles($value);
    }

    public function useyourdrive_entries_field_value($value, $form_id, $field_id, $entry) {
      $form = RGFormsModel::get_form_meta($form_id);

      if (is_array($form["fields"])) {
        foreach ($form["fields"] as $field) {
          if ($field->type === 'useyourdrive' && $field_id == $field->id) {
            return $this->renderUploadedFiles(html_entity_decode($value));
          }
        }
      }

      return $value;
    }

    public function useyourdrive_set_export_values($value, $form_id, $field_id, $lead) {
      $form = RGFormsModel::get_form_meta($form_id);

      if (is_array($form["fields"])) {
        foreach ($form["fields"] as $field) {
          if ($field->type === 'useyourdrive' && $field_id == $field->id) {
            return $this->renderUploadedFiles(html_entity_decode($value), false);
          }
        }
      }

      return $value;
    }

    public function useyourdrive_merge_tag_filter($value, $merge_tag, $modifier, $field, $rawvalue) {

      if ($field->type == 'useyourdrive') {
        return $this->renderUploadedFiles(html_entity_decode($value));
      } else {
        return $value;
      }
    }

    public function renderUploadedFiles($data, $ashtml = true) {

      $uploadedfiles = json_decode($data);

      if (($uploadedfiles !== NULL) && (count((array) $uploadedfiles) > 0)) {
        /* Fill our custom field with the details of our upload session */
        $html = sprintf(__('%d file(s) uploaded:', 'useyourdrive'), count((array) $uploadedfiles));
        $html .= ($ashtml) ? '<ul>' : "\r\n";

        foreach ($uploadedfiles as $fileid => $file) {
          $html .= ($ashtml) ? '<li><a href="' . urldecode($file->link) . '">' : "";
          $html .= $file->path;
          $html .= ($ashtml) ? '</a>' : "";
          $html .= ' (' . $file->size . ')';
          $html .= ($ashtml) ? '</li>' : "\r\n";
        }

        $html .= ($ashtml) ? '</ul>' : "";
      } else {
        return $data;
      }

      return $html;
    }

  }

  $GFUseyourDriveAddOn = new GFUseyourDriveAddOn();
  /* This filter isn't fired if inside class */
  add_filter('gform_export_field_value', array($GFUseyourDriveAddOn, 'useyourdrive_set_export_values'), 10, 4);
}