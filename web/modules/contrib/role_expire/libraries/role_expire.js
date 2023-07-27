/**
 * @file
 * Role Expire js
 *
 * Set of jQuery related routines.
 */

(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.role_expire = {
    attach: function (context, settings) {
      once('role_expire', 'html', context).forEach( function (element) {

        jQuery('input.role-expire-role-expiry', context).parent().hide();

        // No key change needed if Role Assign module is used.
        rolesKey = 'roles';
        if (jQuery('#edit-role-change').length > 0) {
          // Role Delegation module is used.
          var rolesKey = 'role-change';
        }

        jQuery('#edit-' + rolesKey + ' input.form-checkbox', context).each(function() {
          var textfieldId = this.id.replace(rolesKey, "role-expire");

          // Move all expiry date fields under corresponding checkboxes
          jQuery(this).parent().after(jQuery('#'+textfieldId).parent());

          // Show all expiry date fields that have checkboxes checked
          if (jQuery(this).attr("checked")) {
            jQuery('#'+textfieldId).parent().show();
          }
        });

        jQuery('#edit-' + rolesKey + ' input.form-checkbox', context).click(function() {

          var textfieldId = this.id.replace(rolesKey, "role-expire");

          jQuery('#'+textfieldId).parent().toggle();
        });
      })
    }
  }
} (Drupal, drupalSettings, once));
