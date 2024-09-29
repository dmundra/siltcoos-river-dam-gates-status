/**
 * @file
 * Wait for round to be completed.
 */

(function ($, Drupal) {
  /**
   * Attaches the wait behavior to the wait page.
   */
  Drupal.behaviors.wait = {
    attach(context, settings) {
      function processingWait(index, value) {
        const round = settings.round;
        const roundJsonUri = settings.roundJsonUri;
        const returnUri = settings.returnUri;
        const messages = new Drupal.Message();
        const roundCompleteMessage =
          'Round is complete! Results page loading in 3 seconds.';

        if (round.completed === '1') {
          messages.add(roundCompleteMessage);
          setTimeout(() => {
            window.location = returnUri;
          }, 3000);
        }

        setInterval(() => {
          $.ajax({
            type: 'GET',
            url: roundJsonUri,
            data: '',
            dataType: 'json',
            success(updatedRound) {
              if (updatedRound.completed === '1') {
                messages.add(roundCompleteMessage);
                setTimeout(() => {
                  window.location = returnUri;
                }, 3000);
              }
            },
          });
        }, 5000);
      }

      const $wait = $(once('wait', '#wait', context));
      $wait.each(processingWait);
    },
  };
})(jQuery, Drupal);
