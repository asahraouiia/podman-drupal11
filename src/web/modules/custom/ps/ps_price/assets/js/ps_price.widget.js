/**
 * @file
 * Price widget AJAX sync behavior.
 *
 * Listens to changes on transaction_field_candidates (field_operation_code, etc.)
 * and triggers AJAX refresh of price widget to update unit/period based on new rule.
 */

(($, Drupal, drupalSettings, once) => {
  'use strict';

  // Debounce timers per price widget wrapper id to avoid rapid successive
  // AJAX calls when users toggle transaction fields quickly.
  const debounceTimers = {};

  /**
   * Synchronize price widget with transaction type field changes.
   */
  Drupal.behaviors.psPriceWidgetSync = {
    attach(context) {
      const settings = drupalSettings.ps_price || {};
      const candidates = settings.transaction_field_candidates || [];

      if (!candidates.length) {
        return;
      }

      // For each transaction field candidate, listen to changes.
      candidates.forEach(fieldName => {
        // Match both select and radio inputs for this field.
        const selectors = [
          `select[name^="${fieldName}"]`,
          `input[type="radio"][name^="${fieldName}"]`
        ].join(', ');

        // IMPORTANT: Search in document, not just context, because transaction field
        // is outside the AJAX-replaced price wrapper.
        const elements = once('ps-price-sync', selectors, document);
        
        $(elements).on('change', function() {
          // Find all price widget AJAX triggers in the same form.
          const $form = $(this).closest('form');
          const $ajaxTriggers = $form.find('[data-ps-price-ajax-trigger]');

          // Trigger change on each price widget to refresh with new rule,
          // but debounce per wrapper to avoid multiple AJAX calls.
          $ajaxTriggers.each(function() {
            const $trigger = $(this);
            const wrapperId = $trigger.attr('data-ps-price-ajax-trigger') || 'global';

            if (debounceTimers[wrapperId]) {
              clearTimeout(debounceTimers[wrapperId]);
            }

            debounceTimers[wrapperId] = setTimeout(() => {
              // Trigger the AJAX-enabled hidden button.
              $trigger.trigger('click');
            }, 200);
          });
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings, once);
