
(function ($, Drupal) {

    'use strict';




    Drupal.behaviors.custom = {

        attach: function (context, settings) {

            // $(".alreadyexists").alert();
            console.log($("#claimnow").attr("class"));

        }

    }

})(jQuery, Drupal);