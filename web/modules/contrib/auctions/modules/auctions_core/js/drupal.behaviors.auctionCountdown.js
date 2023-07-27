(function ($, Drupal, drupalSettings) {
  'use strict';
  /**
   * Attaches the JS countdown behavior
   */
  Drupal.behaviors.auctionCountdown = {
    attach: function (context, drupalSettings) {
      $('.auction-countdown', context).once('auction-countdown-active').each(function () {
        var countdown = this;
        $(countdown).find('.countdown').auctionCountdown({
          timestamp: $(this).data('unix') * 1000,
          font_size: $(this).data('font-size'),
          callback: function (weeks, days, hours, minutes, seconds) {
            var dateStrings = new Array();
            dateStrings['@weeks'] = Drupal.formatPlural(weeks, '1 week', '@count weeks');
            dateStrings['@days'] = Drupal.formatPlural(days, '1 day', '@count days');
            dateStrings['@hours'] = Drupal.formatPlural(hours, '1 hour', '@count hours');
            dateStrings['@minutes'] = Drupal.formatPlural(minutes, '1 minute', '@count minutes');
            dateStrings['@seconds'] = Drupal.formatPlural(seconds, '1 second', '@count seconds');
            var message = Drupal.t('@weeks, @days, @hours, @minutes, @seconds left.', dateStrings);
            $(countdown).find('.interval').html(message);
          }
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
