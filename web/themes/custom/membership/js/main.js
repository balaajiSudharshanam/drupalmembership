(function ($, Drupal) {

    'use strict';




    Drupal.behaviors.main = {

        attach: function (context, settings) {

           
            console.log($("#claimnow").attr("class"));
        }

    }

})(jQuery, Drupal);