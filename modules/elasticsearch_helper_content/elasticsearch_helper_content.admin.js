(function ($, Drupal) {
  Drupal.behaviors.elasticsearchHelperContentSettingsForm = {
    attach: function attach(context) {
      var form = $('.elasticsearch-helper-content-settings-form', context);

      var triggerFields = function (e) {
        var $target = typeof e.target !== 'undefined' ? $(e.target) : $(this);
        var $bundleSettings = $target.closest('.bundle-settings');
        var $settings = $bundleSettings.nextUntil('.bundle-settings');
        var $fieldSettings = $settings.filter('.field-settings');

        if ($target.is(':checked')) {
          $settings.show();
        } else {
          $fieldSettings.find('.index:input').prop('checked', false);
          $settings.hide();
        }
      };

      $(form).find('.bundle-settings .index:input').once('bundle-settings').each(triggerFields).on('click', triggerFields);
    }
  };
})(jQuery, Drupal);
